<?php
declare(strict_types=1);
ini_set('display_errors','0'); ini_set('log_errors','1'); ini_set('error_log', __DIR__.'/logs_portfolio_delete.log');
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/conexao.php';

if (empty($_SESSION['id_profissional'])) { http_response_code(401); echo json_encode(['ok'=>false,'message'=>'Sessão expirada.']); exit; }

$in  = json_decode(file_get_contents('php://input'), true) ?: [];
$idp = (int)$_SESSION['id_profissional'];
$pid = (int)($in['id'] ?? 0);
if ($pid<=0) { echo json_encode(['ok'=>false,'message'=>'ID inválido']); exit; }

$stmt = $conn->prepare("SELECT url FROM profissional_portfolio WHERE id=? AND id_profissional=?");
$stmt->bind_param("ii",$pid,$idp);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { echo json_encode(['ok'=>false,'message'=>'Item não encontrado']); exit; }

$abs = __DIR__ . '/' . $row['url'];
if (is_file($abs)) @unlink($abs);

$stmt = $conn->prepare("DELETE FROM profissional_portfolio WHERE id=? AND id_profissional=?");
$stmt->bind_param("ii",$pid,$idp);
$ok = $stmt->execute();

echo json_encode(['ok'=>(bool)$ok]);
