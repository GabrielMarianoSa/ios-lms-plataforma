<?php
session_start();
require 'config/db.php';
require __DIR__ . '/partials/bootstrap.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$cursoInfosEnabled = ios_table_exists($conn, 'curso_infos');

$sql = "SELECT c.*, ";

if ($cursoInfosEnabled) {
    $sql .= "ci.modalidade, ci.local, ci.data_inicio, ci.data_fim, ";
} else {
    $sql .= "NULL as modalidade, NULL as local, NULL as data_inicio, NULL as data_fim, ";
}

if ($isLoggedIn) {
     $sql .= "EXISTS(SELECT 1 FROM inscricoes i WHERE i.curso_id = c.id AND i.user_id = {$userId}) AS inscrito, ";
     $sql .= "EXISTS(SELECT 1 FROM inscricoes i WHERE i.curso_id = c.id AND i.user_id = {$userId} AND (i.status = 'pendente' OR i.status = 'em_analise')) AS pendente, ";
     $sql .= "(SELECT COUNT(*) FROM aulas a WHERE a.curso_id = c.id) AS total_aulas, ";
     $sql .= "(
            SELECT COUNT(DISTINCT p.aula_id)
            FROM progresso p
            JOIN aulas a2 ON a2.id = p.aula_id
            WHERE p.user_id = {$userId} AND p.concluida = 1 AND a2.curso_id = c.id
        ) AS aulas_concluidas ";
} else {
    $sql .= "0 as inscrito, 0 as pendente, 0 as total_aulas, 0 as aulas_concluidas ";
}

$sql .= "FROM cursos c ";

if ($cursoInfosEnabled) {
    $sql .= "LEFT JOIN curso_infos ci ON ci.curso_id = c.id ";
}

$sql .= "ORDER BY c.id DESC";
$cursos = $conn->query($sql);

$pageTitle = 'Cursos • IOS';
$activeNav = 'cursos';
require __DIR__ . '/partials/header.php';

