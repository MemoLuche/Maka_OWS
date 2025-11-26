<?php
/**
 * Script de Importación de Base de Datos
 * 
 * Este script permite importar un archivo SQL exportado desde admin_database.php
 * Úsalo en tu instalación local de XAMPP para restaurar la base de datos
 */

// Configuración
$uploadDir = 'uploads/';
$maxFileSize = 100 * 1024 * 1024; // 100 MB máximo

// Crear directorio de uploads si no existe
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Manejar la importación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlFile'])) {
    header('Content-Type: application/json');
    
    try {
        require_once 'config/conexion.php';
        
        $file = $_FILES['sqlFile'];
        
        // Validar archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }
        
        if ($file['size'] > $maxFileSize) {
            throw new Exception('El archivo es demasiado grande. Máximo 100 MB');
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            throw new Exception('Solo se permiten archivos .sql');
        }
        
        // Leer contenido del archivo
        $sqlContent = file_get_contents($file['tmp_name']);
        
        if (empty($sqlContent)) {
            throw new Exception('El archivo está vacío');
        }
        
        // Dividir el SQL en queries individuales
        $queries = explode(';', $sqlContent);
        
        $executedQueries = 0;
        $errors = [];
        
        // Desactivar autocommit para mejor rendimiento
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        $pdo->beginTransaction();
        
        foreach ($queries as $query) {
            $query = trim($query);
            
            // Saltar comentarios y líneas vacías
            if (empty($query) || 
                strpos($query, '--') === 0 || 
                strpos($query, '/*') === 0 ||
                strpos($query, '#') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($query);
                $executedQueries++;
            } catch (PDOException $e) {
                // Guardar errores pero continuar
                $errors[] = [
                    'query' => substr($query, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Commit de la transacción
        $pdo->commit();
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Importación completada',
            'executedQueries' => $executedQueries,
            'totalQueries' => count($queries),
            'errors' => $errors,
            'errorCount' => count($errors)
        ]);
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Base de Datos</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.05em;
        }
        
        .content {
            padding: 40px;
        }
        
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 50px 30px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 30px;
        }
        
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f1ff;
        }
        
        .upload-area.dragover {
            border-color: #28a745;
            background: #e8ffe8;
        }
        
        .upload-area i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .upload-area h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .upload-area p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .file-input {
            display: none;
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
        
        .selected-file {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
        
        .selected-file.show {
            display: block;
        }
        
        .selected-file i {
            color: #4caf50;
            margin-right: 10px;
        }
        
        .selected-file strong {
            color: #2e7d32;
        }
        
        .progress-container {
            display: none;
            margin: 20px 0;
        }
        
        .progress-container.show {
            display: block;
        }
        
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 30px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .result-box {
            display: none;
            padding: 20px;
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
        
        .result-box h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: rgba(255,255,255,0.5);
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-item strong {
            display: block;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-item span {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            background: rgba(255,255,255,0.5);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        
        .warning-box i {
            margin-right: 10px;
            color: #ffc107;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-file-import"></i>
                Importar Base de Datos
            </h1>
            <p>Restaura tu base de datos desde un archivo SQL exportado</p>
        </div>
        
        <div class="content">
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Advertencia:</strong> Esta operación eliminará y recreará todas las tablas existentes. Asegúrate de tener un respaldo antes de continuar.
            </div>
            
            <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <h3>Arrastra tu archivo SQL aquí</h3>
                <p>o haz clic para seleccionar</p>
                <input type="file" id="fileInput" class="file-input" accept=".sql" onchange="handleFileSelect(event)">
            </div>
            
            <div class="selected-file" id="selectedFile">
                <i class="fas fa-file-alt"></i>
                <strong>Archivo seleccionado:</strong> <span id="fileName"></span>
                (<span id="fileSize"></span>)
            </div>
            
            <div style="text-align: center;">
                <button class="btn" id="importBtn" onclick="importDatabase()" disabled>
                    <i class="fas fa-database"></i>
                    Importar Base de Datos
                </button>
            </div>
            
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">0%</div>
                </div>
                <p style="text-align: center; color: #666;">Importando datos...</p>
            </div>
            
            <div class="result-box" id="resultBox">
                <!-- Resultados se mostrarán aquí -->
            </div>
            
            <div style="text-align: center;">
                <a href="admin_database.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Volver al Panel de Administración
                </a>
            </div>
        </div>
    </div>
    
    <script>
        let selectedFile = null;
        
        // Drag and drop
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                handleFile(file);
            }
        }
        
        function handleFile(file) {
            // Validar extensión
            if (!file.name.endsWith('.sql')) {
                alert('Por favor selecciona un archivo .sql válido');
                return;
            }
            
            selectedFile = file;
            
            // Mostrar información del archivo
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('selectedFile').classList.add('show');
            document.getElementById('importBtn').disabled = false;
        }
        
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }
        
        async function importDatabase() {
            if (!selectedFile) {
                alert('Por favor selecciona un archivo primero');
                return;
            }
            
            if (!confirm('¿Estás seguro de que deseas importar esta base de datos? Esto eliminará todos los datos actuales.')) {
                return;
            }
            
            // Deshabilitar botón
            document.getElementById('importBtn').disabled = true;
            
            // Mostrar barra de progreso
            document.getElementById('progressContainer').classList.add('show');
            document.getElementById('resultBox').classList.remove('show');
            
            // Simular progreso
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    updateProgress(progress);
                }
            }, 200);
            
            try {
                const formData = new FormData();
                formData.append('sqlFile', selectedFile);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                clearInterval(progressInterval);
                updateProgress(100);
                
                setTimeout(() => {
                    document.getElementById('progressContainer').classList.remove('show');
                    showResults(data);
                }, 500);
                
            } catch (error) {
                clearInterval(progressInterval);
                document.getElementById('progressContainer').classList.remove('show');
                showResults({
                    success: false,
                    error: 'Error al importar: ' + error.message
                });
            }
        }
        
        function updateProgress(percent) {
            const progressFill = document.getElementById('progressFill');
            progressFill.style.width = percent + '%';
            progressFill.textContent = percent + '%';
        }
        
        function showResults(data) {
            const resultBox = document.getElementById('resultBox');
            resultBox.classList.add('show');
            
            if (data.success) {
                resultBox.className = 'result-box show success';
                resultBox.innerHTML = `
                    <h3>
                        <i class="fas fa-check-circle"></i>
                        ¡Importación Exitosa!
                    </h3>
                    <div class="stats">
                        <div class="stat-item">
                            <strong>${data.executedQueries}</strong>
                            <span>Consultas ejecutadas</span>
                        </div>
                        <div class="stat-item">
                            <strong>${data.totalQueries}</strong>
                            <span>Total de consultas</span>
                        </div>
                    </div>
                    ${data.errorCount > 0 ? `
                        <div style="margin-top: 15px;">
                            <strong>⚠️ ${data.errorCount} advertencias/errores (no críticos)</strong>
                            <div class="error-list">
                                ${data.errors.map(err => `
                                    <div style="margin-bottom: 10px; padding: 5px; background: rgba(255,255,255,0.7); border-radius: 3px;">
                                        <strong>Query:</strong> ${err.query}<br>
                                        <strong>Error:</strong> ${err.error}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    <p style="margin-top: 15px;">
                        <a href="admin_database.php" style="color: #155724; font-weight: 600;">
                            <i class="fas fa-database"></i> Ver base de datos importada
                        </a>
                    </p>
                `;
                
                // Reiniciar formulario
                document.getElementById('selectedFile').classList.remove('show');
                document.getElementById('fileInput').value = '';
                selectedFile = null;
                
            } else {
                resultBox.className = 'result-box show error';
                resultBox.innerHTML = `
                    <h3>
                        <i class="fas fa-times-circle"></i>
                        Error en la Importación
                    </h3>
                    <p><strong>Mensaje de error:</strong></p>
                    <div style="background: rgba(255,255,255,0.5); padding: 10px; border-radius: 5px; margin-top: 10px; font-family: monospace;">
                        ${data.error}
                    </div>
                    <p style="margin-top: 15px;">
                        Por favor, verifica el archivo SQL e intenta nuevamente.
                    </p>
                `;
            }
            
            // Habilitar botón de nuevo
            document.getElementById('importBtn').disabled = false;
        }
    </script>
</body>
</html>
