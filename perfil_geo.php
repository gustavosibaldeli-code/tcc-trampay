<?php
// perfil_geo.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php'; // $conn = new mysqli(...)

if (!isset($_SESSION['id_profissional'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false, 'message'=>'Não autenticado']);
  exit;
}

$id = (int)$_SESSION['id_profissional'];

$stmt = $conn->prepare("SELECT cep, bairro FROM perfil_profissional WHERE profissional_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cep = trim($row['cep'] ?? '');
$bairro = trim($row['bairro'] ?? '');

if ($cep !== '' && $bairro === '') {
  // Normaliza CEP: só dígitos
  $cepNum = preg_replace('/\D+/','', $cep);
  if (strlen($cepNum) === 8) {
    $json = @file_get_contents("https://viacep.com.br/ws/{$cepNum}/json/");
    if ($json) {
      $viacep = json_decode($json, true);
      if (is_array($viacep) && empty($viacep['erro'])) {
        $bairro = trim($viacep['bairro'] ?? '');
        if ($bairro !== '') {
          $up = $conn->prepare("UPDATE perfil_profissional SET bairro = ? WHERE profissional_id = ?");
          $up->bind_param("si", $bairro, $id);
          $up->execute();
          $up->close();
        }
      }
    }
  }
}

echo json_encode([
  'ok'     => true,
  'cep'    => $cep,
  'bairro' => $bairro
]);
