<?php
session_start();
require 'config/db.php';
?>

<h1>Instituto da Oportunidade Social</h1>

<?php if (!isset($_SESSION['user_id'])): ?>
    <a href="auth/register.php">Cadastrar</a> |
    <a href="auth/login.php">Login</a>
    <br><br>
<a href="cursos.php">Ver cursos</a>

<?php else: ?>
    <p>Bem-vindo!</p>
<?php endif; ?>
