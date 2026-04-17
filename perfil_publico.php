<?php
// perfil_publico.php — visão pública com estética do profilepview (sem edição)
// Fluxo: profissionais.php → perfil_publico.php?id=ID
session_start();
$user = $_SESSION['user'] ?? null;
$clienteId = ($user && strtolower((string)($user['tipo'] ?? '')) === 'cliente')
  ? (int)($user['id_cliente'] ?? 0)
  : 0;
require_once 'conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }


// 1) ID do profissional
$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pid <= 0) {
  echo "<script>alert('Profissional não informado.'); window.location.href='profissionais.php';</script>";
  exit;
}

// 2) Checar se existe coluna 'categoria' (para não quebrar caso não exista no schema)
$hasCategoria = false;
try {
  $chk = $conn->prepare("SELECT COUNT(*) total
                         FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME = 'perfil_profissional'
                           AND COLUMN_NAME = 'categoria'");
  $chk->execute();
  $hasCategoria = (int)($chk->get_result()->fetch_assoc()['total'] ?? 0) > 0;
  $chk->close();
} catch(Throwable $e){ $hasCategoria = false; }

// 3) Dados do profissional + perfil
$selectCategoria = $hasCategoria ? ", COALESCE(pp.categoria,'') AS categoria" : "";
$sql = "SELECT  
          p.id_profissional,
          p.nome AS nome,
          p.email,
          p.telefone,
          p.cidade,
          p.categoria,
          p.site,
          COALESCE(pp.foto_perfil, p.avatar_url, 'assets/placeholder-avatar.png') AS foto_perfil,
          COALESCE(pp.banner, '') AS banner,
          COALESCE(pp.comentario, '') AS bio
        FROM profissional p
        LEFT JOIN perfil_profissional pp 
          ON pp.profissional_id = p.id_profissional
        WHERE p.id_profissional = ?";
$st = $conn->prepare($sql);
$st->bind_param('i', $pid);
$st->execute();
$prof = $st->get_result()->fetch_assoc();
$st->close();

if(!$prof){
  echo "<script>alert('Perfil não encontrado.'); window.location.href='profissionais.php';</script>";
  exit;
}

// 4) Média e contagem de avaliações
// média e contagem oficiais (tabela 'avaliacao' com coluna 'nota')
$avaliacao = ['media'=>0.0,'qtd'=>0];
try{
  $st = $conn->prepare("
    SELECT ROUND(AVG(nota),1) AS media, COUNT(*) AS qtd
    FROM avaliacao
    WHERE profissional_id = ?
  ");
  $st->bind_param('i', $pid);
  $st->execute();
  $avaliacao = $st->get_result()->fetch_assoc() ?: $avaliacao;
  $st->close();
}catch(Throwable $e){}


// 5) Serviços (usa preco_min do seu schema; alias 'preco' para UI)
$servicos = [];
try{
  $st = $conn->prepare("SELECT id, titulo, descricao, COALESCE(preco_min,0) AS preco
                      FROM profissional_servico
                      WHERE id_profissional=? AND (ativo IS NULL OR ativo=1)
                      ORDER BY criado_em DESC, id DESC");
  $st->bind_param('i', $pid);
  $st->execute();
  $rs = $st->get_result();
  while($row = $rs->fetch_assoc()) $servicos[] = $row;
  $st->close();
}catch(Throwable $e){}

// 6) Portfólio (se existir)
$portfolio = [];
try{
  $st = $conn->prepare("SELECT id, url AS imagem, COALESCE(legenda,'') AS titulo
                      FROM profissional_portfolio
                      WHERE id_profissional=? ORDER BY criado_em DESC, id DESC");
  $st->bind_param('i', $pid);
  $st->execute();
  $rs = $st->get_result();
  while($row = $rs->fetch_assoc()) $portfolio[] = $row;
  $st->close();
}catch(Throwable $e){}

// 7) Registrar avaliação (cliente)
$mensagemAvaliacao = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'avaliar') {
  // $clienteId já foi definido no topo (seção A)
  $estrelas = (int)($_POST['estrelas'] ?? 0); // vindo do form (1..5)
  $coment   = trim($_POST['comentario'] ?? '');

  if ($clienteId <= 0) {
    $mensagemAvaliacao = 'Entre com sua conta de cliente para avaliar.';
  } elseif ($estrelas < 1 || $estrelas > 5) {
    $mensagemAvaliacao = 'Selecione de 1 a 5 estrelas.';
  } else {
    try {
      // grava ou atualiza a avaliação do mesmo cliente para este profissional
      $sql = "
        INSERT INTO avaliacao (profissional_id, cliente_id, nota, comentario, created_at)
        VALUES (?,?,?,?, NOW())
        ON DUPLICATE KEY UPDATE
          nota = VALUES(nota),
          comentario = VALUES(comentario),
          created_at = NOW()
      ";
      $st = $conn->prepare($sql);
      $st->bind_param('iiis', $pid, $clienteId, $estrelas, $coment);
      $st->execute();
      $st->close();

      // recarrega a média
      $st = $conn->prepare("SELECT ROUND(AVG(nota),1) AS media, COUNT(*) AS qtd FROM avaliacao WHERE profissional_id=?");
      $st->bind_param('i', $pid);
      $st->execute();
      $avaliacao = $st->get_result()->fetch_assoc() ?: $avaliacao;
      $st->close();

      $mensagemAvaliacao = 'Avaliação registrada com sucesso!';
    } catch (Throwable $e) {
      $mensagemAvaliacao = 'Não foi possível registrar sua avaliação agora.';
    }
  }
}
// (opcional) carregar avaliação prévia do cliente para pré-preencher o form
$minhaAval = null;
if ($clienteId > 0) {
  try {
    $st = $conn->prepare("
      SELECT nota, comentario
      FROM avaliacao
      WHERE profissional_id = ? AND cliente_id = ?
      LIMIT 1
    ");
    $st->bind_param('ii', $pid, $clienteId);
    $st->execute();
    $minhaAval = $st->get_result()->fetch_assoc() ?: null;
    $st->close();
  } catch (Throwable $e) {}
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Trampay • Perfil do Profissional</title>

  <!-- Bootstrap / Ícones -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>

  <!-- Fonts usadas no profilepview -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:opsz,wght@14..32,100..900&family=Oswald:wght@200..700&family=Poppins:wght@100;300;400;500;600;700;800;900&family=Zain:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.cdnfonts.com/css/satoshi" rel="stylesheet">

  <style>
    :root{
      --tp-blue-1:#000a25fd;
      --tp-blue-2:#000c2ccc;
      --tp-ink:#02001b;
      --tp-ink-2:#00185c;
      --tp-muted:#555;
      --tp-bg:#fdfdfd;
      --card-bg:#ffffff;
      --card-bd:#e0e0e0;
      --chip-bg:#f6f8ff;
    }
    html{ scroll-behavior:smooth; overflow-x:hidden; }
    body{ color:var(--tp-ink); background:var(--tp-bg); font-family:"Satoshi",system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }

    /* Header igual ao profilepview */
    header{ width:100%; color:#000; }
    .header-top{ position:fixed; top:0; left:0; width:100%; height:100px; padding-top:35px;
      display:flex; justify-content:space-around; align-items:center; z-index:10; transition:background-color .4s, box-shadow .4s; }
    .header-top.scrolled{ background-color:var(--tp-ink); box-shadow:0 2px 12px rgba(0,0,0,.08); }
    .navbar{ display:flex; justify-content:center; color:#fff; z-index:100000; }
    .nav-menu{ list-style:none; display:flex; gap:40px; margin-top:-20px; }
    .nav-menu a{ font-size:20px; font-family:"Bebas Neue"; color:#fff; text-decoration:none; }
    .nav-menu a:hover{ color:#ffd700; background:#00185c22; border-radius:6px; transition:color .2s, background .2s; }
    .logo{ font-family:"Bacasime Antique", serif; font-size:55px; position:absolute; left:50%; transform:translateX(-50%); margin-top:-50px; }
    .logo img{ width:250px; height:auto; }
    .icons-search{ display:flex; align-items:center; margin-left:auto; z-index:7; font-size:20px; padding-right:20px; margin-top:-10px; }
    .icons{ display:flex; align-items:center; gap:15px; color:#fff; margin-top:-20px; }
    .icons i{ font-size:25px; cursor:pointer; }

    /* Hero/Perfil */
    .hero{ padding-top:120px; }
    .hero-wrap{ background:linear-gradient(90deg, var(--tp-blue-1), var(--tp-blue-2)); color:#fff; border-radius:18px; box-shadow:0 8px 32px rgba(0,0,0,.18); padding:22px; }
    .avatar{ width:110px; height:110px; border-radius:999px; object-fit:cover; border:3px solid rgba(255,255,255,.9); box-shadow:0 10px 24px rgba(0,0,0,.35); background:#14223f; }
    .chip{ display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.24); padding:.35rem .6rem; border-radius:999px; font-size:.9rem; }

    /* Seções */
    .section{ background:var(--card-bg); border:1px solid var(--card-bd); border-radius:12px; padding:16px; box-shadow:0 4px 8px rgba(0,0,0,.06); }
    .section-title{ display:flex; align-items:center; gap:8px; font-family:"Bebas Neue"; letter-spacing:.6px; color:var(--tp-ink-2); font-size:1.4rem; margin-bottom:.25rem; }
    .muted{ color:var(--tp-muted); }
    .service-tag{ display:inline-block; background:#f6f8ff; border:1px solid #e8eaf7; border-radius:8px; padding:.35rem .55rem; font-weight:600; color:var(--tp-ink-2); }
    .btn-dark-lite{ background:#00124410; border:1px solid #00124430; color:var(--tp-ink-2); }
    .btn-dark-lite:hover{ background:#00124418; }
    .rating i{ color:#f2b01e; margin-right:2px; }

    .grid-portfolio .tile { background:var(--card-bg); border:1px solid var(--card-bd); border-radius:12px; padding:12px; height:100%; }
    .grid-portfolio img{ width:100%; height:150px; object-fit:cover; border-radius:10px; }

    .footer{ background:linear-gradient(rgba(0,0,0,.65), rgba(0,0,0,.65)), url('aboutbg.png') center/cover no-repeat; border-radius:18px; box-shadow:0 8px 32px rgba(0,0,0,.18); padding:40px 20px; color:#f4f4f4; font-family:Satoshi; font-size:1rem; margin-top:24px; }
  
   /* ===== LOGIN (estilo Trampay) ========================================== */
/* ===== MODAL DE PERFIL ===== */
.modal-perfil{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:10001; align-items:center; justify-content:center }
.modal-perfil.abrir{ display:flex }
.perfil-conteudo{
  width:min(920px,94vw);
  max-height:92vh;                    /* limita a altura */
  background:#fff; border-radius:24px; padding:0;
  box-shadow:0 20px 60px rgba(0,0,0,.35); border:1px solid #e8eaf7;
  font-family:"Satoshi",system-ui; position:relative; overflow:hidden;
  display:flex; flex-direction:column; /* layout interno em coluna */
}
.perfil-conteudo .fechar{
  position:absolute; top:12px; right:14px; font-size:28px; cursor:pointer; color:#fff; z-index:3;
  text-shadow:0 6px 18px rgba(0,0,0,.45); background:transparent; border:0
}
.perfil-header{
  flex:0 0 auto;
  display:grid; grid-template-columns:auto 1fr; column-gap:18px; row-gap:10px; align-items:center;
  padding:20px 24px 18px; background:linear-gradient(90deg,#000a25fd,#000c2ccc); color:#fff;
  box-shadow:0 8px 30px rgba(0,0,0,.2) inset
}
.avatar-wrap{ position:relative; z-index:1 }
#perfilAvatar{
  width:110px; height:110px; object-fit:cover; border-radius:999px;
  border:3px solid rgba(255,255,255,.9); box-shadow:0 10px 24px rgba(0,0,0,.35); background:#14223f
}
.trocar-foto-btn{
  position:absolute; right:-6px; bottom:-6px; display:flex; align-items:center; gap:6px;
  background:#f4f4f4; color:#00133a; border-radius:999px; padding:6px 10px; cursor:pointer;
  font-family:"Bebas Neue"; letter-spacing:.4px; box-shadow:0 10px 20px rgba(0,0,0,.25)
}
.perfil-head-info .titulo{ font-family:"Bebas Neue"; font-size:2.6rem; letter-spacing:1px; margin:0 0 4px }
.perfil-head-info .sub{ opacity:.9; margin:0; font-size:1rem }

.perfil-tabs{
  flex:0 0 auto;
  display:flex; align-items:center; gap:10px; padding:10px 14px; margin:10px 14px 0;
  background:#f6f8ff; border-radius:14px; border:1px solid #eef1ff
}
.tab-btn{
  border:none; background:#eef1ff; color:#00185c; padding:10px 16px; border-radius:999px; cursor:pointer;
  font-family:"Bebas Neue"; letter-spacing:.8px; font-size:1.05rem
}
.tab-btn.active{ background:#00133a; color:#fff }
.tab-btn.sair{ margin-left:auto; background:#e74c3c; color:#fff }

/* Conteúdo rolável do modal */
.perfil-body{ flex:1 1 auto; min-height:0; display:flex; flex-direction:column; }
.tab-container{ flex:1 1 auto; min-height:0; }
.tab-pane{ display:none; height:100%; overflow:auto; padding:14px 22px 8px }
.tab-pane.active{ display:block }

.section-wrap{ padding:2px 0 8px }
.perfil-info p{ margin:.45rem 0; font-size:1.02rem; color:#333 }
.perfil-info strong{ color:#0b156e; font-family:"Bebas Neue"; letter-spacing:.6px }
.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:14px }
@media (max-width:780px){ .grid-2{ grid-template-columns:1fr } }

.btn-row{ display:flex; gap:10px; justify-content:flex-end; margin-top:10px }
.btn{ border:none; padding:12px 18px; border-radius:12px; cursor:pointer; font-family:"Zain"; box-shadow:1px 1px 6px #00000022 }
.btn.primary{ background:linear-gradient(90deg,#000a25fd,#000c2ccc); color:#fff }
.btn.ghost{ background:#f3f3f3; color:#00185c }

.logincontainer {
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10002;
  width: min(420px, 92vw);
  padding: 28px 26px 24px;
  border-radius: 22px;
  background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.06)),
              linear-gradient(90deg, var(--azul1, #000a25fd), var(--azul2, #000c2ccc));
  box-shadow: 0 24px 64px rgba(0,0,0,.40);
  border: 1px solid rgba(255,255,255,.12);
  color: #f4f4f4;
  backdrop-filter: blur(8px);
}
.logincontainer.abrir {
  display: flex;
  flex-direction: column;
}
.logincontainer h2 {
  font-family: "Bebas Neue";
  font-size: 2rem;
  text-align: center;
  margin: 4px 0 18px;
  color: #fff;
}
.logincontainer h2::after {
  content: "";
  display: block;
  width: 58px;
  height: 3px;
  margin: 8px auto 0;
  border-radius: 999px;
  background: linear-gradient(90deg, #a3ddff, #ffffff);
  opacity: .9;
}
.logincontainer label {
  display: block;
  font-family: "Zain";
  font-size: .95rem;
  color: #d7e3ff;
  margin: 10px 2px 6px;
}
.logincontainer input {
  width: 100%;
  padding: 12px 10px 10px;
  border: none;
  border-bottom: 2px solid rgba(255,255,255,.28);
  border-radius: 10px 10px 0 0;
  background: rgba(255,255,255,.06);
  color: #fff;
  font-size: 1rem;
  outline: none;
}
.logincontainer button {
  width: 100%;
  padding: 14px 16px;
  margin-top: 10px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  font-size: 1.02rem;
  font-weight: 700;
  font-family: "Zain";
  color: #fff;
  background: linear-gradient(90deg, var(--azul1, #000a25fd), var(--azul2, #000c2ccc));
  box-shadow: 0 10px 24px rgba(0,0,0,.28);
}
.forgot-password {
  margin: 12px 0 2px;
  font-size: .86rem;
  color: #d7e3ff;
  text-decoration: none;
}


/* Trava o scroll do body quando modal abrir */
body.modal-open{ overflow:hidden }


/* Formulários */
.form-group{ display:flex; flex-direction:column; gap:6px; }
.form-group label{
  font-family:"Zain", system-ui;
  font-size:1rem;
  color:#00185c;
}
.form-group input{
  border:none;
  border-bottom:2px solid #d3d3d3;
  padding:10px 6px;
  outline:none;
  background:transparent;
  font-size:1rem;
  color:#111;
}
.form-group input::placeholder{ color:#9aa1b6; }
.form-group input:focus{
  border-color:#00185c;
  box-shadow:0 1px 0 0 #00185c;
}

.divider{
  margin:14px 0 8px; padding:8px 0;
  font-family:"Bebas Neue", system-ui;
  color:#02001b; letter-spacing:.8px; font-size:1.2rem;
  border-top:1px dashed #e3e3e3;
}

/* Botões */
.btn-row{ display:flex; gap:10px; justify-content:flex-end; margin-top:10px; flex-wrap:wrap; }
.btn{
  border:none;
  padding:12px 18px;
  border-radius:12px;
  cursor:pointer;
  font-family:"Zain", system-ui;
  box-shadow:1px 1px 6px #00000022;
  transition:transform .08s ease, filter .12s ease;
}
.btn:hover{ transform:translateY(-1px); }
.btn:focus-visible{ outline:2px solid #ffd777; outline-offset:2px; }
.btn.primary{
  background:linear-gradient(90deg,#000a25,#000c2c);
  color:#fff;
}
.btn.ghost{
  background:#f3f3f3;
  color:#00185c;
}

/* Preferência do usuário: menos animação */
@media (prefers-reduced-motion: reduce){
  *{ transition:none !important; animation:none !important; }
}
  
/* ===== Estrelas clicáveis ===== */
.tp-stars { display:inline-flex; gap:.25rem; user-select:none; }
.tp-star {
  font-size: 1.8rem; line-height:1; cursor:pointer;
  border:none; background:transparent; padding:.1rem .2rem;
  transition: transform .08s ease-in-out;
}
.tp-star:focus { outline: 2px solid #ffd76a; outline-offset: 2px; }
.tp-star[data-active="true"] { transform: scale(1.02); }
/* cheia x vazia por opacidade no mesmo glifo */
.tp-star .icon { opacity:.35; }
.tp-star.filled .icon { opacity:1; }


  </style>
</head>
<body>
  <!-- HEADER (idêntico ao profilepview; sem gatilhos de edição) -->
  <header>
    <div class="header-top" id="headerTop">
      <nav class="navbar">
        <ul class="nav-menu">
          <li><a href="homepage.html">INICÍO</a></li>
          <li><a href="sobre.html">SOBRE NÓS</a></li>
          <li><a href="profissionais.php">SERVIÇOS</a></li>
        </ul>
      </nav>
      <div class="logo">
        <a href="homepage.html" aria-label="Trampay - Início"><img src="logo.png" alt="logo"></a>
      </div>
      <div class="icons-search">
        <div class="icons">
          <a class="icons" id="btnPerfil" href="#"><i class="bi bi-person"></i></a>
        </div>

         <!-- ===== MODAL LOGIN (visual igual delivery) ===== -->
<div class="logincontainer" id="loginContainer">
  <h2>Login</h2>
  <form id="loginForm" method="POST">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required>

    <button type="submit">Entrar</button>
  </form>

  <a href="esqueci_senha_cliente.html" class="forgot-password">Esqueci minha senha</a>
  <p style="color:#d3d3d3; margin-top:12px; font-size:.9rem">
    Não tem conta? 
    <a href="cadcliente.html" style="color:#fff; text-decoration:none">Cadastre-se</a>
  </p>
</div>




<!-- ===== MODAL PERFIL ===== -->
<div class="modal-perfil" id="modalPerfil">
  <div class="perfil-conteudo" role="dialog" aria-modal="true">
    <button class="fechar" id="fecharPerfil" aria-label="Fechar">&times;</button>

    <div class="perfil-header">
      <div class="avatar-wrap">
        <img id="perfilAvatar" src="avatar.png" alt="Foto do Perfil">
        <label for="inputFoto" class="trocar-foto-btn"><i class="bi bi-camera"></i><span>Trocar foto</span></label>
        <input id="inputFoto" type="file" accept="image/*" hidden>
      </div>
      <div class="perfil-head-info">
        <p class="titulo" id="perfilHandle">@perfil</p>
        <p class="">Gerencie seus dados</p>
      </div>
    </div>

    <div class="perfil-body">
      <div class="perfil-tabs">
        <button class="tab-btn active" data-tab="visao">Resumo</button>
        <button class="tab-btn" data-tab="editar">Editar</button>
        <button class="tab-btn sair" id="btnSair">Sair</button>
      </div>

      <div class="tab-container">
        <div class="tab-pane active" id="tab-visao">
          <div class="section-wrap perfil-info">
            <p><strong>Nome:</strong> <span id="perfilNome">Usuário</span></p>
            <p><strong>E-mail:</strong> <span id="perfilEmail">usuario@exemplo.com</span></p>
            <p><strong>Tipo:</strong> <span id="perfilTipo">Cliente</span></p>
          </div>
        </div>

        <div class="tab-pane" id="tab-editar">
          <form id="formEditarPerfil" class="section-wrap" method="post" autocomplete="off">
            <div class="grid-2">
              <div class="form-group">
                <label for="editNome">Nome</label>
                <input type="text" id="editNome" name="nome" placeholder="Seu nome completo">
              </div>
              <div class="form-group">
                <label for="editEmail">E-mail</label>
                <input type="email" id="editEmail" name="email" placeholder="seu@email.com">
              </div>
            </div>

            <h4 class="divider" style="margin:18px 0 6px; font-family:'Bebas Neue'; letter-spacing:.6px; color:#0b156e">Segurança</h4>
            <div class="grid-2">
              <div class="form-group">
                <label for="editSenha">Nova senha</label>
                <input type="password" id="editSenha" name="senha" placeholder="••••••••">
              </div>
              <div class="form-group">
                <label for="editSenha2">Confirmar nova senha</label>
                <input type="password" id="editSenha2" name="senha2" placeholder="••••••••">
              </div>
            </div>

            <div class="btn-row">
              <button type="button" class="btn ghost" id="btnCancelarEdicao">Cancelar</button>
              <button type="submit" class="btn primary">Salvar alterações</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <main class="container hero">
    <div class="hero-wrap">
      <div class="d-flex gap-3 align-items-center">
        <img class="avatar" src="<?= esc($prof['foto_perfil']) ?>" alt="avatar">
        <div>
          <h2 class="mb-1" style="font-family:'Bebas Neue'; letter-spacing:1px; font-size:2.2rem;"><?= esc($prof['nome']) ?></h2>
          <div class="small" style="opacity:.9">@<?= strtolower(preg_replace('/\s+/','_', iconv('UTF-8','ASCII//TRANSLIT',$prof['nome']))) ?></div>
        <div class="d-flex gap-2 mt-2 flex-wrap">
  <?php if (!empty($prof['cidade'])): ?>
    <span class="chip"><i class="bi bi-geo-alt"></i><span><?= esc($prof['cidade']) ?></span></span>
  <?php endif; ?>

  <span class="chip"><i class="bi bi-envelope"></i><span><?= esc($prof['email']) ?></span></span>

  <?php if (!empty($prof['telefone'])): ?>
    <span class="chip"><i class="bi bi-phone"></i><span><?= esc($prof['telefone']) ?></span></span>
  <?php endif; ?>

  <?php if (!empty($prof['categoria'])): ?>
    <span class="chip"><i class="bi bi-tags"></i><span><?= esc($prof['categoria']) ?></span></span>
  <?php endif; ?>

  <?php if (!empty($prof['site'])): ?>
    <a class="chip" href="<?= esc($prof['site']) ?>" target="_blank" rel="noopener">
      <i class="bi bi-globe"></i><span><?= esc($prof['site']) ?></span>
    </a>
  <?php endif; ?>
</div>
        </div>
        <div class="ms-auto text-end">
          <div class="mb-1">
            <span class="chip"><i class="bi bi-star-fill"></i> <?= number_format((float)$avaliacao['media'],1,',','.') ?></span>
            <span class="chip"><?= (int)$avaliacao['qtd'] ?> avaliações</span>
          </div>

        </div>
      </div>

      <div class="row g-3 mt-2">
        <div class="col-lg-8">
          <div class="section">
            <div class="section-title"><i class="bi bi-person-lines-fill"></i> Sobre mim</div>
            <div class="muted" style="white-space:pre-wrap;"><?= nl2br(esc($prof['bio'])) ?></div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="section">
            <div class="section-title"><i class="bi bi-calendar2-week"></i> Agenda</div>
            <div class="muted small mb-2">Escolha um serviço e prossiga para combinar horários.</div>
            <a class="btn btn-sm btn-dark-lite"
   href="agenda.php?profissional_id=<?= (int)$prof['id_profissional'] ?>&from=perfil_publico">
  <i class="bi bi-calendar-check"></i> Solicitar agendamento
</a>
          </div>
        </div>
      </div>
    </div>

<script>

  /* ===== Helpers ===== */
function bodyLock(lock){ document.body.classList.toggle('modal-open', !!lock); }
function $(sel, root=document){ return root.querySelector(sel); }
function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

/* ===== Estado global simples ===== */
let USER_ATUAL = null;

/* ===== Abre Perfil: se logado -> modal perfil; senão -> login ===== */
async function abrirPerfil(){
  try{
    const r = await fetch('auth.php?action=status', { credentials:'include' });
    const data = await r.json();
    if(data && data.logged_in){
      USER_ATUAL = data.user || null;
      preencherPerfilUI(USER_ATUAL);
      $('#modalPerfil').classList.add('abrir'); bodyLock(true);
    }else{
      $('#loginContainer').classList.add('abrir'); bodyLock(true);
    }
  }catch(e){
    console.error('status error', e);
    $('#loginContainer').classList.add('abrir'); bodyLock(true);
  }
}

/* ===== Preencher UI com dados do usuário ===== */
function preencherPerfilUI(u){
  if(!u) return;
  $('#perfilHandle').textContent = '@' + (u.nome || 'usuario').toLowerCase().replace(/\s+/g,'');
  $('#perfilNome').textContent = u.nome || '';
  $('#perfilEmail').textContent = u.email || '';
  $('#perfilTipo').textContent = (u.tipo || u.user_type || 'Cliente');
  $('#editNome').value = u.nome || '';
  $('#editEmail').value = u.email || '';
  if(u.foto_perfil) $('#perfilAvatar').src = u.foto_perfil;
}

/* ===== Eventos de abrir/fechar ===== */
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('#btnPerfil');
  if(btn){ e.preventDefault(); abrirPerfil(); }
});

$('#fecharPerfil')?.addEventListener('click', ()=>{ $('#modalPerfil').classList.remove('abrir'); bodyLock(false); });
$('#modalPerfil')?.addEventListener('click', (ev)=>{ if(ev.target === $('#modalPerfil')){ $('#modalPerfil').classList.remove('abrir'); bodyLock(false); } });

/* ===== Tabs ===== */
$all('.perfil-tabs .tab-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    if(btn.dataset.tab){
      $all('.perfil-tabs .tab-btn').forEach(b=>b.classList.remove('active'));
      $all('.tab-pane').forEach(p=>p.classList.remove('active'));
      btn.classList.add('active');
      $('#tab-'+btn.dataset.tab)?.classList.add('active');
    }
  });
});

/* ===== Login via POST (evita ?nome=... na URL) ===== */
$('#loginForm')?.addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  const fd = new FormData(ev.currentTarget);
  try{
    const r = await fetch('auth.php?action=login', { method:'POST', body: fd, credentials:'include' });
    const data = await r.json();
    if(data && data.ok){
      // re-checa status e abre perfil preenchido
      const s = await fetch('auth.php?action=status', { credentials:'include' });
      const st = await s.json();
      USER_ATUAL = st.user || null;
      preencherPerfilUI(USER_ATUAL);
      $('#loginContainer').classList.remove('abrir');
      $('#modalPerfil').classList.add('abrir');
      bodyLock(true);
    }else{
      alert(data?.msg || 'Não foi possível entrar.');
    }
  }catch(e){ console.error(e); alert('Erro ao tentar logar.'); }
});

/* ===== Logout ===== */
$('#btnSair')?.addEventListener('click', async ()=>{
  try{
    await fetch('auth.php?action=logout', { method:'POST', credentials:'include' });
  }catch(_){}
  USER_ATUAL = null;
  $('#modalPerfil').classList.remove('abrir');
  bodyLock(false);
});

/* ===== Opcional: marcar ícone como logado no header ===== */
async function marcarHeaderSeLogado(){
  try{
    const r = await fetch('auth.php?action=status', { credentials:'include' });
    const data = await r.json();
    if(data?.logged_in){
      USER_ATUAL = data.user || null;
      const btn = document.getElementById('btnPerfil');
      if(btn) btn.innerHTML = '<i class="bi bi-person-check"></i>';
    }
  }catch(_){}
}
marcarHeaderSeLogado();

function abrirLogin(){
  document.getElementById('loginContainer')?.classList.add('abrir');
}
function fecharLogin(){
  document.getElementById('loginContainer')?.classList.remove('abrir');
}

// Exemplo: botão para abrir (adicione onde quiser)
document.querySelectorAll('[data-open-login]').forEach(btn=>{
  btn.addEventListener('click', e=>{
    e.preventDefault();
    abrirLogin();
  });
});

// Fechar ao clicar fora do modal
document.addEventListener('click', e=>{
  const modal = document.getElementById('loginContainer');
  if(!modal) return;
  if(e.target === modal){ fecharLogin(); }
});


const PROF_ID = <?= (int)$prof['id_profissional'] ?>;


</script>

    <!-- Seções públicas -->
    <div class="row g-4 mt-3">
<!-- Serviços -->
<div class="col-lg-6">
  <div class="section">
    <div class="d-flex justify-content-between align-items-center">
      <div class="section-title"><i class="bi bi-tools"></i> Serviços</div>
    </div>
    <div class="row g-3 mt-1">
      <?php if(!$servicos): ?>
        <div class="col-12 muted small">Nenhum serviço cadastrado ainda.</div>
      <?php else: foreach($servicos as $s): ?>
        <div class="col-md-6">
          <div class="grid-portfolio tile h-100">
            <div class="service-tag"><i class="bi bi-tools"></i> <?= esc($s['titulo']) ?></div>
            <p class="muted mt-2" style="white-space:pre-wrap"><?= nl2br(esc($s['descricao'])) ?></p>

            <div class="d-flex justify-content-between align-items-center">
  <small class="text-muted">
    <i class="bi bi-cash-coin"></i>
    R$ <?= number_format((float)$s['preco'], 2, ',', '.') ?>
  </small>

  <!-- Botão "Contratar" estilo Trampay -->
  <a
    href="agenda.php?profissional_id=<?= (int)$prof['id_profissional'] ?>&servico_id=<?= (int)$s['id'] ?>"
    class="btn btn-sm text-white fw-semibold"
    style="background:linear-gradient(135deg,#001b50,#0033a0);
           border:none;
           border-radius:999px;
           padding:.4rem 1.1rem;
           transition:.3s;"
    onclick="return confirm('Deseja contratar este serviço e prosseguir para agendar?');"
  >
    <i class="bi bi-calendar2-check"></i> Contratar
  </a>
</div>


          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>


      <!-- Portfólio -->
      <div class="col-lg-6">
        <div class="section">
          <div class="d-flex justify-content-between align-items-center">
            <div class="section-title"><i class="bi bi-images"></i> Portfólio</div>
          </div>
          <div class="row g-3 grid-portfolio mt-1">
            <?php if(!$portfolio): ?>
              <div class="col-12 muted small">Este profissional ainda não publicou imagens no portfólio.</div>
            <?php else: foreach($portfolio as $pf): ?>
              <div class="col-md-6 col-6">
                <div class="tile">
                  <img src="<?= esc($pf['imagem']) ?>" alt="<?= esc($pf['titulo']) ?>" onerror="this.src='assets/placeholder-image.png'">
                  <?php if(!empty($pf['titulo'])): ?>
                    <div class="small text-muted mt-2"><?= esc($pf['titulo']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <!-- Avaliações (listagem + formulário) -->
      <div class="col-12">
        <div class="section">
          <div class="section-title"><i class="bi bi-chat-left-quote"></i> Avaliações</div>

          <?php
          // Carregar últimas avaliações (somente leitura)
$reviews = [];
try{
  $st = $conn->prepare("
    SELECT nota, comentario, created_at
    FROM avaliacao
    WHERE profissional_id = ?
    ORDER BY id DESC
    LIMIT 20
  ");
  $st->bind_param('i', $pid);
  $st->execute();
  $rs = $st->get_result();
  while($row = $rs->fetch_assoc()) $reviews[] = $row;
  $st->close();
}catch(Throwable $e){}

          ?>

          <?php if(!$reviews): ?>
            <div class="muted small mb-3">Sem avaliações ainda.</div>
          <?php else: ?>
            <div class="d-flex flex-column gap-3 mb-3">
              <?php foreach($reviews as $rv): ?>
                <div class="grid-portfolio tile">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong>Cliente</strong>
                    <span class="badge text-bg-warning">
  <i class="bi bi-star-fill"></i> <?= number_format((float)$rv['nota'],1,',','.') ?>
</span>
                  </div>
                  <div class="muted" style="white-space:pre-wrap"><?= nl2br(esc($rv['comentario'])) ?></div>
                  <small class="text-muted"><?= esc($rv['created_at']) ?></small>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

<!-- Form de avaliação (estrelas clicáveis) -->
<?php if($mensagemAvaliacao): ?>
  <div class="alert alert-<?= stripos($mensagemAvaliacao,'sucesso')!==false?'success':'warning' ?>">
    <?= esc($mensagemAvaliacao) ?>
  </div>
<?php endif; ?>

<?php if($clienteId > 0): ?>
  <?php $notaInicial = isset($minhaAval['nota']) ? (int)$minhaAval['nota'] : 0; ?>

  <form method="post" class="row g-3" id="form-avaliacao">
    <input type="hidden" name="acao" value="avaliar">
    <input type="hidden" name="estrelas" id="input-estrelas" value="<?= $notaInicial ?>">

    <div class="col-12">
      <label class="form-label d-block">Sua avaliação</label>

      <div class="tp-stars" id="tp-stars" role="radiogroup" aria-label="Avaliação por estrelas">
        <?php for($i=1;$i<=5;$i++): ?>
          <button
            type="button"
            class="tp-star <?= $notaInicial >= $i ? 'filled':'' ?>"
            data-value="<?= $i ?>"
            role="radio"
            aria-checked="<?= $notaInicial===$i ? 'true':'false' ?>"
            title="<?= $i ?> estrela<?= $i>1?'s':'' ?>"
          >
            <span class="icon">★</span>
          </button>
        <?php endfor; ?>
      </div>

      <div class="form-text">
        Clique para escolher de 1 a 5 estrelas.
        <?php if($notaInicial>0): ?>
          (Você já havia dado <strong><?= $notaInicial ?></strong> estrela<?= $notaInicial>1?'s':'' ?> — reenviar atualiza.)
        <?php endif; ?>
      </div>
    </div>

    <div class="col-12">
      <label class="form-label">Comentário (opcional)</label>
      <textarea name="comentario" class="form-control" rows="3" placeholder="Conte como foi sua experiência..."><?= esc($minhaAval['comentario'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
      <button class="btn btn-success" type="submit">
        <i class="bi bi-star"></i> Enviar avaliação
      </button>
    </div>
  </form>

  <script>
    (function(){
      const wrap  = document.getElementById('tp-stars');
      const input = document.getElementById('input-estrelas');
      if(!wrap || !input) return;

      const stars = Array.from(wrap.querySelectorAll('.tp-star'));
      let current = parseInt(input.value || '0', 10) || 0;

      function paint(n){
        stars.forEach((btn, idx)=>{
          const v = idx + 1;
          btn.classList.toggle('filled', v <= n);
          btn.setAttribute('aria-checked', (v === n) ? 'true' : 'false');
          btn.dataset.active = (v === n) ? 'true' : 'false';
        });
      }
      paint(current);

      stars.forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const v = parseInt(btn.dataset.value, 10);
          current = v;
          input.value = String(v);
          paint(v);
        });
        // acessibilidade via teclado
        btn.addEventListener('keydown', (e)=>{
          if(e.key === 'ArrowRight' || e.key === 'ArrowUp'){
            e.preventDefault();
            current = Math.min(5, (current||0) + 1);
            input.value = String(current);
            stars[Math.max(0, current-1)].focus();
            paint(current);
          } else if(e.key === 'ArrowLeft' || e.key === 'ArrowDown'){
            e.preventDefault();
            current = Math.max(1, (current||0) - 1);
            input.value = String(current);
            stars[Math.max(0, current-1)].focus();
            paint(current);
          } else if(e.key === 'Home'){
            e.preventDefault(); current = 1; input.value='1'; paint(1); stars[0].focus();
          } else if(e.key === 'End'){
            e.preventDefault(); current = 5; input.value='5'; paint(5); stars[4].focus();
          } else if(e.key === ' ' || e.key === 'Enter'){
            e.preventDefault();
            const v = parseInt(btn.dataset.value, 10);
            current = v; input.value=String(v); paint(v);
          }
        });
      });

      // validação simples no submit
      const form = document.getElementById('form-avaliacao');
      form?.addEventListener('submit', (e)=>{
        const val = parseInt(input.value || '0', 10) || 0;
        if(val < 1 || val > 5){
          e.preventDefault();
          alert('Selecione de 1 a 5 estrelas antes de enviar.');
        }
      });
    })();
  </script>

<?php else: ?>
  <p class="text-muted mb-0">
    Você precisa entrar com sua conta de cliente para avaliar.
    <a href="#" data-open-login>Fazer login</a>
  </p>
<?php endif; ?>



    </div>
  </main>

  <footer class="container footer mt-4">
    <div class="text-center">© <span id="ano"></span> Trampay — Todos os direitos reservados</div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // Header com efeito de scroll (mesmo do profilepview)
    (function(){
      const top = document.getElementById('headerTop');
      function onScroll(){ if(window.scrollY>10) top.classList.add('scrolled'); else top.classList.remove('scrolled'); }
      onScroll(); window.addEventListener('scroll', onScroll);
      document.getElementById('ano').textContent = new Date().getFullYear();
    })();


    document.querySelectorAll('[data-open-login]').forEach(el => {
  el.addEventListener('click', e => {
    e.preventDefault();
    abrirLogin();
  });
});
    
  </script>
</body>
</html>
