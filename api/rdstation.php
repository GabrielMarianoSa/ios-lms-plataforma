<?php
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = $raw !== false && trim($raw) !== '' ? $raw : json_encode($_POST, JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("INSERT INTO integracoes_log (sistema, payload) VALUES ('RD Station', ?)");
$stmt->bind_param("s", $data);
$stmt->execute();

echo json_encode(["status" => "ok"]);
exit;
