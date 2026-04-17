<?php
// pagamento_status.php — retorna status do pagamento local por ID
require_once __DIR__ . '/config_pagamentos.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'id inválido']); exit; }

$mysqli = db();
$stmt = $mysqli->prepare("SELECT status, txid FROM pagamentos WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) { http_response_code(404); echo json_encode(['error'=>'não encontrado']); exit; }

// Se ainda pendente, opcionalmente bater no MP por TXID aqui:
$status = $res['status'];
if ($status === 'pendente' && !empty($res['txid'])) {
  try {
    $p = mp_call('GET', '/v1/payments/'.$res['txid']);
    $mpStatus = strtolower($p['status'] ?? 'pending');
    if (in_array($mpStatus, ['approved','accredited'])) {
      // atualiza local
      $old = $status;
      $status = 'aprovado';
      $stmt = $mysqli->prepare("UPDATE pagamentos SET status='aprovado', atualizado_em=NOW() WHERE id=?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $stmt->close();

      // log
      $stmt = $mysqli->prepare("INSERT INTO pagamento_status_log (pagamento_id, status_old, status_new, note, created_at)
                                VALUES (?, ?, 'aprovado', 'poll', NOW())");
      $stmt->bind_param('is', $id, $old);
      $stmt->execute();
      $stmt->close();

      // (opcional) agenda -> concluido
      $mysqli->query("UPDATE agenda_profissional ap
                      JOIN pagamentos pg ON pg.agenda_id=ap.id
                      SET ap.status='concluido'
                      WHERE pg.id={$id}");
    }
  } catch(Exception $e) {
    // ignora, volta o status local
  }
}

echo json_encode(['id'=>$id, 'status'=>$status], JSON_UNESCAPED_UNICODE);
