<?php
// pagamento_confirmar.php — marca como pago localmente (DEMO)
if (!isset($_GET['demo']) || $_GET['demo'] != '1') { http_response_code(403); exit('Acesso negado'); }

if (file_exists(__DIR__.'/conexao.php')) require_once __DIR__.'/conexao.php';
require_once __DIR__.'/config_pagamentos.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { exit('ID inválido'); }

$db = db();
$u = $db->prepare("UPDATE pagamentos SET status='aprovado', atualizado_em = NOW() WHERE id = ?");
$u->bind_param('i', $id);
$u->execute();
$u->close();

header('Location: pagamento.php?agenda_id='.(int)($_GET['agenda_id'] ?? 0).'&demo=1');
exit;
