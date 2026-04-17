<?php
session_start();
if (file_exists(__DIR__.'/conexao.php')) require_once __DIR__.'/conexao.php';
require_once __DIR__.'/config_pagamentos.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user=$_SESSION['user']??null;
$clienteId=($user && strtolower($user['tipo']??'')==='cliente')?(int)$user['id_cliente']:0;
if($clienteId<=0){exit('login');}

$db=db();
$agenda_id=(int)($_POST['agenda_id']??0);
if($agenda_id<=0) exit('agenda inválida');

$usar_salvo=isset($_POST['usar_salvo']);
$valor=(float)($db->query("SELECT valor_cobrado FROM agenda_profissional WHERE id=$agenda_id")->fetch_column());

if($usar_salvo){
  $cid=(int)$_POST['card_id'];
  $card=$db->query("SELECT * FROM cartoes_clientes WHERE id=$cid AND cliente_id=$clienteId")->fetch_assoc();
  if(!$card) exit('Cartão não encontrado');
  $bandeira=$card['bandeira'];$tipo=$card['tipo'];$ultimos4=$card['ultimos4'];
}else{
  $numero=preg_replace('/\D+/','',$_POST['numero']??'');
  $ultimos4=substr($numero,-4);
  $bandeira='desconhecida';
  $tipo=$_POST['tipo']??'credito';
  // salva
  $s=$db->prepare("INSERT INTO cartoes_clientes (cliente_id,bandeira,ultimos4,tipo,criado_em) VALUES (?,?,?,?,NOW())");
  $s->bind_param('isss',$clienteId,$bandeira,$ultimos4,$tipo);
  $s->execute();$s->close();
}

try{
  $body=[
    "transaction_amount"=>$valor,
    "description"=>"Pagamento cartão Trampay - agenda #$agenda_id",
    "payment_method_id"=>"visa", // MP identifica bandeira automaticamente com token
    "payer"=>["email"=>get_cliente_email($clienteId) ?: "no-reply@trampay.local"],
  ];
  $mp=mp_call('POST','/v1/payments',$body,['X-Idempotency-Key'=>'trampay-card-'.$agenda_id.'-'.bin2hex(random_bytes(6))]);

  $txid=$mp['id']??null;
  $status=$mp['status']==='approved'?'aprovado':'pendente';
  $stmt=$db->prepare("INSERT INTO pagamentos (agenda_id,profissional_id,cliente_id,valor,metodo,status,txid,brcode,atualizado_em,criado_em)
                      SELECT id,profissional_id,cliente_id,?, 'cartao', ?, ?, '',NOW(),NOW() FROM agenda_profissional WHERE id=?");
  $stmt->bind_param('dssi',$valor,$status,$txid,$agenda_id);
  $stmt->execute();
  $stmt->close();

  header("Location: pagamento.php?agenda_id=$agenda_id");
}catch(Exception $e){
  echo "Erro ao processar cartão: ".htmlspecialchars($e->getMessage());
}
