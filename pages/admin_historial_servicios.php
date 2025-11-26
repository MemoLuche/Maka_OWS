<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}

function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Obtener todo el historial de operaciones con información del servicio
$historial = [];
try {
    $sql = "SELECT o.id, o.servicio_id, o.razon_motivo, o.fecha, 
                   s.nombre as servicio_nombre, s.codigo, s.categoria
            FROM operaciones_servicios o 
            LEFT JOIN servicios s ON o.servicio_id = s.id 
            ORDER BY o.fecha DESC";
    $stmt = $pdo->query($sql);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar razon_motivo para separar cambios y razón
    foreach ($historial as &$op) {
        $razon = $op['razon_motivo'];
        
        // Verificar si contiene el formato "cambios | Razón: texto"
        if (strpos($razon, ' | Razón: ') !== false) {
            $partes = explode(' | Razón: ', $razon, 2);
            $cambios_text = $partes[0];
            $op['razon_real'] = $partes[1];
            
            // Separar los cambios individuales
            $op['cambios_detectados'] = explode(' | ', $cambios_text);
        } else {
            // Es un registro antiguo sin formato
            $op['cambios_detectados'] = ['Registro sin detalles'];
            $op['razon_real'] = $razon;
        }
    }
    
} catch (PDOException $e) {
    error_log("Error al cargar historial: " . $e->getMessage());
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="row">
                <!-- Tabla de Historial (lado izquierdo) -->
                <div class="col-lg-8">
                    <div class="card historial-header-card">
                        <div class="historial-header">
                            <h2>
                                <i class="bi bi-clock-history"></i>Historial Completo de Servicios
                            </h2>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($historial) === 0): ?>
                                <div class="historial-empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>No hay operaciones registradas</p>
                                </div>
                            <?php else: ?>
                                <div style="overflow-x: auto;">
                                    <table class="table historial-table-simple">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Servicio</th>
                                                <th>Código</th>
                                                <th>Modificaciones</th>
                                                <th>Razón</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historial as $op): ?>
                                                <tr>
                                                    <td class="historial-id-simple">#<?php echo esc($op['id']); ?></td>
                                                    <td class="historial-fecha-simple">
                                                        <?php echo date('d/m/Y', strtotime($op['fecha'])); ?>
                                                    </td>
                                                    <td class="historial-producto-simple">
                                                        <?php echo esc($op['servicio_nombre'] ?? 'Servicio eliminado'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="historial-codigo-simple"><?php echo esc($op['codigo'] ?? 'N/A'); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="historial-cambios-container">
                                                            <?php foreach ($op['cambios_detectados'] as $cambio): ?>
                                                                <span class="historial-cambios-badge">
                                                                    <?php echo esc($cambio); ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                    <td class="historial-razon-simple">
                                                        <?php echo esc($op['razon_real']); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas (lado derecho) -->
                <div class="col-lg-4">
                    <?php if (count($historial) > 0): ?>
                        <div class="card historial-stat-card-mini mb-3">
                            <div class="card-body text-center">
                                <i class="bi bi-collection historial-stat-icon-mini"></i>
                                <h4 class="historial-stat-number-mini"><?php echo count($historial); ?></h4>
                                <p class="historial-stat-label-mini">Total de Operaciones</p>
                            </div>
                        </div>

                        <div class="card historial-stat-card-mini mb-3">
                            <div class="card-body text-center">
                                <i class="bi bi-briefcase historial-stat-icon-mini"></i>
                                <h4 class="historial-stat-number-mini">
                                    <?php 
                                    $servicios_unicos = array_unique(array_column($historial, 'servicio_id'));
                                    echo count($servicios_unicos); 
                                    ?>
                                </h4>
                                <p class="historial-stat-label-mini">Servicios Modificados</p>
                            </div>
                        </div>

                        <div class="card historial-stat-card-mini">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-check historial-stat-icon-mini"></i>
                                <h4 class="historial-stat-number-mini">
                                    <?php echo date('d/m/Y', strtotime($historial[0]['fecha'])); ?>
                                </h4>
                                <p class="historial-stat-label-mini">Última Modificación</p>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <a href="?page=admin_servicios" 
                               class="btn" 
                               style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
                                <i class="bi bi-arrow-left me-2"></i>Volver a Búsqueda
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
