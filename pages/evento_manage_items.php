<?php
require_once __DIR__ . '/../config/conexion.php';

// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$evento_id = (int)($_POST['evento_id'] ?? 0);

if ($evento_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de evento inválido']);
    exit;
}

try {
    switch ($action) {
        // ============ INVENTARIO ============
        case 'get_inventario_disponible':
            // Obtener todo el inventario con existencia > 0
            $stmt = $pdo->query("
                SELECT codigo, nombre, categoria, existencia, material, color, medida 
                FROM inventario 
                WHERE existencia > 0 
                ORDER BY categoria, nombre
            ");
            $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $inventario]);
            break;

        case 'add_inventario':
            $codigo = $_POST['codigo'] ?? '';
            $cantidad = (int)($_POST['cantidad'] ?? 1);
            $notas = $_POST['notas'] ?? '';

            if (empty($codigo)) {
                echo json_encode(['success' => false, 'message' => 'Código de inventario requerido']);
                exit;
            }

            // Verificar que existe el inventario y hay suficiente stock
            $stmt = $pdo->prepare("SELECT existencia FROM inventario WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item de inventario no encontrado']);
                exit;
            }

            if ($item['existencia'] < $cantidad) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente. Disponible: ' . $item['existencia']]);
                exit;
            }

            // Insertar o actualizar
            $stmt = $pdo->prepare("
                INSERT INTO evento_inventario (evento_id, inventario_codigo, cantidad, notas)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE cantidad = cantidad + ?, notas = ?
            ");
            $stmt->execute([$evento_id, $codigo, $cantidad, $notas, $cantidad, $notas]);

            echo json_encode(['success' => true, 'message' => 'Inventario agregado correctamente']);
            break;

        case 'update_inventario':
            $codigo = $_POST['codigo'] ?? '';
            $cantidad = (int)($_POST['cantidad'] ?? 1);
            $notas = $_POST['notas'] ?? '';

            if (empty($codigo)) {
                echo json_encode(['success' => false, 'message' => 'Código de inventario requerido']);
                exit;
            }

            // Verificar stock disponible
            $stmt = $pdo->prepare("SELECT existencia FROM inventario WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item && $item['existencia'] < $cantidad) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente. Disponible: ' . $item['existencia']]);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE evento_inventario 
                SET cantidad = ?, notas = ?
                WHERE evento_id = ? AND inventario_codigo = ?
            ");
            $stmt->execute([$cantidad, $notas, $evento_id, $codigo]);

            echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
            break;

        case 'delete_inventario':
            $codigo = $_POST['codigo'] ?? '';

            if (empty($codigo)) {
                echo json_encode(['success' => false, 'message' => 'Código de inventario requerido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM evento_inventario WHERE evento_id = ? AND inventario_codigo = ?");
            $stmt->execute([$evento_id, $codigo]);

            echo json_encode(['success' => true, 'message' => 'Inventario eliminado correctamente']);
            break;

        // ============ SERVICIOS ============
        case 'get_servicios_disponibles':
            // Obtener solo los servicios disponibles (excluir baja temporal y definitiva)
            $stmt = $pdo->query("
                SELECT id, codigo, nombre, categoria, proveedor_default, telefono_default, 
                       email_default, costo_base, descripcion 
                FROM servicios 
                WHERE estado = 'disponible'
                ORDER BY categoria, nombre
            ");
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $servicios]);
            break;

        case 'add_servicio':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);
            $proveedor = $_POST['proveedor'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $email = $_POST['email'] ?? '';
            $costo = $_POST['costo'] ?? null;
            $horario = $_POST['horario'] ?? '';
            $notas = $_POST['notas'] ?? '';
            $confirmado = (int)($_POST['confirmado'] ?? 0);

            if ($servicio_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Servicio no válido']);
                exit;
            }

            if (empty($proveedor)) {
                echo json_encode(['success' => false, 'message' => 'Proveedor requerido']);
                exit;
            }

            // Verificar que el servicio esté disponible (no en baja temporal o definitiva)
            $stmt = $pdo->prepare("SELECT estado FROM servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$servicio) {
                echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
                exit;
            }
            
            if ($servicio['estado'] !== 'disponible') {
                $mensaje_estado = $servicio['estado'] === 'baja_temporal' 
                    ? 'Este servicio está en baja temporal y no puede ser asignado'
                    : 'Este servicio está dado de baja y no puede ser asignado';
                echo json_encode(['success' => false, 'message' => $mensaje_estado]);
                exit;
            }

            // Verificar que no esté ya asignado
            $stmt = $pdo->prepare("SELECT id FROM evento_servicio WHERE evento_id = ? AND servicio_id = ?");
            $stmt->execute([$evento_id, $servicio_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este servicio ya está asignado al evento']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO evento_servicio 
                (evento_id, servicio_id, proveedor, telefono_proveedor, email_proveedor, 
                 costo_acordado, horario_servicio, notas_especiales, confirmado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $evento_id, $servicio_id, $proveedor, $telefono, $email,
                $costo, $horario, $notas, $confirmado
            ]);

            echo json_encode(['success' => true, 'message' => 'Servicio agregado correctamente']);
            break;

        case 'update_servicio':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);
            $proveedor = $_POST['proveedor'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $email = $_POST['email'] ?? '';
            $costo = $_POST['costo'] ?? null;
            $horario = $_POST['horario'] ?? '';
            $notas = $_POST['notas'] ?? '';
            $confirmado = (int)($_POST['confirmado'] ?? 0);

            if ($servicio_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Servicio no válido']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE evento_servicio 
                SET proveedor = ?, telefono_proveedor = ?, email_proveedor = ?,
                    costo_acordado = ?, horario_servicio = ?, notas_especiales = ?, confirmado = ?
                WHERE evento_id = ? AND servicio_id = ?
            ");
            $stmt->execute([
                $proveedor, $telefono, $email, $costo, $horario, $notas, $confirmado,
                $evento_id, $servicio_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Servicio actualizado correctamente']);
            break;

        case 'delete_servicio':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);

            if ($servicio_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Servicio no válido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM evento_servicio WHERE evento_id = ? AND servicio_id = ?");
            $stmt->execute([$evento_id, $servicio_id]);

            echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
            break;

        case 'toggle_servicio_confirmado':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);

            if ($servicio_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Servicio no válido']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE evento_servicio 
                SET confirmado = NOT confirmado
                WHERE evento_id = ? AND servicio_id = ?
            ");
            $stmt->execute([$evento_id, $servicio_id]);

            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error en evento_manage_items: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}

exit; // Importante: evitar que index.php continúe renderizando HTML
