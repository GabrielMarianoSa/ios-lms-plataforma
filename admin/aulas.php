<?php
require 'protect.php';
require '../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Admin • Aulas';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

$cursos = $conn->query("SELECT * FROM cursos");

$topicosEnabled = ios_table_exists($conn, 'topicos');

// Detect optional media columns in `aulas`
$colsRes = $conn->query("SHOW COLUMNS FROM aulas");
$aulaCols = [];
while ($colsRes && ($r = $colsRes->fetch_assoc())) {
    $aulaCols[] = $r['Field'];
}
$aulasHasVideo = in_array('video_url', $aulaCols, true);
$aulasHasPdf = in_array('pdf', $aulaCols, true);
$aulasHasTopico = in_array('topico_id', $aulaCols, true);
$aulasHasOrdem = in_array('ordem', $aulaCols, true);
$aulasHasQuiz = in_array('q1_pergunta', $aulaCols, true);

$editId = (int)($_GET['edit'] ?? 0);
$deleteId = (int)($_GET['delete'] ?? 0);

// Excluir aula
if ($deleteId > 0) {
    $stmt = $conn->prepare('DELETE FROM aulas WHERE id = ?');
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    header('Location: aulas.php');
    exit;
}

// Buscar aula para edição
$aulaEdit = null;
if ($editId > 0) {
    $aulaEdit = $conn->query("SELECT * FROM aulas WHERE id = {$editId} LIMIT 1")->fetch_assoc();
}

$selectedCursoId = (int)($_GET['curso_id'] ?? 0);
if ($aulaEdit && empty($_GET['curso_id'])) {
    $selectedCursoId = (int)($aulaEdit['curso_id'] ?? 0);
}

