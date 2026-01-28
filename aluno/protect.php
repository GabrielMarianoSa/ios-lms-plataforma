<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ios_url('/auth/login.php'));
    exit;
}
