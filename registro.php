<?php
session_start();

// Si ya está logueado, redirigir a dashboard
if (isset($_SESSION['logged_in'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'config/conexion.php';

// Manejo de POST para registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $tipo = 'cliente'; // Siempre será cliente

    // Validaciones básicas
    $errors = [];
    if (empty($nombre_completo)) $errors[] = "El nombre completo es obligatorio.";
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo electrónico inválido.";
    if (empty($celular)) $errors[] = "El número de celular es obligatorio.";
    if (strlen($contrasena) != 8) $errors[] = "La contraseña debe tener exactamente 8 caracteres.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO usuario (nombre_completo, correo, celular, contrasena, tipo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre_completo, $correo, $celular, $contrasena, $tipo]);
            $success = "Usuario registrado exitosamente. Puedes iniciar sesión ahora.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $errors[] = "El correo electrónico ya está registrado.";
            } else {
                $errors[] = "Error al registrar el usuario: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=0.85,maximum-scale=2.0,user-scalable=yes" />
<title>Registro de Usuario - Maka Dashboard</title>
<!-- Enlace al CSS principal consolidado -->
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page-body">
  <!-- Contenedor principal con el formulario posicionado a la derecha -->
  <div class="auth-container">
    <div class="auth-form-box" role="main">

      <h1 id="registro-title" class="auth-title">Registro de Usuario</h1>
      <p class="auth-subtitle">Crea tu cuenta en Maka</p>

      <?php if (!empty($errors)): ?>
      <div class="auth-error">
        <ul>
          <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (isset($success)): ?>
      <div class="auth-success">
        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <form id="registroForm" method="post" class="auth-form">
        <div class="auth-field">
          <label for="nombre_completo">Nombre Completo</label>
          <input id="nombre_completo" name="nombre_completo" type="text" placeholder="Tu nombre completo" required value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
        </div>

        <div class="auth-field">
          <label for="correo">Correo Electrónico</label>
          <input id="correo" name="correo" type="email" placeholder="correo@ejemplo.com" required value="<?php echo htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
        </div>

        <div class="auth-field">
          <label for="celular">Celular</label>
          <input id="celular" name="celular" type="tel" placeholder="123-456-7890" required value="<?php echo htmlspecialchars($_POST['celular'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
        </div>

        <div class="auth-field">
          <label for="contrasena">Contraseña (8 caracteres)</label>
          <input id="contrasena" name="contrasena" type="password" placeholder="••••••••" required maxlength="8" />
        </div>

        <button class="auth-btn-primary" type="submit">Registrarse</button>
        
        <div class="auth-links">
          <a href="login.php" class="auth-btn-secondary">Volver al Login</a>
        </div>
      </form>

    </div>
  </div>

<script>
  // Evitar enviar si faltan datos
  document.getElementById('registroForm').addEventListener('submit', function(e){
    const nombre = document.getElementById('nombre_completo').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const celular = document.getElementById('celular').value.trim();
    const contrasena = document.getElementById('contrasena').value.trim();
    if(!nombre || !correo || !celular || contrasena.length !== 8){
      e.preventDefault();
      alert('Por favor completa todos los campos obligatorios correctamente.');
    }
  });
</script>
</body>
</html>