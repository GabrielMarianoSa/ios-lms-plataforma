<?php
require 'protect.php';
require '../config/db.php';

$sql = "
SELECT 
    inscricoes.id,
    users.nome AS aluno,
    users.email,
    cursos.titulo AS curso,
    inscricoes.status,
    inscricoes.criado_em
FROM inscricoes
JOIN users ON users.id = inscricoes.user_id
JOIN cursos ON cursos.id = inscricoes.curso_id
ORDER BY inscricoes.id DESC
";

$result = $conn->query($sql);
?>

<h1>Inscrições nos cursos - IOS</h1>

<table border="1" cellpadding="8">
<tr>
    <th>ID</th>
    <th>Aluno</th>
    <th>Email</th>
    <th>Curso</th>
    <th>Status</th>
    <th>Data</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['aluno'] ?></td>
    <td><?= $row['email'] ?></td>
    <td><?= $row['curso'] ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= $row['criado_em'] ?></td>
</tr>
<?php endwhile; ?>

</table>

<br>
<a href="dashboard.php">Voltar ao painel</a>
