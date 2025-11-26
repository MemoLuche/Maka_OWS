<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}

function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$success = '';
$error = '';

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $confirmar_contrasena = trim($_POST['confirmar_contrasena'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'cliente');
    
    if (empty($nombre_completo) || empty($correo) || empty($contrasena)) {
        $error = "El nombre, correo y contraseña son obligatorios.";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            // Verificar si el correo ya existe
            $check = $pdo->prepare("SELECT id FROM usuario WHERE correo = ?");
            $check->execute([$correo]);
            
            if ($check->fetch()) {
                $error = "El correo '$correo' ya está registrado. Por favor usa otro correo.";
            } else {
                // Insertar nuevo usuario
                $stmt = $pdo->prepare("INSERT INTO usuario (nombre_completo, correo, celular, contrasena, tipo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nombre_completo, $correo, $celular, $contrasena, $tipo]);
                
                $success = "Usuario '$nombre_completo' creado exitosamente.";
                
                // Limpiar formulario
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = "Error al crear el usuario: " . $e->getMessage();
            error_log("Error creando usuario: " . $e->getMessage());
        }
    }
}
?>

<div class="container-fluid py-4">
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?php echo esc($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo esc($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Formulario de Creación -->
            <div class="card evento-create-card shadow-lg">
                <div class="card-header evento-create-header">
                    <h2 class="mb-0">
                        <i class="bi bi-person-plus-fill me-3"></i>Crear Nuevo Usuario
                    </h2>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <form method="POST">
                        <input type="hidden" name="crear_usuario" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-person-fill me-2" style="color: #99AA8C;"></i>Nombre Completo *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre_completo" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['nombre_completo'] ?? ''); ?>" 
                                       placeholder="Ej: Juan Pérez López"
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-envelope-fill me-2" style="color: #99AA8C;"></i>Correo Electrónico *
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       name="correo" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['correo'] ?? ''); ?>" 
                                       placeholder="usuario@ejemplo.com"
                                       required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-phone-fill me-2" style="color: #99AA8C;"></i>Celular
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       name="celular" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['celular'] ?? ''); ?>" 
                                       placeholder="Ej: 1234567890">
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-shield-fill-check me-2" style="color: #99AA8C;"></i>Tipo de Usuario *
                                </label>
                                <select class="form-select" 
                                        name="tipo" 
                                        style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                        required>
                                    <option value="cliente" <?php echo (($_POST['tipo'] ?? '') === 'cliente') ? 'selected' : ''; ?>>Organizador</option>
                                    <option value="administrador" <?php echo (($_POST['tipo'] ?? '') === 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-key-fill me-2" style="color: #99AA8C;"></i>Contraseña *
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       name="contrasena" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       placeholder="Mínimo 6 caracteres"
                                       required>
                                <small class="text-muted">Debe tener al menos 6 caracteres</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-key-fill me-2" style="color: #99AA8C;"></i>Confirmar Contraseña *
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       name="confirmar_contrasena" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       placeholder="Repetir contraseña"
                                       required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between gap-2 mt-3">
                            <a href="?page=admin_usuarios" 
                               class="btn btn-outline-secondary" 
                               style="border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-arrow-left me-2"></i>Volver a Usuarios
                            </a>
                            <button type="submit" 
                                    class="btn" 
                                    style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-person-plus-fill me-2"></i>Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
