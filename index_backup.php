<?php
require __DIR__ . '/partials/bootstrap.php';
require 'config/db.php';

$pageTitle = 'Instituto da Oportunidade Social • Plataforma de Cursos';
$activeNav = 'home';
require __DIR__ . '/partials/header.php';
?>

<div class="container">
    <div class="hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-7">
                <h1 class="display-6 fw-bold mb-2">Formação profissional gratuita com foco em empregabilidade</h1>
                <p class="lead mb-4 opacity-90">
                    Bem-vindo à plataforma de cursos e LMS. Aqui você se inscreve, acompanha aulas e mede seu progresso.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-lg" href="<?= htmlspecialchars(ios_url('/cursos.php'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-journal-text me-1"></i>Ver cursos
                    </a>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a class="btn btn-outline-light btn-lg" href="<?= htmlspecialchars(ios_url('/auth/login.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                        </a>
                    <?php else: ?>
                        <a class="btn btn-outline-light btn-lg" href="<?= htmlspecialchars(ios_url('/aluno/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-speedometer2 me-1"></i>Minha área
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card card-soft bg-white text-body">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-primary-subtle text-primary"><i class="bi bi-stars"></i></div>
                            <div>
                                <div class="fw-semibold">Como funciona</div>
                                <div class="small text-muted">Inscreva-se, acesse aulas e acompanhe seu progresso.</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex gap-3">
                                    <div class="icon-circle bg-success-subtle text-success"><i class="bi bi-1-circle"></i></div>
                                    <div>
                                        <div class="fw-semibold">Cadastro/Login</div>
                                        <div class="small text-muted">Acesse sua conta de aluno para se inscrever.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-3">
                                    <div class="icon-circle bg-warning-subtle text-warning"><i class="bi bi-2-circle"></i></div>
                                    <div>
                                        <div class="fw-semibold">Inscrição</div>
                                        <div class="small text-muted">Escolha um curso e confirme sua inscrição.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-3">
                                    <div class="icon-circle bg-info-subtle text-info"><i class="bi bi-3-circle"></i></div>
                                    <div>
                                        <div class="fw-semibold">Aulas e progresso</div>
                                        <div class="small text-muted">Marque aulas concluídas e visualize sua evolução.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="icon-circle bg-primary-subtle text-primary"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="fw-semibold">Institucional</div>
                            <div class="text-muted small">Conteúdo introdutório sobre a instituição e sua missão (personalizável).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="icon-circle bg-success-subtle text-success"><i class="bi bi-mortarboard"></i></div>
                        <div>
                            <div class="fw-semibold">Trilhas e cursos</div>
                            <div class="text-muted small">Cursos com acompanhamento de aulas e progresso por aluno.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="icon-circle bg-dark-subtle text-dark"><i class="bi bi-plug"></i></div>
                        <div>
                            <div class="fw-semibold">Integrações</div>
                            <div class="text-muted small">Simulação de integrações com RD Station e Protheus via API.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 p-4 bg-light rounded-4">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <h2 class="h5 mb-1">Quer deixar o institucional “bem bonitao”?</h2>
                <div class="text-muted">Me passe as informações (missão, público, números, parceiros, cursos, etc.) que eu ajusto essa home com um layout mais completo.</div>
            </div>
            <div class="col-12 col-lg-4 text-lg-end">
                <a class="btn btn-primary" href="<?= htmlspecialchars(ios_url('/cursos.php'), ENT_QUOTES, 'UTF-8') ?>">
                    Ver cursos agora <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
