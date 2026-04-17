<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Configuração do banco
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'trampay';
$DB_PORT = 3306;

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco']);
    exit;
}

// Coleta dados do formulário
$profissional_id = intval($_POST['profissional_id'] ?? 0);
$data = $_POST['data'] ?? '';
$hora = $_POST['hora'] ?? '';
$servico = $_POST['servico'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$local = $_POST['local'] ?? '';
$contato = $_POST['contato'] ?? '';

// Validações
if (!$profissional_id || !$data || !$hora || !$servico || !$descricao || !$local || !$contato) {
    echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios']);
    exit;
}

// Busca dados do profissional
$sql_profissional = "SELECT nome, email FROM profissional WHERE id_profissional = ?";
$stmt_prof = $conn->prepare($sql_profissional);
$stmt_prof->bind_param('i', $profissional_id);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$profissional = $result_prof->fetch_assoc();
$stmt_prof->close();

if (!$profissional) {
    echo json_encode(['success' => false, 'error' => 'Profissional não encontrado']);
    exit;
}

// Insere o agendamento no banco
$sql_agendamento = "INSERT INTO agenda_profissional (profissional_id, data, hora, status, servico, descricao, local, contato_cliente) 
                    VALUES (?, ?, ?, 'agendado', ?, ?, ?, ?)";
$stmt_agenda = $conn->prepare($sql_agendamento);
$stmt_agenda->bind_param('issssss', $profissional_id, $data, $hora, $servico, $descricao, $local, $contato);

<?php
// ... código anterior mantido ...

if ($stmt_agenda->execute()) {
    $agendamento_id = $conn->insert_id;
    
    // Envia email para o profissional
    $enviou_email = enviarEmailAgendamento($profissional, $data, $hora, $servico, $descricao, $local, $contato);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Agendamento realizado com sucesso' . ($enviou_email ? ' e email enviado' : ''),
        'agendamento_id' => $agendamento_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar agendamento: ' . $conn->error]);
}

// ... código posterior mantido ...

// Função para enviar email MELHORADA
function enviarEmailAgendamento($profissional, $data, $hora, $servico, $descricao, $local, $contato) {
    $para = $profissional['email'];
    $assunto = "🎯 Novo Agendamento - Trampay";
    
    // Formatar data em português
    $data_br = date('d/m/Y', strtotime($data));
    
    $mensagem = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(90deg, #000a25fd, #000c2ccc); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .detalhes { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>📅 Novo Agendamento Recebido</h1>
        </div>
        <div class='content'>
            <p>Olá <strong>{$profissional['nome']}</strong>,</p>
            <p>Você recebeu um novo agendamento através da plataforma Trampay:</p>
            
            <div class='detalhes'>
                <h3>📋 Detalhes do Agendamento</h3>
                <p><strong>Serviço:</strong> {$servico}</p>
                <p><strong>Data:</strong> {$data_br}</p>
                <p><strong>Horário:</strong> {$hora}</p>
                <p><strong>Local:</strong> {$local}</p>
                <p><strong>Contato do Cliente:</strong> {$contato}</p>
            </div>
            
            <div class='detalhes'>
                <h3>📝 Descrição do Serviço</h3>
                <p>{$descricao}</p>
            </div>
            
            <p>Acesse sua conta na Trampay para gerenciar este agendamento e ver mais detalhes.</p>
        </div>
        <div class='footer'>
            <p>Atenciosamente,<br>
            <strong>Equipe Trampay</strong></p>
            <p>Este é um email automático, por favor não responda.</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "From: Trampay <noreply@trampay.com>\r\n";
    $headers .= "Reply-To: noreply@trampay.com\r\n";
    
    // Em produção, substitua por PHPMailer ou serviço de email profissional
    try {
        // Simulação de envio - em produção, use mail() ou biblioteca de email
        error_log("📧 EMAIL AGENDAMENTO para: {$para}");
        error_log("Assunto: {$assunto}");
        error_log("Mensagem: Novo agendamento de {$servico} para {$data_br} às {$hora}");
        
        // Para teste, você pode descomentar a linha abaixo:
        // return mail($para, $assunto, $mensagem, $headers);
        
        return true; // Simula sucesso
    } catch (Exception $e) {
        error_log("Erro ao enviar email: " . $e->getMessage());
        return false;
    }
}
?>