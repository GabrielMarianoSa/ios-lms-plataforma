<?php
session_start();
require 'config/db.php';

$user_id = $_SESSION['user_id'];
$aula_id = $_GET['aula_id'];
$curso_id = $_GET['curso_id'];

// Verificar se já existe
$check = $conn->query("SELECT id FROM progresso WHERE user_id=$user_id AND aula_id=$aula_id");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO progresso (user_id, aula_id, concluida) VALUES ($user_id, $aula_id, 1)");
}

header("Location: curso.php?id=$curso_id");
exit;
