<?php
// services_upsert.php
declare(strict_types=1);
session_start();
require_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_profissional'])) { http_response_code(401); echo json_encode(['ok'=>false,'message'=>'Não autenticado']); exit; }
$pid = (int)$_SESSION['id_profissional'];

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$id         = isset($in['id']) ? (int)$in['id'] : 0;
$titulo     = trim((string)($in['titulo'] ?? ''));
$descricao  = trim((string)($in['descricao'] ?? ''));
$preco_min  = isset($in['preco_min']) && $in['preco_min'] !== '' ? (float)$in['preco_min'] : null;
$prazo_dias = isset($in['prazo_dias']) && $in['prazo_dias'] !== '' ? (int)$in['prazo_dias'] : null;
$ativo      = isset($in['ativo']) ? (int)$in['ativo'] : 1;

if ($titulo === '' || $descricao === '') {
  echo json_encode(['ok'=>false,'message'=>'Título e descrição são obrigatórios']);
  exit;
}

if ($id > 0) {
  // update (somente do dono)
  $stmt = $conn->prepare("UPDATE profissional_servico 
                          SET titulo=?, descricao=?, preco_min=?, prazo_dias=?, ativo=? 
                          WHERE id=? AND id_profissional=?");
  $stmt->bind_param("ssdiiii", $titulo, $descricao, $preco_min, $prazo_dias, $ativo, $id, $pid);
  $stmt->execute();
  echo json_encode(['ok'=>true, 'id'=>$id]);
} else {
  // insert
  $stmt = $conn->prepare("INSERT INTO profissional_servico (id_profissional, titulo, descricao, preco_min, prazo_dias, ativo) 
                          VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("issdii", $pid, $titulo, $descricao, $preco_min, $prazo_dias, $ativo);
  $stmt->execute();
  echo json_encode(['ok'=>true, 'id'=>$stmt->insert_id]);
}
?>