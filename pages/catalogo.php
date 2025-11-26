    <?php
  require_once __DIR__ . '/../config/conexion.php';
  // Verificación de sesión
  if (!isset($_SESSION['logged_in'])) {
      header('Location: ?page=login');
      exit;
  }
  // helper de escape
  function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

  // Verificar si es administrador
  $isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

  // Obtener eventos del usuario para poder asignar inventario
  $eventosUsuario = [];
  try {
      $sqlEventos = "SELECT id, nombre_evento, nombre_novio_1, nombre_novio_2, fecha_evento, estatus FROM eventos WHERE 1=1";
      $paramsEventos = [];
      
      // Si NO es admin, solo ver eventos asignados
      if (!$isAdmin) {
          $sqlEventos .= " AND organizador_id = ?";
          $paramsEventos[] = $_SESSION['user_id'];
      }
      
      // Solo eventos activos (no finalizados ni cancelados)
      $sqlEventos .= " AND estatus NOT IN ('Finalizado', 'Cancelado')";
      $sqlEventos .= " ORDER BY fecha_evento ASC";
      
      $stmtEventos = $pdo->prepare($sqlEventos);
      $stmtEventos->execute($paramsEventos);
      $eventosUsuario = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      error_log("Error obteniendo eventos: " . $e->getMessage());
  }

  // Manejar asignación de inventario a evento
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_inventario'])) {
      $inventario_codigo = trim($_POST['inventario_codigo'] ?? '');
      $evento_id = (int)($_POST['evento_id'] ?? 0);
      $cantidad = (int)($_POST['cantidad'] ?? 1);
      $notas = trim($_POST['notas'] ?? '');
      
      if ($evento_id > 0 && !empty($inventario_codigo) && $cantidad > 0) {
          try {
              // Verificar que el evento pertenezca al usuario (si no es admin)
              $sqlVerificar = "SELECT id FROM eventos WHERE id = ?";
              $paramsVerificar = [$evento_id];
              
              if (!$isAdmin) {
                  $sqlVerificar .= " AND organizador_id = ?";
                  $paramsVerificar[] = $_SESSION['user_id'];
              }
              
              $stmtVerificar = $pdo->prepare($sqlVerificar);
              $stmtVerificar->execute($paramsVerificar);
              
              if ($stmtVerificar->fetch()) {
                  // Insertar en evento_inventario
                  $stmtInsert = $pdo->prepare("INSERT INTO evento_inventario (evento_id, inventario_codigo, cantidad, notas) VALUES (?, ?, ?, ?)");
                  $stmtInsert->execute([$evento_id, $inventario_codigo, $cantidad, $notas]);
                  
                  $successMsg = "Artículo agregado al evento exitosamente.";
              } else {
                  $errorMsg = "No tienes permiso para modificar este evento.";
              }
          } catch (PDOException $e) {
              $errorMsg = "Error al asignar inventario: " . $e->getMessage();
          }
      } else {
          $errorMsg = "Datos incompletos para asignar inventario.";
      }
  }

    // (Formulario de agregar productos eliminado: la gestión de inventario
    // ahora se realiza manualmente fuera de este panel.)

  // Obtener categorías únicas para el filtro
  try {
      $cat_stmt = $pdo->query("SELECT DISTINCT categoria FROM inventario WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
      $categorias = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      $categorias = [];
      error_log("Error obteniendo categorías: " . $e->getMessage());
  }

  // Obtener valores de filtro (si existen)
  $search_nombre = trim($_GET['search_nombre'] ?? '');
  $search_categoria = trim($_GET['search_categoria'] ?? '');
  $search_color = trim($_GET['search_color'] ?? '');
  $search_material = trim($_GET['search_material'] ?? '');
  $search_precio_min = trim($_GET['search_precio_min'] ?? '');
  $search_precio_max = trim($_GET['search_precio_max'] ?? '');
  $search_disponible = isset($_GET['search_disponible']) ? (int)$_GET['search_disponible'] : 0;

  // Obtener colores únicos
  try {
      $color_stmt = $pdo->query("SELECT DISTINCT color FROM inventario WHERE color IS NOT NULL AND color != '' AND color != 'S/P' ORDER BY color ASC");
      $colores = $color_stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      $colores = [];
  }

  // Obtener materiales únicos
  try {
      $material_stmt = $pdo->query("SELECT DISTINCT material FROM inventario WHERE material IS NOT NULL AND material != '' ORDER BY material ASC");
      $materiales = $material_stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      $materiales = [];
  }

  // Preparar consulta principal con filtros
  $sql = "SELECT * FROM inventario";
  $params = [];
  $where = [];

  if (!empty($search_nombre)) {
      $where[] = "(nombre LIKE ? OR codigo LIKE ?)";
      $params[] = "%$search_nombre%";
      $params[] = "%$search_nombre%";
  }

  if (!empty($search_categoria)) {
      $where[] = "categoria = ?";
      $params[] = $search_categoria;
  }

  if (!empty($search_color)) {
      $where[] = "color = ?";
      $params[] = $search_color;
  }

  if (!empty($search_material)) {
      $where[] = "material = ?";
      $params[] = $search_material;
  }

  if (!empty($search_precio_min)) {
      $where[] = "CAST(precio AS DECIMAL) >= ?";
      $params[] = $search_precio_min;
  }

  if (!empty($search_precio_max)) {
      $where[] = "CAST(precio AS DECIMAL) <= ?";
      $params[] = $search_precio_max;
  }

  if ($search_disponible === 1) {
      $where[] = "existencia > 0";
  }

  if (!empty($where)) {
      $sql .= " WHERE " . implode(" AND ", $where);
  }
    $sql .= " ORDER BY nombre";

    // Paginación: mostrar 50 productos por página
    $perPage = 50;
    $pageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $offset = ($pageNum - 1) * $perPage;

    // Contar total de resultados para calcular páginas
    try {
      $countSql = "SELECT COUNT(*) FROM inventario" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : '');
      $countStmt = $pdo->prepare($countSql);
      $countStmt->execute($params);
      $totalItems = (int)$countStmt->fetchColumn();
    } catch (PDOException $e) {
      $totalItems = 0;
      error_log("Error contando productos: " . $e->getMessage());
    }

    $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $perPage) : 1;

    // Añadir límite y offset (usamos valores enteros directos para evitar problemas de binding en LIMIT)
    $sql .= " LIMIT " . intval($offset) . ", " . intval($perPage);

    // Ejecutar la consulta paginada
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      $productos = [];
      $error = "Error al cargar productos: " . $e->getMessage();
      error_log("Error obteniendo productos: " . $e->getMessage());
    }
  ?>
  <div class="site-container">
  <h1 class="catalogo-title mb-4">Catálogo</h1>

  <?php if (isset($successMsg) && $successMsg): ?>
  <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo esc($successMsg); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <?php if (isset($errorMsg) && $errorMsg): ?>
  <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo esc($errorMsg); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <?php if (isset($error) && $error): ?>
  <div class="alert alert-danger"><?php echo esc($error); ?></div>
  <?php endif; ?>

  <!-- Layout: Filtro a la izquierda + Grid de productos a la derecha -->
  <div class="catalogo-layout">
    
    <!-- Sidebar izquierdo: Filtros mejorados -->
    <aside class="catalogo-sidebar">
      <div class="card catalogo-filters">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="bi bi-funnel-fill me-2"></i>Filtros de Búsqueda
          </h5>
        </div>
        <div class="card-body">
          <form method="GET" action="index.php" id="filtrosForm">
            <input type="hidden" name="page" value="catalogo">
            
            <!-- Búsqueda por nombre/código -->
            <div class="mb-3">
              <label for="search_nombre" class="form-label fw-bold">
                <i class="bi bi-search me-1"></i>Buscar
              </label>
              <input type="text" class="form-control" id="search_nombre" name="search_nombre" 
                     value="<?php echo esc($search_nombre); ?>" 
                     placeholder="Nombre o código...">
              <small class="text-muted">Busca por nombre o código del producto</small>
            </div>

            <hr>
            
            <!-- Filtro por Categoría -->
            <div class="mb-3">
              <label for="search_categoria" class="form-label fw-bold">
                <i class="bi bi-grid-3x3-gap me-1"></i>Categoría
              </label>
              <select class="form-select" id="search_categoria" name="search_categoria">
                <option value="">Todas las categorías</option>
                <?php foreach($categorias as $cat): ?>
                  <option value="<?php echo esc($cat['categoria']); ?>" 
                          <?php echo ($cat['categoria'] == $search_categoria) ? 'selected' : ''; ?>>
                    <?php echo esc($cat['categoria']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Filtro por Color -->
            <div class="mb-3">
              <label for="search_color" class="form-label fw-bold">
                <i class="bi bi-palette me-1"></i>Color
              </label>
              <select class="form-select" id="search_color" name="search_color">
                <option value="">Todos los colores</option>
                <?php foreach($colores as $col): ?>
                  <option value="<?php echo esc($col['color']); ?>" 
                          <?php echo ($col['color'] == $search_color) ? 'selected' : ''; ?>>
                    <?php echo esc($col['color']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Filtro por Material -->
            <div class="mb-3">
              <label for="search_material" class="form-label fw-bold">
                <i class="bi bi-box-seam me-1"></i>Material
              </label>
              <select class="form-select" id="search_material" name="search_material">
                <option value="">Todos los materiales</option>
                <?php foreach($materiales as $mat): ?>
                  <option value="<?php echo esc($mat['material']); ?>" 
                          <?php echo ($mat['material'] == $search_material) ? 'selected' : ''; ?>>
                    <?php echo esc($mat['material']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <hr>

            <!-- Rango de Precios -->
            <div class="mb-3">
              <label class="form-label fw-bold">
                <i class="bi bi-currency-dollar me-1"></i>Rango de Precio
              </label>
              <div class="row g-2">
                <div class="col-6">
                  <input type="number" class="form-control form-control-sm" 
                         name="search_precio_min" 
                         value="<?php echo esc($search_precio_min); ?>" 
                         placeholder="Mínimo" 
                         min="0" 
                         step="0.01">
                </div>
                <div class="col-6">
                  <input type="number" class="form-control form-control-sm" 
                         name="search_precio_max" 
                         value="<?php echo esc($search_precio_max); ?>" 
                         placeholder="Máximo" 
                         min="0" 
                         step="0.01">
                </div>
              </div>
              <small class="text-muted">Precio en MXN</small>
            </div>

            <!-- Checkbox solo disponibles -->
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" 
                       name="search_disponible" value="1" 
                       id="search_disponible"
                       <?php echo ($search_disponible === 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="search_disponible">
                  <i class="bi bi-check-circle me-1"></i>Solo productos disponibles
                </label>
              </div>
            </div>

            <hr>

            <!-- Botones de acción -->
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel me-1"></i>Aplicar Filtros
              </button>
              <a href="?page=catalogo" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Limpiar Filtros
              </a>
            </div>

            <!-- Contador de filtros activos -->
            <?php 
            $filtrosActivos = 0;
            if (!empty($search_nombre)) $filtrosActivos++;
            if (!empty($search_categoria)) $filtrosActivos++;
            if (!empty($search_color)) $filtrosActivos++;
            if (!empty($search_material)) $filtrosActivos++;
            if (!empty($search_precio_min)) $filtrosActivos++;
            if (!empty($search_precio_max)) $filtrosActivos++;
            if ($search_disponible === 1) $filtrosActivos++;
            
            if ($filtrosActivos > 0): ?>
            <div class="alert alert-info mt-3 mb-0 py-2">
              <small>
                <i class="bi bi-info-circle me-1"></i>
                <strong><?php echo $filtrosActivos; ?></strong> filtro<?php echo $filtrosActivos > 1 ? 's' : ''; ?> activo<?php echo $filtrosActivos > 1 ? 's' : ''; ?>
              </small>
            </div>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </aside>

    <!-- Contenido principal: Grid de productos -->
    <main class="catalogo-main">
      <div class="row g-3">
    <?php foreach($productos as $prod): 
        // Determinar ruta de imagen
        $imagenSrc = 'imagenes/placeholder.png'; // Por defecto
        if (!empty($prod['imagen']) && file_exists(__DIR__ . '/../' . $prod['imagen'])) {
            $imagenSrc = $prod['imagen'];
        }
    ?>
    <div class="col-md-4">
      <div class="card p-3 text-center catalogo-card">
        <img src="<?php echo esc($imagenSrc); ?>" alt="<?php echo esc($prod['nombre'] ?? 'Producto'); ?>" class="card-img-top product-img">
        <div class="card-body">
          <h6 class="card-title"><?php echo esc($prod['nombre'] ?? 'Producto'); ?></h6>
          <p class="card-text">
              <?php echo esc($prod['categoria'] ?? 'Categoría'); ?><br>
              <?php if (isset($prod['precio']) && $prod['precio'] !== null && $prod['precio'] !== ''): ?>
              <span class="text-success fw-bold">$<?php echo number_format((float)$prod['precio'], 2); ?></span><br>
              <?php endif; ?>
              <span class="badge <?php echo ($prod['existencia'] > 0) ? 'bg-success' : 'bg-danger'; ?>">
                  Existencia: <?php echo esc($prod['existencia'] ?? '0'); ?>
              </span>
          </p>
          <div class="d-grid gap-2">
              <button type="button" class="btn btn-catalogo-detalle btn-sm" data-bs-toggle="modal" data-bs-target="#modal<?php echo esc($prod['codigo'] ?? '1'); ?>">
                  <i class="bi bi-info-circle me-1"></i>Ver Detalles
              </button>
              <?php if (count($eventosUsuario) > 0 && ($prod['existencia'] ?? 0) > 0): ?>
              <button type="button" class="btn btn-catalogo-asignar btn-sm" data-bs-toggle="modal" data-bs-target="#asignarModal<?php echo esc($prod['codigo'] ?? '1'); ?>">
                  <i class="bi bi-calendar-plus me-1"></i>Asignar a Evento
              </button>
              <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para detalles -->
    <div class="modal fade" id="modal<?php echo esc($prod['codigo'] ?? '1'); ?>" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><?php echo esc($prod['nombre'] ?? 'Producto'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p><strong>Código:</strong> <?php echo esc($prod['codigo'] ?? 'N/A'); ?></p>
            <p><strong>Nombre:</strong> <?php echo esc($prod['nombre'] ?? 'N/A'); ?></p>
            <p><strong>Categoría:</strong> <?php echo esc($prod['categoria'] ?? 'N/A'); ?></p>
            <p><strong>Existencia:</strong> <?php echo esc($prod['existencia'] ?? '0'); ?></p>
            <?php if (isset($prod['precio']) && $prod['precio'] !== null && $prod['precio'] !== ''): ?>
            <p><strong>Precio:</strong> $<?php echo number_format((float)$prod['precio'], 2); ?> MXN</p>
            <?php endif; ?>
            <p><strong>Material:</strong> <?php echo esc($prod['material'] ?? 'N/A'); ?></p>
            <p><strong>Medida:</strong> <?php echo esc($prod['medida'] ?? 'N/A'); ?></p>
            <p><strong>Color:</strong> <?php echo esc($prod['color'] ?? 'N/A'); ?></p>
            <p><strong>Peso:</strong> <?php echo esc($prod['peso'] ?? 'N/A'); ?></p>
            <p><strong>Nota:</strong> <?php echo esc($prod['nota'] ?? 'N/A'); ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para asignar a evento -->
    <?php if (count($eventosUsuario) > 0 && ($prod['existencia'] ?? 0) > 0): ?>
    <div class="modal fade" id="asignarModal<?php echo esc($prod['codigo'] ?? '1'); ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content usuarios-modal">
          <div class="usuarios-modal-header">
            <h5 class="modal-title">
              <i class="bi bi-calendar-plus me-2"></i>Asignar a Evento
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <div class="modal-body usuarios-modal-body">
              <input type="hidden" name="asignar_inventario" value="1">
              <input type="hidden" name="inventario_codigo" value="<?php echo esc($prod['codigo']); ?>">
              
              <div class="mb-3">
                <label class="form-label usuarios-label">Artículo</label>
                <input type="text" class="form-control usuarios-input" 
                       value="<?php echo esc($prod['nombre']); ?>" readonly>
                <small class="text-muted">
                    Código: <?php echo esc($prod['codigo']); ?> | 
                    Disponible: <?php echo esc($prod['existencia']); ?> unidades
                </small>
              </div>
              
              <div class="mb-3">
                <label class="form-label usuarios-label">Seleccionar Evento</label>
                <select class="form-select usuarios-input" name="evento_id" required>
                  <option value="">-- Selecciona un evento --</option>
                  <?php foreach ($eventosUsuario as $evt): ?>
                    <option value="<?php echo $evt['id']; ?>">
                      <?php echo esc($evt['nombre_evento']); ?> - 
                      <?php echo date('d/m/Y', strtotime($evt['fecha_evento'])); ?> - 
                      <span class="badge <?php 
                        echo $evt['estatus'] === 'Confirmado' ? 'bg-success' : 
                             ($evt['estatus'] === 'En Proceso' ? 'bg-info' : 'bg-warning'); 
                      ?>"><?php echo esc($evt['estatus']); ?></span>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label usuarios-label">Cantidad</label>
                <input type="number" class="form-control usuarios-input" name="cantidad" 
                       min="1" max="<?php echo (int)$prod['existencia']; ?>" value="1" required>
                <small class="text-muted">Máximo: <?php echo esc($prod['existencia']); ?> unidades</small>
              </div>
              
              <div class="mb-3">
                <label class="form-label usuarios-label">Notas (opcional)</label>
                <textarea class="form-control usuarios-input" name="notas" rows="3" 
                          placeholder="Ej: Ubicación específica, condiciones especiales..."></textarea>
              </div>
            </div>
            <div class="modal-footer usuarios-modal-footer">
              <button type="button" class="btn btn-secondary usuarios-btn-cancel" data-bs-dismiss="modal">
                Cancelar
              </button>
              <button type="submit" class="btn usuarios-btn-submit">
                <i class="bi bi-check-circle me-1"></i>Asignar a Evento
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
      </div>

      <?php if (empty($productos)): ?>
      <div class="alert alert-info text-center">
        <?php if (!empty($search_nombre) || !empty($search_categoria)): ?>
          No se encontraron productos que coincidan con los criterios de búsqueda.
        <?php else: ?>
          No hay productos en el catálogo.
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Paginación -->
      <?php if ($totalItems > $perPage): ?>
      <nav aria-label="Paginación catálogo" class="mt-4 catalogo-pagination">
        <ul class="pagination justify-content-center">
          <?php
            // Construir base URL manteniendo filtros
            $baseParams = [];
            if ($search_nombre !== '') $baseParams['search_nombre'] = $search_nombre;
            if ($search_categoria !== '') $baseParams['search_categoria'] = $search_categoria;
            $buildUrl = function($p) use ($baseParams) {
                $params = $baseParams;
                $params['page'] = 'catalogo';
                $params['p'] = $p;
                return 'index.php?' . http_build_query($params);
            };
          ?>
          <li class="page-item <?php if ($pageNum <= 1) echo 'disabled'; ?>">
            <a class="page-link" href="<?php echo $pageNum > 1 ? $buildUrl($pageNum - 1) : '#'; ?>">Anterior</a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $pageNum): ?>
              <li class="page-item active"><span class="page-link"><?php echo $i; ?></span></li>
            <?php elseif ($i <= 3 || $i > $totalPages - 3 || ($i >= $pageNum - 2 && $i <= $pageNum + 2)): ?>
              <li class="page-item"><a class="page-link" href="<?php echo $buildUrl($i); ?>"><?php echo $i; ?></a></li>
            <?php elseif ($i == 4 && $pageNum > 6): ?>
              <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php elseif ($i == $totalPages - 3 && $pageNum < $totalPages - 5): ?>
              <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
          <?php endfor; ?>
          <li class="page-item <?php if ($pageNum >= $totalPages) echo 'disabled'; ?>">
            <a class="page-link" href="<?php echo $pageNum < $totalPages ? $buildUrl($pageNum + 1) : '#'; ?>">Siguiente</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
    </main>
    
  </div><!-- fin .catalogo-layout -->
  </div><!-- fin .site-container -->
