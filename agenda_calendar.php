<?php
// api/agenda_calendar.php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$profId = isset($_GET['profissional_id']) ? (int)$_GET['profissional_id'] : 0;
$cliId  = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$year   = (int)($_GET['year'] ?? date('Y'));
$month  = (int)($_GET['month'] ?? date('n'));

if ($profId <= 0) { echo json_encode(['ok'=>false,'err'=>'prof']); exit; }

$inicio = sprintf('%04d-%02d-01', $year, $month);
$fim    = date('Y-m-d', strtotime("$inicio +1 month"));

$out = [];

try{
  $st = $conn->prepare("
    SELECT DATE(data_servico) AS d, status, justificativa, justificado_por
    FROM agenda_profissional
    WHERE profissional_id=? AND data_servico BETWEEN ? AND ?
  ");
  $st->bind_param('iss', $profId, $inicio, $fim);
  $st->execute();
  $rs = $st->get_result();

  while($r=$rs->fetch_assoc()){
    $d = $r['d'];
    if(!isset($out[$d])){
      $out[$d] = ['status'=>['agendado'=>0,'cancelado'=>0,'concluido'=>0], 'reagendado'=>false];
    }
    $s = strtolower($r['status']);
    if(isset($out[$d]['status'][$s])) $out[$d]['status'][$s]++;

    // ping azul = tem justificativa do profissional
    if(!empty($r['justificativa']) && strtolower($r['justificado_por']??'')==='profissional'){
      $out[$d]['reagendado'] = true;
    }
  }
  $st->close();
  echo json_encode(['ok'=>true,'dates'=>$out]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false]);
}
