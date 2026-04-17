<?php
session_start();
$logged_in = isset($_SESSION['user']);
$user = $logged_in ? $_SESSION['user'] : null;
?>

<header>
    <div class="header-top">
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a href="homepage.html">INÍCIO</a></li>
                <li><a href="sobre.html">SOBRE NÓS</a></li>
                <li><a href="profissionais.php">SERVIÇOS</a></li>
                <?php if ($logged_in): ?>
                <li><a href="<?php echo $user['tipo'] === 'Profissional' ? 'profilepview.html' : 'profilecview.html'; ?>">MEU PERFIL</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="logo">
            <img width="250px" src="logo.png" alt="logo">
        </div>
        <div class="icons-search">
            <div class="icons">
                <div class="user-status">
                    <a class="icons" id="btnPerfil" href="#">
                        <i class="bi bi-person<?php echo $logged_in ? '-check' : ''; ?>"></i>
                        <?php if ($logged_in): ?>
                        <span class="user-badge" id="userBadge"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>