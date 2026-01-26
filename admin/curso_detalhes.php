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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$cursoInfosEnabled) {
        $erro = 'A tabela curso_infos ainda não existe. Crie a tabela antes de salvar.';
    } else {
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

        $salvo = true;
        $info = [
            'modalidade' => $modalidade,
            'local' => $local,
            'data_inicio' => $data_inicio ?? '',
            'data_fim' => $data_fim ?? '',
            'turno' => $turno,
            'vagas' => $vagasInt ?? '',
        ];
    }
}

      // Handle media saves (thumbnail/pdf/video) into cursos table
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['salvar_midias']) || isset($_POST['salvar_infos']))) {
        // reload curso in case
        $curso = $conn->query("SELECT * FROM cursos WHERE id = {$curso_id}")->fetch_assoc();

        // check available columns
        $colsRes = $conn->query("SHOW COLUMNS FROM cursos");
        $cols = [];
        while ($r = $colsRes->fetch_assoc()) $cols[] = $r['Field'];

        $updates = [];

        if (in_array('thumbnail', $cols, true) && !empty($_FILES['thumbnail']['tmp_name'])) {
          $up = $_FILES['thumbnail'];
          $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
          $thumbnailName = 'uploads/thumbnails/' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
          if (!is_dir(__DIR__ . '/../uploads/thumbnails')) mkdir(__DIR__ . '/../uploads/thumbnails', 0755, true);
          move_uploaded_file($up['tmp_name'], __DIR__ . '/../' . $thumbnailName);
          $updates['thumbnail'] = $thumbnailName;
        }

        // allow specifying existing path
        if (in_array('thumbnail', $cols, true) && !empty($_POST['thumbnail_path'])) {
          $path = trim((string)$_POST['thumbnail_path']);
          if ($path !== '') $updates['thumbnail'] = $path;
        }

        if (in_array('pdf', $cols, true) && !empty($_FILES['pdf']['tmp_name'])) {
          $up = $_FILES['pdf'];
          $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
          $pdfName = 'uploads/materials/' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
          if (!is_dir(__DIR__ . '/../uploads/materials')) mkdir(__DIR__ . '/../uploads/materials', 0755, true);
          move_uploaded_file($up['tmp_name'], __DIR__ . '/../' . $pdfName);
          $updates['pdf'] = $pdfName;
        }

        if (in_array('pdf', $cols, true) && !empty($_POST['pdf_path'])) {
          $path = trim((string)$_POST['pdf_path']);
          if ($path !== '') $updates['pdf'] = $path;
        }

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
          $salvo = true;
          // refresh curso
          $curso = $conn->query("SELECT * FROM cursos WHERE id = {$curso_id}")->fetch_assoc();
        }
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
    <div class="alert alert-success">Detalhes salvos com sucesso.</div>
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

  <div class="card card-soft">
    <div class="card-body">
      <form method="post" class="row g-3" enctype="multipart/form-data">
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

        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary" <?= !$cursoInfosEnabled ? 'disabled' : '' ?> name="salvar_infos"><i class="bi bi-save me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card card-soft mt-4">
    <div class="card-body">
      <h5>Módulo (imagem + texto)</h5>
      <form method="post" enctype="multipart/form-data" class="row g-3 mt-2">
        <div class="col-12 col-md-6">
          <label class="form-label">Miniatura (jpg/png)</label>
          <input name="thumbnail" type="file" accept="image/*" class="form-control">
          <div class="small text-muted mt-1">Ou cole o caminho existente em <code>uploads/thumbnails/</code> abaixo</div>
          <input name="thumbnail_path" type="text" class="form-control mt-2" placeholder="uploads/thumbnails/arquivo.png" value="<?= htmlspecialchars((string)($curso['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($curso['thumbnail'])): ?>
            <div class="mt-2"><img src="<?= htmlspecialchars(ios_url('/' . ltrim((string)$curso['thumbnail'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="thumb" style="height:80px;object-fit:cover;border-radius:6px;"></div>
          <?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">Texto didático do módulo</label>
          <textarea name="modulo_texto" class="form-control" rows="8" placeholder="Explique como funciona o módulo..."><?= htmlspecialchars((string)($curso['modulo_texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          <div class="small text-muted mt-1">Vídeos, PDFs e perguntas ficam nas aulas.</div>
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary" name="salvar_midias"><i class="bi bi-save me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
