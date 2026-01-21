<?php
require 'protect.php';
require '../config/db.php';

// Criar curso
if (isset($_POST['criar'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $carga = $_POST['carga'];

    $stmt = $conn->prepare("INSERT INTO cursos (titulo, descricao, carga_horaria) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $titulo, $descricao, $carga);
    $stmt->execute();
}

// Excluir curso
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM cursos WHERE id = $id");
}

// Listar cursos
$cursos = $conn->query("SELECT * FROM cursos ORDER BY id DESC");
?>

<h1>Gerenciar Cursos</h1>

<h3>Novo Curso</h3>

<form method="POST">
    <input name="titulo" placeholder="Título do curso" required><br><br>
    <textarea name="descricao" placeholder="Descrição"></textarea><br><br>
    <input name="carga" type="number" placeholder="Carga horária" required><br><br>
    <button name="criar">Criar curso</button>
</form>

<hr>

<h3>Cursos cadastrados</h3>

<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Título</th>
    <th>Carga</th>
    <th>Ações</th>
</tr>

<?php while($c = $cursos->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= $c['titulo'] ?></td>
    <td><?= $c['carga_horaria'] ?>h</td>
    <td>
        <a href="?delete=<?= $c['id'] ?>">Excluir</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<br>
<a href="dashboard.php">Voltar ao painel</a>
