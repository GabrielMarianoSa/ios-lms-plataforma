<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

if (!ios_is_admin()) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$needed = [
    'video_url' => 'VARCHAR(255) DEFAULT NULL',
    'pdf' => 'VARCHAR(255) DEFAULT NULL',
];

$report = [];
foreach ($needed as $col => $definition) {
    $res = $conn->query("SHOW COLUMNS FROM aulas LIKE '" . $conn->real_escape_string($col) . "'");
    if ($res && $res->num_rows > 0) {
        $report[$col] = 'exists';
        continue;
    }

    $sql = "ALTER TABLE aulas ADD COLUMN {$col} {$definition}";
    if ($conn->query($sql) === true) {
        $report[$col] = 'added';
    } else {
        $report[$col] = 'error: ' . $conn->error;
    }
}

?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Migration - Aulas media</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="h3 mb-3">Migration: aulas (mídias)</h1>
    <div class="card">
      <div class="card-body">
        <ul class="mb-0">
          <?php foreach ($report as $k => $v): ?>
            <li><strong><?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="mt-3 d-flex gap-2">
      <a class="btn btn-primary" href="aulas.php">Voltar para Admin → Aulas</a>
      <a class="btn btn-outline-secondary" href="cursos.php">Voltar para Admin → Cursos</a>
    </div>
  </div>
</body>
</html>
