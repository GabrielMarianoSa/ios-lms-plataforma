<?php
require 'protect.php';
require '../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

// Export to CSV/Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_cursos_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr><th>Curso</th><th>Inscritos</th><th>Vagas</th><th>Modalidade</th><th>Local</th><th>Turno</th><th>Data Início</th><th>Data Fim</th></tr>';
    
    $query = "
        SELECT 
            c.titulo, 
            COUNT(i.id) AS total_inscritos, 
            COALESCE(ci.modalidade,'-') AS modalidade, 
            COALESCE(ci.local,'-') AS local, 
            COALESCE(ci.data_inicio,'') AS data_inicio, 
            COALESCE(ci.data_fim,'') AS data_fim, 
            COALESCE(ci.turno,'-') AS turno, 
            COALESCE(ci.vagas,'-') AS vagas 
        FROM cursos c 
        LEFT JOIN inscricoes i ON i.curso_id = c.id 
        LEFT JOIN curso_infos ci ON ci.curso_id = c.id 
        GROUP BY c.id, c.titulo, ci.modalidade, ci.local, ci.data_inicio, ci.data_fim, ci.turno, ci.vagas 
        ORDER BY total_inscritos DESC
    ";
    $result = $conn->query($query);
    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['titulo']) . '</td>';
        echo '<td>' . htmlspecialchars($row['total_inscritos']) . '</td>';
        echo '<td>' . htmlspecialchars($row['vagas']) . '</td>';
        echo '<td>' . htmlspecialchars($row['modalidade']) . '</td>';
        echo '<td>' . htmlspecialchars($row['local']) . '</td>';
        echo '<td>' . htmlspecialchars($row['turno']) . '</td>';
        echo '<td>' . ($row['data_inicio'] ? date('d/m/Y', strtotime($row['data_inicio'])) : '-') . '</td>';
        echo '<td>' . ($row['data_fim'] ? date('d/m/Y', strtotime($row['data_fim'])) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

// Export students list
if (isset($_GET['export']) && $_GET['export'] === 'alunos') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="alunos_inscritos_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr><th>Aluno</th><th>Email</th><th>Curso</th><th>Status</th><th>Data Inscrição</th></tr>';
    
    $query = "
        SELECT u.nome, u.email, c.titulo as curso, i.status, i.criado_em
        FROM inscricoes i
        JOIN users u ON u.id = i.user_id
        JOIN cursos c ON c.id = i.curso_id
        ORDER BY i.criado_em DESC
    ";
    $result = $conn->query($query);
    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['curso']) . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '<td>' . ($row['criado_em'] ? date('d/m/Y H:i', strtotime($row['criado_em'])) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

$pageTitle = 'Relatório Geral • Admin';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

$query = "
    SELECT 
        c.id, 
        c.titulo, 
        COUNT(i.id) AS total_inscritos, 
        COALESCE(ci.modalidade,'-') AS modalidade, 
        COALESCE(ci.local,'-') AS local, 
        COALESCE(ci.data_inicio,'') AS data_inicio, 
        COALESCE(ci.data_fim,'') AS data_fim, 
        COALESCE(ci.turno,'-') AS turno, 
        COALESCE(ci.vagas,'-') AS vagas 
    FROM cursos c 
    LEFT JOIN inscricoes i ON i.curso_id = c.id 
    LEFT JOIN curso_infos ci ON ci.curso_id = c.id 
    GROUP BY c.id, c.titulo, ci.modalidade, ci.local, ci.data_inicio, ci.data_fim, ci.turno, ci.vagas 
    ORDER BY total_inscritos DESC
";

$result = $conn->query($query);
?>

<style>
    .export-btn {
        background: linear-gradient(135deg, #217346 0%, #185c37 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(33, 115, 70, 0.3);
        color: white;
    }
    .export-btn i {
        margin-right: 0.5rem;
    }
</style>

<div class="container py-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">📊 Relatório Geral de Cursos</h1>
            <div class="text-muted">Visão consolidada de cursos, inscrições e detalhes logísticos.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="export-btn" href="?export=excel">
                <i class="bi bi-file-earmark-excel"></i>Exportar Cursos (Excel)
            </a>
            <a class="export-btn" href="?export=alunos">
                <i class="bi bi-people"></i>Exportar Alunos (Excel)
            </a>
            <a class="btn btn-outline-secondary" href="dashboard.php">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Curso</th>
                            <th>Inscritos</th>
                            <th>Vagas</th>
                            <th>Modalidade</th>
                            <th>Local</th>
                            <th>Turno</th>
                            <th class="pe-4">Datas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-primary">
                                        <?= htmlspecialchars($row['titulo'] ?? '') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill px-3">
                                            <?= htmlspecialchars($row['total_inscritos']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['vagas']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($row['modalidade']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['local']) ?></td>
                                    <td><?= htmlspecialchars($row['turno']) ?></td>
                                    <td class="small text-muted pe-4">
                                        <?php if ($row['data_inicio']): ?>
                                            <div><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($row['data_inicio'])) ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['data_fim']): ?>
                                            <div><i class="bi bi-calendar-check me-1"></i><?= date('d/m/Y', strtotime($row['data_fim'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    Nenhum dado encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
