<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['profissional'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$profissional_id = $_SESSION['profissional']['id'];

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

// Busca dados do profissional
$sqlProfissional = "SELECT * FROM profissional WHERE id_profissional = ?";
$stmtProf = $conn->prepare($sqlProfissional);
$stmtProf->bind_param('i', $profissional_id);
$stmtProf->execute();
$resultProf = $stmtProf->get_result();
$profissional = $resultProf->fetch_assoc();
$stmtProf->close();

// Busca dados do perfil profissional
$sqlPerfil = "SELECT * FROM perfil_profissional WHERE profissional_id = ?";
$stmtPerfil = $conn->prepare($sqlPerfil);
$stmtPerfil->bind_param('i', $profissional_id);
$stmtPerfil->execute();
$resultPerfil = $stmtPerfil->get_result();
$perfil = $resultPerfil->fetch_assoc();
$stmtPerfil->close();

if ($profissional) {
    // Combina os dados das duas tabelas
    $dadosCompletos = array_merge($profissional, $perfil ?: []);
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $dadosCompletos['id_profissional'],
            'nome' => $dadosCompletos['nome'] ?? '',
            'email' => $dadosCompletos['email'] ?? '',
            'tipo' => 'profissional',
            // CORREÇÃO: Removidos campos que não existem nas suas tabelas
            // e mantidos apenas os que realmente existem
            'cpf' => $dadosCompletos['cpf'] ?? '',
            // Dados do perfil_profissional (que existem na sua tabela)
            'banner' => $dadosCompletos['banner'] ?? '',
            'foto_perfil' => $dadosCompletos['foto_perfil'] ?? '',
            'publicacao_foto' => $dadosCompletos['publicacao_foto'] ?? '',
            'comentario' => $dadosCompletos['comentario'] ?? '',
            'data_atualizacao' => $dadosCompletos['data_atualizacao'] ?? ''
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Profissional não encontrado']);
}

$conn->close();
?>