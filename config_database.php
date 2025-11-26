<?php
/**
 * Script de Configuración Automática
 * 
 * Este script te ayuda a cambiar rápidamente entre la configuración
 * de servidor remoto y servidor local
 */

$configFile = 'config/conexion.php';
$checkFile = 'config/check_connection.php';

// Configuraciones predefinidas
$configs = [
    'local' => [
        'servidor_ip' => 'localhost',
        'nombre_bd' => 'makadb',
        'usuario_bd' => 'root',
        'password_bd' => '',
        'puerto' => 3306,
        'descripcion' => 'Servidor Local (XAMPP)'
    ],
    'remoto' => [
        'servidor_ip' => '10.70.110.58',
        'nombre_bd' => 'makadb',
        'usuario_bd' => 'amigo_remoto2',
        'password_bd' => '1234',
        'puerto' => 3307,
        'descripcion' => 'Servidor Remoto'
    ]
];

// Si se recibe POST, cambiar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'apply' && isset($_POST['config_type'])) {
        $configType = $_POST['config_type'];
        
        if (!isset($configs[$configType])) {
            die(json_encode(['success' => false, 'error' => 'Configuración no válida']));
        }
        
        $config = $configs[$configType];
        
        try {
            // Actualizar config/conexion.php
            updateConfigFile($configFile, $config);
            
            // Actualizar config/check_connection.php
            updateConfigFile($checkFile, $config);
            
            // Probar conexión
            $testResult = testConnection($config);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'config' => $config,
                'connection' => $testResult
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    if ($action === 'test') {
        $config = [
            'servidor_ip' => $_POST['servidor_ip'],
            'nombre_bd' => $_POST['nombre_bd'],
            'usuario_bd' => $_POST['usuario_bd'],
            'password_bd' => $_POST['password_bd'],
            'puerto' => (int)$_POST['puerto']
        ];
        
        $result = testConnection($config);
        echo json_encode($result);
        exit;
    }
}

// Función para actualizar archivo de configuración
function updateConfigFile($filename, $config) {
    if (!file_exists($filename)) {
        throw new Exception("El archivo $filename no existe");
    }
    
    $content = file_get_contents($filename);
    
    // Reemplazar valores
    $content = preg_replace(
        '/\$servidor_ip\s*=\s*["\'].*?["\'];/',
        '$servidor_ip = "' . $config['servidor_ip'] . '";',
        $content
    );
    
    $content = preg_replace(
        '/\$nombre_bd\s*=\s*["\'].*?["\'];/',
        '$nombre_bd   = "' . $config['nombre_bd'] . '";',
        $content
    );
    
    $content = preg_replace(
        '/\$usuario_bd\s*=\s*["\'].*?["\'];/',
        '$usuario_bd  = "' . $config['usuario_bd'] . '";',
        $content
    );
    
    $content = preg_replace(
        '/\$password_bd\s*=\s*["\'].*?["\'];/',
        '$password_bd = "' . $config['password_bd'] . '";',
        $content
    );
    
    $content = preg_replace(
        '/\$puerto\s*=\s*\d+;/',
        '$puerto      = ' . $config['puerto'] . ';',
        $content
    );
    
    if (file_put_contents($filename, $content) === false) {
        throw new Exception("No se pudo escribir en $filename");
    }
}

// Función para probar conexión
function testConnection($config) {
    $dsn = "mysql:host={$config['servidor_ip']};port={$config['puerto']};dbname={$config['nombre_bd']};charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $config['usuario_bd'], $config['password_bd']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Obtener versión
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
        
        // Obtener número de tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tablesCount = $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => 'Conexión exitosa',
            'version' => $version,
            'tables' => $tablesCount
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error de conexión',
            'error' => $e->getMessage()
        ];
    }
}

// Detectar configuración actual
function getCurrentConfig() {
    global $configFile;
    
    if (!file_exists($configFile)) {
        return null;
    }
    
    $content = file_get_contents($configFile);
    
    preg_match('/\$servidor_ip\s*=\s*["\'](.+?)["\'];/', $content, $matches);
    $servidor_ip = $matches[1] ?? '';
    
    if ($servidor_ip === 'localhost' || $servidor_ip === '127.0.0.1') {
        return 'local';
    } elseif (strpos($servidor_ip, '10.70.110.58') !== false) {
        return 'remoto';
    }
    
    return 'custom';
}

