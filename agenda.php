<?php
// agenda.php — Trampay • agendamento (calendário + reagendar/cancelar) com PRG e ping em tempo real
// GET: profissional_id (obrig.), servico_id (opcional)
session_start();
require_once 'conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ===== Sessão: apenas CLIENTE pode agendar =====
$user = $_SESSION['user'] ?? null;
$clienteId = ($user && strtolower((string)($user['tipo'] ?? '')) === 'cliente')
  ? (int)($user['id_cliente'] ?? 0) : 0;

if ($clienteId <= 0) {
  header('Location: login_cliente.php?redirect='.urlencode($_SERVER['REQUEST_URI']));
  exit;
}

// Mensagem pós-pagamento (links WhatsApp)
if (!empty($_SESSION['whats_links'])) {
  $links = $_SESSION['whats_links'];
  unset($_SESSION['whats_links']);
  echo '<div class="alert alert-success m-3">
          Pagamento confirmado! Você pode compartilhar a confirmação:
          <div class="mt-2 d-flex gap-2">
            <a class="btn btn-sm btn-success" target="_blank" href="'.htmlspecialchars($links['cliente']).'">Enviar confirmação ao Cliente (WhatsApp)</a>
            <a class="btn btn-sm btn-success" target="_blank" href="'.htmlspecialchars($links['prof']).'">Notificar Profissional (WhatsApp)</a>
          </div>
        </div>';
}

// ===== Entrada =====
$profId = isset($_GET['profissional_id']) ? (int)$_GET['profissional_id'] : 0;
$servId = isset($_GET['servico_id']) ? (int)$_GET['servico_id'] : null;
if ($profId <= 0) { http_response_code(400); echo "Profissional inválido."; exit; }

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ===== Flash (PRG) =====
$flash = $_SESSION['flash_agenda'] ?? null;
unset($_SESSION['flash_agenda']);

