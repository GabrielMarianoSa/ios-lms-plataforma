<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

$user_id = (int)$_SESSION['user_id'];

$userStmt = $conn->prepare('SELECT nome, email, tipo FROM users WHERE id = ?');
$userStmt->bind_param('i', $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$sql = "
SELECT
  c.id,
  c.titulo,
  c.descricao,
  c.carga_horaria,
  i.status,
  COUNT(DISTINCT a.id) AS total_aulas,
  COUNT(DISTINCT p.aula_id) AS aulas_concluidas
FROM inscricoes i
JOIN cursos c ON c.id = i.curso_id
LEFT JOIN aulas a ON a.curso_id = c.id
LEFT JOIN progresso p
  ON p.aula_id = a.id
  AND p.user_id = i.user_id
  AND p.concluida = 1
WHERE i.user_id = ?
GROUP BY c.id, c.titulo, c.descricao, c.carga_horaria, i.status
ORDER BY c.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

$cursos = [];
while ($row = $res->fetch_assoc()) {
    $total = (int)$row['total_aulas'];
    $concl = (int)$row['aulas_concluidas'];
    $pct = $total > 0 ? (int)round(($concl / $total) * 100) : 0;

    $row['percentual'] = $pct;
    $row['concluido'] = ($total > 0 && $pct >= 100);
    // Determine approved state. 
    // Status normalization should function similar to ios_normalize_inscricao_status if available, 
    // but here we check raw db val. Common values: 'pendente', 'em_analise', 'aprovado'.
    $st = $row['status'] ?? 'pendente'; 
    $row['is_aprovado'] = ($st === 'aprovado' || $st === 'matriculado');
    
    $cursos[] = $row;
}

$inscritos = count($cursos);
$concluidos = count(array_filter($cursos, fn($c) => !empty($c['concluido'])));
$emAndamento = count(array_filter($cursos, fn($c) => $c['is_aprovado'] && empty($c['concluido'])));
$pendentes = count(array_filter($cursos, fn($c) => !$c['is_aprovado']));


$pageTitle = 'Área do Aluno';
$activeNav = 'aluno';
require __DIR__ . '/../partials/header.php';
?>


<style>
    /* Animation for the "Click here" text - appears, then hides, reappears on hover */
    .avatar-container .avatar-info-text {
        position: absolute;
        top: 100%;
        left: 0;
        white-space: nowrap;
        padding: 8px 12px;
        background: var(--ios-purple);
        color: white;
        border-radius: 8px;
        font-size: 0.75rem;
        box-shadow: 0 4px 15px rgba(89, 0, 179, 0.3);
        opacity: 0;
        transform: translateY(5px);
        pointer-events: none;
        z-index: 10;
        animation: avatarTextShow 5s ease-in-out forwards;
    }
    .avatar-container .avatar-info-text::before {
        content: '';
        position: absolute;
        top: -6px;
        left: 20px;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-bottom: 6px solid var(--ios-purple);
    }
    .avatar-container .avatar-info-text a {
        color: white !important;
        text-decoration: none;
    }

    /* On hover, always show the tooltip */
    .avatar-container:hover .avatar-info-text {
        animation: none !important;
        opacity: 1 !important;
        transform: translateY(10px) !important;
        pointer-events: auto;
    }

    @keyframes avatarTextShow {
        0% { opacity: 0; transform: translateY(5px); }
        10% { opacity: 1; transform: translateY(10px); } /* Appear */
        70% { opacity: 1; transform: translateY(10px); } /* Stay visible */
        100% { opacity: 0; transform: translateY(5px); } /* Hide */
    }

    /* Dashboard cards enhanced styling */
    .dashboard-stat-card {
        background: white;
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .dashboard-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(89, 0, 179, 0.15);
    }
    .course-card-student {
        background: white;
        border-radius: 20px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .course-card-student:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(89, 0, 179, 0.2);
    }
    .welcome-section {
        background: linear-gradient(135deg, var(--ios-purple) 0%, var(--ios-purple-dark) 100%);
        color: white;
        border-radius: 24px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
</style>

<div class="container py-4">
  <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 mb-5">
    <div class="d-flex align-items-center gap-4">
        <div class="position-relative avatar-container">
          <?php $hasAvatar = !empty($_SESSION['avatar']) && strpos($_SESSION['avatar'], 'avatar.png') === false; ?>
          <img src="<?= htmlspecialchars($_SESSION['avatar'] ?? ios_url('/assets/images/avatar.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="student-avatar shadow-lg" style="width:85px;height:85px;object-fit:cover;">
          <a href="<?= ios_url('/aluno/avatar.php') ?>" class="position-absolute bottom-0 end-0 bg-white rounded-circle shadow-sm p-1 text-primary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; text-decoration: none;">
            <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
          </a>

          <?php if (!$hasAvatar): ?>
            <div class="mt-2 small text-muted avatar-info-text"> 
              <a href="<?= ios_url('/aluno/avatar.php') ?>" class="text-decoration-none">Clique aqui para adicionar uma foto de perfil</a>
            </div>
          <?php endif; ?>
        </div>
        <div>
          <h1 class="h3 fw-bold mb-1">Olá, <?= htmlspecialchars(explode(' ', $user['nome'])[0] ?? 'Aluno', ENT_QUOTES, 'UTF-8') ?>!</h1>
          <p class="text-muted mb-0">Continue sua jornada de aprendizado.</p>
        </div>
    </div>

    <div class="d-flex gap-3">
      <a class="btn btn-light shadow-sm fw-semibold" href="<?= htmlspecialchars(ios_url('/cursos.php'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-compass me-2 text-primary"></i>Explorar cursos
      </a>
      <?php if (!empty($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin'): ?>
        <a class="btn btn-primary shadow-sm" href="<?= htmlspecialchars(ios_url('/admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="bi bi-speedometer2 me-2"></i>Admin
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-12 col-md-4">
      <div class="card card-soft p-2 h-100 position-relative overflow-hidden border-0">
         <div class="position-absolute top-0 end-0 mt-n3 me-n3 opacity-10">
             <i class="bi bi-journal-bookmark-fill display-1 text-primary"></i>
         </div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                 <div class="bg-primary-subtle p-3 rounded-pill text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-journal-bookmark fs-4"></i>
                 </div>
                 <div>
                    <div class="h2 fw-bold mb-0"><?= (int)$inscritos ?></div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wide">Cursos Inscritos</div>
                 </div>
            </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card card-soft p-2 h-100 position-relative overflow-hidden border-0">
        <div class="position-absolute top-0 end-0 mt-n3 me-n3 opacity-10">
             <i class="bi bi-hourglass-split display-1 text-warning"></i>
         </div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                 <div class="bg-warning-subtle p-3 rounded-pill text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-play-fill fs-4"></i>
                 </div>
                 <div>
                    <div class="h2 fw-bold mb-0"><?= (int)$emAndamento ?></div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wide">Em Andamento</div>
                 </div>
            </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card card-soft p-2 h-100 position-relative overflow-hidden border-0">
        <div class="position-absolute top-0 end-0 mt-n3 me-n3 opacity-10">
             <i class="bi bi-trophy-fill display-1 text-success"></i>
         </div>
         <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                 <div class="bg-success-subtle p-3 rounded-pill text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-trophy fs-4"></i>
                 </div>
                 <div>
                    <div class="h2 fw-bold mb-0"><?= (int)$concluidos ?></div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wide">Concluídos</div>
                 </div>
            </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($cursos)): ?>
    <div class="card card-soft p-5 text-center">
        <div class="card-body">
            <div class="mb-3 text-muted opacity-50">
                <i class="bi bi-search display-3"></i>
            </div>
            <h3 class="h4 fw-bold">Você ainda não iniciou nenhum curso</h3>
            <p class="text-muted mb-4">Explore nosso catálogo e comece a transformar seu futuro hoje mesmo.</p>
            <a href="<?= ios_url('/cursos.php') ?>" class="btn btn-primary btn-lg shadow-sm">
                Conhecer Cursos
            </a>
        </div>
    </div>
  <?php else: ?>
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h2 class="h4 fw-bold mb-0">Meus Cursos</h2>
    </div>

    <div class="row g-4">
      <?php foreach ($cursos as $c): ?>
        <div class="col-12 col-xl-6">
          <div class="card card-soft h-100 border-0 transition-hover">
            <div class="card-body p-4">
              <div class="d-flex flex-column flex-sm-row gap-4">
                <!-- Icon/Thumb -->
                <div class="d-none d-sm-flex flex-shrink-0 align-items-center justify-content-center bg-light rounded-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-mortarboard fs-1 text-primary opacity-50"></i>
                </div>
                
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                      <h3 class="h5 fw-bold mb-0 text-dark"><?= htmlspecialchars($c['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                      <?php if ($c['is_aprovado']): ?>
                          <?php if (!empty($c['concluido'])): ?>
                              <span class="badge bg-success-subtle text-success rounded-pill px-3"><i class="bi bi-check-lg me-1"></i>Concluído</span>
                          <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Em andamento</span>
                          <?php endif; ?>
                      <?php else: ?>
                          <span class="badge bg-warning text-dark rounded-pill px-3 shadow-sm border border-warning">
                             <i class="bi bi-hourglass-split me-1"></i>Inscrito (Aguardando Aprovação)
                          </span>
                      <?php endif; ?>
                  </div>
                  
                  <p class="text-muted small mb-3 text-truncate-2" style="max-height: 3em; overflow: hidden;"><?= htmlspecialchars($c['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                  
                  <?php if($c['is_aprovado']): ?>
                  <div class="d-flex align-items-center gap-3 small text-muted mb-4">
                    <span><i class="bi bi-clock me-1"></i><?= (int)$c['carga_horaria'] ?>h</span>
                    <span><i class="bi bi-file-play me-1"></i><?= (int)$c['aulas_concluidas'] ?>/<?= (int)$c['total_aulas'] ?> aulas</span>
                  </div>

                  <div class="d-flex align-items-center gap-3">
                      <div class="flex-grow-1">
                          <div class="d-flex justify-content-between small fw-bold mb-1">
                              <span class="text-primary"><?= (int)$c['percentual'] ?>%</span>
                              <span class="text-muted">Concluído</span>
                          </div>
                          <div class="progress" style="height: 6px; border-radius: 4px;">
                            <div class="progress-bar bg-gradient-primary" role="progressbar" style="width: <?= (int)$c['percentual'] ?>%; background: var(--ios-grad-primary);" aria-valuenow="<?= (int)$c['percentual'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>
                      </div>
                      <a class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-semibold" href="<?= htmlspecialchars(ios_url('/curso.php?id=' . (int)$c['id']), ENT_QUOTES, 'UTF-8') ?>">
                          Continuar
                      </a>
                  </div>
                  <?php else: ?>
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Sua inscrição está em análise. Você receberá um aviso assim que for aprovada.
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
