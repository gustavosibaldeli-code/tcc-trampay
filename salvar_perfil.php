<?php
session_start();
include("conexao.php");

if (!isset($_SESSION["id_profissional"])) {
    echo json_encode(["status" => "error", "mensagem" => "Usuário não logado."]);
    exit;
}

$id = $_SESSION["id_profissional"];
$nome = $_POST["nome"] ?? '';
$bio = $_POST["bio"] ?? '';
$foto = $_POST["foto_perfil"] ?? '';

$sql = "UPDATE perfil_profissional SET nome=?, comentario=?, foto_perfil=?, data_atualizacao=NOW() WHERE profissional_id=?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("sssi", $nome, $bio, $foto, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "mensagem" => $stmt->error]);
}
?>
