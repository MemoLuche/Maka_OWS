<?php
require_once __DIR__ . '/../config/conexion.php';
// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}

// Helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Verificar si el usuario es administrador
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

// Obtener ID del evento desde URL
$evento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Consultar evento de la base de datos usando PDO
try {
    $stmt = $pdo->prepare("
        SELECT e.*, u.nombre_completo as organizador_nombre
        FROM eventos e
        LEFT JOIN usuario u ON e.organizador_id = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$evento_id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evento) {
        // Si no existe el evento, redirigir a eventos
        header('Location: ?page=eventos');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error obteniendo evento: " . $e->getMessage());
    header('Location: ?page=eventos');
    exit;
}

// Obtener lista de organizadores para el selector (solo si es admin)
$organizadores = [];
if ($isAdmin) {
    try {
        $stmt_org = $pdo->query("SELECT id, nombre_completo, correo FROM usuario WHERE tipo = 'cliente' ORDER BY nombre_completo ASC");
        $organizadores = $stmt_org->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo organizadores: " . $e->getMessage());
    }
}

// Manejo de asignación de organizador (solo admin)
$success = '';
$error_msg = '';
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_organizador'])) {
    $nuevo_organizador_id = $_POST['organizador_id'] ? (int)$_POST['organizador_id'] : null;
    
    try {
        $stmt_update = $pdo->prepare("UPDATE eventos SET organizador_id = ? WHERE id = ?");
        $stmt_update->execute([$nuevo_organizador_id, $evento_id]);
        
        if ($nuevo_organizador_id) {
            $success = "Organizador asignado exitosamente.";
        } else {
            $success = "Organizador desasignado exitosamente.";
        }
        
        // Actualizar el evento en memoria para reflejar el cambio
        $evento['organizador_id'] = $nuevo_organizador_id;
        
        // Recargar el nombre del organizador si se asignó uno
        if ($nuevo_organizador_id) {
            $stmt_nombre = $pdo->prepare("SELECT nombre_completo FROM usuario WHERE id = ?");
            $stmt_nombre->execute([$nuevo_organizador_id]);
            $org_data = $stmt_nombre->fetch(PDO::FETCH_ASSOC);
            if ($org_data) {
                $evento['organizador_nombre'] = $org_data['nombre_completo'];
            }
        } else {
            $evento['organizador_nombre'] = null;
        }
    } catch (PDOException $e) {
        $error_msg = "Error al asignar organizador: " . $e->getMessage();
    }
}

