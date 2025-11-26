<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}

// Helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Obtener ID del evento desde URL
$evento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($evento_id <= 0) {
    header('Location: ?page=eventos');
    exit;
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_evento'])) {
    try {
        $pdo->beginTransaction();
        
        // Procesar imagen si se subió una nueva
        $imagen_principal = $_POST['imagen_actual'];
        
        // Si se solicitó eliminar la imagen, establecer la imagen por defecto
        if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === '1') {
            $imagen_principal = 'imagenes/cover (1).jpg';
        }
        // Si se subió una nueva imagen
        elseif (isset($_FILES['imagen_evento']) && $_FILES['imagen_evento']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $file_type = $_FILES['imagen_evento']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $extension = pathinfo($_FILES['imagen_evento']['name'], PATHINFO_EXTENSION);
                $nuevo_nombre = 'evento_' . $evento_id . '_' . time() . '.' . $extension;
                $ruta_destino = __DIR__ . '/../imagenes/' . $nuevo_nombre;
                
                if (move_uploaded_file($_FILES['imagen_evento']['tmp_name'], $ruta_destino)) {
                    $imagen_principal = 'imagenes/' . $nuevo_nombre;
                }
            }
        }
        
        // Actualizar evento
        $stmt = $pdo->prepare("
            UPDATE eventos SET
                nombre_evento = ?,
                nombre_novio_1 = ?,
                nombre_novio_2 = ?,
                nombre_responsable = ?,
                numero_responsable = ?,
                correo_responsable = ?,
                ubicacion = ?,
                direccion_completa = ?,
                fecha_evento = ?,
                hora_inicio_montaje = ?,
                hora_fin_montaje = ?,
                hora_inicio_evento = ?,
                hora_fin_evento = ?,
                numero_invitados = ?,
                presupuesto_total = ?,
                anticipo_pagado = ?,
                saldo_pendiente = ?,
                notas_internas = ?,
                notas_cliente = ?,
                imagen_principal = ?,
                estatus = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nombre_evento'],
            $_POST['nombre_novio_1'],
            $_POST['nombre_novio_2'],
            $_POST['nombre_responsable'],
            $_POST['numero_responsable'],
            $_POST['correo_responsable'],
            $_POST['ubicacion'],
            $_POST['direccion_completa'],
            $_POST['fecha_evento'],
            $_POST['hora_inicio_montaje'],
            $_POST['hora_fin_montaje'],
            $_POST['hora_inicio_evento'],
            $_POST['hora_fin_evento'],
            $_POST['numero_invitados'],
            $_POST['presupuesto_total'],
            $_POST['anticipo_pagado'],
            $_POST['saldo_pendiente'],
            $_POST['notas_internas'],
            $_POST['notas_cliente'],
            $imagen_principal,
            $_POST['estatus'],
            $evento_id
        ]);
        
        $pdo->commit();
        $success = true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error al actualizar el evento: ' . $e->getMessage();
    }
}

