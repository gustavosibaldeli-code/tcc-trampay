<?php
// services_delete.php
declare(strict_types=1);
session_start();
require_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_profissional'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
$pid = (int)$_SESSION['id_profissional'];

$in = json_decode(file_get_contents('php://input'), true);
$id = isset($in['id']) ? (int)$in['id'] : 0;
if ($id <= 0) { echo json_encode(['ok'=>false,'message'=>'ID inválido']); exit; }

$stmt = $conn->prepare("DELETE FROM profissional_servico WHERE id=? AND id_profissional=?");
$stmt->bind_param("ii", $id, $pid);
$stmt->execute();

echo json_encode(['ok'=> $stmt->affected_rows > 0]);
?>