// Consultar inventario asignado al evento
try {
    $stmt_inventario = $pdo->prepare("
        SELECT ei.*, i.nombre, i.categoria, i.material, i.color, i.medida, i.existencia
        FROM evento_inventario ei
        INNER JOIN inventario i ON ei.inventario_codigo = i.codigo
        WHERE ei.evento_id = ?
        ORDER BY i.categoria, i.nombre
    ");
    $stmt_inventario->execute([$evento_id]);
    $mobiliario = $stmt_inventario->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error obteniendo inventario: " . $e->getMessage());
    $mobiliario = [];
}

// Consultar servicios asignados al evento
try {
    $stmt_servicios = $pdo->prepare("
        SELECT es.*, s.nombre, s.categoria, s.codigo, s.descripcion
        FROM evento_servicio es
        INNER JOIN servicios s ON es.servicio_id = s.id
        WHERE es.evento_id = ?
        ORDER BY s.categoria, s.nombre
    ");
    $stmt_servicios->execute([$evento_id]);
    $servicios = $stmt_servicios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error obteniendo servicios: " . $e->getMessage());
    $servicios = [];
}

// Consultar cronograma del evento
try {
    $stmt_cronograma = $pdo->prepare("
        SELECT * FROM evento_cronograma
        WHERE evento_id = ?
        ORDER BY hora_inicio ASC
    ");
    $stmt_cronograma->execute([$evento_id]);
    $cronograma = $stmt_cronograma->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error obteniendo cronograma: " . $e->getMessage());
    $cronograma = [];
}

// Preparar datos adicionales que no están en BD (por ahora)
$checklist = [];
// Usar imagen de BD, con fallback a cover (1).jpg
$evento['imagen_principal'] = (!empty($evento['imagen_principal'])) ? $evento['imagen_principal'] : 'imagenes/cover (1).jpg';

// Formatear fecha
$fecha_formateada = date('d \d\e F, Y', strtotime($evento['fecha_evento']));
$meses_es = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
    'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
    'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
    'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
];
$fecha_formateada = strtr($fecha_formateada, $meses_es);
?>

<div class="container-fluid p-4">
    
    <!-- Botón de regresar -->
    <div class="mb-3">
        <a href="?page=eventos" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Eventos
        </a>
    </div>

    <!-- ========== ASIGNACIÓN DE ORGANIZADOR (SOLO ADMIN) ========== -->
    <?php if ($isAdmin): ?>
    <div class="card evento-info-card mb-4">
        <div class="card-body">
            <h5 class="evento-section-title mb-3">
                <i class="bi bi-person-check-fill me-2"></i>Asignación de Organizador
            </h5>
            
            <div class="row align-items-end">
                <div class="col-md-5">
                    <form method="POST" class="d-flex gap-2 align-items-end">
                        <input type="hidden" name="asignar_organizador" value="1">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-2">
                                <i class="bi bi-person-badge me-1"></i>Organizador Asignado
                            </label>
                            <select name="organizador_id" 
                                    class="form-select"
                                    style="border: 2px solid #e5e8e0; border-radius: 8px; padding: 10px;">
                                <option value="">-- Sin asignar --</option>
                                <?php foreach ($organizadores as $org): ?>
                                    <option value="<?php echo $org['id']; ?>" 
                                            <?php echo ($evento['organizador_id'] == $org['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($org['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" 
                                class="btn btn-primary btn-sm"
                                style="padding: 10px 20px; border-radius: 8px; font-weight: 600; white-space: nowrap;">
                            <i class="bi bi-save me-1"></i>Guardar
                        </button>
                    </form>
                </div>
                
                <div class="col-md-7">
                    <?php if ($evento['organizador_id']): ?>
                        <div class="alert alert-success mb-0 d-flex align-items-center" style="padding: 10px 16px; height: 42px;">
                            <i class="bi bi-person-check-fill me-2" style="font-size: 1.2rem;"></i>
                            <span>Asignado a: <strong><?php echo esc($evento['organizador_nombre']); ?></strong></span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0 d-flex align-items-center" style="padding: 10px 16px; height: 42px;">
                            <i class="bi bi-exclamation-triangle me-2" style="font-size: 1.2rem;"></i>
                            <span>Sin organizador asignado</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========== 1. ENCABEZADO CON DATOS RÁPIDOS ========== -->
    <div class="card evento-detalle-header mb-4">
        <div class="row g-0">
            <!-- Imagen principal -->
            <div class="col-md-4">
                <img src="<?php echo esc($evento['imagen_principal']); ?>" 
                     class="evento-detalle-img" 
                     alt="<?php echo esc($evento['nombre_evento']); ?>">
            </div>
            
            <!-- Información rápida -->
            <div class="col-md-8">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="evento-detalle-titulo"><?php echo esc($evento['nombre_evento']); ?></h2>
                            <p class="text-muted mb-0">ID del Evento: #<?php echo str_pad($evento['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            <span class="badge bg-success mt-2"><?php echo esc($evento['estatus']); ?></span>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="evento-detalle-actions">
                            <?php if ($evento['estatus'] !== 'Finalizado' && $evento['estatus'] !== 'Cancelado'): ?>
                            <button class="btn btn-sm btn-success me-2" onclick="iniciarFinalizacionEvento()" title="Finalizar Evento">
                                <i class="bi bi-check-circle"></i> Finalizar Evento
                            </button>
                            <?php endif; ?>
                            <a href="?page=evento_editar&id=<?php echo $evento_id; ?>" class="btn btn-sm btn-outline-primary me-2" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if ($isAdmin): ?>
                            <button class="btn btn-sm btn-outline-danger me-2" title="Eliminar">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="generarPDFEvento()" title="Descargar PDF">
                                <i class="bi bi-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                    
                    <!-- Datos de los novios/clientes -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Novios / Clientes</h6>
                            <p class="mb-1"><strong><?php echo esc($evento['nombre_novio_1'] . ' & ' . $evento['nombre_novio_2']); ?></strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Responsable del Evento</h6>
                            <p class="mb-0"><strong><?php echo esc($evento['nombre_responsable']); ?></strong></p>
                            <p class="mb-0"><i class="bi bi-telephone me-2"></i><?php echo esc($evento['numero_responsable']); ?></p>
                            <p class="mb-0"><i class="bi bi-envelope me-2"></i><?php echo esc($evento['correo_responsable']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== ACCIONES RÁPIDAS ========== -->
    <div class="card evento-info-card mb-4">
        <div class="card-body">
            <h5 class="evento-section-title mb-3">
                <i class="bi bi-lightning me-2"></i>Acciones Rápidas
            </h5>
            
            <div class="d-flex flex-wrap gap-2">
                <a href="tel:<?php echo esc($evento['numero_responsable']); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-telephone me-1"></i>Contactar Cliente
                </a>
                <button class="btn btn-outline-success" onclick="enviarWhatsApp()">
                    <i class="bi bi-whatsapp me-1"></i>Enviar WhatsApp
                </button>
                <button class="btn btn-outline-info" onclick="enviarEmail()">
                    <i class="bi bi-envelope me-1"></i>Enviar Email
                </button>
                <button class="btn btn-outline-primary" onclick="generarContrato()">
                    <i class="bi bi-file-earmark-text me-1"></i>Generar Contrato
                </button>
                <button class="btn btn-outline-primary" onclick="agregarACalendario()">
                    <i class="bi bi-calendar-plus me-1"></i>Agregar a Calendario
                </button>
            </div>
        </div>
    </div>

    <!-- ========== 2. INFORMACIÓN CLAVE DEL EVENTO ========== -->
    <div class="row g-4 mb-4">
        <!-- Fecha y horarios -->
        <div class="col-lg-6">
            <div class="card evento-info-card h-100">
                <div class="card-body">
                    <h5 class="evento-section-title">
                        <i class="bi bi-calendar-event me-2"></i>Fecha y Horarios
                    </h5>
                    
                    <div class="evento-info-item">
                        <i class="bi bi-calendar3 evento-info-icon"></i>
                        <div>
                            <strong>Fecha del Evento</strong>
                            <p class="mb-0"><?php echo $fecha_formateada; ?></p>
                        </div>
                    </div>
                    
                    <div class="evento-info-item">
                        <i class="bi bi-clock evento-info-icon"></i>
                        <div>
                            <strong>Horarios</strong>
                            <p class="mb-1">⚙️ Montaje: <?php echo esc($evento['hora_inicio_montaje']); ?> - <?php echo esc($evento['hora_fin_montaje']); ?></p>
                            <p class="mb-1">🎉 Evento: <?php echo esc($evento['hora_inicio_evento']); ?> - <?php echo esc($evento['hora_fin_evento']); ?></p>
                        </div>
                    </div>
                    
                    <div class="evento-info-item">
                        <i class="bi bi-people evento-info-icon"></i>
                        <div>
                            <strong>Número de Invitados</strong>
                            <p class="mb-0"><?php echo esc($evento['numero_invitados']); ?> personas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ubicación -->
        <div class="col-lg-6">
            <div class="card evento-info-card h-100">
                <div class="card-body">
                    <h5 class="evento-section-title">
                        <i class="bi bi-geo-alt me-2"></i>Ubicación
                    </h5>
                    
                    <div class="evento-info-item">
                        <i class="bi bi-building evento-info-icon"></i>
                        <div>
                            <strong><?php echo esc($evento['ubicacion']); ?></strong>
                            <p class="mb-0 text-muted"><?php echo esc($evento['direccion_completa']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Mapa con API de Google Maps -->
                    <div class="mt-3 mb-3">
                        <div id="map" style="width: 100%; height: 250px; border-radius: 8px; border: 1px solid #ddd;"></div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($evento['direccion_completa']); ?>" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-primary me-2">
                            <i class="bi bi-map me-1"></i>Abrir en Google Maps
                        </a>
                        <button class="btn btn-sm btn-outline-secondary" onclick="compartirUbicacion()">
                            <i class="bi bi-share me-1"></i>Compartir Ubicación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 3. CRONOGRAMA DEL EVENTO ========== -->
    <div class="card evento-info-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="evento-section-title">
                    <i class="bi bi-clock-history me-2"></i>Cronograma del Evento
                </h5>
                <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalAgregarCronograma">
                    <i class="bi bi-plus-circle me-1"></i>Agregar Actividad
                </button>
            </div>
            
            <?php if (count($cronograma) > 0): ?>
            <div class="table-responsive">
                <table class="table evento-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Hora Inicio</th>
                            <th style="width: 100px;">Hora Fin</th>
                            <th>Actividad</th>
                            <th>Descripción</th>
                            <th>Responsable</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cronograma as $item): ?>
                        <tr>
                            <td><strong><?php echo date('H:i', strtotime($item['hora_inicio'])); ?></strong></td>
                            <td><strong><?php echo date('H:i', strtotime($item['hora_fin'])); ?></strong></td>
                            <td><strong><?php echo esc($item['actividad']); ?></strong></td>
                            <td class="small"><?php echo esc($item['descripcion'] ?: '-'); ?></td>
                            <td class="small"><?php echo esc($item['responsable'] ?: '-'); ?></td>
                            <td>
                                <?php if($item['completado']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Hecho</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-circle"></i> Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" title="Editar"
                                        onclick="editarCronograma(<?php echo $item['id']; ?>, 
                                                                  '<?php echo esc($item['hora_inicio']); ?>', 
                                                                  '<?php echo esc($item['hora_fin']); ?>', 
                                                                  '<?php echo esc($item['actividad']); ?>', 
                                                                  '<?php echo esc($item['descripcion'] ?? ''); ?>', 
                                                                  '<?php echo esc($item['responsable'] ?? ''); ?>', 
                                                                  <?php echo $item['completado']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"
                                        onclick="eliminarCronograma(<?php echo $item['id']; ?>, 
                                                                   '<?php echo esc($item['actividad']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No hay actividades programadas todavía. Haz clic en "Agregar Actividad" para crear el cronograma del evento.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== 4. INVENTARIO ASIGNADO ========== -->
    <div class="card evento-info-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="evento-section-title">
                    <i class="bi bi-box-seam me-2"></i>Inventario Asignado
                </h5>
                <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalAgregarInventario" onclick="cargarInventarioDisponible()">
                    <i class="bi bi-plus-circle me-1"></i>Agregar Inventario
                </button>
            </div>
            
            <?php if (count($mobiliario) > 0): ?>
            <div class="table-responsive">
                <table class="table evento-table" id="mobiliarioTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Artículo</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                            <th>Disponible</th>
                            <th>Detalles</th>
                            <th>Notas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mobiliario as $item): ?>
                        <tr>
                            <td><code><?php echo esc($item['inventario_codigo']); ?></code></td>
                            <td><strong><?php echo esc($item['nombre']); ?></strong></td>
                            <td><span class="badge bg-info"><?php echo esc($item['categoria']); ?></span></td>
                            <td><?php echo esc($item['cantidad']); ?></td>
                            <td>
                                <?php if($item['existencia'] >= $item['cantidad']): ?>
                                    <span class="badge bg-success"><?php echo esc($item['existencia']); ?> disponibles</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">⚠️ Solo <?php echo esc($item['existencia']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php if($item['color']): ?>
                                    <div>🎨 <?php echo esc($item['color']); ?></div>
                                <?php endif; ?>
                                <?php if($item['medida']): ?>
                                    <div>📏 <?php echo esc($item['medida']); ?></div>
                                <?php endif; ?>
                                <?php if($item['material']): ?>
                                    <div>🔨 <?php echo esc($item['material']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?php echo esc($item['notas'] ?: '-'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" title="Editar cantidad"
                                        onclick="editarInventario('<?php echo esc($item['inventario_codigo']); ?>', 
                                                                  '<?php echo esc($item['nombre']); ?>', 
                                                                  <?php echo $item['cantidad']; ?>, 
                                                                  '<?php echo esc($item['notas'] ?? ''); ?>', 
                                                                  <?php echo $item['existencia']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Quitar del evento"
                                        onclick="eliminarInventario('<?php echo esc($item['inventario_codigo']); ?>', 
                                                                    '<?php echo esc($item['nombre']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No hay inventario asignado a este evento todavía. Haz clic en "Agregar Inventario" para comenzar.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== 4. SERVICIOS CONTRATADOS ========== -->
    <div class="card evento-info-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="evento-section-title mb-0">
                    <i class="bi bi-briefcase me-2"></i>Servicios Contratados
                </h5>
                <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalAgregarServicio" onclick="cargarServiciosDisponibles()">
                    <i class="bi bi-plus-circle me-1"></i>Agregar Servicio
                </button>
            </div>
            
            <?php if (count($servicios) > 0): ?>
            <div class="table-responsive">
                <table class="table evento-table" id="serviciosTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Contacto</th>
                            <th>Costo</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($servicios as $servicio): ?>
                        <tr>
                            <td><code><?php echo esc($servicio['codigo']); ?></code></td>
                            <td>
                                <strong><?php echo esc($servicio['nombre']); ?></strong>
                                <?php if($servicio['descripcion']): ?>
                                    <br><small class="text-muted"><?php echo esc($servicio['descripcion']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-primary"><?php echo esc($servicio['categoria']); ?></span></td>
                            <td><?php echo esc($servicio['proveedor']); ?></td>
                            <td class="small">
                                <?php if($servicio['telefono_proveedor']): ?>
                                    <div>
                                        <i class="bi bi-telephone"></i> 
                                        <a href="tel:<?php echo esc($servicio['telefono_proveedor']); ?>">
                                            <?php echo esc($servicio['telefono_proveedor']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if($servicio['email_proveedor']): ?>
                                    <div>
                                        <i class="bi bi-envelope"></i> 
                                        <a href="mailto:<?php echo esc($servicio['email_proveedor']); ?>">
                                            <?php echo esc($servicio['email_proveedor']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($servicio['costo_acordado']): ?>
                                    <strong>$<?php echo number_format($servicio['costo_acordado'], 2); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">Sin costo</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?php echo esc($servicio['horario_servicio'] ?: '-'); ?></td>
                            <td>
                                <?php if($servicio['confirmado']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Confirmado</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><i class="bi bi-clock"></i> Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" title="Editar"
                                        onclick="editarServicio(<?php echo $servicio['servicio_id']; ?>, 
                                                               '<?php echo esc($servicio['nombre']); ?>', 
                                                               '<?php echo esc($servicio['proveedor']); ?>', 
                                                               '<?php echo esc($servicio['telefono_proveedor'] ?? ''); ?>', 
                                                               '<?php echo esc($servicio['email_proveedor'] ?? ''); ?>', 
                                                               '<?php echo $servicio['costo_acordado'] ?? ''; ?>', 
                                                               '<?php echo esc($servicio['horario_servicio'] ?? ''); ?>', 
                                                               '<?php echo esc($servicio['notas_especiales'] ?? ''); ?>', 
                                                               <?php echo $servicio['confirmado']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Quitar del evento"
                                        onclick="eliminarServicio(<?php echo $servicio['servicio_id']; ?>, 
                                                                 '<?php echo esc($servicio['nombre']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Resumen de costos de servicios -->
            <?php 
            $total_servicios = 0;
            foreach($servicios as $servicio) {
                if($servicio['costo_acordado']) {
                    $total_servicios += $servicio['costo_acordado'];
                }
            }
            if($total_servicios > 0):
            ?>
            <div class="alert alert-info mt-3">
                <strong>Total en Servicios:</strong> $<?php echo number_format($total_servicios, 2); ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No hay servicios asignados a este evento todavía. Haz clic en "Agregar Servicio" para comenzar.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== 5. PRESUPUESTO Y PAGOS ========== -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card evento-presupuesto-card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                    <h6 class="mt-2">Presupuesto Total</h6>
                    <h3 class="mb-0">$<?php echo number_format($evento['presupuesto_total'], 2); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card evento-presupuesto-card bg-success text-white h-100">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                    <h6 class="mt-2">Anticipo Pagado</h6>
                    <h3 class="mb-0">$<?php echo number_format($evento['anticipo_pagado'], 2); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card evento-presupuesto-card bg-warning text-white h-100">
                <div class="card-body text-center">
                    <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
                    <h6 class="mt-2">Saldo Pendiente</h6>
                    <h3 class="mb-0">$<?php echo number_format($evento['saldo_pendiente'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 6. NOTAS ADICIONALES ========== -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card evento-info-card h-100">
                <div class="card-body">
                    <h5 class="evento-section-title">
                        <i class="bi bi-lock me-2"></i>Notas Internas
                    </h5>
                    <p class="text-muted small">Solo visible para el equipo organizador</p>
                    <div class="evento-notas-box">
                        <?php echo nl2br(esc($evento['notas_internas'])); ?>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="bi bi-pencil me-1"></i>Editar Notas
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card evento-info-card h-100">
                <div class="card-body">
                    <h5 class="evento-section-title">
                        <i class="bi bi-chat-left-text me-2"></i>Notas para el Cliente
                    </h5>
                    <p class="text-muted small">Visible para el cliente</p>
                    <div class="evento-notas-box">
                        <?php echo nl2br(esc($evento['notas_cliente'])); ?>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="bi bi-pencil me-1"></i>Editar Notas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 8. MODO DÍA DEL EVENTO ========== -->
    <div class="card evento-dia-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-alarm me-2"></i>Modo "Día del Evento"
                    </h5>
                    <p class="text-muted small mb-0">Vista simplificada para el día de montaje</p>
                </div>
                <button class="btn btn-primary" onclick="activarModoDiaEvento()">
                    <i class="bi bi-eye me-1"></i>Activar Vista Día del Evento
                </button>
            </div>
        </div>
    </div>

    <!-- ESTILOS PARA MODO DÍA DEL EVENTO -->
    <style>
        .modo-dia-section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .modo-dia-card {
            background: #2a2a2a;
            border: 1px solid #404040;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .modo-dia-list-item {
            background: #2d2d2d;
            border-left: 3px solid #667eea;
            padding: 8px 10px;
            margin-bottom: 6px;
            border-radius: 4px;
            transition: all 0.2s;
            color: #ffffff;
            font-size: 0.85rem;
        }
        
        .modo-dia-list-item:hover {
            background: #3a3a3a;
            border-left-color: #8b9bff;
            transform: translateX(3px);
        }
        
        .timeline-item-active {
            background: rgba(16, 185, 129, 0.25) !important;
            border-left: 5px solid #10b981 !important;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 30px rgba(16, 185, 129, 0.6); }
        }
        
        .timeline-item-completed {
            opacity: 0.6;
        }
        
        .checklist-section .form-check {
            padding: 6px 10px;
            margin-bottom: 4px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .checklist-section .form-check:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        
        .checklist-section .form-check-input {
            width: 18px;
            height: 18px;
            border-width: 2px;
            border-color: #6b7280;
            cursor: pointer;
            float: left;
            margin-left: 0;
            margin-right: 8px;
            margin-top: 2px;
        }
        
        .checklist-section .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }
        
        .checklist-section .form-check-label {
            color: #e5e7eb;
            cursor: pointer;
            font-size: 0.85rem;
            line-height: 1.5;
            display: block;
            overflow: hidden;
        }
        
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }
        
        @keyframes progress-bar-stripes {
            0% { background-position: 0 0; }
            100% { background-position: 40px 0; }
        }
        
        .countdown-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }
        
        .emergency-card {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .emergency-card .card-body {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .service-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        
        .inventory-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #000000;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        
        .accordion-button .badge {
            font-weight: 700;
            padding: 4px 10px;
        }
        
        /* Mejorar contraste de botones del accordion */
        .accordion-button {
            font-weight: 600;
            color: #ffffff !important;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #1e1e1e;
            color: #ffffff !important;
        }
        
        /* Mejorar visibilidad de texto en general */
        #modalDiaEvento .text-white-50 {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        #modalDiaEvento .text-muted {
            color: #9ca3af !important;
        }
        
        #modalDiaEvento small {
            font-size: 0.875rem;
        }
        
        /* Scroll personalizado para mejor apariencia */
        #modalDiaEvento ::-webkit-scrollbar {
            width: 8px;
        }
        
        #modalDiaEvento ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        
        #modalDiaEvento ::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 4px;
        }
        
        #modalDiaEvento ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>

    <!-- MODAL: MODO DÍA DEL EVENTO (Full Screen) -->
    <div class="modal fade" id="modalDiaEvento" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark text-white">
                <!-- Header -->
                <div class="modal-header border-bottom border-secondary py-2" style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <i class="bi bi-broadcast-pin text-danger me-2"></i>
                                    <span id="modoDiaNombre"><?php echo htmlspecialchars($evento['nombre_evento']); ?></span>
                                </h5>
                                <p class="mb-0 small text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?php echo date('l, d \d\e F \d\e Y', strtotime($evento['fecha_evento'])); ?>
                                    <span class="mx-2">|</span>
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?php echo htmlspecialchars($evento['ubicacion']); ?>
                                </p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="desactivarModoDiaEvento()">
                                <i class="bi bi-x-circle me-1"></i>Salir del Modo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="modal-body p-0">
                    <div class="container-fluid">
                        <div class="row g-0">
                            
                            <!-- Columna Izquierda: Countdown + Timeline -->
                            <div class="col-lg-4 border-end border-secondary p-4" style="background-color: #1a1a1a;">
                                
                                <!-- Countdown -->
                                <div class="countdown-card mb-3">
                                    <div class="text-white-50 mb-1" style="font-size: 0.7rem; letter-spacing: 1px; font-weight: 600;">TIEMPO PARA EL EVENTO</div>
                                    <div id="countdownDisplay" class="h2 fw-bold mb-1" style="text-shadow: 0 2px 8px rgba(0,0,0,0.3);">--:--:--</div>
                                    <div id="countdownLabel" class="text-white-50" style="font-size: 0.8rem;">Cargando...</div>
                                </div>

                                <!-- Información Crítica -->
                                <div class="emergency-card mb-3">
                                    <div class="p-2 text-center" style="background: rgba(239, 68, 68, 0.9);">
                                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem;"></i>
                                        <div class="mb-0 mt-1" style="font-weight: 700; font-size: 0.85rem;">CONTACTOS DE EMERGENCIA</div>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="mb-2 p-2" style="background: rgba(255,255,255,0.05); border-radius: 6px;">
                                            <div class="text-white-50 mb-1" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px;">Responsable</div>
                                            <div class="small mb-2"><i class="bi bi-person-fill me-1 text-warning"></i><?php echo isset($evento['responsable']) ? htmlspecialchars($evento['responsable']) : 'No especificado'; ?></div>
                                            <div class="d-grid gap-1">
                                                <?php if(isset($evento['telefono']) && !empty($evento['telefono'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($evento['telefono']); ?>" class="btn btn-success btn-sm" style="font-size: 0.8rem; padding: 4px 8px;">
                                                    <i class="bi bi-telephone-fill me-1"></i>Llamar Ahora
                                                </a>
                                                <a href="https://wa.me/52<?php echo htmlspecialchars($evento['telefono']); ?>" target="_blank" class="btn btn-outline-success btn-sm" style="font-size: 0.8rem; padding: 4px 8px;">
                                                    <i class="bi bi-whatsapp me-1"></i>Enviar WhatsApp
                                                </a>
                                                <?php else: ?>
                                                <small class="text-muted">No hay teléfono registrado</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="p-2" style="background: rgba(255,255,255,0.05); border-radius: 6px;">
                                            <div class="text-white-50 mb-1" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px;">Ubicación</div>
                                            <div style="font-size: 0.8rem;" class="mb-2"><i class="bi bi-geo-alt-fill me-1 text-info"></i><?php echo isset($evento['direccion_completa']) ? htmlspecialchars($evento['direccion_completa']) : 'No especificada'; ?></div>
                                            <?php if(isset($evento['direccion_completa']) && !empty($evento['direccion_completa'])): ?>
                                            <a href="https://maps.google.com/?q=<?php echo urlencode($evento['direccion_completa']); ?>" target="_blank" class="btn btn-info btn-sm w-100" style="font-size: 0.8rem; padding: 4px 8px;">
                                                <i class="bi bi-map me-1"></i>Abrir en Google Maps
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline del Día -->
                                <div class="modo-dia-card">
                                    <div class="modo-dia-section-header" style="font-size: 0.9rem; padding: 8px 12px;">
                                        <i class="bi bi-clock-history"></i>
                                        <span>Cronograma del Día</span>
                                    </div>
                                    <div class="p-2" style="max-height: 300px; overflow-y: auto; background: #1a1a1a;">
                                        <div id="timelineContainer">
                                            <!-- Se llenará con JavaScript -->
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Columna Centro: Checklist -->
                            <div class="col-lg-4 border-end border-secondary p-4" style="background-color: #1e1e1e;">
                                
                                <div class="modo-dia-card mb-3">
                                    <div class="modo-dia-section-header" style="font-size: 0.9rem; padding: 8px 12px;">
                                        <i class="bi bi-list-check"></i>
                                        <span>Checklist del Día</span>
                                    </div>
                                    <div class="p-0" style="background: #1a1a1a;">
                                        <div class="accordion accordion-flush" id="checklistAccordion">
                                            
                                            <!-- Pre-Evento / Montaje -->
                                            <div class="accordion-item bg-dark border-secondary">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#checklistMontaje" style="padding: 10px 12px; font-size: 0.9rem;">
                                                        <i class="bi bi-hammer me-2 text-info" style="font-size: 0.9rem;"></i>
                                                        <span class="flex-grow-1">Pre-Evento / Montaje</span>
                                                        <span class="badge bg-warning text-dark ms-2" id="badgeMontaje" style="font-size: 0.75rem;">0/0</span>
                                                    </button>
                                                </h2>
                                                <div id="checklistMontaje" class="accordion-collapse collapse show" data-bs-parent="#checklistAccordion">
                                                    <div class="accordion-body checklist-section" style="max-height: 250px; overflow-y: auto; background: #1e1e1e; padding: 8px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_llegada_sede" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_llegada_sede">Llegada a la sede</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_revision_sede" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_revision_sede">Revisión del espacio</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_entrega_mobiliario" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_entrega_mobiliario">Recepción de mobiliario</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_montaje_mesas" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_montaje_mesas">Montaje de mesas y sillas</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_decoracion" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_decoracion">Instalación de decoración</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_iluminacion" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_iluminacion">Prueba de iluminación</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_audio" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_audio">Prueba de audio</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Ceremonia -->
                                            <div class="accordion-item bg-dark border-secondary">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#checklistCeremonia" style="padding: 10px 12px; font-size: 0.9rem;">
                                                        <i class="bi bi-heart-fill me-2 text-danger" style="font-size: 0.9rem;"></i>
                                                        <span class="flex-grow-1">Ceremonia</span>
                                                        <span class="badge bg-warning text-dark ms-2" id="badgeCeremonia" style="font-size: 0.75rem;">0/0</span>
                                                    </button>
                                                </h2>
                                                <div id="checklistCeremonia" class="accordion-collapse collapse" data-bs-parent="#checklistAccordion">
                                                    <div class="accordion-body checklist-section" style="background: #1e1e1e; padding: 8px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_llegada_novios" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_llegada_novios">Llegada de novios</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_llegada_invitados" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_llegada_invitados">Recepción de invitados</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_inicio_ceremonia" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_inicio_ceremonia">Inicio de ceremonia</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_fotos_ceremonia" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_fotos_ceremonia">Sesión de fotos post-ceremonia</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Recepción -->
                                            <div class="accordion-item bg-dark border-secondary">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#checklistRecepcion" style="padding: 10px 12px; font-size: 0.9rem;">
                                                        <i class="bi bi-cup-straw me-2 text-warning" style="font-size: 0.9rem;"></i>
                                                        <span class="flex-grow-1">Recepción</span>
                                                        <span class="badge bg-warning text-dark ms-2" id="badgeRecepcion" style="font-size: 0.75rem;">0/0</span>
                                                    </button>
                                                </h2>
                                                <div id="checklistRecepcion" class="accordion-collapse collapse" data-bs-parent="#checklistAccordion">
                                                    <div class="accordion-body checklist-section" style="background: #1e1e1e; padding: 8px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_entrada_recepcion" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_entrada_recepcion">Entrada a recepción</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_brindis" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_brindis">Brindis</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_primer_baile" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_primer_baile">Primer baile</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_cena" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_cena">Servicio de cena</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_pastel" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_pastel">Corte de pastel</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_baile_fiesta" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_baile_fiesta">Baile / Fiesta</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Post-Evento / Limpieza -->
                                            <div class="accordion-item bg-dark border-secondary">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#checklistLimpieza" style="padding: 10px 12px; font-size: 0.9rem;">
                                                        <i class="bi bi-trash me-2 text-success" style="font-size: 0.9rem;"></i>
                                                        <span class="flex-grow-1">Post-Evento / Limpieza</span>
                                                        <span class="badge bg-warning text-dark ms-2" id="badgeLimpieza" style="font-size: 0.75rem;">0/0</span>
                                                    </button>
                                                </h2>
                                                <div id="checklistLimpieza" class="accordion-collapse collapse" data-bs-parent="#checklistAccordion">
                                                    <div class="accordion-body checklist-section" style="background: #1e1e1e; padding: 8px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_despedida" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_despedida">Despedida de invitados</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_desmontaje" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_desmontaje">Desmontaje de equipo</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_limpieza_sede" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_limpieza_sede">Limpieza de sede</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_recoger_mobiliario" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_recoger_mobiliario">Recolección de mobiliario</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="check_revision_final" onchange="actualizarProgreso()">
                                                            <label class="form-check-label" for="check_revision_final">Revisión final del espacio</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Progreso General -->
                                <div class="modo-dia-card">
                                    <div class="p-3" style="background: #1a1a1a;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span style="font-size: 0.9rem; font-weight: 600;">
                                                <i class="bi bi-graph-up me-2 text-success"></i>Progreso General
                                            </span>
                                            <span id="progresoTexto" class="text-white-50" style="font-weight: 500; font-size: 0.85rem;">0 de 0 tareas</span>
                                        </div>
                                        <div class="progress" style="height: 35px; background: #0d0d0d; border-radius: 8px; display: flex; align-items: center;">
                                            <div id="progresoGeneral" 
                                                 class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 style="width: 0%; font-size: 1rem; font-weight: 700; display: flex; align-items: center; justify-content: center; height: 100%;">
                                                0%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Columna Derecha: Servicios y Proveedores -->
                            <div class="col-lg-4 p-4" style="background-color: #232323;">
                                
                                <!-- Servicios Contratados -->
                                <div class="modo-dia-card mb-3">
                                    <div class="modo-dia-section-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 0.9rem; padding: 8px 12px;">
                                        <i class="bi bi-briefcase-fill"></i>
                                        <span>Servicios Contratados</span>
                                    </div>
                                    <div class="p-2" style="max-height: 250px; overflow-y: auto; background: #1a1a1a;">
                                        <div id="serviciosModoDia">
                                            <!-- Se llenará con JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Inventario Asignado -->
                                <div class="modo-dia-card mb-3">
                                    <div class="modo-dia-section-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); font-size: 0.9rem; padding: 8px 12px;">
                                        <i class="bi bi-box-seam-fill"></i>
                                        <span>Inventario Asignado</span>
                                    </div>
                                    <div class="p-2" style="max-height: 250px; overflow-y: auto; background: #1a1a1a;">
                                        <div id="inventarioModoDia">
                                            <!-- Se llenará con JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Notas Rápidas -->
                                <div class="modo-dia-card">
                                    <div class="modo-dia-section-header" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); font-size: 0.9rem; padding: 8px 12px;">
                                        <i class="bi bi-sticky-fill"></i>
                                        <span>Notas Rápidas</span>
                                    </div>
                                    <div class="p-2" style="background: #1a1a1a;">
                                        <textarea id="notasRapidas" class="form-control bg-dark text-white border-secondary" rows="5" placeholder="Escribe notas importantes del día..." style="resize: none; font-size: 0.8rem;"></textarea>
                                        <button class="btn btn-sm btn-success mt-2 w-100" onclick="guardarNotasRapidas()" style="font-size: 0.85rem;">
                                            <i class="bi bi-save-fill me-1"></i>Guardar Notas
                                        </button>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- ========== MODALES ========== -->

<!-- Modal: Agregar Actividad al Cronograma -->
<div class="modal fade" id="modalAgregarCronograma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Agregar Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hora Inicio *</label>
                        <input type="time" class="form-control" id="addCronoHoraInicio" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hora Fin *</label>
                        <input type="time" class="form-control" id="addCronoHoraFin" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Actividad *</label>
                    <input type="text" class="form-control" id="addCronoActividad" placeholder="Ej: Montaje de mesas" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" id="addCronoDescripcion" rows="3" placeholder="Detalles adicionales de la actividad..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Responsable</label>
                    <input type="text" class="form-control" id="addCronoResponsable" placeholder="Nombre del responsable">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarCronograma()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Actividad del Cronograma -->
<div class="modal fade" id="modalEditarCronograma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editCronoId">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hora Inicio *</label>
                        <input type="time" class="form-control" id="editCronoHoraInicio" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hora Fin *</label>
                        <input type="time" class="form-control" id="editCronoHoraFin" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Actividad *</label>
                    <input type="text" class="form-control" id="editCronoActividad" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" id="editCronoDescripcion" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Responsable</label>
                    <input type="text" class="form-control" id="editCronoResponsable">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editCronoCompletado">
                    <label class="form-check-label" for="editCronoCompletado">
                        Marcar como completado
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="actualizarCronograma()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Inventario -->
<div class="modal fade" id="modalAgregarInventario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i>Agregar Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Buscar Item</label>
                    <input type="text" class="form-control" id="buscarInventario" placeholder="Buscar por nombre, código o categoría...">
                </div>
                <div id="listaInventario" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-center text-muted">Cargando inventario...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Inventario -->
<div class="modal fade" id="modalEditarInventario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editInvCodigo">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <input type="text" class="form-control" id="editInvNombre" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" id="editInvCantidad" min="1">
                    <small class="text-muted">Disponible: <span id="editInvDisponible"></span></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" id="editInvNotas" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarInventario()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Servicio -->
<div class="modal fade" id="modalAgregarServicio" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-briefcase me-2"></i>Agregar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Buscar Servicio</label>
                    <input type="text" class="form-control" id="buscarServicio" placeholder="Buscar por nombre o categoría...">
                </div>
                <div id="listaServicios" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-center text-muted">Cargando servicios...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Servicio -->
<div class="modal fade" id="modalEditarServicio" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editServId">
                <div class="mb-3">
                    <label class="form-label">Servicio</label>
                    <input type="text" class="form-control" id="editServNombre" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Proveedor *</label>
                    <input type="text" class="form-control" id="editServProveedor" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="editServTelefono">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="editServEmail">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Costo Acordado</label>
                        <input type="number" class="form-control" id="editServCosto" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Horario</label>
                        <input type="text" class="form-control" id="editServHorario" placeholder="Ej: 18:00 - 23:00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas Especiales</label>
                    <textarea class="form-control" id="editServNotas" rows="3"></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editServConfirmado">
                    <label class="form-check-label" for="editServConfirmado">
                        Servicio confirmado
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarServicio()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Finalizar Evento -->
<div class="modal fade" id="modalFinalizarEvento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle me-2"></i>Finalizar Evento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>¡Atención!</strong> Esta acción marcará el evento como finalizado y ejecutará los siguientes procesos:
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-box-seam me-2"></i>Inventario</h6>
                        <ul class="mb-0">
                            <li>Se devolverá todo el inventario a bodega (<?php echo count($mobiliario); ?> items)</li>
                            <li>La disponibilidad se actualizará automáticamente</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-cash-coin me-2"></i>Pagos</h6>
                        <p class="mb-2"><strong>Presupuesto Total:</strong> $<?php echo number_format($evento['presupuesto_total'], 2); ?></p>
                        <p class="mb-2"><strong>Pagado:</strong> $<?php echo number_format($evento['anticipo_pagado'], 2); ?></p>
                        <p class="mb-0"><strong>Pendiente:</strong> $<?php echo number_format($evento['saldo_pendiente'], 2); ?></p>
                        
                        <?php if ($evento['saldo_pendiente'] > 0): ?>
                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Existe un saldo pendiente de <strong>$<?php echo number_format($evento['saldo_pendiente'], 2); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-diagram-3 me-2"></i>Servicios Contratados</h6>
                        <p class="mb-0"><?php echo count($servicios); ?> servicios serán marcados como finalizados</p>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-file-text me-2"></i>Estado del Evento</h6>
                        <p class="mb-0">El evento cambiará de <span class="badge bg-success"><?php echo esc($evento['estatus']); ?></span> a <span class="badge bg-secondary">Finalizado</span></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmarDevolucionInventario" required>
                    <label class="form-check-label" for="confirmarDevolucionInventario">
                        Confirmo que todo el inventario ha sido devuelto a bodega
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmarPagosCompletos" required>
                    <label class="form-check-label" for="confirmarPagosCompletos">
                        Confirmo que los pagos están verificados
                    </label>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><strong>Ingresa tu contraseña para confirmar *</strong></label>
                    <input type="password" class="form-control" id="passwordConfirmacion" placeholder="Contraseña" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notas de Cierre (opcional)</label>
                    <textarea class="form-control" id="notasCierre" rows="3" placeholder="Comentarios finales sobre el evento..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarFinalizacion()">
                    <i class="bi bi-check-circle me-2"></i>Finalizar Evento
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jsPDF para generación de PDFs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Leaflet.js para mapas (OpenStreetMap - Gratis y sin API key) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Inicializar mapa con Leaflet (OpenStreetMap - más seguro y gratuito)
function initMap() {
    const direccion = '<?php echo addslashes($evento['direccion_completa']); ?>';
    const ubicacionNombre = '<?php echo addslashes($evento['ubicacion']); ?>';
    const coordenadas = '<?php echo addslashes($evento['coordenadas_maps'] ?? ''); ?>';
    
    // Coordenadas por defecto (Guadalajara)
    let lat = 20.6597;
    let lng = -103.3496;
    
    // Intentar extraer coordenadas del campo coordenadas_maps
    if (coordenadas) {
        // Puede venir como "lat,lng" o como URL de Google Maps
        if (coordenadas.includes('google.com/maps') || coordenadas.includes('@')) {
            // Extraer coordenadas de URL de Google Maps
            const coordMatch = coordenadas.match(/[@,](-?\d+\.\d+),(-?\d+\.\d+)/);
            if (coordMatch) {
                lat = parseFloat(coordMatch[1]);
                lng = parseFloat(coordMatch[2]);
            }
        } else {
            // Formato simple "lat,lng"
            const parts = coordenadas.split(',');
            if (parts.length === 2) {
                const parsedLat = parseFloat(parts[0].trim());
                const parsedLng = parseFloat(parts[1].trim());
                if (!isNaN(parsedLat) && !isNaN(parsedLng)) {
                    lat = parsedLat;
                    lng = parsedLng;
                }
            }
        }
    }
    
    // Inicializar mapa
    const map = L.map('map').setView([lat, lng], 15);
    
    // Añadir capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Añadir marcador
    L.marker([lat, lng])
        .addTo(map)
        .bindPopup(`<b>${ubicacionNombre}</b><br>${direccion}`)
        .openPopup();
}

// Cargar el mapa cuando la página esté lista
document.addEventListener('DOMContentLoaded', initMap);
</script>
<script>
const eventoId = <?php echo $evento_id; ?>;

// ========== CRONOGRAMA ==========
function guardarCronograma() {
    const horaInicio = document.getElementById('addCronoHoraInicio').value;
    const horaFin = document.getElementById('addCronoHoraFin').value;
    const actividad = document.getElementById('addCronoActividad').value;
    const descripcion = document.getElementById('addCronoDescripcion').value;
    const responsable = document.getElementById('addCronoResponsable').value;

    if (!horaInicio || !horaFin || !actividad) {
        customAlert('Por favor completa los campos obligatorios', 'warning');
        return;
    }

    const formData = new URLSearchParams({
        action: 'add_cronograma',
        evento_id: eventoId,
        hora_inicio: horaInicio,
        hora_fin: horaFin,
        actividad: actividad,
        descripcion: descripcion,
        responsable: responsable
    });

    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            customAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            customAlert(data.message, 'error');
        }
    });
}

function editarCronograma(id, horaInicio, horaFin, actividad, descripcion, responsable, completado) {
    document.getElementById('editCronoId').value = id;
    document.getElementById('editCronoHoraInicio').value = horaInicio;
    document.getElementById('editCronoHoraFin').value = horaFin;
    document.getElementById('editCronoActividad').value = actividad;
    document.getElementById('editCronoDescripcion').value = descripcion || '';
    document.getElementById('editCronoResponsable').value = responsable || '';
    document.getElementById('editCronoCompletado').checked = completado == 1;
    
    new bootstrap.Modal(document.getElementById('modalEditarCronograma')).show();
}

function actualizarCronograma() {
    const id = document.getElementById('editCronoId').value;
    const horaInicio = document.getElementById('editCronoHoraInicio').value;
    const horaFin = document.getElementById('editCronoHoraFin').value;
    const actividad = document.getElementById('editCronoActividad').value;
    const descripcion = document.getElementById('editCronoDescripcion').value;
    const responsable = document.getElementById('editCronoResponsable').value;
    const completado = document.getElementById('editCronoCompletado').checked ? 1 : 0;

    if (!horaInicio || !horaFin || !actividad) {
        customAlert('Por favor completa los campos obligatorios', 'warning');
        return;
    }

    const formData = new URLSearchParams({
        action: 'update_cronograma',
        id: id,
        evento_id: eventoId,
        hora_inicio: horaInicio,
        hora_fin: horaFin,
        actividad: actividad,
        descripcion: descripcion,
        responsable: responsable,
        completado: completado
    });

    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            customAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            customAlert(data.message, 'error');
        }
    });
}

function eliminarCronograma(id, actividad) {
    customConfirm(
        `¿Estás seguro de eliminar la actividad <strong>"${actividad}"</strong> del cronograma?<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        () => {
            const formData = new URLSearchParams({
                action: 'delete_cronograma',
                id: id,
                evento_id: eventoId
            });

            fetch('/maka/api_evento_items.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    customAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    customAlert(data.message, 'error');
                }
            });
        }, '¿Eliminar actividad?'
    );
}

// ========== INVENTARIO ==========
function cargarInventarioDisponible() {
    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_inventario_disponible'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            mostrarInventarioDisponible(data.data);
        }
    });
}

function mostrarInventarioDisponible(items) {
    const lista = document.getElementById('listaInventario');
    if (items.length === 0) {
        lista.innerHTML = '<p class="text-center text-muted">No hay inventario disponible</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    items.forEach(item => {
        html += `
            <div class="list-group-item list-group-item-action" style="cursor: pointer;" 
                 onclick="seleccionarInventario('${item.codigo}', '${item.nombre}', ${item.existencia})">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${item.nombre}</h6>
                        <small><code>${item.codigo}</code> | ${item.categoria}</small>
                        ${item.medida ? `<br><small>📏 ${item.medida}</small>` : ''}
                        ${item.color ? `<small> | 🎨 ${item.color}</small>` : ''}
                    </div>
                    <span class="badge bg-success">${item.existencia} disponibles</span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    lista.innerHTML = html;
}

function seleccionarInventario(codigo, nombre, existencia) {
    const cantidad = prompt(`¿Cuántas unidades de "${nombre}"?\n(Disponibles: ${existencia})`, '1');
    if (cantidad && parseInt(cantidad) > 0) {
        const notas = prompt('Notas adicionales (opcional):', '');
        agregarInventario(codigo, parseInt(cantidad), notas || '');
    }
}

function agregarInventario(codigo, cantidad, notas) {
    const formData = new URLSearchParams({
        action: 'add_inventario',
        evento_id: eventoId,
        codigo: codigo,
        cantidad: cantidad,
        notas: notas
    });

    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            customAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            customAlert(data.message, 'error');
        }
    });
}

function editarInventario(codigo, nombre, cantidad, notas, existencia) {
    document.getElementById('editInvCodigo').value = codigo;
    document.getElementById('editInvNombre').value = nombre;
    document.getElementById('editInvCantidad').value = cantidad;
    document.getElementById('editInvNotas').value = notas || '';
    document.getElementById('editInvDisponible').textContent = existencia;
    new bootstrap.Modal(document.getElementById('modalEditarInventario')).show();
}

function guardarInventario() {
    const formData = new URLSearchParams({
        action: 'update_inventario',
        evento_id: eventoId,
        codigo: document.getElementById('editInvCodigo').value,
        cantidad: document.getElementById('editInvCantidad').value,
        notas: document.getElementById('editInvNotas').value
    });

    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            customAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            customAlert(data.message, 'error');
        }
    });
}

