<?php
/**
 * Database Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to 'db.php' in the same directory
 * 2. Fill in your database credentials below
 * 3. NEVER commit db.php to version control (it's in .gitignore)
 * 
 * Database Requirements:
 * - MySQL 5.7+ or MariaDB 10.3+
 * - Database name: ios (or your choice)
 * - UTF-8 encoding recommended
 */

$conn = new mysqli(
    'localhost',     // DB Host
    'root',          // DB Username  
    '',              // DB Password
    'ios'            // DB Name
);

if ($conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
