<?php
require_once __DIR__ . '/../config/conexion.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in'])) {
        header('Location: ?page=login');
        exit;
}

// Helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

    <div class="container-fluid p-4">
        <div class="row g-4 justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <!-- Tarjeta de Configuración de Notificaciones -->
                <div class="card notif-config-card shadow-sm">
                    <div class="card-header notif-config-header text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-bell-fill me-2"></i>Configuración de Notificaciones
                        </h4>
                    </div>
                    
                    <div class="card-body p-4">
                        <p class="notif-config-subtitle text-center mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            Personaliza cómo deseas recibir tus notificaciones
                        </p>

                        <!-- Notificaciones al Correo -->
                        <div class="notif-option-item">
                            <div class="notif-option-content">
                                <div class="notif-option-icon notif-icon-email">
                                    <i class="bi bi-envelope-fill"></i>
                                </div>
                                <div class="notif-option-text">
                                    <h6 class="notif-option-title">Notificaciones al Correo</h6>
                                    <p class="notif-option-desc">Recibe alertas y actualizaciones en tu correo electrónico</p>
                                </div>
                            </div>
                            <div class="notif-option-toggle">
                                <label class="notif-switch">
                                    <input type="checkbox" id="switchCorreo" checked>
                                    <span class="notif-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Notificaciones al Celular -->
                        <div class="notif-option-item">
                            <div class="notif-option-content">
                                <div class="notif-option-icon notif-icon-phone">
                                    <i class="bi bi-phone-fill"></i>
                                </div>
                                <div class="notif-option-text">
                                    <h6 class="notif-option-title">Notificaciones al Celular</h6>
                                    <p class="notif-option-desc">Recibe notificaciones push en tu dispositivo móvil</p>
                                </div>
                            </div>
                            <div class="notif-option-toggle">
                                <label class="notif-switch">
                                    <input type="checkbox" id="switchCelular" checked>
                                    <span class="notif-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Notificaciones de Productos -->
                        <div class="notif-option-item">
                            <div class="notif-option-content">
                                <div class="notif-option-icon notif-icon-box">
                                    <i class="bi bi-box-seam-fill"></i>
                                </div>
                                <div class="notif-option-text">
                                    <h6 class="notif-option-title">Notificaciones de Productos</h6>
                                    <p class="notif-option-desc">Recibe alertas sobre stock, nuevos productos y actualizaciones</p>
                                </div>
                            </div>
                            <div class="notif-option-toggle">
                                <label class="notif-switch">
                                    <input type="checkbox" id="switchProductos" checked>
                                    <span class="notif-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Botón de guardar -->
                        <div class="notif-save-section">
                            <button class="btn notif-save-btn" onclick="guardarPreferencias()">
                                <i class="bi bi-check-circle me-2"></i>Guardar Preferencias
                            </button>
                        </div>

                        <!-- Mensaje de confirmación (oculto por defecto) -->
                        <div id="mensajeExito" class="notif-success-msg" style="display: none;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Preferencias guardadas exitosamente
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function guardarPreferencias() {
            // Aquí podrías guardar las preferencias en la base de datos vía AJAX
            const mensaje = document.getElementById('mensajeExito');
            mensaje.style.display = 'block';
            
            // Auto-ocultar después de 3 segundos
            setTimeout(() => {
                mensaje.style.transition = 'opacity 0.5s ease';
                mensaje.style.opacity = '0';
                setTimeout(() => {
                    mensaje.style.display = 'none';
                    mensaje.style.opacity = '1';
                }, 500);
            }, 3000);
        }
    </script>
