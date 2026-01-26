<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../sql/db_check_inscricoes.php'; // Ensure structure

$pageTitle = 'Admin • Inscrições';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_id'], $_POST['new_status'])) {
    $actionId = (int)$_POST['action_id'];
    $newStatus = trim($_POST['new_status']);
    $dataInicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    
    if (in_array($newStatus, ['aprovado', 'reprovado'])) {
        if ($newStatus === 'aprovado' && $dataInicio) {
            $stmt = $conn->prepare("UPDATE inscricoes SET status = ?, data_inicio = ? WHERE id = ?");
            $stmt->bind_param('ssi', $newStatus, $dataInicio, $actionId);
        } else {
             $stmt = $conn->prepare("UPDATE inscricoes SET status = ? WHERE id = ?");
             $stmt->bind_param('si', $newStatus, $actionId);
        }
        $stmt->execute();
        
        echo "<script>window.location.href='inscricoes.php';</script>";
        exit;
    }
}

$sql = "
SELECT 
    inscricoes.id,
    users.nome AS aluno,
    users.email,
    cursos.titulo AS curso,
    inscricoes.status,
    inscricoes.dados_formulario,
    inscricoes.criado_em
FROM inscricoes
JOIN users ON users.id = inscricoes.user_id
JOIN cursos ON cursos.id = inscricoes.curso_id
ORDER BY inscricoes.id DESC
";

$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <div class="d-flex align-items-start align-items-lg-center justify-content-between gap-3 mb-4 flex-column flex-lg-row pt-4">
        <div>
            <h1 class="h3 mb-1">Inscrições</h1>
            <div class="text-muted">Gerenciamento de solicitações de matrícula.</div>
        </div>
        <a class="btn btn-outline-primary" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Voltar ao painel</a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Aluno</th>
                        <th>Curso</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            $status = ios_normalize_inscricao_status($row['status'] ?? ''); 
                            $badgeClass = match($status) {
                                'aprovado' => 'text-bg-success',
                                'reprovado', 'negado' => 'text-bg-danger',
                                'pendente' => 'text-bg-warning text-dark',
                                default => 'text-bg-secondary'
                            };
                            $formData = json_decode($row['dados_formulario'] ?? '{}', true);
                            $hasForm = !empty($formData);
                        ?>
                        <tr>
                            <td class="text-muted ps-4">#<?= (int)$row['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($row['aluno']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['curso']) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($row['criado_em'])) ?></td>
                            <td class="text-end pe-4">
                                <?php if ($hasForm): ?>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick='openReviewModal(<?= json_encode($row) ?>, <?= json_encode($formData) ?>)'
                                            title="Revisar Solicitação">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small me-2">Sem formulário</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Análise de Solicitação #<span id="modalId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <h6 class="text-uppercase text-muted fs-7 fw-bold mb-3">Dados do Aluno</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted fw-normal">Nome:</dt>
                            <dd class="col-sm-8 fw-semibold" id="modalNome"></dd>
                            <dt class="col-sm-4 text-muted fw-normal">Email:</dt>
                            <dd class="col-sm-8" id="modalEmail"></dd>
                            <dt class="col-sm-4 text-muted fw-normal">Curso:</dt>
                            <dd class="col-sm-8 text-primary fw-semibold" id="modalCurso"></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                         <h6 class="text-uppercase text-muted fs-7 fw-bold mb-3">Questionário Socioeconômico</h6>
                         <div id="modalFormData" class="small"></div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h6 class="fw-bold mb-3">Decisão</h6>
                
                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <label for="inputDataInicio" class="form-label small fw-bold text-uppercase text-muted">Data de Início das Aulas</label>
                    <input type="date" id="inputDataInicio" class="form-control" onchange="document.getElementById('hiddenDataInicio').value = this.value">
                    <div class="form-text small">Defina quando o aluno terá acesso ao conteúdo.</div>
                </div>

                <div class="d-flex gap-2">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action_id" id="approveId">
                        <input type="hidden" name="new_status" value="aprovado">
                        <input type="hidden" name="data_inicio" id="hiddenDataInicio">
                        <button type="submit" class="btn btn-success px-4 bg-gradient" onclick="if(!document.getElementById('hiddenDataInicio').value) { alert('Selecione uma data de início!'); return false; }">
                            <i class="bi bi-check-lg me-2"></i>Aprovar Matrícula
                        </button>
                    </form>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action_id" id="rejectId">
                        <input type="hidden" name="new_status" value="reprovado">
                        <button type="submit" class="btn btn-danger px-4 bg-gradient">
                            <i class="bi bi-x-lg me-2"></i>Reprovar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openReviewModal(row, formData) {
        document.getElementById('modalId').textContent = row.id;
        document.getElementById('modalNome').textContent = row.aluno;
        document.getElementById('modalEmail').textContent = row.email;
        document.getElementById('modalCurso').textContent = row.curso;
        
        document.getElementById('approveId').value = row.id;
        document.getElementById('rejectId').value = row.id;

        let html = '';
        const labels = {
            'renda_per_capita': 'Renda Familiar',
            'pessoas_residencia': 'Pessoas na Residência',
            'acesso_internet': 'Internet/PC',
            'escola_publica': 'Tipo de Escola',
            'trabalha': 'Trabalha?',
            'motivo': 'Motivação'
        };

        for (const [key, value] of Object.entries(formData)) {
            if (key === 'data_solicitacao') continue;
            const label = labels[key] || key;
            html += `<div class="mb-2">
                <div class="text-muted fw-normal fs-7">${label}</div>
                <div class="fw-medium">${value}</div>
            </div>`;
        }
        document.getElementById('modalFormData').innerHTML = html;

        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    }
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
