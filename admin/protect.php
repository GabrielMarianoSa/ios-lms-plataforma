<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ' . ios_url('/auth/login.php'));
    exit;
}