$topicos = [];
if ($topicosEnabled && $aulasHasTopico && $selectedCursoId > 0) {
    $stmt = $conn->prepare('SELECT id, titulo, ordem FROM topicos WHERE curso_id = ? ORDER BY ordem ASC, id ASC');
    $stmt->bind_param('i', $selectedCursoId);
    $stmt->execute();
    $topicos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$formError = null;

// Criar / editar aula
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;
    $titulo = trim((string)($_POST['titulo'] ?? ''));
    $conteudo = (string)($_POST['conteudo'] ?? '');
    $aula_id = isset($_POST['aula_id']) ? (int)$_POST['aula_id'] : 0;

    $ordem = $aulasHasOrdem ? (int)($_POST['ordem'] ?? 0) : 0;

    $q1_pergunta = $aulasHasQuiz ? trim((string)($_POST['q1_pergunta'] ?? '')) : '';
    $q1_a = $aulasHasQuiz ? trim((string)($_POST['q1_a'] ?? '')) : '';
    $q1_b = $aulasHasQuiz ? trim((string)($_POST['q1_b'] ?? '')) : '';
    $q1_c = $aulasHasQuiz ? trim((string)($_POST['q1_c'] ?? '')) : '';
    $q1_correta = $aulasHasQuiz ? strtoupper(trim((string)($_POST['q1_correta'] ?? ''))) : '';

    $q2_pergunta = $aulasHasQuiz ? trim((string)($_POST['q2_pergunta'] ?? '')) : '';
    $q2_a = $aulasHasQuiz ? trim((string)($_POST['q2_a'] ?? '')) : '';
    $q2_b = $aulasHasQuiz ? trim((string)($_POST['q2_b'] ?? '')) : '';
    $q2_c = $aulasHasQuiz ? trim((string)($_POST['q2_c'] ?? '')) : '';
    $q2_correta = $aulasHasQuiz ? strtoupper(trim((string)($_POST['q2_correta'] ?? ''))) : '';

    $topico_id = isset($_POST['topico_id']) ? (int)$_POST['topico_id'] : 0;
    if ($topicosEnabled && $aulasHasTopico && $curso_id > 0) {
        if ($topico_id <= 0) {
            // pick first topic for course as default (migration creates 'Geral')
            $stmt = $conn->prepare('SELECT id FROM topicos WHERE curso_id = ? ORDER BY ordem ASC, id ASC LIMIT 1');
            $stmt->bind_param('i', $curso_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $topico_id = (int)($row['id'] ?? 0);
        }
    } else {
        $topico_id = 0;
    }

    $videoUrl = ($aulasHasVideo && !empty($_POST['video_url'])) ? trim((string)$_POST['video_url']) : null;
    $pdfPath = null;
    if ($aulasHasPdf && !empty($_FILES['pdf']['tmp_name'])) {
        $up = $_FILES['pdf'];
        $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
        $pdfPath = 'uploads/aulas/' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!is_dir(__DIR__ . '/../uploads/aulas')) {
            mkdir(__DIR__ . '/../uploads/aulas', 0755, true);
        }
        move_uploaded_file($up['tmp_name'], __DIR__ . '/../' . $pdfPath);
    }

    if ($curso_id <= 0 || $titulo === '') {
        $formError = 'Selecione um curso e preencha o título.';
    }

    if ($formError === null && $aulasHasQuiz) {
        $valid = ['A', 'B', 'C'];
        if ($q1_pergunta !== '' || $q2_pergunta !== '') {
            if ($q1_pergunta === '' || $q2_pergunta === '') {
                $formError = 'Preencha as 2 perguntas (ou deixe as 2 vazias).';
            } elseif (!in_array($q1_correta, $valid, true) || !in_array($q2_correta, $valid, true)) {
                $formError = 'A resposta correta deve ser A, B ou C (nas duas perguntas).';
            }
        }
    }

    if ($formError === null && isset($_POST['salvar']) && $aula_id > 0) {
        // Dynamic update based on available columns
        $sets = ['curso_id = ?', 'titulo = ?', 'conteudo = ?'];
        $types = 'iss';
        $vals = [$curso_id, $titulo, $conteudo];

        if ($topicosEnabled && $aulasHasTopico && $topico_id > 0) {
            $sets[] = 'topico_id = ?';
            $types .= 'i';
            $vals[] = $topico_id;
        }

        if ($aulasHasOrdem) {
            $sets[] = 'ordem = ?';
            $types .= 'i';
            $vals[] = $ordem > 0 ? $ordem : null;
        }

        if ($aulasHasQuiz) {
            $sets[] = 'q1_pergunta = ?'; $types .= 's'; $vals[] = ($q1_pergunta !== '' ? $q1_pergunta : null);
            $sets[] = 'q1_a = ?'; $types .= 's'; $vals[] = ($q1_a !== '' ? $q1_a : null);
            $sets[] = 'q1_b = ?'; $types .= 's'; $vals[] = ($q1_b !== '' ? $q1_b : null);
            $sets[] = 'q1_c = ?'; $types .= 's'; $vals[] = ($q1_c !== '' ? $q1_c : null);
            $sets[] = 'q1_correta = ?'; $types .= 's'; $vals[] = ($q1_correta !== '' ? $q1_correta : null);

            $sets[] = 'q2_pergunta = ?'; $types .= 's'; $vals[] = ($q2_pergunta !== '' ? $q2_pergunta : null);
            $sets[] = 'q2_a = ?'; $types .= 's'; $vals[] = ($q2_a !== '' ? $q2_a : null);
            $sets[] = 'q2_b = ?'; $types .= 's'; $vals[] = ($q2_b !== '' ? $q2_b : null);
            $sets[] = 'q2_c = ?'; $types .= 's'; $vals[] = ($q2_c !== '' ? $q2_c : null);
            $sets[] = 'q2_correta = ?'; $types .= 's'; $vals[] = ($q2_correta !== '' ? $q2_correta : null);
        }

        if ($aulasHasVideo) {
            $sets[] = 'video_url = ?';
            $types .= 's';
            $vals[] = $videoUrl;
        }
        if ($aulasHasPdf && $pdfPath) {
            $sets[] = 'pdf = ?';
            $types .= 's';
            $vals[] = $pdfPath;
        }

        $sql = 'UPDATE aulas SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $types .= 'i';
        $vals[] = $aula_id;
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $formError = 'Erro ao preparar atualização: ' . $conn->error;
        } else {
        $bind = [];
        $bind[] = & $types;
        foreach ($vals as $i => $v) { $bind[] = & $vals[$i]; }
        call_user_func_array([$stmt, 'bind_param'], $bind);
            if (!$stmt->execute()) {
                $formError = 'Erro ao salvar: ' . $stmt->error;
            } else {
                header('Location: aulas.php');
                exit;
            }
        }
    }

    if ($formError === null && isset($_POST['criar'])) {
        $fields = ['curso_id', 'titulo', 'conteudo'];
        $placeholders = ['?', '?', '?'];
        $types = 'iss';
        $vals = [$curso_id, $titulo, $conteudo];

        if ($topicosEnabled && $aulasHasTopico && $topico_id > 0) {
            $fields[] = 'topico_id';
            $placeholders[] = '?';
            $types .= 'i';
            $vals[] = $topico_id;
        }

        if ($aulasHasOrdem) {
            $fields[] = 'ordem';
            $placeholders[] = '?';
            $types .= 'i';
            $vals[] = $ordem > 0 ? $ordem : null;
        }

        if ($aulasHasQuiz) {
            $fields[] = 'q1_pergunta'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q1_pergunta !== '' ? $q1_pergunta : null);
            $fields[] = 'q1_a'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q1_a !== '' ? $q1_a : null);
            $fields[] = 'q1_b'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q1_b !== '' ? $q1_b : null);
            $fields[] = 'q1_c'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q1_c !== '' ? $q1_c : null);
            $fields[] = 'q1_correta'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q1_correta !== '' ? $q1_correta : null);

            $fields[] = 'q2_pergunta'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q2_pergunta !== '' ? $q2_pergunta : null);
            $fields[] = 'q2_a'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q2_a !== '' ? $q2_a : null);
            $fields[] = 'q2_b'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q2_b !== '' ? $q2_b : null);
            $fields[] = 'q2_c'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q2_c !== '' ? $q2_c : null);
            $fields[] = 'q2_correta'; $placeholders[] = '?'; $types .= 's'; $vals[] = ($q2_correta !== '' ? $q2_correta : null);
        }

        if ($aulasHasVideo && $videoUrl !== null) { $fields[] = 'video_url'; $placeholders[] = '?'; $types .= 's'; $vals[] = $videoUrl; }
        if ($aulasHasPdf && $pdfPath) { $fields[] = 'pdf'; $placeholders[] = '?'; $types .= 's'; $vals[] = $pdfPath; }

        $sql = 'INSERT INTO aulas (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $formError = 'Erro ao preparar criação: ' . $conn->error;
        } else {
        $bind = [];
        $bind[] = & $types;
        foreach ($vals as $i => $v) { $bind[] = & $vals[$i]; }
        call_user_func_array([$stmt, 'bind_param'], $bind);
            if (!$stmt->execute()) {
                $formError = 'Erro ao criar aula: ' . $stmt->error;
            } else {
                header('Location: aulas.php');
                exit;
            }
        }
    }
}

