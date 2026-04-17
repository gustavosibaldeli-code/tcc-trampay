<?php
// auth.php — Trampay (unificado)
// Endpoints: status, login_cliente (alias: login), upload_avatar_cliente, logout

declare(strict_types=1);

// ===== Sessão (configurar cookie ANTES de iniciar) =====
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',                          // ajuste se usar subdomínio
  'secure'   => !empty($_SERVER['HTTPS']),   // envia só em HTTPS
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');

// ===== DB =====
require_once __DIR__ . '/conexao.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Conexão inválida']); exit;
}
$conn->set_charset('utf8mb4');

// ===== Helpers =====
function j($arr, int $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function action(): string { return $_GET['action'] ?? $_POST['action'] ?? 'status'; }

// Alias: se vier "login", trata como "login_cliente"
if (action() === 'login') { $_GET['action'] = 'login_cliente'; }

// ====== STATUS ======
if (action() === 'status') {
  $logged = !empty($_SESSION['logged_in']) && !empty($_SESSION['user']);
  j(['logged_in'=>$logged, 'user'=>$logged ? $_SESSION['user'] : null]);
}

// ====== LOGOUT ======
if (action() === 'logout') {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
  j(['ok'=>true, 'logged_in'=>false]);
}

// ====== LOGIN CLIENTE (ou alias: login) ======
if (action() === 'login_cliente') {
  $email = trim($_POST['email'] ?? '');
  $senha = (string)($_POST['senha'] ?? '');

  if ($email === '' || $senha === '') j(['ok'=>false,'msg'=>'Preencha email e senha.'], 400);

  $sql  = "SELECT id_cliente, nome, email, senha, foto_perfil, handle
           FROM clientes WHERE email = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  if (!$stmt) j(['ok'=>false,'msg'=>'Erro interno (prepare).'], 500);
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $rs   = $stmt->get_result();
  $row  = $rs?->fetch_assoc();
  $stmt->close();

  // Falha se não encontrou
  if (!$row) j(['ok'=>false,'msg'=>'Email ou senha inválidos.'], 401);

  $stored = (string)($row['senha'] ?? '');

  // Aceita hash com password_verify OU (fallback) comparação direta para senhas legadas
  $ok = false;
  if ($stored !== '') {
    if (password_get_info($stored)['algo'] !== 0) {
      $ok = password_verify($senha, $stored);
    } else {
      // armazenado em texto puro (legado) — compara direto
      $ok = hash_equals($stored, $senha);
    }
  }

  if (!$ok) j(['ok'=>false,'msg'=>'Email ou senha inválidos.'], 401);

  // Rehash se necessário (apenas se já era hash)
  if ($stored !== '' && password_get_info($stored)['algo'] !== 0 && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
    $newHash = password_hash($senha, PASSWORD_DEFAULT);
    if ($upd = $conn->prepare("UPDATE clientes SET senha=? WHERE id_cliente=?")) {
      $idc = (int)$row['id_cliente'];
      $upd->bind_param('si', $newHash, $idc);
      $upd->execute();
      $upd->close();
    }
  }

  // Sessão (modelo único)
  $_SESSION['logged_in'] = true;
  $_SESSION['user'] = [
    'tipo'        => 'cliente',
    'id_cliente'  => (int)$row['id_cliente'],
    'nome'        => $row['nome'],
    'email'       => $row['email'],
    'handle'      => $row['handle'] ?? null,
    'foto_perfil' => $row['foto_perfil'] ?? null,
  ];

  j(['ok'=>true, 'user'=>$_SESSION['user']]);
}

// ====== UPLOAD AVATAR (CLIENTE) ======
function ensure_uploads_dir(): string {
  $dir = __DIR__ . '/uploads';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  return $dir;
}
function save_uploaded_image(array $file, string $basename): array {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return [false, 'Falha no upload.'];
  if (($file['size'] ?? 0) > 5*1024*1024) return [false, 'Arquivo maior que 5MB.'];

  $mime = @mime_content_type($file['tmp_name']);
  $ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default      => null,
  };
  if (!$ext) return [false, 'Use JPG, PNG ou WEBP.'];

  $dir   = ensure_uploads_dir();
  $fname = $basename . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dest  = $dir . '/' . $fname;

  if (!move_uploaded_file($file['tmp_name'], $dest)) return [false, 'Não foi possível salvar o arquivo.'];
  return [true, 'uploads/' . $fname];
}

if (action() === 'upload_avatar_cliente') {
  if (empty($_SESSION['logged_in']) || empty($_SESSION['user'])) j(['ok'=>false,'msg'=>'Não autenticado.'], 401);
  $u = $_SESSION['user'];
  if (strtolower((string)$u['tipo']) !== 'cliente') j(['ok'=>false,'msg'=>'Apenas clientes podem alterar a foto.'], 403);

  if (empty($_FILES['foto'])) j(['ok'=>false,'msg'=>'Arquivo não enviado.'], 400);

  [$ok, $res] = save_uploaded_image($_FILES['foto'], 'cliente_'.$u['id_cliente']);
  if (!$ok) j(['ok'=>false,'msg'=>$res], 400);

  $stmt = $conn->prepare("UPDATE clientes SET foto_perfil=? WHERE id_cliente=?");
  if (!$stmt) j(['ok'=>false,'msg'=>'Erro no prepare().'], 500);
  $stmt->bind_param('si', $res, $u['id_cliente']);
  $stmt->execute();
  $stmt->close();

  $_SESSION['user']['foto_perfil'] = $res;
  j(['ok'=>true, 'url'=>$res]);
}

// ====== Ação não reconhecida ======
j(['ok'=>false, 'msg'=>'Ação inválida.'], 400);
