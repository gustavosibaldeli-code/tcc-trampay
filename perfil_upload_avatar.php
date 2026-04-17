<?php
// api/perfil_upload_avatar.php — Upload de avatar e persistência no BD (corrigido)

session_start();
header('Content-Type: application/json; charset=utf-8');

try{
  // --------- Autenticação ----------
  $user = $_SESSION['user'] ?? null;
  if (!$user) {
    throw new Exception('Faça login para alterar a foto.');
  }

  // Conexão
  require_once __DIR__ . '/../conexao.php'; // expõe $conn (mysqli)
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  // --------- Validação do arquivo ----------
  if (!isset($_FILES['foto']) || ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    throw new Exception('Envie uma imagem válida.');
  }

  $f = $_FILES['foto'];
  $mime = @mime_content_type($f['tmp_name']);
  if (strpos((string)$mime, 'image/') !== 0) throw new Exception('Arquivo enviado não é uma imagem.');
  if (($f['size'] ?? 0) > 4 * 1024 * 1024) throw new Exception('Imagem acima de 4MB.');

  // Extensão por mimetype
  $ext = '.jpg';
  if (stripos($mime, 'png')  !== false) $ext = '.png';
  if (stripos($mime, 'jpeg') !== false) $ext = '.jpg';
  if (stripos($mime, 'webp') !== false) $ext = '.webp';

  // --------- Caminhos ----------
  $tipo      = strtolower((string)($user['tipo'] ?? 'cliente')); // 'cliente' | 'profissional'
  $idCliente = (int)($user['id_cliente'] ?? 0);
  $idProf    = (int)($user['id_profissional'] ?? 0);

  if ($tipo === 'cliente' && $idCliente <= 0) throw new Exception('Sessão de cliente inválida.');
  if ($tipo !== 'cliente' && $idProf <= 0)   $tipo = 'profissional';

  $destDir = __DIR__ . '/../uploads/avatars';
  if (!is_dir($destDir) && !mkdir($destDir, 0777, true)) {
    throw new Exception('Falha ao preparar pasta de upload.');
  }

  $base    = $tipo === 'cliente' ? ('cli_' . $idCliente) : ('prof_' . $idProf);
  $fname   = $base . '_' . time() . $ext;
  $destAbs = $destDir . '/' . $fname;

  if (!move_uploaded_file($f['tmp_name'], $destAbs)) {
    throw new Exception('Não foi possível salvar a imagem.');
  }

  // Caminho público (ajuste se seu site estiver em subpasta)
  $destPublic = 'uploads/avatars/' . $fname;

  // --------- Persistência no banco ----------
  if ($tipo === 'cliente') {
    // Tabela correta: clientes (plural)
    $st = $conn->prepare("UPDATE clientes SET foto_perfil=? WHERE id_cliente=?");
    $st->bind_param('si', $destPublic, $idCliente);
    $st->execute();
    $st->close();

    $_SESSION['user']['foto_perfil'] = $destPublic;

  } else {
    // Tabela e colunas corretas do profissional
    // perfil_profissional (profissional_id, foto_perfil)
    // Se não existir linha, insere; senão, atualiza.
    $st = $conn->prepare("SELECT id FROM perfil_profissional WHERE profissional_id=? LIMIT 1");
    $st->bind_param('i', $idProf);
    $st->execute();
    $existe = $st->get_result()->fetch_assoc();
    $st->close();

    if ($existe) {
      $st = $conn->prepare("UPDATE perfil_profissional SET foto_perfil=? WHERE profissional_id=?");
      $st->bind_param('si', $destPublic, $idProf);
      $st->execute();
      $st->close();
    } else {
      $st = $conn->prepare("INSERT INTO perfil_profissional (profissional_id, foto_perfil) VALUES (?, ?)");
      $st->bind_param('is', $idProf, $destPublic);
      $st->execute();
      $st->close();
    }

    $_SESSION['user']['foto_perfil'] = $destPublic;
  }

  echo json_encode(['ok'=>true, 'url'=>$destPublic], JSON_UNESCAPED_UNICODE);
  exit;

}catch(Throwable $e){
  http_response_code(400);
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
