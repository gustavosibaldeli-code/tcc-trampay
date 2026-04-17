<?php
// pagamento.php — Checkout Trampay (PIX + Cartão) com polling e Modo Demo (?demo=1)
session_start();

$DEMO = isset($_GET['demo']) && $_GET['demo'] == '1';

if (file_exists(__DIR__.'/conexao.php')) require_once __DIR__.'/conexao.php';
require_once __DIR__.'/config_pagamentos.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ===== Cliente logado =====
$user = $_SESSION['user'] ?? null;
$clienteId = ($user && strtolower((string)($user['tipo'] ?? '')) === 'cliente') ? (int)($user['id_cliente'] ?? 0) : 0;
if ($clienteId <= 0) { header('Location: login_cliente.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); exit; }

// ===== Entrada =====
$agenda_id = isset($_GET['agenda_id']) ? (int)$_GET['agenda_id'] : 0;
if ($agenda_id <= 0) { http_response_code(400); exit('Agenda inválida.'); }

// ===== Dados do agendamento =====
$db = db();
$stmt = $db->prepare("
  SELECT a.id, a.profissional_id, a.cliente_id, a.valor_cobrado, a.servico_id,
         COALESCE(p.nome, 'Profissional') AS profissional_nome
  FROM agenda_profissional a
  LEFT JOIN profissional p ON p.id_profissional = a.profissional_id
  WHERE a.id = ? LIMIT 1
");
$stmt->bind_param('i', $agenda_id);
$stmt->execute();
$agenda = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$agenda) { http_response_code(404); exit('Agendamento não encontrado.'); }

// ===== Busca último pagamento dessa agenda =====
$stmt = $db->prepare("SELECT * FROM pagamentos WHERE agenda_id = ? ORDER BY criado_em DESC LIMIT 1");
$stmt->bind_param('i', $agenda_id);
$stmt->execute();
$pm = $stmt->get_result()->fetch_assoc();
$stmt->close();

$err              = null;
$pagamento_id     = $pm['id']   ?? 0;
$status_local     = $pm['status'] ?? 'pendente';
$mp_payment_id    = $pm['txid'] ?? null;
$qr_code_text     = $pm['brcode'] ?? null;
$qr_code_base64   = null; // vamos usar texto (copia/cola). Se quiser, dá pra salvar a imagem também.

$precisa_criar = false;
if (!$pm) {
  $precisa_criar = true;
} else {
  // se não temos txid/brcode, tentamos criar de novo
  if (empty($pm['txid']) || empty($pm['brcode'])) $precisa_criar = true;
  // se já aprovado, não cria
  if ($status_local === 'aprovado') $precisa_criar = false;
}

if ($precisa_criar) {
  $valor = isset($agenda['valor_cobrado']) ? (float)$agenda['valor_cobrado'] : 0.0;
  try {
    $resp = criar_pagamento_pix_mp(
      $agenda_id,
      (int)$agenda['profissional_id'],
      (int)$agenda['cliente_id'],
      $valor,
      "Pagamento Trampay — agenda #{$agenda_id}"
    );
    $pagamento_id   = $resp['pagamento_id'];
    $mp_payment_id  = $resp['txid'] ?? null;
    $qr_code_text   = $resp['qr_code_text'] ?? null;
    $qr_code_base64 = $resp['qr_code_base64'] ?? null;
    $status_local   = 'pendente';
  } catch (Exception $e) {
    $err = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Pagamento • Trampay</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --ink:#0b1720; --muted:#6b7280; --line:#e5ebf3; --brand:#0a66c2; --ok-bg:#d1e7dd; --ok:#0f5132;
    }
    body{background:#f5f7fb;color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial;}
    .cardx{border-radius:16px;border:1px solid var(--line);box-shadow:0 10px 30px rgba(7,20,46,.07);overflow:hidden;background:#fff;}
    .hero{display:flex;justify-content:space-between;align-items:center;padding:22px 26px;background:linear-gradient(180deg,#eef5ff 0,#ffffff 100%);}
    .brand{display:flex;align-items:center;gap:10px}
    .brand img{height:68px}
    .subtitle{color:var(--muted)}
    .amount{font-weight:800;font-size:20px}
    .section{padding:24px 26px}
    .panel{background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px}
    .copyrow{display:flex;gap:8px;align-items:center}
    .okbadge{color:var(--ok);background:var(--ok-bg);border-radius:10px;padding:6px 10px;display:inline-flex;gap:8px;align-items:center}
    .btn-brand{background:var(--brand);color:#fff;border:none;border-radius:10px;padding:10px 16px}
    .btn-outline{border:1px solid var(--line);background:#fff;color:var(--brand);border-radius:10px;padding:10px 14px}
  
  /* ++ barra azul fixa dentro do card */
.topbar {
  background: #00185c;            /* azul banco */
  color: #fff;
  border-radius: 14px;             /* mesma curva do card */
  padding: 14px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: 0 6px 20px rgba(2,30,80,.18) inset;
}
.topbar .left {
  display: flex; align-items: center; gap: 12px;
}
.topbar .logo {
  height: 44px; width: auto; display: block;
}
.topbar .title {
  line-height: 1.15;
}
.topbar .title small {
  display: block; opacity: .9; font-weight: 500;
}
.topbar .amount {
  text-align: right;
}
.topbar .amount .label {
  font-size: 12px; opacity: .9;
}
.topbar .amount .value {
  font-weight: 800; font-size: 20px;
}
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
        <small>Pagamento — agendamento #<?=htmlspecialchars($agenda_id)?></small>
        <div style="font-weight:600"><?=htmlspecialchars($agenda['profissional_nome'] ?? 'Profissional')?></div>
      </div>
    </div>
    <div class="amount">
      <div class="label">Total</div>
      <div class="value">R$ <?=number_format((float)($agenda['valor_cobrado'] ?? 0),2,',','.')?></div>
    </div>
  </div>
</div>


      <div class="section">
        <?php if($err): ?>
          <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
        <?php endif; ?>

        <div class="row g-4">
          <div class="col-md-6">
            <h5 class="mb-2">Pagamento via PIX</h5>
            <p class="subtitle mb-3">Leia o QR no app do banco ou copie o código de PIX abaixo.</p>

            <div class="panel mb-3 text-center">
              <?php if($qr_code_base64): ?>
                <img src="data:image/png;base64,<?=htmlspecialchars($qr_code_base64)?>" alt="QR Code" style="max-width:260px;width:100%;height:auto;border-radius:8px;box-shadow:0 4px 12px rgba(2,6,23,.06)">
              <?php else: ?>
                <?php if(!empty($qr_code_text)): ?>
                  <div class="subtitle">QR dinâmico sem imagem — use o código de cópia e cola.</div>
                <?php else: ?>
                  <div class="subtitle">Ainda não foi possível gerar o QR. Clique em “Atualizar”.</div>
                <?php endif; ?>
              <?php endif; ?>

              <div class="text-start mt-3">
                <label class="form-label small">Código PIX (copiar/colar)</label>
                <div class="copyrow">
                  <input id="pixcode" class="form-control form-control-sm" readonly value="<?=htmlspecialchars($qr_code_text ?? '')?>">
                  <button class="btn btn-outline-secondary btn-sm" id="copyBtn" type="button">Copiar</button>
                </div>
                <small class="text-muted">Cole este código no seu app bancário, caso não use QR.</small>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3 w-100">
                <div>
                  <small class="subtitle">Status do pagamento:</small><br>
                  <span id="pmStatus" class="subtitle">
                    <?= $status_local === 'aprovado' ? '<span class="okbadge">✔ Pagamento recebido</span>' : 'Aguardando pagamento' ?>
                  </span>
                </div>
                <div>
                  <button id="btnRefresh" class="btn btn-outline btn-sm" type="button">Atualizar</button>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2">
              <a class="btn btn-brand" href="pagamento_cartao.php?agenda_id=<?=urlencode($agenda_id)?>">Pagar com cartão</a>
              <a class="btn btn-outline" href="agenda.php?profissional_id=<?=urlencode((int)$agenda['profissional_id'])?>">Voltar para agenda</a>
              <?php if ($DEMO && $pagamento_id): ?>
                <a class="btn btn-outline" href="pagamento_confirmar.php?id=<?=$pagamento_id?>&demo=1">Marcar como pago (demo)</a>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-md-6">
            <h5 class="mb-2">Instruções rápidas</h5>
            <ol class="subtitle">
              <li>No app do banco: PIX → Ler QR ou Colar o código.</li>
              <li>Confirme a transferência. O Mercado Pago processa em instantes.</li>
              <li>Esta página atualiza automaticamente; quando aprovado, aparece o visto verde.</li>
            </ol>

            <div class="mt-4">
              <h6>Identificadores</h6>
              <dl class="mb-0">
                <dt>Pagamento local</dt><dd>#<?=htmlspecialchars($pagamento_id ?: 0)?></dd>
                <dt>MP payment id</dt><dd><?=htmlspecialchars($mp_payment_id ?? '')?></dd>
              </dl>
            </div>
          </div>
        </div>
      </div> <!-- /section -->
    </div> <!-- /card -->
  </div>
</div>

<script>
const pagamentoLocalId = <?=json_encode((int)$pagamento_id)?>;

document.getElementById('copyBtn').addEventListener('click', async () => {
  const v = document.getElementById('pixcode').value || '';
  if (!v) return alert('Nenhum código para copiar.');
  try { await navigator.clipboard.writeText(v); alert('Código PIX copiado.'); }
  catch(e){ prompt('Copie o código abaixo:', v); }
});

async function checarStatus() {
  if (!pagamentoLocalId) return;
  try {
    const r = await fetch('pagamento_poll.php?id=' + pagamentoLocalId, { cache:'no-store' });
    const j = await r.json();
    const el = document.getElementById('pmStatus');
    if (!j || !j.status) { el.innerText = 'Erro ao checar status'; return; }
    if (j.status === 'aprovado') {
      el.innerHTML = '<span class="okbadge">✔ Pagamento recebido</span>';
    } else if (j.status === 'pendente') {
      el.innerText = 'Aguardando pagamento';
    } else if (j.status === 'falhou' || j.status === 'cancelado') {
      el.innerText = 'Falhou / Cancelado';
    } else {
      el.innerText = j.status;
    }
  } catch(err) { console.error(err); }
}

document.getElementById('btnRefresh').addEventListener('click', checarStatus);

// Poll automático ~2min
let tries = 0, maxTries = 20;
const poll = setInterval(() => {
  if (tries++ > maxTries) return clearInterval(poll);
  checarStatus();
}, 6000);
checarStatus();
</script>
</body>
</html>
