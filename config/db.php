<?php
/**
 * Configuração do Banco de Dados
 * 
 * RECOMENDADO:
 * - Local: usar MySQL do Laragon (host=localhost, user=root, db=ios)
 * - Produção (Railway/Cloud): usar variáveis de ambiente
 */

// Detecta se está em localhost ou produção
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
               || strpos($_SERVER['SERVER_NAME'] ?? '', '.test') !== false;

// Permite configuração via variáveis de ambiente (ex: Railway)
// Preferência:
// - IOS_DB_HOST, IOS_DB_USER, IOS_DB_PASS, IOS_DB_NAME
// Compatibilidade Railway (MySQL plugin costuma expor):
// - MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
$envUrl  = getenv('IOS_DB_URL') ?: (getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: ''));
$envHost = getenv('IOS_DB_HOST') ?: (getenv('MYSQLHOST') ?: '');
$envUser = getenv('IOS_DB_USER') ?: (getenv('MYSQLUSER') ?: '');
$envPass = getenv('IOS_DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');
$envDb   = getenv('IOS_DB_NAME') ?: (getenv('MYSQLDATABASE') ?: '');
$envPort = (int)(getenv('IOS_DB_PORT') ?: (getenv('MYSQLPORT') ?: '0'));

// If a URL is provided, parse it (mysql://user:pass@host:port/db)
if ($envUrl !== '') {
    $parts = parse_url($envUrl);
    if (is_array($parts)) {
        $envHost = $envHost !== '' ? $envHost : (string)($parts['host'] ?? '');
        $envUser = $envUser !== '' ? $envUser : (string)($parts['user'] ?? '');
        $envPass = $envPass !== '' ? $envPass : (string)($parts['pass'] ?? '');
        $path = (string)($parts['path'] ?? '');
        $dbFromUrl = ltrim($path, '/');
        $envDb = $envDb !== '' ? $envDb : $dbFromUrl;
        if ($envPort === 0 && isset($parts['port'])) {
            $envPort = (int)$parts['port'];
        }
    }
}

// Allow HOST:PORT format
if ($envHost !== '' && str_contains($envHost, ':') && $envPort === 0) {
    [$h, $p] = explode(':', $envHost, 2);
    $envHost = $h;
    $envPort = (int)$p;
}

if ($envPort === 0) {
    $envPort = 3306;
}

if ($isLocalhost) {
    // ===== CONFIGURAÇÃO LOCAL (Laragon) =====
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "ios";
    $port = 3306;
} elseif ($envHost !== '' && $envUser !== '' && $envDb !== '') {
    // ===== CONFIGURAÇÃO PRODUÇÃO (ENV) =====
    $host = $envHost;
    $user = $envUser;
    $pass = $envPass;
    $db   = $envDb;
    $port = $envPort;
} else {
    // Produção sem ENV configurado: falhar com mensagem clara (evita credenciais hardcoded no GitHub)
    http_response_code(500);
    die('Banco não configurado. Defina IOS_DB_HOST/IOS_DB_USER/IOS_DB_PASS/IOS_DB_NAME (ou MYSQLHOST/MYSQLUSER/MYSQLPASSWORD/MYSQLDATABASE).');
}

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Define charset UTF-8
$conn->set_charset("utf8mb4");
