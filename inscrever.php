<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$curso_id = $_GET['curso_id'];

// Verificar se já está inscrito
$check = $conn->query("SELECT id FROM inscricoes WHERE user_id=$user_id AND curso_id=$curso_id");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO inscricoes (user_id, curso_id) VALUES ($user_id, $curso_id)");
}

echo "Inscrição realizada com sucesso! <br><br>";
echo "<a href='cursos.php'>Voltar aos cursos</a>";

// Enviar para RD Station (simulado)
file_get_contents("http://localhost/ios/api/rdstation.php", false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'user_id' => $user_id,
            'curso_id' => $curso_id
        ])
    ]
]));

