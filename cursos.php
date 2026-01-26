<?php
session_start();
require 'config/db.php';
require __DIR__ . '/partials/bootstrap.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$cursoInfosEnabled = ios_table_exists($conn, 'curso_infos');

// Fetch ALL courses from database dynamically
$sql = "SELECT c.*, ";

if ($cursoInfosEnabled) {
    $sql .= "ci.modalidade, ci.local, ci.data_inicio, ci.data_fim, ci.turno, ci.vagas, ";
} else {
    $sql .= "NULL as modalidade, NULL as local, NULL as data_inicio, NULL as data_fim, NULL as turno, NULL as vagas, ";
}

$sql .= "(SELECT COUNT(*) FROM aulas a WHERE a.curso_id = c.id) AS total_aulas ";

if ($isLoggedIn) {
    $sql .= ", EXISTS(SELECT 1 FROM inscricoes i WHERE i.curso_id = c.id AND i.user_id = {$userId}) AS inscrito ";
    $sql .= ", EXISTS(SELECT 1 FROM inscricoes i WHERE i.curso_id = c.id AND i.user_id = {$userId} AND (i.status = 'pendente' OR i.status = 'em_analise')) AS pendente ";
    $sql .= ", (SELECT i.status FROM inscricoes i WHERE i.curso_id = c.id AND i.user_id = {$userId} ORDER BY i.id DESC LIMIT 1) AS meu_status ";
}

$sql .= "FROM cursos c ";

if ($cursoInfosEnabled) {
    $sql .= "LEFT JOIN curso_infos ci ON ci.curso_id = c.id ";
}

$sql .= "ORDER BY c.id DESC";
$cursosResult = $conn->query($sql);

// Convert to array for easier manipulation
$cursos = [];
while ($row = $cursosResult->fetch_assoc()) {
    $cursos[] = $row;
}

$pageTitle = 'Cursos • IOS';
$activeNav = 'cursos';
require __DIR__ . '/partials/header.php';

// Icon and color mapping based on course title keywords
function getCourseStyle($titulo) {
    $titulo = mb_strtolower($titulo);
    
    if (str_contains($titulo, 'web') || str_contains($titulo, 'programação') || str_contains($titulo, 'frontend')) {
        return ['icon' => 'bi-code-slash', 'color' => 'bg-primary'];
    }
    if (str_contains($titulo, 'cyber') || str_contains($titulo, 'segurança') || str_contains($titulo, 'security')) {
        return ['icon' => 'bi-shield-lock', 'color' => 'bg-danger'];
    }
    if (str_contains($titulo, 'erp') || str_contains($titulo, 'protheus') || str_contains($titulo, 'sap')) {
        return ['icon' => 'bi-hdd-network', 'color' => 'bg-info'];
    }
    if (str_contains($titulo, 'backend') || str_contains($titulo, 'api') || str_contains($titulo, 'servidor')) {
        return ['icon' => 'bi-server', 'color' => 'bg-dark'];
    }
    if (str_contains($titulo, 'zendesk') || str_contains($titulo, 'atendimento') || str_contains($titulo, 'suporte')) {
        return ['icon' => 'bi-headset', 'color' => 'bg-success'];
    }
    if (str_contains($titulo, 'office') || str_contains($titulo, 'excel') || str_contains($titulo, 'word')) {
        return ['icon' => 'bi-grid-3x3-gap', 'color' => 'bg-warning'];
    }
    if (str_contains($titulo, 'admin') || str_contains($titulo, 'rotinas') || str_contains($titulo, 'gestão')) {
        return ['icon' => 'bi-file-earmark-text', 'color' => 'bg-secondary'];
    }
    if (str_contains($titulo, 'design') || str_contains($titulo, 'figma') || str_contains($titulo, 'ui')) {
        return ['icon' => 'bi-palette', 'color' => 'bg-pink'];
    }
    if (str_contains($titulo, 'data') || str_contains($titulo, 'dados') || str_contains($titulo, 'analytics')) {
        return ['icon' => 'bi-graph-up', 'color' => 'bg-teal'];
    }
    if (str_contains($titulo, 'mobile') || str_contains($titulo, 'app') || str_contains($titulo, 'android') || str_contains($titulo, 'ios')) {
        return ['icon' => 'bi-phone', 'color' => 'bg-indigo'];
    }
    
    // Default
    return ['icon' => 'bi-mortarboard', 'color' => 'bg-primary'];
}

function formatDateBR($date) {
    if (!$date) return null;
    return date('d/m/Y', strtotime($date));
}
?>

<style>
.course-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    overflow: hidden;
}
.course-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(89, 0, 179, 0.15);
}
.course-thumb {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: white;
    position: relative;
    overflow: hidden;
}
.course-thumb.has-image {
    background-size: cover;
    background-position: center;
}
.course-thumb.has-image i {
    display: none;
}
.course-thumb::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.2) 100%);
}
.course-info-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.course-info-pills .badge {
    font-weight: 500;
    font-size: 0.7rem;
}
/* Banner */
.courses-banner {
    background: url('assets/images/banner-cursos.jpg') center center / cover no-repeat;
    background-color: var(--ios-purple);
    color: white;
    padding: 80px 0;
    margin-bottom: 3rem;
    border-radius: 0 0 50px 50px;
    position: relative;
    overflow: hidden;
}
.courses-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(to right, rgba(89, 0, 179, 0.92) 0%, rgba(89, 0, 179, 0.75) 35%, rgba(89, 0, 179, 0.3) 60%, transparent 80%);
    z-index: 1;
}
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}
</style>

