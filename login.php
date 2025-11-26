<?php
session_start();

// Si ya está logueado, redirigir a dashboard
if (isset($_SESSION['logged_in'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'config/conexion.php';

// Manejo de POST para login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT id, tipo, nombre_completo, celular, correo FROM usuario WHERE correo = ? AND contrasena = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['tipo'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                $_SESSION['user_email'] = $user['correo'];
                $_SESSION['user_phone'] = $user['celular'];
                
                // Redirigir según tipo de usuario
                if ($user['tipo'] === 'administrador') {
                    header('Location: index.php?page=dashboard');
                } else {
                    header('Location: index.php?page=dashboard');
                }
                exit;
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error = "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor ingresa usuario y contraseña.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=0.85,maximum-scale=2.0,user-scalable=yes" />
<title>Iniciar sesión - Maka Dashboard</title>
<!-- Enlace al CSS principal consolidado -->
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page-body">
  <!-- Contenedor principal con el formulario posicionado a la derecha -->
  <div class="auth-container">
    <div class="auth-form-box" role="main">
      
      <h1 id="login-title" class="auth-title">¡Bienvenido a Maka!</h1>
      <p class="auth-subtitle">Inicia sesión para continuar</p>

      <?php if (isset($error)): ?>
      <div class="auth-error">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <form id="loginForm" method="post" class="auth-form">
        <div class="auth-field">
          <label for="username">Correo Electrónico</label>
          <input id="username" name="username" type="email" autocomplete="username" placeholder="correo@ejemplo.com" required />
        </div>

        <div class="auth-field">
          <label for="password">Contraseña</label>
          <div class="auth-pass-row">
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required />
            <button type="button" class="auth-toggle-pass" id="togglePass" aria-label="Mostrar contraseña">
              <i class="eye-icon">👁️</i>
            </button>
          </div>
        </div>

        <button class="auth-btn-primary" type="submit">Iniciar Sesión</button>
        
        <div class="auth-links">
          <a href="registro.php" class="auth-btn-secondary">Crear Cuenta</a>
        </div>
      </form>

    </div>
  </div>

<script>
  // Toggle mostrar/ocultar contraseña
  (function(){
    const pass = document.getElementById('password');
    const btn = document.getElementById('togglePass');
    btn.addEventListener('click', function(){
      const isPw = pass.type === 'password';
      pass.type = isPw ? 'text' : 'password';
      btn.textContent = isPw ? '🙈' : '👁️';
      btn.setAttribute('aria-pressed', isPw ? "true": "false");
    });
  })();

  // Evitar enviar si faltan datos
  document.getElementById('loginForm').addEventListener('submit', function(e){
    const u = document.getElementById('username').value.trim();
    const p = document.getElementById('password').value.trim();
    if(!u || !p){
      e.preventDefault();
      alert('Por favor completa usuario y contraseña.');
    }
  });
</script>
</body>
</html>