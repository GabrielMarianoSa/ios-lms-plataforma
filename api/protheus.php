<?php
require '../config/db.php';

$data = json_encode($_POST);

$stmt = $conn->prepare("INSERT INTO integracoes_log (sistema, payload) VALUES ('Protheus', ?)");
$stmt->bind_param("s", $data);
$stmt->execute();

echo json_encode(["status" => "ok"]);