// Course Definitions (Metadata not in DB yet, or augmenting DB data)
$categories = [
    'Tecnologia' => [
        [
            'title' => 'Programação Web',
            'thumb_small' => 'assets/images/thumbs/web_small.jpg', // User will generate
            'thumb_large' => 'assets/images/thumbs/web_large.jpg', // User will generate
            'brief' => 'Crie sites e sistemas do zero e entre no mercado de trabalho.',
            'desc' => 'Aprenda a criar sites e sistemas web modernos. Ideal para quem quer iniciar carreira na tecnologia desenvolvendo raciocínio lógico e criatividade. O mercado busca muito esse profissional e você sairá pronto para criar interfaces incríveis e funcionais.',
            'icon' => 'bi-code-slash',
            'color' => 'bg-primary'
        ],
        [
            'title' => 'Cybersegurança',
            'thumb_small' => 'assets/images/thumbs/cyber_small.jpg',
            'thumb_large' => 'assets/images/thumbs/cyber_large.jpg',
            'brief' => 'Proteja o mundo digital contra ameaças e ataques.',
            'desc' => 'Proteja dados e sistemas contra invasões. Um curso feito para quem gosta de desafios e quer atuar em uma das áreas mais valorizadas da atualidade. Você aprenderá a identificar riscos e criar defesas para garantir a segurança de empresas e pessoas.',
            'icon' => 'bi-shield-lock',
            'color' => 'bg-danger'
        ],
        [
            'title' => 'ERP',
            'thumb_small' => 'assets/images/thumbs/erp_small.jpg',
            'thumb_large' => 'assets/images/thumbs/erp_large.jpg',
            'brief' => 'Domine os sistemas que controlam grandes empresas.',
            'desc' => 'Aprenda a operar sistemas integrados de gestão empresarial (como Protheus). Essencial para quem quer trabalhar em grandes corporações, entendendo como o negócio funciona de ponta a ponta: do financeiro ao estoque.',
            'icon' => 'bi-hdd-network',
            'color' => 'bg-info'
        ],
        [
            'title' => 'Backend',
            'thumb_small' => 'assets/images/thumbs/backend_small.jpg',
            'thumb_large' => 'assets/images/thumbs/backend_large.jpg',
            'brief' => 'Descubra a lógica e os bastidores dos aplicativos.',
            'desc' => 'Cuide do que acontece "por trás das cortinas" dos softwares. Você vai aprender como os dados são processados, armazenados e seguros. Perfeito para quem é curioso, gosta de resolver problemas e entender a lógica profunda da programação.',
            'icon' => 'bi-server',
            'color' => 'bg-dark'
        ]
    ],
    'Gestão e Administrativo' => [
        [
            'title' => 'Zendesk',
            'thumb_small' => 'assets/images/thumbs/zendesk_small.jpg',
            'thumb_large' => 'assets/images/thumbs/zendesk_large.jpg',
            'brief' => 'Torne-se um especialista em atendimento ao cliente.',
            'desc' => 'Domine a plataforma de atendimento mais usada no mundo. Este curso é ótimo para quem gosta de se comunicar, ajudar pessoas e busca oportunidades em áreas de Suporte, Customer Success (Sucesso do Cliente) e Relacionamento.',
            'icon' => 'bi-headset',
            'color' => 'bg-success'
        ],
        [
            'title' => 'Pacote Office',
            'thumb_small' => 'assets/images/thumbs/office_small.jpg',
            'thumb_large' => 'assets/images/thumbs/office_large.jpg',
            'brief' => 'Domine as ferramentas essenciais de qualquer escritório.',
            'desc' => 'O pontapé inicial para sua carreira administrativa. Word, Excel e PowerPoint deixarão de ser um mistério. Você aprenderá a criar documentos profissionais, planilhas organizadas e apresentações impactantes.',
            'icon' => 'bi-grid-3x3-gap',
            'color' => 'bg-warning text-dark'
        ],
        [
            'title' => 'Rotinas Administrativas',
            'thumb_small' => 'assets/images/thumbs/admin_small.jpg',
            'thumb_large' => 'assets/images/thumbs/admin_large.jpg',
            'brief' => 'Prepare-se para o dia a dia das empresas.',
            'desc' => 'Aprenda na prática como funciona um escritório: documentação, fluxo de caixa, atendimento corporativo e organização empresarial. O curso certo para quem busca o primeiro emprego e quer chegar preparado.',
            'icon' => 'bi-file-earmark-text',
            'color' => 'bg-secondary'
        ]
    ]
];

// Fetch DB courses to map IDs and Status
$dbCourses = [];
$res = $conn->query("SELECT id, titulo, inscricoes_abertas FROM cursos");
while($r = $res->fetch_assoc()) {
    $normalized = mb_strtolower(trim($r['titulo']));
    // Determine if open: default to true if column is null (legacy compatibility)
    $isOpen = (isset($r['inscricoes_abertas']) && $r['inscricoes_abertas'] == 0) ? false : true;
    $dbCourses[$normalized] = [
        'id' => (int)$r['id'],
        'open' => $isOpen
    ];
}
?>

<style>
.course-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    overflow: hidden;
}
.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.thumb-placeholder {
    height: 160px; /* Slightly taller */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: white;
    /* subtle pattern for improvement */
    background-image: linear-gradient(45deg, rgba(255,255,255,0.1) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.1) 75%, transparent 75%, transparent);
    background-size: 20px 20px;
}
.collapse-content {
    background-color: #f8f9fa;
    border-radius: 12px; 
    border-left: 5px solid var(--bs-primary);
}
/* Enhanced Banner Style */
.courses-banner {
    /* Background image - user can replace url */
    background: url('assets/images/banner-cursos.jpg') center center / cover no-repeat;
    background-color: var(--ios-purple); /* Fallback */
    color: white;
    padding: 100px 0;
    margin-bottom: 3rem;
    border-radius: 0 0 50px 50px;
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
}
.courses-banner:hover {
    transform: scale(1.01);
    box-shadow: 0 25px 80px rgba(89, 0, 179, 0.35);
}
.courses-banner::before {
    /* Gradient overlay - left side darker for text, right side transparent for image */
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(to right, rgba(89, 0, 179, 0.95) 0%, rgba(89, 0, 179, 0.7) 40%, rgba(0,0,0,0.2) 70%, transparent 100%);
    z-index: 1;
}
</style>

