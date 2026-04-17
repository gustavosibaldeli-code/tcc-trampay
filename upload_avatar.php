<?php
// upload_avatar.php — Corrigido
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

if (!isset($_SESSION['id_profissional'])) {
  echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
  exit;
}

$id_prof = intval($_SESSION['id_profissional']);
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['ok' => false, 'message' => 'Nenhum arquivo enviado.']);
  exit;
}

$ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
$nome_arquivo = 'avatar_' . $id_prof . '_' . uniqid() . '.' . $ext;
$caminho_final = $upload_dir . $nome_arquivo;

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $caminho_final)) {
  echo json_encode(['ok' => false, 'message' => 'Erro ao mover arquivo.']);
  exit;
}

$url_banco = 'uploads/' . $nome_arquivo;

// Atualiza na tabela profissional
$stmt = $conn->prepare("UPDATE profissional SET avatar_url = ? WHERE id_profissional = ?");
$stmt->bind_param("si", $url_banco, $id_prof);
if ($stmt->execute()) {
  echo json_encode(['ok' => true, 'url' => $url_banco, 'message' => 'Avatar atualizado com sucesso!']);
} else {
  echo json_encode(['ok' => false, 'message' => 'Erro no banco.']);
}
$stmt->close();
$conn->close();
?>