<div class="courses-banner shadow-lg">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-7 text-start">
                <h1 class="display-4 fw-bold mb-3 text-white">Nossos Cursos</h1>
                <p class="h4 fw-light text-white mb-0" style="line-height: 1.5;">
                    Formações que vão <span class="fw-bold text-warning">transformar sua carreira.</span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    
    <?php if (empty($cursos)): ?>
        <div class="empty-state">
            <div class="mb-4">
                <i class="bi bi-journal-x display-1 text-muted opacity-50"></i>
            </div>
            <h3 class="fw-bold mb-3">Nenhum curso disponível no momento</h3>
            <p class="text-muted mb-4">Estamos preparando novos cursos incríveis para você. Volte em breve!</p>
            <a href="<?= ios_url('/') ?>" class="btn btn-primary rounded-pill px-4">Voltar ao Início</a>
        </div>
    <?php else: ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Cursos Disponíveis</h2>
                <p class="text-muted mb-0"><?= count($cursos) ?> curso<?= count($cursos) > 1 ? 's' : '' ?> encontrado<?= count($cursos) > 1 ? 's' : '' ?></p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($cursos as $curso): 
                $style = getCourseStyle($curso['titulo']);
                $isOpen = !isset($curso['inscricoes_abertas']) || $curso['inscricoes_abertas'] != 0;
                $hasThumbnail = !empty($curso['thumbnail']);
                $safeId = 'curso-' . (int)$curso['id'];
                
                // User status
                $jaInscrito = $isLoggedIn && !empty($curso['inscrito']);
                $pendente = $isLoggedIn && !empty($curso['pendente']);
                $meuStatus = $isLoggedIn ? ($curso['meu_status'] ?? null) : null;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 course-card rounded-4 shadow-sm">
                    <!-- Thumbnail / Placeholder -->
                    <div class="course-thumb <?= $style['color'] ?> <?= $hasThumbnail ? 'has-image' : '' ?>" 
                         <?php if ($hasThumbnail): ?>style="background-image: url('<?= htmlspecialchars(ios_url('/' . ltrim($curso['thumbnail'], '/'))) ?>')"<?php endif; ?>>
                        <i class="bi <?= $style['icon'] ?>"></i>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold mb-2"><?= htmlspecialchars($curso['titulo']) ?></h5>
                        
                        <!-- Info Pills -->
                        <div class="course-info-pills">
                            <?php if (!empty($curso['carga_horaria'])): ?>
                                <span class="badge bg-light text-dark"><i class="bi bi-clock me-1"></i><?= (int)$curso['carga_horaria'] ?>h</span>
                            <?php endif; ?>
                            <?php if (!empty($curso['modalidade'])): ?>
                                <span class="badge bg-light text-dark"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($curso['modalidade']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($curso['turno'])): ?>
                                <span class="badge bg-light text-dark"><i class="bi bi-sun me-1"></i><?= htmlspecialchars($curso['turno']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($curso['total_aulas'])): ?>
                                <span class="badge bg-light text-dark"><i class="bi bi-play-circle me-1"></i><?= (int)$curso['total_aulas'] ?> aulas</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Description -->
                        <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars($curso['descricao'] ?? 'Aprenda habilidades essenciais para o mercado de trabalho.') ?>
                        </p>
                        
                        <!-- Dates if available -->
                        <?php if (!empty($curso['data_inicio'])): ?>
                            <div class="small text-muted mb-3">
                                <i class="bi bi-calendar-event me-1"></i>
                                Início: <strong><?= formatDateBR($curso['data_inicio']) ?></strong>
                                <?php if (!empty($curso['data_fim'])): ?>
                                    até <?= formatDateBR($curso['data_fim']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Actions -->
                        <div class="d-flex gap-2 mt-auto">
                            <?php if ($isLoggedIn): ?>
                                <?php if ($jaInscrito && ios_is_inscricao_aprovada($meuStatus ?? '')): ?>
                                    <a href="<?= ios_url('/curso.php?id=' . (int)$curso['id']) ?>" class="btn btn-primary w-100 rounded-pill">
                                        <i class="bi bi-play-fill me-1"></i>Acessar Curso
                                    </a>
                                <?php elseif ($pendente): ?>
                                    <button class="btn btn-warning w-100 rounded-pill" disabled>
                                        <i class="bi bi-hourglass-split me-1"></i>Em Análise
                                    </button>
                                <?php elseif ($isOpen): ?>
                                    <a href="<?= ios_url('/inscrever.php?curso_id=' . (int)$curso['id']) ?>" class="btn btn-primary w-100 rounded-pill">
                                        <i class="bi bi-check2-square me-1"></i>Inscrever-se
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 rounded-pill" disabled>
                                        <i class="bi bi-lock me-1"></i>Inscrições Fechadas
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($isOpen): ?>
                                    <a href="<?= ios_url('/auth/login.php?redirect=' . urlencode('/inscrever.php?curso_id=' . (int)$curso['id'])) ?>" class="btn btn-primary w-100 rounded-pill">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Entrar para Inscrever
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 rounded-pill" disabled>
                                        <i class="bi bi-lock me-1"></i>Inscrições Fechadas
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
