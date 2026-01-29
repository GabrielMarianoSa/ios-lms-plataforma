<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string)($_GET['action'] ?? '');

if ($action === 'siteinfo') {
    $stats = [
        'anos' => 24,
        'alunos_formados' => 50000,
        'alunos_por_ano' => 1000,
        'empregabilidade_percent' => 83,
    ];

    $partners = [
        ['name' => 'TOTVS', 'image' => ios_url('/assets/images/totvs.png')],
        ['name' => 'Dell', 'image' => ios_url('/assets/images/dell.png')],
        ['name' => 'Microsoft', 'image' => ios_url('/assets/images/microsoft.png')],
        ['name' => 'Zendesk', 'image' => ios_url('/assets/images/zendesk.png')],
        ['name' => 'IBM', 'image' => ios_url('/assets/images/ibm.png')],
    ];

    // Se existir conteúdo editável no banco, usa como fonte
    try {
        require __DIR__ . '/../config/db.php';
        if (isset($conn) && ($conn instanceof mysqli) && function_exists('ios_table_exists') && ios_table_exists($conn, 'site_conteudo')) {
            $map = [];
            $res = $conn->query("SELECT chave, valor FROM site_conteudo");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $map[(string)$row['chave']] = (string)$row['valor'];
                }
            }

            $stats['anos'] = (int)($map['stats_anos'] ?? $stats['anos']);
            $stats['alunos_formados'] = (int)($map['stats_alunos_formados'] ?? $stats['alunos_formados']);
            $stats['alunos_por_ano'] = (int)($map['stats_alunos_ano'] ?? $stats['alunos_por_ano']);
            $stats['empregabilidade_percent'] = (int)($map['stats_empregabilidade'] ?? $stats['empregabilidade_percent']);
        }

        if (isset($conn) && ($conn instanceof mysqli) && function_exists('ios_table_exists') && ios_table_exists($conn, 'site_parceiros')) {
            $partners = [];
            $res = $conn->query("SELECT nome, logo FROM site_parceiros WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $logo = trim((string)($row['logo'] ?? ''));
                    $partners[] = [
                        'name' => (string)($row['nome'] ?? ''),
                        'image' => $logo !== '' ? ios_url('/' . ltrim($logo, '/')) : '',
                    ];
                }
            }
        }
    } catch (Throwable $e) {
        // Silencioso: mantém fallback
    }

    $info = [
        'ok' => true,
        'institute' => [
            'name' => 'Instituto da Oportunidade Social (IOS)',
            'site' => 'https://ios.org.br/',
        ],
        'stats' => $stats,
        'partners' => $partners,
        'faq' => [
            'como_funciona' => '1) Crie sua conta. 2) Veja os cursos. 3) Solicite inscrição no curso. 4) Aguarde o status (Em análise / Aprovada / Negada). 5) Se aprovado, você acessa as aulas e acompanha seu progresso.',
            'criterios' => 'A aprovação depende da análise do administrador e das regras da turma/edital. Você consegue acompanhar o status na Área do Aluno.',
            'faixa_etaria' => 'A faixa etária pode variar por turma/edital. Para informação oficial e sempre atualizada, consulte o site do IOS.',
        ],
    ];

    echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'courses') {
    try {
        require __DIR__ . '/../config/db.php';

        if (!isset($conn) || !($conn instanceof mysqli)) {
            echo json_encode(['ok' => false, 'error' => 'Conexão com banco indisponível']);
            exit;
        }

        $res = $conn->query('SELECT id, titulo, carga_horaria FROM cursos ORDER BY id DESC LIMIT 25');
        $courses = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $courses[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'titulo' => (string)($row['titulo'] ?? ''),
                    'carga_horaria' => isset($row['carga_horaria']) ? (int)$row['carga_horaria'] : null,
                ];
            }
        }

        echo json_encode(['ok' => true, 'courses' => $courses], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Erro ao buscar cursos'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['ok' => true, 'message' => 'Lulu chatbot API'], JSON_UNESCAPED_UNICODE);
