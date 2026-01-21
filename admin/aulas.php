<?php
require 'protect.php';
require '../config/db.php';

$cursos = $conn->query("SELECT * FROM cursos");

// Criar aula
if (isset($_POST['criar'])) {
    $curso_id = $_POST['curso_id'];
    $titulo = $_POST['titulo'];
    $conteudo = $_POST['conteudo'];

    $stmt = $conn->prepare("INSERT INTO aulas (curso_id, titulo, conteudo) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $curso_id, $titulo, $conteudo);
    $stmt->execute();
}

// Listar aulas
$aulas = $conn->query("
SELECT aulas.*, cursos.titulo AS curso
FROM aulas
JOIN cursos ON cursos.id = aulas.curso_id
ORDER BY aulas.id DESC
");
?>

<h1>Gerenciar Aulas (LMS)</h1>

<h3>Nova Aula</h3>

<form method="POST">
    <select name="curso_id" required>
        <option value="">Selecione o curso</option>
        <?php while($c = $cursos->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= $c['titulo'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <input name="titulo" placeholder="Título da aula" required><br><br>
    <textarea name="conteudo" placeholder="Conteúdo da aula"></textarea><br><br>

    <button name="criar">Criar aula</button>
</form>

<hr>

<h3>Aulas cadastradas</h3>

<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Curso</th>
    <th>Título</th>
</tr>

<?php while($a = $aulas->fetch_assoc()): ?>
<tr>
    <td><?= $a['id'] ?></td>
    <td><?= $a['curso'] ?></td>
    <td><?= $a['titulo'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<br>
<a href="dashboard.php">Voltar</a>
