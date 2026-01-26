<?php
session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/sql/db_check_inscricoes.php'; // Ensure DB structure

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$curso_id = (int)($_GET['curso_id'] ?? 0);

if ($curso_id <= 0) {
    header('Location: cursos.php');
    exit;
}

// Fetch course details
$stmt = $conn->prepare("SELECT titulo FROM cursos WHERE id = ?");
$stmt->bind_param('i', $curso_id);
$stmt->execute();
$res = $stmt->get_result();
$curso = $res->fetch_assoc();
if (!$curso) {
    echo "Curso não encontrado.";
    exit;
}
$curso_nome = $curso['titulo'];

// Verifica se as inscrições estão abertas
$stmt = $conn->prepare("SELECT inscricoes_abertas FROM cursos WHERE id = ?");
$stmt->bind_param('i', $curso_id);
$stmt->execute();
$cStatus = $stmt->get_result()->fetch_assoc();
$inscricoesAbertas = (!isset($cStatus['inscricoes_abertas']) || $cStatus['inscricoes_abertas'] == 1);

if (!$inscricoesAbertas) {
    $pageTitle = 'Inscrições Fechadas';
    $activeNav = 'cursos';
    require __DIR__ . '/partials/header.php';
    echo '<div class="container py-5">
        <div class="alert alert-warning d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
            <div>
                <h5 class="alert-heading fw-bold mb-1">Inscrições Encerradas</h5>
                <p class="mb-0">No momento, as inscrições para o curso <strong>' . htmlspecialchars($curso_nome) . '</strong> estão fechadas. Fique atento a novas turmas.</p>
            </div>
        </div>
        <div class="mt-4"><a href="cursos.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-2"></i>Voltar para Cursos</a></div>
    </div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Check for existing enrollment
$stmt = $conn->prepare("SELECT id, status FROM inscricoes WHERE user_id = ? AND curso_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('ii', $user_id, $curso_id);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();

if ($enrollment) {
    $status = ios_normalize_inscricao_status($enrollment['status']);
    if ($status === 'aprovado') {
        // Already approved
        header('Location: curso.php?id=' . $curso_id);
        exit;
    }
    if ($status === 'pendente') {
        // Pending approval
        $pageTitle = 'Inscrição em Análise';
        $activeNav = 'cursos';
        require __DIR__ . '/partials/header.php';
        echo '<div class="container py-5"><div class="alert alert-info border-info border-opacity-25 bg-info bg-opacity-10 d-flex align-items-center gap-3">
            <i class="bi bi-hourglass-split fs-3 text-info"></i>
            <div>
                <h5 class="alert-heading mb-1 text-info fw-bold">Solicitação em Análise</h5>
                <p class="mb-0 small text-muted">Sua inscrição para <strong>' . htmlspecialchars($curso_nome) . '</strong> já foi enviada e está sendo analisada por nossa equipe. Aguarde o retorno.</p>
            </div>
        </div>
        <div class="mt-4"><a href="aluno/dashboard.php" class="btn btn-primary"><i class="bi bi-arrow-left me-2"></i>Voltar ao Painel</a></div>
        </div>';
        require __DIR__ . '/partials/footer.php';
        exit;
    }
    // If 'negado', allow re-application (logic continues below)
}

// Handle Form Submission
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate Form
    $renda = $_POST['renda'] ?? '';
    $pessoas = $_POST['pessoas'] ?? '';
    $internet = $_POST['internet'] ?? '';
    $escola = $_POST['escola'] ?? '';
    $trabalho = $_POST['trabalho'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    if (!$renda || !$pessoas || !$internet || !$escola || !$trabalho) {
        $error = "Por favor, preencha todas as perguntas do questionário socioeconômico.";
    } else {
        $dados = [
            'renda_per_capita' => $renda,
            'pessoas_residencia' => $pessoas,
            'acesso_internet' => $internet,
            'escola_publica' => $escola,
            'trabalha' => $trabalho,
            'motivo' => $motivo,
            'data_solicitacao' => date('Y-m-d H:i:s')
        ];
        $json_dados = json_encode($dados, JSON_UNESCAPED_UNICODE);

        // Insert new enrollment request
        $stmt = $conn->prepare("INSERT INTO inscricoes (user_id, curso_id, status, dados_formulario, criado_em) VALUES (?, ?, 'pendente', ?, NOW())");
        $stmt->bind_param('iis', $user_id, $curso_id, $json_dados);
        
        if ($stmt->execute()) {
             // Enviar para RD Station (simulado - mantendo lógica anterior)
             @file_get_contents("http://localhost/ios/api/rdstation.php", false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query([
                        'user_id' => $user_id,
                        'curso_id' => $curso_id,
                        'status' => 'pendente'
                    ])
                ]
            ]));

            // Success Page
            $pageTitle = 'Solicitação Enviada';
            $activeNav = 'cursos';
            require __DIR__ . '/partials/header.php';
            ?>
            <div class="container py-5">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4 text-success scale-up-center">
                            <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="fw-bold mb-3">Solicitação Recebida!</h2>
                        <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">
                            Sua solicitação de inscrição para o curso <strong><?= htmlspecialchars($curso_nome) ?></strong> foi enviada com sucesso.
                            <br>Nossa equipe analisará seu perfil e em breve você receberá uma resposta.
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="<?= ios_url('/aluno/dashboard.php') ?>" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-speedometer2 me-2"></i>Ir para Área do Aluno
                            </a>
                            <a href="<?= ios_url('/cursos.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold">
                                Ver mais cursos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            require __DIR__ . '/partials/footer.php';
            exit;
        } else {
            $error = "Erro ao salvar inscrição: " . $conn->error;
        }
    }
}

