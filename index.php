<?php
session_start();

// Verificación de sesión: si no logueado, redirigir a login
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// Manejar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Detecta qué página se pidió (por URL)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.85, maximum-scale=2.0, user-scalable=yes">
  <title>Maka Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Comentario: agregamos cache-busting usando filemtime para forzar
       al navegador/hosting a solicitar la versión actualizada del CSS
       cuando el archivo cambia. Esto evita problemas de caché en el host. -->
  <?php
    $cssPath = __DIR__ . '/style.css';
    $cssVer = file_exists($cssPath) ? filemtime($cssPath) : time();
  ?>
  <link rel="stylesheet" href="style.css?ver=<?php echo $cssVer; ?>">
</head>
<body>

  <div class="main-content">
    <?php include 'header.php'; ?>
    <div class="p-4">
      <?php include "pages/$page.php"; ?>
    </div>
  </div>

  <!-- Bootstrap JavaScript Bundle (incluye Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Modals Script -->
  <script src="js/custom-modals.js"></script>
  
  <!-- DB Connection Checker -->
  <script src="db_connection_checker.js"></script>
</body>
</html>
