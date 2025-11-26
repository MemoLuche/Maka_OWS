<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$userId = $_SESSION['user_id'] ?? null;
$currentUser = null;
$successMessage = '';
$errorMessage = '';

// Obtener datos actuales del usuario
if (!empty($userId)) {
    try {
        $stmt = $pdo->prepare("SELECT nombre_completo, correo, celular FROM usuario WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Error al obtener los datos del usuario.";
    }
}

if ($currentUser === null) {
    header('Location: login.php');
    exit;
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    $errors = [];
    
    // Validar datos básicos
    if (empty($nombre)) {
        $errors[] = "El nombre completo es requerido.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El correo electrónico es inválido.";
    }
    
    // Si se intenta cambiar la contraseña
    if (!empty($newPassword) || !empty($currentPassword)) {
        if (empty($currentPassword)) {
            $errors[] = "Debes ingresar tu contraseña actual para cambiarla.";
        } else {
            // Verificar contraseña actual
            try {
                $stmt = $pdo->prepare("SELECT contrasena FROM usuario WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData && $userData['contrasena'] !== $currentPassword) {
                    $errors[] = "La contraseña actual es incorrecta.";
                }
            } catch (PDOException $e) {
                $errors[] = "Error al verificar la contraseña.";
            }
        }
        
        if (empty($newPassword)) {
            $errors[] = "La nueva contraseña no puede estar vacía.";
        } elseif (strlen($newPassword) < 6) {
            $errors[] = "La nueva contraseña debe tener al menos 6 caracteres.";
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "Las contraseñas no coinciden.";
        }
    }
    
    // Si no hay errores, actualizar
    if (empty($errors)) {
        try {
            if (!empty($newPassword)) {
                // Actualizar con nueva contraseña
                $stmt = $pdo->prepare("UPDATE usuario SET nombre_completo = ?, correo = ?, celular = ?, contrasena = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $celular, $newPassword, $userId]);
            } else {
                // Actualizar sin cambiar contraseña
                $stmt = $pdo->prepare("UPDATE usuario SET nombre_completo = ?, correo = ?, celular = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $celular, $userId]);
            }
            
            $successMessage = "Datos actualizados correctamente.";
            
            // Actualizar datos en memoria
            $currentUser['nombre_completo'] = $nombre;
            $currentUser['correo'] = $email;
            $currentUser['celular'] = $celular;
            
        } catch (PDOException $e) {
            $errorMessage = "Error al actualizar los datos: " . $e->getMessage();
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}
?>

  <!-- Header -->
  <div class="account-header" style="background: transparent;">
    <div class="d-flex align-items-center">
      <div class="text-center flex-grow-1">
        <h5 class="mb-0 fw-bold text-dark">Editar Cuenta</h5>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container-fluid p-3">
    <div class="edit-account-container">
    
    <?php if (!empty($successMessage)): ?>
    <div class="edit-alert edit-alert-success">
      <i class="bi bi-check-circle edit-alert-icon"></i>
      <div><?php echo esc($successMessage); ?></div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
    <div class="edit-alert edit-alert-danger">
      <i class="bi bi-exclamation-triangle edit-alert-icon"></i>
      <div><?php echo $errorMessage; ?></div>
    </div>
    <?php endif; ?>

    <!-- Formulario de Edición -->
    <div class="edit-account-card">
      <form method="POST" action="">
        
        <div class="edit-field">
          <label for="nombre" class="edit-account-label">
            <i class="bi bi-person me-2"></i>Nombre Completo
          </label>
          <input type="text" class="edit-account-input" id="nombre" name="nombre" 
                 value="<?php echo esc($currentUser['nombre_completo']); ?>" required>
        </div>
        
        <div class="edit-field">
          <label for="email" class="edit-account-label">
            <i class="bi bi-envelope me-2"></i>Correo Electrónico
          </label>
          <input type="email" class="edit-account-input" id="email" name="email" 
                 value="<?php echo esc($currentUser['correo']); ?>" required>
        </div>
        
        <div class="edit-field">
          <label for="celular" class="edit-account-label">
            <i class="bi bi-telephone me-2"></i>Teléfono
          </label>
          <input type="tel" class="edit-account-input" id="celular" name="celular" 
                 value="<?php echo esc($currentUser['celular']); ?>">
        </div>
        
        <div class="password-section">
          <div class="password-section-title">
            <i class="bi bi-shield-lock"></i>
            <span>Cambiar Contraseña (Opcional)</span>
          </div>
          
          <div class="edit-field">
            <label for="current_password" class="edit-account-label">Contraseña Actual</label>
            <div class="edit-input-group">
              <input type="password" class="edit-account-input" id="current_password" name="current_password" 
                     placeholder="Ingresa tu contraseña actual">
              <button class="toggle-btn" type="button" id="toggleCurrentPass">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <small class="edit-help-text">Requerida solo si deseas cambiar tu contraseña</small>
          </div>
          
          <div class="edit-field">
            <label for="new_password" class="edit-account-label">Nueva Contraseña</label>
            <div class="edit-input-group">
              <input type="password" class="edit-account-input" id="new_password" name="new_password" 
                     placeholder="Mínimo 6 caracteres">
              <button class="toggle-btn" type="button" id="toggleNewPass">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          
          <div class="edit-field">
            <label for="confirm_password" class="edit-account-label">Confirmar Nueva Contraseña</label>
            <div class="edit-input-group">
              <input type="password" class="edit-account-input" id="confirm_password" name="confirm_password" 
                     placeholder="Repite la nueva contraseña">
              <button class="toggle-btn" type="button" id="toggleConfirmPass">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>
        
        <div class="edit-btn-group">
          <button type="submit" class="edit-btn-primary">
            <i class="bi bi-save"></i>
            <span>Guardar Cambios</span>
          </button>
          <a href="?page=cuenta" class="edit-btn-secondary">Cancelar</a>
        </div>
        
      </form>
    </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle para mostrar/ocultar contraseñas
    function setupPasswordToggle(buttonId, inputId) {
      const button = document.getElementById(buttonId);
      const input = document.getElementById(inputId);
      const icon = button.querySelector('i');
      
      button.addEventListener('click', function() {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    }
    
    setupPasswordToggle('toggleCurrentPass', 'current_password');
    setupPasswordToggle('toggleNewPass', 'new_password');
    setupPasswordToggle('toggleConfirmPass', 'confirm_password');
  </script>