<?php
session_start();

// Conexão com o banco (mantido)
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "trampay";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão (mantido)
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Pegando dados do formulário (mantido)
$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    echo "<script>
        alert('Preencha todos os campos.');
        window.location.href='ja_possui_conta_funcionario.html';
    </script>";
    exit;
}

// Buscar profissional pelo e-mail (mantido)
$stmt = $conn->prepare("SELECT 
        id_profissional, nome, email, cpf, telefone, cidade, categoria, experiencia, bio, site, avatar_url, senha 
    FROM profissional 
    WHERE email = ? 
    LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Email não encontrado (mantido)
    echo "<script>
        alert('E-mail não encontrado. Verifique seu e-mail ou faça cadastro.');
        window.location.href='cadfuncionario.html';
    </script>";
    exit;
} else {
    $row = $result->fetch_assoc();

    // Verifica a senha (mantido: hash e fallback em texto puro)
    $senha_valida = false;
    if (password_verify($senha, $row['senha'])) {
        $senha_valida = true;
    } else if ($row['senha'] === $senha) {
        $senha_valida = true;
    }

    if ($senha_valida) {
        // Verifica/Cria perfil_profissional (mantido)
        $stmt_perfil = $conn->prepare("SELECT id FROM perfil_profissional WHERE profissional_id = ? LIMIT 1");
        $stmt_perfil->bind_param("i", $row['id_profissional']);
        $stmt_perfil->execute();
        $result_perfil = $stmt_perfil->get_result();

        if ($result_perfil->num_rows === 0) {
            // Cria perfil profissional se não existir (mantido)
            $stmt_insert_perfil = $conn->prepare("INSERT INTO perfil_profissional (profissional_id, nome) VALUES (?, ?)");
            $stmt_insert_perfil->bind_param("is", $row['id_profissional'], $row['nome']);
            $stmt_insert_perfil->execute();
            $stmt_insert_perfil->close();
        }
        $stmt_perfil->close();

        // ======== SESSÕES ========
        // Mantém sua sessão original (NÃO REMOVIDA)
        $_SESSION['profissional'] = [
            'id'    => $row['id_profissional'],
            'nome'  => $row['nome'],
            'email' => $row['email'],
            'tipo'  => 'profissional'
        ];

        // >>> ADIÇÃO: sessão padrão esperada pelo profilepview.html + auth_profissional.php?action=status
// ======== SESSÕES ========
// Mantém sua sessão original (NÃO REMOVIDA)
$_SESSION['profissional'] = [
    'id'    => $row['id_profissional'],
    'nome'  => $row['nome'],
    'email' => $row['email'],
    'tipo'  => 'profissional'
];

// >>> ADIÇÃO: sessão padrão esperada pelo profilepview.html + auth_profissional.php?action=status
$_SESSION['logged_in'] = true;
$_SESSION['user'] = [
    'tipo'            => 'profissional',
    'id_profissional' => (int)$row['id_profissional'],
    'nome'            => $row['nome'] ?? '',
    'email'           => $row['email'] ?? '',
    'cpf'             => $row['cpf'] ?? null,
    'telefone'        => $row['telefone'] ?? null,
    'cidade'          => $row['cidade'] ?? null,
    'categoria'       => $row['categoria'] ?? null,
    'experiencia'     => $row['experiencia'] ?? null,
    'bio'             => $row['bio'] ?? null,
    'site'            => $row['site'] ?? null,
    'avatar_url'      => $row['avatar_url'] ?? null,
];

// === Sessão obrigatória para integração com auth_profissional.php ===
$_SESSION['id_profissional'] = (int)$row['id_profissional'];  // <-- ESSENCIAL
$_SESSION['email']           = $row['email'];
$_SESSION['nome']            = $row['nome'];

// Redireciona para o perfil (mantido)
header("Location: profilepview.html?login=ok");
exit;

    } else {
        // Senha incorreta (mantido)
        echo "<script>
            alert('Senha incorreta. Tente novamente.');
            window.location.href='ja_possui_conta_funcionario.html';
        </script>";
        exit;
    }
}

$stmt->close();
$conn->close();