// ===== Dados do profissional e (opcional) serviço =====
$prof = null; $servico = null;
try {
  $st = $conn->prepare("SELECT id_profissional, nome, email FROM profissional WHERE id_profissional=?");
  $st->bind_param('i', $profId);
  $st->execute();
  $prof = $st->get_result()->fetch_assoc();
  $st->close();
  if(!$prof){ http_response_code(404); echo "Profissional não encontrado."; exit; }

  if ($servId) {
    // Prioriza profissional_servico; tenta 'servico' se existir
    $st = $conn->prepare("SELECT id, titulo, COALESCE(preco_min,0) AS preco
                          FROM profissional_servico
                          WHERE id=? AND id_profissional=? AND (ativo IS NULL OR ativo=1)");
    $st->bind_param('ii', $servId, $profId);
    $st->execute();
    $servico = $st->get_result()->fetch_assoc();
    $st->close();

    if(!$servico){
      if ($stmt2 = $conn->prepare("SELECT id, titulo, COALESCE(preco_min,0) AS preco
                                   FROM servico
                                   WHERE id=? AND id_profissional=? AND (ativo IS NULL OR ativo=1)")) {
        $stmt2->bind_param('ii', $servId, $profId);
        $stmt2->execute();
        $servico = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
      }
    }
  }
} catch(Throwable $e) {}

// ===== Ações (agendar / reagendar / cancelar) — PRG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao      = $_POST['acao'] ?? '';
  $dataStr   = trim($_POST['data'] ?? '');
  $horaStr   = trim($_POST['hora'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');
  $dataOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataStr);
  $horaOk = preg_match('/^\d{2}:\d{2}$/', $horaStr);
  $hoje    = new DateTimeImmutable('today');
  $dataSel = $dataOk ? DateTimeImmutable::createFromFormat('Y-m-d', $dataStr) : false;

  $msg = ''; $ok = false;

  try {
    if ($acao === 'agendar') {
      if(!$dataOk || !$horaOk || !$dataSel) throw new Exception('Selecione data e hora.');
      if ($dataSel < $hoje) throw new Exception('Não é possível agendar no passado.');
      $horaFull = $horaStr . ':00';

      // conflito
      $st = $conn->prepare("SELECT COUNT(*) c FROM agenda_profissional
                            WHERE profissional_id=? AND data_servico=? AND hora_servico=? AND status='agendado'");
      $st->bind_param('iss', $profId, $dataStr, $horaFull);
      $st->execute();
      $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
      $st->close();
      if ($c > 0) throw new Exception('Esse horário foi ocupado. Escolha outro.');

      // criar
      $valorCobrado = isset($servico['preco']) ? (float)$servico['preco'] : null;
      $servIdToSave = $servico['id'] ?? null;

      $st = $conn->prepare("INSERT INTO agenda_profissional
        (profissional_id, cliente_id, servico_id, valor_cobrado, data_servico, hora_servico, descricao_servico, status)
        VALUES (?,?,?,?,?,?,?,'agendado')");
      $st->bind_param('iiidsss', $profId, $clienteId, $servIdToSave, $valorCobrado, $dataStr, $horaFull, $descricao);
      $st->execute();
      $st->close();
      $msg = 'Agendamento criado com sucesso!'; $ok = true;

    } elseif ($acao === 'reagendar') {
      $agId = (int)($_POST['agenda_id'] ?? 0);
      if($agId<=0 || !$dataOk || !$horaOk || !$dataSel) throw new Exception('Parâmetros inválidos.');
      if ($dataSel < $hoje) throw new Exception('Não é possível reagendar para o passado.');
      $horaFull = $horaStr . ':00';

      // Confere dono + ativo
      $st = $conn->prepare("SELECT id FROM agenda_profissional
                            WHERE id=? AND cliente_id=? AND profissional_id=? AND status='agendado' LIMIT 1");
      $st->bind_param('iii', $agId, $clienteId, $profId);
      $st->execute();
      $okRow = $st->get_result()->fetch_assoc();
      $st->close();
      if(!$okRow) throw new Exception('Agendamento não encontrado ou já não está ativo.');

      // Conflito no novo slot
      $st = $conn->prepare("SELECT COUNT(*) c FROM agenda_profissional
                            WHERE profissional_id=? AND data_servico=? AND hora_servico=? AND status='agendado' AND id<>?");
      $st->bind_param('issi', $profId, $dataStr, $horaFull, $agId);
      $st->execute();
      $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
      $st->close();
      if ($c > 0) throw new Exception('Esse horário foi ocupado. Escolha outro.');

      // Atualiza (lado do cliente não grava justificativa)
      $st = $conn->prepare("UPDATE agenda_profissional
                            SET data_servico=?, hora_servico=?, descricao_servico=?
                            WHERE id=?");
      $st->bind_param('sssi', $dataStr, $horaFull, $descricao, $agId);
      $st->execute();
      $st->close();
      $msg = 'Agendamento reagendado com sucesso!'; $ok = true;

    } elseif ($acao === 'cancelar') {
      $agId = (int)($_POST['agenda_id'] ?? 0);
      if($agId<=0) throw new Exception('Agendamento inválido.');

      $st = $conn->prepare("UPDATE agenda_profissional
                            SET status='cancelado'
                            WHERE id=? AND cliente_id=? AND profissional_id=? AND status='agendado'");
      $st->bind_param('iii', $agId, $clienteId, $profId);
      $st->execute();
      if($st->affected_rows<=0) throw new Exception('Não foi possível cancelar (já cancelado ou não pertence a você).');
      $st->close();
      $msg = 'Agendamento cancelado.'; $ok = true;
    }

  } catch(Throwable $e) {
    $msg = $e->getMessage(); $ok = false;
  }

  // PRG: guarda flash e redireciona para GET
  $_SESSION['flash_agenda'] = ['msg'=>$msg, 'ok'=>$ok];
  $qs = $_GET; // preserva servico_id etc
  $qs['profissional_id'] = $profId;
  if ($servId) $qs['servico_id'] = $servId;
  header('Location: '.$_SERVER['PHP_SELF'].'?'.http_build_query($qs));
  exit;
}

// ===== Meu agendamento mais recente (qualquer status) =====
$meuAg = null;
try{
  $st = $conn->prepare("SELECT id, data_servico, hora_servico, descricao_servico, status,
                               justificativa, justificado_por, atualizado_em
                        FROM agenda_profissional
                        WHERE profissional_id=? AND cliente_id=?
                        ORDER BY id DESC LIMIT 1");
  $st->bind_param('ii', $profId, $clienteId);
  $st->execute();
  $meuAg = $st->get_result()->fetch_assoc() ?: null;
  $st->close();
}catch(Throwable $e){}

// ===== Pagamento da agenda mais recente =====
$pagamento = null;
$isPago = false; $metodoPago = null; $pagamentoId = null;
if ($meuAg) {
  $st = $conn->prepare("SELECT id, status, metodo 
                        FROM pagamentos 
                        WHERE agenda_id=? 
                        ORDER BY id DESC LIMIT 1");
  $st->bind_param('i', $meuAg['id']);
  $st->execute();
  $pagamento = $st->get_result()->fetch_assoc() ?: null;
  $st->close();

  if ($pagamento && strtolower($pagamento['status']) === 'aprovado') {
    $isPago = true;
    $metodoPago = $pagamento['metodo']; // 'pix', 'cartao', etc.
    $pagamentoId = (int)$pagamento['id'];
  }
}

// ===== Slots ocupados (para gerar disponibilidade 14 dias a partir de hoje) =====
$ocupados = [];
try{
  $st = $conn->prepare("SELECT data_servico, hora_servico FROM agenda_profissional
                        WHERE profissional_id=? AND status='agendado'");
  $st->bind_param('i', $profId);
  $st->execute();
  $rs = $st->get_result();
  while($r=$rs->fetch_assoc()){
    $ocupados[$r['data_servico']][$r['hora_servico']] = true;
  }
  $st->close();
}catch(Throwable $e){}

// ===== Disponibilidade (14 dias, 09–17h, hora cheia) =====
$hoje = new DateTimeImmutable('today');
$dispon = [];
for($d=0; $d<14; $d++){
  $dia = $hoje->modify("+$d day");
  $ymd = $dia->format('Y-m-d');
  $slots = [];
  for($h=9; $h<=17; $h++){
    $hora = sprintf('%02d:00:00', $h);
    $slots[] = [
      'hora'  => substr($hora,0,5),
      'livre' => empty($ocupados[$ymd][$hora])
    ];
  }
  $dispon[$ymd] = $slots;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Agendamento • Trampay</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.cdnfonts.com/css/satoshi" rel="stylesheet">
  <style>
    :root{ --tp-blue-1:#000a25fd; --tp-blue-2:#000c2ccc; --ink:#02001b; --ink2:#00185c;
           --muted:#667085; --bg:#f7f8fc; --card:#fff; --bd:#e9ecf3; }
    body{ background:var(--bg); color:var(--ink); font-family:"Satoshi",system-ui; }
    .hero{ background:linear-gradient(92deg,var(--tp-blue-1),var(--tp-blue-2)); color:#fff;
           border-radius:20px; padding:26px 22px; box-shadow:0 20px 60px rgba(0,0,0,.18);
           margin:26px auto 12px; max-width:1100px;}
    .hero .nome{ font-family:"Bebas Neue"; letter-spacing:1px; font-size:2.3rem; margin:0 }
    .chip{ display:inline-flex; align-items:center; gap:.4rem; padding:.38rem .6rem; border-radius:999px;
           background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.24); color:#fff; }
    .wrap{ max-width:1100px; margin:0 auto 44px; padding:0 14px; }
    .cardish{ background:var(--card); border:1px solid var(--bd); border-radius:16px;
              box-shadow:0 10px 32px rgba(2,0,27,.04); padding:18px; }

    /* Título do mês/ano no calendário em branco */
/* Calendário menor e mais denso */
.tpcal{ background: linear-gradient(135deg,var(--tp-blue-1),var(--tp-blue-2)); color:#fff;
        border-radius:16px; padding:10px 12px 10px; }
.tpcal .tpcal-header{ display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.tpcal .tpcal-header .title{ font-family:'Bebas Neue','Oswald',sans-serif; font-size:24px; letter-spacing:1px; }
.tpcal .tpcal-header .nav{ background:rgba(255,255,255,.12); border:none; color:#fff;
                           width:32px; height:32px; border-radius:10px; cursor:pointer; }

.tpcal .tpcal-grid{ display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
.tpcal .tpcal-grid.head{ opacity:.9; font-weight:600; margin-bottom:6px; }

.tpcal .day{ position:relative; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08);
             border-radius:12px; padding:8px; height:78px; overflow:hidden; cursor:pointer; }
.tpcal .day .num{ font-weight:700; font-size:13px; }
.tpcal .day .dot{ position:absolute; left:50%; bottom:6px; transform:translateX(-50%);
                  width:7px; height:7px; border-radius:999px; background:#2d9cdb; box-shadow:0 0 0 2px rgba(255,255,255,.25); }
.tpcal .day .badges{ position:absolute; right:6px; bottom:4px; display:flex; gap:4px; }
.tpcal .badge{ font-size:9px; padding:1px 5px; border-radius:999px; background:rgba(255,255,255,.14); }

.slots{ display:flex; flex-wrap:wrap; gap:6px; }
.slot{ min-width:72px; padding:.45rem .6rem; border-radius:10px; border:1px solid var(--bd); text-align:center; }

    .btn-primary{ background:linear-gradient(92deg,var(--tp-blue-1),var(--tp-blue-2)); border:0; }
    .muted{ color:var(--muted) }
    .sticky{ position:sticky; top:20px }
    .client-info{ background:#fff7e6;border:1px solid #ffe3b3;border-radius:12px;padding:12px;margin-bottom:12px }
  </style>
</head>
<body>

  <!-- HERO -->
  <section class="hero">
    <div class="d-flex flex-wrap align-items-center gap-3">
      <div>
        <h1 class="nome">Agendar com <?= esc($prof['nome']) ?></h1>
        <div class="d-flex gap-2 flex-wrap">
          <span class="chip"><i class="bi bi-person-badge"></i> Profissional #<?= (int)$prof['id_profissional'] ?></span>
          <a class="chip text-decoration-none"
   href="perfil_publico.php?id=<?= (int)$prof['id_profissional'] ?>">
   <i class="bi bi-arrow-left"></i> Voltar ao perfil
</a>
          <?php if($servico): ?>
            <span class="chip"><i class="bi bi-tools"></i> <?= esc($servico['titulo']) ?></span>
            <span class="chip"><i class="bi bi-cash-coin"></i> R$ <?= number_format((float)$servico['preco'],2,',','.') ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <main class="wrap" id="containerAgendamento">
    <?php if($flash): ?>
      <div class="alert <?= $flash['ok']?'alert-success':'alert-warning' ?> mb-3"><?= esc($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if ($meuAg && !empty($meuAg['justificativa'])): ?>
      <div id="boxJustificativaTop" class="alert alert-warning mt-2" role="alert" style="white-space: pre-wrap;">
        <strong>Mensagem do <?= esc($meuAg['justificado_por'] ?: 'profissional') ?>:</strong>
        <?= esc($meuAg['justificativa']) ?>
        <?php if (!empty($meuAg['atualizado_em'])): ?>
          <div class="small text-muted mt-1">Atualizado em: <?= esc($meuAg['atualizado_em']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <!-- Calendário & Horários -->
      <div class="col-lg-8">
        <div class="cardish">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="title"><i class="bi bi-calendar2-week"></i> Escolha a data</div>
            <div class="legend d-none d-md-flex gap-2">
              <span class="tag"><span class="slot livre" style="min-width:44px;padding:.2rem .5rem">9:00</span> Livre</span>
              <span class="tag"><span class="slot ocupado" style="min-width:44px;padding:.2rem .5rem">9:00</span> Ocupado</span>
              <span class="tag"><span class="slot sel" style="min-width:44px;padding:.2rem .5rem">9:00</span> Selecionado</span>
            </div>
          </div>

          <!-- Calendário Mensal Trampay -->
          <div id="tpCalendar"
               data-prof-id="<?= (int)$profId ?>"
               data-cli-id="<?= (int)$clienteId ?>"
               class="tpcal shadow-sm">

            <div class="tpcal-header">
              <button type="button" class="nav prev" aria-label="Mês anterior">&lsaquo;</button>
              <div class="title">
                <span id="tpcalMonth">MÊS</span>
                <span id="tpcalYear">ANO</span>
              </div>
              <button type="button" class="nav next" aria-label="Próximo mês">&rsaquo;</button>
            </div>

            <div class="tpcal-grid head">
              <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
            </div>
            <div class="tpcal-grid body" id="tpcalBody"></div>

            <div class="tpcal-legend">
              <span class="badge" style="background:rgba(255,255,255,.18)">●</span>
              <span>bolinha azul: dia com justificativa do profissional (reagendo/cancelamento)</span>
            </div>
          </div>

          <!-- Horários do dia selecionado + INFO DO CLIENTE -->
          <div id="slotsArea" class="mt-3">
            <div class="muted">Selecione um dia acima para ver os horários e o status do seu agendamento.</div>
          </div>
        </div>
      </div>

      <!-- Resumo & Ações -->
      <div class="col-lg-4">
        <div class="cardish sticky">
          <div class="title"><i class="bi bi-clipboard2-check"></i> Resumo</div>
          <div class="small muted mb-2">Escolha data e hora para habilitar a confirmação.</div>

          <form method="post" id="form-agenda" class="d-grid gap-2" autocomplete="off">
            <input type="hidden" name="acao" value="agendar" id="acao-input">
            <input type="hidden" name="data" id="data-input">
            <input type="hidden" name="hora" id="hora-input">
            <?php if($servId): ?><input type="hidden" name="servico_id" value="<?= (int)$servId ?>"><?php endif; ?>

            <div class="mb-2">
              <label class="form-label">Descrição (opcional)</label>
              <textarea name="descricao" class="form-control" rows="3" placeholder="Ex.: instalação, visita técnica, etc."></textarea>
            </div>

            <div class="border rounded p-2 mb-1 bg-light">
              <div class="d-flex justify-content-between"><span class="muted">Data</span> <strong id="res-data">—</strong></div>
              <div class="d-flex justify-content-between"><span class="muted">Hora</span> <strong id="res-hora">—</strong></div>
            </div>

            <button class="btn btn-primary" type="submit" id="btn-confirm" disabled>
              <i class="bi bi-calendar-check"></i> Confirmar agendamento
            </button>
            <button class="btn btn-outline-secondary" type="button" onclick="limparSelecao()">
              <i class="bi bi-eraser"></i> Limpar seleção
            </button>
          </form>

<?php if($meuAg): ?>
  <?php
    $dataFmt = date('d/m/Y', strtotime($meuAg['data_servico']));
    $horaFmt = substr($meuAg['hora_servico'],0,5);
    $isProfJust = !empty($meuAg['justificativa']) && strtolower((string)$meuAg['justificado_por'])==='profissional';
    $isReagendadoPorProf = ($meuAg['status']==='agendado') && $isProfJust;
  ?>
  <hr>
  <div class="title" style="font-size:1.2rem"><i class="bi bi-clock-history"></i> Seu agendamento</div>

  <div class="small mb-2">
    <?php if ($isReagendadoPorProf): ?>
      <div id="agDataHora">
        <i class="bi bi-calendar-event"></i>
        <strong>Reagendado para:</strong>
        <strong><?= esc($dataFmt) ?></strong>
        &nbsp; <i class="bi bi-alarm"></i>
        <strong><?= esc($horaFmt) ?></strong>
      </div>
    <?php else: ?>
      <div id="agDataHora">
        <i class="bi bi-calendar-event"></i>
        Data: <strong><?= esc($dataFmt) ?></strong>
        &nbsp; <i class="bi bi-alarm"></i>
        Hora: <strong><?= esc($horaFmt) ?></strong>
      </div>
    <?php endif; ?>

    <div>Status: <strong id="agStatus"><?= esc($meuAg['status']) ?></strong></div>

    <?php if ($isPago): ?>
      <div class="mt-1">
        <span class="badge text-bg-success">
          <i class="bi bi-check-circle-fill"></i>
          Pago <?= $metodoPago==='pix' ? 'via Pix' : 'no cartão' ?>
        </span>
      </div>
    <?php endif; ?>

    <?php if(!empty($meuAg['descricao_servico'])): ?>
      <div class="text-muted mt-1">“<?= esc($meuAg['descricao_servico']) ?>”</div>
    <?php endif; ?>

    <?php if ($isProfJust): ?>
      <div id="boxJustificativaRight" class="alert alert-warning mt-2" role="alert" style="white-space: pre-wrap;">
        <strong>Mensagem do profissional:</strong>
        <?= esc($meuAg['justificativa']) ?>
        <?php if (!empty($meuAg['atualizado_em'])): ?>
          <div class="small text-muted mt-1">Atualizado em: <?= esc($meuAg['atualizado_em']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if($meuAg['status']==='agendado'): ?>
    <form method="post" class="d-grid gap-2" onsubmit="return prepararReagendar();">
      <input type="hidden" name="acao" value="reagendar">
      <input type="hidden" name="agenda_id" value="<?= (int)$meuAg['id'] ?>">
      <input type="hidden" name="data" id="data-reag">
      <input type="hidden" name="hora" id="hora-reag">
      <input type="hidden" name="descricao" id="desc-reag">
      <button class="btn btn-outline-primary" type="submit">
        <i class="bi bi-arrow-repeat"></i> Reagendar p/ seleção
      </button>
    </form>

    <form method="post" class="mt-2" onsubmit="return confirm('Cancelar seu agendamento?');">
      <input type="hidden" name="acao" value="cancelar">
      <input type="hidden" name="agenda_id" value="<?= (int)$meuAg['id'] ?>">
      <button class="btn btn-outline-danger w-100" type="submit">
        <i class="bi bi-x-circle"></i> Cancelar agendamento
      </button>
    </form>

    <?php if (!$isPago): ?>
      <!-- Botão PAGAR (quando ainda não pago) -->
      <form class="mt-2" action="pagamento.php" method="get">
        <input type="hidden" name="agenda_id" value="<?= (int)$meuAg['id'] ?>">
        <button class="btn btn-success w-100" type="submit">
          <i class="bi bi-qr-code"></i> Pagar
        </button>
      </form>
    <?php else: ?>
      <!-- Mensagem quando JÁ FOI PAGO -->
      <div class="alert alert-success mt-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        Pagamento recebido <?= $metodoPago==='pix' ? 'via Pix' : 'no cartão' ?>. Seu agendamento está confirmado.
      </div>
    <?php endif; ?>

  <?php endif; ?>
<?php endif; ?>

        </div>
      </div>
    </div>
  </main>

  <script>
  // ===== Dados do PHP =====
  const DISPON = <?= json_encode($dispon, JSON_UNESCAPED_UNICODE) ?>;

  // ===== Referências de resumo =====
  const dataIn  = document.getElementById('data-input');
  const horaIn  = document.getElementById('hora-input');
  const resData = document.getElementById('res-data');
  const resHora = document.getElementById('res-hora');
  const btnConfirm = document.getElementById('btn-confirm');
  const formAgenda = document.getElementById('form-agenda');
  const area       = document.getElementById('slotsArea');

  // cache da página atual do calendário (preenchido em render())
  let CAL_MONTH_CACHE = {};

  function checkReady(){ btnConfirm.disabled = !(dataIn.value && horaIn.value); }
  function limparSelecao(){
    document.querySelectorAll('#tpcalBody .day.selected').forEach(el=>el.classList.remove('selected'));
    area.innerHTML = '<div class="muted">Selecione um dia acima para ver os horários e o status do seu agendamento.</div>';
    dataIn.value=''; horaIn.value='';
    resData.textContent='—'; resHora.textContent='—';
    checkReady();
  }
  window.limparSelecao = limparSelecao;

  function renderClientInfoForDay(ymd){
    const info = CAL_MONTH_CACHE[ymd]?.client;
    const has = !!info;
    if(!has) return '';
    const status = info.status || '-';
    const hora   = info.hora   || '--:--';
    const justificativa = info.justificativa || '';
    const atualizado = info.atualizado_em ? `<div class="small text-muted mt-1">Atualizado em: ${info.atualizado_em}</div>` : '';
    const badge = status === 'cancelado' ? 'bg-danger' : (status === 'agendado' ? 'bg-primary' : 'bg-success');
    const label = status.charAt(0).toUpperCase() + status.slice(1);
    return `
      <div class="client-info">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong>Status do seu agendamento neste dia</strong>
          <span class="badge ${badge}">${label}</span>
        </div>
        <div><i class="bi bi-alarm"></i> Hora: <strong>${hora}</strong></div>
        ${justificativa ? `<div class="mt-1"><i class="bi bi-megaphone"></i> Justificativa do profissional:<br><span style="white-space:pre-wrap">${justificativa}</span></div>` : ''}
        ${atualizado}
      </div>
    `;
  }

  function renderSlots(dia){
    const clientBlock = renderClientInfoForDay(dia);

    const slots = (DISPON[dia] || []);
    if(!slots.length){
      area.innerHTML = clientBlock + '<div class="muted">Sem horários disponíveis para este dia.</div>';
      return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'slots';
    slots.forEach(s=>{
      const b = document.createElement('button');
      b.type='button';
      b.className='slot ' + (s.livre? 'livre':'ocupado');
      b.textContent = s.hora;
      if(s.livre){
        b.addEventListener('click', ()=>{
          wrap.querySelectorAll('.slot.livre').forEach(x=>x.classList.remove('sel'));
          horaIn.value = s.hora;
          resHora.textContent = s.hora;
          b.classList.add('sel');
          checkReady();
        });
      }else{
        b.disabled = true;
      }
      wrap.appendChild(b);
    });

    area.innerHTML = clientBlock;
    area.appendChild(wrap);
  }

  // ========= Calendário mensal (bolinha azul via api/agenda_calendar.php) =========
  (function(){
    const el = document.getElementById('tpCalendar');
    if(!el) return;

    const profId = parseInt(el.dataset.profId || '0', 10);
    const cliId  = parseInt(el.dataset.cliId  || '0', 10);

    const monthNames = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                        'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    let view = new Date();

    const $month = document.getElementById('tpcalMonth');
    const $year  = document.getElementById('tpcalYear');
    const $body  = document.getElementById('tpcalBody');

    function startOfCalendar(date){
      const d = new Date(date.getFullYear(), date.getMonth(), 1);
      const day = d.getDay(); // 0=Dom
      d.setDate(d.getDate() - day);
      return d;
    }
    function fmtDate(d){
      const y = d.getFullYear();
      const m = (d.getMonth()+1).toString().padStart(2,'0');
      const da = d.getDate().toString().padStart(2,'0');
      return `${y}-${m}-${da}`;
    }
    async function fetchMonthData(y, m){
      const url = `api/agenda_calendar.php?profissional_id=${profId}&cliente_id=${cliId}&year=${y}&month=${m}`;
      const r = await fetch(url, {cache:'no-store'});
      if(!r.ok) return {};
      const j = await r.json();
      return (j.ok && j.dates) ? j.dates : {};
    }
    function selectDay(cell, d){
      $body.querySelectorAll('.day.selected').forEach(el=>el.classList.remove('selected'));
      cell.classList.add('selected');
      const ymd = fmtDate(d);
      dataIn.value = ymd;
      resData.textContent = ymd.split('-').reverse().join('/');
      horaIn.value=''; resHora.textContent='—';
      checkReady();
      renderSlots(ymd);
    }

    async function render(){
      const y = view.getFullYear();
      const m = view.getMonth();
      $month.textContent = monthNames[m].toUpperCase();
      $year.textContent  = y;

      const apiDates = await fetchMonthData(y, m+1);
      CAL_MONTH_CACHE = apiDates; // <- para renderSlots(dia) conseguir mostrar status/justificativa
      $body.innerHTML = '';
      const start = startOfCalendar(view);

      for(let i=0;i<42;i++){
        const d = new Date(start.getFullYear(), start.getMonth(), start.getDate()+i);
        const inMonth = d.getMonth() === m;
        const cell = document.createElement('div');
        cell.className = 'day' + (inMonth ? '' : ' muted');

        const num = document.createElement('div');
        num.className = 'num';
        num.textContent = d.getDate();
        cell.appendChild(num);

        const key = fmtDate(d);
        const info = apiDates[key];
        if(info && info.reagendado){
          const dot = document.createElement('span');
          dot.className = 'dot';
          cell.appendChild(dot);
        }
        if(info && info.status){
          const badges = document.createElement('div');
          badges.className = 'badges';
          const s = info.status;
          [['agendado','Ag'],['cancelado','Cx'],['concluido','Ok']].forEach(([k,label])=>{
            if(s[k]>0){
              const b = document.createElement('span');
              b.className = 'badge';
              b.textContent = label + ':' + s[k];
              badges.appendChild(b);
            }
          });
          if(badges.children.length) cell.appendChild(badges);
        }
        if(inMonth){
          cell.addEventListener('click', ()=> selectDay(cell, d));
        }
        $body.appendChild(cell);
      }
    }

    el.querySelector('.nav.prev').addEventListener('click', ()=>{ view = new Date(view.getFullYear(), view.getMonth()-1, 1); render(); });
    el.querySelector('.nav.next').addEventListener('click', ()=>{ view = new Date(view.getFullYear(), view.getMonth()+1, 1); render(); });

    render();
    setInterval(render, 10000); // atualiza bolinhas/contagens
  })();

  // Reagendar (lado cliente usa a seleção atual)
 function prepararReagendar(){
  const d = (document.getElementById('data-input')?.value || '');
  const h = (document.getElementById('hora-input')?.value || '');
  if(!d || !h){ alert('Selecione nova data e hora na coluna esquerda.'); return false; }

  const formReag = document.querySelector('form input[name="acao"][value="reagendar"]')?.closest('form');
  if(!formReag){ alert('Formulário de reagendamento não encontrado.'); return false; }

  // limpa restos antigos
  formReag.querySelector('#data-reag')?.remove();
  formReag.querySelector('#hora-reag')?.remove();
  formReag.querySelector('#desc-reag')?.remove();

  // injeta os campos com a seleção atual
  const i1 = document.createElement('input'); i1.type='hidden'; i1.name='data'; i1.id='data-reag'; i1.value=d; formReag.appendChild(i1);
  const i2 = document.createElement('input'); i2.type='hidden'; i2.name='hora'; i2.id='hora-reag'; i2.value=h; formReag.appendChild(i2);
  const i3 = document.createElement('input'); i3.type='hidden'; i3.name='descricao'; i3.id='desc-reag';
  i3.value = (document.querySelector('#form-agenda textarea[name="descricao"]')?.value || '');
  formReag.appendChild(i3);

  return confirm('Confirmar reagendamento para ' + d.split('-').reverse().join('/') + ' às ' + h + '?');
}
  // Proteção no submit de agendar
  formAgenda?.addEventListener('submit', (e)=>{
    if(btnConfirm.disabled){
      e.preventDefault();
      alert('Selecione data e hora para agendar.');
    }
  });

  // Ping leve para atualizar status/justificativa em tempo real no bloco da direita/topo
  (function(){
    const profId = <?= (int)$profId ?>;
    const cliId  = <?= (int)$clienteId ?>;

    async function ping(){
      try{
        const r = await fetch(`api/agenda_status.php?profissional_id=${profId}&cliente_id=${cliId}`, {cache:'no-store'});
        if(!r.ok) return;
        const j = await r.json();
        if(!j.ok || !j.ag) return;

        const status = j.ag.status;
        const justificativa = j.ag.justificativa || '';
        const por = (j.ag.justificado_por || '').toLowerCase();
        const isProfJust = justificativa && por === 'profissional';
        const isReagendadoPorProf = (status === 'agendado') && isProfJust;

        const statusEl = document.querySelector('#agStatus');
        const dataEl   = document.querySelector('#agDataHora');

        if(statusEl) statusEl.textContent = status;

        const dataBR = j.ag.data_.split('-').reverse().join('/');
        const hora   = j.ag.hora_;
        if(dataEl){
          if(isReagendadoPorProf){
            dataEl.innerHTML = `<i class="bi bi-calendar-event"></i> <strong>Reagendado para:</strong>
                                <strong>${dataBR}</strong> &nbsp; <i class="bi bi-alarm"></i> <strong>${hora}</strong>`;
          }else{
            dataEl.innerHTML = `<i class="bi bi-calendar-event"></i> Data: <strong>${dataBR}</strong>
                                &nbsp; <i class="bi bi-alarm"></i> Hora: <strong>${hora}</strong>`;
          }
        }

        // (Opcional) aqui poderíamos dar um fetch em um endpoint "pagamento_status.php?agenda_id=..."
        // para atualizar o badge "Pago" em tempo real, se você quiser. Por ora, mantemos estático
        // até o próximo refresh/entrada na tela.

        // Top box
        let topBox = document.querySelector('#boxJustificativaTop');
        if(isProfJust){
          if(!topBox){
            topBox = document.createElement('div');
            topBox.id = 'boxJustificativaTop';
            topBox.className = 'alert alert-warning mt-2';
            topBox.style.whiteSpace = 'pre-wrap';
            document.querySelector('#containerAgendamento')?.prepend(topBox);
          }
          topBox.innerHTML = `<strong>Mensagem do profissional:</strong> ${justificativa}`
            + (j.ag.atualizado_em ? `<div class="small text-muted mt-1">Atualizado em: ${j.ag.atualizado_em}</div>` : '');
        }else if(topBox){
          topBox.remove();
        }
      }catch(e){}
    }

    ping();
    setInterval(ping, 10000);
  })();
  </script>
</body>
</html>
