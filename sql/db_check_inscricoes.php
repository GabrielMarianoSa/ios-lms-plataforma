<?php
// sql/db_check_inscricoes.php
// Ensures the inscricoes table has the correct columns.
// This is a helper to run continuously since we can't easily run CLI migrations.

if (isset($conn) && $conn instanceof mysqli) {
    // Check 'status'
    $col = $conn->query("SHOW COLUMNS FROM inscricoes LIKE 'status'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE inscricoes ADD COLUMN status VARCHAR(20) DEFAULT 'pendente'");
    }

    // Check 'dados_formulario'
    $col = $conn->query("SHOW COLUMNS FROM inscricoes LIKE 'dados_formulario'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE inscricoes ADD COLUMN dados_formulario TEXT NULL");
    }

    // Check 'data_inicio'
    $col = $conn->query("SHOW COLUMNS FROM inscricoes LIKE 'data_inicio'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE inscricoes ADD COLUMN data_inicio DATE DEFAULT NULL");
    }

    // Seed Courses (one-time-ish): only seed when table is empty
    $countRes = $conn->query('SELECT COUNT(*) AS total FROM cursos');
    $totalCursos = 0;
    if ($countRes && ($r = $countRes->fetch_assoc())) {
        $totalCursos = (int)($r['total'] ?? 0);
    }

    if ($totalCursos > 0) {
        return;
    }

    $coursesToSeed = [
        'Programação Web' => ['desc' => 'Aprenda HTML, CSS e JavaScript.', 'ch' => 120],
        'Cybersegurança' => ['desc' => 'Fundamentos de segurança da informação e proteção.', 'ch' => 80],
        'ERP' => ['desc' => 'Gestão empresarial com sistemas integrados (Protheus).', 'ch' => 100],
        'Backend' => ['desc' => 'Desenvolvimento de APIs e lógica de servidor (PHP/SQL).', 'ch' => 120],
        'Zendesk' => ['desc' => 'Plataforma de atendimento e suporte.', 'ch' => 40],
        'Pacote Office' => ['desc' => 'Word, Excel e PowerPoint para empresas.', 'ch' => 60],
        'Rotinas Administrativas' => ['desc' => 'Fluxos de escritório e documentação.', 'ch' => 80]
    ];

    foreach ($coursesToSeed as $title => $info) {
        $safeTitle = $conn->real_escape_string($title);
        $check = $conn->query("SELECT id FROM cursos WHERE titulo = '$safeTitle' LIMIT 1");
        if ($check && $check->num_rows === 0) {
            $desc = $conn->real_escape_string($info['desc']);
            $ch = (int)$info['ch'];
            $conn->query("INSERT INTO cursos (titulo, descricao, carga_horaria, criado_em) VALUES ('$safeTitle', '$desc', $ch, NOW())");
        }
    }
}
