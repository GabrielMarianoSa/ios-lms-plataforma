<?php
require 'config/db.php';

// Check if 'status' column exists in 'inscricoes'
$checkStatus = $conn->query("SHOW COLUMNS FROM inscricoes LIKE 'status'");
if ($checkStatus->num_rows == 0) {
    echo "Adding 'status' column to 'inscricoes'...\n";
    $conn->query("ALTER TABLE inscricoes ADD COLUMN status VARCHAR(20) DEFAULT 'pendente'");
} else {
    echo "'status' column already exists.\n";
    // Ensure default is pendente
    $conn->query("ALTER TABLE inscricoes MODIFY COLUMN status VARCHAR(20) DEFAULT 'pendente'");
}

// Check if 'dados_formulario' column exists in 'inscricoes'
$checkForm = $conn->query("SHOW COLUMNS FROM inscricoes LIKE 'dados_formulario'");
if ($checkForm->num_rows == 0) {
    echo "Adding 'dados_formulario' column to 'inscricoes'...\n";
    $conn->query("ALTER TABLE inscricoes ADD COLUMN dados_formulario TEXT NULL");
} else {
    echo "'dados_formulario' column already exists.\n";
}

echo "Database successfully updated for registration workflow.\n";
