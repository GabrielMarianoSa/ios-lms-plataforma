<?php
/**
 * Configuração do Banco de Dados
 * 
 * PARA PRODUÇÃO (InfinityFree, 000webhost, etc):
 * Altere as variáveis abaixo com os dados do seu servidor
 */

// Detecta se está em localhost ou produção
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
               || strpos($_SERVER['SERVER_NAME'] ?? '', '.test') !== false;

// Permite configuração via variáveis de ambiente (ex: Railway)
// Use: IOS_DB_HOST, IOS_DB_USER, IOS_DB_PASS, IOS_DB_NAME
$envHost = getenv('IOS_DB_HOST') ?: '';
$envUser = getenv('IOS_DB_USER') ?: '';
$envPass = getenv('IOS_DB_PASS') ?: '';
$envDb   = getenv('IOS_DB_NAME') ?: '';

if ($isLocalhost) {
    // ===== CONFIGURAÇÃO LOCAL (Laragon) =====
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "ios";
} elseif ($envHost !== '' && $envUser !== '' && $envDb !== '') {
    // ===== CONFIGURAÇÃO PRODUÇÃO (ENV) =====
    $host = $envHost;
    $user = $envUser;
    $pass = $envPass;
    $db   = $envDb;
} else {
    // ===== CONFIGURAÇÃO PRODUÇÃO (InfinityFree) =====
    $host = "sql308.infinityfree.com";
    $user = "if0_40996471";
    $pass = "c61UIRHRkC";
    $db   = "if0_40996471_iosprocessoseletivo";
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Define charset UTF-8
$conn->set_charset("utf8mb4");
