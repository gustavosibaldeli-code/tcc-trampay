<?php
// ja_possui_conta.php — LOGIN CLIENTE (Trampay)
declare(strict_types=1);

// Não deixe NADA antes do PHP (nem espaços) para não quebrar os headers
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',
  'secure'   => isset($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/conexao.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  header('Location: ja_possui_conta.html?erro=conexao'); exit;
}
$conn->set_charset('utf8mb4');

// Helpers
function back($q){ header('Location: ja_possui_conta.html'.$q); exit; }
function go($q){ header('Location: '.$q); exit; }

// Inputs
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
  back('?erro=campos');
}

// Confere se a tabela existe
$temClientes = false;
if ($rs = $conn->query("SHOW TABLES LIKE 'clientes'")) {
  $temClientes = (bool)$rs->num_rows;
  $rs->close();
}
if (!$temClientes) {
  // Caso seu projeto ainda use 'usuarios' como fallback, você pode redirecionar para o login genérico:
  // back('?erro=sem_tabela_clientes');
  // ou tentar fallback automático:
  $sql = "SELECT id, nome, email, senha FROM usuarios WHERE email = ? LIMIT 1";
  $idCol = 'id';
  $tabela = 'usuarios';
} else {
  $sql = "SELECT id_cliente AS id, nome, email, senha FROM clientes WHERE email = ? LIMIT 1";
  $idCol = 'id';
  $tabela = 'clientes';
}

// Busca o usuário
$stmt = $conn->prepare($sql);
if (!$stmt) { back('?erro=dbprep'); }
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
  $stmt->close();
  back('?erro=nao_encontrado');
}
$row = $res->fetch_assoc();
$stmt->close();

// Valida a senha (hash moderno ou legado sem hash)
$stored = (string)($row['senha'] ?? '');
$ok = password_verify($senha, $stored) || hash_equals($stored, $senha);

if (!$ok) {
  back('?erro=senha');
}

// Se o armazenado já era hash e estiver defasado, rehash para padrão atual
$info = password_get_info($stored);
if ($info['algo'] !== 0 && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
  $novo = password_hash($senha, PASSWORD_DEFAULT);
  if ($tabela === 'clientes') {
    $up = $conn->prepare("UPDATE clientes SET senha = ? WHERE id_cliente = ?");
  } else {
    $up = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
  }
  if ($up) { $up->bind_param('si', $novo, $row[$idCol]); $up->execute(); $up->close(); }
}

// Cria sessão de CLIENTE
if ($tabela === 'clientes') {
  $_SESSION['cliente'] = [
    'id_cliente' => (int)$row['id'],
    'nome'       => $row['nome'] ?? '',
    'email'      => $row['email'] ?? '',
    'tipo'       => 'cliente',
  ];
} else {
  // fallback 'usuarios' (se você ainda estiver migrando)
  $_SESSION['cliente'] = [
    'id_cliente' => (int)$row['id'],
    'nome'       => $row['nome'] ?? '',
    'email'      => $row['email'] ?? '',
    'tipo'       => 'cliente',
  ];
}

// Regenera o ID da sessão por segurança
session_regenerate_id(true);

// Redireciona para a Home com “boas-vindas” (seu JS na homepage abre o modal nesse parâmetro)
go('homepage.html?welcome=1');
