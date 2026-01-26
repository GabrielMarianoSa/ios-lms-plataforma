<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

$user_id = (int)$_SESSION['user_id'];

// ensure avatars directory exists
$uploadDir = __DIR__ . '/../assets/uploads/avatars';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// ensure avatar column exists
$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
if (!$colRes || $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['action']) && $_POST['action'] === 'delete') {
        // delete avatar
        $stmt = $conn->prepare('SELECT avatar FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!empty($row['avatar'])) {
            $file = $uploadDir . '/' . $row['avatar'];
            if (file_exists($file)) {
                @unlink($file);
            }
            $upd = $conn->prepare('UPDATE users SET avatar = NULL WHERE id = ?');
            $upd->bind_param('i', $user_id);
            $upd->execute();
        }
        unset($_SESSION['avatar']);
        header('Location: avatar.php');
        exit;
    }

    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($f['type'], $allowed, true)) {
            $error = 'Formato inválido. Use JPG, PNG ou WEBP.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Arquivo muito grande. Máx 2MB.';
        } else {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $dest = $uploadDir . '/' . $filename;
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                // remove old avatar if any
                $stmt = $conn->prepare('SELECT avatar FROM users WHERE id = ?');
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if (!empty($row['avatar'])) {
                    $old = $uploadDir . '/' . $row['avatar'];
                    if (file_exists($old)) @unlink($old);
                }

                $upd = $conn->prepare('UPDATE users SET avatar = ? WHERE id = ?');
                $upd->bind_param('si', $filename, $user_id);
                $upd->execute();

                $_SESSION['avatar'] = ios_url('/assets/uploads/avatars/' . $filename);
                $success = 'Foto enviada com sucesso.';
            } else {
                $error = 'Falha ao mover arquivo.';
            }
        }
    } else {
        $error = 'Nenhum arquivo enviado.';
    }
}

// fetch current avatar
$stmt = $conn->prepare('SELECT avatar, nome FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$pageTitle = 'Foto de Perfil';
require __DIR__ . '/../partials/header.php';
?>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6">
      <div class="card card-soft">
        <div class="card-body">
          <h1 class="h5 mb-3">Foto de Perfil</h1>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>

          <div class="d-flex align-items-center gap-3 mb-3">
            <img src="<?= htmlspecialchars($_SESSION['avatar'] ?? ios_url('/assets/images/avatar.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle" style="width:120px;height:120px;object-fit:cover;">
            <div>
              <div class="fw-bold"><?= htmlspecialchars($user['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              <div class="text-muted small">Tamanho recomendado: 256x256px. Máx 2MB. JPG/PNG/WEBP.</div>
            </div>
          </div>

          <form method="POST" enctype="multipart/form-data" class="mb-3">
            <div class="mb-2">
              <input type="file" name="avatar" accept="image/*" class="form-control">
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary" type="submit">Enviar nova foto</button>
              <button class="btn btn-outline-danger" type="submit" name="action" value="delete">Remover foto</button>
            </div>
          </form>

          <a href="<?= htmlspecialchars(ios_url('/aluno/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light">Voltar</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php';
