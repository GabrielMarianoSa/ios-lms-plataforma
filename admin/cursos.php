<?php
require 'protect.php';
require '../config/db.php';

require __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Admin • Cursos';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

// Criar curso
if (isset($_POST['criar'])) {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $carga = isset($_POST['carga']) ? (int)$_POST['carga'] : 0;
    $moduloTexto = trim((string)($_POST['modulo_texto'] ?? ''));

    // Descobrir colunas existentes na tabela 'cursos'
    $colsRes = $conn->query("SHOW COLUMNS FROM cursos");
    $cols = [];
    while ($r = $colsRes->fetch_assoc()) {
        $cols[] = $r['Field'];
    }

    $hasThumbnail = in_array('thumbnail', $cols, true);
    $hasModuloTexto = in_array('modulo_texto', $cols, true);

    $thumbnailName = null;

    // Handle uploads only if the corresponding column exists
    if ($hasThumbnail && !empty($_FILES['thumbnail']['tmp_name'])) {
        $up = $_FILES['thumbnail'];
        $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
        $thumbnailName = 'uploads/thumbnails/' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!is_dir(__DIR__ . '/../uploads/thumbnails')) mkdir(__DIR__ . '/../uploads/thumbnails', 0755, true);
        move_uploaded_file($up['tmp_name'], __DIR__ . '/../' . $thumbnailName);
    }

    // Construir INSERT dinamicamente conforme colunas disponíveis
    $fields = ['titulo', 'descricao', 'carga_horaria'];
    $placeholders = ['?', '?', '?'];
    $types = 'ssi';
    $values = [$titulo, $descricao, $carga];

    if ($hasThumbnail && $thumbnailName) { $fields[] = 'thumbnail'; $placeholders[] = '?'; $types .= 's'; $values[] = $thumbnailName; }
    if ($hasModuloTexto) { $fields[] = 'modulo_texto'; $placeholders[] = '?'; $types .= 's'; $values[] = ($moduloTexto !== '' ? $moduloTexto : null); }

    $sql = "INSERT INTO cursos (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // bind_param requires references
        $bindParams = [];
        $bindParams[] = & $types;
        for ($i = 0; $i < count($values); $i++) {
            $bindParams[] = & $values[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();
    } else {
        // Fallback: basic insert (should not occur often)
        $stmt = $conn->prepare("INSERT INTO cursos (titulo, descricao, carga_horaria) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $titulo, $descricao, $carga);
        $stmt->execute();
    }
}

// Excluir curso
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM cursos WHERE id = $id");
}

// Alternar status de inscrição
if (isset($_GET['toggle_inscricao'])) {
    $id = (int)$_GET['toggle_inscricao'];
    // Assuming the column inscricoes_abertas exists (0 or 1)
    // We toggle it: if 1 set 0, if 0 set 1.
    // If null, set 1.
    $check = $conn->query("SELECT inscricoes_abertas FROM cursos WHERE id = $id");
    if($check && $r = $check->fetch_assoc()) {
        $newState = empty($r['inscricoes_abertas']) ? 1 : 0;
        $conn->query("UPDATE cursos SET inscricoes_abertas = $newState WHERE id = $id");
    }
    // Redirect to avoid re-action on refresh
    header("Location: cursos.php");
    exit;
}

$cursoInfosEnabled = ios_table_exists($conn, 'curso_infos');

function ios_format_date_pt(?string $ymd): string
{
    if (!$ymd) return '';

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd) !== 1) {
        return $ymd;
    }

    try {
        $dt = new DateTime($ymd);
        return $dt->format('d/m/Y');
    } catch (Throwable $e) {
        return $ymd;
    }
}

// Listar cursos
if ($cursoInfosEnabled) {
    $sql = "SELECT c.*, ci.modalidade, ci.local, ci.data_inicio, ci.data_fim, ci.turno, ci.vagas FROM cursos c LEFT JOIN curso_infos ci ON ci.curso_id = c.id ORDER BY c.id DESC";
    $cursos = $conn->query($sql);
} else {
    $cursos = $conn->query("SELECT * FROM cursos ORDER BY id DESC");
}
?>

