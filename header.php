<?php
// ---------------------------------------------------------------------------
// Header: inicialización de sesión y obtención del nombre de usuario
// Comentario: este bloque garantiza que haya una sesión activa y que la
// variable `$user` contenga el nombre para mostrar en el header.
// ---------------------------------------------------------------------------
$user = 'Admin';
if (session_status() === PHP_SESSION_NONE) session_start();
// Si existen variables de sesión con diferentes keys, usar la disponible
if (isset($_SESSION['user_name'])) $user = $_SESSION['user_name'];
if (isset($_SESSION['username'])) $user = $_SESSION['username'];

// Verificar si es administrador
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

// Avatar: usar la ruta guardada en sesión si está definida, sino imagen por defecto
// Puedes establecerla en tu script de login o desde cualquier lugar con:
// $_SESSION['avatar'] = 'imagenes/tu_foto.jpg';
$avatar = 'imagenes/headerimg.jpg';
if (isset($_SESSION['avatar']) && is_string($_SESSION['avatar']) && $_SESSION['avatar'] !== '') {
  $avatar = $_SESSION['avatar'];
}
?>

<!-- ---------------------------------------------------------------------- -->
<!-- Barra superior (header)                                                  -->
<!-- Comentario: `top-bar` es la barra fija superior; contiene marca,        -->
<!-- navegación principal, información del usuario y botón de logout.       -->
<!-- ---------------------------------------------------------------------- -->
<nav class="top-bar navbar navbar-expand fixed-top">
  <div class="container-fluid d-flex align-items-center">

    <!-- Marca/Nombre de la aplicación -->
    <a class="navbar-brand text-white fw-bold" href="index.php">
      <div class="brand-container">
        <span class="brand-subtitle">OWS</span>
        <span class="brand-main">MAKA</span>
      </div>
    </a>

    <!-- Inicio: Navegación principal en el header -->
    <!-- Comentario: cada enlace usa la clase `animated-btn` definida en CSS -->
    <!-- para aplicar la animación y la transición solicitada. -->
    <ul class="navbar-nav ms-3 me-auto d-flex flex-row">
      <li class="nav-item">
        <!-- Dashboard: link principal -->
        <a class="nav-link animated-btn text-white px-2" href="index.php?page=dashboard">
          <i class="bi bi-house me-1"></i>Dashboard
        </a>
      </li>
      <li class="nav-item">
        <!-- Notificaciones -->
        <a class="nav-link animated-btn text-white px-2" href="index.php?page=notificaciones">
          <i class="bi bi-bell me-1"></i>Notificaciones
        </a>
      </li>
      <li class="nav-item">
        <!-- Eventos -->
        <a class="nav-link animated-btn text-white px-2" href="index.php?page=eventos">
          <i class="bi bi-calendar-event me-1"></i>Eventos
        </a>
      </li>
      <li class="nav-item">
        <!-- Catálogo -->
        <a class="nav-link animated-btn text-white px-2" href="index.php?page=catalogo">
          <i class="bi bi-book me-1"></i>Catálogo
        </a>
      </li>
      <li class="nav-item">
        <!-- Servicios -->
        <a class="nav-link animated-btn text-white px-2" href="index.php?page=servicios">
          <i class="bi bi-briefcase me-1"></i>Servicios
        </a>
      </li>
    
      <?php if ($isAdmin): ?>
      <li class="nav-item">
        <!-- Gestión Admin -->
        <a class="nav-link animated-btn text-white px-2" href="index.php?page=admin_gestion">
          <i class="bi bi-shield-check me-1"></i>Admin
        </a>
      </li>
      <?php endif; ?>
    </ul>
    <!-- Fin: Navegación principal en el header -->

    <!-- Inicio: Panel derecho con configuraciones y avatar -->
    <div class="ms-auto d-flex align-items-center gap-3">
      <!-- Configuraciones como icono -->
      <a href="index.php?page=configuraciones" class="config-icon-link" title="Configuraciones">
        <i class="bi bi-gear-fill"></i>
      </a>
      <!-- Avatar del usuario: enlace directo a 'Mi Cuenta' -->
      <a href="index.php?page=cuenta" title="Mi cuenta">
        <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="rounded-circle header-avatar">
      </a>
    </div>
    <!-- Fin: Panel derecho con configuraciones y avatar -->

  </div>
</nav>
