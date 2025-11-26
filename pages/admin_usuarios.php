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

// Manejo de eliminación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $id = (int)$_POST['id'];
    
    if ($id > 0 && $id != $_SESSION['user_id']) { // No permitir eliminar su propia cuenta
        try {
            $stmt = $pdo->prepare("DELETE FROM usuario WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Usuario eliminado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al eliminar: " . $e->getMessage();
        }
    } else {
        $error = "No puedes eliminar tu propia cuenta.";
    }
}

// Manejo de edición de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    $id = (int)$_POST['id'];
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    
    if ($id > 0 && !empty($nombre_completo) && !empty($correo)) {
        try {
            $stmt = $pdo->prepare("UPDATE usuario SET nombre_completo = ?, correo = ?, celular = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$nombre_completo, $correo, $celular, $tipo, $id]);
            $success = "Usuario actualizado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Obtener todos los usuarios
try {
    $stmt = $pdo->query("SELECT id, nombre_completo, correo, celular, tipo FROM usuario ORDER BY tipo DESC, nombre_completo ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
    $error = "Error al leer usuarios: " . $e->getMessage();
}
?>

<div class="container-fluid py-4">
    

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
                <i class="bi bi-people-fill"></i>Gestión de Usuarios
            </h2>
        </div>
        <div class="card-body p-4">
            <?php if (count($usuarios) === 0): ?>
                <p class="text-center text-muted">No hay usuarios registrados.</p>
            <?php else: ?>
                <!-- Barra de búsqueda y botón crear -->
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                    <div class="usuarios-search-container flex-grow-1">
                        <input type="text" id="searchUsuario" class="form-control usuarios-search-input" placeholder="Buscar por ID, nombre, correo o celular...">
                        <i class="bi bi-search usuarios-search-icon"></i>
                    </div>
                    <a href="?page=admin_crear_usuario" 
                       class="btn" 
                       style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white; border: none; border-radius: 10px; padding: 12px 24px; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); font-weight: 600; white-space: nowrap;">
                        <i class="bi bi-person-plus-fill me-2"></i>Crear Usuario
                    </a>
                </div>

                <div style="overflow-x: visible;">
                    <table class="table usuarios-table-simple" id="usuariosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Celular</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td class="usuarios-id-simple">#<?php echo esc($usuario['id']); ?></td>
                                    <td class="usuarios-nombre-simple"><?php echo esc($usuario['nombre_completo']); ?></td>
                                    <td class="usuarios-correo-simple"><?php echo esc($usuario['correo']); ?></td>
                                    <td class="usuarios-celular-simple"><?php echo esc($usuario['celular']); ?></td>
                                    <td>
                                        <?php if ($usuario['tipo'] === 'administrador'): ?>
                                            <span class="usuarios-badge-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="usuarios-badge-cliente">Organizador</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm usuarios-btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?php echo $usuario['id']; ?>"
                                            title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm usuarios-btn-delete" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal<?php echo $usuario['id']; ?>"
                                            title="Eliminar">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Editar Usuario -->
                                <div class="modal fade" id="editModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content usuarios-modal">
                                            <div class="usuarios-modal-header">
                                                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Usuario</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body usuarios-modal-body">
                                                    <input type="hidden" name="accion" value="editar_usuario">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Nombre Completo</label>
                                                        <input type="text" class="form-control usuarios-input" name="nombre_completo" 
                                                               value="<?php echo esc($usuario['nombre_completo']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Correo</label>
                                                        <input type="email" class="form-control usuarios-input" name="correo" 
                                                               value="<?php echo esc($usuario['correo']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Celular</label>
                                                        <input type="text" class="form-control usuarios-input" name="celular" 
                                                               value="<?php echo esc($usuario['celular']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Tipo de Usuario</label>
                                                        <select class="form-select usuarios-input" name="tipo" required>
                                                            <option value="cliente" <?php echo $usuario['tipo'] === 'cliente' ? 'selected' : ''; ?>>Organizador</option>
                                                            <option value="administrador" <?php echo $usuario['tipo'] === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label usuarios-label">Nueva Contraseña</label>
                                                        <input type="password" class="form-control usuarios-input" name="nueva_contrasena"
                                                               placeholder="Dejar en blanco para no cambiar">
                                                        <small class="text-muted">Solo si deseas cambiar la contraseña</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer usuarios-modal-footer">
                                                    <button type="button" class="btn btn-secondary usuarios-btn-cancel" data-bs-dismiss="modal">
                                                        Cancelar
                                                    </button>
                                                    <button type="submit" class="btn usuarios-btn-submit">
                                                        Guardar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Eliminar Usuario -->
                                <div class="modal fade" id="deleteModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-sm">
                                        <div class="modal-content usuarios-modal">
                                            <div class="usuarios-modal-header-danger">
                                                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Eliminar</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body usuarios-modal-body text-center">
                                                    <input type="hidden" name="accion" value="eliminar_usuario">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    <i class="bi bi-person-x-fill usuarios-delete-icon"></i>
                                                    <p class="usuarios-delete-text">
                                                        ¿Eliminar a <strong><?php echo esc($usuario['nombre_completo']); ?></strong>?
                                                    </p>
                                                    <small class="text-muted">Esta acción no se puede deshacer</small>
                                                </div>
                                                <div class="modal-footer usuarios-modal-footer">
                                                    <button type="button" class="btn btn-secondary usuarios-btn-cancel" data-bs-dismiss="modal">
                                                        Cancelar
                                                    </button>
                                                    <button type="submit" class="btn usuarios-btn-delete-confirm">
                                                        Eliminar
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
// Función de búsqueda para usuarios
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchUsuario');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('usuariosTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                // Buscar en ID (columna 0), Nombre (1), Correo (2), Celular (3)
                for (let j = 0; j < 4; j++) {
                    if (cells[j]) {
                        const text = cells[j].textContent || cells[j].innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        });
    }
});
</script>
