<?php
require_once __DIR__ . '/../config/conexion.php';
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Verificar si es administrador
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

// Obtener categorías únicas para el filtro
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT categoria FROM servicios WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
    $categorias = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    error_log("Error obteniendo categorías: " . $e->getMessage());
}

// Obtener valores de filtro
$search_nombre = trim($_GET['search_nombre'] ?? '');
$search_categoria = trim($_GET['search_categoria'] ?? '');
$search_codigo = trim($_GET['search_codigo'] ?? '');

// Preparar consulta con filtros
$sql = "SELECT * FROM servicios";
$params = [];
$where = [];

if (!empty($search_nombre)) {
    $where[] = "nombre LIKE ?";
    $params[] = "%$search_nombre%";
}

if (!empty($search_categoria)) {
    $where[] = "categoria = ?";
    $params[] = $search_categoria;
}

if (!empty($search_codigo)) {
    $where[] = "codigo LIKE ?";
    $params[] = "%$search_codigo%";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY categoria ASC, nombre ASC";

// Ejecutar consulta
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $servicios = [];
    $error = "Error al cargar servicios: " . $e->getMessage();
    error_log("Error obteniendo servicios: " . $e->getMessage());
}

// Obtener eventos disponibles
try {
    $eventos_stmt = $pdo->query("SELECT id, nombre_evento, fecha_evento, estatus FROM eventos ORDER BY fecha_evento DESC");
    $eventos = $eventos_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eventos = [];
    error_log("Error obteniendo eventos: " . $e->getMessage());
}

// Procesar asignación de servicio a evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_servicio'])) {
    $servicio_id = (int)$_POST['servicio_id'];
    $evento_id = (int)$_POST['evento_id'];
    
    try {
        // Verificar que el servicio esté disponible
        $check_estado = $pdo->prepare("SELECT estado FROM servicios WHERE id = ?");
        $check_estado->execute([$servicio_id]);
        $servicio = $check_estado->fetch(PDO::FETCH_ASSOC);
        
        if (!$servicio) {
            $_SESSION['toast_message'] = 'Servicio no encontrado';
            $_SESSION['toast_type'] = 'error';
        } elseif ($servicio['estado'] !== 'disponible') {
            $mensaje = $servicio['estado'] === 'baja_temporal' 
                ? 'Este servicio está en baja temporal y no puede ser asignado a eventos'
                : 'Este servicio está dado de baja definitivamente y no puede ser asignado';
            $_SESSION['toast_message'] = $mensaje;
            $_SESSION['toast_type'] = 'error';
        } else {
            // Verificar si ya está asignado
            $check = $pdo->prepare("SELECT id FROM evento_servicio WHERE evento_id = ? AND servicio_id = ?");
            $check->execute([$evento_id, $servicio_id]);
            
            if ($check->fetch()) {
                $_SESSION['toast_message'] = 'Este servicio ya está asignado a este evento';
                $_SESSION['toast_type'] = 'warning';
            } else {
                // Insertar asignación
                $insert = $pdo->prepare("INSERT INTO evento_servicio (evento_id, servicio_id, fecha_asignacion) VALUES (?, ?, NOW())");
                $insert->execute([$evento_id, $servicio_id]);
                
                $_SESSION['toast_message'] = 'Servicio asignado exitosamente al evento';
                $_SESSION['toast_type'] = 'success';
            }
        }
    } catch (PDOException $e) {
        $_SESSION['toast_message'] = 'Error al asignar servicio: ' . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
        error_log("Error asignando servicio: " . $e->getMessage());
    }
    
    header('Location: ?page=servicios');
    exit;
}

