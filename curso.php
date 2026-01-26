<?php
session_start();
require 'config/db.php';

require __DIR__ . '/partials/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$curso_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Dados do curso
$curso = $conn->query("SELECT * FROM cursos WHERE id = $curso_id")->fetch_assoc();

if (!$curso) {
    http_response_code(404);
    $pageTitle = 'Curso não encontrado';
    $activeNav = 'cursos';
    require __DIR__ . '/partials/header.php';
    echo '<div class="container"><div class="alert alert-danger">Curso não encontrado.</div></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Restringir acesso: precisa estar inscrito (exceto admin)
$user_id = (int)$user_id;
$isAdmin = !empty($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
$isEnrolled = false;
$inscricaoStatus = null;
if (!$isAdmin) {
    $chk = $conn->query("SELECT status FROM inscricoes WHERE user_id={$user_id} AND curso_id={$curso_id} ORDER BY id DESC LIMIT 1");
    $isEnrolled = $chk && $chk->num_rows > 0;
    if ($isEnrolled) {
        $row = $chk->fetch_assoc();
        $inscricaoStatus = $row ? ($row['status'] ?? null) : null;
        $isEnrolled = ios_is_inscricao_aprovada((string)$inscricaoStatus);
    }

    if (!$isEnrolled) {
        $pageTitle = 'Acesso restrito';
        $activeNav = 'cursos';
        require __DIR__ . '/partials/header.php';
        ?>
        <div class="container">
            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-exclamation-triangle fs-5"></i>
                <div>
                    <?php $b = ios_inscricao_badge((string)$inscricaoStatus); ?>
                    <?php if ($inscricaoStatus !== null && $inscricaoStatus !== ''): ?>
                        <div class="fw-semibold">Sua solicitação está: <span class="badge <?= htmlspecialchars($b['class'], ENT_QUOTES, 'UTF-8') ?>"><i class="bi <?= htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="small">
                            <?php if (ios_normalize_inscricao_status((string)$inscricaoStatus) === 'pendente'): ?>
                                Assim que um administrador aprovar, você terá acesso ao conteúdo.
                            <?php else: ?>
                                Você pode solicitar novamente sua inscrição quando quiser.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="fw-semibold">Você ainda não solicitou inscrição neste curso.</div>
                        <div class="small">Envie sua solicitação para que a equipe analise e aprove o acesso.</div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a class="btn btn-primary" href="<?= htmlspecialchars(ios_url('/inscrever.php?curso_id=' . (int)$curso_id), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-check2-square me-1"></i>Inscrever agora
                        </a>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(ios_url('/cursos.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-arrow-left me-1"></i>Voltar ao catálogo
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }
}

// Progresso do aluno
$progresso = [];
$sql = "SELECT p.aula_id FROM progresso p JOIN aulas a ON a.id = p.aula_id WHERE p.user_id = $user_id AND p.concluida = 1 AND a.curso_id = $curso_id";
$res = $conn->query($sql);
while($p = $res->fetch_assoc()){
    $progresso[] = $p['aula_id'];
}

// Aulas do curso
$orderSql = 'ORDER BY id ASC';
$colOrd = $conn->query("SHOW COLUMNS FROM aulas LIKE 'ordem'");
if ($colOrd && $colOrd->num_rows > 0) {
    $orderSql = 'ORDER BY COALESCE(ordem, 999999) ASC, id ASC';
}

$aulas = $conn->query("SELECT * FROM aulas WHERE curso_id = $curso_id {$orderSql}");
$totalAulas = $aulas ? $aulas->num_rows : 0;

$concluidas = count($progresso);
$percentual = $totalAulas > 0 ? round(($concluidas / $totalAulas) * 100) : 0;

// Próxima aula (primeira não concluída)
$proximaAulaId = null;
if ($totalAulas > 0) {
    $aulasTmp = $conn->query("SELECT id FROM aulas WHERE curso_id = {$curso_id} {$orderSql}");
    if ($aulasTmp) {
        while ($row = $aulasTmp->fetch_assoc()) {
            $aid = (int)$row['id'];
            if (!in_array($aid, $progresso, true)) {
                $proximaAulaId = $aid;
                break;
            }
        }
    }
}

// Optional: topics support
$topicosEnabled = ios_table_exists($conn, 'topicos');
$aulasHasTopico = false;
$colRes = $conn->query("SHOW COLUMNS FROM aulas LIKE 'topico_id'");
if ($colRes && $colRes->num_rows > 0) {
    $aulasHasTopico = true;
}

$topicos = [];
$aulasPorTopico = [];
if ($topicosEnabled && $aulasHasTopico) {
    $topicosRes = $conn->query("SELECT id, titulo, ordem FROM topicos WHERE curso_id = {$curso_id} ORDER BY ordem ASC, id ASC");
    if ($topicosRes) {
        while ($t = $topicosRes->fetch_assoc()) {
            $topicos[] = $t;
            $aulasPorTopico[(int)$t['id']] = [];
        }
    }

    $aulasRes2 = $conn->query("SELECT * FROM aulas WHERE curso_id = {$curso_id} ORDER BY COALESCE(topico_id, 0) ASC, id ASC");
    if ($aulasRes2) {
        while ($a = $aulasRes2->fetch_assoc()) {
            $tid = (int)($a['topico_id'] ?? 0);
            if (!isset($aulasPorTopico[$tid])) {
                $aulasPorTopico[$tid] = [];
            }
            $aulasPorTopico[$tid][] = $a;
        }
    }
}
?>

<?php
$pageTitle = (string)$curso['titulo'];
$activeNav = 'cursos';
require __DIR__ . '/partials/header.php';
?>

<div class="container">
    <?php
    // show course thumbnail/banner if exists in DB
    if (!empty($curso['thumbnail']) && file_exists(__DIR__ . '/' . ltrim($curso['thumbnail'], '/'))): ?>
        <div class="mb-4">
            <img src="<?= htmlspecialchars(ios_url('/' . ltrim((string)$curso['thumbnail'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="Banner do curso" class="img-fluid rounded-4 shadow-sm w-100">
        </div>
    <?php else:
        $bannerPath = __DIR__ . '/assets/images/curso-banner.jpg';
        if (file_exists($bannerPath)): ?>
            <div class="mb-4">
                <img src="<?= ios_url('/assets/images/curso-banner.jpg') ?>" alt="Banner do curso" class="img-fluid rounded-4 shadow-sm w-100">
            </div>
    <?php endif; endif; ?>
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($curso['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="text-muted"><?= htmlspecialchars($curso['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="text-lg-end">
            <div class="h2 mb-0 text-primary fw-semibold"><?= (int)$percentual ?>%</div>
            <div class="text-muted small">progresso no curso</div>
        </div>
    </div>

    <?php if (!empty($curso['modulo_texto'])): ?>
        <div class="card card-soft mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                    <h2 class="h5 mb-0">Como funciona este módulo</h2>
                    <?php if ($proximaAulaId): ?>
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(ios_url('/aula.php?curso_id=' . (int)$curso_id . '&aula_id=' . (int)$proximaAulaId), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-play-circle me-1"></i>Continuar
                        </a>
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="white-space: pre-wrap;">
                    <?= htmlspecialchars((string)$curso['modulo_texto'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if ($proximaAulaId): ?>
            <div class="mb-4">
                <a class="btn btn-primary" href="<?= htmlspecialchars(ios_url('/aula.php?curso_id=' . (int)$curso_id . '&aula_id=' . (int)$proximaAulaId), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-play-circle me-1"></i>Começar / Continuar
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card card-soft mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">Progresso</div>
                <div class="small text-muted"><?= (int)$concluidas ?>/<?= (int)$totalAulas ?> aulas concluídas</div>
            </div>
            <div class="progress" role="progressbar" aria-label="Progresso" aria-valuenow="<?= (int)$percentual ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= (int)$percentual ?>%"></div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0">Conteúdo do curso</h2>
        <a class="btn btn-outline-secondary btn-sm" href="cursos.php"><i class="bi bi-arrow-left me-1"></i>Voltar aos cursos</a>
    </div>

    <?php if ($totalAulas === 0): ?>
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-info-circle fs-5"></i>
            <div>
                <div class="fw-semibold">Este curso ainda não tem aulas cadastradas.</div>
                <div class="small">Assim que novas aulas forem publicadas, elas vão aparecer aqui.</div>
                <?php if ($isAdmin): ?>
                    <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(ios_url('/admin/aulas.php'), ENT_QUOTES, 'UTF-8') ?>">Gerenciar aulas (admin)</a></div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php if ($topicosEnabled && $aulasHasTopico && count($topicos) > 0): ?>
            <div class="accordion" id="topicosAccordion">
                <?php foreach ($topicos as $idx => $t): ?>
                    <?php
                        $tid = (int)$t['id'];
                        $items = $aulasPorTopico[$tid] ?? [];
                        $collapseId = 'topico_' . $tid;
                        $headingId = 'heading_' . $tid;
                        $open = ($idx === 0);
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="<?= htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="accordion-button <?= $open ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$t['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                <span class="ms-2 badge text-bg-light"><?= count($items) ?> aula(s)</span>
                            </button>
                        </h2>
                        <div id="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" class="accordion-collapse collapse <?= $open ? 'show' : '' ?>" aria-labelledby="<?= htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8') ?>" data-bs-parent="#topicosAccordion">
                            <div class="accordion-body p-0">
                                <?php if (count($items) === 0): ?>
                                    <div class="p-3 text-muted">Nenhuma aula neste tópico ainda.</div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($items as $a): ?>
                                            <div class="list-group-item d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                                                <div class="fw-semibold"><?= htmlspecialchars((string)$a['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <a class="btn btn-sm btn-outline-primary" href="aula.php?curso_id=<?= (int)$curso_id ?>&aula_id=<?= (int)$a['id'] ?>">
                                                        <i class="bi bi-play-circle me-1"></i>Ver aula
                                                    </a>
                                                    <?php if (in_array($a['id'], $progresso)): ?>
                                                        <span class="badge text-bg-success"><i class="bi bi-check2 me-1"></i>Concluída</span>
                                                    <?php else: ?>
                                                        <a class="btn btn-sm btn-primary" href="concluir_aula.php?aula_id=<?= (int)$a['id'] ?>&curso_id=<?= (int)$curso_id ?>">
                                                            <i class="bi bi-check2-square me-1"></i>Concluir
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php while($a = $aulas->fetch_assoc()): ?>
                    <div class="list-group-item d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="aula.php?curso_id=<?= (int)$curso_id ?>&aula_id=<?= (int)$a['id'] ?>">
                                <i class="bi bi-play-circle me-1"></i>Ver aula
                            </a>
                            <?php if (in_array($a['id'], $progresso)): ?>
                                <span class="badge text-bg-success"><i class="bi bi-check2 me-1"></i>Concluída</span>
                            <?php else: ?>
                                <a class="btn btn-sm btn-primary" href="concluir_aula.php?aula_id=<?= (int)$a['id'] ?>&curso_id=<?= (int)$curso_id ?>">
                                    <i class="bi bi-check2-square me-1"></i>Concluir
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
