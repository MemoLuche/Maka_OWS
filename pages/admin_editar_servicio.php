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
$servicio = null;

// Obtener ID del servicio
$servicio_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($servicio_id <= 0) {
    header('Location: ?page=admin_servicios');
    exit;
}

// Obtener datos del servicio
try {
    $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servicio) {
        header('Location: ?page=admin_servicios');
        exit;
    }
} catch (PDOException $e) {
    $error = "Error al cargar el servicio: " . $e->getMessage();
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_servicio'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $costo_base = isset($_POST['costo_estimado']) ? (float)$_POST['costo_estimado'] : 0.00;
    $proveedor_default = trim($_POST['proveedor_sugerido'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $estado = trim($_POST['estado'] ?? 'disponible');
    $razon_motivo = trim($_POST['razon_motivo'] ?? '');
    
    if (empty($nombre) || empty($razon_motivo)) {
        $error = "El nombre del servicio y la razón del cambio son obligatorios.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Obtener valores anteriores
            $stmt_anterior = $pdo->prepare("SELECT nombre, categoria, costo_base, proveedor_default, estado FROM servicios WHERE id = ?");
            $stmt_anterior->execute([$servicio_id]);
            $valores_anteriores = $stmt_anterior->fetch(PDO::FETCH_ASSOC);
            
            // Detectar cambios
            $cambios = [];
            if ($nombre !== $valores_anteriores['nombre']) {
                $cambios[] = 'Nombre: "' . $valores_anteriores['nombre'] . '" → "' . $nombre . '"';
            }
            if ($categoria !== $valores_anteriores['categoria']) {
                $cambios[] = 'Categoría: "' . ($valores_anteriores['categoria'] ?: 'N/A') . '" → "' . ($categoria ?: 'N/A') . '"';
            }
            if ($costo_base != $valores_anteriores['costo_base']) {
                $cambios[] = 'Costo: $' . number_format($valores_anteriores['costo_base'], 2) . ' → $' . number_format($costo_base, 2);
            }
            if ($proveedor_default !== $valores_anteriores['proveedor_default']) {
                $cambios[] = 'Proveedor: "' . ($valores_anteriores['proveedor_default'] ?: 'N/A') . '" → "' . ($proveedor_default ?: 'N/A') . '"';
            }
            if ($estado !== $valores_anteriores['estado']) {
                $cambios[] = 'Estado: "' . $valores_anteriores['estado'] . '" → "' . $estado . '"';
            }
            
            $detalle_cambios = !empty($cambios) ? implode(' | ', $cambios) : 'Sin cambios detectados';
            
            // Actualizar el servicio
            $stmt = $pdo->prepare("UPDATE servicios SET nombre = ?, categoria = ?, costo_base = ?, proveedor_default = ?, descripcion = ?, notas = ?, estado = ? WHERE id = ?");
            $stmt->execute([$nombre, $categoria, $costo_base, $proveedor_default, $descripcion, $notas, $estado, $servicio_id]);
            
            // Registrar la operación con los cambios detectados
            $razon_completa = $detalle_cambios . ' | Razón: ' . $razon_motivo;
            $stmt_op = $pdo->prepare("INSERT INTO operaciones_servicios (servicio_id, razon_motivo, fecha) VALUES (?, ?, NOW())");
            $stmt_op->execute([$servicio_id, $razon_completa]);
            
            $pdo->commit();
            $success = "Servicio actualizado exitosamente.";
            
            // Recargar datos del servicio
            $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al actualizar el servicio: " . $e->getMessage();
        }
    }
}

// Obtener historial de operaciones
$historial = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM operaciones_servicios WHERE servicio_id = ? ORDER BY fecha DESC LIMIT 10");
    $stmt->execute([$servicio_id]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar historial: " . $e->getMessage());
}

// Obtener categorías únicas de servicios
$categorias = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM servicios WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
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
                        <i class="bi bi-pencil-square me-3"></i>Editar Servicio
                    </h2>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <form method="POST">
                        <input type="hidden" name="actualizar_servicio" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-tag-fill me-2" style="color: #99AA8C;"></i>Nombre del Servicio *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($servicio['nombre']); ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-upc-scan me-2" style="color: #99AA8C;"></i>Código
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px; background-color: #e9ecef;"
                                       value="<?php echo esc($servicio['codigo'] ?? 'N/A'); ?>" 
                                       readonly>
                                <small class="text-muted">El código no se puede modificar</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-bookmark-fill me-2" style="color: #99AA8C;"></i>Categoría
                                </label>
                                <select class="form-select" 
                                        name="categoria" 
                                        style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;">
                                    <option value="">-- Seleccionar categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo esc($cat); ?>" <?php echo ($servicio['categoria'] === $cat) ? 'selected' : ''; ?>>
                                            <?php echo esc($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-currency-dollar me-2" style="color: #99AA8C;"></i>Costo Estimado
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="costo_estimado" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($servicio['costo_base'] ?? 0.00); ?>" 
                                       step="0.01" 
                                       min="0">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-building me-2" style="color: #99AA8C;"></i>Proveedor Sugerido
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="proveedor_sugerido" 
                                   style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                   value="<?php echo esc($servicio['proveedor_default'] ?? ''); ?>" 
                                   placeholder="Nombre del proveedor">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-card-text me-2" style="color: #99AA8C;"></i>Descripción
                            </label>
                            <textarea class="form-control" 
                                      name="descripcion" 
                                      rows="3" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Descripción del servicio..."><?php echo esc($servicio['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-sticky me-2" style="color: #99AA8C;"></i>Notas Adicionales
                            </label>
                            <textarea class="form-control" 
                                      name="notas" 
                                      rows="3" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Notas internas..."><?php echo esc($servicio['notas'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-toggle-on me-2" style="color: #99AA8C;"></i>Estado del Servicio *
                            </label>
                            <select class="form-select" 
                                    name="estado" 
                                    style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;" 
                                    required>
                                <option value="disponible" <?php echo ($servicio['estado'] === 'disponible') ? 'selected' : ''; ?>>
                                    ✅ Disponible
                                </option>
                                <option value="baja_temporal" <?php echo ($servicio['estado'] === 'baja_temporal') ? 'selected' : ''; ?>>
                                    ⚠️ Baja Temporal (No disponible temporalmente)
                                </option>
                                <option value="baja_definitiva" <?php echo ($servicio['estado'] === 'baja_definitiva') ? 'selected' : ''; ?>>
                                    ❌ Baja Definitiva (Contrato terminado / Servicio descontinuado)
                                </option>
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>Cambiar a "Baja" impide que el servicio se asigne a nuevos eventos.
                            </small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-chat-left-text-fill me-2" style="color: #99AA8C;"></i>Razón del Cambio *
                            </label>
                            <textarea class="form-control" 
                                      name="razon_motivo" 
                                      rows="4" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Explica por qué se está editando este servicio..."
                                      required></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>Este campo es obligatorio y quedará registrado en el historial.
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between gap-2">
                            <a href="?page=admin_servicios" 
                               class="btn btn-outline-secondary" 
                               style="border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-arrow-left me-2"></i>Volver
                            </a>
                            <button type="submit" 
                                    class="btn" 
                                    style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Historial de Cambios -->
            <?php if (!empty($historial)): ?>
                <div class="card mt-4 shadow-lg">
                    <div class="card-header" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white;">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Historial de Cambios (Últimos 10)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cambios Realizados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $op): ?>
                                        <tr>
                                            <td style="white-space: nowrap;">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($op['fecha'])); ?>
                                            </td>
                                            <td>
                                                <small><?php echo esc($op['razon_motivo']); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
