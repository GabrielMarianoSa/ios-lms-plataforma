<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Editor de Conteúdo • Admin';
$activeNav = 'admin';
$isAdminArea = true;

// Evita fatal error se as tabelas ainda não existirem
if (!isset($conn) || !($conn instanceof mysqli) || !ios_table_exists($conn, 'site_conteudo') || !ios_table_exists($conn, 'site_parceiros')) {
    http_response_code(500);
    require __DIR__ . '/../partials/header.php';
    echo '<div class="container py-5"><div class="alert alert-danger">';
    echo '<strong>Editor indisponível:</strong> tabelas de conteúdo não encontradas no banco.';
    echo '<br>Rode o SQL de criação de tabelas (site_conteudo.sql) e recarregue.';
    echo '</div></div>';
    require __DIR__ . '/../partials/footer.php';
    exit;
}

// Normalização de defaults antigos (evita texto com cara de IA)
$conn->query("UPDATE site_conteudo SET valor='24 anos transformando vidas' WHERE chave='home_badge' AND valor='24 Anos Transformando Vidas'");

// Processar ações (salvar, upload, etc)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!function_exists('ios_csrf_validate') || !ios_csrf_validate($_POST['csrf'] ?? null)) {
        http_response_code(400);
        $erro = 'Solicitação inválida. Recarregue a página e tente novamente.';
    } else {
    
        // Salvar conteúdo de texto
        if (isset($_POST['salvar_conteudo'])) {
            foreach ($_POST['conteudo'] as $chave => $valor) {
                if ($chave === 'site_url') {
                    continue;
                }
                $chave = $conn->real_escape_string($chave);
                $valor = $conn->real_escape_string($valor);
                $conn->query("UPDATE site_conteudo SET valor = '$valor' WHERE chave = '$chave'");
            }
            $sucesso = "Conteúdo atualizado com sucesso!";
        }
    
        // Upload de banner
        if (isset($_FILES['upload_banner']) && $_FILES['upload_banner']['error'] === UPLOAD_ERR_OK) {
            $maxBytes = 3 * 1024 * 1024; // 3MB
            $tmp = (string)($_FILES['upload_banner']['tmp_name'] ?? '');
            $size = (int)($_FILES['upload_banner']['size'] ?? 0);
            $origName = (string)($_FILES['upload_banner']['name'] ?? '');
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if ($size <= 0 || $size > $maxBytes) {
                $erro = 'Arquivo inválido (tamanho máximo 3MB).';
            } elseif ($tmp === '' || !is_uploaded_file($tmp)) {
                $erro = 'Upload inválido.';
            } elseif (!in_array($ext, $allowed, true)) {
                $erro = 'Formato inválido. Envie JPG, PNG ou WEBP.';
            } elseif (@getimagesize($tmp) === false) {
                $erro = 'O arquivo enviado não é uma imagem válida.';
            } else {
                $uploadDir = __DIR__ . '/../assets/images/';
                $fileName = 'banner-home-' . time() . '.' . $ext;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($tmp, $uploadPath)) {
                    $relativePath = 'assets/images/' . $fileName;
                    $conn->query("UPDATE site_conteudo SET valor = '$relativePath' WHERE chave = 'home_banner'");
                    $sucesso = "Banner atualizado com sucesso!";
                } else {
                    $erro = "Erro ao fazer upload do banner.";
                }
            }
        }
    
        // Adicionar/Editar parceiro
        if (isset($_POST['salvar_parceiro'])) {
            $id = (int)($_POST['parceiro_id'] ?? 0);
            $nome = $conn->real_escape_string((string)($_POST['parceiro_nome'] ?? ''));
            $ordem = (int)($_POST['parceiro_ordem'] ?? 0);
            $logo = $conn->real_escape_string((string)($_POST['parceiro_logo'] ?? ''));

            if ($id > 0) {
                $conn->query("UPDATE site_parceiros SET nome='$nome', ordem=$ordem, logo='$logo' WHERE id=$id");
            } else {
                $conn->query("INSERT INTO site_parceiros (nome, logo, ordem) VALUES ('$nome', '$logo', $ordem)");
            }
            $sucesso = "Parceiro salvo com sucesso!";
        }
    
        // Excluir parceiro
        if (isset($_POST['excluir_parceiro'])) {
            $id = (int)($_POST['parceiro_id'] ?? 0);
            $conn->query("DELETE FROM site_parceiros WHERE id = $id");
            $sucesso = "Parceiro excluído!";
        }
    }
}

