<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}

function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$success = '';
$error = '';

// Manejo de asignación/reasignación de eventos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_evento'])) {
    $evento_id = (int)$_POST['evento_id'];
    $organizador_id = $_POST['organizador_id'] ? (int)$_POST['organizador_id'] : null;
    
    if ($evento_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE eventos SET organizador_id = ? WHERE id = ?");
            $stmt->execute([$organizador_id, $evento_id]);
            
            if ($organizador_id) {
                $success = "Evento asignado exitosamente.";
            } else {
                $success = "Evento desasignado exitosamente.";
            }
        } catch (PDOException $e) {
            $error = "Error al asignar: " . $e->getMessage();
        }
    }
}

// Obtener todos los eventos con información del organizador asignado
try {
    $stmt = $pdo->query("
        SELECT e.*, u.nombre_completo as organizador_nombre
        FROM eventos e
        LEFT JOIN usuario u ON e.organizador_id = u.id
        ORDER BY e.fecha_evento DESC, e.id DESC
    ");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eventos = [];
    $error = "Error al leer eventos: " . $e->getMessage();
}

// Obtener todos los organizadores (usuarios tipo 'cliente')
try {
    $stmt = $pdo->query("SELECT id, nombre_completo, correo FROM usuario WHERE tipo = 'cliente' ORDER BY nombre_completo ASC");
    $organizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $organizadores = [];
}
?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <a href="?page=admin_gestion" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Administración
        </a>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo esc($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo esc($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card usuarios-card">
        <div class="usuarios-header">
            <h2>
                <i class="bi bi-calendar-check-fill"></i>Asignar Eventos a Organizadores
            </h2>
        </div>
        <div class="card-body p-4">
            <?php if (count($eventos) === 0): ?>
                <p class="text-center text-muted">No hay eventos registrados.</p>
            <?php else: ?>
                <!-- Barra de búsqueda mejorada -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="usuarios-search-container">
                            <input type="text" id="searchEvento" class="form-control usuarios-search-input" 
                                   placeholder="Buscar por ID, nombre del evento, novios o responsable...">
                            <i class="bi bi-search usuarios-search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                            <i class="bi bi-x-circle me-2"></i>Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- Sistema de filtros mejorado -->
                <div class="row mb-4">
                    <!-- Filtro por Asignación -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-person-check me-2"></i>Estado de Asignación
                        </label>
                        <select class="form-select" id="filtroAsignacion" onchange="aplicarFiltros()">
                            <option value="todos">Todos (<?php echo count($eventos); ?>)</option>
                            <option value="asignados">Asignados (<?php echo count(array_filter($eventos, fn($e) => $e['organizador_id'] !== null)); ?>)</option>
                            <option value="sin-asignar">Sin Asignar (<?php echo count(array_filter($eventos, fn($e) => $e['organizador_id'] === null)); ?>)</option>
                        </select>
                    </div>

                    <!-- Filtro por Estatus -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-flag me-2"></i>Estatus del Evento
                        </label>
                        <select class="form-select" id="filtroEstatus" onchange="aplicarFiltros()">
                            <option value="todos">Todos</option>
                            <option value="Pendiente">Pendiente (<?php echo count(array_filter($eventos, fn($e) => $e['estatus'] === 'Pendiente')); ?>)</option>
                            <option value="Confirmado">Confirmado (<?php echo count(array_filter($eventos, fn($e) => $e['estatus'] === 'Confirmado')); ?>)</option>
                            <option value="En Proceso">En Proceso (<?php echo count(array_filter($eventos, fn($e) => $e['estatus'] === 'En Proceso')); ?>)</option>
                            <option value="Finalizado">Finalizado (<?php echo count(array_filter($eventos, fn($e) => $e['estatus'] === 'Finalizado')); ?>)</option>
                            <option value="Cancelado">Cancelado (<?php echo count(array_filter($eventos, fn($e) => $e['estatus'] === 'Cancelado')); ?>)</option>
                        </select>
                    </div>

                    <!-- Filtro por Organizador -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-person-lines-fill me-2"></i>Organizador
                        </label>
                        <select class="form-select" id="filtroOrganizador" onchange="aplicarFiltros()">
                            <option value="todos">Todos</option>
                            <?php foreach ($organizadores as $org): ?>
                                <option value="<?php echo $org['id']; ?>">
                                    <?php echo esc($org['nombre_completo']); ?>
                                    (<?php echo count(array_filter($eventos, fn($e) => $e['organizador_id'] == $org['id'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por Rango de Fechas -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-calendar-range me-2"></i>Rango de Fechas
                        </label>
                        <select class="form-select" id="filtroFecha" onchange="aplicarFiltros()">
                            <option value="todos">Todas las fechas</option>
                            <option value="proximos">Próximos 30 días</option>
                            <option value="este-mes">Este mes</option>
                            <option value="proximo-mes">Próximo mes</option>
                            <option value="pasados">Eventos pasados</option>
                        </select>
                    </div>
                </div>

                <!-- Indicador de resultados -->
                <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                    <span>
                        <i class="bi bi-info-circle me-2"></i>
                        Mostrando <strong id="resultadosCount"><?php echo count($eventos); ?></strong> de <?php echo count($eventos); ?> eventos
                    </span>
                    <span id="filtrosActivos" class="badge bg-primary" style="display: none;">
                        <i class="bi bi-funnel-fill me-1"></i>Filtros activos
                    </span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table usuarios-table-simple" id="eventosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Evento</th>
                                <th>Responsable</th>
                                <th>Fecha</th>
                                <th>Estatus</th>
                                <th>Organizador Asignado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $evento): ?>
                                <tr data-estado="<?php echo $evento['organizador_id'] ? 'asignado' : 'sin-asignar'; ?>"
                                    data-estatus="<?php echo esc($evento['estatus']); ?>"
                                    data-organizador="<?php echo $evento['organizador_id'] ? $evento['organizador_id'] : '0'; ?>"
                                    data-fecha="<?php echo $evento['fecha_evento']; ?>">
                                    <td class="usuarios-id-simple">#<?php echo esc($evento['id']); ?></td>
                                    <td class="usuarios-nombre-simple">
                                        <strong><?php echo esc($evento['nombre_evento']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo esc($evento['nombre_novio_1'] . ' & ' . $evento['nombre_novio_2']); ?></small>
                                    </td>
                                    <td class="usuarios-correo-simple"><?php echo esc($evento['nombre_responsable']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></td>
                                    <td>
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
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo esc($evento['estatus']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($evento['organizador_id']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-person-check-fill me-1"></i><?php echo esc($evento['organizador_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-person-x me-1"></i>Sin asignar
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm usuarios-btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#asignarModal<?php echo $evento['id']; ?>"
                                            title="Asignar/Reasignar">
                                            <i class="bi bi-person-plus-fill"></i>
                                        </button>
                                        <a href="?page=evento_detalle&id=<?php echo $evento['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Asignar/Reasignar Organizador -->
                                <div class="modal fade" id="asignarModal<?php echo $evento['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content usuarios-modal">
                                            <div class="usuarios-modal-header">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-person-plus-fill me-2"></i>
                                                    Asignar Organizador
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body usuarios-modal-body">
                                                    <input type="hidden" name="asignar_evento" value="1">
                                                    <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Evento</label>
                                                        <input type="text" class="form-control usuarios-input" 
                                                               value="<?php echo esc($evento['nombre_evento']); ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Asignar a Organizador</label>
                                                        <select class="form-select usuarios-input" name="organizador_id">
                                                            <option value="">-- Sin asignar --</option>
                                                            <?php foreach ($organizadores as $org): ?>
                                                                <option value="<?php echo $org['id']; ?>" 
                                                                        <?php echo ($evento['organizador_id'] == $org['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo esc($org['nombre_completo']); ?> (<?php echo esc($org['correo']); ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small class="text-muted">Selecciona un organizador para asignar este evento</small>
                                                    </div>
                                                    
                                                    <?php if ($evento['organizador_id']): ?>
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        Actualmente asignado a: <strong><?php echo esc($evento['organizador_nombre']); ?></strong>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer usuarios-modal-footer">
                                                    <button type="button" class="btn btn-secondary usuarios-btn-cancel" data-bs-dismiss="modal">
                                                        Cancelar
                                                    </button>
                                                    <button type="submit" class="btn usuarios-btn-submit">
                                                        Guardar Asignación
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Sistema de filtros mejorado
let totalEventos = 0;

document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('eventosTable');
    const rows = table?.getElementsByTagName('tbody')[0]?.getElementsByTagName('tr') || [];
    totalEventos = rows.length;
    
    // Búsqueda mejorada
    const searchInput = document.getElementById('searchEvento');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            aplicarFiltros();
        });
    }
});

function aplicarFiltros() {
    const table = document.getElementById('eventosTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    // Obtener valores de filtros
    const searchText = document.getElementById('searchEvento')?.value.toLowerCase() || '';
    const filtroAsignacion = document.getElementById('filtroAsignacion')?.value || 'todos';
    const filtroEstatus = document.getElementById('filtroEstatus')?.value || 'todos';
    const filtroOrganizador = document.getElementById('filtroOrganizador')?.value || 'todos';
    const filtroFecha = document.getElementById('filtroFecha')?.value || 'todos';
    
    let visibleCount = 0;
    let filtrosActivos = false;
    
    // Verificar si hay filtros activos
    if (searchText || filtroAsignacion !== 'todos' || filtroEstatus !== 'todos' || 
        filtroOrganizador !== 'todos' || filtroFecha !== 'todos') {
        filtrosActivos = true;
    }
    
    // Obtener fecha actual para filtros de fecha
    const hoy = new Date();
    const treintaDias = new Date();
    treintaDias.setDate(hoy.getDate() + 30);
    
    const mesActual = hoy.getMonth();
    const añoActual = hoy.getFullYear();
    const primerDiaMes = new Date(añoActual, mesActual, 1);
    const ultimoDiaMes = new Date(añoActual, mesActual + 1, 0);
    
    const primerDiaSiguiente = new Date(añoActual, mesActual + 1, 1);
    const ultimoDiaSiguiente = new Date(añoActual, mesActual + 2, 0);
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        
        // 1. Filtro de búsqueda por texto
        let matchSearch = true;
        if (searchText) {
            let found = false;
            // Buscar en ID (0), Evento (1), Responsable (2)
            for (let j = 0; j < 3; j++) {
                if (cells[j]) {
                    const text = cells[j].textContent || cells[j].innerText;
                    if (text.toLowerCase().indexOf(searchText) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            matchSearch = found;
        }
        
        // 2. Filtro de asignación
        const estado = row.getAttribute('data-estado');
        let matchAsignacion = true;
        if (filtroAsignacion !== 'todos') {
            matchAsignacion = (filtroAsignacion === estado);
        }
        
        // 3. Filtro de estatus
        const estatus = row.getAttribute('data-estatus');
        let matchEstatus = true;
        if (filtroEstatus !== 'todos') {
            matchEstatus = (filtroEstatus === estatus);
        }
        
        // 4. Filtro de organizador
        const organizadorId = row.getAttribute('data-organizador');
        let matchOrganizador = true;
        if (filtroOrganizador !== 'todos') {
            matchOrganizador = (filtroOrganizador === organizadorId);
        }
        
        // 5. Filtro de fecha
        const fechaEvento = new Date(row.getAttribute('data-fecha'));
        let matchFecha = true;
        if (filtroFecha !== 'todos') {
            switch(filtroFecha) {
                case 'proximos':
                    matchFecha = (fechaEvento >= hoy && fechaEvento <= treintaDias);
                    break;
                case 'este-mes':
                    matchFecha = (fechaEvento >= primerDiaMes && fechaEvento <= ultimoDiaMes);
                    break;
                case 'proximo-mes':
                    matchFecha = (fechaEvento >= primerDiaSiguiente && fechaEvento <= ultimoDiaSiguiente);
                    break;
                case 'pasados':
                    matchFecha = (fechaEvento < hoy);
                    break;
            }
        }
        
        // Mostrar/ocultar fila según todos los filtros
        const mostrar = matchSearch && matchAsignacion && matchEstatus && matchOrganizador && matchFecha;
        row.style.display = mostrar ? '' : 'none';
        
        if (mostrar) visibleCount++;
    }
    
    // Actualizar contador de resultados
    document.getElementById('resultadosCount').textContent = visibleCount;
    
    // Mostrar/ocultar badge de filtros activos
    const badgeFiltros = document.getElementById('filtrosActivos');
    if (badgeFiltros) {
        badgeFiltros.style.display = filtrosActivos ? 'inline-block' : 'none';
    }
}

function limpiarFiltros() {
    // Limpiar búsqueda
    const searchInput = document.getElementById('searchEvento');
    if (searchInput) searchInput.value = '';
    
    // Resetear todos los selects
    const filtroAsignacion = document.getElementById('filtroAsignacion');
    if (filtroAsignacion) filtroAsignacion.value = 'todos';
    
    const filtroEstatus = document.getElementById('filtroEstatus');
    if (filtroEstatus) filtroEstatus.value = 'todos';
    
    const filtroOrganizador = document.getElementById('filtroOrganizador');
    if (filtroOrganizador) filtroOrganizador.value = 'todos';
    
    const filtroFecha = document.getElementById('filtroFecha');
    if (filtroFecha) filtroFecha.value = 'todos';
    
    // Aplicar filtros (mostrará todo)
    aplicarFiltros();
}

// Función legacy para compatibilidad
function filtrarEventos(filtro) {
    const filtroAsignacion = document.getElementById('filtroAsignacion');
    if (filtroAsignacion) {
        filtroAsignacion.value = filtro;
        aplicarFiltros();
    }
}
</script>
