<?php
require __DIR__ . '/../config/db.php';

$coursesToSeed = [
    [
        'titulo' => 'Programação Web',
        'descricao' => 'Aprenda HTML, CSS e JavaScript para criar sites modernos e responsivos.',
        'carga_horaria' => 120
    ],
    [
        'titulo' => 'Cybersegurança',
        'descricao' => 'Fundamentos de segurança da informação, proteção de dados e prevenção de ataques.',
        'carga_horaria' => 80
    ],
    [
        'titulo' => 'ERP',
        'descricao' => 'Gestão empresarial com sistemas integrados (TOTVS/Protheus).',
        'carga_horaria' => 100
    ],
    [
        'titulo' => 'Backend',
        'descricao' => 'Desenvolvimento de APIs e lógica de servidor com PHP e Banco de Dados.',
        'carga_horaria' => 120
    ],
    [
        'titulo' => 'Zendesk',
        'descricao' => 'Plataforma de atendimento ao cliente e suporte omnichannel.',
        'carga_horaria' => 40
    ],
    [
        'titulo' => 'Pacote Office',
        'descricao' => 'Domine Word, Excel e PowerPoint para o ambiente corporativo.',
        'carga_horaria' => 60
    ],
    [
        'titulo' => 'Rotinas Administrativas',
        'descricao' => 'Principais atividades de um auxiliar administrativo.',
        'carga_horaria' => 80
    ]
];

echo "Seeding courses...\n";

foreach ($coursesToSeed as $c) {
    // Check if exists
    $safeTitle = $conn->real_escape_string($c['titulo']);
    $check = $conn->query("SELECT id FROM cursos WHERE titulo = '$safeTitle'");
    
    if ($check->num_rows == 0) {
        $desc = $conn->real_escape_string($c['descricao']);
        $ch = (int)$c['carga_horaria'];
        $sql = "INSERT INTO cursos (titulo, descricao, carga_horaria, criado_em) VALUES ('$safeTitle', '$desc', $ch, NOW())";
        
        if ($conn->query($sql)) {
            echo "[Created] {$c['titulo']}\n";
        } else {
            echo "[Error] Failed to create {$c['titulo']}: " . $conn->error . "\n";
        }
    } else {
        echo "[Exists] {$c['titulo']}\n";
    }
}

echo "Done.";
