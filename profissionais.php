<?php
// profissionais.php
// Visual padrão Trampay + listagem de profissionais com filtro opcional por categoria

mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: text/html; charset=utf-8');

// ====== CONEXÃO ======
$host = "localhost";
$user = "root";
$pass = "";
$db   = "trampay";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
  http_response_code(500);
  echo "Erro de conexão: " . $conn->connect_error;
  exit;
}
$conn->set_charset("utf8mb4");

// ====== DETECTA SE EXISTE COLUNA 'categoria' EM perfil_profissional ======
$hasCategoria = false;
$colCheckSql = "
  SELECT COUNT(*) AS total
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'perfil_profissional'
    AND COLUMN_NAME = 'categoria'
";
if ($rs = $conn->query($colCheckSql)) {
  $row = $rs->fetch_assoc();
  $hasCategoria = !empty($row['total']) && intval($row['total']) > 0;
  $rs->free();
}

// ===== CATEGORIAS FIXAS (combo) =====
$CATS_FIXAS = [
  'Manutenção automotiva',
  'Reparos domésticos',
  'Design e Criação',
  'Estética e Beleza',
  'Tecnologia',
  'Aulas e Educação',
  'Saúde',
  'Mudanças e Reformas',
];

// ====== INPUT FILTRO ======
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : "";

// ====== CONSULTA PROFISSIONAIS ======
$baseSql = "
  SELECT 
    p.id_profissional,
    p.nome AS nome_profissional,
    p.email AS email_profissional,
    p.categoria,                 -- <- vem de profissional
    COALESCE(pp.foto_perfil, p.avatar_url, 'assets/placeholder-avatar.png') AS foto_perfil,
    pp.comentario
  FROM profissional p
  LEFT JOIN perfil_profissional pp 
         ON pp.profissional_id = p.id_profissional
";

$params = [];
$types  = "";
$where  = "";

if ($categoria !== "" && $categoria !== "todos") {
  $where = " WHERE p.categoria = ? ";  // <- usa p.categoria
  $params[] = $categoria;
  $types   .= "s";
}

$order = " ORDER BY p.id_profissional DESC";
$sql   = $baseSql . $where . $order;

$stmt = $conn->prepare($sql);
if (!$stmt) {
  // Mostra na tela em vez de quebrar a página
  echo "<div style='margin:20px' class='alert alert-danger'>Erro no prepare(): " . h($conn->error) . "</div>";
  $profissionais = [];
} else {
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $profissionais = [];
  while ($row = $res->fetch_assoc()) {
    $profissionais[] = $row;
  }
  $stmt->close();
}


