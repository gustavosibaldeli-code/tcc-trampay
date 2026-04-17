<?php
// api/agenda_status.php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$profId = isset($_GET['profissional_id']) ? (int)$_GET['profissional_id'] : 0;
$cliId  = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

if ($profId<=0 || $cliId<=0) { echo json_encode(['ok'=>false]); exit; }

try{
  $st = $conn->prepare("
    SELECT id, DATE(data_servico) AS data_, TIME_FORMAT(hora_servico,'%H:%i') AS hora_,
           status, justificativa, justificado_por,
           DATE_FORMAT(atualizado_em,'%d/%m/%Y %H:%i') AS atualizado_em
    FROM agenda_profissional
    WHERE profissional_id=? AND cliente_id=?
    ORDER BY id DESC LIMIT 1
  ");
  $st->bind_param('ii', $profId, $cliId);
  $st->execute();
  $ag = $st->get_result()->fetch_assoc();
  $st->close();

  echo json_encode(['ok'=>true,'ag'=>$ag]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false]);
}
