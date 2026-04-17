<?php
// cadcliente.php — cadastro de cliente + login imediato
session_start();

// ===== Conexão =====
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
  $mysqli = new mysqli("localhost", "root", "", "trampay");
  $mysqli->set_charset('utf8mb4');
} catch (Throwable $e) {
  http_response_code(500);
  exit("Erro ao conectar no banco.");
}

// ===== Apenas POST =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método não permitido');
}

// ===== Coleta & saneamento =====
$nome   = trim($_POST['nome']   ?? '');
$cpf    = trim($_POST['cpf']    ?? '');
$email  = trim($_POST['email']  ?? '');
$tel    = trim($_POST['telefone'] ?? '');
$senha  = (string)($_POST['senha'] ?? '');

// Campos que existem no HTML mas NÃO existem na tabela clientes:
// data_nascimento, cep — vamos ignorar para não quebrar o INSERT.
// (Sua tabela `clientes` tem: nome, cpf, email, handle, foto_perfil, telefone, cidade, endereco, senha) 

// Validações básicas
if ($nome === '' || $email === '' || $senha === '') {
  exit("<script>alert('Preencha nome, e-mail e senha.'); history.back();</script>");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  exit("<script>alert('E-mail inválido.'); history.back();</script>");
}
if (strlen($senha) < 6) {
  exit("<script>alert('Senha deve ter ao menos 6 caracteres.'); history.back();</script>");
}

// Normalizações leves (mantêm formatação visual, mas garantem limites)
$cpf = substr($cpf, 0, 14);
$tel = substr($tel, 0, 20);

// Hash de senha (bcrypt)
$hash = password_hash($senha, PASSWORD_BCRYPT);

// ===== INSERT compatível com o schema =====
// Tabela clientes: id_cliente (AI), nome, cpf, email (UNIQUE), handle (NULL), foto_perfil (NULL),
// telefone, cidade (NULL), endereco (NULL), senha, criado_em (DEFAULT)
try {
  $stmt = $mysqli->prepare("
    INSERT INTO clientes (nome, cpf, email, telefone, senha)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("sssss", $nome, $cpf, $email, $tel, $hash);
  $stmt->execute();
  $novoId = $stmt->insert_id;

  // (Opcional) se você tiver um checkbox real dos termos com name="aceite",
  // dá para registrar em `aceite_termos`:
  // if (!empty($_POST['aceite'])) {
  //   $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  //   $v  = '1.0';
  //   $stmt2 = $mysqli->prepare("
  //     INSERT INTO aceite_termos (tipo_usuario, usuario_id, ip_usuario, versao_termos)
  //     VALUES ('cliente', ?, ?, ?)
  //   ");
  //   $stmt2->bind_param("iss", $novoId, $ip, $v);
  //   $stmt2->execute();
  // }

// ===== Login imediato (sessão) =====
$_SESSION['user'] = [
  'tipo'        => 'cliente',
  'id_cliente'  => $novoId,
  'nome'        => $nome,
  'email'       => $email,
  'foto_perfil' => null,
  'handle'      => null,
];

$_SESSION['logged_in'] = true;          // <-- ADICIONE ESTA LINHA
session_regenerate_id(true);            // (opcional, segurança)

header('Location: homepage.html');      // mantém seu redirect
exit;

} catch (mysqli_sql_exception $e) {
  // Trata e-mail duplicado (erro 1062 por UNIQUE em email)
  if ($e->getCode() == 1062) {
    exit("<script>alert('Este e-mail já está cadastrado. Tente fazer login.'); window.location.href='ja_possui_conta.html';</script>");
  }
  // Outro erro qualquer
  error_log("Erro cadastro cliente: ".$e->getMessage());
  http_response_code(500);
  exit("<script>alert('Erro ao cadastrar.'); history.back();</script>");
}
