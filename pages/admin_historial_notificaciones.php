<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}

function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Obtener todas las notificaciones creadas (sin opciones de eliminar)
try {
    $stmt = $pdo->query("
        SELECT 
            id, 
            tipo, 
            texto, 
            fecha
        FROM notificaciones 
        ORDER BY fecha DESC
    ");
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historial = [];
    $error = "Error al cargar historial: " . $e->getMessage();
}
?>

<div class="container-fluid py-4 admin-historial-notificaciones">
    <!-- Encabezado con diseño mejorado -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10">
            <div class="card shadow-lg" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h1 class="mb-1" style="color: white; font-weight: 700; font-size: 2rem;">
                                <i class="bi bi-clock-history me-2"></i>Historial de Notificaciones
                            </h1>
                            <p class="mb-0" style="color: rgba(255,255,255,0.9); font-size: 0.95rem;">
                                Registro completo de todas las notificaciones del sistema
                            </p>
                        </div>
                        <a href="?page=admin_crear_notificacion" class="btn btn-light btn-lg" style="border-radius: 12px; font-weight: 600; padding: 12px 28px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas mejoradas -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm" style="border: none; border-radius: 15px; background: white;">
                        <div class="card-body text-center p-4">
                            <div class="mb-2" style="width: 60px; height: 60px; margin: 0 auto; background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-bell-fill" style="font-size: 1.8rem; color: white;"></i>
                            </div>
                            <h3 class="mb-1" style="color: #99AA8C; font-weight: 700;"><?php echo count($historial); ?></h3>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem; font-weight: 500;">Total de Notificaciones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm" style="border: none; border-radius: 15px; background: white;">
                        <div class="card-body text-center p-4">
                            <div class="mb-2" style="width: 60px; height: 60px; margin: 0 auto; background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-eye-fill" style="font-size: 1.8rem; color: white;"></i>
                            </div>
                            <h3 class="mb-1" style="color: #17a2b8; font-weight: 700;"><?php echo count($historial); ?></h3>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem; font-weight: 500;">Visibles Actualmente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm" style="border: none; border-radius: 15px; background: white;">
                        <div class="card-body text-center p-4">
                            <div class="mb-2" style="width: 60px; height: 60px; margin: 0 auto; background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-calendar-check-fill" style="font-size: 1.8rem; color: white;"></i>
                            </div>
                            <h3 class="mb-1" style="color: #ffc107; font-weight: 700;">
                                <?php echo count($historial) > 0 ? date('d/m/Y', strtotime($historial[0]['fecha'])) : '-'; ?>
                            </h3>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem; font-weight: 500;">Última Creación</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista del historial con diseño mejorado -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php if (count($historial) === 0): ?>
                <div class="card shadow-sm text-center" style="border: none; border-radius: 20px; padding: 60px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div style="width: 100px; height: 100px; margin: 0 auto 20px; background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(153,170,140,0.3);">
                        <i class="bi bi-inbox fs-1" style="color: white;"></i>
                    </div>
                    <h4 class="mb-2" style="color: #99AA8C; font-weight: 700;">No hay notificaciones creadas</h4>
                    <p class="mb-0 text-muted">Las notificaciones que crees aparecerán aquí</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($historial as $item): ?>
                        <div class="col-12">
                            <div class="card shadow-sm h-100" style="border: none; border-radius: 15px; background: white; transition: all 0.3s; overflow: hidden;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)';">
                                <div class="card-body p-4">
                                    <div class="row align-items-start">
                                        <!-- Icono lateral -->
                                        <div class="col-auto d-none d-md-block">
                                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-bell-fill" style="font-size: 1.4rem; color: white;"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Contenido -->
                                        <div class="col">
                                            <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                                                <span class="badge" style="background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.8em;">
                                                    <i class="bi bi-tag-fill me-1"></i><?php echo esc($item['tipo']); ?>
                                                </span>
                                                <span class="badge bg-secondary" style="padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.8em;">
                                                    <i class="bi bi-hash"></i><?php echo esc($item['id']); ?>
                                                </span>
                                                <span class="text-muted ms-auto" style="font-size: 0.85rem; font-weight: 500;">
                                                    <i class="bi bi-clock me-1" style="color: #99AA8C;"></i>
                                                    <?php echo esc(date('d/m/Y - H:i', strtotime($item['fecha']))); ?>
                                                </span>
                                            </div>
                                            
                                            <div style="padding: 16px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid #99AA8C; border-radius: 10px; margin-top: 12px;">
                                                <p class="mb-0" style="color: #333; line-height: 1.6; font-size: 1rem;">
                                                    <?php echo esc($item['texto']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
