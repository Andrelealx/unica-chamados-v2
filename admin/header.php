<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF'], ".php");
$pageTitles = [
  'painel'     => 'Painel',
  'dashboard'  => 'Dashboard',
  'visitas'    => 'Visitas',
  'usuarios'   => 'Gerenciar Usuários'
];

$title = isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <a class="navbar-brand" href="painel.php">
    <img src="../assets/img/logo.png" alt="Logo" style="max-height:40px; margin-right:10px;">
    <span>Controle Administrativo</span>
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNavbar" 
          aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  
  <div class="collapse navbar-collapse" id="adminNavbar">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="painel.php"><i class="fas fa-tachometer-alt"></i> Chamados</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="visitas.php"><i class="fas fa-calendar-alt"></i> Visitas</a>
      </li>
      <?php if(isset($_SESSION['admin_id']) && $_SESSION['nivel_acesso'] == 2): ?>
      <li class="nav-item">
        <a class="nav-link" href="usuarios.php"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
      </li>
      <?php endif; ?>
    </ul>
    <ul class="navbar-nav">
      <li class="nav-item">
        <span class="navbar-text mr-3"><?php echo $title; ?></span>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
      </li>
    </ul>
  </div>
</nav>
