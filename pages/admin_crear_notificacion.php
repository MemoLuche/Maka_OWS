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

// Manejo de eliminación de notificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_notificacion'])) {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Notificación eliminada exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al eliminar notificación: " . $e->getMessage();
        }
    }
}

// Manejo de creación de notificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_notificacion'])) {
    $tipo = trim($_POST['tipo'] ?? '');
    $texto = trim($_POST['texto'] ?? '');
    
    if (!empty($tipo) && !empty($texto)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO notificaciones (tipo, texto, fecha) VALUES (?, ?, NOW())");
            $stmt->execute([$tipo, $texto]);
            
            $success = "Notificación creada exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al crear notificación: " . $e->getMessage();
        }
    } else {
        $error = "Por favor completa todos los campos.";
    }
}

// Obtener todas las notificaciones
try {
    $stmt = $pdo->query("SELECT id, tipo, texto, fecha FROM notificaciones ORDER BY fecha DESC");
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notificaciones = [];
}
?>

<div class="container-fluid py-4">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo esc($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo esc($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario de creación -->
    <div class="row justify-content-center mb-5 admin-notificacion-form">
        <div class="col-lg-8 col-xl-6">
            <div class="card evento-create-card shadow-lg">
                <div class="card-header evento-create-header">
                    <h2 class="mb-0">
                        <i class="bi bi-plus-circle me-3"></i>Nueva Notificación
                    </h2>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Notificación</label>
                            <select name="tipo" class="form-select" required>
                                <option value="">Selecciona un tipo</option>
                                <option value="Info">Información</option>
                                <option value="Alerta">Alerta</option>
                                <option value="Importante">Importante</option>
                                <option value="Recordatorio">Recordatorio</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Mensaje</label>
                            <textarea name="texto" class="form-control" rows="4" placeholder="Escribe el mensaje de la notificación..." required></textarea>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="?page=admin_historial_notificaciones" class="btn w-100" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; border-radius: 12px; font-weight: 600; padding: 14px 24px; box-shadow: 0 4px 15px rgba(108,117,125,0.3); transition: all 0.3s; border: none;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(108,117,125,0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(108,117,125,0.3)';">
                                    <i class="bi bi-clock-history me-2"></i>Ver Historial
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="crear_notificacion" class="btn w-100" style="background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); color: white; border-radius: 12px; font-weight: 600; padding: 14px 24px; box-shadow: 0 4px 15px rgba(153,170,140,0.4); transition: all 0.3s; border: none;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(153,170,140,0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(153,170,140,0.4)';">
                                    <i class="bi bi-send-fill me-2"></i>Crear Notificación
                                </button>
                            </div>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>
