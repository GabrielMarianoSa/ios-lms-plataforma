<?php
session_start();
require __DIR__ . '/config/db.php';

$pageTitle = 'IOS • Instituto da Oportunidade Social';
$activeNav = 'home';
require __DIR__ . '/partials/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6 fade-up-enter">
                <div class="ios-badge mb-4">
                    Transformando Futuros
                </div>
                <h1 class="hero-title">
                    Oportunidade real para quem quer vencer.
                </h1>
                <p class="hero-subtitle">
                    Cursos gratuitos de tecnologia e gestão para jovens e pessoas com deficiência. Prepare-se para o mercado de trabalho com quem entende do assunto.
                </p>
                
                <div class="d-flex flex-wrap gap-3">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="<?= ios_url('/auth/register.php') ?>" class="btn-primary-ios">
                            Inscreva-se Agora
                        </a>
                        <a href="<?= ios_url('/auth/login.php') ?>" class="btn-outline-ios">
                            Já sou Aluno
                        </a>
                    <?php else: ?>
                        <a href="<?= ios_url('/aluno/dashboard.php') ?>" class="btn-primary-ios">
                            Minha Área
                        </a>
                        <a href="<?= ios_url('/cursos.php') ?>" class="btn-outline-ios">
                            Ver Cursos
                        </a>
                    <?php endif; ?>
                </div>

                <div class="mt-5 d-flex flex-wrap gap-3">
                    <span class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 rounded-pill px-4 py-2 fw-semibold text-white border border-light border-opacity-25 backdrop-blur">
                        <i class="bi bi-gift-fill text-warning"></i> 100% Gratuito
                    </span>
                    <span class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 rounded-pill px-4 py-2 fw-semibold text-white border border-light border-opacity-25 backdrop-blur">
                         <i class="bi bi-display text-info"></i> Online e Presencial
                    </span>
                </div>
            </div>
            
            <div class="col-lg-6 position-relative fade-up-enter delay-200">
                <img src="assets/images/alunoestudando.jpg" alt="Alunos IOS" class="img-fluid floating-image w-100">
                
                <!-- Floating Card -->
                <div class="position-absolute bottom-0 start-0 translate-middle-y bg-white p-4 rounded-4 shadow-lg d-none d-md-block ms-5 mb-5" style="max-width: 250px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-3 rounded-circle text-primary">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">40.000+</h5>
                            <small class="text-muted">Jovens formados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="container py-5 mt-n5 position-relative" style="z-index: 10;">
    <div class="bg-white rounded-5 shadow-lg p-5">
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3 border-end-md">
                <div class="stat-item text-center">
                    <div class="stat-number fw-bold display-4 text-primary" data-val="24" data-suffix="">0</div>
                    <div class="stat-label text-muted fw-semibold">Anos de História</div>
                </div>
            </div>
            <div class="col-6 col-md-3 border-end-md">
                <div class="stat-item text-center">
                    <div class="stat-number fw-bold display-4 text-primary" data-val="50000" data-prefix="+ " data-suffix=" mil">0</div>
                    <div class="stat-label text-muted fw-semibold">Alunos Formados</div>
                </div>
            </div>
            <div class="col-6 col-md-3 border-end-md">
                <div class="stat-item text-center">
                    <div class="stat-number fw-bold display-4 text-primary" data-val="1000" data-suffix="+">0</div>
                    <div class="stat-label text-muted fw-semibold">Mais de mil alunos por ano</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item text-center">
                    <div class="stat-number fw-bold display-4 text-primary" data-val="83" data-suffix="%">0%</div>
                    <div class="stat-label text-muted fw-semibold">Empregabilidade</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const counters = document.querySelectorAll('.stat-number');
        const speed = 100;

        const animate = (counter) => {
            const value = +counter.getAttribute('data-val');
            const suffix = counter.getAttribute('data-suffix') || '';
            const prefix = counter.getAttribute('data-prefix') || '';
            
            let startTime = null;

            const updateCount = (currentTime) => {
                if (!startTime) startTime = currentTime;
                const progress = currentTime - startTime;
                const duration = 2000; // 2 seconds
                
                if (progress < duration) {
                    const currentVal = Math.ceil(value * (progress / duration));
                    // Formatting based on magnitude for smooth look
                    let displayVal = currentVal;
                     if (prefix.trim() === '+' && suffix.trim() === 'mil') {
                       // Special handling for 50 mil to show proper count up to 50
                       displayVal = Math.ceil(50 * (progress/duration)); 
                    }
                    
                    counter.innerText = prefix + displayVal + suffix;
                    requestAnimationFrame(updateCount);
                } else {
                    counter.innerText = prefix + (value === 50000 ? '50' : value) + suffix;
                }
            };
            
            requestAnimationFrame(updateCount);
        }
        
        let observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if(entry.isIntersecting){
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => {
            observer.observe(counter);
        });
    });
