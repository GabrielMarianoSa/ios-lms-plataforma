<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/../config/db.php';

$pageTitle = 'Admin • Integrações';
$activeNav = 'admin';
$isAdminArea = true;
require __DIR__ . '/../partials/header.php';

$sistema = $_GET['sistema'] ?? '';
$q = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($sistema === 'RD Station' || $sistema === 'Protheus') {
    $where[] = 'sistema = ?';
    $types .= 's';
    $params[] = $sistema;
}

if ($q !== '') {
    $where[] = 'payload LIKE ?';
    $types .= 's';
    $params[] = '%' . $q . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT * FROM integracoes_log {$whereSql} ORDER BY id DESC LIMIT 200";
$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

function pretty_payload(string $payload): string
{
    $decoded = json_decode($payload, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $pretty ?: $payload;
    }

    return $payload;
}

?>

<div class="container">
  <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-1">Logs de Integrações</h1>
      <div class="text-muted">RD Station e Protheus (simulados) — últimas 200 entradas.</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-primary" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Voltar ao painel</a>
    </div>
  </div>

  <form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-12 col-md-3">
      <label class="form-label">Sistema</label>
      <select name="sistema" class="form-select">
        <option value="" <?= $sistema === '' ? 'selected' : '' ?>>Todos</option>
        <option value="RD Station" <?= $sistema === 'RD Station' ? 'selected' : '' ?>>RD Station</option>
        <option value="Protheus" <?= $sistema === 'Protheus' ? 'selected' : '' ?>>Protheus</option>
      </select>
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Buscar no payload</label>
      <input name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ex: user_id=1, curso_id=3...">
    </div>
    <div class="col-12 col-md-3 d-grid">
      <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
    </div>
  </form>

  <?php if (empty($rows)): ?>
    <div class="alert alert-warning">Nenhum log encontrado com os filtros atuais.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Sistema</th>
            <th>Data</th>
            <th>Payload</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="text-muted">#<?= (int)($row['id'] ?? 0) ?></td>
              <td>
                <?php $sys = (string)($row['sistema'] ?? ''); ?>
                <span class="badge <?= $sys === 'Protheus' ? 'text-bg-dark' : 'text-bg-primary' ?>">
                  <?= htmlspecialchars($sys, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td class="small text-muted">
                <?= htmlspecialchars((string)($row['criado_em'] ?? $row['created_at'] ?? $row['data'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td>
                <details>
                  <summary class="small">ver payload</summary>
                  <pre class="small bg-light p-2 rounded mb-0" style="max-width: 720px; white-space: pre-wrap;"><?= htmlspecialchars(pretty_payload((string)($row['payload'] ?? '')), ENT_QUOTES, 'UTF-8') ?></pre>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