// Listar aulas
$aulas = $conn->query("
SELECT aulas.*, cursos.titulo AS curso, topicos.titulo AS topico
FROM aulas
LEFT JOIN cursos ON cursos.id = aulas.curso_id
LEFT JOIN topicos ON topicos.id = aulas.topico_id
ORDER BY cursos.titulo ASC, topicos.ordem ASC, topicos.titulo ASC, COALESCE(aulas.ordem, 999999) ASC, aulas.id ASC
");
?>

<div class="container">
    <div class="d-flex align-items-start align-items-lg-center justify-content-between gap-3 mb-4 flex-column flex-lg-row">
        <div>
            <h1 class="h3 mb-1">Aulas (LMS)</h1>
            <div class="text-muted">Cadastre aulas por curso e acompanhe o conteúdo.</div>
        </div>
        <a class="btn btn-outline-primary" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Voltar ao painel</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5"><?= $aulaEdit ? 'Editar aula' : 'Nova aula' ?></h2>
                    <?php if (!empty($formError)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string)$formError, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <form method="POST" class="vstack gap-3" enctype="multipart/form-data">
                        <?php if ($aulaEdit): ?>
                            <input type="hidden" name="aula_id" value="<?= (int)$aulaEdit['id'] ?>">
                        <?php endif; ?>
                        <div>
                            <label class="form-label">Curso</label>
                            <select name="curso_id" class="form-select" required id="cursoSelect">
                                <option value="">Selecione o curso</option>
                                <?php while($c = $cursos->fetch_assoc()): ?>
                                        <?php
                                            $cid = (int)$c['id'];
                                            $sel = false;
                                            if ($aulaEdit) $sel = (int)$aulaEdit['curso_id'] === $cid;
                                            elseif ($selectedCursoId > 0) $sel = $selectedCursoId === $cid;
                                        ?>
                                        <option value="<?= $cid ?>" <?= $sel ? 'selected' : '' ?>><?= htmlspecialchars($c['titulo'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <?php if ($topicosEnabled && $aulasHasTopico): ?>
                            <div>
                                <label class="form-label">Tópico</label>
                                <?php if ($selectedCursoId <= 0 && !$aulaEdit): ?>
                                    <select name="topico_id" class="form-select" disabled>
                                        <option>Selecione um curso primeiro</option>
                                    </select>
                                    <div class="form-text">Dica: escolha o curso para carregar os tópicos.</div>
                                <?php else: ?>
                                    <select name="topico_id" class="form-select">
                                        <option value="">(Automático: primeiro tópico)</option>
                                        <?php foreach ($topicos as $t): ?>
                                            <?php
                                                $tid = (int)$t['id'];
                                                $selT = $aulaEdit ? ((int)($aulaEdit['topico_id'] ?? 0) === $tid) : false;
                                            ?>
                                            <option value="<?= $tid ?>" <?= $selT ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['titulo'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="form-label">Título</label>
                            <input name="titulo" class="form-control" placeholder="Título da aula" required value="<?= htmlspecialchars((string)($aulaEdit['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <?php if ($aulasHasOrdem): ?>
                            <div>
                                <label class="form-label">Ordem (opcional)</label>
                                <input name="ordem" type="number" class="form-control" placeholder="1, 2, 3..." value="<?= htmlspecialchars((string)($aulaEdit['ordem'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text">Se vazio, o sistema usa a ordem de criação.</div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="form-label">Conteúdo</label>
                            <textarea name="conteudo" class="form-control" rows="8" placeholder="Conteúdo da aula (texto)" ><?= htmlspecialchars((string)($aulaEdit['conteudo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">Este texto aparece quando o aluno clica em “Ver aula”.</div>
                        </div>

                        <?php if ($aulasHasVideo): ?>
                            <div>
                                <label class="form-label">Link do vídeo (YouTube)</label>
                                <input name="video_url" type="url" class="form-control" placeholder="https://youtube.com/watch?v=..." value="<?= htmlspecialchars((string)($aulaEdit['video_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text">O aluno verá o vídeo incorporado dentro da aula.</div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0" role="alert">
                                Para habilitar vídeo por aula, rode: <strong>admin/migrate_aulas_media.php</strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($aulasHasPdf): ?>
                            <div>
                                <label class="form-label">PDF da aula (opcional)</label>
                                <input name="pdf" type="file" accept="application/pdf" class="form-control">
                                <?php if (!empty($aulaEdit['pdf'])): ?>
                                    <div class="mt-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(ios_url('/' . ltrim((string)$aulaEdit['pdf'], '/')), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">
                                            Ver PDF atual
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0" role="alert">
                                Para habilitar PDF por aula, rode: <strong>admin/migrate_aulas_media.php</strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($aulasHasQuiz): ?>
                            <div class="border rounded-3 p-3">
                                <div class="fw-semibold mb-2">Questões (2) • Múltipla escolha</div>
                                <div class="small text-muted mb-3">O aluno precisa acertar as 2 para concluir a aula.</div>

                                <div class="mb-3">
                                    <label class="form-label">Pergunta 1</label>
                                    <textarea name="q1_pergunta" class="form-control" rows="2" placeholder="Digite a pergunta..."><?= htmlspecialchars((string)($aulaEdit['q1_pergunta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <div class="row g-2 mt-2">
                                        <div class="col-12 col-md-4"><input name="q1_a" class="form-control" placeholder="A) opção" value="<?= htmlspecialchars((string)($aulaEdit['q1_a'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="col-12 col-md-4"><input name="q1_b" class="form-control" placeholder="B) opção" value="<?= htmlspecialchars((string)($aulaEdit['q1_b'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="col-12 col-md-4"><input name="q1_c" class="form-control" placeholder="C) opção" value="<?= htmlspecialchars((string)($aulaEdit['q1_c'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label">Resposta correta (A/B/C)</label>
                                        <select name="q1_correta" class="form-select">
                                            <?php $q1c = strtoupper((string)($aulaEdit['q1_correta'] ?? '')); ?>
                                            <option value="">Selecione</option>
                                            <option value="A" <?= $q1c==='A'?'selected':'' ?>>A</option>
                                            <option value="B" <?= $q1c==='B'?'selected':'' ?>>B</option>
                                            <option value="C" <?= $q1c==='C'?'selected':'' ?>>C</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Pergunta 2</label>
                                    <textarea name="q2_pergunta" class="form-control" rows="2" placeholder="Digite a pergunta..."><?= htmlspecialchars((string)($aulaEdit['q2_pergunta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <div class="row g-2 mt-2">
                                        <div class="col-12 col-md-4"><input name="q2_a" class="form-control" placeholder="A) opção" value="<?= htmlspecialchars((string)($aulaEdit['q2_a'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="col-12 col-md-4"><input name="q2_b" class="form-control" placeholder="B) opção" value="<?= htmlspecialchars((string)($aulaEdit['q2_b'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="col-12 col-md-4"><input name="q2_c" class="form-control" placeholder="C) opção" value="<?= htmlspecialchars((string)($aulaEdit['q2_c'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label">Resposta correta (A/B/C)</label>
                                        <select name="q2_correta" class="form-select">
                                            <?php $q2c = strtoupper((string)($aulaEdit['q2_correta'] ?? '')); ?>
                                            <option value="">Selecione</option>
                                            <option value="A" <?= $q2c==='A'?'selected':'' ?>>A</option>
                                            <option value="B" <?= $q2c==='B'?'selected':'' ?>>B</option>
                                            <option value="C" <?= $q2c==='C'?'selected':'' ?>>C</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($aulaEdit): ?>
                            <div class="d-flex gap-2">
                                <button name="salvar" class="btn btn-primary"><i class="bi bi-save me-1"></i>Salvar</button>
                                <a href="aulas.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        <?php else: ?>
                            <button name="criar" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Criar aula</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5">Aulas cadastradas</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Curso</th>
                                    <th>Tópico</th>
                                    <th>Título</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($a = $aulas->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-muted">#<?= (int)$a['id'] ?></td>
                                        <td><?= htmlspecialchars((string)($a['curso'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars((string)($a['topico'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted">
                                                <?= $aulasHasOrdem ? ('Ordem: ' . htmlspecialchars((string)($a['ordem'] ?? '—'), ENT_QUOTES, 'UTF-8') . ' • ') : '' ?>
                                                <?= (!empty($a['video_url']) ? 'Vídeo • ' : '') ?>
                                                <?= (!empty($a['pdf']) ? 'PDF • ' : '') ?>
                                                <?= ($aulasHasQuiz && !empty($a['q1_pergunta']) && !empty($a['q2_pergunta']) ? 'Quiz' : '') ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="aulas.php?edit=<?= (int)$a['id'] ?>"><i class="bi bi-pencil me-1"></i>Editar</a>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(ios_url('/aula.php?curso_id=' . (int)$a['curso_id'] . '&aula_id=' . (int)$a['id']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer"><i class="bi bi-box-arrow-up-right me-1"></i>Ver</a>
                                            <a class="btn btn-sm btn-outline-danger" href="aulas.php?delete=<?= (int)$a['id'] ?>" onclick="return confirm('Excluir esta aula?')"><i class="bi bi-trash me-1"></i>Excluir</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  (function () {
    var sel = document.getElementById('cursoSelect');
    if (!sel) return;
    sel.addEventListener('change', function () {
      var v = sel.value || '';
      var url = new URL(window.location.href);
      if (v) url.searchParams.set('curso_id', v);
      else url.searchParams.delete('curso_id');
      // keep edit if present
      window.location.href = url.toString();
    });
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