<div class="courses-banner shadow-lg">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-6 text-start">
                <h1 class="display-3 fw-bold mb-3 text-white">Nossos Cursos</h1>
                <p class="h2 fw-light text-white mb-0" style="line-height: 1.4;">
                    Conheça as formações que vão <br>
                    <span class="fw-bold text-warning">transformar sua carreira.</span>
                </p>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <!-- Espaço livre para a arte do JPG brilhar no lado direito -->
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php foreach ($categories as $catName => $courses): ?>
        <div class="mb-5">
            <h2 class="fw-bold mb-4 border-start border-5 border-primary ps-3"><?= htmlspecialchars($catName) ?></h2>
            
            <div class="row g-4">
                <?php foreach ($courses as $index => $course): 
                    $safeId = 'course-' . md5($course['title']);
                    $dbId = 0;
                    $isOpen = true; // Default to open if not found in DB (conceptual placeholder)

                    foreach($dbCourses as $dName => $dInfo) {
                        if (str_contains(mb_strtolower($dName), mb_strtolower($course['title'])) || str_contains(mb_strtolower($course['title']), mb_strtolower($dName))) {
                            $dbId = $dInfo['id'];
                            $isOpen = $dInfo['open'];
                            break;
                        }
                    }
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 course-card rounded-4 shadow-sm">
                        <!-- Placeholder Thumb -->
                        <div class="thumb-placeholder <?= $course['color'] ?>">
                            <i class="bi <?= $course['icon'] ?>"></i>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-bold mb-2"><?= htmlspecialchars($course['title']) ?></h5>
                            <p class="text-muted small mb-3 flex-grow-1"><?= htmlspecialchars($course['brief']) ?></p>
                            
                            <button class="btn btn-outline-primary w-100 rounded-pill fw-semibold mt-auto" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $safeId ?>" aria-expanded="false">
                                Ver Detalhes <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 collapse" id="<?= $safeId ?>">
                    <div class="collapse-content p-4 shadow-sm mt-0 mb-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-toggle="collapse" data-bs-target="#<?= $safeId ?>"></button>
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center mb-3 mb-md-0 d-none d-md-block">
                                <div class="ratio ratio-1x1 rounded- circle overflow-hidden bg-secondary rounded-4 shadow-sm">
                                    <div class="d-flex align-items-center justify-content-center text-white h-100 <?= $course['color'] ?>">
                                        <i class="bi <?= $course['icon'] ?> fs-1"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <h3 class="fw-bold text-primary mb-3"><?= htmlspecialchars($course['title']) ?></h3>
                                <p class="lead fs-6 mb-4"><?= htmlspecialchars($course['desc']) ?></p>
                                
                                <div class="d-flex gap-3 flex-wrap">
                                    <?php if ($isLoggedIn): ?>
                                        <?php if($dbId > 0 && $isOpen): ?>
                                            <a href="<?= ios_url('/inscrever.php?curso_id=' . $dbId) ?>" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                                                Quero me Inscrever Agora!
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="btn btn-secondary btn-lg rounded-pill px-4">
                                                <?= ($dbId > 0 && !$isOpen) ? 'Inscrições Encerradas' : 'Em breve' ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if($dbId > 0 && !$isOpen): ?>
                                            <button disabled class="btn btn-secondary btn-lg rounded-pill px-4">Inscrições Encerradas</button>
                                        <?php else: ?>
                                            <a href="<?= ios_url('/auth/login.php?redirect=' . urlencode('inscrever.php?curso_id=' . ($dbId ?: 0))) ?>" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                                                Inscrever-se
                                            </a>
                                            <div class="d-flex align-items-center text-muted small">
                                                <i class="bi bi-info-circle me-1"></i> Você precisará acessar a Área do Aluno.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const collapses = document.querySelectorAll('.collapse');
    collapses.forEach(el => {
        el.addEventListener('shown.bs.collapse', (e) => {
            const y = e.target.getBoundingClientRect().top + window.scrollY - 100;
            window.scrollTo({top: y, behavior: 'smooth'});
        });
    });
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
