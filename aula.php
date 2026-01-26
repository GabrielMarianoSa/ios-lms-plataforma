<?php
session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/partials/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ios_url('/auth/login.php'));
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$curso_id = (int)($_GET['curso_id'] ?? 0);
$aula_id = (int)($_GET['aula_id'] ?? 0);

// Fetch course and lesson
$curso = $conn->query("SELECT * FROM cursos WHERE id = {$curso_id} LIMIT 1")->fetch_assoc();
$aula = $conn->query("SELECT * FROM aulas WHERE id = {$aula_id} AND curso_id = {$curso_id} LIMIT 1")->fetch_assoc();

// Optional topic title
$topicoTitulo = null;
$colRes = $conn->query("SHOW COLUMNS FROM aulas LIKE 'topico_id'");
if ($colRes && $colRes->num_rows > 0 && !empty($aula['topico_id'])) {
    $topicoId = (int)$aula['topico_id'];
    $topicosEnabled = ios_table_exists($conn, 'topicos');
    if ($topicosEnabled) {
        $t = $conn->query("SELECT titulo FROM topicos WHERE id = {$topicoId} LIMIT 1")->fetch_assoc();
        if ($t && isset($t['titulo'])) {
            $topicoTitulo = (string)$t['titulo'];
        }
    }
}

