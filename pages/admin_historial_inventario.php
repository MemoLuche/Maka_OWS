<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}

function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Obtener todo el historial de operaciones con información del producto
$historial = [];
try {
    $sql = "SELECT o.id_operacion, o.id_inventario, o.razon_motivo, o.fecha, 
                   i.nombre as producto_nombre, i.categoria
            FROM operaciones_inventario o 
            LEFT JOIN inventario i ON o.id_inventario = i.codigo 
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
                                <i class="bi bi-clock-history"></i>Historial Completo
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
                                                <th>Producto</th>
                                                <th>Código</th>
                                                <th>Modificaciones</th>
                                                <th>Razón</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historial as $op): ?>
                                                <tr>
                                                    <td class="historial-id-simple">#<?php echo esc($op['id_operacion']); ?></td>
                                                    <td class="historial-fecha-simple">
                                                        <?php echo date('d/m/Y', strtotime($op['fecha'])); ?>
                                                    </td>
                                                    <td class="historial-producto-simple">
                                                        <?php echo esc($op['producto_nombre'] ?? 'Producto eliminado'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="historial-codigo-simple"><?php echo esc($op['id_inventario']); ?></span>
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
                                <i class="bi bi-box-seam historial-stat-icon-mini"></i>
                                <h4 class="historial-stat-number-mini">
                                    <?php 
                                    $productos_unicos = array_unique(array_column($historial, 'id_inventario'));
                                    echo count($productos_unicos); 
                                    ?>
                                </h4>
                                <p class="historial-stat-label-mini">Productos Modificados</p>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
