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
$producto = null;

// Obtener código del producto
$codigo_producto = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo_producto)) {
    header('Location: ?page=admin_inventario');
    exit;
}

// Obtener datos del producto
try {
    $stmt = $pdo->prepare("SELECT * FROM inventario WHERE codigo = ?");
    $stmt->execute([$codigo_producto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        header('Location: ?page=admin_inventario');
        exit;
    }
} catch (PDOException $e) {
    $error = "Error al cargar el producto: " . $e->getMessage();
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_producto'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $existencia = isset($_POST['existencia']) ? (int)$_POST['existencia'] : 0;
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0.00;
    $razon_motivo = trim($_POST['razon_motivo'] ?? '');
    
    if (empty($nombre) || empty($razon_motivo)) {
        $error = "El nombre del producto y la razón del cambio son obligatorios.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Obtener valores anteriores
            $stmt_anterior = $pdo->prepare("SELECT nombre, categoria, existencia, precio FROM inventario WHERE codigo = ?");
            $stmt_anterior->execute([$codigo_producto]);
            $valores_anteriores = $stmt_anterior->fetch(PDO::FETCH_ASSOC);
            
            // Detectar cambios
            $cambios = [];
            if ($nombre !== $valores_anteriores['nombre']) {
                $cambios[] = 'Nombre: "' . $valores_anteriores['nombre'] . '" → "' . $nombre . '"';
            }
            if ($categoria !== $valores_anteriores['categoria']) {
                $cambios[] = 'Categoría: "' . ($valores_anteriores['categoria'] ?: 'N/A') . '" → "' . ($categoria ?: 'N/A') . '"';
            }
            if ($existencia != $valores_anteriores['existencia']) {
                $cambios[] = 'Existencia: ' . $valores_anteriores['existencia'] . ' → ' . $existencia . ' unidades';
            }
            if ($precio != $valores_anteriores['precio']) {
                $cambios[] = 'Precio: $' . number_format($valores_anteriores['precio'], 2) . ' → $' . number_format($precio, 2);
            }
            
            $detalle_cambios = !empty($cambios) ? implode(' | ', $cambios) : 'Sin cambios detectados';
            
            // Actualizar el producto en inventario
            $stmt = $pdo->prepare("UPDATE inventario SET nombre = ?, categoria = ?, existencia = ?, precio = ? WHERE codigo = ?");
            $stmt->execute([$nombre, $categoria, $existencia, $precio, $codigo_producto]);
            
            // Registrar la operación con los cambios detectados
            $razon_completa = $detalle_cambios . ' | Razón: ' . $razon_motivo;
            $stmt_op = $pdo->prepare("INSERT INTO operaciones_inventario (id_inventario, razon_motivo, fecha) VALUES (?, ?, NOW())");
            $stmt_op->execute([$codigo_producto, $razon_completa]);
            
            $pdo->commit();
            $success = "Producto actualizado exitosamente.";
            
            // Recargar datos del producto
            $stmt = $pdo->prepare("SELECT * FROM inventario WHERE codigo = ?");
            $stmt->execute([$codigo_producto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al actualizar el producto: " . $e->getMessage();
        }
    }
}

// Obtener historial de operaciones
$historial = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM operaciones_inventario WHERE id_inventario = ? ORDER BY fecha DESC LIMIT 10");
    $stmt->execute([$codigo_producto]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar historial: " . $e->getMessage());
}

// Obtener categorías únicas del inventario
$categorias = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM inventario WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error al cargar categorías: " . $e->getMessage());
}
?>

<div class="container-fluid py-4">
    <?php if ($error): ?>
        <div class="alert alert-danger-custom alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?php echo esc($error); ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success-custom alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span><?php echo esc($success); ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Formulario de Edición -->
            <div class="card evento-create-card shadow-lg">
                <div class="card-header evento-create-header">
                    <h2 class="mb-0">
                        <i class="bi bi-pencil-square me-3"></i>Editar Producto
                    </h2>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <form method="POST">
                        <input type="hidden" name="actualizar_producto" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-tag-fill me-2" style="color: #99AA8C;"></i>Nombre del Producto *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($producto['nombre']); ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-bookmark-fill me-2" style="color: #99AA8C;"></i>Categoría
                                </label>
                                <select class="form-select" 
                                        name="categoria" 
                                        style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;">
                                    <option value="">-- Seleccionar categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo esc($cat); ?>" <?php echo ($producto['categoria'] === $cat) ? 'selected' : ''; ?>>
                                            <?php echo esc($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-box-seam me-2" style="color: #99AA8C;"></i>Existencia (unidades)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="existencia" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($producto['existencia'] ?? 0); ?>" 
                                       min="0">
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-currency-dollar me-2" style="color: #99AA8C;"></i>Precio
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="precio" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($producto['precio'] ?? 0.00); ?>" 
                                       step="0.01" 
                                       min="0">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-chat-left-text-fill me-2" style="color: #99AA8C;"></i>Razón del Cambio *
                            </label>
                            <textarea class="form-control" 
                                      name="razon_motivo" 
                                      rows="4" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Explica por qué se está editando este producto..."
                                      required></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>Este campo es obligatorio y quedará registrado en el historial.
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" 
                                    class="btn" 
                                    style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
