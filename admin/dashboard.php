<?php
require 'protect.php';
require '../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Admin • Painel';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

// Stats
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalCursos = $conn->query("SELECT COUNT(*) as total FROM cursos")->fetch_assoc()['total'];
$totalInscricoes = $conn->query("SELECT COUNT(*) as total FROM inscricoes")->fetch_assoc()['total'];
$pendentes = $conn->query("SELECT COUNT(*) as total FROM inscricoes WHERE status = 'pendente' OR status = 'em_analise'")->fetch_assoc()['total'];
$totalAulas = $conn->query("SELECT COUNT(*) as total FROM aulas")->fetch_assoc()['total'];

// Recent inscriptions
$recentInscricoes = $conn->query("
    SELECT i.*, u.nome as aluno_nome, u.email as aluno_email, c.titulo as curso_titulo
    FROM inscricoes i
    JOIN users u ON u.id = i.user_id
    JOIN cursos c ON c.id = i.curso_id
    ORDER BY i.id DESC
    LIMIT 5
");
?>

<style>
    .admin-hero {
        background: linear-gradient(135deg, var(--ios-purple) 0%, var(--ios-purple-dark) 100%);
        color: white;
        border-radius: 24px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(89, 0, 179, 0.15);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .quick-action {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }
    .quick-action:hover {
        border-color: var(--ios-purple);
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(89, 0, 179, 0.15);
        color: var(--ios-purple);
    }
    .quick-action i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
        color: var(--ios-purple);
    }
    .notification-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
</style>

<div class="container py-4">
    <!-- Hero Section -->
    <div class="admin-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            <div>
                <h1 class="h2 fw-bold mb-2">Bem-vindo ao Painel</h1>
                <p class="mb-0 opacity-75">Gerencie cursos, aulas e alunos de forma simples.</p>
            </div>
            <a href="<?= ios_url('/index.php') ?>" class="btn btn-light fw-semibold shadow-sm">
                <i class="bi bi-arrow-left me-2"></i>Voltar ao Site
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold mb-0"><?= (int)$totalUsers ?></div>
                        <div class="text-muted small">Alunos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-journal-bookmark"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold mb-0"><?= (int)$totalCursos ?></div>
                        <div class="text-muted small">Cursos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-play-circle"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold mb-0"><?= (int)$totalAulas ?></div>
                        <div class="text-muted small">Aulas</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold mb-0"><?= (int)$pendentes ?></div>
                        <div class="text-muted small">Pendentes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions - Big and Clear -->
    <h2 class="h4 fw-bold mb-4">O que você quer fazer?</h2>
    <div class="row g-4 mb-5">
        <div class="col-6 col-md-4 col-lg-2">
            <a href="cursos.php" class="quick-action">
                <i class="bi bi-plus-circle"></i>
                <div class="fw-bold">Criar Curso</div>
                <small class="text-muted d-none d-md-block mt-1">Novo ou gerenciar existentes</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="aulas.php" class="quick-action">
                <i class="bi bi-collection-play"></i>
                <div class="fw-bold">Adicionar Aula</div>
                <small class="text-muted d-none d-md-block mt-1">Vídeos, PDFs e conteúdo</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="inscricoes.php" class="quick-action position-relative">
                <i class="bi bi-person-check"></i>
                <?php if($pendentes > 0): ?>
                    <span class="notification-badge"><?= $pendentes ?></span>
                <?php endif; ?>
                <div class="fw-bold">Aprovar Alunos</div>
                <small class="text-muted d-none d-md-block mt-1">Gerenciar inscrições</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="relatorio_geral.php" class="quick-action">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <div class="fw-bold">Relatórios</div>
                <small class="text-muted d-none d-md-block mt-1">Exportar dados</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="integracoes.php" class="quick-action">
                <i class="bi bi-plug"></i>
                <div class="fw-bold">Integrações</div>
                <small class="text-muted d-none d-md-block mt-1">Logs externos</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="<?= ios_url('/cursos.php') ?>" class="quick-action" target="_blank">
                <i class="bi bi-eye"></i>
                <div class="fw-bold">Ver Site</div>
                <small class="text-muted d-none d-md-block mt-1">Como aluno vê</small>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-list-task me-2 text-primary"></i>Últimas Inscrições</h5>
                        <a href="inscricoes.php" class="btn btn-sm btn-outline-primary rounded-pill">Ver todas</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4">Aluno</th>
                                    <th class="border-0">Curso</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0 pe-4">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentInscricoes && $recentInscricoes->num_rows > 0): ?>
                                    <?php while ($row = $recentInscricoes->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold"><?= htmlspecialchars($row['aluno_nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['aluno_email']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($row['curso_titulo']) ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $st = $row['status'];
                                            $badgeClass = match($st) {
                                                'aprovado', 'matriculado' => 'bg-success',
                                                'negado' => 'bg-danger',
                                                default => 'bg-warning text-dark'
                                            };
                                            $stLabel = match($st) {
                                                'aprovado', 'matriculado' => 'Aprovado',
                                                'negado' => 'Negado',
                                                default => 'Pendente'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $stLabel ?></span>
                                        </td>
                                        <td class="pe-4">
                                            <?php if ($st === 'pendente' || $st === 'em_analise'): ?>
                                                <a href="inscricoes.php?aprovar=<?= $row['id'] ?>" class="btn btn-sm btn-success rounded-pill">
                                                    <i class="bi bi-check-lg"></i> Aprovar
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Nenhuma inscrição ainda.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-lightbulb me-2 text-warning"></i>Dicas Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 mb-3 p-3 bg-light rounded-3">
                        <div class="text-primary"><i class="bi bi-lightbulb fs-4"></i></div>
                        <div>
                            <div class="fw-semibold">Adicionar Aulas</div>
                            <small class="text-muted">Clique em "Adicionar Aula" e escolha o curso. Você pode subir vídeos do YouTube e PDFs.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3 p-3 bg-light rounded-3">
                        <div class="text-success"><i class="bi bi-check-circle fs-4"></i></div>
                        <div>
                            <div class="fw-semibold">Aprovar Inscrições</div>
                            <small class="text-muted">Quando um aluno se inscreve, você precisa aprovar para ele ter acesso ao conteúdo.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-3 p-3 bg-light rounded-3">
                        <div class="text-info"><i class="bi bi-download fs-4"></i></div>
                        <div>
                            <div class="fw-semibold">Exportar Dados</div>
                            <small class="text-muted">Em Relatórios, você pode baixar uma planilha Excel com todos os dados.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
