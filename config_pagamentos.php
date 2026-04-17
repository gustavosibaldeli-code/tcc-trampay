<?php
// config_pagamentos.php — helpers MP + DB robusto + criação de pagamento PIX

if (session_status() === PHP_SESSION_NONE) session_start();

// Conexão/Configs do projeto (opcional)
if (file_exists(__DIR__.'/conexao.php')) require_once __DIR__.'/conexao.php';
if (file_exists(__DIR__.'/config.php'))  require_once __DIR__.'/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// === TOKEN MP (produção) ===
if (!defined('MP_ACCESS_TOKEN')) {
  define('MP_ACCESS_TOKEN', 'APP_USR-8701787539718592-110509-5fa33eb85ffd1fe54136f708af802880-355297523');
}

// Taxa (opcional)
if (!defined('TRAMPAY_FEE_PERCENT')) define('TRAMPAY_FEE_PERCENT', 0.0);

// ========= DB (robusto) =========
function db(): mysqli {
  if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) return $GLOBALS['conn'];
  if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];

  static $cached = null;
  if ($cached instanceof mysqli) return $cached;

  $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
  $user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
  $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
  $name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'trampay');

  $cached = new mysqli($host, $user, $pass, $name);
  if ($cached->connect_error) throw new Exception('Erro DB: ' . $cached->connect_error);
  $cached->set_charset('utf8mb4');
  return $cached;
}

// ========= Mercado Pago =========
function mp_call(string $method, string $path, $body = null, array $headersExtra = []) {
  $url = 'https://api.mercadopago.com' . $path;

  $ch = curl_init($url);
  $headers = [
    'Authorization: Bearer ' . MP_ACCESS_TOKEN,
    'Content-Type: application/json'
  ];
  foreach ($headersExtra as $k => $v) $headers[] = "$k: $v";

  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);

  $resp = curl_exec($ch);
  if ($resp === false) { $err = curl_error($ch); curl_close($ch); throw new Exception("Erro curl MP: $err"); }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $data = json_decode($resp, true);
  if ($code >= 400) {
    $msg = $data['message'] ?? ($data['error'] ?? $resp);
    throw new Exception("MP API erro HTTP $code: " . $msg);
  }
  return $data;
}

function get_cliente_email(int $cliente_id): ?string {
  try {
    $db = db();
    $q = $db->prepare("SELECT email FROM clientes WHERE id_cliente = ? LIMIT 1");
    $q->bind_param('i', $cliente_id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();
    return $r['email'] ?? null;
  } catch (Throwable $e) { return null; }
}

function checar_status_mp($mp_payment_id) {
  return mp_call('GET', "/v1/payments/{$mp_payment_id}");
}

// cria PIX e salva no DB; retorna dados úteis
function criar_pagamento_pix_mp(int $agenda_id, int $profissional_id, int $cliente_id, float $valor, string $descricao = ''): array {
  $db = db();

  // cria registro local pendente
  $ins = $db->prepare("
    INSERT INTO pagamentos (agenda_id, profissional_id, cliente_id, valor, metodo, status, criado_em)
    VALUES (?, ?, ?, ?, 'pix', 'pendente', NOW())
  ");
  $ins->bind_param('iiid', $agenda_id, $profissional_id, $cliente_id, $valor);
  $ins->execute();
  $pagamento_id = $ins->insert_id;
  $ins->close();

  // body MP
  $body = [
    "transaction_amount" => (float)$valor,
    "description"        => $descricao ?: "Pagamento Trampay — agenda #{$agenda_id}",
    "payment_method_id"  => "pix",
    "payer"              => [ "email" => get_cliente_email($cliente_id) ?: "no-reply@trampay.local" ],
    "external_reference" => "trampay_local_{$pagamento_id}"
  ];

  // idempotência correta
  $idemp = 'trampay-'.$pagamento_id.'-'.bin2hex(random_bytes(8));
  $mp = mp_call('POST', '/v1/payments', $body, ['X-Idempotency-Key' => $idemp]);

  $txid           = $mp['id'] ?? null;
  $trx            = $mp['point_of_interaction']['transaction_data'] ?? [];
  $qr_code_text   = $trx['qr_code'] ?? ($trx['brcode'] ?? null); // copia/cola
  $qr_code_base64 = $trx['qr_code_base64'] ?? null;              // imagem

  $up = $db->prepare("UPDATE pagamentos SET txid = ?, brcode = ?, pix_chave = ?, atualizado_em = NOW() WHERE id = ?");
  $pix_chave = $qr_code_text ?: '';
  $up->bind_param('sssi', $txid, $qr_code_text, $pix_chave, $pagamento_id);
  $up->execute();
  $up->close();

  return [
    'payment'          => $mp,
    'pagamento_id'     => $pagamento_id,
    'qr_code_base64'   => $qr_code_base64,
    'qr_code_text'     => $qr_code_text,
    'txid'             => $txid
  ];
}