function eliminarInventario(codigo, nombre) {
    customConfirm(
        `¿Estás seguro de eliminar <strong>"${nombre}"</strong> del evento?<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        () => {
            const formData = new URLSearchParams({
                action: 'delete_inventario',
                evento_id: eventoId,
                codigo: codigo
            });

            fetch('/maka/api_evento_items.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    customAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    customAlert(data.message, 'error');
                }
            });
        }, '¿Eliminar producto?'
    );
}

// ========== SERVICIOS ==========
function cargarServiciosDisponibles() {
    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_servicios_disponibles'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            mostrarServiciosDisponibles(data.data);
        }
    });
}

function mostrarServiciosDisponibles(servicios) {
    const lista = document.getElementById('listaServicios');
    if (servicios.length === 0) {
        lista.innerHTML = '<p class="text-center text-muted">No hay servicios disponibles</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    servicios.forEach(serv => {
        html += `
            <div class="list-group-item list-group-item-action" style="cursor: pointer;" 
                 onclick='seleccionarServicio(${JSON.stringify(serv)})'>
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${serv.nombre}</h6>
                        <small><code>${serv.codigo}</code> | <span class="badge bg-primary">${serv.categoria}</span></small>
                        ${serv.descripcion ? `<br><small class="text-muted">${serv.descripcion}</small>` : ''}
                        ${serv.proveedor_default ? `<br><small>👤 ${serv.proveedor_default}</small>` : ''}
                    </div>
                    ${serv.costo_base ? `<span class="badge bg-success">$${parseFloat(serv.costo_base).toFixed(2)}</span>` : ''}
                </div>
            </div>
        `;
    });
    html += '</div>';
    lista.innerHTML = html;
}

function seleccionarServicio(serv) {
    document.getElementById('editServId').value = serv.id;
    document.getElementById('editServNombre').value = serv.nombre;
    document.getElementById('editServProveedor').value = serv.proveedor_default || '';
    document.getElementById('editServTelefono').value = serv.telefono_default || '';
    document.getElementById('editServEmail').value = serv.email_default || '';
    document.getElementById('editServCosto').value = serv.costo_base || '';
    document.getElementById('editServHorario').value = '';
    document.getElementById('editServNotas').value = '';
    document.getElementById('editServConfirmado').checked = false;
    
    bootstrap.Modal.getInstance(document.getElementById('modalAgregarServicio')).hide();
    new bootstrap.Modal(document.getElementById('modalEditarServicio')).show();
}

function guardarServicio() {
    const proveedor = document.getElementById('editServProveedor').value;
    if (!proveedor) {
        customAlert('El proveedor es obligatorio', 'warning');
        return;
    }

    const formData = new URLSearchParams({
        action: 'add_servicio',
        evento_id: eventoId,
        servicio_id: document.getElementById('editServId').value,
        proveedor: proveedor,
        telefono: document.getElementById('editServTelefono').value,
        email: document.getElementById('editServEmail').value,
        costo: document.getElementById('editServCosto').value,
        horario: document.getElementById('editServHorario').value,
        notas: document.getElementById('editServNotas').value,
        confirmado: document.getElementById('editServConfirmado').checked ? 1 : 0
    });

    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            customAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            customAlert(data.message, 'error');
        }
    });
}

function editarServicio(id, nombre, proveedor, telefono, email, costo, horario, notas, confirmado) {
    document.getElementById('editServId').value = id;
    document.getElementById('editServNombre').value = nombre;
    document.getElementById('editServProveedor').value = proveedor;
    document.getElementById('editServTelefono').value = telefono || '';
    document.getElementById('editServEmail').value = email || '';
    document.getElementById('editServCosto').value = costo || '';
    document.getElementById('editServHorario').value = horario || '';
    document.getElementById('editServNotas').value = notas || '';
    document.getElementById('editServConfirmado').checked = confirmado == 1;
    
    // Cambiar el botón guardar para actualizar en vez de agregar
    const btnGuardar = document.querySelector('#modalEditarServicio .btn-primary');
    btnGuardar.onclick = actualizarServicio;
    
    new bootstrap.Modal(document.getElementById('modalEditarServicio')).show();
}

function actualizarServicio() {
    const formData = new URLSearchParams({
        action: 'update_servicio',
        evento_id: eventoId,
        servicio_id: document.getElementById('editServId').value,
        proveedor: document.getElementById('editServProveedor').value,
        telefono: document.getElementById('editServTelefono').value,
        email: document.getElementById('editServEmail').value,
        costo: document.getElementById('editServCosto').value,
        horario: document.getElementById('editServHorario').value,
        notas: document.getElementById('editServNotas').value,
        confirmado: document.getElementById('editServConfirmado').checked ? 1 : 0
    });

    fetch('/maka/api_evento_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            customAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            customAlert(data.message, 'error');
        }
    });
}

function eliminarServicio(id, nombre) {
    customConfirm(
        `¿Estás seguro de eliminar <strong>"${nombre}"</strong> del evento?<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        () => {
            const formData = new URLSearchParams({
                action: 'delete_servicio',
                evento_id: eventoId,
                servicio_id: id
            });

            fetch('/maka/api_evento_items.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    customAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    customAlert(data.message, 'error');
                }
            });
        }, '¿Eliminar servicio?'
    );
}

