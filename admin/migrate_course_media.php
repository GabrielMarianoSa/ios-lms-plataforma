<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

if (!ios_is_admin()) {
    http_response_code(403);
    echo "Acesso negado.";
    exit;
}

/**
 * Migration helper: add optional columns to `cursos` if missing.
 */
$needed = [
    'thumbnail' => "VARCHAR(255) DEFAULT NULL",
    'pdf' => "VARCHAR(255) DEFAULT NULL",
    'video_url' => "VARCHAR(255) DEFAULT NULL",
];

$report = [];
foreach ($needed as $col => $definition) {
    $res = $conn->query("SHOW COLUMNS FROM cursos LIKE '" . $conn->real_escape_string($col) . "'");
    if ($res && $res->num_rows > 0) {
        $report[$col] = 'exists';
        continue;
    }

    $sql = "ALTER TABLE cursos ADD COLUMN {$col} {$definition}";
    if ($conn->query($sql) === true) {
        $report[$col] = 'added';
    } else {
        $report[$col] = 'error: ' . $conn->error;
    }
}

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Migration - Cursos media</title>
  <link href="<?= ios_url('/assets/css/site.css') ?>" rel="stylesheet">
</head>
<body class="p-4">
  <h1>Migration: curso media columns</h1>
  <ul>
    <?php foreach ($report as $k => $v): ?>
      <li><strong><?= htmlspecialchars($k) ?>:</strong> <?= htmlspecialchars($v) ?></li>
    <?php endforeach; ?>
  </ul>
  <p><a href="cursos.php">Voltar</a></p>
</body>
</html>
