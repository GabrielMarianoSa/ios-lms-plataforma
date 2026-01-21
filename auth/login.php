<?php
session_start();
require '../config/db.php';

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

        header("Location: ../index.php");
        exit;
    } else {
        $erro = "Login inválido";
    }
}
?>

<h2>Login - IOS</h2>

<?php if (isset($erro)) echo "<p style='color:red'>$erro</p>"; ?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="senha" placeholder="Senha" required><br><br>
    <button type="submit">Entrar</button>
</form>