// ========== FINALIZAR EVENTO ==========
function iniciarFinalizacionEvento() {
    new bootstrap.Modal(document.getElementById('modalFinalizarEvento')).show();
}

function confirmarFinalizacion() {
    // Validar checkboxes
    const checkInventario = document.getElementById('confirmarDevolucionInventario').checked;
    const checkPagos = document.getElementById('confirmarPagosCompletos').checked;
    const password = document.getElementById('passwordConfirmacion').value.trim();
    
    if (!checkInventario || !checkPagos) {
        customAlert('Debes confirmar todos los puntos antes de finalizar el evento', 'warning', 'Validación Requerida');
        return;
    }
    
    if (!password) {
        customAlert('Debes ingresar tu contraseña para confirmar', 'warning', 'Contraseña Requerida');
        return;
    }
    
    // Confirmación final
    customConfirm(
        '¿Estás completamente seguro de finalizar este evento?<br><br><strong class="text-danger">⚠️ Esta acción NO se puede deshacer.</strong><br><br>Se ejecutarán los siguientes procesos:<ul class="text-start mt-2 mb-0"><li>Devolución de inventario a bodega</li><li>Actualización de disponibilidad</li><li>Servicios marcados como finalizados</li><li>Cambio de estado del evento</li></ul>',
        () => {
            const notasCierre = document.getElementById('notasCierre').value.trim();
            
            // Enviar solicitud
            const formData = new URLSearchParams({
                action: 'finalizar_evento',
                evento_id: eventoId,
                password: password,
                notas_cierre: notasCierre
            });
            
            fetch('/maka/api_evento_items.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    customAlert(data.message, 'success', '✅ Evento Finalizado');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    customAlert(data.message, 'error', '❌ Error al Finalizar');
                }
            })
            .catch(err => {
                customAlert('Error de conexión: ' + err.message, 'error', 'Error de Conexión');
            });
        },
        '⚠️ Confirmar Finalización'
    );
}

