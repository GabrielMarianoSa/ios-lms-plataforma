<?php
require '../config/db.php';

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

<h2>Cadastro - IOS</h2>

<form method="POST">
    <input type="text" name="nome" placeholder="Nome" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="senha" placeholder="Senha" required><br><br>
    <button type="submit">Cadastrar</button>
</form>
