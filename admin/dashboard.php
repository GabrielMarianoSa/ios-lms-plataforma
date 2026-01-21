<?php
require 'protect.php';
require '../config/db.php';

$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalCursos = $conn->query("SELECT COUNT(*) as total FROM cursos")->fetch_assoc()['total'];
$totalInscricoes = $conn->query("SELECT COUNT(*) as total FROM inscricoes")->fetch_assoc()['total'];
?>

<h1>Painel Administrativo - IOS</h1>

<p>Total de alunos: <?= $totalUsers ?></p>
<p>Total de cursos: <?= $totalCursos ?></p>
<p>Total de inscrições: <?= $totalInscricoes ?></p>

<hr>

<a href="cursos.php">Gerenciar Cursos</a><br>
<a href="inscricoes.php">Ver Inscrições</a><br>
<a href="aulas.php">Gerenciar Aulas (LMS)</a><br>


<a href="integracoes.php">Integrações (RD Station / Protheus)</a><br>

<a href="../index.php">Voltar ao site</a>