// Compartir ubicación
function compartirUbicacion() {
    const ubicacion = '<?php echo esc($evento['ubicacion']); ?>';
    const direccion = '<?php echo esc($evento['direccion_completa']); ?>';
    const mapsLink = '<?php echo esc($evento['coordenadas_maps']); ?>';
    
    const mensaje = `📍 Ubicación del Evento: ${ubicacion}\n${direccion}\n\n🗺️ Ver en Google Maps:\n${mapsLink}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Ubicación del Evento',
            text: mensaje
        });
    } else {
        // Copiar al portapapeles
        navigator.clipboard.writeText(mensaje).then(() => {
            customAlert('Ubicación copiada al portapapeles', 'success', '📋 Copiado');
        });
    }
}

// ========== GENERAR PDF ==========
async function generarPDFEvento() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');
    
    // Mostrar indicador de carga
    const loadingMsg = document.createElement('div');
    loadingMsg.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.3);z-index:9999;text-align:center;';
    loadingMsg.innerHTML = '<div style="font-size:18px;margin-bottom:10px;"><i class="bi bi-hourglass-split"></i> Generando PDF...</div><div class="spinner-border text-primary" role="status"></div>';
    document.body.appendChild(loadingMsg);
    
    try {
        // Configuración
        const pageWidth = 210; // A4 width in mm
        const pageHeight = 297; // A4 height in mm
        const margin = 15;
        const contentWidth = pageWidth - (margin * 2);
        let yPos = margin;
        
        // Colores
        const primaryColor = [139, 69, 19]; // Café/Marrón
        const accentColor = [218, 165, 32]; // Dorado
        const textColor = [51, 51, 51];
        const lightGray = [245, 245, 245];
        
        // ========== ENCABEZADO ==========
        pdf.setFillColor(...primaryColor);
        pdf.rect(0, 0, pageWidth, 40, 'F');
        
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(24);
        pdf.setFont('helvetica', 'bold');
        pdf.text('MAKA Eventos', margin, 18);
        
        pdf.setFontSize(18);
        pdf.text('<?php echo addslashes($evento['nombre_evento']); ?>', margin, 30);
        
        // ID del evento
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('ID: #<?php echo str_pad($evento['id'], 4, '0', STR_PAD_LEFT); ?>', pageWidth - margin, 18, { align: 'right' });
        
        yPos = 50;
        
        // ========== INFORMACIÓN GENERAL ==========
        pdf.setTextColor(...textColor);
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Información General', margin, yPos);
        yPos += 8;
        
        // Fondo gris claro
        pdf.setFillColor(...lightGray);
        pdf.rect(margin, yPos - 3, contentWidth, 35, 'F');
        
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Novios/Clientes:', margin + 5, yPos + 3);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo addslashes($evento['nombre_novio_1'] . ' & ' . $evento['nombre_novio_2']); ?>', margin + 45, yPos + 3);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Fecha del Evento:', margin + 5, yPos + 10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo $fecha_formateada; ?>', margin + 45, yPos + 10);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Ubicación:', margin + 5, yPos + 17);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo addslashes($evento['ubicacion']); ?>', margin + 45, yPos + 17);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Dirección:', margin + 5, yPos + 24);
        pdf.setFont('helvetica', 'normal');
        const direccion = pdf.splitTextToSize('<?php echo addslashes($evento['direccion_completa']); ?>', contentWidth - 45);
        pdf.text(direccion, margin + 45, yPos + 24);
        
        yPos += 40;
        
        // ========== HORARIOS ==========
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Horarios', margin, yPos);
        yPos += 8;
        
        pdf.setFillColor(...lightGray);
        pdf.rect(margin, yPos - 3, contentWidth, 20, 'F');
        
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Montaje:', margin + 5, yPos + 3);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo esc($evento['hora_inicio_montaje']); ?> - <?php echo esc($evento['hora_fin_montaje']); ?>', margin + 30, yPos + 3);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Evento:', margin + 5, yPos + 10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo esc($evento['hora_inicio_evento']); ?> - <?php echo esc($evento['hora_fin_evento']); ?>', margin + 30, yPos + 10);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Invitados:', margin + 100, yPos + 3);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo esc($evento['numero_invitados']); ?> personas', margin + 125, yPos + 3);
        
        yPos += 25;
        
        // ========== CONTACTO RESPONSABLE ==========
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Responsable del Evento', margin, yPos);
        yPos += 8;
        
        pdf.setFillColor(...lightGray);
        pdf.rect(margin, yPos - 3, contentWidth, 20, 'F');
        
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Nombre:', margin + 5, yPos + 3);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo addslashes($evento['nombre_responsable']); ?>', margin + 30, yPos + 3);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Teléfono:', margin + 5, yPos + 10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo esc($evento['numero_responsable']); ?>', margin + 30, yPos + 10);
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('Email:', margin + 100, yPos + 10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('<?php echo addslashes($evento['correo_responsable']); ?>', margin + 120, yPos + 10);
        
        yPos += 25;
        
        // ========== PRESUPUESTO ==========
        if (yPos > 240) {
            pdf.addPage();
            yPos = margin;
        }
        
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Presupuesto', margin, yPos);
        yPos += 8;
        
        // Presupuesto Total
        pdf.setFillColor(41, 128, 185); // Azul
        pdf.rect(margin, yPos, contentWidth/3 - 3, 25, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(10);
        pdf.text('Presupuesto Total', margin + (contentWidth/3 - 3)/2, yPos + 8, { align: 'center' });
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('$<?php echo number_format($evento['presupuesto_total'], 2); ?>', margin + (contentWidth/3 - 3)/2, yPos + 18, { align: 'center' });
        
        // Anticipo Pagado
        pdf.setFillColor(39, 174, 96); // Verde
        pdf.rect(margin + contentWidth/3, yPos, contentWidth/3 - 3, 25, 'F');
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('Anticipo Pagado', margin + contentWidth/3 + (contentWidth/3 - 3)/2, yPos + 8, { align: 'center' });
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('$<?php echo number_format($evento['anticipo_pagado'], 2); ?>', margin + contentWidth/3 + (contentWidth/3 - 3)/2, yPos + 18, { align: 'center' });
        
        // Saldo Pendiente
        pdf.setFillColor(243, 156, 18); // Naranja
        pdf.rect(margin + (contentWidth/3)*2, yPos, contentWidth/3, 25, 'F');
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        pdf.text('Saldo Pendiente', margin + (contentWidth/3)*2 + (contentWidth/3)/2, yPos + 8, { align: 'center' });
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('$<?php echo number_format($evento['saldo_pendiente'], 2); ?>', margin + (contentWidth/3)*2 + (contentWidth/3)/2, yPos + 18, { align: 'center' });
        
        yPos += 35;
        
        // ========== CRONOGRAMA ==========
        <?php if (count($cronograma) > 0): ?>
        if (yPos > 230) {
            pdf.addPage();
            yPos = margin;
        }
        
        pdf.setTextColor(...textColor);
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Cronograma del Evento', margin, yPos);
        yPos += 8;
        
        // Encabezado de tabla
        pdf.setFillColor(...primaryColor);
        pdf.rect(margin, yPos, contentWidth, 8, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Hora Inicio', margin + 2, yPos + 5.5);
        pdf.text('Hora Fin', margin + 25, yPos + 5.5);
        pdf.text('Actividad', margin + 45, yPos + 5.5);
        pdf.text('Responsable', margin + 120, yPos + 5.5);
        yPos += 8;
        
        pdf.setTextColor(...textColor);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        
        <?php foreach($cronograma as $idx => $item): ?>
        if (yPos > 270) {
            pdf.addPage();
            yPos = margin;
        }
        
        // Alternar colores de fila
        if (<?php echo $idx; ?> % 2 === 0) {
            pdf.setFillColor(...lightGray);
            pdf.rect(margin, yPos, contentWidth, 7, 'F');
        }
        
        pdf.text('<?php echo date('H:i', strtotime($item['hora_inicio'])); ?>', margin + 2, yPos + 5);
        pdf.text('<?php echo date('H:i', strtotime($item['hora_fin'])); ?>', margin + 25, yPos + 5);
        pdf.text(pdf.splitTextToSize('<?php echo addslashes($item['actividad']); ?>', 70)[0], margin + 45, yPos + 5);
        pdf.text('<?php echo addslashes($item['responsable'] ?: '-'); ?>', margin + 120, yPos + 5);
        
        yPos += 7;
        <?php endforeach; ?>
        
        yPos += 5;
        <?php endif; ?>
        
        // ========== INVENTARIO ==========
        <?php if (count($mobiliario) > 0): ?>
        if (yPos > 220) {
            pdf.addPage();
            yPos = margin;
        }
        
        pdf.setTextColor(...textColor);
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Inventario Asignado', margin, yPos);
        yPos += 8;
        
        // Encabezado de tabla
        pdf.setFillColor(...primaryColor);
        pdf.rect(margin, yPos, contentWidth, 8, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Código', margin + 2, yPos + 5.5);
        pdf.text('Artículo', margin + 25, yPos + 5.5);
        pdf.text('Categoría', margin + 80, yPos + 5.5);
        pdf.text('Cantidad', margin + 120, yPos + 5.5);
        pdf.text('Color', margin + 145, yPos + 5.5);
        yPos += 8;
        
        pdf.setTextColor(...textColor);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        
        <?php foreach($mobiliario as $idx => $item): ?>
        if (yPos > 270) {
            pdf.addPage();
            yPos = margin;
        }
        
        if (<?php echo $idx; ?> % 2 === 0) {
            pdf.setFillColor(...lightGray);
            pdf.rect(margin, yPos, contentWidth, 7, 'F');
        }
        
        pdf.text('<?php echo esc($item['inventario_codigo']); ?>', margin + 2, yPos + 5);
        pdf.text(pdf.splitTextToSize('<?php echo addslashes($item['nombre']); ?>', 50)[0], margin + 25, yPos + 5);
        pdf.text('<?php echo addslashes($item['categoria']); ?>', margin + 80, yPos + 5);
        pdf.text('<?php echo esc($item['cantidad']); ?>', margin + 120, yPos + 5);
        pdf.text('<?php echo addslashes($item['color'] ?: '-'); ?>', margin + 145, yPos + 5);
        
        yPos += 7;
        <?php endforeach; ?>
        
        yPos += 5;
        <?php endif; ?>
        
        // ========== SERVICIOS ==========
        <?php if (count($servicios) > 0): ?>
        if (yPos > 220) {
            pdf.addPage();
            yPos = margin;
        }
        
        pdf.setTextColor(...textColor);
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Servicios Contratados', margin, yPos);
        yPos += 8;
        
        // Encabezado de tabla
        pdf.setFillColor(...primaryColor);
        pdf.rect(margin, yPos, contentWidth, 8, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Servicio', margin + 2, yPos + 5.5);
        pdf.text('Categoría', margin + 60, yPos + 5.5);
        pdf.text('Proveedor', margin + 95, yPos + 5.5);
        pdf.text('Costo', margin + 145, yPos + 5.5);
        pdf.text('Estado', margin + 170, yPos + 5.5);
        yPos += 8;
        
        pdf.setTextColor(...textColor);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        
        <?php foreach($servicios as $idx => $servicio): ?>
        if (yPos > 270) {
            pdf.addPage();
            yPos = margin;
        }
        
        if (<?php echo $idx; ?> % 2 === 0) {
            pdf.setFillColor(...lightGray);
            pdf.rect(margin, yPos, contentWidth, 7, 'F');
        }
        
        pdf.text(pdf.splitTextToSize('<?php echo addslashes($servicio['nombre']); ?>', 55)[0], margin + 2, yPos + 5);
        pdf.text('<?php echo addslashes($servicio['categoria']); ?>', margin + 60, yPos + 5);
        pdf.text(pdf.splitTextToSize('<?php echo addslashes($servicio['proveedor']); ?>', 45)[0], margin + 95, yPos + 5);
        pdf.text('<?php echo $servicio['costo_acordado'] ? '$' . number_format($servicio['costo_acordado'], 2) : '-'; ?>', margin + 145, yPos + 5);
        pdf.text('<?php echo $servicio['confirmado'] ? 'Confirmado' : 'Pendiente'; ?>', margin + 170, yPos + 5);
        
        yPos += 7;
        <?php endforeach; ?>
        
        yPos += 5;
        <?php endif; ?>
        
        // ========== PIE DE PÁGINA ==========
        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(8);
            pdf.setTextColor(150, 150, 150);
            pdf.text(`Página ${i} de ${totalPages}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
            pdf.text('Generado por MAKA Eventos - ' + new Date().toLocaleDateString('es-MX'), margin, pageHeight - 10);
        }
        
        // Guardar PDF
        pdf.save('Evento_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $evento['nombre_evento']); ?>_<?php echo date('Ymd'); ?>.pdf');
        
        document.body.removeChild(loadingMsg);
        
    } catch (error) {
        console.error('Error generando PDF:', error);
        document.body.removeChild(loadingMsg);
        customAlert('Error al generar el PDF. Por favor, intenta nuevamente.', 'error', 'Error en PDF');
    }
}

