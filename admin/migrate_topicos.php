<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

if (!ios_is_admin()) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$report = [];

// 1) Create `topicos` table if missing
if (!ios_table_exists($conn, 'topicos')) {
    $sql = "CREATE TABLE topicos (
      id INT NOT NULL AUTO_INCREMENT,
      curso_id INT NOT NULL,
      titulo VARCHAR(150) NOT NULL,
      ordem INT DEFAULT 1,
      criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      INDEX idx_topicos_curso (curso_id),
      INDEX idx_topicos_curso_ordem (curso_id, ordem, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

    $report[] = $conn->query($sql) ? 'Tabela topicos: criada' : ('Tabela topicos: erro: ' . $conn->error);
} else {
    $report[] = 'Tabela topicos: já existe';
}

// 2) Add `topico_id` column to aulas if missing
$colRes = $conn->query("SHOW COLUMNS FROM aulas LIKE 'topico_id'");
if ($colRes && $colRes->num_rows === 0) {
    $report[] = $conn->query("ALTER TABLE aulas ADD COLUMN topico_id INT DEFAULT NULL") ? 'Coluna aulas.topico_id: adicionada' : ('Coluna aulas.topico_id: erro: ' . $conn->error);
} else {
    $report[] = 'Coluna aulas.topico_id: já existe';
}

// 3) Create index if missing
$idxRes = $conn->query("SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='aulas' AND index_name='idx_aulas_curso_topico'");
$idxCount = ($idxRes ? (int)($idxRes->fetch_assoc()['c'] ?? 0) : 0);
if ($idxCount === 0) {
    $report[] = $conn->query("CREATE INDEX idx_aulas_curso_topico ON aulas (curso_id, topico_id, id)") ? 'Índice aulas idx_aulas_curso_topico: criado' : ('Índice aulas idx_aulas_curso_topico: erro: ' . $conn->error);
} else {
    $report[] = 'Índice aulas idx_aulas_curso_topico: já existe';
}

// 4) Ensure each course has a default topic and attach existing lessons
if (ios_table_exists($conn, 'topicos')) {
    $cursosRes = $conn->query('SELECT id, titulo FROM cursos ORDER BY id ASC');
    if ($cursosRes) {
        while ($c = $cursosRes->fetch_assoc()) {
            $cursoId = (int)$c['id'];

            // Find or create default topic
            $stmt = $conn->prepare("SELECT id FROM topicos WHERE curso_id=? ORDER BY ordem ASC, id ASC LIMIT 1");
            $stmt->bind_param('i', $cursoId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            $defaultTopicoId = (int)($row['id'] ?? 0);
            if ($defaultTopicoId <= 0) {
                $tituloTopico = 'Geral';
                $ordem = 1;
                $ins = $conn->prepare('INSERT INTO topicos (curso_id, titulo, ordem) VALUES (?, ?, ?)');
                $ins->bind_param('isi', $cursoId, $tituloTopico, $ordem);
                if ($ins->execute()) {
                    $defaultTopicoId = (int)$conn->insert_id;
                    $report[] = "Curso #{$cursoId}: tópico padrão 'Geral' criado";
                } else {
                    $report[] = "Curso #{$cursoId}: erro ao criar tópico padrão: " . $conn->error;
                }
            }

            if ($defaultTopicoId > 0) {
                // Assign existing lessons without topic
                $upd = $conn->prepare('UPDATE aulas SET topico_id = ? WHERE curso_id = ? AND (topico_id IS NULL OR topico_id = 0)');
                $upd->bind_param('ii', $defaultTopicoId, $cursoId);
                if ($upd->execute()) {
                    $affected = (int)$upd->affected_rows;
                    if ($affected > 0) {
                        $report[] = "Curso #{$cursoId}: {$affected} aula(s) vinculada(s) ao tópico padrão";
                    }
                }
            }
        }
    }
}

?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Migration - Tópicos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="h3 mb-3">Migration: tópicos (Curso → Tópicos → Aulas)</h1>
    <div class="card">
      <div class="card-body">
        <ul class="mb-0">
          <?php foreach ($report as $line): ?>
            <li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
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