function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Trampay • Profissionais</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap e ícones -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <!-- Fonts usadas na homepage -->
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
    html, body { margin:0; padding:0; }
    body{ color:var(--tp-ink); background:var(--tp-bg); font-family:"Satoshi", system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; }

    /* HEADER igual à homepage */
    header{ width:100%; }
    .header-top{ position:fixed; top:0; left:0; width:100%; height:100px; padding-top:35px; display:flex; justify-content:space-around; align-items:center; z-index:10; transition: background-color .4s, box-shadow .4s; }
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

    /* Conteúdo */
    .page{ padding-top:130px; }
    .hero-wrap{ background:linear-gradient(90deg, var(--tp-blue-1), var(--tp-blue-2)); color:#fff; border-radius:18px; box-shadow:0 8px 32px rgba(0,0,0,.18); padding:24px; margin-bottom:18px; }
    .hero-wrap h1{ margin:0; font-family:"Bebas Neue"; letter-spacing:1px; }
    .hero-wrap p{ margin:.25rem 0 0; opacity:.9; }

    .filter-bar{ background:#fff; border:1px solid var(--card-bd); border-radius:12px; padding:12px; box-shadow:0 4px 8px rgba(0,0,0,.06); }
    .form-select{ background:#fff; border-color:#dfe3f0; }
    .form-select:focus{ border-color:#00195f; box-shadow:0 0 0 .2rem rgba(0,25,95,.08); }

    .grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:18px; margin-top:14px; }
    .card-prof{ background:var(--card-bg); border:1px solid var(--card-bd); border-radius:14px; padding:14px; box-shadow:0 4px 8px rgba(0,0,0,.06); display:flex; gap:12px; }
    .avatar{ width:68px; height:68px; border-radius:999px; object-fit:cover; background:#14223f; border:2px solid rgba(0,0,0,.06); }
    .name{ font-weight:700; color:var(--tp-ink-2); margin:0; }
    .email{ font-size:.92rem; color:#333; margin:0 0 6px; word-break: break-all; }
    .bio{ font-size:.92rem; color:var(--tp-muted); margin:0; }
    .chip{ display:inline-block; background:#f6f8ff; border:1px solid #e8eaf7; border-radius:999px; padding:.22rem .55rem; font-size:.8rem; color:var(--tp-ink-2); font-weight:600; }

    .footer{ background:linear-gradient(rgba(0,0,0,.65), rgba(0,0,0,.65)), url('aboutbg.png') center/cover no-repeat; border-radius:18px; box-shadow:0 8px 32px rgba(0,0,0,.18); padding:40px 20px; color:#f4f4f4; font-family:Satoshi; font-size:1rem; margin-top:24px; text-align:center; }
  
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
  
  </style>
</head>
<body>
  <!-- Header igual homepage -->
  <header>
    <div class="header-top" id="headerTop">
      <nav class="navbar">
        <ul class="nav-menu">
          <li><a href="sobre.html">SOBRE NÓS</a></li>
          <li><a href="profissionais.php">SERVIÇOS</a></li>
        </ul>
      </nav>
      <div class="logo">
        <a href="homepage.html" aria-label="Trampay - Início"><img src="logo.png" alt="logo"></a>
      </div>
     <div class="icons-search">
  <div class="icons">
    <!-- Ícone abre modal de login/perfil -->
   <a class="icons" id="btnPerfil" href="#" title="Meu perfil">
  <i class="bi bi-person"></i>
</a>
  </div>
</div>   
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

  <main class="container page">
    <div class="hero-wrap mb-3">
      <h1>Profissionais</h1>
      <p>Encontre profissionais verificados na plataforma.</p>
    </div>

<form method="get" action="profissionais.php" class="cat-filter" 
      style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
  <select name="categoria" aria-label="Filtrar por categoria"
          style="padding:10px 12px; border-radius:12px; border:1px solid #dfe3ff;"
          onchange="this.form.submit()">
    <option value="">Todas as categorias</option>
    <?php foreach ($CATS_FIXAS as $opt): ?>
      <option value="<?= h($opt) ?>" <?= ($opt === $categoria ? 'selected' : '') ?>>
        <?= h($opt) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <!-- sem botão: o onchange já envia -->
</form>


 <?php if (empty($profissionais)): ?>
  <div class="alert alert-warning">Nenhum profissional encontrado.</div>
<?php else: ?>

      <div class="grid">
        <?php foreach ($profissionais as $p): 
          $foto = !empty($p['foto_perfil']) ? $p['foto_perfil'] : 'assets/placeholder-avatar.png';
          $bio  = !empty($p['comentario']) ? $p['comentario'] : 'Perfil ainda sem descrição.';
        ?>
          <div class="card-prof">
            <img class="avatar" src="<?=h($foto)?>" alt="foto de <?=h($p['nome_profissional'] ?: 'Profissional')?>">
            <div class="flex-fill">
              <p class="name"><?=h($p['nome_profissional'] ?: 'Profissional')?></p>
              <p class="email"><?=h($p['email_profissional'] ?: '—')?></p>
              <p class="bio"><?=h($bio)?></p>
              <div class="mt-2 d-flex gap-2 flex-wrap">
                <?php if ($hasCategoria && !empty($p['categoria'])): ?>
                  <span class="chip"><i class="bi bi-tag"></i> <?=h($p['categoria'])?></span>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-primary" href="perfil_publico.php?id=<?= (int)$p['id_profissional'] ?>">
  <i class="bi bi-eye"></i> Ver perfil
</a>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <footer class="footer mt-4">
      © <span id="ano"></span> Trampay — Todos os direitos reservados
    </footer>
  </main>

  <script>
    // Header com fundo no scroll (igual homepage)
    (function(){
      const top = document.getElementById('headerTop');
      function onScroll(){ if(window.scrollY>10) top.classList.add('scrolled'); else top.classList.remove('scrolled'); }
      onScroll(); window.addEventListener('scroll', onScroll);
      document.getElementById('ano').textContent = new Date().getFullYear();
    })();

/* ===== Helpers ===== */
function bodyLock(lock){ document.body.classList.toggle('modal-open', !!lock); }
function $(sel, root=document){ return root.querySelector(sel); }
function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

/* ===== Estado global ===== */
let USER_ATUAL = null;

/* ===== Preencher UI do perfil ===== */
function preencherPerfilUI(u){
  if(!u) return;
  const handle = (u.handle && u.handle.trim()) ? u.handle : (u.nome || 'usuario').toLowerCase().replace(/\s+/g,'');
  $('#perfilHandle') && ($('#perfilHandle').textContent = '@' + handle);
  $('#perfilNome')   && ($('#perfilNome').textContent   = u.nome  || '');
  $('#perfilEmail')  && ($('#perfilEmail').textContent  = u.email || '');
  $('#perfilTipo')   && ($('#perfilTipo').textContent   = (u.tipo || u.user_type || 'Cliente'));
  $('#editNome')     && ($('#editNome').value           = u.nome  || '');
  $('#editEmail')    && ($('#editEmail').value          = u.email || '');
  if(u.foto_perfil && $('#perfilAvatar')) $('#perfilAvatar').src = u.foto_perfil;
}

/* ===== Abrir Perfil: se logado -> modal perfil; senão -> login ===== */
async function abrirPerfil(){
  try{
    const r = await fetch('auth.php?action=status', { credentials:'include' });
    const data = await r.json();
    if(data?.logged_in){
      USER_ATUAL = data.user || null;
      preencherPerfilUI(USER_ATUAL);
      $('#modalPerfil')?.classList.add('abrir'); bodyLock(true);
    }else{
      $('#loginContainer')?.classList.add('abrir'); bodyLock(true);
    }
  }catch(e){
    console.error('status error', e);
    $('#loginContainer')?.classList.add('abrir'); bodyLock(true);
  }
}

/* ===== Eventos de abrir/fechar Perfil ===== */
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('#btnPerfil');
  if(btn){ e.preventDefault(); abrirPerfil(); }
});

$('#fecharPerfil')?.addEventListener('click', ()=>{
  $('#modalPerfil')?.classList.remove('abrir'); bodyLock(false);
});
$('#modalPerfil')?.addEventListener('click', (ev)=>{
  if(ev.target === $('#modalPerfil')){ $('#modalPerfil')?.classList.remove('abrir'); bodyLock(false); }
});

/* ===== Tabs do perfil ===== */
$all('.perfil-tabs .tab-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    if(!btn.dataset.tab) return;
    $all('.perfil-tabs .tab-btn').forEach(b=>b.classList.remove('active'));
    $all('.tab-pane').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    $('#tab-'+btn.dataset.tab)?.classList.add('active');
  });
});

/* ===== Login via modal (POST) ===== */
const loginForm = $('#loginForm');
if(loginForm){
  loginForm.addEventListener('submit', async (ev)=>{
    ev.preventDefault();
    const btn = loginForm.querySelector('button[type="submit"]');
    const fd  = new FormData(loginForm);

    try{
      btn && (btn.disabled = true, btn.textContent = 'Entrando...');

      // Usa alias "login" -> login_cliente no seu auth.php
      const r = await fetch('auth.php?action=login', { method:'POST', body: fd, credentials:'include' });
      const data = await r.json();

      if(data?.ok){
        // pega dados atualizados
        const s = await fetch('auth.php?action=status', { credentials:'include' });
        const st = await s.json();
        USER_ATUAL = st?.user || data?.user || null;

        preencherPerfilUI(USER_ATUAL);

        // UI: fecha login, abre perfil e trava o scroll
        $('#loginContainer')?.classList.remove('abrir');
        $('#modalPerfil')?.classList.add('abrir');
        bodyLock(true);

        // troca ícone do header
        const btnPerfil = document.getElementById('btnPerfil');
        if(btnPerfil) btnPerfil.innerHTML = '<i class="bi bi-person-check"></i>';
      }else{
        alert(data?.msg || 'Email ou senha inválidos.');
      }
    }catch(e){
      console.error(e);
      alert('Erro ao tentar logar.');
    }finally{
      btn && (btn.disabled = false, btn.textContent = 'Entrar');
    }
  });
}

/* ===== Logout (só fecha modais, não redireciona) ===== */
$('#btnSair')?.addEventListener('click', async ()=>{
  try{ await fetch('auth.php?action=logout', { method:'POST', credentials:'include' }); }catch(_){}
  USER_ATUAL = null;
  $('#modalPerfil')?.classList.remove('abrir');
  bodyLock(false);
  const btnPerfil = document.getElementById('btnPerfil');
  if(btnPerfil) btnPerfil.innerHTML = '<i class="bi bi-person"></i>';
});

/* ===== Marcar ícone no header se já estiver logado ===== */
(async function marcarHeaderSeLogado(){
  try{
    const r = await fetch('auth.php?action=status', { credentials:'include' });
    const data = await r.json();
    if(data?.logged_in){
      USER_ATUAL = data.user || null;
      const btn = document.getElementById('btnPerfil');
      if(btn) btn.innerHTML = '<i class="bi bi-person-check"></i>';
    }
  }catch(_){}
})();

/* ===== Abrir/Fechar Login manualmente (se precisar em outros botões) ===== */
function abrirLogin(){ $('#loginContainer')?.classList.add('abrir'); bodyLock(true); }
function fecharLogin(){ $('#loginContainer')?.classList.remove('abrir'); bodyLock(false); }
document.querySelectorAll('[data-open-login]').forEach(btn=>{
  btn.addEventListener('click', e=>{ e.preventDefault(); abrirLogin(); });
});
document.addEventListener('click', e=>{
  const modal = document.getElementById('loginContainer');
  if(!modal) return;
  if(e.target === modal){ fecharLogin(); }
});

/* ===== BLOQUEAR ida para cliente_editar.php: sempre usar o modal ===== */
$all('a[href*="cliente_editar.php"]').forEach(a=>{
  a.addEventListener('click', (e)=>{
    e.preventDefault();
    abrirPerfil(); // checa status e abre o modal certo
  });
});
      
  </script>
</body>
</html>s