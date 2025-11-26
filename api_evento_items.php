<?php
session_start();
require_once __DIR__ . '/config/conexion.php';

// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$evento_id = (int)($_POST['evento_id'] ?? 0);

if ($evento_id <= 0 && $action !== 'get_inventario_disponible' && $action !== 'get_servicios_disponibles') {
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
                echo json_encode(['success' => false, 'message' => 'Item no encontrado']);
                exit;
            }

            if ($item['existencia'] < $cantidad) {
                echo json_encode(['success' => false, 'message' => 'No hay suficiente stock disponible']);
                exit;
            }

            // Verificar si ya existe esta asignación
            $stmt = $pdo->prepare("SELECT id FROM evento_inventario WHERE evento_id = ? AND inventario_codigo = ?");
            $stmt->execute([$evento_id, $codigo]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este artículo ya está asignado al evento']);
                exit;
            }

            // Iniciar transacción para asegurar consistencia
            $pdo->beginTransaction();
            
            try {
                // Insertar en evento_inventario
                $stmt = $pdo->prepare("
                    INSERT INTO evento_inventario (evento_id, inventario_codigo, cantidad, notas, fecha_asignacion)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$evento_id, $codigo, $cantidad, $notas]);
                
                // Reducir existencia en inventario
                $stmt = $pdo->prepare("
                    UPDATE inventario 
                    SET existencia = existencia - ? 
                    WHERE codigo = ?
                ");
                $stmt->execute([$cantidad, $codigo]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Inventario agregado correctamente']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'update_inventario':
            $codigo = $_POST['codigo'] ?? '';
            $cantidad = (int)($_POST['cantidad'] ?? 1);
            $notas = $_POST['notas'] ?? '';

            if (empty($codigo)) {
                echo json_encode(['success' => false, 'message' => 'Código de inventario requerido']);
                exit;
            }

            // Obtener cantidad actual asignada
            $stmt = $pdo->prepare("SELECT cantidad FROM evento_inventario WHERE evento_id = ? AND inventario_codigo = ?");
            $stmt->execute([$evento_id, $codigo]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$actual) {
                echo json_encode(['success' => false, 'message' => 'Item no encontrado en el evento']);
                exit;
            }
            
            $cantidad_anterior = $actual['cantidad'];
            $diferencia = $cantidad - $cantidad_anterior;

            // Verificar stock disponible si se está aumentando la cantidad
            if ($diferencia > 0) {
                $stmt = $pdo->prepare("SELECT existencia FROM inventario WHERE codigo = ?");
                $stmt->execute([$codigo]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item || $item['existencia'] < $diferencia) {
                    echo json_encode(['success' => false, 'message' => 'No hay suficiente stock disponible']);
                    exit;
                }
            }

            // Iniciar transacción
            $pdo->beginTransaction();
            
            try {
                // Actualizar evento_inventario
                $stmt = $pdo->prepare("
                    UPDATE evento_inventario 
                    SET cantidad = ?, notas = ?
                    WHERE evento_id = ? AND inventario_codigo = ?
                ");
                $stmt->execute([$cantidad, $notas, $evento_id, $codigo]);
                
                // Ajustar existencia en inventario
                // Si diferencia es positiva: se necesita más (restar del stock)
                // Si diferencia es negativa: se devuelve (sumar al stock)
                $stmt = $pdo->prepare("
                    UPDATE inventario 
                    SET existencia = existencia - ? 
                    WHERE codigo = ?
                ");
                $stmt->execute([$diferencia, $codigo]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'delete_inventario':
            $codigo = $_POST['codigo'] ?? '';

            if (empty($codigo)) {
                echo json_encode(['success' => false, 'message' => 'Código de inventario requerido']);
                exit;
            }

            // Obtener cantidad asignada antes de eliminar (para devolverla al stock)
            $stmt = $pdo->prepare("SELECT cantidad FROM evento_inventario WHERE evento_id = ? AND inventario_codigo = ?");
            $stmt->execute([$evento_id, $codigo]);
            $asignado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asignado) {
                echo json_encode(['success' => false, 'message' => 'Item no encontrado en el evento']);
                exit;
            }

            // Iniciar transacción
            $pdo->beginTransaction();
            
            try {
                // Eliminar de evento_inventario
                $stmt = $pdo->prepare("DELETE FROM evento_inventario WHERE evento_id = ? AND inventario_codigo = ?");
                $stmt->execute([$evento_id, $codigo]);
                
                // Devolver existencia al inventario
                $stmt = $pdo->prepare("
                    UPDATE inventario 
                    SET existencia = existencia + ? 
                    WHERE codigo = ?
                ");
                $stmt->execute([$asignado['cantidad'], $codigo]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Inventario eliminado y stock devuelto correctamente']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
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

            if ($servicio_id <= 0 || empty($proveedor)) {
                echo json_encode(['success' => false, 'message' => 'Servicio y proveedor son requeridos']);
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

            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM evento_servicio WHERE evento_id = ? AND servicio_id = ?");
            $stmt->execute([$evento_id, $servicio_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este servicio ya está asignado al evento']);
                exit;
            }

            // Insertar
            $stmt = $pdo->prepare("
                INSERT INTO evento_servicio 
                (evento_id, servicio_id, proveedor, telefono_proveedor, email_proveedor, 
                 costo_acordado, horario_servicio, notas, confirmado, fecha_contratacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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

            if ($servicio_id <= 0 || empty($proveedor)) {
                echo json_encode(['success' => false, 'message' => 'Servicio y proveedor son requeridos']);
                exit;
            }

            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE evento_servicio 
                SET proveedor = ?, telefono_proveedor = ?, email_proveedor = ?, 
                    costo_acordado = ?, horario_servicio = ?, notas = ?, confirmado = ?
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
                echo json_encode(['success' => false, 'message' => 'ID de servicio inválido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM evento_servicio WHERE evento_id = ? AND servicio_id = ?");
            $stmt->execute([$evento_id, $servicio_id]);

            echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
            break;

        case 'toggle_servicio_confirmado':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);
            $confirmado = (int)($_POST['confirmado'] ?? 0);

            if ($servicio_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de servicio inválido']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE evento_servicio 
                SET confirmado = ?
                WHERE evento_id = ? AND servicio_id = ?
            ");
            $stmt->execute([$confirmado, $evento_id, $servicio_id]);

            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            break;

        case 'finalizar_evento':
            $password = $_POST['password'] ?? '';
            $notas_cierre = $_POST['notas_cierre'] ?? '';
            
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Contraseña requerida']);
                exit;
            }
            
            // Verificar contraseña del usuario logueado
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT contrasena FROM usuario WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || $user['contrasena'] !== $password) {
                echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
                exit;
            }
            
            // Iniciar transacción para todo el proceso
            $pdo->beginTransaction();
            
            try {
                // 1. Devolver TODO el inventario asignado a bodega
                $stmt = $pdo->prepare("
                    SELECT inventario_codigo, cantidad 
                    FROM evento_inventario 
                    WHERE evento_id = ?
                ");
                $stmt->execute([$evento_id]);
                $items_devolver = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($items_devolver as $item) {
                    $stmt = $pdo->prepare("
                        UPDATE inventario 
                        SET existencia = existencia + ? 
                        WHERE codigo = ?
                    ");
                    $stmt->execute([$item['cantidad'], $item['inventario_codigo']]);
                }
                
                // 2. Eliminar asignaciones de inventario
                $stmt = $pdo->prepare("DELETE FROM evento_inventario WHERE evento_id = ?");
                $stmt->execute([$evento_id]);
                
                // 3. Actualizar estado del evento a Finalizado
                $notas_finales = "EVENTO FINALIZADO - " . date('Y-m-d H:i:s');
                if (!empty($notas_cierre)) {
                    $notas_finales .= "\n\nNotas de cierre:\n" . $notas_cierre;
                }
                
                $stmt = $pdo->prepare("
                    UPDATE eventos 
                    SET estatus = 'Finalizado',
                        notas_internas = CONCAT(IFNULL(notas_internas, ''), '\n\n', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$notas_finales, $evento_id]);
                
                // 4. Registrar auditoría (opcional - si quieres llevar un log)
                // Puedes crear una tabla de auditoría más adelante
                
                $pdo->commit();
                
                $total_items = count($items_devolver);
                echo json_encode([
                    'success' => true, 
                    'message' => "Evento finalizado exitosamente.\n✓ {$total_items} items devueltos a bodega\n✓ Estado actualizado\n✓ Proceso completado"
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // ============ CRONOGRAMA ============
        case 'add_cronograma':
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? '';
            $actividad = $_POST['actividad'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $responsable = $_POST['responsable'] ?? '';

            if (empty($hora_inicio) || empty($hora_fin) || empty($actividad)) {
                echo json_encode(['success' => false, 'message' => 'Campos obligatorios faltantes']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO evento_cronograma 
                (evento_id, hora_inicio, hora_fin, actividad, descripcion, responsable, completado) 
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$evento_id, $hora_inicio, $hora_fin, $actividad, $descripcion, $responsable]);

            echo json_encode(['success' => true, 'message' => 'Actividad agregada al cronograma']);
            break;

        case 'update_cronograma':
            $id = (int)($_POST['id'] ?? 0);
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? '';
            $actividad = $_POST['actividad'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $responsable = $_POST['responsable'] ?? '';
            $completado = (int)($_POST['completado'] ?? 0);

            if ($id <= 0 || empty($hora_inicio) || empty($hora_fin) || empty($actividad)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE evento_cronograma 
                SET hora_inicio = ?, hora_fin = ?, actividad = ?, descripcion = ?, 
                    responsable = ?, completado = ?
                WHERE id = ? AND evento_id = ?
            ");
            $stmt->execute([$hora_inicio, $hora_fin, $actividad, $descripcion, $responsable, $completado, $id, $evento_id]);

            echo json_encode(['success' => true, 'message' => 'Actividad actualizada']);
            break;

        case 'delete_cronograma':
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM evento_cronograma WHERE id = ? AND evento_id = ?");
            $stmt->execute([$id, $evento_id]);

            echo json_encode(['success' => true, 'message' => 'Actividad eliminada del cronograma']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error en api_evento_items: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}

exit;
