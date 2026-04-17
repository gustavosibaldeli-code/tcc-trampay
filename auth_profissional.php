<?php
// auth_profissional.php — compatível com profilepview.html
// Endpoints suportados (via ?action=):
//  - status           (GET)
//  - login            (POST JSON: {email, senha})
//  - logout           (GET)
//  - change_password  (POST JSON: {atual, nova})

declare(strict_types=1);

// cookies de sessão (seguro para localhost; remova 'secure'=>true se não estiver em HTTPS)
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/conexao.php'; // $conn = new mysqli(...)

function json_input(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function get_user_public(array $row): array {
  // só devolve dados necessários ao front
  return [
    'id_profissional' => (int)$row['id_profissional'],
    'nome'            => $row['nome'] ?? '',
    'email'           => $row['email'] ?? '',
    'telefone'        => $row['telefone'] ?? '',
    'cidade'          => $row['cidade'] ?? '',
    'categoria'       => $row['categoria'] ?? '',
    'site'            => $row['site'] ?? '',
    'bio'             => $row['bio'] ?? '',
    'avatar_url'      => $row['avatar_url'] ?? null,
  ];
}

function require_login_or_fail() {
  if (!isset($_SESSION['id_profissional'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Não autenticado.']);
    exit;
  }
}

$action = $_GET['action'] ?? 'status';

switch ($action) {
  case 'status': {
    if (!isset($_SESSION['id_profissional'])) {
      echo json_encode(['logged_in' => false]);
      exit;
    }
    // Carrega o usuário do banco para manter os dados atualizados
    $id = (int)$_SESSION['id_profissional'];
    $stmt = $conn->prepare("SELECT id_profissional, nome, email, telefone, cidade, categoria, site, bio, avatar_url FROM profissional WHERE id_profissional = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if (!$res) {
      // sessão apontava para alguém que não existe mais
      session_unset(); session_destroy();
      echo json_encode(['logged_in' => false]);
      exit;
    }
    echo json_encode(['logged_in' => true, 'user' => get_user_public($res)]);
    exit;
  }

  case 'login': {
    $in = json_input();
    $email = trim($in['email'] ?? '');
    $senha = $in['senha'] ?? '';

    if ($email === '' || $senha === '') {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'Informe e-mail e senha.']);
      exit;
    }

    // tabela e colunas conforme seu dump (profissional / id_profissional / senha bcrypt) 
    $stmt = $conn->prepare("SELECT id_profissional, nome, email, senha, telefone, cidade, categoria, site, bio, avatar_url FROM profissional WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($senha, $row['senha'])) {
      http_response_code(401);
      echo json_encode(['ok' => false, 'message' => 'E-mail ou senha inválidos.']);
      exit;
    }

    // 🔑 ESSENCIAL: gravar a sessão para liberar uploads e CRUD
    $_SESSION['id_profissional'] = (int)$row['id_profissional'];
    $_SESSION['email']           = $row['email'];
    $_SESSION['nome']            = $row['nome'];

    echo json_encode(['ok' => true, 'user' => get_user_public($row)]);
    exit;
  }

  case 'logout': {
    session_unset();
    session_destroy();
    echo json_encode(['ok' => true]);
    exit;
  }

  case 'change_password': {
    require_login_or_fail();
    $in = json_input();
    $atual = $in['atual'] ?? '';
    $nova  = $in['nova']  ?? '';

    if (strlen($nova) < 6) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'A nova senha deve ter ao menos 6 caracteres.']);
      exit;
    }

    $id = (int)$_SESSION['id_profissional'];
    $stmt = $conn->prepare("SELECT senha FROM profissional WHERE id_profissional = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !password_verify($atual, $row['senha'])) {
      http_response_code(401);
      echo json_encode(['ok' => false, 'message' => 'Senha atual incorreta.']);
      exit;
    }

    $hash = password_hash($nova, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE profissional SET senha = ? WHERE id_profissional = ?");
    $stmt->bind_param("si", $hash, $id);
    $ok = $stmt->execute();

    echo json_encode(['ok' => (bool)$ok]);
    exit;
  }

  default: {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Ação inválida.']);
    exit;
  }
}
