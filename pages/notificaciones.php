<?php
require_once __DIR__ . '/../config/conexion.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in'])) {
        header('Location: ?page=login');
        exit;
}

// Helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Crear tabla si no existe para ocultar notificaciones por usuario
try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones_ocultas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_notificacion INT NOT NULL,
                id_usuario INT NOT NULL,
                fecha_oculto DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_notification (id_notificacion, id_usuario)
        )");
} catch (PDOException $e) {
        error_log("Error al crear tabla notificaciones_ocultas: " . $e->getMessage());
}

// Manejar ocultación de notificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        $userId = (int) $_SESSION['user_id'];
        
        if ($deleteId > 0 && $userId > 0) {
                try {
                        $stmt = $pdo->prepare('INSERT IGNORE INTO notificaciones_ocultas (id_notificacion, id_usuario) VALUES (?, ?)');
                        $stmt->execute([$deleteId, $userId]);
                } catch (PDOException $e) {
                        $error = 'Error al ocultar la notificación: ' . $e->getMessage();
                }
        }
        // Redirigir para evitar reenvío de formulario
        header('Location: ?page=notificaciones');
        exit;
}

// Obtener notificaciones que no estén ocultas por el usuario actual
try {
        $userId = (int) $_SESSION['user_id'];
        $stmt = $pdo->prepare('
                SELECT n.id, n.tipo, n.texto, n.fecha 
                FROM notificaciones n
                LEFT JOIN notificaciones_ocultas no ON n.id = no.id_notificacion AND no.id_usuario = ?
                WHERE no.id IS NULL
                ORDER BY n.fecha DESC
        ');
        $stmt->execute([$userId]);
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
        $notificaciones = [];
        $error = 'Error al leer notificaciones: ' . $e->getMessage();
}

?>

    <div class="container-fluid p-4">
        <h1 class="catalogo-title mb-4">
            Notificaciones
        </h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo esc($error); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (count($notificaciones) === 0): ?>
                <div class="col-12">
                    <div class="alert text-center no-notif">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No hay notificaciones.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notificaciones as $n): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card catalogo-card notificacion-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="tipo-badge"><?php echo esc($n['tipo']); ?></span>
                                    <div class="d-flex align-items-center gap-2">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo esc($n['fecha']); ?>
                                        </small>
                                        <form method="post" id="deleteForm<?php echo (int)$n['id']; ?>" class="d-inline">
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$n['id']; ?>">
                                            <button type="button" onclick="confirmarEliminarNotificacion(<?php echo (int)$n['id']; ?>)" class="btn-delete-notif" title="Eliminar notificación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="mb-0"><?php echo esc($n['texto']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    
    <script>
    function confirmarEliminarNotificacion(id) {
        customConfirm(
            '¿Deseas ocultar esta notificación?<br><small class="text-muted">Dejará de mostrarse pero no se eliminará del sistema.</small>',
            () => {
                document.getElementById('deleteForm' + id).submit();
            },
            'Ocultar Notificación'
        );
    }
    </script>