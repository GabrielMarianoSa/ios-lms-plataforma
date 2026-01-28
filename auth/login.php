<?php
require __DIR__ . '/../partials/bootstrap.php';
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tipo'] = $user['tipo'];
        // set avatar path in session if present
        if (!empty($user['avatar'])) {
            $_SESSION['avatar'] = ios_url('/assets/uploads/avatars/' . $user['avatar']);
        } else {
            unset($_SESSION['avatar']);
        }

        header('Location: ' . ios_url('/index.php'));
        exit;
    } else {
        $erro = "Login inválido";
    }
}
?>

<?php
$pageTitle = 'Entrar • IOS';
require __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card card-soft">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Entrar</h1>
                    <p class="text-muted mb-3">Acesse sua conta para se inscrever e acompanhar cursos.</p>

                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="POST" class="vstack gap-3">
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="seuemail@exemplo.com" required>
                        </div>
                        <div>
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                        </button>
                    </form>

                    <hr>
                    <div class="small text-muted">Não tem conta? <a href="register.php">Cadastre-se</a>.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
