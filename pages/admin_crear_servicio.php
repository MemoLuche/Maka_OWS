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

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_servicio'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $costo_base = isset($_POST['costo_base']) ? (float)$_POST['costo_base'] : 0.00;
    $proveedor_default = trim($_POST['proveedor_default'] ?? '');
    $telefono_default = trim($_POST['telefono_default'] ?? '');
    $email_default = trim($_POST['email_default'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $estado = trim($_POST['estado'] ?? 'disponible');
    
    if (empty($codigo) || empty($nombre)) {
        $error = "El código y el nombre del servicio son obligatorios.";
    } else {
        try {
            // Verificar si el código ya existe
            $check = $pdo->prepare("SELECT id FROM servicios WHERE codigo = ?");
            $check->execute([$codigo]);
            
            if ($check->fetch()) {
                $error = "El código '$codigo' ya está registrado. Por favor usa otro código.";
            } else {
                // Insertar nuevo servicio
                $stmt = $pdo->prepare("INSERT INTO servicios (codigo, nombre, categoria, costo_base, proveedor_default, telefono_default, email_default, descripcion, notas, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$codigo, $nombre, $categoria, $costo_base, $proveedor_default, $telefono_default, $email_default, $descripcion, $notas, $estado]);
                
                $nuevo_id = $pdo->lastInsertId();
                
                // Registrar la creación en el historial
                $stmt_hist = $pdo->prepare("INSERT INTO operaciones_servicios (servicio_id, razon_motivo, fecha) VALUES (?, ?, NOW())");
                $stmt_hist->execute([$nuevo_id, "Servicio creado | Código: $codigo | Nombre: $nombre"]);
                
                $success = "Servicio '$nombre' creado exitosamente con código '$codigo'.";
                
                // Limpiar formulario
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = "Error al crear el servicio: " . $e->getMessage();
            error_log("Error creando servicio: " . $e->getMessage());
        }
    }
}

// Obtener categorías existentes para sugerencias
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
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?php echo esc($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo esc($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Formulario de Creación -->
            <div class="card evento-create-card shadow-lg">
                <div class="card-header evento-create-header">
                    <h2 class="mb-0">
                        <i class="bi bi-plus-circle me-3"></i>Crear Nuevo Servicio
                    </h2>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <form method="POST">
                        <input type="hidden" name="crear_servicio" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-upc-scan me-2" style="color: #99AA8C;"></i>Código del Servicio *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="codigo" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['codigo'] ?? ''); ?>" 
                                       placeholder="Ej: SRV-001"
                                       required>
                                <small class="text-muted">Debe ser único e identificable</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-tag-fill me-2" style="color: #99AA8C;"></i>Nombre del Servicio *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['nombre'] ?? ''); ?>" 
                                       placeholder="Ej: DJ Profesional"
                                       required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-bookmark-fill me-2" style="color: #99AA8C;"></i>Categoría
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="categoria" 
                                       list="categorias_list"
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['categoria'] ?? ''); ?>" 
                                       placeholder="Ej: Audio y Música">
                                <datalist id="categorias_list">
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo esc($cat); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="text-muted">Escribe o selecciona una categoría existente</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-currency-dollar me-2" style="color: #99AA8C;"></i>Costo Base
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="costo_base" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['costo_base'] ?? '0.00'); ?>" 
                                       step="0.01" 
                                       min="0"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-building me-2" style="color: #99AA8C;"></i>Proveedor Sugerido
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="proveedor_default" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['proveedor_default'] ?? ''); ?>" 
                                       placeholder="Nombre del proveedor">
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-telephone-fill me-2" style="color: #99AA8C;"></i>Teléfono
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="telefono_default" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['telefono_default'] ?? ''); ?>" 
                                       placeholder="(123) 456-7890">
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-envelope-fill me-2" style="color: #99AA8C;"></i>Email
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       name="email_default" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['email_default'] ?? ''); ?>" 
                                       placeholder="proveedor@email.com">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-toggle-on me-2" style="color: #99AA8C;"></i>Estado del Servicio *
                            </label>
                            <select class="form-select" 
                                    name="estado" 
                                    style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                    required>
                                <option value="disponible" selected>✅ Disponible</option>
                                <option value="baja_temporal">⚠️ Baja Temporal</option>
                                <option value="baja_definitiva">❌ Baja Definitiva</option>
                            </select>
                            <small class="text-muted">Define la disponibilidad del servicio</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-card-text me-2" style="color: #99AA8C;"></i>Descripción
                            </label>
                            <textarea class="form-control" 
                                      name="descripcion" 
                                      rows="3" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Descripción detallada del servicio..."><?php echo esc($_POST['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-sticky me-2" style="color: #99AA8C;"></i>Notas Adicionales
                            </label>
                            <textarea class="form-control" 
                                      name="notas" 
                                      rows="3" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Notas internas, requisitos especiales, etc..."><?php echo esc($_POST['notas'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between gap-2">
                            <a href="?page=admin_servicios" 
                               class="btn btn-outline-secondary" 
                               style="border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-arrow-left me-2"></i>Volver a Búsqueda
                            </a>
                            <button type="submit" 
                                    class="btn" 
                                    style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-plus-circle me-2"></i>Crear Servicio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