// Búsqueda en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('buscarInventario')?.addEventListener('input', function(e) {
        const termino = e.target.value.toLowerCase();
        document.querySelectorAll('#listaInventario .list-group-item').forEach(item => {
            const texto = item.textContent.toLowerCase();
            item.style.display = texto.includes(termino) ? '' : 'none';
        });
    });

    document.getElementById('buscarServicio')?.addEventListener('input', function(e) {
        const termino = e.target.value.toLowerCase();
        document.querySelectorAll('#listaServicios .list-group-item').forEach(item => {
            const texto = item.textContent.toLowerCase();
            item.style.display = texto.includes(termino) ? '' : 'none';
        });
    });
});
</script>

<?php if ($success || $error_msg): ?>
<style>
.toast-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    min-width: 350px;
    max-width: 500px;
    animation: slideInRight 0.4s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

.toast-notification.hiding {
    animation: slideOutRight 0.4s ease-in forwards;
}
</style>

<div class="toast-notification" id="toastNotification">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-lg mb-0" role="alert" style="border-left: 5px solid #28a745;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>¡Éxito!</strong>
                    <div><?php echo esc($success); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-lg mb-0" role="alert" style="border-left: 5px solid #dc3545;">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Error</strong>
                    <div><?php echo esc($error_msg); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastElement = document.getElementById('toastNotification');
    if (toastElement) {
        // Auto-hide después de 5 segundos
        setTimeout(function() {
            toastElement.classList.add('hiding');
            setTimeout(function() {
                toastElement.remove();
            }, 400); // Tiempo de la animación de salida
        }, 5000);
    }
});
</script>
<?php endif; ?>

<!-- Modales Personalizados para Alert y Confirm -->
<div class="modal fade" id="customAlertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi me-2" id="alertIcon"></i>
                    <span id="alertTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0" id="alertMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="alertOkBtn">
                    <i class="bi bi-check-lg me-1"></i>Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi bi-question-circle-fill text-warning me-2"></i>
                    <span id="confirmTitle">Confirmación</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0" id="confirmMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="confirmOkBtn">
                    <i class="bi bi-check-lg me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Datos del evento para JavaScript