</script>
        </div>
    </div>
</section>

<!-- About / Differentials -->
<section class="py-5">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 fade-up-enter">
                <div class="position-relative">
                    <div class="bg-primary position-absolute top-0 start-0 w-100 h-100 rounded-5" style="transform: rotate(-3deg); z-index: 0; opacity: 0.1;"></div>
                    <img src="assets/images/cadeirante.jpg" alt="Inclusão IOS" class="img-fluid rounded-5 shadow-lg position-relative z-1 w-100">
                </div>
            </div>
            <div class="col-lg-6 fade-up-enter delay-100">
                <span class="text-primary fw-bold text-uppercase tracking-wider small">Sobre Nós</span>
                <h2 class="display-5 fw-bold mb-4 mt-2 text-dark">Educação que gera <span class="text-purple">Emprego</span>.</h2>
                <p class="text-muted fs-5 mb-5 lh-lg">
                    O IOS não oferece apenas cursos. Nós construímos pontes entre talentos diversos e empresas inovadoras. Nossa metodologia une conhecimento técnico com habilidades comportamentais essenciais.
                </p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="hover-card">
                            <div class="feature-icon-wrapper">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <h4 class="h5 fw-bold">Tecnologia</h4>
                            <p class="text-muted small mb-0">Cursos de ERP, Programação Web, Suporte em TI e Cybersegurança.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="hover-card">
                            <div class="feature-icon-wrapper">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <h4 class="h5 fw-bold">Gestão</h4>
                            <p class="text-muted small mb-0">Aprendizado administrativo e comportamental para o mundo corporativo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section class="container mb-5">
    <div class="partners-section">
        <div class="text-center mb-5">
            <h2 class="h3 fw-bold">Quem apoia nossa causa</h2>
            <p class="text-muted">Empresas que acreditam e investem no futuro</p>
        </div>
        
        <div class="row justify-content-center align-items-center gy-4 px-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-container">
                    <img src="assets/images/totvs.png" alt="TOTVS">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-container">
                    <img src="assets/images/dell.png" alt="Dell">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-container">
                    <img src="assets/images/microsoft.png" alt="Microsoft">
                </div>
            </div>
             <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-container">
                    <img src="assets/images/zendesk.png" alt="Zendesk">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-container">
                    <img src="assets/images/ibm.png" alt="IBM">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="bg-primary rounded-5 p-5 text-center text-white shadow-lg overflow-hidden position-relative">
            <!-- decorative circles removed as requested -->

            <div class="position-relative z-1 py-4">
                <h2 class="display-6 fw-bold mb-3 text-white">Seu futuro começa agora!</h2>
                <p class="lead mb-4 opacity-90">Junte-se a milhares de alunos que transformaram suas carreiras com o IOS.</p>
                <a href="<?= ios_url('/auth/register.php') ?>" class="btn-primary-ios">
                   Quero me Inscrever
                </a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
