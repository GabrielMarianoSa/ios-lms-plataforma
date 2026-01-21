<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$curso_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Dados do curso
$curso = $conn->query("SELECT * FROM cursos WHERE id = $curso_id")->fetch_assoc();

// Progresso do aluno
$progresso = [];
$res = $conn->query("SELECT aula_id FROM progresso WHERE user_id = $user_id AND concluida = 1");
while($p = $res->fetch_assoc()){
    $progresso[] = $p['aula_id'];
}

// Aulas do curso
$aulas = $conn->query("SELECT * FROM aulas WHERE curso_id = $curso_id");
$totalAulas = $aulas->num_rows;

$concluidas = count($progresso);
$percentual = $totalAulas > 0 ? round(($concluidas / $totalAulas) * 100) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $curso['titulo'] ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">

    <h1><?= $curso['titulo'] ?></h1>
    <p><?= $curso['descricao'] ?></p>

    <p><strong>Progresso:</strong> <?= $percentual ?>%</p>
    <hr>

    <h3>Aulas</h3>

    <ul class="list-group">
    <?php while($a = $aulas->fetch_assoc()): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <strong><?= $a['titulo'] ?></strong>

            <?php if (in_array($a['id'], $progresso)): ?>
                <span class="badge bg-success">Concluída</span>
            <?php else: ?>
                <a class="btn btn-sm btn-primary" href="concluir_aula.php?aula_id=<?= $a['id'] ?>&curso_id=<?= $curso_id ?>">
                    Marcar como concluída
                </a>
            <?php endif; ?>
        </li>
    <?php endwhile; ?>
    </ul>

    <br>
    <a class="btn btn-secondary" href="cursos.php">Voltar aos cursos</a>

</div>

</body>
</html>