$pageTitle = 'Solicitação de Inscrição';
$activeNav = 'cursos';
require __DIR__ . '/partials/header.php';
?>

<style>
    .form-check-input:checked {
        background-color: var(--ios-primary, #0d6efd);
        border-color: var(--ios-primary, #0d6efd);
    }
    .step-card {
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 1rem;
        transition: transform 0.2s;
    }
    .step-card:hover {
        transform: translateY(-2px);
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <h1 class="fw-bold h2 mb-2">Solicitar Inscrição</h1>
                <p class="text-muted">Curso: <strong class="text-primary"><?= htmlspecialchars($curso_nome) ?></strong></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-4 rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-circle-fill fs-5"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary bg-gradient text-white p-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-clipboard-data fs-2"></i>
                        <div>
                            <h5 class="mb-1 fw-bold">Questionário Socioeconômico</h5>
                            <small class="text-white-50">Responda para que possamos avaliar sua bolsa de estudos integral.</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5 bg-light bg-opacity-25">
                    <form method="POST" action="">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3 d-block">1. Qual a renda mensal aproximada de sua família (soma de todos que trabalham)?</label>
                            <div class="bg-white p-3 rounded-3 shadow-sm step-card">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="renda" id="renda1" value="Ate 1 salario" required>
                                    <label class="form-check-label" for="renda1">Até 1 salário mínimo (R$ 1.412,00)</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="renda" id="renda2" value="1 a 2 salarios">
                                    <label class="form-check-label" for="renda2">De 1 a 2 salários mínimos</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="renda" id="renda3" value="2 a 4 salarios">
                                    <label class="form-check-label" for="renda3">De 2 a 4 salários mínimos</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="renda" id="renda4" value="Acima de 4 salarios">
                                    <label class="form-check-label" for="renda4">Acima de 4 salários mínimos</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3 d-block">2. Quantas pessoas moram com você?</label>
                            <div class="bg-white p-3 rounded-3 shadow-sm step-card">
                                <input type="number" name="pessoas" class="form-control" placeholder="Ex: 3" min="0" max="20" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3 d-block">3. Você possui computador e internet em casa?</label>
                            <div class="bg-white p-3 rounded-3 shadow-sm step-card">
                                <select name="internet" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="Sim, ambos">Sim, computador e internet</option>
                                    <option value="Apenas internet (celular)">Apenas internet (uso no celular)</option>
                                    <option value="Nao">Não possuo</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3 d-block">4. Em qual tipo de escola você estuda ou estudou?</label>
                            <div class="bg-white p-3 rounded-3 shadow-sm step-card">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="escola" id="escola1" value="Publica" required>
                                    <label class="form-check-label" for="escola1">Escola Pública</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="escola" id="escola2" value="Particular com bolsa">
                                    <label class="form-check-label" for="escola2">Particular com bolsa integral</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="escola" id="escola3" value="Particular">
                                    <label class="form-check-label" for="escola3">Particular</label>
                                </div>
                            </div>
                        </div>

                         <div class="mb-4">
                            <label class="form-label fw-bold mb-3 d-block">5. Você trabalha atualmente?</label>
                             <div class="bg-white p-3 rounded-3 shadow-sm step-card">
                                <select name="trabalho" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="Nao">Não, estou buscando a primeira oportunidade</option>
                                    <option value="Nao desempregado">Não, estou desempregado</option>
                                    <option value="Sim formal">Sim, carteira assinada</option>
                                    <option value="Sim informal">Sim, bicos/informal</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-5">
                            <label class="form-label fw-bold mb-3 d-block">Por que você quer fazer este curso? (Opcional)</label>
                            <textarea name="motivo" class="form-control" rows="3" placeholder="Conte um pouco sobre seus objetivos..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold hover-scale shadow-sm">
                                <i class="bi bi-send-fill me-2"></i>Enviar Solicitação
                            </button>
                            <a href="<?= ios_url('/cursos.php') ?>" class="btn btn-link link-secondary text-decoration-none">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>

