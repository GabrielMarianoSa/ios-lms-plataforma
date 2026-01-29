<?php

declare(strict_types=1);

/**
 * Chatbot IA - Lulu (powered by Groq)
 * 
 * Endpoint seguro que conecta com Groq API para responder perguntas sobre o IOS.
 * A chave API fica no backend e NUNCA é exposta ao frontend.
 */

require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// ===== SEGURANÇA: Rate Limiting Básico =====
// Sessão já foi iniciada em bootstrap.php, não precisa chamar session_start() novamente

if (!isset($_SESSION['lulu_requests'])) {
    $_SESSION['lulu_requests'] = [];
}

// Limpa requests antigos (>1 min)
$_SESSION['lulu_requests'] = array_filter(
    $_SESSION['lulu_requests'],
    fn($t) => (time() - $t) < 60
);

// Limite: 10 requests por minuto
if (count($_SESSION['lulu_requests']) >= 10) {
    http_response_code(429);
    echo json_encode([
        'ok' => false,
        'error' => 'Muitas perguntas! Aguarde um pouco e tente novamente. 😊'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['lulu_requests'][] = time();

// ===== RECEBE MENSAGEM DO USUÁRIO =====
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$userMessage = trim((string)($data['message'] ?? ''));

if ($userMessage === '') {
    echo json_encode(['ok' => false, 'error' => 'Mensagem vazia'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== BUSCA INFORMAÇÕES DO BANCO (Contexto Dinâmico) =====
$cursos = [];
$result = $conn->query('SELECT titulo, descricao, carga_horaria FROM cursos ORDER BY id DESC LIMIT 10');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cursos[] = [
            'titulo' => $row['titulo'],
            'descricao' => substr($row['descricao'], 0, 200),
            'carga_horaria' => $row['carga_horaria']
        ];
    }
}

// ===== CONTEXTO DA IA (Sistema Prompt) =====
$cursosText = '';
foreach ($cursos as $c) {
    $cursosText .= "- {$c['titulo']} ({$c['carga_horaria']}h): {$c['descricao']}\n";
}

$systemPrompt = <<<PROMPT
Você é Lulu, a assistente virtual do **Instituto da Oportunidade Social (IOS)**.

**SOBRE O IOS:**
- Nome: Instituto da Oportunidade Social (IOS)
- Site oficial: https://ios.org.br/
- Missão: Transformar vidas por meio de educação profissionalizante gratuita.
- História: Mais de 24 anos de atuação.
- Números: +50.000 alunos formados, ~1.000 alunos por ano, 83% de empregabilidade.
- Parceiros: TOTVS, Dell, Microsoft, Zendesk, IBM e outras empresas de tecnologia.

**CURSOS DISPONÍVEIS (atualizados do banco):**
{$cursosText}

**COMO FUNCIONA:**
1. O aluno cria uma conta na plataforma.
2. Navega pelos cursos disponíveis.
3. Solicita inscrição no curso desejado.
4. Aguarda análise do administrador (status: Em análise / Aprovada / Negada).
5. Se aprovado, acessa as aulas e acompanha seu progresso na Área do Aluno.

**CRITÉRIOS DE APROVAÇÃO:**
A aprovação depende da análise do administrador e das regras da turma/edital. O aluno consegue acompanhar o status na Área do Aluno.

**FAIXA ETÁRIA:**
Pode variar por turma e edital. Para informações oficiais e sempre atualizadas, consulte o site do IOS: https://ios.org.br/

**SUA PERSONALIDADE:**
- Seja simpática, prestativa e objetiva.
- Use emojis moderadamente para dar calor humano (😊, 💜, 📚, etc).
- Mantenha respostas curtas (máx. 3-4 linhas) sempre que possível.
- Se não souber algo, indique o site oficial do IOS: https://ios.org.br/
- NUNCA invente informações. Se não tiver certeza, diga que não sabe.

**REGRAS IMPORTANTES:**
- Você responde APENAS sobre o IOS, cursos, inscrições e educação profissionalizante.
- NÃO responda perguntas sobre outros assuntos (política, religião, entretenimento, etc).
- Se perguntarem algo fora do escopo, diga educadamente: "Eu só posso ajudar com dúvidas sobre o IOS e nossos cursos. 😊"
PROMPT;

// ===== CHAMA GROQ API =====
$apiKey = (string)(getenv('GROQ_API_KEY') ?: '');
$apiKey = trim($apiKey);

if ($apiKey === '') {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'IA não configurada no servidor. Defina a variável de ambiente GROQ_API_KEY.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Extensão cURL não está habilitada no servidor.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [
    'model' => 'llama-3.3-70b-versatile', // Modelo rápido e inteligente
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.7,
    'max_tokens' => 300, // Respostas curtas
    'top_p' => 1,
    'stream' => false
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError !== '') {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Erro de conexão com a IA. Tente novamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'A IA está temporariamente indisponível. Por favor, tente novamente em instantes.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = json_decode($response, true);

if (!isset($result['choices'][0]['message']['content'])) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Resposta inválida da IA.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$aiResponse = trim($result['choices'][0]['message']['content']);

// ===== RETORNA RESPOSTA =====
echo json_encode([
    'ok' => true,
    'message' => $aiResponse,
    'timestamp' => time()
], JSON_UNESCAPED_UNICODE);
