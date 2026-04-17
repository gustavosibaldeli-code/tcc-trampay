<?php
// avaliar.php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false, 'msg'=>'Faça login para avaliar.']);
  exit;
}

$clienteId = (int)$_SESSION['user_id'];

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'trampay';

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($db->connect_error) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Erro de conexão.']);
  exit;
}
$db->set_charset('utf8mb4');

$profId    = isset($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : 0;
$nota      = isset($_POST['nota']) ? (int)$_POST['nota'] : 0;
$comentario= isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

if ($profId <= 0 || $nota < 1 || $nota > 5) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'msg'=>'Dados inválidos.']);
  exit;
}

// UPSERT: atualiza se já existir avaliação do mesmo cliente para esse profissional
$sql = "
  INSERT INTO avaliacao (profissional_id, cliente_id, nota, comentario)
  VALUES (?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE nota = VALUES(nota), comentario = VALUES(comentario)
";
$stmt = $db->prepare($sql);
$stmt->bind_param('iiis', $profId, $clienteId, $nota, $comentario);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Não foi possível salvar sua avaliação.']);
  exit;
}

echo json_encode(['ok'=>true,'msg'=>'Avaliação registrada!']);
