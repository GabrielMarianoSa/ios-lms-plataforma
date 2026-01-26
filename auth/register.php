<?php
require __DIR__ . '/../config/db.php';

require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (nome, email, senha) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nome, $email, $senha);
    $stmt->execute();

    header("Location: login.php");
}
?>

<?php
$pageTitle = 'Cadastro • IOS';
require __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card card-soft">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Criar conta</h1>
                    <p class="text-muted mb-3">Cadastre-se para se inscrever em cursos e acompanhar aulas.</p>

                    <form method="POST" class="vstack gap-3">
                        <div>
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" placeholder="Seu nome" required>
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="seuemail@exemplo.com" required>
                        </div>
                        <div>
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" placeholder="Crie uma senha" required>
                        </div>
                        <button name="criar" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i>Cadastrar
                        </button>
                    </form>

                    <hr>
                    <div class="small text-muted">Já tem conta? <a href="login.php">Entrar</a>.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
