<?php
session_start();

// Verificar que el usuario esté autenticado como administrador
// Puedes ajustar esta validación según tu sistema de autenticación
/*
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login_admin.php');
    exit();
}
*/

require_once 'config/conexion.php';

// Manejar peticiones Ajax
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        // Obtener tablas
        if ($_GET['ajax'] === 'getTables') {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $tablesInfo = [];
            foreach ($tables as $table) {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                $tablesInfo[] = [
                    'name' => $table,
                    'rows' => $count
                ];
            }
            echo json_encode(['success' => true, 'tables' => $tablesInfo]);
            exit;
        }
        
        // Obtener estructura de tabla
        if ($_GET['ajax'] === 'getTableStructure') {
            $tableName = $_GET['table'];
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener índices
            $indexStmt = $pdo->query("SHOW INDEX FROM `$tableName`");
            $indexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'structure' => $structure, 'indexes' => $indexes]);
            exit;
        }
        
        // Obtener datos de tabla
        if ($_GET['ajax'] === 'getTableData') {
            $tableName = $_GET['table'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT $limit OFFSET $offset");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `$tableName`");
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode(['success' => true, 'data' => $data, 'total' => $total]);
            exit;
        }
        
        // Ejecutar consulta
        if ($_GET['ajax'] === 'executeQuery' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = trim($input['query']);
            
            if (empty($query)) {
                echo json_encode(['success' => false, 'error' => 'La consulta está vacía']);
                exit;
            }
            
            $startTime = microtime(true);
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Si es SELECT, SHOW, DESCRIBE, EXPLAIN
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)/i', $query)) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'rows' => count($data),
                    'executionTime' => $executionTime,
                    'type' => 'select'
                ]);
            } else {
                $rowCount = $stmt->rowCount();
                echo json_encode([
                    'success' => true,
                    'affectedRows' => $rowCount,
                    'executionTime' => $executionTime,
                    'type' => 'modification'
                ]);
            }
            exit;
        }
        
        // Actualizar registro
        if ($_GET['ajax'] === 'updateCell' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $tableName = $input['table'];
            $column = $input['column'];
            $value = $input['value'];
            $whereClause = $input['where'];
            
            $sql = "UPDATE `$tableName` SET `$column` = :value WHERE $whereClause";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':value', $value);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'affectedRows' => $stmt->rowCount()]);
            exit;
        }
        
        // Eliminar registro
        if ($_GET['ajax'] === 'deleteRow' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $tableName = $input['table'];
            $whereClause = $input['where'];
            
            $sql = "DELETE FROM `$tableName` WHERE $whereClause";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'affectedRows' => $stmt->rowCount()]);
            exit;
        }
        
        // Información de la BD
        if ($_GET['ajax'] === 'getDbInfo') {
            $versionStmt = $pdo->query("SELECT VERSION() as version");
            $version = $versionStmt->fetch(PDO::FETCH_ASSOC)['version'];
            
            $sizeStmt = $pdo->query("
                SELECT 
                    SUM(data_length + index_length) / 1024 / 1024 AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = '$nombre_bd'
            ");
            $size = $sizeStmt->fetch(PDO::FETCH_ASSOC)['size_mb'];
            
            echo json_encode([
                'success' => true,
                'server' => $servidor_ip,
                'port' => $puerto,
                'database' => $nombre_bd,
                'user' => $usuario_bd,
                'version' => $version,
                'size' => round($size, 2)
            ]);
            exit;
        }
        
        // Exportar a CSV
        if ($_GET['ajax'] === 'exportCSV' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $data = $input['data'];
            $filename = $input['filename'] ?? 'export';
            
            if (empty($data)) {
                echo json_encode(['success' => false, 'error' => 'No hay datos para exportar']);
                exit;
            }
            
            // Generar CSV
            $csvContent = "";
            
            // Encabezados
            $columns = array_keys($data[0]);
            $csvContent .= implode(',', array_map(function($col) {
                return '"' . str_replace('"', '""', $col) . '"';
            }, $columns)) . "\n";
            
            // Datos
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($row as $value) {
                    $value = $value ?? 'NULL';
                    $csvRow[] = '"' . str_replace('"', '""', $value) . '"';
                }
                $csvContent .= implode(',', $csvRow) . "\n";
            }
            
            // Devolver CSV como base64 para descarga del lado del cliente
            echo json_encode([
                'success' => true,
                'csv' => base64_encode($csvContent),
                'filename' => $filename . '.csv'
            ]);
            exit;
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Workbench - Web</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1e1e1e;
            color: #d4d4d4;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Toolbar superior */
        .toolbar {
            background: #1e1e1e;
            border-bottom: 1px solid #3e3e42;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .toolbar-title {
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toolbar-title i {
            color: #007acc;
            font-size: 20px;
        }
        
        .toolbar-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .toolbar-btn {
            background: #007acc;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .toolbar-btn:hover {
            background: #005a9e;
        }
        
        .toolbar-btn.danger {
            background: #d9534f;
        }
        
        .toolbar-btn.danger:hover {
            background: #c9302c;
        }
        
        /* Container principal */
        .main-container {
            display: flex;
            height: calc(100vh - 45px);
        }
        
        /* Panel lateral (navegador) */
        .sidebar {
            width: 280px;
            background: #252526;
            border-right: 1px solid #3e3e42;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .sidebar-header {
            background: #1e1e1e;
            padding: 12px 15px;
            border-bottom: 1px solid #3e3e42;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: #cccccc;
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
        }
        
        .db-info-panel {
            padding: 15px;
            border-bottom: 1px solid #3e3e42;
            background: #1e1e1e;
        }
        
        .db-info-panel h3 {
            color: #4ec9b0;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .db-info-item {
            padding: 6px 0;
            font-size: 12px;
            color: #cccccc;
            display: flex;
            justify-content: space-between;
        }
        
        .db-info-item label {
            color: #858585;
        }
        
        .db-info-item span {
            color: #dcdcaa;
        }
        
        .schema-tree {
            padding: 10px 0;
        }
        
        .schema-section {
            margin-bottom: 5px;
        }
        
        .schema-section-header {
            padding: 8px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            font-size: 13px;
        }
        
        .schema-section-header:hover {
            background: #2a2d2e;
        }
        
        .schema-section-header i {
            color: #858585;
            font-size: 12px;
            width: 12px;
        }
        
        .schema-section-header.active i {
            transform: rotate(90deg);
        }
        
        .table-list {
            display: none;
        }
        
        .table-list.open {
            display: block;
        }
        
        .table-item {
            padding: 8px 15px 8px 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            font-size: 13px;
        }
        
        .table-item:hover {
            background: #2a2d2e;
        }
        
        .table-item.active {
            background: #37373d;
            border-left: 3px solid #007acc;
        }
        
        .table-item i {
            color: #4ec9b0;
            font-size: 14px;
        }
        
        .table-item .row-count {
            margin-left: auto;
            font-size: 11px;
            color: #858585;
        }
        
        /* Panel principal */
        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #1e1e1e;
        }
        
        /* Tabs */
        .tabs-container {
            background: #2d2d30;
            border-bottom: 1px solid #3e3e42;
            display: flex;
            align-items: flex-end;
            padding: 0 10px;
        }
        
        .tab {
            background: #2d2d30;
            border: 1px solid transparent;
            border-bottom: none;
            padding: 8px 20px 8px 15px;
            cursor: pointer;
            font-size: 13px;
            color: #969696;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #ffffff;
        }
        
        .tab.active {
            background: #1e1e1e;
            border-color: #3e3e42;
            color: #ffffff;
        }
        
        .tab i {
            font-size: 12px;
        }
        
        .tab .close-tab {
            margin-left: 8px;
            color: #969696;
            font-size: 14px;
        }
        
        .tab .close-tab:hover {
            color: #e74c3c;
        }
        
        /* Contenido de tabs */
        .tab-content {
            display: none;
            flex: 1;
            flex-direction: column;
            overflow: hidden;
        }
        
        .tab-content.active {
            display: flex;
        }
        
        /* Editor SQL */
        .sql-editor-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .sql-editor-toolbar {
            background: #2d2d30;
            padding: 8px 15px;
            border-bottom: 1px solid #3e3e42;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .sql-editor {
            flex: 1;
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            padding: 15px;
            border: none;
            resize: none;
            outline: none;
            line-height: 1.6;
        }
        
        .sql-editor::-webkit-scrollbar {
            width: 12px;
        }
        
        .sql-editor::-webkit-scrollbar-track {
            background: #1e1e1e;
        }
        
        .sql-editor::-webkit-scrollbar-thumb {
            background: #424242;
            border-radius: 6px;
        }
        
        .sql-editor::-webkit-scrollbar-thumb:hover {
            background: #4e4e4e;
        }
        
        .results-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #252526;
            border-top: 1px solid #3e3e42;
            overflow: hidden;
            min-height: 0; /* Importante para que el flex funcione correctamente */
        }
        
        .results-header {
            background: #2d2d30;
            padding: 10px 15px;
            border-bottom: 1px solid #3e3e42;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-info {
            font-size: 12px;
            color: #cccccc;
        }
        
        .results-info .success {
            color: #4ec9b0;
        }
        
        .results-info .error {
            color: #f48771;
        }
        
        .results-table-container {
            flex: 1;
            overflow: auto;
            overflow-x: auto;
            overflow-y: auto;
            max-width: 100%;
            min-height: 0; /* Importante para que el flex funcione */
            display: block; /* Asegurar que sea un bloque para overflow */
        }
        
        /* Estilo de la barra de scroll */
        .results-table-container::-webkit-scrollbar {
            height: 12px;
            width: 12px;
        }
        
        .results-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }
        
        .results-table-container::-webkit-scrollbar-thumb {
            background: #99AA8C;
            border-radius: 6px;
        }
        
        .results-table-container::-webkit-scrollbar-thumb:hover {
            background: #8a9a75;
        }
        
        /* Tabla de resultados */
        .results-table {
            width: max-content; /* Cambiar de 100% a max-content para permitir scroll horizontal */
            min-width: 100%; /* Mínimo 100% pero puede crecer */
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .results-table th {
            background: #2d2d30;
            color: #cccccc;
            padding: 10px 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            border-bottom: 1px solid #3e3e42;
            border-right: 1px solid #3e3e42;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .results-table td {
            padding: 8px 15px;
            border-bottom: 1px solid #3e3e42;
            border-right: 1px solid #3e3e42;
            color: #000000;
            background: #ffffff;
        }
        
        .results-table tr:hover {
            background: #f0f0f0;
        }
        
        .results-table td.null {
            color: #999999;
            font-style: italic;
            background: #ffffff;
        }
        
        .results-table td.editable {
            cursor: text;
            position: relative;
        }
        
        .results-table td.editable:hover {
            outline: 1px solid #007acc;
        }
        
        .results-table td input {
            background: #1e1e1e;
            color: #d4d4d4;
            border: 1px solid #007acc;
            padding: 4px 8px;
            width: 100%;
            font-size: 13px;
        }
        
        /* Resizer entre editor y resultados */
        .resizer {
            height: 5px;
            background: #3e3e42;
            cursor: ns-resize;
            position: relative;
        }
        
        .resizer:hover,
        .resizer:active {
            background: #007acc;
        }
        
        /* Vista de tabla */
        .table-view-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .table-view-toolbar {
            background: #2d2d30;
            padding: 10px 15px;
            border-bottom: 1px solid #3e3e42;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .table-view-toolbar input {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            color: #d4d4d4;
            padding: 6px 10px;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .table-view-toolbar select {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            color: #d4d4d4;
            padding: 6px 10px;
            border-radius: 3px;
            font-size: 13px;
        }
        
        /* Alertas */
        .alert {
            padding: 10px 15px;
            margin: 10px;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .alert-success {
            background: #1e3a28;
            border-left: 3px solid #4ec9b0;
            color: #4ec9b0;
        }
        
        .alert-error {
            background: #3a1e1e;
            border-left: 3px solid #f48771;
            color: #f48771;
        }
        
        .alert-info {
            background: #1e2a3a;
            border-left: 3px solid #569cd6;
            color: #569cd6;
        }
        
        /* Botones */
        .btn {
            background: #0e639c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #1177bb;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-success {
            background: #388a34;
        }
        
        .btn-success:hover {
            background: #4caf50;
        }
        
        .btn-download {
            background: #17a2b8;
        }
        
        .btn-download:hover {
            background: #138496;
        }
        
        .btn-danger {
            background: #c5453a;
        }
        
        .btn-danger:hover {
            background: #d9534f;
        }
        
        /* Scrollbar global */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1e1e1e;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #424242;
            border-radius: 6px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #4e4e4e;
        }
        
        /* Loader */
        .loader {
            border: 3px solid #3e3e42;
            border-top: 3px solid #007acc;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 30, 30, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        /* Context menu */
        .context-menu {
            position: fixed;
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 3px;
            padding: 5px 0;
            display: none;
            z-index: 2000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
        }
        
        .context-menu-item {
            padding: 8px 30px 8px 15px;
            cursor: pointer;
            font-size: 13px;
            color: #cccccc;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .context-menu-item:hover {
            background: #37373d;
        }
        
        .context-menu-item i {
            width: 16px;
            font-size: 12px;
        }
        
        /* Row number column */
        .row-number {
            background: #2d2d30;
            color: #858585;
            text-align: center;
            font-size: 12px;
            width: 50px;
        }
        
        .pagination {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #2d2d30;
            border-top: 1px solid #3e3e42;
            font-size: 13px;
        }
        
        .pagination button {
            padding: 4px 10px;
        }
        
        .pagination span {
            color: #cccccc;
        }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="toolbar">
        <div class="toolbar-title">
            <i class="fas fa-database"></i>
            MySQL Workbench - Web Edition
        </div>
        <div class="toolbar-buttons">
            <button class="toolbar-btn" onclick="refreshSchema()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button class="toolbar-btn" onclick="openNewQueryTab()">
                <i class="fas fa-plus"></i> Nueva Consulta
            </button>
            <a href="admin_dashboard.php" class="toolbar-btn" style="text-decoration: none;">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
    
    <!-- Container principal -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-server"></i> NAVEGADOR DE ESQUEMAS
            </div>
            
            <div class="db-info-panel">
                <h3><i class="fas fa-database"></i> <span id="dbName">Cargando...</span></h3>
                <div class="db-info-item">
                    <label>Servidor:</label>
                    <span id="dbServer"></span>
                </div>
                <div class="db-info-item">
                    <label>Versión:</label>
                    <span id="dbVersion"></span>
                </div>
                <div class="db-info-item">
                    <label>Tamaño:</label>
                    <span id="dbSize"></span>
                </div>
            </div>
            
            <div class="sidebar-content">
                <div class="schema-tree">
                    <div class="schema-section">
                        <div class="schema-section-header active" onclick="toggleSection(this)">
                            <i class="fas fa-caret-right"></i>
                            <i class="fas fa-table"></i>
                            <span>Tablas</span>
                        </div>
                        <div class="table-list open" id="tableList">
                            <div class="loader"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel principal -->
        <div class="main-panel">
            <div class="tabs-container" id="tabsContainer">
                <div class="tab active" data-tab="query1">
                    <i class="fas fa-code"></i>
                    Query 1
                </div>
            </div>
            
            <div class="tab-content active" id="query1">
                <div class="sql-editor-container">
                    <div class="sql-editor-toolbar">
                        <button class="btn btn-success" onclick="executeQuery('query1')">
                            <i class="fas fa-play"></i> Ejecutar (Ctrl+Enter)
                        </button>
                        <button class="btn" onclick="clearEditor('query1')">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                        <button class="btn" onclick="formatSQL('query1')">
                            <i class="fas fa-align-left"></i> Formatear
                        </button>
                        <button class="btn btn-download" onclick="exportToCSV('query1')" id="btn-export-query1" style="display: none;">
                            <i class="fas fa-download"></i> Descargar CSV
                        </button>
                    </div>
                    <textarea class="sql-editor" id="editor-query1" placeholder="-- Escribe tu consulta SQL aquí&#10;SELECT * FROM tabla LIMIT 100;"></textarea>
                    <div class="resizer" onmousedown="initResize(event, 'query1')"></div>
                    <div class="results-container" id="results-query1">
                        <div class="results-header">
                            <div class="results-info">
                                Resultados aparecerán aquí...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Context Menu -->
    <div class="context-menu" id="contextMenu">
        <div class="context-menu-item" onclick="copyCell()">
            <i class="fas fa-copy"></i> Copiar
        </div>
        <div class="context-menu-item" onclick="editCell()">
            <i class="fas fa-edit"></i> Editar
        </div>
        <div class="context-menu-item" onclick="deleteRow()">
            <i class="fas fa-trash"></i> Eliminar fila
        </div>
    </div>

    <script>
        // ========== CUSTOM MODALS ==========
        function customAlert(message, type = 'info', title = null) {
            let iconClass = '';
            let defaultTitle = '';
            
            switch(type) {
                case 'success':
                    iconClass = 'fa-check-circle';
                    defaultTitle = 'Éxito';
                    break;
                case 'error':
                    iconClass = 'fa-exclamation-triangle';
                    defaultTitle = 'Error';
                    break;
                case 'warning':
                    iconClass = 'fa-exclamation-circle';
                    defaultTitle = 'Advertencia';
                    break;
                default:
                    iconClass = 'fa-info-circle';
                    defaultTitle = 'Información';
            }
            
            alert((title || defaultTitle) + ': ' + message);
        }
        
        // ========== VARIABLES GLOBALES ==========
        let currentTable = null;
        let currentCell = null;
        let tabCounter = 1;
        let resizing = false;
        let currentResizeTab = null;
        
        // Cargar información inicial
        window.addEventListener('DOMContentLoaded', function() {
            loadDbInfo();
            loadTables();
            setupKeyboardShortcuts();
        });
        
        // Información de la BD
        async function loadDbInfo() {
            try {
                const response = await fetch('?ajax=getDbInfo');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('dbName').textContent = data.database;
                    document.getElementById('dbServer').textContent = `${data.server}:${data.port}`;
                    document.getElementById('dbVersion').textContent = data.version;
                    document.getElementById('dbSize').textContent = `${data.size} MB`;
                }
            } catch (error) {
                console.error('Error loading DB info:', error);
            }
        }
        
        // Cargar tablas
        async function loadTables() {
            try {
                const response = await fetch('?ajax=getTables');
                const data = await response.json();
                
                if (data.success) {
                    const tableList = document.getElementById('tableList');
                    tableList.innerHTML = '';
                    
                    data.tables.forEach(table => {
                        const item = document.createElement('div');
                        item.className = 'table-item';
                        item.innerHTML = `
                            <i class="fas fa-table"></i>
                            <span>${table.name}</span>
                            <span class="row-count">${table.rows}</span>
                        `;
                        item.onclick = () => openTableTab(table.name);
                        tableList.appendChild(item);
                    });
                }
            } catch (error) {
                console.error('Error loading tables:', error);
            }
        }
        
        // Toggle sección del árbol
        function toggleSection(header) {
            header.classList.toggle('active');
            const list = header.nextElementSibling;
            list.classList.toggle('open');
        }
        
        // Abrir nueva pestaña de consulta
        function openNewQueryTab() {
            tabCounter++;
            const tabId = `query${tabCounter}`;
            
            // Crear tab
            const tab = document.createElement('div');
            tab.className = 'tab';
            tab.setAttribute('data-tab', tabId);
            tab.innerHTML = `
                <i class="fas fa-code"></i>
                Query ${tabCounter}
                <span class="close-tab" onclick="closeTab(event, '${tabId}')">×</span>
            `;
            tab.onclick = (e) => {
                if (!e.target.classList.contains('close-tab')) {
                    switchTab(tabId);
                }
            };
            
            document.getElementById('tabsContainer').appendChild(tab);
            
            // Crear contenido
            const content = document.createElement('div');
            content.className = 'tab-content';
            content.id = tabId;
            content.innerHTML = `
                <div class="sql-editor-container">
                    <div class="sql-editor-toolbar">
                        <button class="btn btn-success" onclick="executeQuery('${tabId}')">
                            <i class="fas fa-play"></i> Ejecutar (Ctrl+Enter)
                        </button>
                        <button class="btn" onclick="clearEditor('${tabId}')">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                        <button class="btn" onclick="formatSQL('${tabId}')">
                            <i class="fas fa-align-left"></i> Formatear
                        </button>
                        <button class="btn btn-download" onclick="exportToCSV('${tabId}')" id="btn-export-${tabId}" style="display: none;">
                            <i class="fas fa-download"></i> Descargar CSV
                        </button>
                    </div>
                    <textarea class="sql-editor" id="editor-${tabId}" placeholder="-- Escribe tu consulta SQL aquí"></textarea>
                    <div class="resizer" onmousedown="initResize(event, '${tabId}')"></div>
                    <div class="results-container" id="results-${tabId}">
                        <div class="results-header">
                            <div class="results-info">Resultados aparecerán aquí...</div>
                        </div>
                    </div>
                </div>
            `;
            
            document.querySelector('.main-panel').appendChild(content);
            switchTab(tabId);
        }
        
        // Abrir pestaña de tabla
        async function openTableTab(tableName) {
            const tabId = `table-${tableName}`;
            
            // Si ya existe, solo cambiar a ella
            if (document.getElementById(tabId)) {
                switchTab(tabId);
                return;
            }
            
            currentTable = tableName;
            
            // Crear tab
            const tab = document.createElement('div');
            tab.className = 'tab';
            tab.setAttribute('data-tab', tabId);
            tab.innerHTML = `
                <i class="fas fa-table"></i>
                ${tableName}
                <span class="close-tab" onclick="closeTab(event, '${tabId}')">×</span>
            `;
            tab.onclick = (e) => {
                if (!e.target.classList.contains('close-tab')) {
                    switchTab(tabId);
                }
            };
            
            document.getElementById('tabsContainer').appendChild(tab);
            
            // Crear contenido
            const content = document.createElement('div');
            content.className = 'tab-content';
            content.id = tabId;
            content.innerHTML = `
                <div class="table-view-container">
                    <div class="table-view-toolbar">
                        <button class="btn" onclick="refreshTableData('${tableName}', '${tabId}')">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button class="btn btn-success" onclick="showTableStructure('${tableName}')">
                            <i class="fas fa-wrench"></i> Estructura
                        </button>
                        <button class="btn btn-download" onclick="exportToCSV('${tabId}')" id="btn-export-${tabId}" style="display: none;">
                            <i class="fas fa-download"></i> Descargar CSV
                        </button>
                        <input type="text" id="filter-${tabId}" placeholder="Filtrar..." 
                               onkeyup="filterTable('${tabId}')" style="margin-left: auto; width: 200px;">
                        <select id="limit-${tabId}" onchange="refreshTableData('${tableName}', '${tabId}')">
                            <option value="100">100 registros</option>
                            <option value="500">500 registros</option>
                            <option value="1000" selected>1000 registros</option>
                            <option value="5000">5000 registros</option>
                        </select>
                    </div>
                    <div class="results-container" id="results-${tabId}">
                        <div class="loader"></div>
                    </div>
                </div>
            `;
            
            document.querySelector('.main-panel').appendChild(content);
            switchTab(tabId);
            
            // Cargar datos
            await loadTableData(tableName, tabId);
        }
        
        // Cargar datos de tabla
        async function loadTableData(tableName, tabId) {
            try {
                const limit = document.getElementById(`limit-${tabId}`)?.value || 1000;
                const response = await fetch(`?ajax=getTableData&table=${encodeURIComponent(tableName)}&limit=${limit}`);
                const data = await response.json();
                
                if (data.success) {
                    displayTableResults(data.data, `results-${tabId}`, tableName, data.total);
                }
            } catch (error) {
                console.error('Error loading table data:', error);
            }
        }
        
        // Cambiar entre tabs
        function switchTab(tabId) {
            // Desactivar todos
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Activar seleccionado
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // Actualizar tabla activa en sidebar
            document.querySelectorAll('.table-item').forEach(item => item.classList.remove('active'));
            if (tabId.startsWith('table-')) {
                const tableName = tabId.replace('table-', '');
                const tableItem = Array.from(document.querySelectorAll('.table-item'))
                    .find(item => item.querySelector('span').textContent === tableName);
                if (tableItem) tableItem.classList.add('active');
            }
        }
        
        // Cerrar tab
        function closeTab(event, tabId) {
            event.stopPropagation();
            
            const tab = document.querySelector(`[data-tab="${tabId}"]`);
            const content = document.getElementById(tabId);
            
            const wasActive = tab.classList.contains('active');
            
            tab.remove();
            content.remove();
            
            // Si era la activa, activar la primera disponible
            if (wasActive) {
                const firstTab = document.querySelector('.tab');
                if (firstTab) {
                    switchTab(firstTab.getAttribute('data-tab'));
                }
            }
        }
        
        // Ejecutar consulta
        async function executeQuery(tabId) {
            const editor = document.getElementById(`editor-${tabId}`);
            const query = editor.value.trim();
            
            if (!query) {
                showAlert('Por favor escribe una consulta', 'error', tabId);
                return;
            }
            
            const resultsContainer = document.getElementById(`results-${tabId}`);
            resultsContainer.innerHTML = '<div class="loader"></div>';
            
            try {
                const response = await fetch('?ajax=executeQuery', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({query})
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.type === 'select') {
                        displayQueryResults(data.data, `results-${tabId}`, data.rows, data.executionTime);
                    } else {
                        showAlert(`Consulta ejecutada exitosamente. ${data.affectedRows} fila(s) afectada(s). Tiempo: ${data.executionTime}ms`, 'success', tabId);
                        resultsContainer.innerHTML = '';
                    }
                } else {
                    showAlert(`Error: ${data.error}`, 'error', tabId);
                }
            } catch (error) {
                showAlert(`Error: ${error.message}`, 'error', tabId);
            }
        }
        
        // Almacenar datos para exportación
        const exportData = {};
        
        // Mostrar resultados de consulta
        function displayQueryResults(data, containerId, rowCount, executionTime) {
            const container = document.getElementById(containerId);
            const tabId = containerId.replace('results-', '');
            
            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="results-header">
                        <div class="results-info">
                            <span class="success">Consulta ejecutada exitosamente. 0 filas. Tiempo: ${executionTime}ms</span>
                        </div>
                    </div>
                    <div style="padding: 20px; text-align: center; color: #858585;">
                        No se encontraron resultados
                    </div>
                `;
                // Ocultar botón de exportar
                const exportBtn = document.getElementById(`btn-export-${tabId}`);
                if (exportBtn) exportBtn.style.display = 'none';
                delete exportData[tabId];
                return;
            }
            
            // Guardar datos para exportación
            exportData[tabId] = data;
            
            // Mostrar botón de exportar
            const exportBtn = document.getElementById(`btn-export-${tabId}`);
            if (exportBtn) exportBtn.style.display = 'inline-flex';
            
            const columns = Object.keys(data[0]);
            
            let html = `
                <div class="results-header">
                    <div class="results-info">
                        <span class="success">${rowCount} fila(s) retornada(s). Tiempo: ${executionTime}ms</span>
                    </div>
                </div>
                <div class="results-table-container">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th class="row-number">#</th>
            `;
            
            columns.forEach(col => {
                html += `<th>${escapeHtml(col)}</th>`;
            });
            
            html += `</tr></thead><tbody>`;
            
            data.forEach((row, index) => {
                html += `<tr><td class="row-number">${index + 1}</td>`;
                columns.forEach(col => {
                    const value = row[col];
                    if (value === null) {
                        html += `<td class="null">NULL</td>`;
                    } else {
                        html += `<td>${escapeHtml(String(value))}</td>`;
                    }
                });
                html += `</tr>`;
            });
            
            html += `</tbody></table></div>`;
            container.innerHTML = html;
        }
        
        // Mostrar resultados de tabla con edición
        function displayTableResults(data, containerId, tableName, total) {
            const container = document.getElementById(containerId);
            const tabId = containerId.replace('results-', '');
            
            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="results-header">
                        <div class="results-info">Tabla vacía</div>
                    </div>
                    <div style="padding: 20px; text-align: center; color: #858585;">
                        No hay datos en esta tabla
                    </div>
                `;
                // Ocultar botón de exportar
                const exportBtn = document.getElementById(`btn-export-${tabId}`);
                if (exportBtn) exportBtn.style.display = 'none';
                delete exportData[tabId];
                return;
            }
            
            // Guardar datos para exportación
            exportData[tabId] = data;
            
            // Mostrar botón de exportar
            const exportBtn = document.getElementById(`btn-export-${tabId}`);
            if (exportBtn) exportBtn.style.display = 'inline-flex';
            
            const columns = Object.keys(data[0]);
            
            let html = `
                <div class="results-header">
                    <div class="results-info">
                        <span class="success">${data.length} de ${total} registros</span>
                    </div>
                </div>
                <div class="results-table-container">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th class="row-number">#</th>
            `;
            
            columns.forEach(col => {
                html += `<th>${escapeHtml(col)}</th>`;
            });
            
            html += `</tr></thead><tbody>`;
            
            data.forEach((row, index) => {
                const rowData = JSON.stringify(row).replace(/"/g, '&quot;');
                html += `<tr data-row='${rowData}'>
                    <td class="row-number">${index + 1}</td>`;
                
                columns.forEach(col => {
                    const value = row[col];
                    if (value === null) {
                        html += `<td class="null editable" data-column="${escapeHtml(col)}" 
                                 data-table="${escapeHtml(tableName)}" 
                                 ondblclick="makeEditable(this)">NULL</td>`;
                    } else {
                        html += `<td class="editable" data-column="${escapeHtml(col)}" 
                                 data-table="${escapeHtml(tableName)}" 
                                 ondblclick="makeEditable(this)">${escapeHtml(String(value))}</td>`;
                    }
                });
                html += `</tr>`;
            });
            
            html += `</tbody></table></div>`;
            container.innerHTML = html;
        }
        
        // Hacer celda editable
        function makeEditable(cell) {
            if (cell.querySelector('input')) return;
            
            const originalValue = cell.classList.contains('null') ? '' : cell.textContent;
            const input = document.createElement('input');
            input.value = originalValue;
            input.style.width = '100%';
            
            input.onblur = async function() {
                const newValue = this.value;
                if (newValue !== originalValue) {
                    await updateCell(cell, newValue);
                } else {
                    cell.textContent = originalValue || 'NULL';
                    if (!originalValue) cell.classList.add('null');
                }
            };
            
            input.onkeydown = function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                } else if (e.key === 'Escape') {
                    cell.textContent = originalValue || 'NULL';
                    if (!originalValue) cell.classList.add('null');
                }
            };
            
            cell.textContent = '';
            cell.classList.remove('null');
            cell.appendChild(input);
            input.focus();
            input.select();
        }
        
        // Actualizar celda
        async function updateCell(cell, newValue) {
            const column = cell.dataset.column;
            const tableName = cell.dataset.table;
            const row = JSON.parse(cell.closest('tr').dataset.row);
            
            // Construir WHERE clause
            const whereParts = [];
            for (const [key, value] of Object.entries(row)) {
                if (value === null) {
                    whereParts.push(`\`${key}\` IS NULL`);
                } else {
                    whereParts.push(`\`${key}\` = ${JSON.stringify(value)}`);
                }
            }
            const whereClause = whereParts.join(' AND ');
            
            try {
                const response = await fetch('?ajax=updateCell', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        table: tableName,
                        column: column,
                        value: newValue || null,
                        where: whereClause
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cell.textContent = newValue || 'NULL';
                    if (!newValue) {
                        cell.classList.add('null');
                    }
                    // Actualizar el dataset de la fila
                    row[column] = newValue || null;
                    cell.closest('tr').dataset.row = JSON.stringify(row);
                    
                    showNotification('Celda actualizada exitosamente', 'success');
                } else {
                    customAlert('Error al actualizar: ' + data.error, 'error');
                    cell.textContent = cell.classList.contains('null') ? 'NULL' : cell.textContent;
                }
            } catch (error) {
                customAlert('Error: ' + error.message, 'error');
                cell.textContent = cell.classList.contains('null') ? 'NULL' : cell.textContent;
            }
        }
        
        // Refresh
        function refreshSchema() {
            loadDbInfo();
            loadTables();
        }
        
        function refreshTableData(tableName, tabId) {
            loadTableData(tableName, tabId);
        }
        
        // Clear editor
        function clearEditor(tabId) {
            document.getElementById(`editor-${tabId}`).value = '';
        }
        
        // Format SQL (simple)
        function formatSQL(tabId) {
            const editor = document.getElementById(`editor-${tabId}`);
            let sql = editor.value;
            
            // Simple formatting
            sql = sql.replace(/\bSELECT\b/gi, 'SELECT');
            sql = sql.replace(/\bFROM\b/gi, '\nFROM');
            sql = sql.replace(/\bWHERE\b/gi, '\nWHERE');
            sql = sql.replace(/\bJOIN\b/gi, '\nJOIN');
            sql = sql.replace(/\bORDER BY\b/gi, '\nORDER BY');
            sql = sql.replace(/\bGROUP BY\b/gi, '\nGROUP BY');
            sql = sql.replace(/\bLIMIT\b/gi, '\nLIMIT');
            
            editor.value = sql;
        }
        
        // Mostrar estructura de tabla
        async function showTableStructure(tableName) {
            try {
                const response = await fetch(`?ajax=getTableStructure&table=${encodeURIComponent(tableName)}`);
                const data = await response.json();
                
                if (data.success) {
                    const tabId = `structure-${tableName}`;
                    
                    // Si ya existe, solo mostrarla
                    if (document.getElementById(tabId)) {
                        switchTab(tabId);
                        return;
                    }
                    
                    // Crear tab
                    const tab = document.createElement('div');
                    tab.className = 'tab';
                    tab.setAttribute('data-tab', tabId);
                    tab.innerHTML = `
                        <i class="fas fa-wrench"></i>
                        ${tableName} (estructura)
                        <span class="close-tab" onclick="closeTab(event, '${tabId}')">×</span>
                    `;
                    tab.onclick = (e) => {
                        if (!e.target.classList.contains('close-tab')) {
                            switchTab(tabId);
                        }
                    };
                    
                    document.getElementById('tabsContainer').appendChild(tab);
                    
                    // Crear contenido
                    let html = `
                        <div class="tab-content" id="${tabId}">
                            <div style="padding: 20px; overflow: auto;">
                                <h2 style="color: #4ec9b0; margin-bottom: 20px;">
                                    <i class="fas fa-table"></i> Estructura de ${tableName}
                                </h2>
                                
                                <h3 style="color: #569cd6; margin: 20px 0 10px 0;">Columnas</h3>
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Campo</th>
                                            <th>Tipo</th>
                                            <th>Null</th>
                                            <th>Key</th>
                                            <th>Default</th>
                                            <th>Extra</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    data.structure.forEach(field => {
                        html += `
                            <tr>
                                <td><strong>${escapeHtml(field.Field)}</strong></td>
                                <td>${escapeHtml(field.Type)}</td>
                                <td>${escapeHtml(field.Null)}</td>
                                <td>${field.Key ? '<span style="color: #dcdcaa;">' + escapeHtml(field.Key) + '</span>' : ''}</td>
                                <td>${field.Default !== null ? escapeHtml(field.Default) : '<span class="null">NULL</span>'}</td>
                                <td>${escapeHtml(field.Extra)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                    </tbody>
                                </table>
                                
                                <h3 style="color: #569cd6; margin: 30px 0 10px 0;">Índices</h3>
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Columna</th>
                                            <th>Único</th>
                                            <th>Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    if (data.indexes && data.indexes.length > 0) {
                        data.indexes.forEach(index => {
                            html += `
                                <tr>
                                    <td>${escapeHtml(index.Key_name)}</td>
                                    <td>${escapeHtml(index.Column_name)}</td>
                                    <td>${index.Non_unique === '0' ? '✓' : ''}</td>
                                    <td>${escapeHtml(index.Index_type)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html += '<tr><td colspan="4" style="text-align: center; color: #858585;">No hay índices</td></tr>';
                    }
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    document.querySelector('.main-panel').appendChild(document.createRange().createContextualFragment(html));
                    switchTab(tabId);
                }
            } catch (error) {
                customAlert('Error al cargar estructura: ' + error.message, 'error');
            }
        }
        
        // Mostrar alerta
        function showAlert(message, type, tabId) {
            const resultsContainer = document.getElementById(`results-${tabId}`);
            resultsContainer.innerHTML = `
                <div class="alert alert-${type}">
                    ${escapeHtml(message)}
                </div>
            `;
        }
        
        // Notificación flotante
        function showNotification(message, type) {
            // Crear notificación
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4caf50' : '#f44336'};
                color: white;
                padding: 15px 25px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                font-size: 14px;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${escapeHtml(message)}
            `;
            
            document.body.appendChild(notification);
            
            // Remover después de 3 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Agregar animaciones CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Filtrar tabla
        function filterTable(tabId) {
            const filter = document.getElementById(`filter-${tabId}`).value.toLowerCase();
            const table = document.querySelector(`#results-${tabId} .results-table`);
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }
        
        // Atajos de teclado
        function setupKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl+Enter: Ejecutar consulta
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    const activeTab = document.querySelector('.tab-content.active');
                    if (activeTab && activeTab.id.startsWith('query')) {
                        executeQuery(activeTab.id);
                    }
                }
                
                // Ctrl+N: Nueva consulta
                if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    openNewQueryTab();
                }
                
                // Ctrl+W: Cerrar tab actual
                if (e.ctrlKey && e.key === 'w') {
                    e.preventDefault();
                    const activeTab = document.querySelector('.tab.active');
                    if (activeTab && activeTab.getAttribute('data-tab') !== 'query1') {
                        closeTab(e, activeTab.getAttribute('data-tab'));
                    }
                }
            });
        }
        
        // Resize editor/results
        function initResize(e, tabId) {
            resizing = true;
            currentResizeTab = tabId;
            
            document.addEventListener('mousemove', doResize);
            document.addEventListener('mouseup', stopResize);
        }
        
        function doResize(e) {
            if (!resizing) return;
            
            const container = document.getElementById(currentResizeTab);
            const editor = container.querySelector('.sql-editor');
            const containerRect = container.getBoundingClientRect();
            const newHeight = e.clientY - editor.getBoundingClientRect().top;
            
            if (newHeight > 100 && newHeight < containerRect.height - 200) {
                editor.style.height = newHeight + 'px';
            }
        }
        
        function stopResize() {
            resizing = false;
            document.removeEventListener('mousemove', doResize);
            document.removeEventListener('mouseup', stopResize);
        }
        
        // Exportar a CSV
        async function exportToCSV(tabId) {
            const data = exportData[tabId];
            
            if (!data || data.length === 0) {
                customAlert('No hay datos para exportar', 'warning');
                return;
            }
            
            // Generar nombre del archivo
            let filename = 'export';
            if (tabId.startsWith('table-')) {
                filename = tabId.replace('table-', '');
            } else {
                filename = `query_${new Date().toISOString().slice(0, 10)}`;
            }
            
            try {
                // Método 1: Generar CSV del lado del cliente (más rápido)
                const csv = generateCSVClient(data);
                downloadCSV(csv, filename + '.csv');
                
                showNotification('CSV descargado exitosamente', 'success');
            } catch (error) {
                console.error('Error al exportar CSV:', error);
                customAlert('Error al generar el CSV: ' + error.message, 'error');
            }
        }
        
        // Generar CSV del lado del cliente
        function generateCSVClient(data) {
            if (!data || data.length === 0) {
                return '';
            }
            
            // Encabezados
            const columns = Object.keys(data[0]);
            let csv = columns.map(col => `"${String(col).replace(/"/g, '""')}"`).join(',') + '\n';
            
            // Datos
            data.forEach(row => {
                const values = columns.map(col => {
                    let value = row[col];
                    if (value === null || value === undefined) {
                        value = 'NULL';
                    }
                    value = String(value).replace(/"/g, '""');
                    return `"${value}"`;
                });
                csv += values.join(',') + '\n';
            });
            
            return csv;
        }
        
        // Descargar CSV
        function downloadCSV(csvContent, filename) {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (navigator.msSaveBlob) { // IE 10+
                navigator.msSaveBlob(blob, filename);
            } else {
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Cerrar menú contextual al hacer clic fuera
        document.addEventListener('click', function() {
            document.getElementById('contextMenu').style.display = 'none';
        });
    </script>
</body>
</html>
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
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .section h3 {
            color: #764ba2;
            margin: 20px 0 15px 0;
            font-size: 1.3em;
        }
        
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: 2px solid #ced4da;
            border-radius: 8px;
            resize: vertical;
            background: #fff;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        input[type="text"],
        input[type="number"],
        select {
            padding: 12px 15px;
            border: 2px solid #ced4da;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
            margin-bottom: 15px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-right: 10px;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button.danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        
        button.danger:hover {
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
        }
        
        button.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        
        button.warning:hover {
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .table-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .table-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-3px);
        }
        
        .table-card h4 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.1em;
            word-break: break-word;
        }
        
        .table-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .table-actions button {
            margin: 0;
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .db-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .db-info p {
            margin: 8px 0;
            color: #495057;
        }
        
        .db-info strong {
            color: #667eea;
        }
        
        .sql-examples {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #e9ecef;
        }
        
        .sql-examples h4 {
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .sql-examples code {
            display: block;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            color: #d63384;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .sql-examples code:hover {
            background: #e9ecef;
        }
        
        .scrollable-table {
            max-height: 600px;
            overflow: auto;
            border-radius: 8px;
        }
        
        .label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .inline-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .inline-form > div {
            flex: 1;
            min-width: 200px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Administrador de Base de Datos</h1>
            <p>Gestión completa de la base de datos MySQL</p>
        </div>
        
        <div class="content">
            <a href="admin_dashboard.php" class="back-link">← Volver al Dashboard</a>
            
            <?php if ($message): ?>
                <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">✗ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Información de la BD -->
            <div class="db-info">
                <p><strong>Servidor:</strong> <?php echo htmlspecialchars($servidor_ip . ':' . $puerto); ?></p>
                <p><strong>Base de datos:</strong> <?php echo htmlspecialchars($nombre_bd); ?></p>
                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario_bd); ?></p>
                <p><strong>Tablas encontradas:</strong> <?php echo count($tables); ?></p>
            </div>
            
            <!-- Ejecutar consulta SQL -->
            <div class="section">
                <h2>📝 Ejecutar Consulta SQL</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="label">Escribe tu consulta SQL:</label>
                        <textarea name="sql_query" placeholder="SELECT * FROM tu_tabla LIMIT 10"><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
                    </div>
                    <button type="submit" name="execute_query">▶ Ejecutar Consulta</button>
                </form>
                
                <div class="sql-examples">
                    <h4>Ejemplos de consultas:</h4>
                    <code onclick="document.querySelector('textarea[name=sql_query]').value = this.textContent">SELECT * FROM usuarios LIMIT 20</code>
                    <code onclick="document.querySelector('textarea[name=sql_query]').value = this.textContent">SHOW TABLES</code>
                    <code onclick="document.querySelector('textarea[name=sql_query]').value = this.textContent">DESCRIBE nombre_tabla</code>
                    <code onclick="document.querySelector('textarea[name=sql_query]').value = this.textContent">SELECT COUNT(*) as total FROM tu_tabla</code>
                    <code onclick="document.querySelector('textarea[name=sql_query]').value = this.textContent">UPDATE tabla SET columna = 'valor' WHERE id = 1</code>
                    <code onclick="document.querySelector('textarea[name=sql_query]').value = this.textContent">DELETE FROM tabla WHERE condicion</code>
                </div>
                
                <?php if ($queryResult !== null): ?>
                    <div class="scrollable-table">
                        <?php if (count($queryResult) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($queryResult[0]) as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queryResult as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="margin-top: 20px; color: #6c757d;">No se encontraron resultados.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Lista de tablas -->
            <div class="section">
                <h2>📊 Tablas de la Base de Datos</h2>
                
                <?php if (count($tables) > 0): ?>
                    <div class="tables-grid">
                        <?php foreach ($tables as $table): ?>
                            <div class="table-card">
                                <h4><?php echo htmlspecialchars($table); ?></h4>
                                <div class="table-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
                                        <button type="submit" name="view_structure">Ver Estructura</button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
                                        <input type="hidden" name="limit" value="100">
                                        <button type="submit" name="view_data">Ver Datos</button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de vaciar esta tabla? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
                                        <input type="hidden" name="confirm_truncate" value="TRUNCATE">
                                        <button type="submit" name="truncate_table" class="warning">Vaciar Tabla</button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿ELIMINAR PERMANENTEMENTE la tabla <?php echo htmlspecialchars($table); ?>? Esta acción NO se puede deshacer.');">
                                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
                                        <input type="hidden" name="confirm_drop" value="DROP">
                                        <button type="submit" name="drop_table" class="danger">Eliminar Tabla</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No se encontraron tablas en la base de datos.</p>
                <?php endif; ?>
            </div>
            
            <!-- Estructura de tabla -->
            <?php if ($tableStructure): ?>
                <div class="section">
                    <h2>🔧 Estructura de la Tabla</h2>
                    <div class="scrollable-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>Tipo</th>
                                    <th>Null</th>
                                    <th>Key</th>
                                    <th>Default</th>
                                    <th>Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableStructure as $field): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($field['Field']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($field['Type']); ?></td>
                                        <td><?php echo htmlspecialchars($field['Null']); ?></td>
                                        <td><?php echo htmlspecialchars($field['Key']); ?></td>
                                        <td><?php echo htmlspecialchars($field['Default'] ?? 'NULL'); ?></td>
                                        <td><?php echo htmlspecialchars($field['Extra']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Datos de tabla -->
            <?php if ($tableData): ?>
                <div class="section">
                    <h2>📋 Datos de la Tabla: <?php echo htmlspecialchars($tableData['name']); ?></h2>
                    
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($tableData['name']); ?>">
                        <div>
                            <label class="label">Límite de registros:</label>
                            <input type="number" name="limit" value="100" min="1" max="10000">
                        </div>
                        <div>
                            <button type="submit" name="view_data">Recargar</button>
                        </div>
                    </form>
                    
                    <?php if (count($tableData['data']) > 0): ?>
                        <div class="scrollable-table">
                            <table>
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($tableData['data'][0]) as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableData['data'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="margin-top: 20px; color: #6c757d;">La tabla está vacía.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