// Buscar conteúdo atual
$conteudo = [];
$result = $conn->query("SELECT * FROM site_conteudo ORDER BY grupo, id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $conteudo[$row['grupo']][] = $row;
    }
}

// Buscar parceiros
$parceiros = [];
$result = $conn->query("SELECT * FROM site_parceiros ORDER BY ordem, id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parceiros[] = $row;
    }
}

require __DIR__ . '/../partials/header.php';
?>

<style>
    .editor-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--ios-purple-dark);
    }
    .banner-preview {
        width: 100%;
        max-width: 400px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .parceiro-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 2px solid transparent;
        transition: all 0.3s;
    }
    .parceiro-card:hover {
        border-color: var(--ios-purple);
    }
    .parceiro-logo {
        max-height: 40px;
        max-width: 100px;
        object-fit: contain;
    }
</style>

<div class="container py-4">
    
    <!-- Hero -->
    <div class="admin-hero" style="background: linear-gradient(135deg, var(--ios-purple) 0%, var(--ios-purple-dark) 100%); color: white; border-radius: 24px; padding: 2rem; margin-bottom: 2rem;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 fw-bold mb-2">Editor de Conteúdo</h1>
                <p class="mb-0 opacity-75">Edite textos, imagens e estatísticas do site</p>
            </div>
            <a href="dashboard.php" class="btn btn-light fw-semibold">
                <i class="bi bi-arrow-left me-2"></i>Voltar ao Painel
            </a>
        </div>
    </div>
    
    <!-- Alertas -->
    <?php if (isset($sucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= $erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="editorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-content" type="button">
                Home
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-content" type="button">
                Estatísticas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="partners-tab" data-bs-toggle="tab" data-bs-target="#partners-content" type="button">
                Parceiros
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-content" type="button">
                Geral
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="editorTabsContent">
        
        <!-- HOME -->
        <div class="tab-pane fade show active" id="home-content" role="tabpanel">
            <form method="POST" class="editor-section">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(ios_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="editor-header">
                    <h3 class="h5 fw-bold mb-0">Conteúdo da Home</h3>
                    <button type="submit" name="salvar_conteudo" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
                
                <?php if (isset($conteudo['home'])): foreach ($conteudo['home'] as $item): ?>
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($item['descricao']) ?></label>
                        <?php if ($item['tipo'] === 'textarea'): ?>
                            <textarea name="conteudo[<?= $item['chave'] ?>]" class="form-control" rows="3"><?= htmlspecialchars($item['valor']) ?></textarea>
                        <?php elseif ($item['tipo'] === 'imagem'): ?>
                            <div class="mb-2">
                                <img src="<?= ios_url('/' . $item['valor']) ?>" class="banner-preview" alt="Banner">
                            </div>
                            <input type="text" name="conteudo[<?= $item['chave'] ?>]" class="form-control mb-2" value="<?= htmlspecialchars($item['valor']) ?>" readonly>
                            <small class="text-muted">Use o formulário abaixo para fazer upload de novo banner</small>
                        <?php else: ?>
                            <input type="text" name="conteudo[<?= $item['chave'] ?>]" class="form-control" value="<?= htmlspecialchars($item['valor']) ?>">
                        <?php endif; ?>
                        <small class="text-muted">Chave: <code><?= $item['chave'] ?></code></small>
                    </div>
                <?php endforeach; endif; ?>
            </form>
            
            <!-- Upload Banner -->
            <form method="POST" enctype="multipart/form-data" class="editor-section">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(ios_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <h4 class="h6 fw-bold mb-3"><i class="bi bi-image me-2"></i>Upload de Novo Banner</h4>
                <div class="form-group">
                    <input type="file" name="upload_banner" class="form-control" accept="image/*">
                    <small class="text-muted">Formatos aceitos: JPG, PNG, WEBP</small>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload me-2"></i>Fazer Upload
                </button>
            </form>
        </div>
        
        <!-- ESTATÍSTICAS -->
        <div class="tab-pane fade" id="stats-content" role="tabpanel">
            <form method="POST" class="editor-section">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(ios_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="editor-header">
                    <h3 class="h5 fw-bold mb-0">Estatísticas do Site</h3>
                    <button type="submit" name="salvar_conteudo" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
                
                <?php if (isset($conteudo['estatisticas'])): foreach ($conteudo['estatisticas'] as $item): ?>
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($item['descricao']) ?></label>
                        <input type="number" name="conteudo[<?= $item['chave'] ?>]" class="form-control" value="<?= htmlspecialchars($item['valor']) ?>">
                        <small class="text-muted">Chave: <code><?= $item['chave'] ?></code></small>
                    </div>
                <?php endforeach; endif; ?>
            </form>
        </div>
        
        <!-- PARCEIROS -->
        <div class="tab-pane fade" id="partners-content" role="tabpanel">
            <div class="editor-section">
                <div class="editor-header">
                    <h3 class="h5 fw-bold mb-0">Gerenciar Parceiros</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoParceiro">
                        <i class="bi bi-plus-circle me-2"></i>Adicionar Parceiro
                    </button>
                </div>
                
                <?php foreach ($parceiros as $p): ?>
                    <div class="parceiro-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= ios_url('/' . $p['logo']) ?>" class="parceiro-logo" alt="<?= htmlspecialchars($p['nome']) ?>">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($p['nome']) ?></div>
                                    <small class="text-muted">Ordem: <?= $p['ordem'] ?></small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarParceiro(<?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir este parceiro?')">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(ios_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="parceiro_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="excluir_parceiro" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- GERAL -->
        <div class="tab-pane fade" id="general-content" role="tabpanel">
            <form method="POST" class="editor-section">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(ios_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="editor-header">
                    <h3 class="h5 fw-bold mb-0">Configurações Gerais</h3>
                    <button type="submit" name="salvar_conteudo" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
                
                <?php if (isset($conteudo['geral'])): foreach ($conteudo['geral'] as $item): ?>
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($item['descricao']) ?></label>
                        <?php if ($item['chave'] === 'site_url'): ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($item['valor']) ?>" disabled>
                            <small class="text-muted">Este valor é fixo no código e não pode ser alterado pelo painel.</small>
                        <?php else: ?>
                            <input type="text" name="conteudo[<?= $item['chave'] ?>]" class="form-control" value="<?= htmlspecialchars($item['valor']) ?>">
                        <?php endif; ?>
                        <small class="text-muted">Chave: <code><?= $item['chave'] ?></code></small>
                    </div>
                <?php endforeach; endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Modal Adicionar/Editar Parceiro -->
<div class="modal fade" id="novoParceiro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(ios_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar/Editar Parceiro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="parceiro_id" id="parceiro_id">
                    <div class="mb-3">
                        <label class="form-label">Nome do Parceiro</label>
                        <input type="text" name="parceiro_nome" id="parceiro_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Caminho do Logo</label>
                        <input type="text" name="parceiro_logo" id="parceiro_logo" class="form-control" placeholder="assets/images/logo.png" required>
                        <small class="text-muted">Ex: assets/images/empresa.png</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordem de Exibição</label>
                        <input type="number" name="parceiro_ordem" id="parceiro_ordem" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_parceiro" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarParceiro(parceiro) {
    document.getElementById('parceiro_id').value = parceiro.id;
    document.getElementById('parceiro_nome').value = parceiro.nome;
    document.getElementById('parceiro_logo').value = parceiro.logo;
    document.getElementById('parceiro_ordem').value = parceiro.ordem;
    new bootstrap.Modal(document.getElementById('novoParceiro')).show();
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