$currentConfig = getCurrentConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurador de Base de Datos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .current-config {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .current-config h3 {
            color: #1976d2;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .config-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .config-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .config-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .config-card.active {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .config-card h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }
        
        .config-card .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .config-card.local .icon { color: #4caf50; }
        .config-card.remoto .icon { color: #2196f3; }
        
        .config-details {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
        }
        
        .config-details div {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .config-details div:last-child {
            border-bottom: none;
        }
        
        .config-details strong {
            color: #333;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
            margin-top: 15px;
        }
        
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn.success {
            background: #28a745;
        }
        
        .btn.success:hover {
            background: #218838;
        }
        
        .result-box {
            display: none;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .result-box.show {
            display: block;
        }
        
        .result-box.success {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }
        
        .result-box.error {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .active-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .links {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-cog"></i>
                Configurador de Base de Datos
            </h1>
            <p>Cambia fácilmente entre servidor local y remoto</p>
        </div>
        
        <div class="content">
            <div class="current-config">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Configuración Actual
                </h3>
                <p id="currentConfigText">
                    <?php
                    if ($currentConfig === 'local') {
                        echo '<strong style="color: #28a745;">🟢 Servidor Local (XAMPP)</strong>';
                    } elseif ($currentConfig === 'remoto') {
                        echo '<strong style="color: #2196f3;">🔵 Servidor Remoto</strong>';
                    } else {
                        echo '<strong style="color: #ff9800;">🟠 Configuración Personalizada</strong>';
                    }
                    ?>
                </p>
            </div>
            
            <h2 style="margin-bottom: 20px; color: #333;">Selecciona una configuración:</h2>
            
            <div class="config-cards">
                <!-- Config Local -->
                <div class="config-card local <?php echo $currentConfig === 'local' ? 'active' : ''; ?>" 
                     onclick="selectConfig('local')">
                    <?php if ($currentConfig === 'local'): ?>
                    <div class="active-badge">✓ ACTIVA</div>
                    <?php endif; ?>
                    
                    <div class="icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3>Servidor Local</h3>
                    <div class="config-details">
                        <div>
                            <span>Servidor:</span>
                            <strong>localhost</strong>
                        </div>
                        <div>
                            <span>Puerto:</span>
                            <strong>3306</strong>
                        </div>
                        <div>
                            <span>Usuario:</span>
                            <strong>root</strong>
                        </div>
                        <div>
                            <span>Base de datos:</span>
                            <strong>makadb</strong>
                        </div>
                    </div>
                    <button class="btn success" onclick="applyConfig(event, 'local')">
                        <i class="fas fa-check"></i>
                        Aplicar Local
                    </button>
                </div>
                
                <!-- Config Remota -->
                <div class="config-card remoto <?php echo $currentConfig === 'remoto' ? 'active' : ''; ?>" 
                     onclick="selectConfig('remoto')">
                    <?php if ($currentConfig === 'remoto'): ?>
                    <div class="active-badge">✓ ACTIVA</div>
                    <?php endif; ?>
                    
                    <div class="icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3>Servidor Remoto</h3>
                    <div class="config-details">
                        <div>
                            <span>Servidor:</span>
                            <strong>10.70.110.58</strong>
                        </div>
                        <div>
                            <span>Puerto:</span>
                            <strong>3307</strong>
                        </div>
                        <div>
                            <span>Usuario:</span>
                            <strong>amigo_remoto2</strong>
                        </div>
                        <div>
                            <span>Base de datos:</span>
                            <strong>makadb</strong>
                        </div>
                    </div>
                    <button class="btn success" onclick="applyConfig(event, 'remoto')">
                        <i class="fas fa-check"></i>
                        Aplicar Remoto
                    </button>
                </div>
            </div>
            
            <div class="result-box" id="resultBox">
                <!-- Resultados -->
            </div>
            
            <div class="links">
                <a href="admin_database.php">
                    <i class="fas fa-database"></i>
                    Panel de Administración
                </a>
                <a href="import_database.php">
                    <i class="fas fa-file-import"></i>
                    Importar Base de Datos
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i>
                    Ir al Inicio
                </a>
            </div>
        </div>
    </div>
    
    <script>
        let selectedConfig = '<?php echo $currentConfig; ?>';
        
        function selectConfig(type) {
            selectedConfig = type;
            document.querySelectorAll('.config-card').forEach(card => {
                card.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
        
        async function applyConfig(event, type) {
            event.stopPropagation();
            
            const btn = event.target.closest('.btn');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aplicando...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'apply');
                formData.append('config_type', type);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showResult(
                        'success',
                        `✅ Configuración aplicada correctamente a <strong>${data.config.descripcion}</strong><br><br>` +
                        `<strong>Estado de conexión:</strong><br>` +
                        (data.connection.success ? 
                            `✅ Conexión exitosa<br>📊 MySQL ${data.connection.version}<br>📁 ${data.connection.tables} tablas encontradas` :
                            `❌ ${data.connection.message}: ${data.connection.error}`)
                    );
                    
                    // Actualizar badge
                    location.reload();
                } else {
                    showResult('error', '❌ Error: ' + data.error);
                }
                
            } catch (error) {
                showResult('error', '❌ Error: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        function showResult(type, message) {
            const resultBox = document.getElementById('resultBox');
            resultBox.className = 'result-box show ' + type;
            resultBox.innerHTML = message;
        }
    </script>
</body>
</html>
