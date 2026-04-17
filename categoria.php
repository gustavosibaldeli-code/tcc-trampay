<?php
// categoria.php — Lista serviços por categoria (Trampay)
declare(strict_types=1);

// ====== Conexão ======
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbnm = 'trampay';
$db = new mysqli($host, $user, $pass, $dbnm);
if ($db->connect_error) { die('Falha ao conectar: ' . $db->connect_error); }
$db->set_charset('utf8mb4');

// ====== Leitura e normalização do parâmetro ======
$catParam = isset($_GET['cat']) ? trim((string)$_GET['cat']) : 'Todos';
$catParam = mb_substr($catParam, 0, 100); // sanidade
$catTitle = $catParam === '' ? 'Todos' : $catParam;

// ====== Paginação ======
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ====== Monta lista de categorias com contagem (base: profissional.categoria) ======
// Contamos serviços ativos por categoria do profissional
$sqlCats = "
  SELECT
    COALESCE(NULLIF(TRIM(p.categoria), ''), 'Outros') AS categoria,
    COUNT(s.id) AS total
  FROM profissional p
  JOIN servico s ON s.id_profissional = p.id_profissional
  WHERE s.ativo = 1
  GROUP BY COALESCE(NULLIF(TRIM(p.categoria), ''), 'Outros')
  ORDER BY categoria
";
$cats = [];
if (!$res = $db->query($sqlCats)) {
  die('Erro na consulta de categorias: ' . $db->error);
}
while ($row = $res->fetch_assoc()) { $cats[] = $row; }
$res->free();

// ====== Condição por categoria ======
$where = "WHERE s.ativo = 1";
$params = [];
$types  = '';

if ($catTitle === 'Outros') {
  $where .= " AND (p.categoria IS NULL OR TRIM(p.categoria) = '')";
} elseif ($catTitle !== 'Todos') {
  $where .= " AND p.categoria = ?";
  $params[] = $catTitle;
  $types   .= 's';
}

// ====== Total para paginação ======
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM servico s
  JOIN profissional p ON p.id_profissional = s.id_profissional
  $where
";
$stmt = $db->prepare($sqlCount);
if (!$stmt) { die('Erro no prepare (count): ' . $db->error); }
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$total = 0;
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

// ====== Busca serviços da categoria, com dados do profissional e foto do perfil ======
$sqlList = "
  SELECT
    s.id, s.titulo, s.descricao, s.preco_min, s.prazo_dias, s.created_at,
    p.id_profissional, p.nome, COALESCE(NULLIF(TRIM(p.categoria), ''), 'Outros') AS categoria,
    COALESCE(pp.foto_perfil, '') AS foto_perfil
  FROM servico s
  JOIN profissional p     ON p.id_profissional = s.id_profissional
  LEFT JOIN perfil_profissional pp ON pp.profissional_id = p.id_profissional
  $where
  ORDER BY s.created_at DESC, s.id DESC
  LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sqlList);
if (!$stmt) { die('Erro no prepare (list): ' . $db->error); }

// bind dinâmico: tipos + dois inteiros (limit/offset)
$bindTypes = $types . 'ii';
if ($types) { $params[] = $perPage; $params[] = $offset; }
else        { $params   = [$perPage, $offset]; }
$stmt->bind_param($bindTypes, ...$params);

$stmt->execute();
$servicos = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $servicos[] = $row; }
$stmt->close();

