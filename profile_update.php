<?php
// profile_update.php
declare(strict_types=1);
session_start();
require_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_profissional'])) { http_response_code(401); echo json_encode(['ok'=>false,'message'=>'Não autenticado']); exit; }
$pid = (int)$_SESSION['id_profissional'];

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$nome     = trim((string)($in['nome'] ?? ''));
$cidade   = trim((string)($in['cidade'] ?? ''));
$telefone = trim((string)($in['telefone'] ?? ''));
$categoria= trim((string)($in['categoria'] ?? ''));
$site     = trim((string)($in['site'] ?? ''));
$bio      = trim((string)($in['bio'] ?? ''));

if ($nome === '') { echo json_encode(['ok'=>false,'message'=>'Nome é obrigatório']); exit; }

$stmt = $conn->prepare("UPDATE profissional 
                        SET nome=?, cidade=?, telefone=?, categoria=?, site=?, bio=? 
                        WHERE id_profissional=?");
$stmt->bind_param("ssssssi", $nome, $cidade, $telefone, $categoria, $site, $bio, $pid);
$stmt->execute();

// Reflete o "Sobre" também na tabela de perfil (comentario)
$conn->query("INSERT INTO perfil_profissional (profissional_id, comentario)
              VALUES ($pid, '".$conn->real_escape_string($bio)."')
              ON DUPLICATE KEY UPDATE comentario=VALUES(comentario)");

echo json_encode(['ok'=>true]);
?>