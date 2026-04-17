<?php
// pagamento_poll.php — checa status no DB e confirma com MP se estiver pendente
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__.'/conexao.php')) require_once __DIR__.'/conexao.php';
require_once __DIR__.'/config_pagamentos.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['error'=>'invalid_id']); exit; }

$db = db();
$q = $db->prepare("SELECT * FROM pagamentos WHERE id = ? LIMIT 1");
$q->bind_param('i', $id);
$q->execute();
$pm = $q->get_result()->fetch_assoc();
$q->close();
if (!$pm) { echo json_encode(['error'=>'not_found']); exit; }

$status = $pm['status'];
$txid   = $pm['txid'] ?? null;

if ($status === 'pendente' && $txid) {
  try {
    $mp = checar_status_mp($txid);
    $mp_status = $mp['status'] ?? null; // approved, pending, in_process, rejected...

    $novo = $status;
    if ($mp_status === 'approved') $novo = 'aprovado';
    elseif (in_array($mp_status, ['pending','in_process','queued'])) $novo = 'pendente';
    else $novo = 'falhou';

    if ($novo !== $status) {
      $u = $db->prepare("UPDATE pagamentos SET status = ?, atualizado_em = NOW() WHERE id = ?");
      $u->bind_param('si', $novo, $id);
      $u->execute();
      $u->close();

      // log opcional
      if ($db->query("SHOW TABLES LIKE 'pagamento_status_log'")->num_rows) {
        $ins = $db->prepare("INSERT INTO pagamento_status_log (pagamento_id, status_old, status_new, note) VALUES (?, ?, ?, ?)");
        $note = "MP status: " . ($mp_status ?? 'unknown');
        $ins->bind_param('isss', $id, $status, $novo, $note);
        $ins->execute();
        $ins->close();
      }

      $status = $novo;
    }
  } catch (Throwable $e) {
    // mantém status local se MP falhar
  }
}

echo json_encode(['id'=>$id, 'status'=>$status]);