// ====== Helpers ======
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money_br($v): string {
  if ($v === null || $v === '') return '—';
  return 'R$ ' . number_format((float)$v, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Trampay • Categoria: <?php echo h($catTitle); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <style>
    :root{ --ink:#000102; --blue:#000c2c; --muted:#9fb0d2; }
    *{ box-sizing:border-box }
    body{ margin:0; background:#f4f4f4; color:#111; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial }
    a{ color:inherit; text-decoration:none }

    /* Header (padrão Trampay, similar servicos.php) */
    .header-top{ position:sticky; top:0; z-index:9999; background:#02011b;
      display:grid; grid-template-columns:1fr auto 1fr; align-items:center; padding:14px 24px; }
    .nav-menu{ display:flex; gap:28px; list-style:none; padding:0; margin:0; }
    .nav-menu a{ color:#fff; font-family:"Bebas Neue"; letter-spacing:.6px; font-size:20px }
    .logo img{ height:48px }
    .header-right{ justify-self:end }
    #btnPerfil{ display:inline-flex; align-items:center; justify-content:center; background:transparent; border:none }
    #btnPerfil i{ color:#fff; font-size:24px; line-height:1 }

    .hero{ background:#000102; color:#fff; padding:46px 20px }
    .wrap{ width:min(1200px,92vw); margin:0 auto }

    .grid{ display:grid; grid-template-columns:260px 1fr; gap:24px; margin:22px 0 30px }
    @media (max-width:900px){ .grid{ grid-template-columns:1fr } }

    .side{ background:#fff; border:1px solid #e8eaf7; border-radius:16px; padding:16px }
    .side h3{ margin:0 0 10px; font-family:"Bebas Neue"; letter-spacing:1px; color:#00133a }
    .cat-list{ display:flex; flex-direction:column; gap:8px; }
    .cat-item{ display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:10px; border:1px solid #eef1ff; background:#f7f9ff }
    .cat-item.active{ background:#00133a; color:#fff; border-color:#00133a }
    .cat-item small{ opacity:.8 }

    .content{ min-height:300px }
    .bar{ display:flex; align-items:center; justify-content:space-between; margin-bottom:12px }
    .bar h2{ margin:0; font-family:"Bebas Neue"; letter-spacing:1px; font-size:28px; color:#00133a }

    .cards{ display:grid; grid-template-columns:repeat(3, 1fr); gap:14px }
    @media (max-width:1000px){ .cards{ grid-template-columns:repeat(2,1fr) } }
    @media (max-width:640px){ .cards{ grid-template-columns:1fr } }

    .card{ background:#fff; border:1px solid #e8eaf7; border-radius:16px; overflow:hidden; display:flex; gap:12px; padding:12px }
    .avatar{ width:72px; height:72px; border-radius:12px; object-fit:cover; background:#f2f2f2; border:1px solid #eef2ff }
    .card h4{ margin:0; font-size:1.05rem }
    .card p{ margin:.25rem 0; color:#333 }
    .meta{ display:flex; gap:10px; font-size:.92rem; color:#00185c; opacity:.9 }
    .actions{ margin-top:8px; display:flex; gap:8px }
    .btn{ border:none; padding:10px 14px; border-radius:12px; cursor:pointer; font-family:"Bebas Neue"; letter-spacing:.6px; box-shadow:1px 1px 6px #00000014 }
    .btn.dark{ background:#00133a; color:#fff }
    .btn.ghost{ background:#eef2ff; color:#00133a }

    .pagination{ display:flex; gap:8px; justify-content:center; margin:22px 0 }
    .pagination a, .pagination span{ padding:8px 12px; border-radius:10px; border:1px solid #e8eaf7; background:#fff }
    .pagination .current{ background:#00133a; color:#fff; border-color:#00133a }

    footer{ text-align:center; padding:18px; color:#445; font-size:.95rem }
  </style>
</head>
<body>

<header class="header-top">
  <nav>
    <ul class="nav-menu">
      <li><a href="homepage.html">INÍCIO</a></li>
      <li><a href="profissionais.php">SERVIÇOS</a></li>
    </ul>
  </nav>
  <div class="logo"><img src="logo.png" alt="Trampay"></div>
  <div class="header-right"><a id="btnPerfil" href="#"><i class="bi bi-person"></i></a></div>
</header>

<section class="hero">
  <div class="wrap">
    <h1>Categoria: <?php echo h($catTitle); ?></h1>
    <p>Veja os serviços disponíveis nesta categoria. Filtramos pelos profissionais com essa especialidade.</p>
  </div>
</section>

<main class="wrap">
  <div class="grid">

    <!-- Sidebar categorias -->
    <aside class="side">
      <h3>Categorias</h3>
      <div class="cat-list">
        <?php
        $mkHref = function(string $c): string { return 'categoria.php?cat=' . urlencode($c); };
        $isActive = function(string $c) use ($catTitle): bool { return $c === $catTitle; };
        // Link "Todos"
        ?>
        <a class="cat-item <?php echo $isActive('Todos')?'active':''; ?>" href="<?php echo $mkHref('Todos'); ?>">
          <span><i class="bi bi-grid-1x2-fill"></i> Todos</span>
          <small><?php echo (int)$total; ?></small>
        </a>
        <?php foreach ($cats as $c): 
          $cname = (string)$c['categoria'];
          $ctot  = (int)$c['total'];
        ?>
        <a class="cat-item <?php echo $isActive($cname)?'active':''; ?>" href="<?php echo $mkHref($cname); ?>">
          <span><i class="bi bi-tag-fill"></i> <?php echo h($cname); ?></span>
          <small><?php echo $ctot; ?></small>
        </a>
        <?php endforeach; ?>
      </div>
    </aside>

    <!-- Conteúdo -->
    <section class="content">
      <div class="bar">
        <h2><?php echo h($catTitle); ?> — <?php echo (int)$total; ?> serviço(s)</h2>
      </div>

      <?php if (!$servicos): ?>
        <p>Nenhum serviço encontrado nesta categoria.</p>
      <?php else: ?>
        <div class="cards" id="lista">
          <?php foreach ($servicos as $s): 
            $profId = (int)$s['id_profissional'];
            $foto   = $s['foto_perfil'] ?: 'avatar.png';
            $prazo  = is_null($s['prazo_dias']) ? '—' : ((int)$s['prazo_dias'] . ' dia(s)');
          ?>
          <article class="card">
            <img class="avatar" src="<?php echo h($foto); ?>" alt="<?php echo h($s['nome'] ?? 'Profissional'); ?>">
            <div>
              <h4><?php echo h($s['titulo']); ?></h4>
              <p><?php echo h($s['descricao']); ?></p>
              <div class="meta">
                <span><i class="bi bi-person-badge"></i> <?php echo h($s['nome']); ?></span>
                <span><i class="bi bi-cash-coin"></i> <?php echo money_br($s['preco_min']); ?></span>
                <span><i class="bi bi-stopwatch"></i> <?php echo h($prazo); ?></span>
              </div>
              <div class="actions">
                <a class="btn dark"  href="perfil_publico.php?id_profissional=<?php echo $profId; ?>"><i class="bi bi-eye"></i> Ver perfil</a>
                <!-- Se você tiver uma rota para detalhar o serviço, troque o href abaixo -->
                <a class="btn ghost" href="perfil_publico.php?id_profissional=<?php echo $profId; ?>#servicos"><i class="bi bi-list-task"></i> Ver serviço</a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($pages > 1): ?>
          <nav class="pagination" aria-label="Paginação de serviços">
            <?php
              $base = 'categoria.php?cat=' . urlencode($catTitle) . '&page=';
              $prev = max(1, $page-1);
              $next = min($pages, $page+1);
            ?>
            <a href="<?php echo $base . $prev; ?>">&laquo;</a>
            <?php for ($i=1; $i<=$pages; $i++): ?>
              <?php if ($i === $page): ?>
                <span class="current"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="<?php echo $base . $i; ?>"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <a href="<?php echo $base . $next; ?>">&raquo;</a>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>

  </div>
</main>

<footer>© <?php echo date('Y'); ?> Trampay — todos os direitos reservados.</footer>

</body>
</html>
