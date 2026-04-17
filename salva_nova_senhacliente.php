<?php
require 'conexao.php';

function exibirMensagem($texto, $tipo = "sucesso") {
    $cor = $tipo === "sucesso" ? "#d4edda" : "#f8d7da";
    $textoCor = $tipo === "sucesso" ? "#155724" : "#721c24";
    echo "
    <div style='
        background-color: {$cor};
        color: {$textoCor};
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        font-family: Arial, sans-serif;
        max-width: 400px;
        margin: 80px auto;
        box-shadow: 0px 4px 15px rgba(0,0,0,0.2);
    '>
        <p style='font-size: 18px; margin-bottom: 20px;'>{$texto}</p>
        <a href='ja_possui_conta.html' style='
            display: inline-block;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        '>Voltar para o login</a>
    </div>
    ";
}

if (!empty($_POST['token']) && !empty($_POST['senha1']) && !empty($_POST['senha2'])) {
    if ($_POST['senha1'] === $_POST['senha2']) {
        $senha = $_POST['senha1']; // mantendo texto puro para compatibilidade
        $token = $_POST['token'];

        $stmt = $conn->prepare("UPDATE usuarios SET senha=?, reset_token=NULL, token_expira=NULL WHERE reset_token=?");
        $stmt->bind_param("ss", $senha, $token);

        if ($stmt->execute()) {
            exibirMensagem("Senha alterada com sucesso!", "sucesso");
        } else {
            exibirMensagem("Erro ao atualizar senha.", "erro");
        }
    } else {
        exibirMensagem("As senhas não coincidem.", "erro");
    }
} else {
    exibirMensagem("Preencha todos os campos.", "erro");
}
?>
