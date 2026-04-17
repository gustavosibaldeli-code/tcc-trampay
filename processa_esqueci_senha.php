<?php
require 'conexao.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!empty($_POST['email'])) {
    $email = trim($_POST['email']);

    // Consulta segura
    $stmt = $conn->prepare("SELECT * FROM profissional WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $token = bin2hex(random_bytes(50));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Atualiza token e expiração
        $update = $conn->prepare("UPDATE profissional SET reset_token=?, token_expira=? WHERE email=?");
        $update->bind_param("sss", $token, $expira, $email);
        $update->execute();

        // Link para redefinir
        $link = "http://localhost/TCC/redefinir.php?token=" . $token;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'empresatrampay@gmail.com';
            $mail->Password = 'ppgx zrbs nssc vtqh'; // senha de app
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('empresatrampay@gmail.com', 'Trampay suporte');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Alterar de senha';
            $mail->Body    = "Clique no link para redefinir sua senha: <a href='$link'>$link</a>";

            $mail->send();
            echo "sucesso";
        } catch (Exception $e) {
            echo "erro: {$mail->ErrorInfo}";
        }
    } else {
        echo "erro";
    }
} else {
    echo "erro";
}
?>
