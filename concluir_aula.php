<?php
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ios_url('/auth/login.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$aula_id = (int)($_GET['aula_id'] ?? 0);
$curso_id = (int)($_GET['curso_id'] ?? 0);

// If aula has quiz, only allow conclusion when approved
$aulaRes = $conn->query("SELECT q1_pergunta, q2_pergunta FROM aulas WHERE id={$aula_id} LIMIT 1");
if ($aulaRes && ($aRow = $aulaRes->fetch_assoc())) {
    $hasQuiz = !empty($aRow['q1_pergunta']) && !empty($aRow['q2_pergunta']);
    if ($hasQuiz) {
        $chk = $conn->query("SELECT aprovado FROM aula_quiz_respostas WHERE user_id={$user_id} AND aula_id={$aula_id} LIMIT 1");
        $ok = ($chk && ($r = $chk->fetch_assoc()) && !empty($r['aprovado']));
        if (!$ok) {
            header('Location: ' . ios_url("/aula.php?curso_id={$curso_id}&aula_id={$aula_id}"));
            exit;
        }
    }
}

// Verificar se já existe
$check = $conn->query("SELECT id FROM progresso WHERE user_id=$user_id AND aula_id=$aula_id");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO progresso (user_id, aula_id, concluida) VALUES ($user_id, $aula_id, 1)");
}

header('Location: ' . ios_url("/curso.php?id=$curso_id"));
exit;
