<?php
// reviews_list.php — lista avaliações do profissional logado (somente leitura)
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs_reviews_list.log');

header('Content-Type: application/json; charset=utf-8');

session_set_cookie_params(['path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
session_start();

require_once __DIR__ . '/conexao.php'; // $conn = new mysqli(...)

if (empty($_SESSION['id_profissional'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
  exit;
}

$id_prof = (int) $_SESSION['id_profissional'];

/*
  Tabelas/colunas conforme teu banco:
  - avaliacao(id, profissional_id, cliente_id, nota, comentario, created_at)
  - clientes(id_cliente, nome, ...)
*/
$sql = "
  SELECT
    a.id,
    a.nota,
    a.comentario,
    DATE_FORMAT(a.created_at, '%d/%m/%Y %H:%i') AS data,
    c.nome AS cliente
  FROM avaliacao a
  JOIN clientes c ON c.id_cliente = a.cliente_id
  WHERE a.profissional_id = ?
  ORDER BY a.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  error_log('Prepare failed: '.$conn->error);
  echo json_encode(['ok'=>false, 'items'=>[], 'message'=>'Erro interno.']);
  exit;
}

$stmt->bind_param('i', $id_prof);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
  $items[] = [
    'id'        => (int)$row['id'],
    'cliente'   => $row['cliente'] ?? 'Cliente',
    'nota'      => (float)$row['nota'],
    'comentario'=> $row['comentario'] ?? '',
    'data'      => $row['data'] ?? ''
  ];
}

echo json_encode(['ok' => true, 'items' => $items]);
