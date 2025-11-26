<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gestión de Eventos</title>
    <!-- Carga de Bootstrap CSS (Grid y Utilidades) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Carga de Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- ENLACE AL ARCHIVO CSS PRINCIPAL CONSOLIDADO -->
    <link rel="stylesheet" href="style.css">
    
</head>
<body>

<?php
require_once __DIR__ . '/../config/conexion.php';
// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}
// helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

// ============================================
// DASHBOARD PARA ADMINISTRADORES
// ============================================
if ($isAdmin) {
    // DEBUG TEMPORAL
    error_log("=== DEBUG DASHBOARD ADMIN ===");
    error_log("Usuario admin ID: " . $userId);
    error_log("Tipo usuario: " . $_SESSION['user_type']);
    
    // Estadísticas generales del sistema
    try {
        // Total de eventos en el sistema
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos");
        $total_eventos_sistema = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        error_log("Total eventos: " . $total_eventos_sistema);
        
        // DEBUG: Verificar si hay datos
        if ($total_eventos_sistema == 0) {
            // Verificar si la tabla existe y tiene datos
            $debug_stmt = $pdo->query("SELECT COUNT(*) as cnt FROM eventos");
            $debug_count = $debug_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            error_log("DEBUG Dashboard: Total eventos encontrados: " . $debug_count);
        }
        
        // Eventos por estatus
        $stmt = $pdo->query("SELECT estatus, COUNT(*) as cantidad FROM eventos GROUP BY estatus");
        $eventos_por_estatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eventos sin asignar
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos WHERE organizador_id IS NULL OR organizador_id = 0");
        $eventos_sin_asignar = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Total de usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario");
        $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Total de inventario
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventario");
        $total_inventario = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Inventario disponible - usando cualquier valor que no sea NULL
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventario WHERE disponibilidad = 'Disponible'");
            $inventario_disponible = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            // Si falla, intentar con otra columna o asumir todo disponible
            error_log("Error en disponibilidad inventario: " . $e->getMessage());
            $inventario_disponible = $total_inventario; // Por defecto, todo disponible
        }
        
        // Total de servicios
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM servicios");
            $total_servicios = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error contando servicios: " . $e->getMessage());
            $total_servicios = 0;
        }
        
        // Eventos próximos (próximos 30 días)
        $stmt = $pdo->query("SELECT id, nombre_evento, nombre_novio_1, nombre_novio_2, fecha_evento, estatus, organizador_id 
                            FROM eventos 
                            WHERE fecha_evento >= CURDATE() 
                            AND fecha_evento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                            ORDER BY fecha_evento ASC 
                            LIMIT 5");
        $eventos_proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eventos recientes (últimos eventos creados)
        $stmt = $pdo->query("SELECT id, nombre_evento, nombre_novio_1, nombre_novio_2, fecha_evento, estatus
                            FROM eventos 
                            ORDER BY id DESC 
                            LIMIT 5");
        $eventos_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener nombres de organizadores para eventos próximos
        $organizadores = [];
        try {
            $stmt = $pdo->query("SELECT id, nombre_completo FROM usuario WHERE tipo_usuario = 'operativo'");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $org) {
                $organizadores[$org['id']] = $org['nombre_completo'];
            }
        } catch (PDOException $e) {
            error_log("Error obteniendo organizadores: " . $e->getMessage());
        }
        
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas admin: " . $e->getMessage());
        
        // Mostrar error en pantalla para debug
        echo "<div class='alert alert-danger m-4'>";
        echo "<strong>Error cargando estadísticas:</strong> " . htmlspecialchars($e->getMessage());
        echo "</div>";
        
        $total_eventos_sistema = 0;
        $eventos_sin_asignar = 0;
        $total_usuarios = 0;
        $total_inventario = 0;
        $inventario_disponible = 0;
        $total_servicios = 0;
        $eventos_proximos = [];
        $eventos_recientes = [];
        $eventos_por_estatus = [];
        $organizadores = [];
    }
    
    // Procesar contadores por estatus
    $count_pendiente = 0;
    $count_confirmado = 0;
    $count_en_proceso = 0;
    $count_finalizado = 0;
    $count_cancelado = 0;
    
    foreach ($eventos_por_estatus as $item) {
        switch ($item['estatus']) {
            case 'Pendiente':
                $count_pendiente = $item['cantidad'];
                break;
            case 'Confirmado':
                $count_confirmado = $item['cantidad'];
                break;
            case 'En Proceso':
                $count_en_proceso = $item['cantidad'];
                break;
            case 'Finalizado':
                $count_finalizado = $item['cantidad'];
                break;
            case 'Cancelado':
                $count_cancelado = $item['cantidad'];
                break;
        }
    }
    ?>
    
    <div class="site-container">
        
        
        <!-- Header de Admin Dashboard -->
        <div class="dashboard-header mb-4" style="background: linear-gradient(135deg, #99AA8C 0%, #a3b18a 50%, #ccd5ae 100%); padding: 30px; border-radius: 20px; box-shadow: 0 8px 30px rgba(153,170,140,0.2); position: relative; overflow: hidden;">
            <!-- Patrón decorativo de fondo -->
            <div style="position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -30%; left: -5%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%;"></div>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3" style="position: relative; z-index: 1;">
                <div>
                    <h1 class="mb-2" style="color: white; font-weight: 700; font-size: 2.2em; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
                        <i class="bi bi-speedometer2 me-3"></i>Panel de Administración
                    </h1>
                    <p class="mb-0" style="color: rgba(255,255,255,0.95); font-size: 1.1em; font-weight: 500;">
                        <i class="bi bi-calendar-check me-2"></i>
                        Vista general del sistema • <?php echo date('d/m/Y H:i'); ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?page=admin_crear_usuario" class="btn btn-light" style="border-radius: 10px; font-weight: 600; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s; border: none; font-size: 0.9rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                        <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
                    </a>
                    <a href="?page=admin_crear_producto" class="btn btn-light" style="border-radius: 10px; font-weight: 600; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s; border: none; font-size: 0.9rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                        <i class="bi bi-plus-circle me-2"></i>Nuevo Producto
                    </a>
                    <a href="?page=admin_crear_servicio" class="btn btn-light" style="border-radius: 10px; font-weight: 600; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s; border: none; font-size: 0.9rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                        <i class="bi bi-briefcase-fill me-2"></i>Nuevo Servicio
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas reorganizadas en formato rectangular compacto -->
        <div class="row g-3 mb-4">
            <!-- Primera fila - Estadísticas principales (4 columnas iguales) -->
            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.1s both;">
                <div class="dashboard-stat-card" style="transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.transform='translateY(-10px) scale(1.02)'; this.style.boxShadow='0 15px 40px rgba(153,170,140,0.3)'; this.style.borderColor='#99AA8C';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow=''; this.style.borderColor='transparent';">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #a3b18a, #8a9a75); box-shadow: 0 6px 20px rgba(163,177,138,0.4);">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: #2d3e1f; font-size: 2.5em; font-weight: 800;"><?php echo $total_eventos_sistema; ?></div>
                        <div class="stat-label" style="color: #5a6f4d; font-weight: 600; font-size: 0.95em;">Total Eventos</div>
                        <a href="?page=eventos" class="stat-link" style="color: #99AA8C; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.color='#7d8f74'; this.style.transform='translateX(5px)';" onmouseout="this.style.color='#99AA8C'; this.style.transform='translateX(0)';">Ver todos →</a>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.2s both;">
                <div class="dashboard-stat-card" style="transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.transform='translateY(-10px) scale(1.02)'; this.style.boxShadow='0 15px 40px rgba(255,193,7,0.3)'; this.style.borderColor='#ffc107';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow=''; this.style.borderColor='transparent';">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff9800); box-shadow: 0 6px 20px rgba(255,193,7,0.4);">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: #e65100; font-size: 2.5em; font-weight: 800;"><?php echo $eventos_sin_asignar; ?></div>
                        <div class="stat-label" style="color: #f57c00; font-weight: 600; font-size: 0.95em;">Sin Asignar</div>
                        <a href="?page=admin_asignar_eventos" class="stat-link" style="color: #ff9800; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.color='#f57c00'; this.style.transform='translateX(5px)';" onmouseout="this.style.color='#ff9800'; this.style.transform='translateX(0)';">Asignar →</a>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.3s both;">
                <div class="dashboard-stat-card" style="transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.transform='translateY(-10px) scale(1.02)'; this.style.boxShadow='0 15px 40px rgba(153,170,140,0.3)'; this.style.borderColor='#99AA8C';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow=''; this.style.borderColor='transparent';">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #99AA8C, #8a9a75); box-shadow: 0 6px 20px rgba(153,170,140,0.4);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: #2d3e1f; font-size: 2.5em; font-weight: 800;"><?php echo $total_usuarios; ?></div>
                        <div class="stat-label" style="color: #5a6f4d; font-weight: 600; font-size: 0.95em;">Usuarios Totales</div>
                        <a href="?page=admin_usuarios" class="stat-link" style="color: #99AA8C; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.color='#7d8f74'; this.style.transform='translateX(5px)';" onmouseout="this.style.color='#99AA8C'; this.style.transform='translateX(0)';">Gestionar →</a>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.4s both;">
                <div class="dashboard-stat-card" style="transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.transform='translateY(-10px) scale(1.02)'; this.style.boxShadow='0 15px 40px rgba(204,213,174,0.3)'; this.style.borderColor='#ccd5ae';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow=''; this.style.borderColor='transparent';">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ccd5ae, #99AA8C); box-shadow: 0 6px 20px rgba(204,213,174,0.4);">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: #2d3e1f; font-size: 2.5em; font-weight: 800;"><?php echo $inventario_disponible; ?></div>
                        <div class="stat-label" style="color: #5a6f4d; font-weight: 600; font-size: 0.95em;">Inventario Disponible</div>
                        <small style="color: #8a9a75; font-weight: 600;">de <?php echo $total_inventario; ?> total</small>
                    </div>
                </div>
            </div>

            <!-- Segunda fila - Estatus de eventos (4 columnas iguales) -->
            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.5s both;">
                <div class="admin-status-card status-pendiente" style="background: linear-gradient(135deg, #fff9e6 0%, #fff3cc 100%); border: 2px solid #ffd966; border-radius: 15px; transition: all 0.3s; position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(255,217,102,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(255,217,102,0.1); border-radius: 50%;"></div>
                    <i class="bi bi-clock-history" style="font-size: 2em; color: #f39c12; margin-bottom: 10px;"></i>
                    <div class="status-number" style="font-size: 2.8em; font-weight: 800; color: #e67e22;"><?php echo $count_pendiente; ?></div>
                    <div class="status-label" style="font-weight: 600; color: #d68910;">Pendientes</div>
                </div>
            </div>
            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.6s both;">
                <div class="admin-status-card status-confirmado" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border: 2px solid #81c784; border-radius: 15px; transition: all 0.3s; position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(129,199,132,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(129,199,132,0.1); border-radius: 50%;"></div>
                    <i class="bi bi-check-circle-fill" style="font-size: 2em; color: #4caf50; margin-bottom: 10px;"></i>
                    <div class="status-number" style="font-size: 2.8em; font-weight: 800; color: #2e7d32;"><?php echo $count_confirmado; ?></div>
                    <div class="status-label" style="font-weight: 600; color: #388e3c;">Confirmados</div>
                </div>
            </div>
            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.7s both;">
                <div class="admin-status-card status-proceso" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #64b5f6; border-radius: 15px; transition: all 0.3s; position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(100,181,246,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(100,181,246,0.1); border-radius: 50%;"></div>
                    <i class="bi bi-gear-fill" style="font-size: 2em; color: #2196f3; margin-bottom: 10px; animation: rotate 3s linear infinite;"></i>
                    <div class="status-number" style="font-size: 2.8em; font-weight: 800; color: #1565c0;"><?php echo $count_en_proceso; ?></div>
                    <div class="status-label" style="font-weight: 600; color: #1976d2;">En Proceso</div>
                </div>
            </div>
            <div class="col-6 col-lg-3" style="animation: fadeInUp 0.6s ease-out 0.8s both;">
                <div class="admin-status-card status-finalizado" style="background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%); border: 2px solid #ba68c8; border-radius: 15px; transition: all 0.3s; position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(186,104,200,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(186,104,200,0.1); border-radius: 50%;"></div>
                    <i class="bi bi-trophy-fill" style="font-size: 2em; color: #9c27b0; margin-bottom: 10px;"></i>
                    <div class="status-number" style="font-size: 2.8em; font-weight: 800; color: #6a1b9a;"><?php echo $count_finalizado; ?></div>
                    <div class="status-label" style="font-weight: 600; color: #7b1fa2;">Finalizados</div>
                </div>
            </div>
        </div>

        <!-- Contenido principal en 2 columnas -->
        <div class="row g-4">
            
            <!-- Columna Izquierda -->
            <div class="col-12 col-lg-8">
                
                <!-- Eventos Próximos (30 días) -->
                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-range me-2"></i>Próximos Eventos (30 días)
                            </h5>
                            <a href="?page=eventos" class="btn btn-sm btn-outline-secondary">
                                Ver Todos <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (!empty($eventos_proximos)): ?>
                            <div class="admin-eventos-table">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Evento</th>
                                            <th>Fecha</th>
                                            <th>Organizador</th>
                                            <th>Estatus</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eventos_proximos as $evento): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc($evento['nombre_evento'] ?: 'Sin nombre'); ?></strong>
                                                    <?php if (!empty($evento['nombre_novio_1']) || !empty($evento['nombre_novio_2'])): ?>
                                                        <br><small class="text-muted">
                                                            <?php 
                                                            $novios = array_filter([
                                                                $evento['nombre_novio_1'] ?? '', 
                                                                $evento['nombre_novio_2'] ?? ''
                                                            ]);
                                                            echo esc(implode(' & ', $novios)); 
                                                            ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <i class="bi bi-calendar3"></i>
                                                    <?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?>
                                                    <br><small class="text-muted">
                                                        <?php 
                                                        $dias = floor((strtotime($evento['fecha_evento']) - time()) / 86400);
                                                        echo $dias == 0 ? 'Hoy' : ($dias == 1 ? 'Mañana' : "En $dias días");
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($evento['organizador_id']): ?>
                                                        <i class="bi bi-person-check text-success"></i>
                                                        <?php echo esc($organizadores[$evento['organizador_id']] ?? 'ID: ' . $evento['organizador_id']); ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="bi bi-exclamation-triangle"></i> Sin asignar
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge evento-badge-<?php echo strtolower(str_replace(' ', '-', $evento['estatus'])); ?>">
                                                        <?php echo esc($evento['estatus']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?page=evento_detalle&id=<?php echo $evento['id']; ?>" 
                                                       class="btn btn-sm btn-catalogo-detalle">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-small">
                                <i class="bi bi-calendar-x"></i>
                                <p>No hay eventos programados para los próximos 30 días</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Eventos Recientes -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Actividad Reciente
                        </h5>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (!empty($eventos_recientes)): ?>
                            <div class="eventos-lista">
                                <?php foreach ($eventos_recientes as $evento): ?>
                                    <div class="evento-item">
                                        <div class="evento-item-icon">
                                            <i class="bi bi-calendar-plus"></i>
                                        </div>
                                        <div class="evento-item-content">
                                            <div class="evento-item-title">
                                                <?php echo esc($evento['nombre_evento'] ?: 'Evento sin nombre'); ?>
                                            </div>
                                            <div class="evento-item-meta">
                                                <span>
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?>
                                                </span>
                                                <span class="badge evento-badge-<?php echo strtolower(str_replace(' ', '-', $evento['estatus'])); ?>">
                                                    <?php echo esc($evento['estatus']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="evento-item-action">
                                            <a href="?page=evento_detalle&id=<?php echo $evento['id']; ?>" 
                                               class="btn btn-sm btn-catalogo-detalle">
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-small">
                                <i class="bi bi-inbox"></i>
                                <p>No hay actividad reciente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Columna Derecha -->
            <div class="col-12 col-lg-4">
                
                <!-- Accesos Rápidos Admin -->
                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning-fill me-2"></i>Herramientas Admin
                        </h5>
                    </div>
                    <div class="dashboard-card-body">
                        <div class="d-grid gap-2">
                            <a href="?page=admin_gestion" class="btn btn-quick-action">
                                <i class="bi bi-gear-fill me-2"></i>Gestión General
                            </a>
                            <a href="?page=admin_asignar_eventos" class="btn btn-quick-action">
                                <i class="bi bi-person-workspace me-2"></i>Asignar Eventos
                            </a>
                            <a href="?page=admin_inventario" class="btn btn-quick-action">
                                <i class="bi bi-box-seam me-2"></i>Inventario
                            </a>
                            <a href="?page=admin_servicios" class="btn btn-quick-action">
                                <i class="bi bi-briefcase me-2"></i>Servicios
                            </a>
                            <a href="?page=admin_usuarios" class="btn btn-quick-action">
                                <i class="bi bi-people-fill me-2"></i>Usuarios
                            </a>
                            <a href="?page=admin_historial_inventario" class="btn btn-quick-action">
                                <i class="bi bi-clock-history me-2"></i>Historial
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Recursos -->
                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-pie-chart-fill me-2"></i>Recursos del Sistema
                        </h5>
                    </div>
                    <div class="dashboard-card-body">
                        <div class="resumen-item">
                            <div class="resumen-label">
                                <i class="bi bi-box-seam text-success me-2"></i>
                                Inventario Total
                            </div>
                            <div class="resumen-value"><?php echo $total_inventario; ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Disponibles
                            </div>
                            <div class="resumen-value"><?php echo $inventario_disponible; ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">
                                <i class="bi bi-briefcase text-info me-2"></i>
                                Servicios
                            </div>
                            <div class="resumen-value"><?php echo $total_servicios; ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">
                                <i class="bi bi-people text-primary me-2"></i>
                                Usuarios
                            </div>
                            <div class="resumen-value"><?php echo $total_usuarios; ?></div>
                        </div>
                        
                        <?php if ($total_inventario > 0): ?>
                            <hr class="my-3">
                            <div class="progress-section">
                                <div class="mb-2 d-flex justify-content-between">
                                    <small class="text-muted">Disponibilidad Inventario</small>
                                    <small class="text-muted">
                                        <strong><?php echo round(($inventario_disponible / $total_inventario) * 100); ?>%</strong>
                                    </small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" 
                                         style="width: <?php echo ($inventario_disponible / $total_inventario) * 100; ?>%; background-color: #8a9a75;"
                                         role="progressbar">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alertas y Notificaciones -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bell-fill me-2"></i>Alertas
                        </h5>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if ($eventos_sin_asignar > 0): ?>
                            <div class="alert alert-warning mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong><?php echo $eventos_sin_asignar; ?></strong> evento(s) sin asignar
                                <a href="?page=admin_asignar_eventos" class="alert-link ms-2">Asignar →</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        $eventos_hoy = 0;
                        foreach ($eventos_proximos as $ev) {
                            if (date('Y-m-d', strtotime($ev['fecha_evento'])) === date('Y-m-d')) {
                                $eventos_hoy++;
                            }
                        }
                        if ($eventos_hoy > 0): 
                        ?>
                            <div class="alert alert-info mb-2">
                                <i class="bi bi-calendar-check-fill me-2"></i>
                                <strong><?php echo $eventos_hoy; ?></strong> evento(s) hoy
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($eventos_sin_asignar == 0 && $eventos_hoy == 0): ?>
                            <div class="empty-state-small">
                                <i class="bi bi-check-circle"></i>
                                <p>Todo en orden</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <?php
    
// ============================================
// DASHBOARD PARA USUARIOS OPERATIVOS/CLIENTES
// ============================================
} else {

// Obtener eventos del usuario
try {
    $sql = "SELECT id, nombre_evento, nombre_novio_1, nombre_novio_2, fecha_evento, estatus, ubicacion, imagen_principal 
            FROM eventos 
            WHERE organizador_id = ? 
            ORDER BY fecha_evento ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtrar eventos por estado
    $eventos_proximos = [];
    $eventos_en_proceso = [];
    $eventos_finalizados = 0;
    $evento_proximo = null;
    
    $hoy = date('Y-m-d');
    foreach ($eventos as $evento) {
        if ($evento['estatus'] === 'Finalizado' || $evento['estatus'] === 'Cancelado') {
            $eventos_finalizados++;
        } elseif ($evento['fecha_evento'] >= $hoy) {
            $eventos_proximos[] = $evento;
            if (!$evento_proximo) {
                $evento_proximo = $evento;
            }
        }
        
        if ($evento['estatus'] === 'En Proceso' || $evento['estatus'] === 'Confirmado') {
            $eventos_en_proceso[] = $evento;
        }
    }
    
    $total_eventos = count($eventos);
    
} catch (PDOException $e) {
    $eventos = [];
    $eventos_proximos = [];
    $eventos_en_proceso = [];
    $eventos_finalizados = 0;
    $total_eventos = 0;
    $evento_proximo = null;
    error_log("Error obteniendo eventos: " . $e->getMessage());
}

// Obtener inventario asignado a eventos del usuario
try {
    $sql_inv = "SELECT COUNT(DISTINCT ei.inventario_id) as total_items
                FROM evento_inventario ei
                INNER JOIN eventos e ON ei.evento_id = e.id
                WHERE e.organizador_id = ?";
    $stmt_inv = $pdo->prepare($sql_inv);
    $stmt_inv->execute([$userId]);
    $inventario_asignado = $stmt_inv->fetch(PDO::FETCH_ASSOC)['total_items'] ?? 0;
} catch (PDOException $e) {
    $inventario_asignado = 0;
    error_log("Error obteniendo inventario: " . $e->getMessage());
}

// Obtener servicios asignados a eventos del usuario
try {
    $sql_srv = "SELECT COUNT(DISTINCT es.servicio_id) as total_servicios
                FROM evento_servicio es
                INNER JOIN eventos e ON es.evento_id = e.id
                WHERE e.organizador_id = ?";
    $stmt_srv = $pdo->prepare($sql_srv);
    $stmt_srv->execute([$userId]);
    $servicios_asignados = $stmt_srv->fetch(PDO::FETCH_ASSOC)['total_servicios'] ?? 0;
} catch (PDOException $e) {
    $servicios_asignados = 0;
    error_log("Error obteniendo servicios: " . $e->getMessage());
}

// Calcular días hasta el próximo evento
$dias_proximo_evento = null;
if ($evento_proximo) {
    $fecha_proximo = new DateTime($evento_proximo['fecha_evento']);
    $fecha_hoy = new DateTime($hoy);
    $diff = $fecha_hoy->diff($fecha_proximo);
    $dias_proximo_evento = $diff->days;
}
?>

<div class="site-container">
    
    <!-- Header de bienvenida -->
    <div class="dashboard-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="dashboard-welcome mb-1">
                    <i class="bi bi-house-heart me-2"></i>Bienvenido, <?php echo esc($userName); ?>
                </h1>
                <p class="text-muted mb-0">
                    <i class="bi bi-calendar-check me-1"></i>
                    <?php echo strftime('%A, %d de %B de %Y', strtotime($hoy)); ?>
                </p>
            </div>
            <div>
                <a href="?page=evento_create" class="btn btn-catalogo-asignar">
                    <i class="bi bi-plus-circle me-2"></i>Crear Nuevo Evento
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row g-3 mb-4">
        <!-- Total Eventos -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #a3b18a, #8a9a75);">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_eventos; ?></div>
                    <div class="stat-label">Eventos Totales</div>
                </div>
            </div>
        </div>

        <!-- Eventos Próximos -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #99AA8C, #8a9a75);">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($eventos_proximos); ?></div>
                    <div class="stat-label">Eventos Próximos</div>
                </div>
            </div>
        </div>

        <!-- Inventario Asignado -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ccd5ae, #99AA8C);">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $inventario_asignado; ?></div>
                    <div class="stat-label">Artículos Asignados</div>
                </div>
            </div>
        </div>

        <!-- Servicios Contratados -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e9edc9, #ccd5ae);">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $servicios_asignados; ?></div>
                    <div class="stat-label">Servicios Asignados</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal en 2 columnas -->
    <div class="row g-4">
        
        <!-- Columna Izquierda -->
        <div class="col-12 col-lg-8">
            
            <!-- Próximo Evento Destacado -->
            <?php if ($evento_proximo): ?>
            <div class="dashboard-card mb-4">
                <div class="dashboard-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-star-fill me-2"></i>Tu Próximo Evento
                    </h5>
                </div>
                <div class="dashboard-card-body">
                    <div class="evento-destacado">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <?php 
                                $imagen_evento = (!empty($evento_proximo['imagen_principal'])) ? $evento_proximo['imagen_principal'] : 'imagenes/cover (1).jpg';
                                ?>
                                <img src="<?php echo esc($imagen_evento); ?>" 
                                     alt="Imagen del evento" 
                                     class="evento-imagen"
                                     onerror="this.src='imagenes/cover (1).jpg'">
                            </div>
                            <div class="col-md-8">
                                <h4 class="evento-nombre mb-2">
                                    <?php echo esc($evento_proximo['nombre_evento'] ?: 'Evento sin nombre'); ?>
                                </h4>
                                <?php if (!empty($evento_proximo['nombre_novio_1']) || !empty($evento_proximo['nombre_novio_2'])): ?>
                                    <p class="evento-novios mb-2">
                                        <i class="bi bi-people-fill me-1"></i>
                                        <?php 
                                        $novios = array_filter([
                                            $evento_proximo['nombre_novio_1'] ?? '', 
                                            $evento_proximo['nombre_novio_2'] ?? ''
                                        ]);
                                        echo esc(implode(' & ', $novios)); 
                                        ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="evento-detalles">
                                    <div class="mb-2">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        <strong><?php echo date('d/m/Y', strtotime($evento_proximo['fecha_evento'])); ?></strong>
                                        <?php if ($dias_proximo_evento !== null): ?>
                                            <span class="badge ms-2" style="background-color: #8a9a75;">
                                                <?php 
                                                if ($dias_proximo_evento == 0) {
                                                    echo '¡Hoy!';
                                                } elseif ($dias_proximo_evento == 1) {
                                                    echo 'Mañana';
                                                } else {
                                                    echo "En $dias_proximo_evento días";
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($evento_proximo['ubicacion'])): ?>
                                        <div class="mb-2">
                                            <i class="bi bi-geo-alt-fill me-2"></i>
                                            <?php echo esc($evento_proximo['ubicacion']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <i class="bi bi-flag-fill me-2"></i>
                                        <span class="badge evento-badge-<?php echo strtolower(str_replace(' ', '-', $evento_proximo['estatus'])); ?>">
                                            <?php echo esc($evento_proximo['estatus']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <a href="?page=evento_detalle&id=<?php echo $evento_proximo['id']; ?>" 
                                           class="btn btn-sm btn-catalogo-asignar">
                                            <i class="bi bi-eye me-1"></i>Ver Detalles Completos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista de Eventos Activos -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>Eventos Activos
                        </h5>
                        <a href="?page=eventos" class="btn btn-sm btn-outline-secondary">
                            Ver Todos <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="dashboard-card-body">
                    <?php if (!empty($eventos_en_proceso)): ?>
                        <div class="eventos-lista">
                            <?php foreach (array_slice($eventos_en_proceso, 0, 5) as $evento): ?>
                                <div class="evento-item">
                                    <div class="evento-item-icon">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <div class="evento-item-content">
                                        <div class="evento-item-title">
                                            <?php echo esc($evento['nombre_evento'] ?: 'Evento sin nombre'); ?>
                                        </div>
                                        <div class="evento-item-meta">
                                            <span><i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></span>
                                            <span class="badge evento-badge-<?php echo strtolower(str_replace(' ', '-', $evento['estatus'])); ?>">
                                                <?php echo esc($evento['estatus']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="evento-item-action">
                                        <a href="?page=evento_detalle&id=<?php echo $evento['id']; ?>" 
                                           class="btn btn-sm btn-catalogo-detalle">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class="bi bi-inbox"></i>
                            <p>No tienes eventos activos en este momento</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Columna Derecha -->
        <div class="col-12 col-lg-4">
            
            <!-- Accesos Rápidos -->
            <div class="dashboard-card mb-4">
                <div class="dashboard-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning-fill me-2"></i>Accesos Rápidos
                    </h5>
                </div>
                <div class="dashboard-card-body">
                    <div class="d-grid gap-2">
                        <a href="?page=evento_create" class="btn btn-quick-action">
                            <i class="bi bi-plus-circle me-2"></i>Crear Evento
                        </a>
                        <a href="?page=catalogo" class="btn btn-quick-action">
                            <i class="bi bi-grid-3x3-gap me-2"></i>Ver Catálogo
                        </a>
                        <a href="?page=servicios" class="btn btn-quick-action">
                            <i class="bi bi-briefcase me-2"></i>Ver Servicios
                        </a>
                        <a href="?page=eventos" class="btn btn-quick-action">
                            <i class="bi bi-calendar-event me-2"></i>Mis Eventos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Calendario Pequeño de Próximas Fechas -->
            <div class="dashboard-card mb-4">
                <div class="dashboard-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar2-week me-2"></i>Próximas Fechas
                    </h5>
                </div>
                <div class="dashboard-card-body">
                    <?php if (!empty($eventos_proximos)): ?>
                        <div class="timeline-eventos">
                            <?php foreach (array_slice($eventos_proximos, 0, 5) as $evento): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <div class="timeline-day"><?php echo date('d', strtotime($evento['fecha_evento'])); ?></div>
                                        <div class="timeline-month"><?php echo strtoupper(date('M', strtotime($evento['fecha_evento']))); ?></div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <?php echo esc($evento['nombre_evento'] ?: 'Evento sin nombre'); ?>
                                        </div>
                                        <?php if (!empty($evento['ubicacion'])): ?>
                                            <div class="timeline-location">
                                                <i class="bi bi-geo-alt"></i>
                                                <?php echo esc(substr($evento['ubicacion'], 0, 30)) . (strlen($evento['ubicacion']) > 30 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class="bi bi-calendar-x"></i>
                            <p>No hay eventos programados</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumen de Progreso -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart-fill me-2"></i>Resumen General
                    </h5>
                </div>
                <div class="dashboard-card-body">
                    <div class="resumen-item">
                        <div class="resumen-label">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Eventos Finalizados
                        </div>
                        <div class="resumen-value"><?php echo $eventos_finalizados; ?></div>
                    </div>
                    <div class="resumen-item">
                        <div class="resumen-label">
                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                            En Proceso
                        </div>
                        <div class="resumen-value"><?php echo count($eventos_en_proceso); ?></div>
                    </div>
                    <div class="resumen-item">
                        <div class="resumen-label">
                            <i class="bi bi-calendar-plus text-info me-2"></i>
                            Próximos
                        </div>
                        <div class="resumen-value"><?php echo count($eventos_proximos); ?></div>
                    </div>
                    
                    <?php if ($total_eventos > 0): ?>
                        <hr class="my-3">
                        <div class="progress-section">
                            <div class="mb-2 d-flex justify-content-between">
                                <small class="text-muted">Progreso de Eventos</small>
                                <small class="text-muted">
                                    <strong><?php echo round(($eventos_finalizados / $total_eventos) * 100); ?>%</strong>
                                </small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" 
                                     style="width: <?php echo ($eventos_finalizados / $total_eventos) * 100; ?>%; background-color: #8a9a75;"
                                     role="progressbar">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

<?php } // Fin del if-else admin/operativo ?>

</div>

<!-- DB Connection Checker Script -->
<script src="db_connection_checker.js"></script>