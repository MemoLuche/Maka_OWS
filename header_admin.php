<?php
// Header para administrador - Logo MAKA y botón de usuarios
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!-- Barra superior para administrador -->
<nav class="top-bar navbar navbar-expand fixed-top">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    
    <!-- Marca/Nombre de la aplicación -->
    <a class="navbar-brand text-white fw-bold" href="admin_dashboard.php">
      <div class="brand-container">
        <span class="brand-subtitle">OWS</span>
        <span class="brand-main">MAKA</span>
      </div>
    </a>

    <!-- Botón de usuarios a la derecha -->
    <a href="admin_usuarios_gestion.php" class="btn btn-light">
      <i class="bi bi-people-fill me-2"></i>Usuarios
    </a>

  </div>
</nav>
