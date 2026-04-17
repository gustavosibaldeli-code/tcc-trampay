<?php
session_start();
if (file_exists(__DIR__.'/conexao.php')) require_once __DIR__.'/conexao.php';
require_once __DIR__.'/config_pagamentos.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user = $_SESSION['user'] ?? null;
$clienteId = ($user && strtolower((string)($user['tipo'] ?? ''))==='cliente') ? (int)($user['id_cliente']??0):0;
if($clienteId<=0){header('Location: login_cliente.php?redirect='.urlencode($_SERVER['REQUEST_URI']));exit;}

$agenda_id = (int)($_GET['agenda_id']??0);
if($agenda_id<=0){http_response_code(400);exit('Agenda inválida');}

$db=db();
$q=$db->prepare("SELECT a.id,a.profissional_id,a.valor_cobrado,p.nome AS profissional_nome
                 FROM agenda_profissional a
                 LEFT JOIN profissional p ON p.id_profissional=a.profissional_id
                 WHERE a.id=? LIMIT 1");
$q->bind_param('i',$agenda_id);
$q->execute();
$ag=$q->get_result()->fetch_assoc();
$q->close();
if(!$ag){exit('Agendamento não encontrado.');}

// cartões já salvos
$cards=$db->query("SELECT * FROM cartoes_clientes WHERE cliente_id={$clienteId} ORDER BY criado_em DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pagamento com Cartão • Trampay</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
body{background:#f5f7fb;color:#0b1720;font-family:Inter,system-ui;}
.cardx{border-radius:16px;border:1px solid #e5ebf3;box-shadow:0 10px 30px rgba(7,20,46,.07);background:#fff;}
.hero{display:flex;justify-content:space-between;align-items:center;padding:22px 26px;background:linear-gradient(180deg,#eef5ff 0,#ffffff 100%);}
.brand img{height:68px}
.section{padding:24px 26px}
.btn-brand{background:#0a66c2;color:#fff;border:none;border-radius:10px;padding:10px 16px}
.btn-outline{border:1px solid #e5ebf3;background:#fff;color:#0a66c2;border-radius:10px;padding:10px 14px}
.form-control{border-radius:8px}
/* ++ barra azul fixa dentro do card (igual a do PIX) */
.topbar {
  background: #00185c;
  color: #fff;
  border-radius: 14px;
  padding: 14px 18px;
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  box-shadow: 0 6px 20px rgba(2,30,80,.18) inset;
}
.topbar .left{display:flex; align-items:center; gap:12px;}
.topbar .logo{height:44px; width:auto; display:block;}
.topbar .title{line-height:1.15;}
.topbar .title small{display:block; opacity:.9; font-weight:500;}
.topbar .amount{text-align:right;}
.topbar .amount .label{font-size:12px; opacity:.9;}
.topbar .amount .value{font-weight:800; font-size:20px;}

</style>
</head>
<body>
<div class="container py-4">
<div class="mx-auto" style="max-width:860px">
<div class="cardx">
 <div class="section" style="padding-bottom:0">
  <div class="topbar">
    <div class="left">
      <?php if (file_exists(__DIR__.'/logo.png')): ?>
        <img src="logo.png" class="logo" alt="Trampay">
      <?php else: ?>
        <div style="font-family:'Bebas Neue',sans-serif;font-size:28px;letter-spacing:1px">TRAMPAY</div>
      <?php endif; ?>
      <div class="title">
        <small>Pagamento com cartão — agendamento #<?=htmlspecialchars($agenda_id)?></small>
        <div style="font-weight:600"><?=htmlspecialchars($ag['profissional_nome'] ?? 'Profissional')?></div>
      </div>
    </div>
    <div class="amount">
      <div class="label">Total</div>
      <div class="value">R$ <?=number_format((float)($ag['valor_cobrado'] ?? 0),2,',','.')?></div>
    </div>
  </div>
</div>


  <div class="section">
    <h5 class="mb-3">Pagamento com Cartão</h5>

    <?php if($cards): ?>
      <div class="mb-4">
        <label class="form-label">Cartões salvos</label>
        <form method="post" action="cartao_salvar.php">
          <input type="hidden" name="agenda_id" value="<?=$agenda_id?>">
          <input type="hidden" name="usar_salvo" value="1">
          <?php foreach($cards as $c): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="card_id" value="<?=$c['id']?>" required>
              <label class="form-check-label">
                <?=htmlspecialchars(strtoupper($c['bandeira']))?> •••• <?=$c['ultimos4']?> — <?=htmlspecialchars($c['tipo'])?>
              </label>
            </div>
          <?php endforeach; ?>
          <button class="btn btn-brand mt-3" type="submit">Pagar com cartão selecionado</button>
        </form>
      </div>
    <?php endif; ?>

    <hr>

    <form method="post" action="cartao_salvar.php">
      <input type="hidden" name="agenda_id" value="<?=$agenda_id?>">
      <label class="form-label">Número do cartão</label>
      <input class="form-control mb-2" name="numero" maxlength="19" required>
      <div class="row">
        <div class="col"><label class="form-label">Validade (MM/AA)</label>
          <input class="form-control mb-2" name="validade" placeholder="12/29" required></div>
        <div class="col"><label class="form-label">CVV</label>
          <input class="form-control mb-2" name="cvv" maxlength="4" required></div>
      </div>
      <label class="form-label">Nome impresso</label>
      <input class="form-control mb-3" name="nome" required>
      <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select class="form-select" name="tipo" required>
          <option value="credito">Crédito</option>
          <option value="debito">Débito</option>
        </select>
      </div>
      <button class="btn btn-brand" type="submit">Pagar agora</button>
      <a href="pagamento.php?agenda_id=<?=$agenda_id?>" class="btn btn-outline ms-2">Voltar para PIX</a>
      <a href="agenda.php?profissional_id=<?=$ag['profissional_id']?>" class="btn btn-outline ms-2">Voltar para agenda</a>
    </form>
  </div>
</div>
</div>
</div>
</body>
</html>
