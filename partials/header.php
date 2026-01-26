<?php
/** @var string|null $pageTitle */
/** @var string|null $activeNav */
/** @var bool|null $isAdminArea */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/bootstrap.php';

$title = isset($pageTitle) && trim((string)$pageTitle) !== '' ? (string)$pageTitle : 'IOS - Plataforma de Cursos';
$active = $activeNav ?? '';
$adminArea = (bool)($isAdminArea ?? false);

?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= htmlspecialchars(ios_url('/assets/css/site.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= htmlspecialchars(ios_url('/index.php'), ENT_QUOTES, 'UTF-8') ?>">
      IOS
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navIos" aria-controls="navIos" aria-expanded="false" aria-label="Alternar navegação">
      <i class="bi bi-list fs-1 text-primary"></i>
    </button>

    <div class="collapse navbar-collapse" id="navIos">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= htmlspecialchars(ios_url('/index.php'), ENT_QUOTES, 'UTF-8') ?>">Início</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $active === 'cursos' ? 'active' : '' ?>" href="<?= htmlspecialchars(ios_url('/cursos.php'), ENT_QUOTES, 'UTF-8') ?>">Cursos</a>
        </li>
        <?php if (ios_is_logged_in()): ?>
          <li class="nav-item">
            <a class="nav-link <?= $active === 'aluno' ? 'active' : '' ?>" href="<?= htmlspecialchars(ios_url('/aluno/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">Área do Aluno</a>
          </li>
        <?php endif; ?>
        <?php if (ios_is_admin()): ?>
          <li class="nav-item">
            <a class="nav-link <?= $active === 'admin' ? 'active' : '' ?>" href="<?= htmlspecialchars(ios_url('/admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">Admin</a>
          </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex gap-2 align-items-center ms-lg-3">
        <?php if (!ios_is_logged_in()): ?>
          <a class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold" href="<?= htmlspecialchars(ios_url('/auth/login.php'), ENT_QUOTES, 'UTF-8') ?>">Área do Aluno</a>
          <a class="btn btn-sm btn-primary rounded-pill px-4 fw-bold" href="<?= htmlspecialchars(ios_url('/auth/register.php'), ENT_QUOTES, 'UTF-8') ?>">Inscrever-se</a>
        <?php else: ?>
          <?php
            $avatarUrl = $_SESSION['avatar'] ?? ios_url('/assets/images/avatar.png');
          ?>
          <div class="dropdown">
            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" role="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle border border-2 border-white shadow-sm" style="width:40px;height:40px;object-fit:cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="<?= htmlspecialchars(ios_url('/aluno/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">Minha Área</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= htmlspecialchars(ios_url('/auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>">Sair</a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<main class="py-4">