// Consultar evento de la base de datos
try {
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->execute([$evento_id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evento) {
        header('Location: ?page=eventos');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error obteniendo evento: " . $e->getMessage());
    header('Location: ?page=eventos');
    exit;
}
?>

<div class="container-fluid p-4">
    
    <?php if (isset($success) && $success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            customAlert('Evento actualizado exitosamente', 'success', 'Guardado Exitoso');
            setTimeout(() => {
                window.location.href = '?page=evento_detalle&id=<?php echo $evento_id; ?>';
            }, 1500);
        });
    </script>
    <?php endif; ?>
    
    <!-- Botón de regresar -->
    <div class="mb-3">
        <a href="?page=evento_detalle&id=<?php echo $evento_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Detalles
        </a>
    </div>

    <!-- Título -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="mb-0">
                <i class="bi bi-pencil-square me-2"></i>Editar Evento
            </h2>
            <p class="text-muted mb-0">ID del Evento: #<?php echo str_pad($evento['id'], 4, '0', STR_PAD_LEFT); ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo esc($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Formulario de edición -->
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="imagen_actual" value="<?php echo esc($evento['imagen_principal']); ?>">
        
        <!-- Imagen del Evento -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-image me-2"></i>Imagen del Evento
                </h5>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Imagen Actual</label>
                            <div class="border rounded p-2">
                                <img src="<?php echo esc($evento['imagen_principal']); ?>" 
                                     class="img-fluid rounded" 
                                     alt="Imagen actual"
                                     id="imagenPreview"
                                     style="max-height: 300px; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="imagen_evento" class="form-label">Cambiar Imagen</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="imagen_evento" 
                                   name="imagen_evento"
                                   accept="image/jpeg,image/jpg,image/png,image/webp"
                                   onchange="previewImage(event)">
                            <small class="text-muted">Formatos permitidos: JPG, PNG, WEBP. Tamaño máximo: 5MB</small>
                        </div>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarImagenCover()">
                                <i class="bi bi-trash me-1"></i>Eliminar Imagen y Usar Cover por Defecto
                            </button>
                            <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">
                            <small class="d-block text-muted mt-2">Al eliminar, se establecerá la imagen predeterminada del sistema</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Básica -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-info-circle me-2"></i>Información Básica
                </h5>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="nombre_evento" class="form-label">Nombre del Evento *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre_evento" 
                               name="nombre_evento"
                               value="<?php echo esc($evento['nombre_evento']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="nombre_novio_1" class="form-label">Novio/Cliente 1 *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre_novio_1" 
                               name="nombre_novio_1"
                               value="<?php echo esc($evento['nombre_novio_1']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="nombre_novio_2" class="form-label">Novio/Cliente 2 *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre_novio_2" 
                               name="nombre_novio_2"
                               value="<?php echo esc($evento['nombre_novio_2']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="estatus" class="form-label">Estado del Evento *</label>
                        <select class="form-select" id="estatus" name="estatus" required>
                            <option value="Pendiente" <?php echo $evento['estatus'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Confirmado" <?php echo $evento['estatus'] === 'Confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="En Proceso" <?php echo $evento['estatus'] === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="Finalizado" <?php echo $evento['estatus'] === 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                            <option value="Cancelado" <?php echo $evento['estatus'] === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="numero_invitados" class="form-label">Número de Invitados</label>
                        <input type="number" 
                               class="form-control" 
                               id="numero_invitados" 
                               name="numero_invitados"
                               value="<?php echo esc($evento['numero_invitados']); ?>" 
                               min="1">
                    </div>
                </div>
            </div>
        </div>

        <!-- Responsable del Evento -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-person me-2"></i>Responsable del Evento
                </h5>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="nombre_responsable" class="form-label">Nombre Completo *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre_responsable" 
                               name="nombre_responsable"
                               value="<?php echo esc($evento['nombre_responsable']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="numero_responsable" class="form-label">Teléfono *</label>
                        <input type="tel" 
                               class="form-control" 
                               id="numero_responsable" 
                               name="numero_responsable"
                               value="<?php echo esc($evento['numero_responsable']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="correo_responsable" class="form-label">Correo Electrónico *</label>
                        <input type="email" 
                               class="form-control" 
                               id="correo_responsable" 
                               name="correo_responsable"
                               value="<?php echo esc($evento['correo_responsable']); ?>" 
                               required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ubicación -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-geo-alt me-2"></i>Ubicación del Evento
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ubicacion" class="form-label">Nombre del Lugar *</label>
                        <input type="text" 
                               class="form-control" 
                               id="ubicacion" 
                               name="ubicacion"
                               value="<?php echo esc($evento['ubicacion']); ?>" 
                               placeholder="Ej: Hacienda Colonial San José"
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="direccion_completa" class="form-label">Dirección Completa *</label>
                        <input type="text" 
                               class="form-control" 
                               id="direccion_completa" 
                               name="direccion_completa"
                               value="<?php echo esc($evento['direccion_completa']); ?>" 
                               placeholder="Ej: Calle Principal #123, Guadalajara, Jalisco, México"
                               required>
                        <small class="text-muted">El mapa se generará automáticamente con esta dirección</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fecha y Horarios -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-calendar-event me-2"></i>Fecha y Horarios
                </h5>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="fecha_evento" class="form-label">Fecha del Evento *</label>
                        <input type="date" 
                               class="form-control" 
                               id="fecha_evento" 
                               name="fecha_evento"
                               value="<?php echo esc($evento['fecha_evento']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hora_inicio_montaje" class="form-label">Hora Inicio Montaje</label>
                        <input type="time" 
                               class="form-control" 
                               id="hora_inicio_montaje" 
                               name="hora_inicio_montaje"
                               value="<?php echo esc($evento['hora_inicio_montaje']); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hora_fin_montaje" class="form-label">Hora Fin Montaje</label>
                        <input type="time" 
                               class="form-control" 
                               id="hora_fin_montaje" 
                               name="hora_fin_montaje"
                               value="<?php echo esc($evento['hora_fin_montaje']); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hora_inicio_evento" class="form-label">Hora Inicio Evento *</label>
                        <input type="time" 
                               class="form-control" 
                               id="hora_inicio_evento" 
                               name="hora_inicio_evento"
                               value="<?php echo esc($evento['hora_inicio_evento']); ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hora_fin_evento" class="form-label">Hora Fin Evento *</label>
                        <input type="time" 
                               class="form-control" 
                               id="hora_fin_evento" 
                               name="hora_fin_evento"
                               value="<?php echo esc($evento['hora_fin_evento']); ?>" 
                               required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Presupuesto y Pagos -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-currency-dollar me-2"></i>Presupuesto y Pagos
                </h5>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="presupuesto_total" class="form-label">Presupuesto Total</label>
                        <input type="number" 
                               class="form-control" 
                               id="presupuesto_total" 
                               name="presupuesto_total"
                               value="<?php echo esc($evento['presupuesto_total']); ?>" 
                               step="0.01"
                               min="0">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="anticipo_pagado" class="form-label">Anticipo Pagado</label>
                        <input type="number" 
                               class="form-control" 
                               id="anticipo_pagado" 
                               name="anticipo_pagado"
                               value="<?php echo esc($evento['anticipo_pagado']); ?>" 
                               step="0.01"
                               min="0">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="saldo_pendiente" class="form-label">Saldo Pendiente</label>
                        <input type="number" 
                               class="form-control" 
                               id="saldo_pendiente" 
                               name="saldo_pendiente"
                               value="<?php echo esc($evento['saldo_pendiente']); ?>" 
                               step="0.01"
                               min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notas -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-journal-text me-2"></i>Notas
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="notas_internas" class="form-label">Notas Internas</label>
                        <textarea class="form-control" 
                                  id="notas_internas" 
                                  name="notas_internas" 
                                  rows="5"
                                  placeholder="Notas visibles solo para el equipo organizador..."><?php echo esc($evento['notas_internas']); ?></textarea>
                        <small class="text-muted">Solo visible para el equipo</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="notas_cliente" class="form-label">Notas para el Cliente</label>
                        <textarea class="form-control" 
                                  id="notas_cliente" 
                                  name="notas_cliente" 
                                  rows="5"
                                  placeholder="Notas visibles para el cliente..."><?php echo esc($evento['notas_cliente']); ?></textarea>
                        <small class="text-muted">Visible para el cliente</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="?page=evento_detalle&id=<?php echo $evento_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </a>
                    <button type="submit" name="actualizar_evento" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<script>
// Preview de imagen antes de subir
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagenPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}

// Eliminar imagen y usar cover por defecto
function eliminarImagenCover() {
    customConfirm(
        '¿Estás seguro de que deseas eliminar la imagen actual y usar la imagen por defecto?<br><small class="text-muted">Se establecerá la imagen predeterminada del sistema.</small>',
        () => {
            // Marcar que se debe eliminar la imagen
            document.getElementById('eliminar_imagen').value = '1';
            
            // Mostrar preview de la imagen por defecto
            document.getElementById('imagenPreview').src = 'imagenes/cover (1).jpg';
            
            // Limpiar el input de archivo
            document.getElementById('imagen_evento').value = '';
            
            // Agregar borde rojo al preview para indicar cambio pendiente
            document.getElementById('imagenPreview').style.border = '3px solid #dc3545';
            
            // Mostrar mensaje de confirmación visual
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show mt-2';
            alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Cambio pendiente:</strong> La imagen se eliminará y se establecerá la imagen por defecto al guardar.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const imageContainer = document.getElementById('imagenPreview').closest('.col-md-4');
            if (imageContainer && !imageContainer.querySelector('.alert')) {
                imageContainer.appendChild(alertDiv);
            }
        },
        'Eliminar Imagen'
    );
}

// Cuando se selecciona un nuevo archivo, cancelar la eliminación
document.getElementById('imagen_evento')?.addEventListener('change', function() {
    if (this.files && this.files.length > 0) {
        document.getElementById('eliminar_imagen').value = '0';
        document.getElementById('imagenPreview').style.border = '';
        
        // Remover alerta si existe
        const alert = document.querySelector('.col-md-4 .alert');
        if (alert) {
            alert.remove();
        }
    }
});

// Calcular saldo pendiente automáticamente
document.getElementById('presupuesto_total')?.addEventListener('input', calcularSaldo);
document.getElementById('anticipo_pagado')?.addEventListener('input', calcularSaldo);

function calcularSaldo() {
    const total = parseFloat(document.getElementById('presupuesto_total').value) || 0;
    const anticipo = parseFloat(document.getElementById('anticipo_pagado').value) || 0;
    const saldo = total - anticipo;
    document.getElementById('saldo_pendiente').value = saldo.toFixed(2);
}
</script>
