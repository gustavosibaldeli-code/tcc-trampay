<?php
// pagamento_webhook_pix.php — webhook do MP para PIX
require_once __DIR__ . '/config_pagamentos.php';

// 1) Corpo & log bruto (opcional)
$raw = file_get_contents('php://input');
file_put_contents(__DIR__ . '/logs_webhook.txt', date('c')." ".$raw.PHP_EOL, FILE_APPEND);
$data = json_decode($raw, true);

// 2) Extrai o ID do pagamento
$paymentId = null;
// Formato comum do MP: { "type":"payment", "data":{"id":"123"} }
if (isset($data['type']) && $data['type'] === 'payment' && isset($data['data']['id'])) {
  $paymentId = (string)$data['data']['id'];
} elseif (isset($_GET['data_id'])) {
  $paymentId = (string)$_GET['data_id'];
}

if (!$paymentId) { http_response_code(400); exit('no payment id'); }

// 3) Consulta status real no MP
try {
  $payment = mp_call('GET', '/v1/payments/'.$paymentId);
} catch (Exception $e) {
  http_response_code(500);
  exit('mp query fail');
}
$status  = strtolower($payment['status'] ?? '');

// 4) Se aprovado, atualiza o banco
$mysqli = db();

if ($status === 'approved' || $status === 'accredited') {
  $txid = (string)($payment['id'] ?? '');
  $external_ref = (string)($payment['external_reference'] ?? '');

  // Tenta atualizar por txid
  $stmt = $mysqli->prepare("UPDATE pagamentos SET status='aprovado', atualizado_em=NOW() WHERE txid=?");
  $stmt->bind_param('s', $txid);
  $stmt->execute();
  $rows = $stmt->affected_rows;
  $stmt->close();

  // (fallback) tentar por external_reference se você salvou esse campo em outra coluna
  if ($rows === 0 && $external_ref !== '') {
    // Exemplo: se você salva external_reference em `comprovante_url` (ajuste se tiver outra coluna)
    $stmt = $mysqli->prepare("UPDATE pagamentos SET status='aprovado', atualizado_em=NOW() WHERE comprovante_url=?");
    $stmt->bind_param('s', $external_ref);
    $stmt->execute();
    $rows = $stmt->affected_rows;
    $stmt->close();
  }

  // Log
  if ($rows > 0) {
    // Descobre o pagamento_id para log e agenda
    $stmt = $mysqli->prepare("SELECT id, agenda_id FROM pagamentos WHERE txid=? LIMIT 1");
    $stmt->bind_param('s', $txid);
    $stmt->execute();
    $pay = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pay) {
      $pid = (int)$pay['id'];
      $stmt = $mysqli->prepare("INSERT INTO pagamento_status_log (pagamento_id, status_old, status_new, note, created_at)
                                VALUES (?, 'pendente', 'aprovado', 'webhook', NOW())");
      $stmt->bind_param('i', $pid);
      $stmt->execute();
      $stmt->close();

      // (opcional) refletir na agenda
      $agId = (int)$pay['agenda_id'];
      if ($agId > 0) {
        $stmt = $mysqli->prepare("UPDATE agenda_profissional SET status='concluido' WHERE id=?");
        $stmt->bind_param('i', $agId);
        $stmt->execute();
        $stmt->close();
      }
    }
  }

  http_response_code(200);
  echo 'ok';
  exit;
}

// 5) Não aprovado — apenas 200 para o MP não reenfileirar à toa
http_response_code(200);
echo 'ignored';