if (!$curso || !$aula) {
    http_response_code(404);
    $pageTitle = 'Aula não encontrada';
    $activeNav = 'cursos';
    require __DIR__ . '/partials/header.php';
    echo '<div class="container"><div class="alert alert-danger">Aula não encontrada.</div></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Restrict access: enrolled or admin
$isAdmin = !empty($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
if (!$isAdmin) {
    $chk = $conn->query("SELECT status, data_inicio FROM inscricoes WHERE user_id={$user_id} AND curso_id={$curso_id} ORDER BY id DESC LIMIT 1");
    $status = null;
    $dataInicio = null;
    
    if ($chk && $chk->num_rows > 0) {
        $r = $chk->fetch_assoc();
        $status = $r ? ($r['status'] ?? null) : null;
        $dataInicio = $r['data_inicio'] ?? null;
    }

    // 1. Check Approval
    if (!$chk || $chk->num_rows === 0 || !ios_is_inscricao_aprovada((string)$status)) {
        $pageTitle = 'Acesso restrito';
        $activeNav = 'cursos';
        require __DIR__ . '/partials/header.php';
        ?>
        <div class="container">
            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-exclamation-triangle fs-5"></i>
                <div>
                    <?php if ($status !== null && $status !== ''): ?>
                        <?php $b = ios_inscricao_badge((string)$status); ?>
                        <div class="fw-semibold">Sua solicitação está: <span class="badge <?= htmlspecialchars($b['class'], ENT_QUOTES, 'UTF-8') ?>"><i class="bi <?= htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="small">
                            <?php if (ios_normalize_inscricao_status((string)$status) === 'pendente'): ?>
                                Aguarde a análise da equipe. Assim que aprovado, você terá acesso às aulas.
                            <?php else: ?>
                                Você pode solicitar novamente para este curso.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="fw-semibold">Você precisa solicitar inscrição para acessar esta aula.</div>
                        <div class="small">Envie sua solicitação e aguarde aprovação.</div>
                    <?php endif; ?>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= htmlspecialchars(ios_url('/inscrever.php?curso_id=' . (int)$curso_id), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-check2-square me-1"></i>Solicitar inscrição
                        </a>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(ios_url('/cursos.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }

    // 2. Check Start Date
    if ($dataInicio && strtotime($dataInicio) > strtotime(date('Y-m-d'))) {
        $pageTitle = 'Em Breve';
        $activeNav = 'cursos';
        require __DIR__ . '/partials/header.php';
        ?>
        <div class="container py-5">
            <div class="text-center">
                <div class="display-1 text-primary mb-3"><i class="bi bi-calendar-event"></i></div>
                <h1 class="h3 fw-bold mb-2">Quase lá!</h1>
                <p class="lead text-muted mb-4">
                    Suas aulas para este curso começam dia <strong class="text-dark"><?= date('d/m/Y', strtotime($dataInicio)) ?></strong>.
                </p>
                <div class="alert alert-info d-inline-block px-4 py-3 rounded-4 border-0 shadow-sm">
                    <i class="bi bi-info-circle-fill me-2"></i> O conteúdo será liberado automaticamente nesta data.
                </div>
                <div class="mt-5">
                    <a href="<?= ios_url('/aluno/dashboard.php') ?>" class="btn btn-primary rounded-pill px-4">Voltar para Minha Área</a>
                </div>
            </div>
        </div>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }
}

// progress
$done = false;
$res = $conn->query("SELECT id FROM progresso WHERE user_id={$user_id} AND aula_id={$aula_id} AND concluida=1 LIMIT 1");
if ($res && $res->num_rows > 0) {
    $done = true;
}

$pageTitle = (string)$aula['titulo'];
$activeNav = 'cursos';
require __DIR__ . '/partials/header.php';

// Quiz status for this user/aula
$quizAprovado = false;
$quizMsg = null;
$quizErro = null;
$quizScript = ""; // To inject JS

$hasQuiz = !empty($aula['q1_pergunta']) && !empty($aula['q2_pergunta']);
if ($hasQuiz) {
    $stmt = $conn->prepare('SELECT q1_resposta, q2_resposta, acertos, aprovado FROM aula_quiz_respostas WHERE user_id=? AND aula_id=? LIMIT 1');
    $stmt->bind_param('ii', $user_id, $aula_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $quizAprovado = !empty($row['aprovado']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_quiz'])) {
        $q1 = strtoupper(trim((string)($_POST['q1'] ?? '')));
        $q2 = strtoupper(trim((string)($_POST['q2'] ?? '')));
        $valid = ['A','B','C'];

        if (!in_array($q1, $valid, true) || !in_array($q2, $valid, true)) {
             $quizScript = "Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, selecione uma alternativa para cada pergunta.',
                confirmButtonColor: '#0d6efd'
            });";
        } else {
            $acertos = 0;
            $q1c = strtoupper((string)($aula['q1_correta'] ?? ''));
            $q2c = strtoupper((string)($aula['q2_correta'] ?? ''));
            if ($q1 !== '' && $q1c !== '' && $q1 === $q1c) $acertos++;
            if ($q2 !== '' && $q2c !== '' && $q2 === $q2c) $acertos++;
            $aprovado = ($acertos === 2) ? 1 : 0;

            $up = $conn->prepare('INSERT INTO aula_quiz_respostas (user_id, aula_id, q1_resposta, q2_resposta, acertos, aprovado) VALUES (?, ?, ?, ?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE q1_resposta=VALUES(q1_resposta), q2_resposta=VALUES(q2_resposta), acertos=VALUES(acertos), aprovado=VALUES(aprovado)');
            $up->bind_param('iissii', $user_id, $aula_id, $q1, $q2, $acertos, $aprovado);
            if (!$up->execute()) {
                 $quizScript = "Swal.fire({icon: 'error', title: 'Erro', text: 'Não foi possível salvar suas respostas.'});";
            } else {
                if ($aprovado) {
                    $quizAprovado = true;
                    // marca progresso automaticamente
                    $check = $conn->query("SELECT id FROM progresso WHERE user_id={$user_id} AND aula_id={$aula_id} LIMIT 1");
                    if (!$check || $check->num_rows == 0) {
                        $conn->query("INSERT INTO progresso (user_id, aula_id, concluida) VALUES ({$user_id}, {$aula_id}, 1)");
                    }
                    $done = true;

                    // encontrar próxima aula não concluída neste curso
                    $nextAulaId = null;
                    if (!empty($aula['ordem'])) {
                        $curOrd = (int)$aula['ordem'];
                        $stmtNext = $conn->prepare('SELECT a.id FROM aulas a WHERE a.curso_id=? AND a.ordem>? AND NOT EXISTS (SELECT 1 FROM progresso p WHERE p.user_id=? AND p.aula_id=a.id AND p.concluida=1) ORDER BY a.ordem ASC LIMIT 1');
                        if ($stmtNext) {
                            $stmtNext->bind_param('iii', $curso_id, $curOrd, $user_id);
                            $stmtNext->execute();
                            $r = $stmtNext->get_result()->fetch_assoc();
                            if ($r && isset($r['id'])) { $nextAulaId = (int)$r['id']; }
                            $stmtNext->close();
                        }
                    } else {
                        $curId = (int)$aula_id;
                        $stmtNext = $conn->prepare('SELECT a.id FROM aulas a WHERE a.curso_id=? AND a.id>? AND NOT EXISTS (SELECT 1 FROM progresso p WHERE p.user_id=? AND p.aula_id=a.id AND p.concluida=1) ORDER BY a.id ASC LIMIT 1');
                        if ($stmtNext) {
                             $stmtNext->bind_param('iii', $curso_id, $curId, $user_id);
                             $stmtNext->execute();
                             $r = $stmtNext->get_result()->fetch_assoc();
                             if ($r && isset($r['id'])) { $nextAulaId = (int)$r['id']; }
                             $stmtNext->close();
                        }
                    }
                    
                    $redirect = $nextAulaId ? ios_url("/aula.php?curso_id=$curso_id&aula_id=$nextAulaId") : ios_url("/curso.php?id=$curso_id");
                    
                    $quizScript = "Swal.fire({
                        icon: 'success',
                        title: 'Parabéns!',
                        text: 'Você acertou as 2 questões e pode concluir a aula. Avançando...',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => { window.location.href = '$redirect'; });";

                } else {
                    $quizAprovado = false;
                    $quizScript = "Swal.fire({
                        icon: 'error',
                        title: 'Tente novamente',
                        text: 'Você precisa acertar as duas questões para avançar.',
                        confirmButtonText: 'Entendi'
                    });";
                }
            }
        }
    }
}

?>

<div class="container">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="text-muted small">Curso: <?= htmlspecialchars((string)$curso['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($topicoTitulo): ?>
                <div class="text-muted small">Tópico: <?= htmlspecialchars($topicoTitulo, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <h1 class="h3 mb-1"><?= htmlspecialchars((string)$aula['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(ios_url('/curso.php?id=' . (int)$curso_id), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao curso
            </a>
            <?php if ($done): ?>
                <span class="btn btn-success disabled"><i class="bi bi-check2 me-1"></i>Concluída</span>
            <?php else: ?>
                <?php if (!empty($hasQuiz) && !$quizAprovado): ?>
                    <span class="btn btn-primary disabled"><i class="bi bi-check2-square me-1"></i>Concluir (faça o quiz)</span>
                <?php else: ?>
                    <a class="btn btn-primary" href="<?= htmlspecialchars(ios_url('/concluir_aula.php?aula_id=' . (int)$aula_id . '&curso_id=' . (int)$curso_id), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-check2-square me-1"></i>Marcar como concluída
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body">
            <?php
            $conteudo = trim((string)($aula['conteudo'] ?? ''));
            $videoUrl = trim((string)($aula['video_url'] ?? ''));
            $pdf = trim((string)($aula['pdf'] ?? ''));

            $ytId = null;
            if ($videoUrl !== '' && preg_match('/(?:v=|youtu\.be\/|embed\/|shorts\/)([A-Za-z0-9_-]{6,})/', $videoUrl, $m)) {
                $ytId = $m[1];
            }
            ?>

            <?php if ($ytId): ?>
                <div class="ratio ratio-16x9 mb-4">
                    <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId, ENT_QUOTES, 'UTF-8') ?>" title="Vídeo da aula" allowfullscreen></iframe>
                </div>
            <?php elseif ($videoUrl !== ''): ?>
                <div class="mb-3 small text-muted">Vídeo: <a href="<?= htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">Abrir link</a></div>
            <?php endif; ?>

            <?php if ($pdf !== ''): ?>
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(ios_url('/' . ltrim($pdf, '/')), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer"><i class="bi bi-file-earmark-pdf me-1"></i>Abrir PDF</a>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars(ios_url('/' . ltrim($pdf, '/')), ENT_QUOTES, 'UTF-8') ?>" download><i class="bi bi-download me-1"></i>Baixar PDF</a>
                </div>
                <div class="ratio ratio-1x1 mb-4" style="--bs-aspect-ratio: 120%;">
                    <iframe src="<?= htmlspecialchars(ios_url('/' . ltrim($pdf, '/')), ENT_QUOTES, 'UTF-8') ?>" title="PDF da aula"></iframe>
                </div>
            <?php endif; ?>

            <?php if ($conteudo === '' && $videoUrl === '' && $pdf === ''): ?>
                <div class="text-muted">Nenhum conteúdo foi adicionado nesta aula ainda.</div>
            <?php elseif ($conteudo !== ''): ?>
                <div class="lh-lg" style="white-space: pre-wrap;">
                    <?= htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($hasQuiz)): ?>
                <hr class="my-4">
                <h2 class="h5">Validação rápida (2 questões)</h2>
                <p class="text-muted mb-3">Para concluir a aula, acerte as 2 perguntas.</p>

                <?php if ($quizMsg): ?>
                    <div class="alert alert-info" role="alert"><?= htmlspecialchars($quizMsg, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($quizErro): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($quizErro, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" class="vstack gap-3">
                    <div class="border rounded-3 p-3">
                        <div class="fw-semibold mb-2">1) <?= htmlspecialchars((string)$aula['q1_pergunta'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach (['A','B','C'] as $opt):
                            $txt = (string)($aula['q1_' . strtolower($opt)] ?? '');
                            if ($txt === '') continue;
                        ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="q1" id="q1<?= $opt ?>" value="<?= $opt ?>">
                                <label class="form-check-label" for="q1<?= $opt ?>"><?= htmlspecialchars($opt . ') ' . $txt, ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border rounded-3 p-3">
                        <div class="fw-semibold mb-2">2) <?= htmlspecialchars((string)$aula['q2_pergunta'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach (['A','B','C'] as $opt):
                            $txt = (string)($aula['q2_' . strtolower($opt)] ?? '');
                            if ($txt === '') continue;
                        ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="q2" id="q2<?= $opt ?>" value="<?= $opt ?>">
                                <label class="form-check-label" for="q2<?= $opt ?>"><?= htmlspecialchars($opt . ') ' . $txt, ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" name="enviar_quiz" value="1"><i class="bi bi-send me-1"></i>Enviar respostas</button>
                        <?php if ($quizAprovado): ?>
                            <?php if (!empty($nextAulaUrl)): ?>
                                <a id="nextAulaBtn" class="btn btn-success" href="<?= htmlspecialchars($nextAulaUrl, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-arrow-right me-1"></i>Avançar para próxima aula</a>
                            <?php else: ?>
                                <a class="btn btn-success" href="<?= htmlspecialchars(ios_url('/curso.php?id=' . (int)$curso_id), ENT_QUOTES, 'UTF-8') ?>">Voltar ao curso</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($quizMsg) && !$quizAprovado): ?>
        <!-- Modal de aviso quando errar o quiz (autoabre e com destaque vermelho) -->
        <div class="modal fade" id="quizFailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-x-circle-fill me-2"></i>Você errou</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fw-semibold mb-2"><?= htmlspecialchars('Você errou. Tente novamente para poder avançar!', ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="small text-muted mb-0"><?= htmlspecialchars($quizMsg, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
                (function(){
                        try {
                                var modalEl = document.getElementById('quizFailModal');
                                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                modal.show();
                        } catch(e){}
                })();
        </script>
<?php endif; ?>

<?php if (!empty($quizAprovado) && !empty($nextAulaUrl)): ?>
    <script>
        (function(){
            // after short delay, advance automatically; user can also click the green button
            var url = <?= json_encode($nextAulaUrl) ?>;
            try {
                setTimeout(function(){ window.location.href = url; }, 1400);
            } catch(e){}
        })();
    </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?= $quizScript ?? '' ?>
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
