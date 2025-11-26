<?php
require_once __DIR__ . '/../config/conexion.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}

// Helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Variables para mensajes
$success = '';
$error = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_fallo']) && isset($_POST['mensaje'])) {
    $tipo_fallo = trim($_POST['tipo_fallo']);
    $mensaje = trim($_POST['mensaje']);
    
    // Validaciones
    if (empty($tipo_fallo)) {
        $error = 'Por favor selecciona un tipo de fallo.';
    } elseif (empty($mensaje)) {
        $error = 'Por favor describe el fallo que encontraste.';
    } elseif (strlen($mensaje) < 10) {
        $error = 'El mensaje debe tener al menos 10 caracteres.';
    } else {
        try {
            // Insertar en la base de datos
            $stmt = $pdo->prepare('INSERT INTO fallos (tipo_fallo, mensaje, fecha) VALUES (:tipo_fallo, :mensaje, NOW())');
            $stmt->execute([
                ':tipo_fallo' => $tipo_fallo,
                ':mensaje' => $mensaje
            ]);
            
            $success = '¡Gracias! Tu reporte ha sido enviado exitosamente. Lo revisaremos pronto.';
            
            // Limpiar el formulario
            $_POST = [];
        } catch (PDOException $e) {
            $error = 'Error al enviar el reporte. Por favor intenta nuevamente.';
            // En desarrollo podrías mostrar: $e->getMessage()
        }
    }
}
?>

    <div class="container-fluid p-4">
        <div class="row g-4">
            <div class="col-12">
                <!-- Tarjeta de Reportar Falla -->
                <div class="card reportar-falla-card h-100" style="width:750px; margin:0 auto;">
                    <div class="card-header config-header-custom text-white text-center">
                        <h4 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Reportar Falla
                        </h4>
                    </div>
                    
                    <div class="card-body p-4">
                        <p class="text-center mb-4" style="color: #6b7b5a; font-size: 0.95rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Ayúdanos a mejorar reportando cualquier problema que encuentres
                        </p>

                        <!-- Mensajes de éxito o error -->
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-falla-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo esc($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-falla-error" role="alert">
                                <i class="bi bi-x-circle-fill me-2"></i>
                                <?php echo esc($error); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario de reporte -->
                        <form method="POST" action="" id="formReportarFalla">
                            
                            <!-- Tipo de Fallo -->
                            <div class="mb-4">
                                <label for="tipo_fallo" class="form-label falla-label">
                                    <i class="bi bi-tag-fill me-2"></i>Tipo de Fallo
                                </label>
                                <select class="form-select falla-select" id="tipo_fallo" name="tipo_fallo" required>
                                    <option value="" selected disabled>Selecciona el tipo de fallo...</option>
                                    <option value="Errores de Pedidos">1. Errores de Pedidos</option>
                                    <option value="Errores en Productos">2. Errores en Productos</option>
                                    <option value="La pagina no funciona correctamente">3. La página no funciona correctamente</option>
                                    <option value="Informacion incorrecta o confusa">4. Información incorrecta o confusa</option>
                                    <option value="Errores en la Pagina">5. Errores en la Página</option>
                                </select>
                            </div>

                            <!-- Mensaje/Descripción -->
                            <div class="mb-4">
                                <label for="mensaje" class="form-label falla-label">
                                    <i class="bi bi-chat-left-text-fill me-2"></i>Descripción del Problema
                                </label>
                                <textarea 
                                    class="form-control falla-textarea" 
                                    id="mensaje" 
                                    name="mensaje" 
                                    rows="6" 
                                    placeholder="Describe detalladamente el problema que encontraste. Incluye pasos para reproducirlo si es posible..."
                                    required
                                    minlength="10"
                                ><?php echo isset($_POST['mensaje']) ? esc($_POST['mensaje']) : ''; ?></textarea>
                                <div class="form-text falla-help-text">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Mínimo 10 caracteres. Entre más detalles proporciones, más rápido podremos solucionarlo.
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex justify-content-center gap-3 pt-3 border-top" style="border-color: #e5e8e0 !important;">
                                <button type="submit" class="btn btn-falla-submit">
                                    <i class="bi bi-send-fill me-2"></i>Enviar Reporte
                                </button>
                                <a href="?page=configuraciones" class="btn btn-falla-cancel">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validación adicional en el cliente
        document.getElementById('formReportarFalla').addEventListener('submit', function(e) {
            const tipoFallo = document.getElementById('tipo_fallo').value;
            const mensaje = document.getElementById('mensaje').value.trim();
            
            if (!tipoFallo) {
                e.preventDefault();
                customAlert('Por favor selecciona un tipo de fallo.', 'warning', 'Campo Requerido');
                return false;
            }
            
            if (mensaje.length < 10) {
                e.preventDefault();
                customAlert('El mensaje debe tener al menos 10 caracteres.', 'warning', 'Mensaje Muy Corto');
                return false;
            }
        });

        // Auto-ocultar mensaje de éxito después de 5 segundos
        <?php if (!empty($success)): ?>
        setTimeout(function() {
            const successAlert = document.querySelector('.alert-falla-success');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>