// Función para determinar icono según categoría
function getServiceIcon($categoria) {
    // normalizar: minúsculas, quitar tildes, colapsar espacios
    $norm = function($s){
        $s = mb_strtolower(trim((string)$s));
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n'
        ];
        $s = strtr($s, $map);
        $s = preg_replace('/[^\p{L}\p{N}\s\-]/u','', $s); // eliminar otros símbolos
        $s = preg_replace('/\s+/u',' ', $s);
        return $s;
    };

    $cat = $norm($categoria);

    $icons = [
        // Audio / Música
        'audio' => 'bi-music-note-beamed',
        'musica' => 'bi-music-note-beamed',
        'banda' => 'bi-music-note-beamed',
        'sonido' => 'bi-music-note-beamed',
        'mariachi' => 'bi-music-note-beamed',
        'dj' => 'bi-music-note-beamed',
        'jazz' => 'bi-music-note-beamed',
        'trio' => 'bi-music-note-beamed',
        // Catering / Bebidas
        'catering' => 'bi-cup-straw',
        'bebidas' => 'bi-cup-straw',
        'comida' => 'bi-cup-straw',
        'bar' => 'bi-cup-straw',
        // Fotografía / Vídeo
        'fotografia' => 'bi-camera-fill',
        'foto' => 'bi-camera-fill',
        'video' => 'bi-camera-reels-fill',
        'film' => 'bi-camera-reels-fill',
        // Decoración / Flores
        'decoracion' => 'bi-flower1',
        'flores' => 'bi-flower1',
        'flor' => 'bi-flower1',
        // Animación / Entretenimiento
        'animacion' => 'bi-emoji-smile',
        'entretenimiento' => 'bi-emoji-smile',
        'show' => 'bi-emoji-smile',
        // Transporte
        'transporte' => 'bi-truck',
        'camion' => 'bi-truck',
        // Staff / Personal
        'staff' => 'bi-person-fill',
        'personal' => 'bi-person-fill',
    ];

    // normalizar claves del mapa
    $iconsNorm = [];
    foreach ($icons as $k => $v) {
        $iconsNorm[$norm($k)] = $v;
    }

    // 1) match exacto
    if ($cat !== '' && isset($iconsNorm[$cat])) {
        return $iconsNorm[$cat];
    }

    // 2) buscar por substring en la categoría normalizada
    foreach ($iconsNorm as $k => $v) {
        if ($k !== '' && strpos($cat, $k) !== false) {
            return $v;
        }
    }

    // 3) fallback
    return 'bi-briefcase-fill';
}
?>

