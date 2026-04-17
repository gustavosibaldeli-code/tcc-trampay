<?php
// cadfuncionario.php — cadastro + login imediato (com CEP → bairro via ViaCEP)
session_start();

// ======================= CONEXÃO =======================
$mysqli = @new mysqli("localhost", "root", "", "trampay");
if ($mysqli->connect_error) {
  exit("<script>alert('Erro ao conectar no banco: {$mysqli->connect_error}'); history.back();</script>");
}
$mysqli->set_charset('utf8mb4');

// ======================= SÓ POST =======================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método não permitido');
}

// ======================= CAMPOS DO FORM =======================
// conforme cadfuncionario.html (adicione o <input name="cep"> no seu form)
$nome      = trim($_POST['nome']      ?? '');
$cpf       = trim($_POST['cpf']       ?? ''); // pode normalizar se quiser
$email     = trim($_POST['email']     ?? '');
$telefone  = trim($_POST['telefone']  ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$senha     = (string)($_POST['senha'] ?? '');
$cep_raw   = trim($_POST['cep']       ?? ''); // NOVO (opcional, mas recomendado)

// validações básicas
if ($nome==='' || $cpf==='' || $email==='' || $telefone==='' || $categoria==='' || $senha==='') {
  exit("<script>alert('Preencha todos os campos obrigatórios.'); history.back();</script>");
}

// ======================= ANTI-DUPLICIDADE (EMAIL) =======================
$stmt = $mysqli->prepare("SELECT id_profissional FROM profissional WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  exit("<script>alert('Já existe um cadastro com este e-mail. Faça login.'); window.location.href='ja_possui_conta_funcionario.html?motivo=email';</script>");
}
$stmt->close();

// ======================= INSERT PROFISSIONAL =======================
$hash = password_hash($senha, PASSWORD_BCRYPT);

$sql = "INSERT INTO profissional (nome, cpf, email, telefone, categoria, senha)
        VALUES (?,?,?,?,?,?)";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
  exit("<script>alert('Erro interno (prepare).'); history.back();</script>");
}
$stmt->bind_param("ssssss", $nome, $cpf, $email, $telefone, $categoria, $hash);
if (!$stmt->execute()) {
  $err = addslashes($stmt->error);
  $stmt->close();
  exit("<script>alert('Erro ao cadastrar: {$err}'); history.back();</script>");
}
$prof_id = $stmt->insert_id;
$stmt->close();

// ======================= CEP → BAIRRO (opcional) =======================
$cep      = preg_replace('/\D+/', '', $cep_raw); // mantém só dígitos
$bairro   = null;

if ($cep && strlen($cep) === 8) {
  // consulta ViaCEP com timeout curto para não travar seu cadastro
  $ctx = stream_context_create([
    'http' => ['timeout' => 3] // 3s
  ]);
  $json = @file_get_contents("https://viacep.com.br/ws/{$cep}/json/", false, $ctx);
  if ($json) {
    $viacep = json_decode($json, true);
    if (is_array($viacep) && empty($viacep['erro'])) {
      $bairro = trim($viacep['bairro'] ?? '') ?: null;
    }
  }
}

// ======================= PERFIL_PROFISSIONAL =======================
// garante a existência do perfil e salva cep/bairro quando houver
$stmt = $mysqli->prepare("SELECT id FROM perfil_profissional WHERE profissional_id = ? LIMIT 1");
$stmt->bind_param("i", $prof_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  if ($cep && $bairro) {
    $stmt2 = $mysqli->prepare("INSERT INTO perfil_profissional (profissional_id, cep, bairro) VALUES (?, ?, ?)");
    $cep_mask = substr($cep,0,5).'-'.substr($cep,5); // 00000-000
    $stmt2->bind_param("iss", $prof_id, $cep_mask, $bairro);
  } elseif ($cep) {
    $stmt2 = $mysqli->prepare("INSERT INTO perfil_profissional (profissional_id, cep) VALUES (?, ?)");
    $cep_mask = substr($cep,0,5).'-'.substr($cep,5);
    $stmt2->bind_param("is", $prof_id, $cep_mask);
  } else {
    $stmt2 = $mysqli->prepare("INSERT INTO perfil_profissional (profissional_id) VALUES (?)");
    $stmt2->bind_param("i", $prof_id);
  }
  $stmt2->execute();
  $stmt2->close();
} else {
  // já existe: atualiza cep/bairro se veio do form
  if ($cep) {
    if ($bairro) {
      $stmt2 = $mysqli->prepare("UPDATE perfil_profissional SET cep = ?, bairro = ? WHERE profissional_id = ?");
      $cep_mask = substr($cep,0,5).'-'.substr($cep,5);
      $stmt2->bind_param("ssi", $cep_mask, $bairro, $prof_id);
    } else {
      $stmt2 = $mysqli->prepare("UPDATE perfil_profissional SET cep = ? WHERE profissional_id = ?");
      $cep_mask = substr($cep,0,5).'-'.substr($cep,5);
      $stmt2->bind_param("si", $cep_mask, $prof_id);
    }
    $stmt2->execute();
    $stmt2->close();
  }
}
$stmt->close();

// ======================= SESSÃO (EVITA “LOGAR COMO OUTRO”) =======================
session_regenerate_id(true);
$_SESSION = []; // limpa restos de sessão (ex.: id 14)

// Chaves que o auth_profissional.php e o front esperam
$_SESSION['id_profissional'] = (int)$prof_id;   // ESSENCIAL
$_SESSION['email']           = $email;
$_SESSION['nome']            = $nome;

// Espelho para o front (profilepview)
$_SESSION['logged_in'] = true;
$_SESSION['user'] = [
  'tipo'            => 'profissional',
  'id_profissional' => (int)$prof_id,
  'nome'            => $nome,
  'email'           => $email,
  'cpf'             => $cpf,
  'telefone'        => $telefone,
  'categoria'       => $categoria,
  // estes podem ser preenchidos depois pelo usuário
  'cidade'          => null,
  'site'            => null,
  'bio'             => null,
  'avatar_url'      => null,
];

// ======================= REDIRECIONA JÁ LOGADO =======================
header("Location: profilepview.html?cad=ok");
exit;
