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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $existencia = isset($_POST['existencia']) ? (int)$_POST['existencia'] : 0;
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0.00;
    $material = trim($_POST['material'] ?? '');
    $medida = trim($_POST['medida'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $nota = trim($_POST['nota'] ?? '');
    $imagen = '';
    
    if (empty($codigo) || empty($nombre)) {
        $error = "El código y el nombre del producto son obligatorios.";
    } else {
        try {
            // Verificar si el código ya existe
            $check = $pdo->prepare("SELECT codigo FROM inventario WHERE codigo = ?");
            $check->execute([$codigo]);
            
            if ($check->fetch()) {
                $error = "El código '$codigo' ya está registrado. Por favor usa otro código.";
            } else {
                // Procesar imagen si se subió
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../imagenes_producto/';
                    
                    // Crear directorio si no existe
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Formato de imagen no permitido. Usa JPG, PNG, GIF o WEBP.";
                    } else {
                        // Generar nombre único para la imagen
                        $new_filename = $codigo . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                            $imagen = 'imagenes_producto/' . $new_filename;
                        } else {
                            $error = "Error al guardar la imagen.";
                        }
                    }
                }
                
                if (empty($error)) {
                    // Insertar nuevo producto
                    $stmt = $pdo->prepare("INSERT INTO inventario (codigo, nombre, categoria, existencia, precio, material, medida, color, peso, nota, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$codigo, $nombre, $categoria, $existencia, $precio, $material, $medida, $color, $peso, $nota, $imagen]);
                    
                    // Registrar la creación en el historial
                    $razon_completa = "Producto creado | Código: $codigo | Nombre: $nombre | Existencia inicial: $existencia unidades | Razón: Nuevo producto agregado al inventario";
                    $stmt_op = $pdo->prepare("INSERT INTO operaciones_inventario (id_inventario, razon_motivo, fecha) VALUES (?, ?, NOW())");
                    $stmt_op->execute([$codigo, $razon_completa]);
                    
                    $success = "Producto '$nombre' creado exitosamente con código '$codigo'.";
                    
                    // Limpiar formulario
                    $_POST = [];
                }
            }
        } catch (PDOException $e) {
            $error = "Error al crear el producto: " . $e->getMessage();
            error_log("Error creando producto: " . $e->getMessage());
        }
    }
}

// Obtener categorías existentes para sugerencias
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
                        <i class="bi bi-plus-circle me-3"></i>Crear Nuevo Producto
                    </h2>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="crear_producto" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-upc-scan me-2" style="color: #99AA8C;"></i>Código del Producto *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="codigo" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['codigo'] ?? ''); ?>" 
                                       placeholder="Ej: PROD-001"
                                       required>
                                <small class="text-muted">Debe ser único e identificable</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-tag-fill me-2" style="color: #99AA8C;"></i>Nombre del Producto *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['nombre'] ?? ''); ?>" 
                                       placeholder="Ej: Silla Tiffany Blanca"
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
                                       placeholder="Ej: Mobiliario">
                                <datalist id="categorias_list">
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo esc($cat); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="text-muted">Escribe o selecciona una categoría existente</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-boxes me-2" style="color: #99AA8C;"></i>Existencia Inicial
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="existencia" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['existencia'] ?? '0'); ?>" 
                                       min="0"
                                       placeholder="0">
                                <small class="text-muted">Unidades disponibles</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-currency-dollar me-2" style="color: #99AA8C;"></i>Precio
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="precio" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['precio'] ?? '0.00'); ?>" 
                                       step="0.01" 
                                       min="0"
                                       placeholder="0.00">
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-gear me-2" style="color: #99AA8C;"></i>Material
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="material" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['material'] ?? ''); ?>" 
                                       placeholder="Ej: Madera, Plástico">
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-rulers me-2" style="color: #99AA8C;"></i>Medida
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="medida" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['medida'] ?? ''); ?>" 
                                       placeholder="Ej: 50x40x90 cm">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-palette-fill me-2" style="color: #99AA8C;"></i>Color
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="color" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['color'] ?? ''); ?>" 
                                       placeholder="Ej: Blanco, Negro, Dorado">
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-speedometer me-2" style="color: #99AA8C;"></i>Peso
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="peso" 
                                       style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                       value="<?php echo esc($_POST['peso'] ?? ''); ?>" 
                                       placeholder="Ej: 5 kg, 2.5 kg">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-sticky me-2" style="color: #99AA8C;"></i>Notas Adicionales
                            </label>
                            <textarea class="form-control" 
                                      name="nota" 
                                      rows="3" 
                                      style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;"
                                      placeholder="Notas internas, observaciones, cuidados especiales..."><?php echo esc($_POST['nota'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                <i class="bi bi-image me-2" style="color: #99AA8C;"></i>Imagen del Producto
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   name="imagen" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   style="border: 2px solid #e5e8e0; border-radius: 10px; padding: 12px;">
                            <small class="text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP</small>
                        </div>
                        
                        <div class="d-flex justify-content-between gap-2">
                            <a href="?page=admin_inventario" 
                               class="btn btn-outline-secondary" 
                               style="border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-arrow-left me-2"></i>Volver a Búsqueda
                            </a>
                            <button type="submit" 
                                    class="btn" 
                                    style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); color: white; border: none; border-radius: 10px; padding: 12px 30px; font-weight: 600;">
                                <i class="bi bi-plus-circle me-2"></i>Crear Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