const eventoData = {
    id: <?php echo $evento_id; ?>,
    nombre: <?php echo json_encode($evento['nombre_evento'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    novio1: <?php echo json_encode($evento['nombre_novio_1'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    novio2: <?php echo json_encode($evento['nombre_novio_2'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    fecha: <?php echo json_encode($evento['fecha_evento'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    hora: <?php echo json_encode($evento['hora_inicio_evento'] ?? '19:00', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    lugar: <?php echo json_encode($evento['ubicacion'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    direccion: <?php echo json_encode($evento['direccion_completa'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    responsable: <?php echo json_encode($evento['nombre_responsable'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    telefono: <?php echo json_encode($evento['numero_responsable'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    correo: <?php echo json_encode($evento['correo_responsable'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    invitados: <?php echo (int)$evento['numero_invitados']; ?>,
    presupuesto: <?php echo (float)($evento['presupuesto_estimado'] ?? 0); ?>
};

console.log('eventoData cargado:', eventoData);

// Variable global para mantener la instancia del modal
let currentAlertModal = null;

// Función personalizada para Alert
function customAlert(message, type = 'info', title = null) {
    // Si hay un modal abierto, cerrarlo primero
    if (currentAlertModal) {
        currentAlertModal.hide();
    }
    
    const modalElement = document.getElementById('customAlertModal');
    currentAlertModal = new bootstrap.Modal(modalElement);
    
    const alertIcon = document.getElementById('alertIcon');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    const okBtn = document.getElementById('alertOkBtn');
    
    // Configurar icono y título según el tipo
    let iconClass = '';
    let defaultTitle = '';
    let btnClass = 'btn-primary';
    
    switch(type) {
        case 'success':
            iconClass = 'bi-check-circle-fill text-success';
            defaultTitle = 'Éxito';
            btnClass = 'btn-success';
            break;
        case 'error':
            iconClass = 'bi-exclamation-triangle-fill text-danger';
            defaultTitle = 'Error';
            btnClass = 'btn-danger';
            break;
        case 'warning':
            iconClass = 'bi-exclamation-circle-fill text-warning';
            defaultTitle = 'Advertencia';
            btnClass = 'btn-warning';
            break;
        default:
            iconClass = 'bi-info-circle-fill text-info';
            defaultTitle = 'Información';
            btnClass = 'btn-primary';
    }
    
    alertIcon.className = 'bi me-2 ' + iconClass;
    alertTitle.textContent = title || defaultTitle;
    alertMessage.innerHTML = message;
    okBtn.className = 'btn ' + btnClass;
    
    // Limpiar el modal cuando se cierre
    modalElement.addEventListener('hidden.bs.modal', function() {
        currentAlertModal = null;
    }, { once: true });
    
    currentAlertModal.show();
}

// Función personalizada para Confirm
function customConfirm(message, callback, title = 'Confirmación') {
    const modal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmOkBtn');
    
    confirmTitle.textContent = title;
    confirmMessage.innerHTML = message;
    
    // Remover listeners anteriores clonando el botón
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Agregar nuevo listener
    newConfirmBtn.addEventListener('click', function() {
        modal.hide();
        if (callback) callback();
    });
    
    modal.show();
}

// ========== FUNCIONES DE ACCIONES RÁPIDAS ==========

// 1. Generar Contrato PDF
window.generarContrato = function() {
    console.log('generarContrato() llamada');
    
    // Verificar que jsPDF esté disponible
    if (typeof window.jspdf === 'undefined') {
        alert('Error: La librería jsPDF no se ha cargado correctamente. Por favor recarga la página.');
        console.error('jsPDF no está disponible');
        return;
    }
    
    // Validar que existan los datos mínimos necesarios
    const errores = [];
    
    if (!eventoData.nombre || eventoData.nombre.trim() === '') {
        errores.push('- Nombre del evento');
    }
    if (!eventoData.novio1 || eventoData.novio1.trim() === '') {
        errores.push('- Nombre del primer celebrante');
    }
    if (!eventoData.novio2 || eventoData.novio2.trim() === '') {
        errores.push('- Nombre del segundo celebrante');
    }
    if (!eventoData.fecha || eventoData.fecha.trim() === '') {
        errores.push('- Fecha del evento');
    }
    if (!eventoData.lugar || eventoData.lugar.trim() === '') {
        errores.push('- Lugar del evento');
    }
    if (!eventoData.direccion || eventoData.direccion.trim() === '') {
        errores.push('- Dirección del evento');
    }
    if (!eventoData.responsable || eventoData.responsable.trim() === '') {
        errores.push('- Nombre del responsable');
    }
    if (!eventoData.telefono || eventoData.telefono.trim() === '') {
        errores.push('- Teléfono del responsable');
    }
    if (!eventoData.correo || eventoData.correo.trim() === '') {
        errores.push('- Correo del responsable');
    }
    if (!eventoData.invitados || eventoData.invitados <= 0) {
        errores.push('- Número de invitados');
    }
    
    // Si hay errores, mostrarlos
    if (errores.length > 0) {
        const mensajeError = 'No se puede generar el contrato. Faltan los siguientes datos:<br><br>' + errores.join('<br>') + '<br><br>Por favor completa la información del evento antes de generar el contrato.';
        try {
            customAlert(mensajeError, 'warning', 'Datos Incompletos');
        } catch(e) {
            alert('No se puede generar el contrato. Faltan datos: ' + errores.join(', '));
        }
        return;
    }
    
    // Advertencia si no hay servicios ni inventario
    let servicios = obtenerServiciosContratados();
    let inventario = obtenerInventarioAsignado();
    
    if (servicios.length === 0 && inventario.length === 0) {
        const confirmar = confirm('⚠️ ADVERTENCIA: Este evento no tiene servicios contratados ni inventario asignado.\\n\\n¿Deseas generar el contrato de todas formas?');
        if (!confirmar) {
            return;
        }
    }
    
    // Crear un nuevo documento
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Configuración de colores (verde Maka)
    const colorPrimario = [153, 170, 140]; // #99AA8C
    const colorTexto = [51, 51, 51];
    
    let yPos = 20;
    
    // Encabezado
    doc.setFillColor(...colorPrimario);
    doc.rect(0, 0, 210, 30, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont('helvetica', 'bold');
    doc.text('MAKA EVENTOS', 105, 15, { align: 'center' });
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Contrato de Servicios para Eventos', 105, 23, { align: 'center' });
    
    yPos = 40;
    doc.setTextColor(...colorTexto);
    
    // Información del Evento
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('INFORMACIÓN DEL EVENTO', 20, yPos);
    yPos += 10;
    
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text('Nombre del Evento: ' + eventoData.nombre, 20, yPos);
    yPos += 7;
    doc.text('Celebrantes: ' + eventoData.novio1 + ' & ' + eventoData.novio2, 20, yPos);
    yPos += 7;
    doc.text('Fecha: ' + formatearFecha(eventoData.fecha) + ' | Hora: ' + (eventoData.hora || 'Por definir'), 20, yPos);
    yPos += 7;
    doc.text('Lugar: ' + eventoData.lugar, 20, yPos);
    yPos += 7;
    doc.text('Dirección: ' + eventoData.direccion, 20, yPos);
    yPos += 7;
    doc.text('Número de Invitados: ' + eventoData.invitados, 20, yPos);
    yPos += 7;
    doc.text('Presupuesto Estimado: $' + Number(eventoData.presupuesto).toLocaleString('es-MX', {minimumFractionDigits: 2}), 20, yPos);
    
    yPos += 15;
    
    // Responsable
    doc.setFont('helvetica', 'bold');
    doc.text('RESPONSABLE DEL EVENTO', 20, yPos);
    yPos += 10;
    doc.setFont('helvetica', 'normal');
    doc.text('Nombre: ' + eventoData.responsable, 20, yPos);
    yPos += 7;
    doc.text('Teléfono: ' + eventoData.telefono, 20, yPos);
    yPos += 7;
    doc.text('Correo: ' + eventoData.correo, 20, yPos);
    
    yPos += 15;
    
    // Obtener servicios contratados (ya los tenemos de la validación)
    if (servicios.length > 0) {
        doc.setFont('helvetica', 'bold');
        doc.text('SERVICIOS CONTRATADOS', 20, yPos);
        yPos += 8;
        
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        servicios.forEach((servicio, index) => {
            if (yPos > 270) {
                doc.addPage();
                yPos = 20;
            }
            doc.text((index + 1) + '. ' + servicio.nombre + ' - $' + Number(servicio.costo).toLocaleString('es-MX'), 25, yPos);
            yPos += 6;
            doc.setFontSize(9);
            doc.setTextColor(100, 100, 100);
            doc.text('   Proveedor: ' + servicio.proveedor, 25, yPos);
            yPos += 5;
            doc.setFontSize(10);
            doc.setTextColor(...colorTexto);
        });
        
        yPos += 5;
        doc.setFont('helvetica', 'bold');
        doc.text('Total Servicios: $' + calcularTotalServicios(servicios).toLocaleString('es-MX', {minimumFractionDigits: 2}), 25, yPos);
        yPos += 10;
    } else {
        // No hay servicios contratados
        doc.setFont('helvetica', 'bold');
        doc.text('SERVICIOS CONTRATADOS', 20, yPos);
        yPos += 8;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(150, 150, 150);
        doc.text('No se han contratado servicios para este evento.', 25, yPos);
        doc.setTextColor(...colorTexto);
        yPos += 10;
    }
    
    // Obtener inventario asignado (ya lo tenemos de la validación)
    if (inventario.length > 0) {
        if (yPos > 240) {
            doc.addPage();
            yPos = 20;
        }
        
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.text('INVENTARIO ASIGNADO', 20, yPos);
        yPos += 8;
        
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        inventario.forEach((item, index) => {
            if (yPos > 270) {
                doc.addPage();
                yPos = 20;
            }
            doc.text((index + 1) + '. ' + item.nombre + ' - Cantidad: ' + item.cantidad, 25, yPos);
            yPos += 6;
        });
        yPos += 5;
    } else {
        // No hay inventario asignado
        if (yPos > 260) {
            doc.addPage();
            yPos = 20;
        }
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.text('INVENTARIO ASIGNADO', 20, yPos);
        yPos += 8;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(150, 150, 150);
        doc.text('No se ha asignado inventario para este evento.', 25, yPos);
        doc.setTextColor(...colorTexto);
        yPos += 10;
    }
    
    // Términos y condiciones (nueva página)
    doc.addPage();
    yPos = 20;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('TÉRMINOS Y CONDICIONES', 105, yPos, { align: 'center' });
    yPos += 15;
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    const terminos = [
        '1. El cliente se compromete a realizar el pago total del evento según lo acordado.',
        '2. MAKA Eventos se compromete a proveer todos los servicios e inventario especificados.',
        '3. Cualquier cambio debe ser notificado con al menos 15 días de anticipación.',
        '4. En caso de cancelación, se aplicarán las políticas establecidas.',
        '5. El inventario debe ser devuelto en las mismas condiciones.',
        '6. Los servicios contratados serán provistos en la fecha y hora acordadas.'
    ];
    
    terminos.forEach(termino => {
        if (yPos > 270) {
            doc.addPage();
            yPos = 20;
        }
        const lineas = doc.splitTextToSize(termino, 170);
        doc.text(lineas, 20, yPos);
        yPos += lineas.length * 7;
    });
    
    // Firmas
    yPos += 20;
    if (yPos > 240) {
        doc.addPage();
        yPos = 40;
    }
    
    doc.line(20, yPos, 90, yPos);
    doc.line(120, yPos, 190, yPos);
    yPos += 5;
    doc.setFontSize(9);
    doc.text('Firma del Cliente', 20, yPos);
    doc.text('Firma de MAKA Eventos', 120, yPos);
    yPos += 5;
    doc.text(eventoData.responsable, 20, yPos);
    doc.text('Representante Autorizado', 120, yPos);
    
    // Pie de página
    const totalPaginas = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPaginas; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text('Página ' + i + ' de ' + totalPaginas, 105, 290, { align: 'center' });
        doc.text('Generado el ' + new Date().toLocaleDateString('es-MX'), 20, 290);
    }
    
    // Descargar
    const nombreArchivo = 'Contrato_' + eventoData.nombre.replace(/[^a-z0-9]/gi, '_') + '_' + new Date().getTime() + '.pdf';
    doc.save(nombreArchivo);
    
    // Mostrar mensaje de éxito después de un pequeño delay
    setTimeout(function() {
        try {
            customAlert('Contrato generado y descargado exitosamente', 'success');
        } catch(e) {
            alert('Contrato generado exitosamente');
        }
    }, 300);
};

// Funciones auxiliares para el contrato
function obtenerServiciosContratados() {
    const servicios = [];
    const rows = document.querySelectorAll('#serviciosTable tbody tr');
    rows.forEach(row => {
        const cells = row.cells;
        if (cells && cells.length >= 4) {
            servicios.push({
                nombre: cells[1].textContent.trim(),
                proveedor: cells[2].textContent.trim(),
                costo: parseFloat(cells[3].textContent.replace(/[$,]/g, '')) || 0
            });
        }
    });
    return servicios;
}

function obtenerInventarioAsignado() {
    const inventario = [];
    const rows = document.querySelectorAll('#mobiliarioTable tbody tr');
    rows.forEach(row => {
        const cells = row.cells;
        if (cells && cells.length >= 3) {
            inventario.push({
                nombre: cells[1].textContent.trim(),
                cantidad: cells[2].textContent.trim()
            });
        }
    });
    return inventario;
}

function calcularTotalServicios(servicios) {
    return servicios.reduce((total, servicio) => total + servicio.costo, 0);
}

function formatearFecha(fecha) {
    if (!fecha) return 'Por definir';
    const date = new Date(fecha + 'T00:00:00');
    return date.toLocaleDateString('es-MX', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// 2. Enviar WhatsApp
window.enviarWhatsApp = function() {
    console.log('enviarWhatsApp() llamada');
    
    // Validar que exista el teléfono
    if (!eventoData.telefono || eventoData.telefono.trim() === '') {
        try {
            customAlert('No hay un número de teléfono registrado para este evento.', 'warning', 'Teléfono No Disponible');
        } catch(e) {
            alert('No hay un número de teléfono registrado para este evento.');
        }
        return;
    }
    
    // Mensaje simple y profesional
    const mensaje = 'Hola ' + eventoData.responsable + ', te saludo de MAKA Eventos. Te contacto sobre tu evento ' + eventoData.nombre + ' programado para el ' + formatearFecha(eventoData.fecha) + ' en ' + eventoData.lugar + '. En que podemos ayudarte?';
    
    const telefono = eventoData.telefono.replace(/\D/g, ''); // Solo números
    
    // Abrir WhatsApp Web o app
    const whatsappUrl = 'https://wa.me/52' + telefono + '?text=' + encodeURIComponent(mensaje);
    window.open(whatsappUrl, '_blank');
};

// 3. Enviar Email
window.enviarEmail = function() {
    console.log('enviarEmail() llamada');
    
    // Validar que exista el correo
    if (!eventoData.correo || eventoData.correo.trim() === '') {
        try {
            customAlert('No hay un correo electrónico registrado para este evento.', 'warning', 'Correo No Disponible');
        } catch(e) {
            alert('No hay un correo electrónico registrado para este evento.');
        }
        return;
    }
    
    // Asunto del correo
    const asunto = 'MAKA Eventos - ' + eventoData.nombre;
    
    // Cuerpo del correo profesional
    const cuerpo = 'Estimado/a ' + eventoData.responsable + ',%0D%0A%0D%0A' +
                   'Reciba un cordial saludo de parte de MAKA Eventos.%0D%0A%0D%0A' +
                   'Me comunico con usted en relación a su evento:%0D%0A%0D%0A' +
                   'Evento: ' + eventoData.nombre + '%0D%0A' +
                   'Celebrantes: ' + eventoData.novio1 + ' y ' + eventoData.novio2 + '%0D%0A' +
                   'Fecha: ' + formatearFecha(eventoData.fecha) + '%0D%0A' +
                   'Lugar: ' + eventoData.lugar + '%0D%0A' +
                   'Invitados: ' + eventoData.invitados + ' personas%0D%0A%0D%0A' +
                   'Quedamos a su disposicion para cualquier consulta o aclaracion que requiera.%0D%0A%0D%0A' +
                   'Atentamente,%0D%0A' +
                   'MAKA Eventos';
    
    // Abrir cliente de correo
    const mailtoUrl = 'mailto:' + eventoData.correo + '?subject=' + encodeURIComponent(asunto) + '&body=' + cuerpo;
    window.location.href = mailtoUrl;
};

// 4. Agregar a Calendario
window.agregarACalendario = function() {
    console.log('agregarACalendario() llamada');
    
    if (!eventoData.fecha) {
        try {
            customAlert('No se ha definido una fecha para el evento', 'warning');
        } catch(e) {
            alert('No se ha definido una fecha para el evento');
        }
        return;
    }
    
    // Detectar si es dispositivo móvil, tablet o tiene pantalla táctil
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    const isAppleDevice = /iPhone|iPad|iPod|Macintosh/i.test(navigator.userAgent);
    
    // Si es dispositivo táctil O Apple (incluye iPad que a veces se identifica como Mac), usar .ics
    if (isMobile || (isTouchDevice && isAppleDevice)) {
        // Para móviles/tablets/iPad: descargar archivo .ics
        generarArchivoICS();
    } else {
        // Para escritorio sin táctil: abrir Google Calendar
        abrirGoogleCalendar();
    }
};

// Función para generar archivo .ics (móvil/tablet)
function generarArchivoICS() {
    const fecha = new Date(eventoData.fecha + 'T00:00:00');
    const horaEvento = eventoData.hora || '19:00';
    const [horas, minutos] = horaEvento.split(':');
    
    // Fecha de inicio
    const fechaInicio = new Date(fecha);
    fechaInicio.setHours(parseInt(horas), parseInt(minutos), 0);
    
    // Fecha de fin (6 horas después)
    const fechaFin = new Date(fechaInicio);
    fechaFin.setHours(fechaInicio.getHours() + 6);
    
    // Formatear fechas para ICS (formato: YYYYMMDDTHHmmss)
    function formatearFechaICS(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hour = String(date.getHours()).padStart(2, '0');
        const minute = String(date.getMinutes()).padStart(2, '0');
        const second = String(date.getSeconds()).padStart(2, '0');
        return year + month + day + 'T' + hour + minute + second;
    }
    
    const dtStart = formatearFechaICS(fechaInicio);
    const dtEnd = formatearFechaICS(fechaFin);
    const dtStamp = formatearFechaICS(new Date());
    
    // Crear contenido del archivo ICS
    const icsContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//MAKA Eventos//ES',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'DTSTART:' + dtStart,
        'DTEND:' + dtEnd,
        'DTSTAMP:' + dtStamp,
        'UID:' + eventoData.id + '-' + Date.now() + '@makaevetos.com',
        'SUMMARY:' + eventoData.nombre,
        'DESCRIPTION:Evento: ' + eventoData.novio1 + ' & ' + eventoData.novio2 + '\\n' + eventoData.invitados + ' invitados\\nResponsable: ' + eventoData.responsable + '\\nTeléfono: ' + eventoData.telefono,
        'LOCATION:' + eventoData.lugar + ', ' + eventoData.direccion,
        'STATUS:CONFIRMED',
        'SEQUENCE:0',
        'BEGIN:VALARM',
        'TRIGGER:-PT24H',
        'ACTION:DISPLAY',
        'DESCRIPTION:Recordatorio: ' + eventoData.nombre + ' mañana',
        'END:VALARM',
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\r\n');
    
    // Crear blob y descargar
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'Evento_' + eventoData.nombre.replace(/[^a-z0-9]/gi, '_') + '.ics';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    setTimeout(function() {
        try {
            customAlert('Archivo de calendario descargado. Ábrelo para agregarlo a tu calendario.', 'success', 'Evento Descargado');
        } catch(e) {
            alert('Archivo de calendario descargado exitosamente');
        }
    }, 300);
}

// Función para abrir Google Calendar (escritorio)
function abrirGoogleCalendar() {
    const fecha = new Date(eventoData.fecha + 'T00:00:00');
    const fechaISO = fecha.toISOString().split('T')[0].replace(/-/g, '');
    const horaInicio = (eventoData.hora || '19:00').replace(':', '') + '00';
    const horaFin = sumarHoras(eventoData.hora || '19:00', 6).replace(':', '') + '00';
    
    const titulo = encodeURIComponent(eventoData.nombre);
    const descripcion = encodeURIComponent('Evento: ' + eventoData.novio1 + ' & ' + eventoData.novio2 + '\\n' + eventoData.invitados + ' invitados\\nResponsable: ' + eventoData.responsable + '\\nTeléfono: ' + eventoData.telefono);
    const ubicacion = encodeURIComponent(eventoData.lugar + ', ' + eventoData.direccion);
    
    // Crear URL de Google Calendar
    const googleCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' + titulo + '&dates=' + fechaISO + 'T' + horaInicio + '/' + fechaISO + 'T' + horaFin + '&details=' + descripcion + '&location=' + ubicacion;
    
    // Abrir en nueva ventana
    window.open(googleCalUrl, '_blank');
    
    setTimeout(function() {
        try {
            customAlert('Se abrió Google Calendar para agregar el evento', 'info', 'Calendario Abierto');
        } catch(e) {
            console.log('Calendario abierto');
        }
    }, 300);
}

function sumarHoras(hora, horasAgregar) {
    if (!hora) return '01:00';
    const [h, m] = hora.split(':').map(Number);
    let nuevaHora = h + horasAgregar;
    if (nuevaHora >= 24) nuevaHora -= 24;
    return `${nuevaHora.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
}

// Verificar que todo esté cargado correctamente
console.log('Script de Acciones Rápidas cargado');
console.log('eventoData:', eventoData);
console.log('jsPDF disponible:', typeof window.jspdf !== 'undefined');

// ==================== MODO DÍA DEL EVENTO ====================

// Variables globales para el modo día del evento
let countdownInterval = null;
let modoDiaActivo = false;

// Activar Modo Día del Evento
window.activarModoDiaEvento = function() {
    const modal = new bootstrap.Modal(document.getElementById('modalDiaEvento'));
    modal.show();
    modoDiaActivo = true;
    
    // Iniciar countdown
    iniciarCountdown();
    
    // Cargar timeline
    cargarTimelineEvento();
    
    // Cargar servicios
    cargarServiciosModoDia();
    
    // Cargar inventario
    cargarInventarioModoDia();
    
    // Inicializar progreso
    actualizarProgreso();
    
    // Cargar notas guardadas
    cargarNotasRapidas();
};

// Desactivar Modo Día del Evento
window.desactivarModoDiaEvento = function() {
    customConfirm('¿Seguro que deseas salir del Modo Día del Evento?', function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalDiaEvento'));
        if (modal) {
            modal.hide();
        }
        modoDiaActivo = false;
        
        // Detener countdown
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }, 'Salir del Modo');
};

// Countdown hasta el evento
function iniciarCountdown() {
    const fechaEvento = eventoData.fecha;
    const horaEvento = eventoData.hora || '00:00:00';
    
    const fechaHoraEvento = new Date(fechaEvento + 'T' + horaEvento);
    
    function actualizarCountdown() {
        const ahora = new Date();
        const diferencia = fechaHoraEvento - ahora;
        
        const countdownDisplay = document.getElementById('countdownDisplay');
        const countdownLabel = document.getElementById('countdownLabel');
        
        if (diferencia <= 0) {
            // El evento ya pasó o está en curso
            const tiempoTranscurrido = Math.abs(diferencia);
            const horas = Math.floor(tiempoTranscurrido / (1000 * 60 * 60));
            const minutos = Math.floor((tiempoTranscurrido % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((tiempoTranscurrido % (1000 * 60)) / 1000);
            
            countdownDisplay.textContent = String(horas).padStart(2, '0') + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            countdownLabel.textContent = '¡EVENTO EN CURSO!';
            countdownDisplay.style.color = '#10b981'; // Verde
        } else {
            // Falta tiempo para el evento
            const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
            const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);
            
            if (dias > 0) {
                countdownDisplay.textContent = dias + 'd ' + String(horas).padStart(2, '0') + 'h ' + String(minutos).padStart(2, '0') + 'm';
                countdownLabel.textContent = 'Días para el evento';
                countdownDisplay.style.color = '#3b82f6'; // Azul
            } else if (horas > 0) {
                countdownDisplay.textContent = String(horas).padStart(2, '0') + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
                countdownLabel.textContent = 'Horas para el evento';
                countdownDisplay.style.color = '#f59e0b'; // Naranja
            } else {
                countdownDisplay.textContent = String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
                countdownLabel.textContent = '¡ÚLTIMO MINUTO!';
                countdownDisplay.style.color = '#ef4444'; // Rojo
            }
        }
    }
    
    actualizarCountdown();
    countdownInterval = setInterval(actualizarCountdown, 1000);
}

// Cargar cronograma en timeline
function cargarTimelineEvento() {
    const container = document.getElementById('timelineContainer');
    
    // Obtener actividades del cronograma desde la tabla
    const actividadesRows = document.querySelectorAll('#cronogramaTable tbody tr');
    
    if (actividadesRows.length === 0) {
        container.innerHTML = '<div class="list-group-item bg-dark text-muted text-center border-secondary"><small>No hay actividades en el cronograma</small></div>';
        return;
    }
    
    const ahora = new Date();
    const horaActual = ahora.getHours() + ':' + String(ahora.getMinutes()).padStart(2, '0');
    
    let html = '';
    actividadesRows.forEach((row, index) => {
        const cols = row.querySelectorAll('td');
        if (cols.length >= 4) {
            const horaInicio = cols[0].textContent.trim();
            const horaFin = cols[1].textContent.trim();
            const actividad = cols[2].textContent.trim();
            const responsable = cols[3].textContent.trim();
            
            // Determinar si es la actividad actual
            const esActual = horaInicio <= horaActual && horaActual <= horaFin;
            const yaTermino = horaFin < horaActual;
            
            let bgClass = 'bg-dark';
            let borderClass = 'border-secondary';
            let iconClass = 'bi-clock';
            let iconColor = 'text-muted';
            
            if (esActual) {
                bgClass = 'bg-success bg-opacity-25';
                borderClass = 'border-success';
                iconClass = 'bi-broadcast-pin';
                iconColor = 'text-success';
            } else if (yaTermino) {
                bgClass = 'bg-secondary bg-opacity-10';
                iconClass = 'bi-check-circle';
                iconColor = 'text-success';
            }
            
            let itemClass = 'modo-dia-list-item';
            if (esActual) itemClass += ' timeline-item-active';
            if (yaTermino) itemClass += ' timeline-item-completed';
            
            html += '<div class="' + itemClass + '">';
            html += '  <div class="d-flex align-items-start">';
            html += '    <i class="bi ' + iconClass + ' ' + iconColor + ' me-3" style="font-size: 1.2rem;"></i>';
            html += '    <div class="flex-grow-1">';
            html += '      <div class="d-flex justify-content-between align-items-start mb-1">';
            html += '        <strong class="' + (esActual ? 'text-success' : 'text-white') + '" style="font-size: 0.95rem;">' + actividad + '</strong>';
            html += '        <span class="badge bg-secondary ms-2" style="font-size: 0.75rem;">' + horaInicio + ' - ' + horaFin + '</span>';
            html += '      </div>';
            if (responsable && responsable !== '-') {
                html += '      <small class="text-white-50"><i class="bi bi-person-fill me-1"></i>' + responsable + '</small>';
            }
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
        }
    });
    
    container.innerHTML = html;
}

// Cargar servicios en el modo día
function cargarServiciosModoDia() {
    const container = document.getElementById('serviciosModoDia');
    
    // Obtener servicios desde la tabla
    const serviciosRows = document.querySelectorAll('#serviciosTable tbody tr');
    
    if (serviciosRows.length === 0) {
        container.innerHTML = '<div class="list-group-item bg-dark text-muted text-center border-secondary"><small>No hay servicios contratados</small></div>';
        return;
    }
    
    let html = '';
    serviciosRows.forEach((row, index) => {
        const cols = row.querySelectorAll('td');
        if (cols.length >= 3) {
            const nombre = cols[0].textContent.trim();
            const proveedor = cols[1].textContent.trim();
            const costo = cols[2].textContent.trim();
            
            html += '<div class="modo-dia-list-item">';
            html += '  <div class="d-flex justify-content-between align-items-start mb-2">';
            html += '    <strong class="text-white" style="font-size: 0.95rem;">' + nombre + '</strong>';
            html += '    <span class="service-badge">' + costo + '</span>';
            html += '  </div>';
            html += '  <small class="text-white-50"><i class="bi bi-shop-window me-1"></i>' + proveedor + '</small>';
            html += '</div>';
        }
    });
    
    container.innerHTML = html;
}

// Cargar inventario en el modo día
function cargarInventarioModoDia() {
    const container = document.getElementById('inventarioModoDia');
    
    // Obtener inventario desde la tabla
    const inventarioRows = document.querySelectorAll('#mobiliarioTable tbody tr');
    
    if (inventarioRows.length === 0) {
        container.innerHTML = '<div class="list-group-item bg-dark text-muted text-center border-secondary"><small>No hay inventario asignado</small></div>';
        return;
    }
    
    let html = '';
    inventarioRows.forEach((row, index) => {
        const cols = row.querySelectorAll('td');
        if (cols.length >= 2) {
            const nombre = cols[0].textContent.trim();
            const cantidad = cols[1].textContent.trim();
            
            html += '<div class="modo-dia-list-item">';
            html += '  <div class="d-flex justify-content-between align-items-center">';
            html += '    <span class="text-white" style="font-size: 0.95rem;">' + nombre + '</span>';
            html += '    <span class="inventory-badge">× ' + cantidad + '</span>';
            html += '  </div>';
            html += '</div>';
        }
    });
    
    container.innerHTML = html;
}

// Actualizar progreso del checklist
window.actualizarProgreso = function() {
    // Obtener todos los checkboxes
    const todosChecks = document.querySelectorAll('#checklistAccordion input[type="checkbox"]');
    const checksCompletados = document.querySelectorAll('#checklistAccordion input[type="checkbox"]:checked');
    
    const total = todosChecks.length;
    const completados = checksCompletados.length;
    const porcentaje = total > 0 ? Math.round((completados / total) * 100) : 0;
    
    // Actualizar barra de progreso
    const barraProgreso = document.getElementById('progresoGeneral');
    barraProgreso.style.width = porcentaje + '%';
    barraProgreso.textContent = porcentaje + '%';
    
    // Actualizar texto
    document.getElementById('progresoTexto').textContent = completados + ' de ' + total + ' tareas';
    
    // Actualizar badges de cada sección
    actualizarBadgeSeccion('checklistMontaje', 'badgeMontaje');
    actualizarBadgeSeccion('checklistCeremonia', 'badgeCeremonia');
    actualizarBadgeSeccion('checklistRecepcion', 'badgeRecepcion');
    actualizarBadgeSeccion('checklistLimpieza', 'badgeLimpieza');
};

function actualizarBadgeSeccion(seccionId, badgeId) {
    const seccion = document.getElementById(seccionId);
    const todosChecks = seccion.querySelectorAll('input[type="checkbox"]');
    const checksCompletados = seccion.querySelectorAll('input[type="checkbox"]:checked');
    
    const total = todosChecks.length;
    const completados = checksCompletados.length;
    
    const badge = document.getElementById(badgeId);
    badge.textContent = completados + '/' + total;
    
    if (completados === total && total > 0) {
        badge.classList.remove('bg-warning');
        badge.classList.add('bg-success');
    } else {
        badge.classList.remove('bg-success');
        badge.classList.add('bg-warning');
    }
}

// Guardar notas rápidas en localStorage
window.guardarNotasRapidas = function() {
    const notas = document.getElementById('notasRapidas').value;
    const key = 'notas_evento_' + eventoData.id;
    
    try {
        localStorage.setItem(key, notas);
        customAlert('Notas guardadas correctamente', 'success', 'Guardado');
    } catch (e) {
        customAlert('Error al guardar las notas', 'danger', 'Error');
    }
};

// Cargar notas rápidas desde localStorage
function cargarNotasRapidas() {
    const key = 'notas_evento_' + eventoData.id;
    
    try {
        const notas = localStorage.getItem(key);
        if (notas) {
            document.getElementById('notasRapidas').value = notas;
        }
    } catch (e) {
        console.log('No se pudieron cargar las notas');
    }
}

console.log('Modo Día del Evento cargado');
</script>
</script>
