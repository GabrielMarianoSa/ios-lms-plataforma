<?php
session_start();
require 'config/db.php';

$cursos = $conn->query("SELECT * FROM cursos");
?>

<h1>Cursos disponíveis - IOS</h1>

<?php while($c = $cursos->fetch_assoc()): ?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px">
        <h3><?= $c['titulo'] ?></h3>
        <p><?= $c['descricao'] ?></p>
        <p>Carga horária: <?= $c['carga_horaria'] ?>h</p>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="inscrever.php?curso_id=<?= $c['id'] ?>">Inscrever-se</a>
            <a href="curso.php?id=<?= $c['id'] ?>">Acessar curso</a>

        <?php else: ?>
            <a href="auth/login.php">Faça login para se inscrever</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<a href="index.php">Voltar</a>
