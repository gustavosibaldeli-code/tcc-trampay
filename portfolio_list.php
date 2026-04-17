<?php
declare(strict_types=1);
ini_set('display_errors','0'); ini_set('log_errors','1'); ini_set('error_log', __DIR__.'/logs_portfolio_list.log');
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/conexao.php';

if (empty($_SESSION['id_profissional'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Sessão expirada.']); exit;
}
$id = (int)$_SESSION['id_profissional'];

$stmt = $conn->prepare("SELECT id, url FROM profissional_portfolio WHERE id_profissional = ? ORDER BY criado_em DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) { $items[] = ['id'=>(int)$row['id'], 'url'=>$row['url']]; }
echo json_encode(['ok'=>true,'items'=>$items]);
