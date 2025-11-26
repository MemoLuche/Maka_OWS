<?php
require_once __DIR__ . '/../config/conexion.php';
// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}
// helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Verificar si el usuario es administrador
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

// Obtener términos de búsqueda
$search_nombre = trim($_GET['search_nombre'] ?? '');
$search_estatus = trim($_GET['search_estatus'] ?? '');
$search_fecha_desde = trim($_GET['search_fecha_desde'] ?? '');
$search_fecha_hasta = trim($_GET['search_fecha_hasta'] ?? '');
$search_operativo = trim($_GET['search_operativo'] ?? '');

// Obtener estatus únicos
$estatus_disponibles = ['Pendiente', 'Confirmado', 'En Proceso', 'Finalizado', 'Cancelado'];

// Obtener lista de organizadores/clientes si es admin
$organizadores = [];
if ($isAdmin) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT e.organizador_id, u.nombre_completo 
                            FROM eventos e 
                            LEFT JOIN usuario u ON e.organizador_id = u.id 
                            WHERE e.organizador_id IS NOT NULL AND e.organizador_id != 0
                            ORDER BY u.nombre_completo ASC");
        $organizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo organizadores: " . $e->getMessage());
    }
}

// Obtener eventos desde la base de datos
$eventos = [];
$hasSearch = !empty($search_nombre) || !empty($search_estatus) || !empty($search_fecha_desde) || !empty($search_fecha_hasta) || isset($_GET['search_operativo']);

try {
  // Base de la consulta
  $sql = "SELECT id, nombre_novio_1, nombre_novio_2, nombre_evento, nombre_responsable, numero_responsable, ubicacion, fecha_evento, estatus, imagen_principal FROM eventos WHERE 1=1";
  $params = [];
  
  // Si NO es admin, solo ver eventos asignados
  if (!$isAdmin) {
    $sql .= " AND organizador_id = ?";
    $params[] = $_SESSION['user_id'];
  }
  
  // Aplicar filtros de búsqueda
  if (!empty($search_nombre)) {
    $sql .= " AND (nombre_evento LIKE ? OR nombre_novio_1 LIKE ? OR nombre_novio_2 LIKE ? OR nombre_responsable LIKE ?)";
    $search_term = "%$search_nombre%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
  }
  
  if (!empty($search_estatus)) {
    $sql .= " AND estatus = ?";
    $params[] = $search_estatus;
  }
  
  if (isset($_GET['search_operativo']) && $search_operativo !== '') {
    if ($search_operativo === '0') {
      $sql .= " AND (organizador_id IS NULL OR organizador_id = 0)";
    } else {
      $sql .= " AND organizador_id = ?";
      $params[] = $search_operativo;
    }
  }
  
  if (!empty($search_fecha_desde)) {
    $sql .= " AND fecha_evento >= ?";
    $params[] = $search_fecha_desde;
  }
  
  if (!empty($search_fecha_hasta)) {
    $sql .= " AND fecha_evento <= ?";
    $params[] = $search_fecha_hasta;
  }
  
  $sql .= " ORDER BY fecha_evento DESC, id DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $eventos = [];
  $error = 'Error al obtener eventos: ' . $e->getMessage();
}

// Mostramos varios eventos en una cuadrícula: 3 por fila en pantallas grandes
?>

