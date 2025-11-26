<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}

function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Obtener términos de búsqueda
$search_nombre = trim($_GET['search_nombre'] ?? '');
$search_categoria = trim($_GET['search_categoria'] ?? '');

// Obtener categorías únicas
$categorias = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM inventario WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
}

// Buscar productos - SIEMPRE mostrar resultados (con o sin filtros)
$productos = [];
try {
    $sql = "SELECT codigo, nombre, categoria, existencia, precio FROM inventario WHERE 1=1";
    $params = [];
    
    if (!empty($search_nombre)) {
        $sql .= " AND (nombre LIKE ? OR codigo LIKE ?)";
        $params[] = "%$search_nombre%";
        $params[] = "%$search_nombre%";
    }
    
    if (!empty($search_categoria)) {
        $sql .= " AND categoria = ?";
        $params[] = $search_categoria;
    }
    
    $sql .= " ORDER BY nombre ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productos = [];
    $error = "Error al cargar productos: " . $e->getMessage();
    error_log("Error en inventario: " . $e->getMessage());
}
?>

<div class="container-fluid py-4">
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo esc($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Buscador -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8 col-xl-6">
            <div class="card evento-create-card shadow-lg">
                <div class="card-header evento-create-header" style="background-color: #FFFFFF;">
                    <h5 class="mb-0">
                        <i class="bi bi-search me-2"></i>Buscar Producto
                    </h5>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="admin_inventario">
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-tag-fill me-2" style="color: #99AA8C;"></i>Nombre o Código
                            </label>
                            <input type="text" 
                                   name="search_nombre" 
                                   class="form-control" 
                                   style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 15px; font-size: 1.05em;"
                                   placeholder="Buscar por nombre o código..." 
                                   value="<?php echo esc($search_nombre); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-bookmark-fill me-2" style="color: #99AA8C;"></i>Categoría
                            </label>
                            <select name="search_categoria" 
                                    class="form-select" 
                                    style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 15px; font-size: 1.05em;">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo esc($cat['categoria']); ?>" 
                                            <?php echo $search_categoria === $cat['categoria'] ? 'selected' : ''; ?>>
                                        <?php echo esc($cat['categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <a href="?page=admin_historial_inventario" 
                               class="btn" 
                               style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; box-shadow: 0 4px 10px rgba(108, 117, 125, 0.3); font-weight: 600;">
                                <i class="bi bi-clock-history me-2"></i>Historial
                            </a>
                            <div class="d-flex gap-2">
                                <a href="?page=admin_crear_producto" 
                                   class="btn" 
                                   style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); font-weight: 600;">
                                    <i class="bi bi-plus-circle me-2"></i>Crear Producto
                                </a>
                                <button type="submit" 
                                        class="btn" 
                                        style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px 40px; box-shadow: 0 4px 10px rgba(153, 170, 140, 0.3); font-weight: 600;">
                                    <i class="bi bi-search me-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php if (count($productos) === 0): ?>
                    <div class="empty-search-state">
                        <i class="bi bi-inbox"></i>
                        <p>No se encontraron productos con los criterios de búsqueda</p>
                    </div>
                <?php else: ?>
                    <h3 class="inventario-results-title">
                        <i class="bi bi-list-check me-2"></i>Resultados de Búsqueda (<?php echo count($productos); ?>)
                    </h3>
                    
                    <div class="card inventario-results-card">
                        <div class="table-responsive">
                            <table class="table inventario-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-tag-fill me-2"></i>Nombre</th>
                                        <th><i class="bi bi-bookmark-fill me-2"></i>Categoría</th>
                                        <th><i class="bi bi-box me-2"></i>Existencia</th>
                                        <th><i class="bi bi-currency-dollar me-2"></i>Precio</th>
                                        <th style="text-align: center;"><i class="bi bi-gear-fill me-2"></i>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $prod): ?>
                                        <tr>
                                            <td>
                                                <div class="producto-nombre">
                                                    <i class="bi bi-box-seam"></i>
                                                    <span><?php echo esc($prod['nombre']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="producto-categoria">
                                                    <?php echo esc($prod['categoria'] ?? 'Sin categoría'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="producto-existencia">
                                                    <i class="bi bi-box-seam"></i><?php echo esc($prod['existencia'] ?? '0'); ?> unidades
                                                </span>
                                            </td>
                                            <td>
                                                <span class="producto-precio">
                                                    $<?php echo number_format($prod['precio'] ?? 0, 2); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="?page=admin_editar_catalogo&codigo=<?php echo urlencode($prod['codigo']); ?>" 
                                                   class="btn btn-sm inventario-btn-edit"
                                                   title="Editar producto">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
            <?php endif; ?>
        </div>
    </div>
</div>
