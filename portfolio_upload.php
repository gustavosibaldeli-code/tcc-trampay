<?php
// portfolio_upload.php — HARDENED
declare(strict_types=1);

// Nunca “vazar” warnings na resposta JSON:
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs_portfolio_upload.log');

header('Content-Type: application/json; charset=utf-8');
session_set_cookie_params(['path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
session_start();

require_once __DIR__ . '/conexao.php'; // $conn

function jexit($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['ok'=>$ok, 'message'=>$msg], $extra));
  exit;
}

// 1) Autenticação
if (empty($_SESSION['id_profissional'])) {
  http_response_code(401);
  jexit(false, 'Sessão expirada. Faça login novamente.');
}
$id_prof = (int)$_SESSION['id_profissional'];

// 2) Checagens do PHP ini (tamanho de post)
if (empty($_FILES)) {
  // Quando post_max_size estoura, $_FILES vem vazio
  jexit(false, 'Nenhum arquivo recebido. Verifique o tamanho (post_max_size / upload_max_filesize).');
}

if (!isset($_FILES['arquivo'])) {
  jexit(false, 'Campo de arquivo ausente. Envie como "arquivo".');
}

$file = $_FILES['arquivo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
  $codes = [
    UPLOAD_ERR_INI_SIZE   => 'Arquivo maior que o permitido (upload_max_filesize).',
    UPLOAD_ERR_FORM_SIZE  => 'Arquivo maior que o permitido (MAX_FILE_SIZE).',
    UPLOAD_ERR_PARTIAL    => 'Upload parcial.',
    UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado.',
    UPLOAD_ERR_NO_TMP_DIR => 'Sem diretório temporário.',
    UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar no disco.',
    UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão do PHP.',
  ];
  $msg = $codes[$file['error']] ?? ('Erro no upload (' . $file['error'] . ')');
  jexit(false, $msg);
}

// 3) Validação de tipo/tamanho
$maxBytes = 8 * 1024 * 1024; // 8 MB
if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
  jexit(false, 'Tamanho inválido. Máximo 8 MB.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$okMimes = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($mime, $okMimes, true)) {
  jexit(false, 'Formato inválido. Use JPG, PNG, WEBP ou GIF.');
}

// 4) Preparar diretório final
$dir = __DIR__ . '/uploads/';
if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
  jexit(false, 'Não foi possível criar a pasta "uploads/". Verifique permissões.');
}

// 5) Gerar nome e mover
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$ext = $ext ? ('.' . strtolower($ext)) : (
  $mime === 'image/jpeg' ? '.jpg' :
  ($mime === 'image/png' ? '.png' : ($mime === 'image/webp' ? '.webp' : '.img'))
);

$fname = 'portfolio_' . $id_prof . '_' . bin2hex(random_bytes(6)) . $ext;
$full  = $dir . $fname;

if (!move_uploaded_file($file['tmp_name'], $full)) {
  jexit(false, 'Falha ao mover o arquivo para uploads/.');
}

// 6) Caminho relativo salvo no banco
$urlBanco = 'uploads/' . $fname;

$stmt = $conn->prepare("INSERT INTO profissional_portfolio (id_profissional, url) VALUES (?, ?)");
if (!$stmt) {
  error_log('Prepare falhou: '.$conn->error);
  jexit(false, 'Erro interno ao preparar comando.');
}
$stmt->bind_param("is", $id_prof, $urlBanco);
if (!$stmt->execute()) {
  error_log('Execute falhou: '.$stmt->error);
  jexit(false, 'Erro ao salvar no banco.');
}
$stmt->close();

jexit(true, 'Imagem adicionada ao portfólio!', ['url' => $urlBanco]);