<div class="site-container">
    <h1 class="catalogo-title mb-4"><i class="bi bi-briefcase me-2"></i>Catálogo de Servicios</h1>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo esc($error); ?></div>
    <?php endif; ?>

    <!-- Layout: Sidebar + Grid -->
    <div class="catalogo-layout">
        
        <!-- Sidebar Filtros -->
        <aside class="catalogo-sidebar">
            <div class="card p-3 catalogo-filters">
                <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filtrar Servicios</h5>
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="servicios">
                    
                    <div class="mb-3">
                        <label for="search_nombre" class="form-label">Nombre del Servicio</label>
                        <input type="text" class="form-control" id="search_nombre" name="search_nombre" 
                               value="<?php echo esc($search_nombre); ?>" placeholder="Buscar...">
                    </div>

                    <div class="mb-3">
                        <label for="search_codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="search_codigo" name="search_codigo" 
                               value="<?php echo esc($search_codigo); ?>" placeholder="Ej: SRV-001">
                    </div>

                    <div class="mb-3">
                        <label for="search_categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="search_categoria" name="search_categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo esc($cat['categoria']); ?>" 
                                    <?php echo ($search_categoria === $cat['categoria']) ? 'selected' : ''; ?>>
                                    <?php echo esc($cat['categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-catalogo-asignar">
                            <i class="bi bi-search me-2"></i>Buscar
                        </button>
                        <a href="?page=servicios" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Limpiar
                        </a>
                    </div>
                </form>

                <hr class="my-3">

                <div class="catalogo-filters-footer">
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong><?php echo count($servicios); ?></strong> servicio(s) encontrado(s)
                    </p>
                </div>
            </div>
        </aside>

        <!-- Grid de Servicios -->
        <main class="catalogo-main">
            <?php if (empty($servicios)): ?>
                <div class="catalogo-empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>No se encontraron servicios</h3>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($servicios as $servicio): 
                        $estado = $servicio['estado'] ?? 'disponible';
                        $estadoClass = '';
                        $estadoBadge = '';
                        $estadoIcono = '';
                        $deshabilitarAsignacion = false;
                        
                        if ($estado === 'baja_temporal') {
                            $estadoClass = 'border-warning';
                            $estadoBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Baja Temporal</span>';
                            $estadoIcono = 'bi-exclamation-triangle-fill text-warning';
                            $deshabilitarAsignacion = true;
                        } elseif ($estado === 'baja_definitiva') {
                            $estadoClass = 'border-danger opacity-75';
                            $estadoBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Baja Definitiva</span>';
                            $estadoIcono = 'bi-x-circle-fill text-danger';
                            $deshabilitarAsignacion = true;
                        } else {
                            $estadoBadge = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Disponible</span>';
                        }
                    ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card p-3 catalogo-card h-100 <?php echo $estadoClass; ?>">
                                <div class="text-center mb-3 position-relative">
                                    <i class="bi <?php echo getServiceIcon($servicio['categoria'] ?? ''); ?>" style="font-size: 3rem; color: var(--color-primary);"></i>
                                    <?php if ($estadoIcono): ?>
                                        <i class="bi <?php echo $estadoIcono; ?> position-absolute top-0 end-0" style="font-size: 1.5rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-0">
                                    <h6 class="card-title text-center mb-2"><?php echo esc($servicio['nombre']); ?></h6>
                                    
                                    <!-- Estado del servicio -->
                                    <p class="text-center mb-2">
                                        <?php echo $estadoBadge; ?>
                                    </p>
                                    
                                    <?php if (!empty($servicio['codigo'])): ?>
                                        <p class="text-center text-muted small mb-2">
                                            <span class="badge bg-secondary"><?php echo esc($servicio['codigo']); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($servicio['categoria'])): ?>
                                        <p class="text-center mb-2">
                                            <span class="badge" style="background-color: var(--color-primary);"><?php echo esc($servicio['categoria']); ?></span>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($servicio['descripcion'])): ?>
                                        <p class="card-text text-center small text-muted mb-3">
                                            <?php echo esc($servicio['descripcion']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="text-center mb-2">
                                        <?php if (isset($servicio['costo_estimado']) && $servicio['costo_estimado'] > 0): ?>
                                            <small class="d-block">
                                                <i class="bi bi-cash-coin text-servicios-green"></i>
                                                Desde $<?php echo esc(number_format($servicio['costo_estimado'], 2)); ?>
                                            </small>
                                        <?php endif; ?>

                                        <?php if (!empty($servicio['proveedor_sugerido'])): ?>
                                            <small class="d-block">
                                                <i class="bi bi-building text-servicios-green"></i>
                                                <?php echo esc($servicio['proveedor_sugerido']); ?>
                                            </small>
                                        <?php endif; ?>

                                        <?php if (!empty($servicio['duracion_aproximada'])): ?>
                                            <small class="d-block">
                                                <i class="bi bi-clock text-servicios-green"></i>
                                                <?php echo esc($servicio['duracion_aproximada']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-grid mt-3">
                                        <?php if ($deshabilitarAsignacion): ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="bi bi-slash-circle me-1"></i>No Disponible
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-servicios-asignar" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalAsignarEvento" 
                                                    data-servicio-id="<?php echo $servicio['id']; ?>"
                                                    data-servicio-nombre="<?php echo esc($servicio['nombre']); ?>">
                                                <i class="bi bi-plus-circle me-1"></i>Asignar a Evento
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal Asignar Servicio a Evento -->
<div class="modal fade" id="modalAsignarEvento" tabindex="-1" aria-labelledby="modalAsignarEventoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #99AA8C 0%, #a3b18a 100%); color: white;">
                <h5 class="modal-title" id="modalAsignarEventoLabel">
                    <i class="bi bi-calendar-plus me-2"></i>Asignar Servicio a Evento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="?page=servicios">
                <div class="modal-body">
                    <input type="hidden" name="asignar_servicio" value="1">
                    <input type="hidden" name="servicio_id" id="modal_servicio_id">
                    
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Asignando servicio: <strong id="modal_servicio_nombre"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="evento_id" class="form-label">Seleccionar Evento *</label>
                        <select class="form-select" id="evento_id" name="evento_id" required>
                            <option value="">-- Selecciona un evento --</option>
                            <?php foreach ($eventos as $evento): ?>
                                <option value="<?php echo $evento['id']; ?>">
                                    <?php echo esc($evento['nombre_evento'] ?: 'Evento sin nombre'); ?>
                                    <?php if (!empty($evento['fecha_evento'])): ?>
                                        - <?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($evento['estatus'])): ?>
                                        (<?php echo esc($evento['estatus']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (empty($eventos)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No hay eventos disponibles. <a href="?page=evento_create">Crear evento</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-catalogo-asignar" <?php echo empty($eventos) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check-circle me-1"></i>Asignar Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<?php if (isset($_SESSION['toast_message'])): ?>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-<?php echo $_SESSION['toast_type'] === 'success' ? 'success' : ($_SESSION['toast_type'] === 'warning' ? 'warning' : 'danger'); ?> text-white">
            <i class="bi bi-<?php echo $_SESSION['toast_type'] === 'success' ? 'check-circle' : ($_SESSION['toast_type'] === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?> me-2"></i>
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?php echo esc($_SESSION['toast_message']); ?>
        </div>
    </div>
</div>
<script>
    setTimeout(() => {
        const toastEl = document.querySelector('.toast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl);
            toast.hide();
        }
    }, 5000);
</script>
<?php 
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
endif; 
?>

<script>
// Manejar modal de asignación
const modalAsignar = document.getElementById('modalAsignarEvento');
if (modalAsignar) {
    modalAsignar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const servicioId = button.getAttribute('data-servicio-id');
        const servicioNombre = button.getAttribute('data-servicio-nombre');
        
        document.getElementById('modal_servicio_id').value = servicioId;
        document.getElementById('modal_servicio_nombre').textContent = servicioNombre;
    });
}
</script>