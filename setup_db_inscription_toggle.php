<?php
require 'config/db.php';
require 'partials/bootstrap.php';

echo "<h2>Migrating Database...</h2>";

// 1. Add inscricoes_abertas to cursos if not exists
$check = $conn->query("SHOW COLUMNS FROM cursos LIKE 'inscricoes_abertas'");
if ($check->num_rows === 0) {
    echo "Adding `inscricoes_abertas` column to `cursos`...<br>";
    $conn->query("ALTER TABLE cursos ADD COLUMN inscricoes_abertas TINYINT(1) DEFAULT 1");
} else {
    echo "`inscricoes_abertas` column already exists.<br>";
}

// 2. Ensure inscricoes has status column (likely already there, but strictly checking)
$check = $conn->query("SHOW COLUMNS FROM inscricoes LIKE 'status'");
if ($check->num_rows === 0) {
    echo "Adding `status` column to `inscricoes`...<br>";
    $conn->query("ALTER TABLE inscricoes ADD COLUMN status VARCHAR(20) DEFAULT 'pendente'");
} else {
    echo "`status` column already exists.<br>";
}

echo "<h3>Migration Complete.</h3>";
echo "<a href='index.php'>Go Home</a>";
?>
