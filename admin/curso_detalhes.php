<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Admin • Detalhes do Curso';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

$cursoInfosEnabled = ios_table_exists($conn, 'curso_infos');
$curso_id = (int)($_GET['curso_id'] ?? 0);

$curso = null;
if ($curso_id > 0) {
    $curso = $conn->query("SELECT * FROM cursos WHERE id = {$curso_id}")->fetch_assoc();
}

if (!$curso) {
    http_response_code(404);
    ?>
    <div class="container">
      <div class="alert alert-danger">Curso não encontrado.</div>
      <a class="btn btn-outline-primary" href="cursos.php"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
    <?php
    require __DIR__ . '/../partials/footer.php';
    exit;
}

$info = [
    'modalidade' => '',
    'local' => '',
    'data_inicio' => '',
    'data_fim' => '',
    'turno' => '',
    'vagas' => '',
];

if ($cursoInfosEnabled) {
    $stmt = $conn->prepare('SELECT modalidade, local, data_inicio, data_fim, turno, vagas FROM curso_infos WHERE curso_id = ? LIMIT 1');
    $stmt->bind_param('i', $curso_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $info = array_merge($info, $row);
    }
}

$salvo = false;
$erro = null;

// UNIFIED POST handler - saves BOTH infos and media in one action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_tudo'])) {
    
    // 1. Save curso_infos (dates, location, etc) if table exists
    if ($cursoInfosEnabled) {
        $modalidade = trim((string)($_POST['modalidade'] ?? ''));
        $local = trim((string)($_POST['local'] ?? ''));
        $data_inicio = trim((string)($_POST['data_inicio'] ?? ''));
        $data_fim = trim((string)($_POST['data_fim'] ?? ''));
        $turno = trim((string)($_POST['turno'] ?? ''));
        $vagas = trim((string)($_POST['vagas'] ?? ''));

        $data_inicio = $data_inicio !== '' ? $data_inicio : null;
        $data_fim = $data_fim !== '' ? $data_fim : null;
        $vagasInt = $vagas !== '' ? (int)$vagas : null;

        $existsStmt = $conn->prepare('SELECT id FROM curso_infos WHERE curso_id = ? LIMIT 1');
        $existsStmt->bind_param('i', $curso_id);
        $existsStmt->execute();
        $exists = $existsStmt->get_result()->fetch_assoc();

        if ($exists) {
            $upd = $conn->prepare('UPDATE curso_infos SET modalidade=?, local=?, data_inicio=?, data_fim=?, turno=?, vagas=? WHERE curso_id=?');
            $upd->bind_param('sssssii', $modalidade, $local, $data_inicio, $data_fim, $turno, $vagasInt, $curso_id);
            $upd->execute();
        } else {
            $ins = $conn->prepare('INSERT INTO curso_infos (curso_id, modalidade, local, data_inicio, data_fim, turno, vagas) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $ins->bind_param('isssssi', $curso_id, $modalidade, $local, $data_inicio, $data_fim, $turno, $vagasInt);
            $ins->execute();
        }

        $info = [
            'modalidade' => $modalidade,
            'local' => $local,
            'data_inicio' => $data_inicio ?? '',
            'data_fim' => $data_fim ?? '',
            'turno' => $turno,
            'vagas' => $vagasInt ?? '',
        ];
    }

    // 2. Save media (thumbnail, modulo_texto) to cursos table
    $colsRes = $conn->query("SHOW COLUMNS FROM cursos");
    $cols = [];
    while ($r = $colsRes->fetch_assoc()) $cols[] = $r['Field'];

    $updates = [];

    // Thumbnail upload
    if (in_array('thumbnail', $cols, true) && !empty($_FILES['thumbnail']['tmp_name'])) {
        $up = $_FILES['thumbnail'];
        $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
        $thumbnailName = 'uploads/thumbnails/' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!is_dir(__DIR__ . '/../uploads/thumbnails')) mkdir(__DIR__ . '/../uploads/thumbnails', 0755, true);
        move_uploaded_file($up['tmp_name'], __DIR__ . '/../' . $thumbnailName);
        $updates['thumbnail'] = $thumbnailName;
    }

    // Thumbnail path (manual entry)
    if (in_array('thumbnail', $cols, true) && !empty($_POST['thumbnail_path']) && empty($_FILES['thumbnail']['tmp_name'])) {
        $path = trim((string)$_POST['thumbnail_path']);
        if ($path !== '') $updates['thumbnail'] = $path;
    }

    // Modulo texto
    if (in_array('modulo_texto', $cols, true)) {
        $mt = trim((string)($_POST['modulo_texto'] ?? ''));
        $updates['modulo_texto'] = $mt !== '' ? $mt : null;
    }

    if (!empty($updates)) {
        $parts = [];
        $vals = [];
        foreach ($updates as $k => $v) {
            $parts[] = "`" . $conn->real_escape_string($k) . "` = ?";
            $vals[] = $v;
        }
        $sql = "UPDATE cursos SET " . implode(', ', $parts) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $types = str_repeat('s', count($vals)) . 'i';
        $bindParams = [];
        $bindParams[] = & $types;
        foreach ($vals as $i => $vv) $bindParams[] = & $vals[$i];
        $bindParams[] = & $curso_id;
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();
    }

    // Refresh curso data
    $curso = $conn->query("SELECT * FROM cursos WHERE id = {$curso_id}")->fetch_assoc();
    $salvo = true;
}

