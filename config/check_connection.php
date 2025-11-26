<?php
// Archivo para verificar el estado de la conexión a la base de datos
// Devuelve JSON con el estado

header('Content-Type: application/json');

$servidor_ip = "localhost";
$usuario_bd  = "root";
$password_bd = "";
$puerto      = 3306;

$dsn = "mysql:host=$servidor_ip;port=$puerto;dbname=$nombre_bd;charset=utf8mb4";

$response = [
    'success' => false,
    'message' => '',
    'details' => [
        'servidor' => $servidor_ip,
        'puerto' => $puerto,
        'base_datos' => $nombre_bd,
        'usuario' => $usuario_bd
    ],
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Intentar conectar
    $pdo = new PDO($dsn, $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar que realmente funciona con una consulta simple
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db_actual");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['message'] = '✅ Conexión exitosa a la base de datos';
    $response['details']['mysql_version'] = $info['version'];
    $response['details']['database_actual'] = $info['db_actual'];
    
    // Obtener número de tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response['details']['total_tablas'] = count($tablas);
    $response['details']['tablas'] = $tablas;
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = '❌ Error de conexión';
    $response['details']['error'] = $e->getMessage();
    $response['details']['error_code'] = $e->getCode();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