<div class="site-container">
    <h1 class="catalogo-title mb-4">
        <i class="bi bi-calendar-event me-2"></i>
        <?php echo $isAdmin ? 'Todos los Eventos' : 'Mis Eventos'; ?>
    </h1>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i>Evento creado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i>Evento eliminado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo esc($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Layout: Filtro a la izquierda + Grid de eventos a la derecha -->
    <div class="catalogo-layout">
        
        <!-- Sidebar izquierdo: Filtros -->
        <aside class="catalogo-sidebar">
            <div class="card p-3 catalogo-filters">
                <h5 class="mb-3">
                    <i class="bi bi-funnel-fill me-2"></i>Filtrar Eventos
                </h5>
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="eventos">
                    
                    <div class="mb-3">
                        <label for="search_nombre" class="form-label">
                            <i class="bi bi-search me-1"></i>Buscar Evento
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="search_nombre" 
                               name="search_nombre" 
                               value="<?php echo esc($search_nombre); ?>" 
                               placeholder="Nombre, novios, responsable...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="search_estatus" class="form-label">
                            <i class="bi bi-flag-fill me-1"></i>Estatus
                        </label>
                        <select class="form-select" id="search_estatus" name="search_estatus">
                            <option value="">Todos los estatus</option>
                            <?php foreach ($estatus_disponibles as $est): ?>
                                <option value="<?php echo esc($est); ?>" 
                                        <?php echo $search_estatus === $est ? 'selected' : ''; ?>>
                                    <?php echo esc($est); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($isAdmin): ?>
                    <div class="mb-3">
                        <label for="search_operativo" class="form-label">
                            <i class="bi bi-person-badge me-1"></i>Organizador
                        </label>
                        <select class="form-select" id="search_operativo" name="search_operativo">
                            <option value="">Todos los organizadores</option>
                            <option value="0" <?php echo $search_operativo === '0' ? 'selected' : ''; ?>>Sin asignar</option>
                            <?php foreach ($organizadores as $org): ?>
                                <option value="<?php echo esc($org['organizador_id']); ?>" 
                                        <?php echo $search_operativo == $org['organizador_id'] ? 'selected' : ''; ?>>
                                    <?php echo esc($org['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="search_fecha_desde" class="form-label">
                            <i class="bi bi-calendar-range me-1"></i>Fecha Desde
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="search_fecha_desde" 
                               name="search_fecha_desde" 
                               value="<?php echo esc($search_fecha_desde); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="search_fecha_hasta" class="form-label">
                            <i class="bi bi-calendar-range me-1"></i>Fecha Hasta
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="search_fecha_hasta" 
                               name="search_fecha_hasta" 
                               value="<?php echo esc($search_fecha_hasta); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-funnel-fill me-1"></i>Aplicar Filtros
                    </button>
                    
                    <?php if ($hasSearch): ?>
                        <a href="?page=eventos" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i>Limpiar Filtros
                        </a>
                    <?php endif; ?>
                </form>
                
                <!-- Contador de resultados -->
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted d-block text-center">
                        <i class="bi bi-calendar-check me-1"></i>
                        <?php echo count($eventos); ?> evento<?php echo count($eventos) != 1 ? 's' : ''; ?> 
                        <?php echo $hasSearch ? 'encontrado' . (count($eventos) != 1 ? 's' : '') : ''; ?>
                    </small>
                </div>
            </div>
        </aside>

        <!-- Contenido principal: Grid de eventos -->
        <main class="catalogo-main">
            <?php if (count($eventos) === 0): ?>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card text-center p-5" style="border: 2px dashed #99AA8C; background-color: #f8f9fa;">
                            <i class="bi bi-calendar-x" style="font-size: 4rem; color: #99AA8C;"></i>
                            <h5 class="mt-3 mb-2">No se encontraron eventos</h5>
                            <p class="text-muted">
                                <?php if ($hasSearch): ?>
                                    Intenta modificar los filtros de búsqueda
                                <?php else: ?>
                                    <?php echo $isAdmin ? 'Aún no hay eventos registrados' : 'No tienes eventos asignados'; ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!$hasSearch): ?>
                                <a href="?page=evento_create" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle me-2"></i>Crear Primer Evento
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach($eventos as $evento): ?>
                    <div class="col-12 col-md-6 col-lg-4 d-flex">
                        <div class="card w-100 evento-card">
                            <?php 
                            $imgPath = (!empty($evento['imagen_principal'])) ? $evento['imagen_principal'] : 'imagenes/cover (1).jpg';
                            ?>
                            <img src="<?php echo esc($imgPath); ?>" class="card-img-top" alt="<?php echo esc($evento['nombre_evento'] ?? ''); ?>" style="height: 250px; object-fit: cover; width: 100%; border: none !important; border-radius: 8px 8px 0 0 !important; display: block;">
                            
                            <div class="card-body text-center">
                                <h5 class="card-title mb-2 evento-title"><?php echo esc($evento['nombre_evento'] ?? 'Evento sin nombre'); ?></h5>
                                
                                <div class="text-start mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-event"></i> 
                                        <?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> 
                                        <?php echo esc($evento['ubicacion'] ?? 'Sin ubicación'); ?>
                                    </small>
                                </div>
                                
                                <div class="mb-2">
                                    <?php
                                    $badge_class = 'bg-secondary';
                                    switch(strtolower($evento['estatus'] ?? 'pendiente')) {
                                        case 'confirmado':
                                            $badge_class = 'bg-success';
                                            break;
                                        case 'en proceso':
                                            $badge_class = 'bg-info';
                                            break;
                                        case 'pendiente':
                                            $badge_class = 'bg-warning';
                                            break;
                                        case 'cancelado':
                                            $badge_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo esc($evento['estatus'] ?? 'Pendiente'); ?></span>
                                </div>
                                
                                <a href="?page=evento_detalle&id=<?php echo esc($evento['id']); ?>" class="btn btn-sm btn-detalles">
                                    <i class="bi bi-eye"></i> Ver detalles
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Floating action button: agregar evento -->
<a href="?page=evento_create" class="fab-add-event" title="Agregar evento" aria-label="Agregar evento">+</a>