?>

<div class="container">
  <div class="d-flex align-items-start align-items-lg-center justify-content-between gap-3 mb-4 flex-column flex-lg-row">
    <div>
      <h1 class="h3 mb-1">Detalhes do curso</h1>
      <div class="text-muted"><?= htmlspecialchars($curso['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-primary" href="cursos.php"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
      <a class="btn btn-primary" href="<?= htmlspecialchars(ios_url('/curso.php?id=' . (int)$curso_id), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">
        <i class="bi bi-box-arrow-up-right me-1"></i>Ver no site
      </a>
    </div>
  </div>

  <?php if ($salvo): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="bi bi-check-circle-fill"></i>
      <span>Todas as alterações foram salvas com sucesso!</span>
    </div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <?php if (!$cursoInfosEnabled): ?>
    <div class="alert alert-warning" role="alert">
      <div class="d-flex gap-2">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
          <div class="fw-semibold">Tabela opcional não encontrada: curso_infos</div>
          <div class="small">Para habilitar datas/local/modalidade/turno/vagas de forma dinâmica, rode o SQL em <strong>sql/curso_infos.sql</strong> no banco <strong>ios</strong>.</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- UNIFIED FORM - All fields in one form with one save button -->
  <form method="post" enctype="multipart/form-data">
    
    <!-- Card 1: Informações do Curso -->
    <div class="card card-soft mb-4">
      <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-event me-2 text-primary"></i>Informações do Curso</h5>
        <small class="text-muted">Datas, local, modalidade e vagas</small>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label">Modalidade</label>
            <select name="modalidade" class="form-select" <?= !$cursoInfosEnabled ? 'disabled' : '' ?>>
              <?php
                $opts = ['' => 'Selecione', 'Presencial' => 'Presencial', 'Online' => 'Online', 'Híbrido' => 'Híbrido'];
                foreach ($opts as $val => $label) {
                    $selected = ((string)$info['modalidade'] === (string)$val) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "\" {$selected}>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</option>";
                }
              ?>
            </select>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label">Local</label>
            <input name="local" class="form-control" value="<?= htmlspecialchars((string)$info['local'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Av. Gen. Ataliba Leonel, 245 - Santana - SP" <?= !$cursoInfosEnabled ? 'disabled' : '' ?>>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Data de início</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars((string)$info['data_inicio'], ENT_QUOTES, 'UTF-8') ?>" <?= !$cursoInfosEnabled ? 'disabled' : '' ?>>
            <small class="text-muted">Alunos só acessam após essa data</small>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Data de fim</label>
            <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars((string)$info['data_fim'], ENT_QUOTES, 'UTF-8') ?>" <?= !$cursoInfosEnabled ? 'disabled' : '' ?>>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Turno</label>
            <select name="turno" class="form-select" <?= !$cursoInfosEnabled ? 'disabled' : '' ?>>
              <?php
                $turnos = ['' => 'Selecione', 'Manhã' => 'Manhã', 'Tarde' => 'Tarde', 'Noite' => 'Noite'];
                foreach ($turnos as $val => $label) {
                    $selected = ((string)$info['turno'] === (string)$val) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "\" {$selected}>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</option>";
                }
              ?>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Vagas</label>
            <input type="number" name="vagas" class="form-control" value="<?= htmlspecialchars((string)$info['vagas'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: 30" <?= !$cursoInfosEnabled ? 'disabled' : '' ?>>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 2: Mídia e Conteúdo -->
    <div class="card card-soft mb-4">
      <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Mídia e Conteúdo</h5>
        <small class="text-muted">Miniatura e texto explicativo do módulo</small>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Miniatura (jpg/png)</label>
            <input name="thumbnail" type="file" accept="image/*" class="form-control">
            <div class="small text-muted mt-1">
              <i class="bi bi-lightbulb text-warning me-1"></i>
              <strong>Dica:</strong> Use imagens 600×400 pixels para melhor resultado.
            </div>
            
            <div class="mt-3">
              <label class="form-label text-muted small">Ou digite o caminho existente:</label>
              <input name="thumbnail_path" type="text" class="form-control form-control-sm" placeholder="uploads/thumbnails/arquivo.png" value="<?= htmlspecialchars((string)($curso['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <?php if (!empty($curso['thumbnail'])): ?>
              <div class="mt-3 p-3 bg-light rounded">
                <div class="small text-muted mb-2">Miniatura atual:</div>
                <img src="<?= htmlspecialchars(ios_url('/' . ltrim((string)$curso['thumbnail'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="thumb" style="max-height:120px;object-fit:cover;border-radius:8px;">
              </div>
            <?php endif; ?>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Texto didático do módulo</label>
            <textarea name="modulo_texto" class="form-control" rows="8" placeholder="Explique como funciona o módulo, pré-requisitos, como o aluno deve avançar..."><?= htmlspecialchars((string)($curso['modulo_texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="small text-muted mt-1">
              <i class="bi bi-info-circle me-1"></i>
              Vídeos, PDFs e perguntas ficam nas aulas.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Single Save Button -->
    <div class="d-flex justify-content-end gap-2">
      <a href="cursos.php" class="btn btn-outline-secondary">Cancelar</a>
      <button type="submit" name="salvar_tudo" class="btn btn-primary btn-lg px-5">
        <i class="bi bi-check-lg me-2"></i>Salvar Tudo
      </button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
