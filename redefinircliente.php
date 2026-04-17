<?php
require 'conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            text-align: left;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="form-container">
    <?php
    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE reset_token=? AND token_expira > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            ?>
            <h2>Redefinir Senha</h2>
            <form method="POST" action="salva_nova_senhacliente.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <label for="senha1">Nova senha:</label>
                <input type="password" name="senha1" id="senha1" required>
                
                <label for="senha2">Confirmar senha:</label>
                <input type="password" name="senha2" id="senha2" required>
                
                <button type="submit">Redefinir</button>
            </form>
            <?php
        } else {
            echo '<div class="message">Token inválido ou expirado.</div>';
        }
    } else {
        echo '<div class="message">Token não informado.</div>';
    }
    ?>
    </div>
</body>
</html>
