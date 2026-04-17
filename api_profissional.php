<?php
// api_profissional.php
declare(strict_types=1);
session_start();
require_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_profissional'])) { http_response_code(401); echo json_encode(['ok'=>false,'items'=>[]]); exit; }
$pid = (int)$_SESSION['id_profissional'];

$method = $_GET['m'] ?? '';
if ($method === 'services.list') {
  $res = $conn->query("SELECT id, titulo, descricao, preco_min, prazo_dias, ativo FROM profissional_servico WHERE id_profissional={$pid} ORDER BY id DESC");
  $items = [];
  while ($row = $res->fetch_assoc()) {
    $items[] = [
      'id'=>(int)$row['id'],
      'titulo'=>$row['titulo'],
      'descricao'=>$row['descricao'],
      'preco_min'=>$row['preco_min'],
      'prazo_dias'=>$row['prazo_dias'],
      'ativo'=>(int)$row['ativo']
    ];
  }
  echo json_encode(['ok'=>true, 'items'=>$items]);
  exit;
}

echo json_encode(['ok'=>false,'message'=>'Método inválido']);
?>