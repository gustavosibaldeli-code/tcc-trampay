<?php
declare(strict_types=1);

/* =========================================================
   Trampay • Serviços
   - Lista categorias com contagem de serviços (ativos)
   - Profissionais em destaque (perfil atualizado)
   - Serviços recentes (cards)
   Dependências de banco:
     • profissional (id_profissional, nome, email, categoria, ...)
     • servico (id, id_profissional, titulo, descricao, preco_min, prazo_dias, ativo, created_at)
     • perfil_profissional (profissional_id, foto_perfil, banner, comentario, data_atualizacao)
   ========================================================= */

# ===== Conexão DB =====
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'trampay';

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($db->connect_error) { die('Falha ao conectar: ' . $db->connect_error); }
$db->set_charset('utf8mb4');

# ===== Utilidades =====
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money_br($v): string {
  if ($v === null || $v === '') return '—';
  return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

# ===== Categorias: contagem de serviços ATIVOS por categoria do profissional =====
$cats = [];
$sqlCats = "
  SELECT
    COALESCE(NULLIF(TRIM(p.categoria), ''), 'Outros') AS categoria,
    COUNT(s.id) AS total
  FROM profissional p
  JOIN servico s
    ON s.id_profissional = p.id_profissional
   AND s.ativo = 1
  GROUP BY COALESCE(NULLIF(TRIM(p.categoria), ''), 'Outros')
  ORDER BY categoria ASC
";
if (!$res = $db->query($sqlCats)) {
  die('Erro ao buscar categorias: ' . $db->error);
}
while ($row = $res->fetch_assoc()) { $cats[] = $row; }
$res->free();

# ===== Profissionais em destaque: últimos que atualizaram o perfil =====
$pros = [];
$sqlPros = "
  SELECT
    p.id_profissional, p.nome,
    COALESCE(pp.foto_perfil, '') AS foto_perfil,
    COALESCE(pp.comentario, '') AS comentario,
    pp.data_atualizacao
  FROM perfil_profissional pp
  JOIN profissional p ON p.id_profissional = pp.profissional_id
  ORDER BY pp.data_atualizacao DESC
  LIMIT 6
";
if (!$res2 = $db->query($sqlPros)) {
  die('Erro ao buscar profissionais: ' . $db->error);
}
while ($row = $res2->fetch_assoc()) { $pros[] = $row; }
$res2->free();

# ===== Serviços recentes =====
$servicos = [];
$sqlSrv = "
  SELECT
    s.id, s.titulo, s.descricao, s.preco_min, s.prazo_dias, s.created_at,
    p.id_profissional, p.nome AS nome_prof, COALESCE(p.categoria,'') AS categoria,
    COALESCE(pp.foto_perfil,'') AS foto_perfil
  FROM servico s
  JOIN profissional p ON p.id_profissional = s.id_profissional
  LEFT JOIN perfil_profissional pp ON pp.profissional_id = p.id_profissional
  WHERE s.ativo = 1
  ORDER BY s.created_at DESC, s.id DESC
  LIMIT 12
";
if (!$res3 = $db->query($sqlSrv)) {
  die('Erro ao buscar serviços: ' . $db->error);
}
while ($row = $res3->fetch_assoc()) { $servicos[] = $row; }
$res3->free();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Trampay • Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Ícones -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Fonte display (mesma família da sua homepage) -->
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">

  <style>
    /* ============ Trampay Design System (Serviços) ============ */
    :root{
      --ink:#0b0f1a;
      --ink-2:#1a2235;
      --blue:#00133a;
      --blue-2:#0a2a7a;
      --muted:#9fb0d2;
      --bg:#f5f7fb;
      --card:#ffffff;
      --line:#e8eaf7;
      --brand-1:#4361ee;
      --brand-2:#5a8cff;
      --accent:#00d4ff;
      --ok:#0bb07b;
      --warn:#f5a524;
      --danger:#e5484d;
      --radius:16px;
      --shadow: 0 8px 28px rgba(5, 20, 70, .12);
      --shadow-2: 0 14px 38px rgba(10, 30, 90, .16);
    }
    *{ box-sizing:border-box }
    html,body{ height:100% }
    body{
      margin:0; color:var(--ink);
      background:linear-gradient(180deg,#f7f9ff 0%, #eef2ff 40%, #f7f9ff 100%);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }
    a{ color:inherit; text-decoration:none }

    /* Navegação (visual da homepage, com hover amarelo e sem sublinhado) */
.nav-menu{
  list-style:none;
  display:flex;
  gap:40px;
  margin:0;
  padding:0;
}

.nav-menu a{
  color:#fff;
  font-family:"Bebas Neue";
  letter-spacing:.6px;
  font-size:20px;
  text-decoration:none;       /* remove sublinhado */
  opacity:.95;
  padding:6px 10px;           /* dá área de clique */
  border-radius:6px;
  transition: color .2s ease, background .2s ease, opacity .2s ease;
}

/* Hover: amarelo + leve highlight de fundo */
.nav-menu a:hover{
  color:#ffd700;
  background:#00185c22;
  opacity:1;
  text-decoration:none;       /* garante sem linha */
}

/* Foco via teclado: acessível, sem sublinhado */
.nav-menu a:focus-visible{
  outline:2px solid #ffd700;
  outline-offset:2px;
  text-decoration:none;
}

/* Estado "ativo" (página atual) – use class="active" no link */
.nav-menu a.active{
  color:#ffd700;
  background:#00185c22;
  text-decoration:none;
}


/* ===== HEADER FIXO IGUAL HOME ===== */
.header-top{
  position: fixed;            /* fica por cima durante o scroll */
  top: 0; left: 0; right: 0;  /* ocupa a largura toda */
  width: 100%;
  display: grid;
  grid-template-columns: 1fr auto 1fr;  /* menu | logo | ícones */
  align-items: center;
  padding: 16px 24px;
  background: linear-gradient(180deg,#02011b 0%, #02011b 70%, #02011bd0 100%);
  border-bottom: 1px solid rgba(255,255,255,.06);
  backdrop-filter: blur(8px);
  z-index: 4000;              /* acima do conteúdo, abaixo dos modais */
}
.header-top.scrolled{
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
}
.navbar{ justify-self:start; }
.logo{ position: static; transform:none; margin:0; justify-self:center; }
.logo img{ height: clamp(56px, 7vw, 96px); width:auto; } /* ajuste o tamanho aqui */
.icons-search{ justify-self:end; align-self:center; margin:0 8px 0 0; }
#btnPerfil, .icons-search .icons a{
  display:inline-flex; align-items:center; justify-content:center;
  width:36px; height:36px; border-radius:999px; color:#fff; background:transparent; border:0;
}
#btnPerfil i, .icons-search i{ font-size:24px; line-height:1; color:#fff; }
.icons{ display:flex; align-items:center; gap:15px; color:#fff; }
.icons i{ font-size:25px; cursor:pointer; }

/* altura de respiro para o header fixo */
body{ padding-top: 128px; }   /* ajuste fino: 130–160px conforme o tamanho da logo */


/* empurra o conteúdo pra baixo do header fixo */
.page, main, .wrap, section{ scroll-margin-top:120px; }  /* opcional */


    /* ===== Hero ===== */
    .hero{
      position:relative; overflow:hidden;
      background:
        radial-gradient(1000px 600px at 80% -20%, #2b3a86 0%, transparent 60%),
        radial-gradient(900px 500px at -10% 20%, #15306a 0%, transparent 60%),
        #000102;
      color:#fff; padding:56px 20px 38px;
      border-bottom:1px solid rgba(255,255,255,.06);
    }
    .wrap{ width:min(1200px,92vw); margin:0 auto }
    .hero h1{
      margin:0 0 10px; font-family:"Bebas Neue"; letter-spacing:1px;
      font-size:clamp(28px, 4.2vw, 54px);
    }
    .hero p{ margin:0; max-width:720px; opacity:.9; line-height:1.5 }
    .hero .cta{ display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; }
    .btn{
      border:none; padding:12px 16px; border-radius:14px; cursor:pointer;
      font-family:"Bebas Neue"; letter-spacing:.6px; font-size:18px;
      box-shadow:1px 1px 6px #00000014; transition:transform .15s ease, box-shadow .15s ease, background .3s ease;
    }
    .btn.dark{
      background:linear-gradient(135deg, var(--brand-1), var(--brand-2));
      color:#fff; box-shadow: var(--shadow);
    }
    .btn.dark:hover{ transform: translateY(-2px); box-shadow: var(--shadow-2); }
    .btn.ghost{ background:#eef2ff; color:#00133a }
    .btn.ghost:hover{ background:#e6ecff }

    /* ===== Seções ===== */
    .section{ padding:42px 0 }
    .section h2{
      margin:0 0 6px; font-family:"Bebas Neue"; letter-spacing:1px; color:var(--blue);
      font-size:clamp(22px, 3vw, 34px);
    }
    .section h3{ margin:0 0 22px; color:#2c3961; font-weight:500; opacity:.9 }

    /* ===== Categorias ===== */
    .cat-grid{
      display:grid; grid-template-columns:repeat(4,1fr); gap:16px;
    }
    @media (max-width:1100px){ .cat-grid{ grid-template-columns:repeat(3,1fr) } }
    @media (max-width:760px){ .cat-grid{ grid-template-columns:repeat(2,1fr) } }
    @media (max-width:520px){ .cat-grid{ grid-template-columns:1fr } }

    .cat-card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      padding:16px;
      box-shadow:var(--shadow);
      display:flex; flex-direction:column; gap:10px;
      transition: transform .15s ease, box-shadow .15s ease, border-color .2s ease;
    }
    .cat-card:hover{
      transform: translateY(-3px);
      border-color:#dbe3ff;
      box-shadow: var(--shadow-2);
    }
    .cat-name{
      display:flex; align-items:center; gap:10px; color:var(--blue);
      font-weight:600;
    }
    .cat-pill{
      margin-left:auto; font-size:.9rem; color:#1b2a5a;
      background:#e9efff; border:1px solid #d9e4ff; padding:6px 10px; border-radius:999px;
    }
    .cat-desc{ color:#445; opacity:.92; font-size:.95rem; line-height:1.45 }
    .cat-actions{ display:flex; gap:10px; margin-top:8px }

    /* ===== Profissionais em destaque ===== */
    .pro-grid{
      display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;
    }
    @media (max-width:1000px){ .pro-grid{ grid-template-columns:repeat(2,1fr) } }
    @media (max-width:640px){ .pro-grid{ grid-template-columns:1fr } }

    .card{
      background:var(--card); border:1px solid var(--line); border-radius:var(--radius);
      overflow:hidden; display:flex; gap:14px; padding:14px; align-items:flex-start;
      box-shadow:var(--shadow); transition: transform .15s ease, box-shadow .15s ease, border-color .2s ease;
    }
    .card:hover{ transform: translateY(-3px); box-shadow: var(--shadow-2); border-color:#dbe3ff; }
    .avatar{ width:80px; height:80px; border-radius:14px; object-fit:cover; background:#f2f4ff; border:1px solid #eef2ff }
    .card h4{ margin:2px 0 6px; font-size:1.05rem; color:#0d1a3f }
    .card p{ margin:.25rem 0; color:#334; line-height:1.5 }
    .meta{ display:flex; gap:12px; flex-wrap:wrap; font-size:.93rem; color:#1b2a5a; opacity:.95 }
    .meta span{ display:inline-flex; align-items:center; gap:6px }
    .actions{ margin-top:10px; display:flex; gap:10px; flex-wrap:wrap }

    /* ===== Serviços recentes ===== */
    .srv-grid{
      display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;
    }
    @media (max-width:1000px){ .srv-grid{ grid-template-columns:repeat(2,1fr) } }
    @media (max-width:640px){ .srv-grid{ grid-template-columns:1fr } }

    footer{ text-align:center; padding:22px; color:#445; font-size:.95rem }

    /* ===== Modal de perfil/login ===== */
    .modal{ position:fixed; inset:0; display:none; place-items:center; background:rgba(2,1,27,.45); backdrop-filter:blur(6px); }
    .modal.show{ display:grid; }
    .modal-card{
      width:min(780px, 92vw); background:#050c27; color:#dfe7ff;
      border:1px solid rgba(255,255,255,.08); border-radius:16px; overflow:hidden; box-shadow: var(--shadow-2);
      display:grid; grid-template-columns:1fr 1fr;
    }
    @media (max-width:820px){ .modal-card{ grid-template-columns:1fr } }
    .modal-side{
      background:
        radial-gradient(80% 60% at 80% 70%, rgba(255,255,255,.10), transparent 60%),
        radial-gradient(50% 50% at 10% 20%, rgba(255,255,255,.06), transparent 60%),
        linear-gradient(135deg,#000a25fd,#000c2ccc);
      padding:28px; display:flex; flex-direction:column; justify-content:space-between;
    }
    .modal-side h3{ margin:0 0 8px; font-family:"Bebas Neue"; letter-spacing:.6px; font-size:28px }
    .modal-content{ padding:26px; background:#090f2c }
    .form-group{ display:flex; flex-direction:column; gap:8px; margin-bottom:12px }
    .form-group label{ font-size:.95rem; opacity:.9 }
    .form-group input{ background:#0d1536; border:1px solid #1b2759; color:#eaf2ff; border-radius:12px; padding:12px 14px }
    .login-actions{ display:flex; gap:10px; margin-top:10px }
    .profile-info p{ margin:6px 0 }
    .close-x{ position:absolute; top:12px; right:12px; background:transparent; border:none; color:#fff; font-size:22px; cursor:pointer }
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
/* --- Header layout e posicionamento do ícone --- */
.header-top{
  display: grid;
  grid-template-columns: 1fr auto 1fr; /* esq | logo | dir */
  align-items: center;
  padding: 14px 24px;
}
.logo{ justify-self: center; }                  /* centro certinho */
.icons-search{
  justify-self: end;                            /* canto direito */
  align-self: center;
  margin-right: 8px;                            /* respiro da borda */
}
#btnPerfil, .icons-search .icons a{
  display:inline-flex; align-items:center; justify-content:center;
  width:36px; height:36px; border-radius:999px;
  color:#fff; background:transparent; border:0; cursor:pointer;
}
#btnPerfil i, .icons-search i{ font-size:24px; line-height:1; color:#fff; }

/* Preferência do usuário: menos animação */
@media (prefers-reduced-motion: reduce){
  *{ transition:none !important; animation:none !important; }
}
  

/* ==== FIX: centraliza a logo sem absolute ==== */
.header-top{
  display:grid;
  grid-template-columns: 1fr auto 1fr; /* esq | logo | dir */
  align-items:center;
  padding:14px 24px;                   /* sem padding-top gigante */
  height:auto;                         /* deixa a altura natural */
}
.navbar{ justify-self:start; }         /* menu à esquerda */
.logo{
  position:static;                     /* ← tira do fluxo absoluto */
  transform:none;                      /* ← sem translate */
  margin:0;                            /* ← remove o -50px */
  justify-self:center;                 /* centraliza na grid */
}
.logo img{
  height: clamp(56px, 7vw, 96px);      /* tamanho da logo (ajuste à vontade) */
  width:auto;
}
.icons-search{ justify-self:end; align-self:center; margin:0 8px 0 0; }


  </style>
</head>
<body>

<header>
  <div class="header-top" id="headerTop">
    <nav class="navbar">
      <ul class="nav-menu">
         <li><a href="homepage.html" >INICÍO</a></li>
        <li><a href="sobre.html">SOBRE NÓS</a></li>
      </ul>
    </nav>

    <!-- logo central, maior -->
    <div class="logo">
      <a href="homepage.html" aria-label="Trampay - Início">
        <img src="logo.png" alt="logo">
      </a>
    </div>

    <!-- ícone do perfil à direita -->
    <div class="icons-search">
      <div class="icons">
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

<section class="hero">
  <div class="wrap">
    <h1>Encontre o serviço certo, com a qualidade Trampay.</h1>
    <p>Busque por categoria, veja profissionais em destaque e contrate com segurança.</p>
    <div class="cta">
      <a class="btn dark" href="#categorias"><i class="bi bi-grid-1x2-fill"></i> Ver categorias</a>
      <a class="btn ghost" href="#recentes"><i class="bi bi-lightning-charge-fill"></i> Serviços recentes</a>
    </div>
  </div>
</section>

<section id="categorias" class="section">
  <div class="wrap">
    <h2>Categorias</h2>
    <h3>Escolha a área e veja os serviços disponíveis</h3>

    <div class="cat-grid">
      <?php foreach ($cats as $c):
        $cat = trim((string)$c['categoria']);
        $tot = (int)$c['total'];
        $href = 'categoria.php?cat=' . urlencode($cat);
      ?>
        <article class="cat-card">
          <div class="cat-name">
            <i class="bi bi-tag-fill"></i> <span><?= h($cat) ?></span>
            <span class="cat-pill"><?= $tot ?></span>
          </div>
          <div class="cat-desc muted">Explore profissionais desta especialidade e peça um orçamento.</div>
          <div class="cat-actions">
            <a class="btn dark" href="<?= $href ?>"><i class="bi bi-arrow-right-circle"></i> Ver categoria</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="destaque" class="section" style="background:#eef2ff">
  <div class="wrap">
    <h2>Profissionais em destaque</h2>
    <h3>Quem atualizou o portfólio recentemente</h3>

    <div class="pro-grid">
      <?php foreach ($pros as $p):
        $idp  = (int)$p['id_profissional'];
        $nome = $p['nome'] ? trim($p['nome']) : 'Profissional';
        $foto = $p['foto_perfil'] ?: 'avatar.png';
        $desc = $p['comentario'] ?: 'Profissional verificado na plataforma.';
      ?>
        <article class="card">
          <img class="avatar" src="<?= h($foto) ?>" alt="<?= h($nome) ?>">
          <div>
            <h4><?= h($nome) ?></h4>
            <p class="muted"><?= h($desc) ?></p>
            <div class="actions">
              <a class="btn dark"  href="perfil_publico.php?id_profissional=<?= $idp ?>"><i class="bi bi-eye"></i> Ver perfil</a>
              <a class="btn ghost" href="perfil_publico.php?id_profissional=<?= $idp ?>#servicos"><i class="bi bi-list-task"></i> Ver serviços</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="recentes" class="section">
  <div class="wrap">
    <h2>Serviços recentes</h2>
    <h3>Novas ofertas adicionadas por profissionais</h3>

    <div class="srv-grid">
      <?php foreach ($servicos as $s):
        $id      = (int)$s['id'];
        $titulo  = $s['titulo'] ?: 'Serviço';
        $desc    = $s['descricao'] ?: '';
        $preco   = $s['preco_min'];
        $prazo   = is_null($s['prazo_dias']) ? '—' : ((int)$s['prazo_dias'] . ' dia(s)');
        $idp     = (int)$s['id_profissional'];
        $nomep   = $s['nome_prof'] ?: 'Profissional';
        $foto    = $s['foto_perfil'] ?: 'avatar.png';
        $cat     = trim((string)$s['categoria']);
      ?>
        <article class="card">
          <img class="avatar" src="<?= h($foto) ?>" alt="<?= h($nomep) ?>">
          <div>
            <h4><?= h($titulo) ?></h4>
            <p class="muted"><?= h($desc) ?></p>
            <div class="meta">
              <span><i class="bi bi-person-badge"></i> <?= h($nomep) ?></span>
              <span><i class="bi bi-tags-fill"></i> <?= h($cat ?: 'Outros') ?></span>
              <span><i class="bi bi-cash-coin"></i> <?= money_br($preco) ?></span>
              <span><i class="bi bi-stopwatch"></i> <?= h($prazo) ?></span>
            </div>
            <div class="actions">
              <a class="btn dark"  href="perfil_publico.php?id_profissional=<?= $idp ?>"><i class="bi bi-eye"></i> Ver perfil</a>
              <a class="btn ghost" href="perfil_publico.php?id_profissional=<?= $idp ?>#servicos"><i class="bi bi-list-task"></i> Ver serviço</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<footer>© <?= date('Y') ?> Trampay — todos os direitos reservados.</footer>

<!-- ========== Modal de Perfil / Login (simples e eficaz) ========== -->
<div id="perfilModal" class="modal" aria-hidden="true" role="dialog">
  <div class="modal-card">
    <button class="close-x" aria-label="Fechar" onclick="fecharPerfil()"><i class="bi bi-x-lg"></i></button>

    <div class="modal-side">
      <div>
        <h3>Seu perfil Trampay</h3>
        <p class="muted">Gerencie sua conta, visualize dados e acesse configurações.</p>
      </div>
      <div class="muted" style="font-size:.9rem;">Segurança de sessão ativa. Dúvidas? suporte@trampay.com</div>
    </div>

    <div class="modal-content">

      <!-- Bloco logado -->
      <div id="perfilLogado" style="display:none">
        <h3 style="margin-top:0;font-family:'Bebas Neue';letter-spacing:.6px;">Olá, <span id="perfilNome">Usuário</span> 👋</h3>
        <div class="profile-info">
          <p><strong>E-mail:</strong> <span id="perfilEmail">usuario@exemplo.com</span></p>
          <p><strong>Tipo:</strong> <span id="perfilTipo">Cliente</span></p>
        </div>
        <div class="login-actions">
         <a id="btnMeuPerfil" class="btn dark" href="#"><i class="bi bi-person-gear"></i> Meu Perfil</a>
          <button class="btn ghost" onclick="fazerLogout()"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </div>
      </div>

      <!-- Bloco não logado -->
      <div id="perfilNaoLogado" style="display:none">
        <h3 style="margin-top:0;font-family:'Bebas Neue';letter-spacing:.6px;">Entre na sua conta</h3>
        <form id="formLogin" onsubmit="event.preventDefault(); fazerLogin();">
          <div class="form-group">
            <label for="loginEmail">E-mail</label>
            <input id="loginEmail" name="email" type="email" placeholder="seu@email.com" required>
          </div>
          <div class="form-group">
            <label for="loginSenha">Senha</label>
            <input id="loginSenha" name="senha" type="password" placeholder="••••••••" required>
          </div>
          <div class="login-actions">
            <button class="btn dark" type="submit"><i class="bi bi-door-open-fill"></i> Entrar</button>
            <a class="btn ghost" href="cadcliente.html"><i class="bi bi-person-plus"></i> Criar conta</a>
          </div>
        </form>
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

 (function () {
    const topBar = document.getElementById('headerTop');
    function onScroll(){ (window.scrollY > 30) ? topBar.classList.add('scrolled')
                                              : topBar.classList.remove('scrolled'); }
    onScroll();
    window.addEventListener('scroll', onScroll, {passive:true});
  })();

</script>

</body>
</html>