<div class="container">
    <div class="d-flex align-items-start align-items-lg-center justify-content-between gap-3 mb-4 flex-column flex-lg-row">
        <div>
            <h1 class="h3 mb-1">Cursos</h1>
            <div class="text-muted">Crie e gerencie os cursos da plataforma.</div>
        </div>
        <a class="btn btn-outline-primary" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Voltar ao painel</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5">Novo curso</h2>
                        <form method="POST" class="vstack gap-3" enctype="multipart/form-data">
                        <div>
                            <label class="form-label">Título</label>
                            <input name="titulo" class="form-control" placeholder="Título do curso" required>
                        </div>
                        <div>
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="5" placeholder="Descrição"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Carga horária (h)</label>
                            <input name="carga" type="number" class="form-control" placeholder="Ex: 40" required>
                        </div>
                        <div>
                            <label class="form-label">Miniatura (jpg/png)</label>
                            <input name="thumbnail" type="file" accept="image/*" class="form-control">
                            <div class="form-text">Sugestão: use uma imagem mais “quadrada/pequena” (ex: 600×600) para ficar discreto.</div>
                        </div>
                        <div>
                            <label class="form-label">Texto didático do módulo</label>
                            <textarea name="modulo_texto" class="form-control" rows="6" placeholder="Explique como funciona o módulo, pré-requisitos, como o aluno deve avançar, etc."></textarea>
                            <div class="form-text">Vídeos e PDFs ficam dentro das aulas. Aqui é só a orientação do módulo.</div>
                        </div>
                        <button name="criar" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Criar curso</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5">Cursos cadastrados</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Carga</th>
                                    <?php if ($cursoInfosEnabled): ?>
                                        <th>Datas</th>
                                        <th>Local</th>
                                        <th>Modalidade</th>
                                        <th>Turno</th>
                                        <th>Vagas</th>
                                    <?php endif; ?>
                                    <th>Miniatura</th>
                                    <th>Inscrições</th>
                                    <th>Detalhes</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($c = $cursos->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-muted">#<?= (int)$c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int)$c['carga_horaria'] ?>h</td>
                                        <?php if ($cursoInfosEnabled): ?>
                                            <?php
                                                $di = isset($c['data_inicio']) ? (string)$c['data_inicio'] : '';
                                                $df = isset($c['data_fim']) ? (string)$c['data_fim'] : '';
                                                $diFmt = ios_format_date_pt($di);
                                                $dfFmt = ios_format_date_pt($df);
                                                $datas = ($diFmt && $dfFmt) ? ($diFmt . ' a ' . $dfFmt) : '—';
                                            ?>
                                            <td class="small text-muted"><?= htmlspecialchars($datas, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars((string)($c['local'] ?? '—') ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="small">
                                                <?php $mod = (string)($c['modalidade'] ?? ''); ?>
                                                <?php if ($mod !== ''): ?>
                                                    <span class="badge badge-soft"><?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars((string)($c['turno'] ?? '—') ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars((string)($c['vagas'] ?? '—') ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php endif; ?>
                                        <td class="small text-center">
                                            <?php if (!empty($c['thumbnail'])): ?>
                                                <img src="<?= htmlspecialchars(ios_url('/' . ltrim((string)$c['thumbnail'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="thumb" style="height:40px;object-fit:cover;border-radius:6px;">
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </t class="text-center">
                                            <?php 
                                                // Default to 1 (open) if column missing or null, but user requested feature so we assume it works or defaults to open.
                                                // Actually if column is missing, this index might be undefined if we didn't selecting it properly or DB update failed.
                                                // However, PHP handles nulls in loose comparison.
                                                $isOpen = !empty($c['inscricoes_abertas']); 
                                            ?>
                                            <?php if ($isOpen): ?>
                                                <a href="?toggle_inscricao=<?= $c['id'] ?>" class="badge bg-success text-decoration-none" title="Clique para fechar">Abertas</a>
                                            <?php else: ?>
                                                <a href="?toggle_inscricao=<?= $c['id'] ?>" class="badge bg-danger text-decoration-none" title="Clique para abrir">Fechadas</a>
                                            <?php endif; ?>
                                        </td>
                                        <tdd>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="curso_detalhes.php?curso_id=<?= (int)$c['id'] ?>">
                                                <i class="bi bi-calendar-week me-1"></i>Datas/local
                                            </a>
                                            <a class="btn btn-sm btn-outline-secondary ms-1" href="<?= htmlspecialchars(ios_url('/curso.php?id=' . (int)$c['id']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Ver no site
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-danger" href="?delete=<?= (int)$c['id'] ?>" onclick="return confirm('Excluir este curso?')">
                                                <i class="bi bi-trash me-1"></i>Excluir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!$cursoInfosEnabled): ?>
                        <div class="alert alert-secondary mt-3 mb-0" role="alert">
                            <div class="d-flex gap-2">
                                <i class="bi bi-info-circle"></i>
                                <div>
                                    Para habilitar datas/local/modalidade, rode o SQL em <strong>sql/curso_infos.sql</strong> no banco <strong>ios</strong>. Isso não altera tabelas existentes.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
