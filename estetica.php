<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="estilo3.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampay</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Zain:wght@200;300;400;700;800;900&display=swap');
        @import url('https://fonts.cdnfonts.com/css/satoshi');
    ::-webkit-scrollbar {
            width: 7px;
        }

        ::-webkit-scrollbar-track {
            background-color: rgb(221, 220, 230);
        }

        ::-webkit-scrollbar-thumb {
            background-color: #02001b;
            border-radius: 50px;
        }

    html{
      scroll-behavior: smooth;
      overflow-x: hidden;
    }

    body {
      color: #02001b;
      background-color: #fdfdfd;
    }

    html, body {
      margin: 0;
      padding: 0;
    }

           header {
            font-family: "Bacasime Antique", serif;
            font-size: 10px;
            font-style: normal;
            width: 100%;
            height: 20px;
            color: rgb(0, 0, 0);
        }

        .header-top {
            top: 0;
            background-color:#02001b !important;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-top: -0px;
            position: fixed;
            z-index: 10;
            height: 100px;
            padding-top: 35px;
            align-items: center;
            
        }

          .header-top img{
            width: 250px;
            margin-top: -45px;
            margin-left: 280px;
          }

        .logo {
            font-family: "Bacasime Antique", serif;
            font-size: 55px;
            position: absolute;
            left: 90%;
            transform: translateX(-50%);
            margin-top: -90px;

           
        }

        .icons-search {
            display: flex;
            align-items: center;
            margin-left: auto;
            z-index: 7;
            font-size: 20px;
            padding-right: 20px;
            margin-top: -10px;
        }

        .icons {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            margin-top: -20px;
        }

        .icons i {
            font-size: 25px;
            cursor: pointer;
        }

        .navbar {
            display: flex;
            justify-content: center;
            color: white;
            z-index: 100000;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            gap: 40px;
            margin-top: -20px;
        }

        .nav-menu a {
            font-size: 20px;
            font-family: Bebas Neue;
            color: rgb(255, 255, 255);
            text-decoration: none;
        }

        .nav-menu a:hover {
            color: #ffd700;         
            background: #00185c22; 
            border-radius: 6px;
            transition: color 0.2s, background 0.2s;
      }

        @keyframes gradientMove {
          0% {
            background-position: 0% 50%;
          }
          50% {
            background-position: 100% 50%;
          }
          100% {
            background-position: 0% 50%;
          }
        }

    body{ padding-top:60px } /* evita o conteúdo entrar por baixo do header */

    /* ===== HERO ================================================== */
    .hero{
      background:#000102; color:#fff; position:relative; overflow:hidden;
      min-height:86vh; display:flex; align-items:center; justify-content:center;
       border-left:10px solid #000c2c;
    }
    .hero-inner{ 
      width:min(1200px,92vw); 
      display:grid; 
      grid-template-columns:1.1fr .9fr; 
      gap:30px; align-items:center }

    .hero h1{
      font-family:"Bebas Neue";
       font-size:clamp(42px,8vw,90px); 
       margin:0 0 10px;
        letter-spacing:1.5px;
    }
    .hero p{ 
      font-size:1.15rem;
       color:#dfe3ff; 
       margin:0 0 16px;
        line-height:1.6 }
        
    .hero .cta{ display:flex; gap:10px; flex-wrap:wrap }
    .btn-grad{
      background:linear-gradient(90deg,#000a25fd,#000c2ccc); color:#fff;
      border:none; padding:14px 22px; border-radius:999px; font-family:"Bebas Neue";
      letter-spacing:.8px; font-size:1.1rem; box-shadow:0 8px 20px rgba(0,0,0,.28); cursor:pointer;
      text-decoration: none;
    }
    .btn-ghost{
      background:#ffffff; color: #000c2c; border:1px solid #ffffff45; padding:14px 22px; border-radius:999px;
      font-family:"Bebas Neue"; letter-spacing:.8px; font-size:1.1rem; backdrop-filter:blur(3px); text-decoration: none;
    }
    .hero-img{
      border-radius:18px; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center;
      padding:10px; overflow:hidden;
    }
    .hero-img img{ width:90vw; height:90vh; object-fit:cover; border-radius:12px; filter:contrast(1.02) }

    /* ===== WHY =================================================== */
    .why{ 
      background:#000102; 
      color:#fff; 
      height: 100vh;
      padding:56px 0;
       border-left:10px solid #000c2c;
        box-shadow:10px 0 32px 0 #33333366;
      }

    .why-inner{ 
      width:min(1200px,92vw);
       margin:0 auto;
        display:grid; 
        grid-template-columns:1fr 1fr;
         gap:30px; 
         align-items:center }

    .why h2{ 
      font-family:"Bebas Neue";
       font-size:clamp(36px,5.5vw,72px); 
       margin:0 0 6px }
    
       .why p{ 
        color:#dfe3ff; 
        line-height:1.7;
         font-size:1.12rem }

    /* ===== PLANS ================================================= */
.plans{ background:#f4f4f4; padding:60px 0;
 border-left:10px solid #000c2c; }
.plans h2{ text-align:center; font-family:"Bebas Neue"; font-size:clamp(38px,5vw,64px); color:#00052e; margin:0 0 6px }
.plans h3{ text-align:center; color:#02001f; font-family:"Satoshi"; margin:0 0 28px; font-weight:400 }

.plan-grid{ 
  width:min(1200px,92vw); 
  margin:0 auto; 
  display:grid; 
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); 
  gap:16px 
}

.plan{
 
  position:relative;
  border-radius:14px; 
  padding:22px 18px; 
  border:1px solid #1a1a1a44;
  box-shadow:0 8px 24px rgba(0,0,0,.25); 
  transition:.25s;
  background-color: #f4f4f4; 
  background-position:center; 
  background-size:cover;
  color:#000000;                        /* força branco no texto */ /* melhora contraste */
}

.plan::before{ 
  content:""; 
  position:absolute; 
  inset:0; 
  border-radius:14px; 
  z-index:0;
   border-left:10px solid #000c2c;
}

.plan *{ position:relative; z-index:1 } /* garante que o texto fique acima do ::before */

.plan h4{ 
  font-family:"Bebas Neue"; 
  color:#01002c; 
  font-size:1.9rem; 
  margin:4px 0 6px 
}

.price{ 
  font-family:"Bebas Neue"; 
  font-size:2.4rem; 
  color:#000000; 
  margin:6px 0 4px 
}

.plan ul{ 
  margin:10px 0 0; 
  padding:0 0 0 18px; 
  color:#000000 
}

.plan:hover{ 
  transform:translateY(-6px); 
  box-shadow:0 16px 36px rgba(0,0,0,.35) 
}

    /* ===== COURIERS (cards) ===================================== */
    .couriers{ background:#f4f4f4; padding:60px 0;  border-left:10px solid #000c2c;}
    .couriers h2{ text-align:center; font-family:"Bebas Neue"; font-size:clamp(38px,5vw,64px); color: #00052e;; margin:0 0 6px }
    .couriers h3{ text-align:center; color:#555; font-weight:400; margin:0 0 24px }
    .cards{ width:min(1200px,92vw); margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:18px }
    .card{
      background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.08);
      border:1px solid #e8e8ee; transition:.25s
    }
    .card-media{ background:url('aboutbg.png') center/cover; display:flex; justify-content:center; padding:22px }
    .card-media img{ width:96px; height:96px; border-radius:999px; object-fit:cover; box-shadow:0 8px 24px rgba(0,0,0,.25) }
    .card-body{ padding:16px }
    .name{ text-align:center; color:#011b3d; font-family:"Bebas Neue"; letter-spacing:.8px; font-size:1.6rem; margin:4px 0 0 }
    .role{ text-align:center; color:#666; margin:0 0 10px }
    .info{ list-style:none; padding:0; margin:0 0 6px; font-size:.96rem; color:#333 }
    .card:hover{ transform:translateY(-6px); box-shadow:0 16px 36px rgba(0,0,0,.12) }

    /* ===== TESTIMONIALS ========================================= */
    .testi{ background:#f4f4f4; padding:24px 0 64px;  border-left:10px solid #000c2c; }
    .testi h2{ text-align:center; font-family:"Bebas Neue"; font-size:clamp(36px,5vw,60px); color:#000102; margin:0 }
    .testi h3{ text-align:center; color:#555; margin:6px 0 14px; font-weight:400 }

      .testi-blocks {
        display: flex;
        justify-content: center;
        gap: 32px;
        flex-wrap: wrap;
        margin-top: 24px;
      }
      .testi-blocks blockquote {
        width: min(420px, 40vw);
        margin: 0;
        background: #EDEDED;
        color: #000000bb;
        padding: 20px 30px 20px 74px;
        position: relative;
        border-left: 8px solid #000c2c;
        border-radius: 10px;
        box-shadow: 0 10px 32px rgba(0,0,0,.18);
        font-size: 1.05rem;
        line-height: 1.6;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }
      .testi-blocks blockquote::before {
        content: "\201C";
        position: absolute;
        left: 14px;
        top: -10px;
        font-size: 4rem;
        color: #0d0949;
      }
      .testi-blocks blockquote span {
        display: block;
        margin-top: 10px;
        font-family: "Bebas Neue";
        color: #333;
      }

    /* ===== FOOTER =============================================== */
    .footer{
      background:linear-gradient(rgba(0,0,0,.65),rgba(0,0,0,.65)), url('aboutbg.png') center/cover no-repeat;
      color:#f4f4f4; border-radius:18px; padding:40px 20px; margin-top:40px; box-shadow:0 8px 32px rgba(0,0,0,.18)
    }
    .footer .wrap{ width:min(1200px,92vw); margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px }
    .footer-title{ font-family:"Zain"; font-size:1.4rem; text-transform:uppercase; margin-bottom:10px }
    .footer-links{ list-style:none; margin:0; padding:0 } .footer-links a{ color:#fff; text-decoration:none } .footer-links a:hover{ color:#333 }
    .social{ display:flex; gap:12px } .social a{ display:inline-flex; width:44px; height:44px; border-radius:12px; align-items:center; justify-content:center; background:linear-gradient(90deg,#000a25fd,#000c2ccc); color:#fff; text-decoration:none; font-size:22px }
    .footer-bottom{ text-align:center; margin-top:16px; color:#ddd }

    /* ===== MODAIS (mesmo do dev) ================================ */
    .logincontainer{ display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:10002; width:min(420px,92vw); padding:28px 26px 24px; border-radius:22px; background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.06)), linear-gradient(90deg,#000a25fd,#000c2ccc); box-shadow:0 24px 64px rgba(0,0,0,.40); border:1px solid rgba(255,255,255,.12); color:#f4f4f4; backdrop-filter:blur(8px) }
    .logincontainer.abrir{ display:flex; flex-direction:column }
    .logincontainer h2{ font-family:"Bebas Neue"; font-size:2rem; text-align:center; margin:4px 0 18px; color:#fff }
    .logincontainer h2::after{ content:""; display:block; width:58px; height:3px; margin:8px auto 0; border-radius:999px; background:linear-gradient(90deg,#a3ddff,#ffffff); opacity:.9 }
    .logincontainer label{ display:block; font-family:"Zain"; font-size:.95rem; color:#d7e3ff; margin:10px 2px 6px }
    .logincontainer input{ width:100%; padding:12px 10px 10px; border:none; border-bottom:2px solid rgba(255,255,255,.28); border-radius:10px 10px 0 0; background:rgba(255,255,255,.06); color:#fff; font-size:1rem; outline:none }
    .logincontainer button{ width:100%; padding:14px 16px; margin-top:10px; border:none; border-radius:14px; cursor:pointer; font-size:1.02rem; font-weight:700; font-family:"Zain"; color:#fff; background:linear-gradient(90deg,#000a25fd,#000c2ccc); box-shadow:0 10px 24px rgba(0,0,0,.28) }
    .forgot-password{ margin:12px 0 2px; font-size:.86rem; color:#d7e3ff; text-decoration:none }

    .modal-perfil{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:10001; align-items:center; justify-content:center }
    .perfil-conteudo{ width:min(920px,94vw); background:#fff; border-radius:24px; padding:0 0 18px; box-shadow:0 20px 60px rgba(0,0,0,.35); border:1px solid #e8eaf7; font-family:"Satoshi",system-ui; position:relative; overflow:hidden }
    .perfil-conteudo .fechar{ position:absolute; top:12px; right:14px; font-size:28px; cursor:pointer; color:#fff; z-index:3; text-shadow:0 6px 18px rgba(0,0,0,.45) }
    .perfil-header{ position:relative; display:grid; grid-template-columns:auto 1fr; column-gap:18px; row-gap:10px; align-items:center; padding:20px 24px 18px; background:linear-gradient(90deg,#000a25fd,#000c2ccc); color:#fff; box-shadow:0 8px 30px rgba(0,0,0,.2) inset }
    .avatar-wrap{ position:relative; z-index:1 }
    #perfilAvatar{ width:110px; height:110px; object-fit:cover; border-radius:999px; border:3px solid rgba(255,255,255,.9); box-shadow:0 10px 24px rgba(0,0,0,.35); background:#14223f }
    .trocar-foto-btn{ position:absolute; right:-6px; bottom:-6px; display:flex; align-items:center; gap:6px; background:#f4f4f4; color:#00133a; border-radius:999px; padding:6px 10px; cursor:pointer; font-family:"Bebas Neue"; letter-spacing:.4px; box-shadow:0 10px 20px rgba(0,0,0,.25) }
    .perfil-head-info .titulo{ font-family:"Bebas Neue"; font-size:2.6rem; letter-spacing:1px; margin:0 0 4px }
    .perfil-head-info .sub{ opacity:.9; margin:0; font-size:1rem }
    .perfil-tabs{ display:flex; align-items:center; gap:10px; padding:10px 14px; margin:10px 14px 0; background:#f6f8ff; border-radius:14px; border:1px solid #eef1ff }
    .tab-btn{ border:none; background:#eef1ff; color:#00185c; padding:10px 16px; border-radius:999px; cursor:pointer; font-family:"Bebas Neue"; letter-spacing:.8px; font-size:1.05rem }
    .tab-btn.active{ background:#00133a; color:#fff } .tab-btn.sair{ margin-left:auto; background:#e74c3c; color:#fff }
    .tab-pane{ display:none } .tab-pane.active{ display:block }
    .section-wrap{ padding:14px 22px 8px } .perfil-info p{ margin:.45rem 0; font-size:1.02rem; color:#333 }
    .perfil-info strong{ color:#0b156e; font-family:"Bebas Neue"; letter-spacing:.6px }
    .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:14px } .grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px }
    @media (max-width:780px){ .grid-2,.grid-3{ grid-template-columns:1fr } }
    .form-group{ display:flex; flex-direction:column; gap:6px }
    .form-group label{ font-family:"Zain"; font-size:1rem; color:#00185c }
    .form-group input{ border:none; border-bottom:2px solid #d3d3d3; padding:10px 6px; outline:none; background:transparent; font-size:1rem; color:#111 }
    .form-group input:focus{ border-color:#00185c }
    .divider{ margin:14px 0 8px; padding:8px 0; font-family:"Bebas Neue"; color:#02001b; letter-spacing:.8px; font-size:1.2rem; border-top:1px dashed #e3e3e3 }
    .btn-row{ display:flex; gap:10px; justify-content:flex-end; margin-top:10px }
    .btn{ border:none; padding:12px 18px; border-radius:12px; cursor:pointer; font-family:"Zain"; box-shadow:1px 1px 6px #00000022 }
    .btn.primary{ background:linear-gradient(90deg,var(--azul1),var(--azul2)); color:#fff }
    .btn.ghost{ background:#f3f3f3; color:#00185c }

    /* rolagem suave */
html{ scroll-behavior:smooth; }

/* compensa a altura do header fixo (~90–100px) */
#planos,
#motoboys{
  scroll-margin-top: 96px;  /* ajuste fino se precisar */
}

@media (max-width: 1100px) {
  .hero-inner {
    grid-template-columns: 1fr;
    gap: 24px;
    text-align: center;
  }
  .hero-img img {
    width: 100%;
    height: auto;
    max-width: 400px;
    margin: 0 auto;
  }
  .header-top img {
    margin-left: 0;
  }
}

@media (max-width: 900px) {
  .header-top {
    flex-direction: column;
    height: auto;
    padding-top: 10px;
    gap: 10px;
  }
  .header-top img {
    width: 180px;
    margin-top: 0;
    margin-left: 0;
  }
  .logo {
    font-size: 36px;
    margin-top: 0;
    left: 50%;
    transform: translateX(-50%);
  }
  .nav-menu {
    gap: 18px;
    margin-top: 0;
  }
  .hero h1 {
    font-size: 2rem;
  }
  .plans h2, .couriers h2, .testi h2 {
    font-size: 2rem;
  }
  .plan-grid {
    grid-template-columns: 1fr;
  }
  .why-inner {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .cards {
    grid-template-columns: 1fr;
  }
  .testi-blocks {
    flex-direction: column;
    gap: 18px;
    align-items: center;
  }
  .testi-blocks blockquote {
    width: 90vw;
    min-width: 0;
    max-width: 420px;
  }
  .footer .wrap {
    grid-template-columns: 1fr;
    gap: 18px;
  }
}

@media (max-width: 600px) {
  .header-top {
    flex-direction: column;
    height: auto;
    padding-top: 6px;
    gap: 6px;
  }
  .header-top img {
    width: 120px;
    margin-top: 0;
    margin-left: 0;
  }
  .logo {
    font-size: 24px;
    margin-top: 0;
    left: 50%;
    transform: translateX(-50%);
  }
  .nav-menu {
    flex-direction: column;
    gap: 8px;
    margin-top: 0;
    align-items: center;
  }
  .hero {
    min-height: 60vh;
    padding: 18px 0;
  }
  .hero-inner {
    width: 98vw;
    gap: 10px;
  }
  .hero h1 {
    font-size: 1.2rem;
  }
  .hero-img img {
    width: 100%;
    height: auto;
    max-width: 250px;
  }
  .why {
    padding: 24px 0;
    height: auto;
  }
  .why-inner {
    width: 98vw;
    gap: 10px;
  }
  .plans {
    padding: 24px 0;
  }
  .plan {
    padding: 12px 8px;
  }
  .plan h4 {
    font-size: 1.1rem;
  }
  .price {
    font-size: 1.3rem;
  }
  .card-media img {
    width: 64px;
    height: 64px;
  }
  .footer {
    padding: 18px 6px;
    border-radius: 10px;
  }
  .footer .wrap {
    width: 98vw;
    gap: 8px;
  }
  .testi-blocks blockquote {
    padding: 14px 10px 14px 38px;
    font-size: 0.98rem;
  }
  .testi-blocks blockquote::before {
    left: 4px;
    top: -6px;
    font-size: 2.2rem;
  }
}
  .spline-container{
          width: 100%;
          height: 600px;
          margin-right: 80px;
          margin-top: -20px;

        }

.card .info li[title="Bio"] {
  background: #fff;
  border: none;
  color: #444;
  font-style: italic;
  font-size: 0.95rem;
  white-space: normal;
  max-width: 100%;
  line-height: 1.4;
}

  </style>
</head>
<body>

<?php
require_once 'conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Profissionais de “Estética e Beleza” + foto + média de avaliação
$sql = "
  SELECT
    p.id_profissional,
    p.nome,
    COALESCE(p.cidade,'') AS cidade,
    COALESCE(pp.foto_perfil, p.avatar_url, 'uploads/default.png') AS foto,
    COALESCE(pp.comentario, p.bio) AS bio,
    ROUND(AVG(a.nota), 1)  AS media,
    COUNT(a.id)            AS total_avals
  FROM profissional p
  LEFT JOIN perfil_profissional pp ON pp.profissional_id = p.id_profissional
  LEFT JOIN avaliacao a           ON a.profissional_id   = p.id_profissional
  WHERE p.categoria = 'Estética e Beleza'
     OR p.categoria LIKE '%Estética%'
     OR p.categoria LIKE '%Beleza%'
  GROUP BY p.id_profissional, p.nome, cidade, foto, bio
  ORDER BY (AVG(a.nota) IS NULL), media DESC, total_avals DESC, p.nome ASC
";

$res   = $conn->query($sql);
$profs = $res->fetch_all(MYSQLI_ASSOC);

// helper de saída segura
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

  <!-- ===== HEADER ===== -->
  <header>
    <div class="header-top">
      <nav class="navbar">
        <ul class="nav-menu">
          <li><a href="homepage.html">INÍCIO</a></li>
          <li><a href="sobre.html">SOBRE NÓS</a></li>
          <li><a href="servicos.php">SERVIÇOS</a></li>
        </ul>
      </nav>
      <img src="logo.png" alt="logo"></a>
     <div class="icons-search">
        <div class="icons">
          <a class="icons" id="btnPerfil" href="#"><i class="bi bi-person"></i></a>
        </div>
      </div>
    </div>
  </header>

  <!-- ===== HERO ===== -->
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1>Procedimentos de estética e beleza</h1>
        <p>Da transformação ao brilho final: tecnologia, confiança e os melhores profissionais de beleza. Com a Trampay Estética, você foca no seu potencial — a gente cuida do resto.</p>
        <div class="cta">
          <a href="#planos" class="btn-grad">VER PLANOS</a>
          <a href="#motoboys" class="btn-ghost">CONHECER NOSSOS PROFISSIONAIS</a>
        </div>
      </div>
      <div class="hero-img">
        <img src="team.jpg" alt="Entregador em moto">
      </div>
    </div>
  </section>

  <!-- ===== WHY ===== -->
  <section class="why">
    <div class="why-inner">
      <div>
        <h2>Por que a Trampay?</h2>
        <p>Porque na Trampay você encontra os melhores profissionais de estética e beleza, selecionados para oferecer um atendimento de alta qualidade, seguro e confiável. Nossa plataforma conecta você de forma rápida e prática a especialistas em cuidados com a pele, cabelo, unhas, maquiagem e bem-estar, prontos para realçar sua beleza com técnicas modernas e resultados visíveis.
Com a Trampay, você tem a tranquilidade de contratar profissionais verificados, comparar avaliações e agendar tudo online, sem complicações. Beleza, confiança e praticidade — tudo em um só lugar.</p>
      </div>
      <div class="spline-container">
        <spline-viewer url="https://prod.spline.design/oE7Z9IQjVTAmUJ9x/scene.splinecode"></spline-viewer>
        <script type="module" src="https://unpkg.com/@splinetool/viewer@1.10.57/build/spline-viewer.js"></script>        
       </div>
       <ul style="margin:0; padding-left:18px; line-height:1.9"></ul>
      </ul>
      </div>
    </div>
  </section>

  <!-- ===== PLANS ===== -->
  <!-- =====<section class="plans" id="planos">
    <h2>Nossos planos</h2>
    <h3>Escolha seu plano ideal</h3>
    <div class="plan-grid">
      <article class="plan">
        <h4>Plano essencial</h4>
        <div class="price">R$89,90</div>
        <p style="margin:0">2x sem juros</p>
        <ul>
          <li>1 atendimento mensal</li>
          <li>Atendimento presencial ou domiciliar</li>
          <li>Alguns serviços disponíveis: Manicure/Pedicure, cortes de cabelo</li>
        </ul>
      </article>
      <article class="plan">
        <h4>Plano regular</h4>
        <div class="price">R$199,99</div>
        <p style="margin:0">Valor mensal</p>
        <ul>
          <li>4 atendimentos por mês</li>
          <li>Atendimento prioritário</li>
          <li>Desconto de 15% em serviços adicionais contratados fora do plano.</li>
        </ul>
      </article>
      <article class="plan">
        <h4>Plano profissional</h4>
        <div class="price">R$385,00</div>
        <p style="margin:0">Valor mensal</p>
        <ul>
          <li>Até 6 sessões mensais, incluindo tratamentos faciais, corporais ou capilares</li>
          <li>Acesso a profissionais com melhor avaliação na plataforma.</li>
          <li>Consultoria personalizada de estética e imagem.</li>
        </ul>
      </article>
      <article class="plan">
        <h4>Personalizado</h4>
        <div class="price">Sob consulta</div>
        <p style="margin:0">Entre em contato com o profissional desejado</p>
        <ul>
          <li>Flexibilidade total na escolha de tratamentos, horários e pacotes combinados.</li>
          <li>Acordos registrados pela plataforma para garantir segurança e transparência.</li>
        </ul>
      </article>
    </div>
    <p style="text-align:center; margin-top:10px; color:#666; font-size:.95rem">*Valores baseados na média do mercado. Custos adicional e taxas são calculados automaticamente no app.</p>
  </section> ===== -->

  <!-- ===== COURIERS ===== -->
  <section class="couriers" id="motoboys">
    <h2>Profissionais em destaque</h2>
    <h3>Profissionais verificados e avaliados pela comunidade</h3>
<div class="cards">
  <?php if (!empty($profs)): ?>
<?php foreach ($profs as $p):
  $id     = (int)$p['id_profissional'];
  $nome   = h($p['nome']);
  $cidade = h($p['cidade']);
  $foto   = h($p['foto'] ?: 'uploads/default.png');
  $media  = $p['media'] !== null ? number_format((float)$p['media'], 1, ',', '.') : '–';
  $total  = (int)$p['total_avals'];
  $role   = $cidade ? "Estética e Beleza • $cidade" : "Estética e Beleza";
  $href   = "perfil_publico.php?id=".$id;

  // NOVO: bio
  $bioRaw = $p['bio'] ?? '';
  $bio    = trim($bioRaw ?? '');
  if ($bio === '' || $bio === null) { $bio = 'Perfil em atualização'; }
  $bioChip = mb_strlen($bio) > 80 ? mb_substr($bio, 0, 77) . '…' : $bio;
  $bioChip = h($bioChip);
?>
  <a class="card" href="<?= $href ?>">
    <div class="card-media">
      <img src="<?= $foto ?>" alt="Foto de <?= $nome ?>">
    </div>
    <div class="card-body">
      <h4 class="name"><?= $nome ?></h4>
      <p class="role"><?= $role ?></p>
      <ul class="info">
        <li title="Avaliações"><i class="bi bi-star-fill"></i> <?= $media ?> (<?= $total ?>)</li>
        <li title="Bio"><?= $bioChip ?></li>
      </ul>
    </div>
  </a>
<?php endforeach; ?>

  <?php else: ?>
    <div class="card" style="grid-column:1/-1;text-align:center;padding:28px">
      <div class="card-body">
        <h4 class="name">Nenhum profissional encontrado</h4>
        <p class="role">Ainda não há cadastros na categoria “Estética e Beleza”.</p>
        <ul class="info"><li>Explore outras áreas em <a href="profissionais.php">Serviços</a>.</li></ul>
      </div>
    </div>
  <?php endif; ?>
</div>

  <!-- ===== TESTIMONIALS ===== -->
  <section class="testi">
    <h2>Avaliações</h2>
    <h3>O que nossos clientes dizem</h3>
    <div class="testi-blocks">
        <blockquote>“A Ana Paula foi super atenciosa e cuidadosa com o design das sobrancelhas. O atendimento em casa facilitou muito minha rotina e o resultado ficou exatamente como eu queria.”
            <span>Marina Teixeira</span>
            <span>Serviço contratado: Design de sobrancelhas – Plano Bem-Estar”</span>
        </blockquote>
        <blockquote>“A Isabelly foi super profissional e o atendimento foi impecável. Fiz drenagem linfática e saí me sentindo muito mais leve. O ambiente era calmo e bem organizado. Recomendo! ”
            <span>Eduardo Lima</span>
            <span>Serviço contratado: Drenagem linfática – Plano Regular</span>
        </blockquote>
    </div>
  </section>

  <!-- ===== FOOTER ===== -->
  <footer class="footer">
    <div class="wrap">
      <div>
        <h3 class="footer-title">Institucional</h3>
        <ul class="footer-links">
          <li><a href="sobre.html">Quem somos</a></li>
          <li><a href="#">Como funciona</a></li>
          <li><a href="#">Seja parceiro</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-title">Ajuda</h3>
        <ul class="footer-links">
          <li><a href="#">Perguntas frequentes</a></li>
          <li><a href="#">Política de privacidade</a></li>
          <li><a href="#">Termos de uso</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-title">Siga</h3>
        <div class="social">
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      <div>
        <h3 class="footer-title">Newsletter</h3>
        <form>
          <div class="d-flex gap-2">
            <input class="form-control" placeholder="Seu e-mail">
            <button class="btn-grad" type="button">Inscrever</button>
          </div>
        </form>
      </div>
    </div>
    <p class="footer-bottom">© 2025 Trampay. Todos os direitos reservados.</p>
  </footer>

  <!-- ===== MODAL LOGIN ===== -->
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
    <p style="color:#d3d3d3; margin-top:12px; font-size:.9rem">Não tem conta? <a href="cadcliente.html" style="color:#fff; text-decoration:none">Cadastre-se</a></p>
  </div>

  <!-- ===== MODAL PERFIL ===== -->
  <div class="modal-perfil" id="modalPerfil">
    <div class="perfil-conteudo">
      <span class="fechar" id="fecharPerfil">&times;</span>
      <div class="perfil-header">
        <div class="avatar-wrap">
          <img id="perfilAvatar" src="avatar.png" alt="Foto do Perfil">
          <label for="inputFoto" class="trocar-foto-btn"><i class="bi bi-camera"></i><span>Trocar foto</span></label>
          <input id="inputFoto" type="file" accept="image/*" hidden>
        </div>
        <div class="perfil-head-info">
          <p class="titulo" id="perfilHandle">@perfil</p>
          <p class="sub">Gerencie seus dados</p>
        </div>
      </div>

      <div class="perfil-tabs">
        <button class="tab-btn active" data-tab="visao">Resumo</button>
        <button class="tab-btn" data-tab="editar">Editar</button>
        <button class="tab-btn sair" id="btnSair">Sair</button>
      </div>

      <div class="tab-pane active" id="tab-visao">
        <div class="section-wrap perfil-info">
          <p><strong>Nome:</strong> <span id="perfilNome">Usuário</span></p>
          <p><strong>E-mail:</strong> <span id="perfilEmail">usuario@exemplo.com</span></p>
          <p><strong>Tipo:</strong> <span id="perfilTipo">Cliente</span></p>
        </div>
      </div>

      <div class="tab-pane" id="tab-editar">
        <form id="formEditarPerfil" enctype="multipart/form-data" class="section-wrap">
          <div class="grid-2">
            <div class="form-group"><label for="editNome">Nome</label><input type="text" id="editNome" name="nome" placeholder="Seu nome completo"></div>
            <div class="form-group"><label for="editEmail">E-mail</label><input type="email" id="editEmail" name="email" placeholder="seu@email.com"></div>
          </div>
          <div class="divider">Trocar senha (opcional)</div>
          <div class="grid-3">
            <div class="form-group"><label for="senhaAtual">Senha atual</label><input type="password" id="senhaAtual" name="senha_atual" placeholder="••••••••"></div>
            <div class="form-group"><label for="novaSenha">Nova senha</label><input type="password" id="novaSenha" name="nova_senha" placeholder="••••••••"></div>
            <div class="form-group"><label for="confirmaSenha">Confirmar nova senha</label><input type="password" id="confirmaSenha" name="confirma_nova_senha" placeholder="••••••••"></div>
          </div>
          <div class="btn-row">
            <button type="button" class="btn ghost" id="btnCancelarEdicao">Cancelar</button>
            <button type="submit" class="btn primary" id="btnSalvarPerfil"><i class="bi bi-check2-circle"></i> Salvar alterações</button>
          </div>
          <small class="hint">Para alterar e-mail e/ou senha, preencha a <b>Senha atual</b> por segurança.</small>
        </form>
      </div>
    </div>
  </div>

  <!-- ===== SCRIPT (igual ao dev, sem redirecionar de volta) ===== -->
  <script>
  // Header: cor ao rolar
  window.addEventListener('scroll', () => {
    const h = document.querySelector('.header-top');
    if (window.scrollY > 50) h.classList.add('scrolled'); else h.classList.remove('scrolled');
  });

  // Helpers
  function setActiveTab(tab){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    const btn=document.querySelector(`.tab-btn[data-tab="${tab}"]`);
    const pane=document.getElementById(`tab-${tab}`);
    if(btn) btn.classList.add('active'); if(pane) pane.classList.add('active');
  }
  function handleFromName(nome){
    if(!nome) return 'perfil';
    const s=nome.normalize('NFD').replace(/[\u0300-\u036f]/g,'');
    const b=s.toLowerCase().replace(/[^a-z0-9]+/g,'');
    return b||'perfil';
  }
  function preencherPerfilUI(user){
    if(!user) return;
    const $=id=>document.getElementById(id);
    const nome=user.nome||'', email=user.email||'', tipo=user.tipo||'Cliente';
    $('perfilNome').textContent=nome; $('perfilEmail').textContent=email; $('perfilTipo').textContent=tipo;
    $('perfilHandle').textContent='@'+handleFromName(nome);
    if ($('editNome'))  $('editNome').value=nome;
    if ($('editEmail')) $('editEmail').value=email;
    if (user.avatar_url && $('perfilAvatar')) $('perfilAvatar').src=user.avatar_url;
  }

  // API auth.php
  async function verificarLoginStatus(){
    try{
      const r=await fetch('auth.php?action=status'); const d=await r.json();
      const btn=document.getElementById('btnPerfil');
      if(d.logged_in){ btn.innerHTML='<i class="bi bi-person-check"></i>'; preencherPerfilUI(d.user); return d.user; }
      btn.innerHTML='<i class="bi bi-person"></i>'; return null;
    }catch(e){ console.error(e); return null; }
  }
  function mostrarLogin(){ document.getElementById('loginContainer').classList.add('abrir'); }
  function fecharLogin(){ document.getElementById('loginContainer').classList.remove('abrir'); }
  function mostrarPerfil(){ document.getElementById('modalPerfil').style.display='flex'; setActiveTab('visao'); }
  function fecharPerfil(){ document.getElementById('modalPerfil').style.display='none'; }

  async function fazerLogin(ev){
    if(ev) ev.preventDefault();
    const fd=new FormData(document.getElementById('loginForm')); fd.append('action','login');
    try{
      const r=await fetch('auth.php',{method:'POST',body:fd}); const d=await r.json();
      if(d.success){ fecharLogin(); preencherPerfilUI(d.user); await verificarLoginStatus(); mostrarPerfil(); }
      else alert('Erro: '+(d.error||'Falha no login'));
    }catch{ alert('Erro ao conectar com o servidor'); }
  }
  async function fazerLogout(){
    try{
      const r=await fetch('auth.php?action=logout'); const d=await r.json();
      if(!d.logged_in){ alert('Logout realizado!'); fecharPerfil(); await verificarLoginStatus(); }
    }catch{ alert('Erro ao fazer logout'); }
  }

  async function enviarFotoPerfil(file){
    const fd=new FormData(); fd.append('action','update_profile'); fd.append('foto',file);
    const r=await fetch('auth.php',{method:'POST',body:fd}); const d=await r.json();
    if(!d.success) throw new Error(d.error||'Falha ao atualizar foto'); return d.user;
  }
  async function salvarEdicoesPerfil(e){
    e.preventDefault();
    const nome=(document.getElementById('editNome')?.value||'').trim();
    const email=(document.getElementById('editEmail')?.value||'').trim();
    const senha_atual=document.getElementById('senhaAtual')?.value||'';
    const nova_senha=document.getElementById('novaSenha')?.value||'';
    const confirma=document.getElementById('confirmaSenha')?.value||'';
    if(nova_senha||confirma){
      if(nova_senha.length<8){ alert('A nova senha deve ter no mínimo 8 caracteres.'); return; }
      if(nova_senha!==confirma){ alert('A confirmação da nova senha não confere.'); return; }
      if(!senha_atual){ alert('Informe a senha atual para trocar e-mail/senha.'); return; }
    }
    const fd=new FormData();
    fd.append('action','update_profile');
    if(nome) fd.append('nome',nome);
    if(email) fd.append('email',email);
    if(senha_atual) fd.append('senha_atual',senha_atual);
    if(nova_senha) fd.append('nova_senha',nova_senha);
    try{
      const r=await fetch('auth.php',{method:'POST',body:fd}); const d=await r.json();
      if(!d.success){ alert('Erro: '+(d.error||'Falha ao atualizar perfil')); return; }
      preencherPerfilUI(d.user);
      ['senhaAtual','novaSenha','confirmaSenha'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; });
      setActiveTab('visao'); alert('Perfil atualizado com sucesso!');
    }catch{ alert('Erro ao conectar com o servidor.'); }
  }

  // Init
  document.addEventListener('DOMContentLoaded', async ()=>{
    await verificarLoginStatus();

    document.getElementById('btnPerfil').addEventListener('click', async (e)=>{
      e.preventDefault();
      const u=await verificarLoginStatus();
      if(u) mostrarPerfil(); else mostrarLogin();
    });
    document.getElementById('loginForm').addEventListener('submit', fazerLogin);
    document.getElementById('btnSair').addEventListener('click', fazerLogout);
    document.getElementById('fecharPerfil').addEventListener('click', fecharPerfil);
    document.querySelectorAll('.tab-btn').forEach(b=>{ const t=b.dataset.tab; if(t) b.addEventListener('click',()=>setActiveTab(t)); });
    document.getElementById('btnCancelarEdicao').addEventListener('click', ()=>setActiveTab('visao'));

    const inputFoto=document.getElementById('inputFoto'); const avatarEl=document.getElementById('perfilAvatar');
    inputFoto.addEventListener('change', async (e)=>{
      const file=e.target.files?.[0]; if(!file) return;
      const rd=new FileReader(); rd.onload=()=>{ if(avatarEl) avatarEl.src=rd.result; }; rd.readAsDataURL(file);
      try{ const u=await enviarFotoPerfil(file); preencherPerfilUI(u); alert('Foto atualizada!'); }catch(err){ alert(err.message); }
    });

    // Fechar ao clicar fora
    window.addEventListener('click',(e)=>{
      if(e.target===document.getElementById('loginContainer')) fecharLogin();
      if(e.target===document.getElementById('modalPerfil')) fecharPerfil();
    });
  });

  
  </script>
</body>
</html>
