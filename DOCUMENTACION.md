# 📚 DOCUMENTACIÓN TÉCNICA - MAKA

> Sistema de Gestión de Eventos (OWS - Organización de Wedding Services)  
> Versión: 2.1  
> Última actualización: 25 de Noviembre 2025

---

## 📑 ÍNDICE

1. [Visión General](#visión-general)
2. [Arquitectura](#arquitectura)
3. [Base de Datos](#base-de-datos)
4. [Autenticación y Seguridad](#autenticación-y-seguridad)
5. [Módulos Principales](#módulos-principales)
6. [Panel de Administración](#panel-de-administración)
7. [API REST](#api-rest)
8. [Sistema de Verificación](#sistema-de-verificación)
9. [Guía de Instalación](#guía-de-instalación)
10. [Problemas Conocidos](#problemas-conocidos)

---

## 🎯 VISIÓN GENERAL

### Descripción
**MAKA** es una plataforma web para la gestión integral de eventos sociales (bodas, celebraciones), que permite administrar inventario, servicios, proveedores, clientes y tracking de envíos.

### Tecnologías
- **Backend**: PHP 7.4+ con PDO
- **Frontend**: HTML5, CSS3, JavaScript Vanilla
- **Framework CSS**: Bootstrap 5.3.2
- **Base de Datos**: MySQL/MariaDB
- **Servidor**: XAMPP / Servidor remoto

### Usuarios del Sistema
1. **Administrador**: Control total del sistema, gestión de usuarios, inventario, servicios y asignación de eventos
2. **Cliente/Organizador**: Visualización y gestión de eventos asignados, acceso a catálogo y servicios

### Características Principales
- ✅ Gestión completa de eventos (crear, editar, asignar, finalizar)
- ✅ Control de inventario con stock en tiempo real
- ✅ Catálogo de servicios con proveedores
- ✅ Asignación de inventario y servicios a eventos
- ✅ Sistema de notificaciones globales
- ✅ Panel administrativo completo
- ✅ Historial de operaciones y trazabilidad
- ✅ Cronograma detallado por evento
- ✅ Generación de reportes PDF
- ✅ Verificador de conexión a base de datos
- ✅ Sistema de roles y permisos

---

## 🏗️ ARQUITECTURA

### Estructura de Directorios
```
maka/
├── config/                                    # Configuración del sistema
│   ├── conexion.php                          # Conexión PDO a MySQL
│   ├── check_connection.php                  # Verificador de conexión
│   ├── migracion_servicios.sql              # Script de migración de servicios
│   └── actualizar_imagenes_inventario.sql   # Script de actualización de imágenes
├── pages/                                     # Vistas del sistema
│   ├── dashboard.php                         # Dashboard principal (en construcción)
│   ├── eventos.php                           # Listado de eventos con filtros
│   ├── evento_detalle.php                    # Detalle completo de evento
│   ├── evento_create.php                     # Crear evento
│   ├── evento_create_new.php                 # Versión alternativa
│   ├── evento_editar.php                     # Editar evento
│   ├── evento_manage_items.php               # API de gestión de items
│   ├── catalogo.php                          # Catálogo de inventario
│   ├── servicios.php                         # Catálogo de servicios
│   ├── tracking.php                          # Seguimiento de envíos
│   ├── notificaciones.php                    # Sistema de notificaciones
│   ├── cuenta.php                            # Perfil de usuario
│   ├── cuenta_ajustes.php                    # Ajustes de cuenta
│   ├── configuraciones.php                   # Configuraciones generales
│   ├── config_notificaciones.php             # Config de notificaciones
│   ├── reportar_falla.php                    # Reporte de problemas
│   ├── admin_gestion.php                     # Panel de administración
│   ├── admin_usuarios.php                    # Gestión de usuarios
│   ├── admin_inventario.php                  # Administración de inventario
│   ├── admin_servicios.php                   # Administración de servicios
│   ├── admin_editar_servicio.php            # Editar servicio individual
│   ├── admin_historial_servicios.php        # Historial de cambios
│   ├── admin_historial_inventario.php       # Historial de inventario
│   ├── admin_historial_notificaciones.php   # Historial de notificaciones creadas
│   ├── admin_crear_notificacion.php         # Crear notificaciones globales
│   ├── admin_asignar_eventos.php            # Asignar eventos a organizadores
│   ├── admin_crear_producto.php             # Crear productos/servicios
│   ├── admin_crear_servicio.php             # Crear servicio
│   ├── admin_crear_usuario.php              # Crear usuario
│   └── admin_editar_catalogo.php            # Editar catálogo
├── js/                                        # Scripts JavaScript
│   └── custom_models.js                      # Sistema de modales personalizados (customAlert, customConfirm)
├── imagenes/                                  # Recursos gráficos
├── imagenes_producto/                         # Imágenes de productos
├── docs/                                      # Documentación adicional
│   ├── README_ADMIN_SERVICIOS.md             # Doc de admin de servicios
│   ├── README_VALIDACION_ESTADO_SERVICIOS.MD # Validación de estado de servicios
│   └── [otros archivos de documentación]    # Guías técnicas adicionales
├── index.php                                  # Front controller principal
├── login.php                                  # Autenticación
├── registro.php                               # Registro de usuarios
├── header.php                                 # Header para usuarios
├── header_admin.php                           # Header para administradores
├── api_evento_items.php                       # API REST principal
├── admin_database.php                         # Administración de BD
├── style.css                                  # Estilos consolidados (2707+ líneas)
├── db_connection_checkers.js                  # Verificador de conexión BD
├── inventario.csv                             # Datos de importación
├── servicios.csv                              # Datos de servicios
└── DOCUMENTACION.md                           # Este archivo
```

### Patrón MVC Simplificado
```
Usuario → index.php → pages/{modulo}.php → Vista
              ↓
         config/conexion.php → MySQL
              ↓
         api_evento_items.php → AJAX
```

### Enrutamiento
- **Query String**: `?page=nombre_pagina`
- **Ejemplo**: `index.php?page=eventos`
- **Front Controller**: `index.php` incluye dinámicamente `pages/$page.php`

### 🎨 Frontend y Utilidades JavaScript

#### Sistema de Modales Personalizados (`js/custom_models.js`)

El sistema implementa reemplazos modernos para `alert()` y `confirm()` nativos de JavaScript, utilizando modales de Bootstrap 5 para una mejor experiencia de usuario.

**Características:**
- ✅ Modales estilizados con Bootstrap 5
- ✅ Iconos dinámicos según tipo de mensaje
- ✅ Compatible con toda la aplicación
- ✅ Callbacks para confirmaciones
- ✅ Creación dinámica de modales

**Funciones Principales:**

##### `customAlert(message, type, title)`
Reemplazo de `alert()` con soporte de tipos:

```javascript
// Tipos disponibles: 'success', 'error', 'warning', 'info' (default)
customAlert('Operación exitosa', 'success', '¡Éxito!');
customAlert('Ha ocurrido un error', 'error', 'Error');
customAlert('Verifique los datos', 'warning', 'Advertencia');
customAlert('Información importante', 'info', 'Información');
```

**Características visuales:**
- **Success**: Icono de check verde, botón verde
- **Error**: Icono de triángulo rojo, botón rojo
- **Warning**: Icono de exclamación amarillo, botón amarillo
- **Info**: Icono de información azul, botón azul

##### `customConfirm(message, callback, title)`
Reemplazo de `confirm()` con callback:

```javascript
customConfirm(
    '¿Está seguro de eliminar este elemento?',
    function() {
        // Código a ejecutar si confirma
        console.log('Usuario confirmó');
    },
    'Confirmar eliminación'
);
```

**Uso en el proyecto:**
- Utilizado en páginas de administración para confirmar acciones
- Alertas de éxito/error después de operaciones AJAX
- Mensajes de validación de formularios
- Confirmaciones antes de eliminaciones

**Implementación técnica:**
- Los modales se crean dinámicamente al cargar la página
- Se reutilizan instancias para evitar duplicados
- Listeners se reemplazan usando clonación de nodos
- Compatible con eventos de Bootstrap

---

## 💾 BASE DE DATOS

### Configuración de Conexión

#### Servidor Principal (Escuela)
```php
$servidor_ip = "148.220.209.0";
$nombre_bd   = "makadb";
$usuario_bd  = "amigo_remoto2";
$password_bd = "1234";
$puerto      = 3307;
```

#### Servidor Alternativo (Diego)
```php
$servidor_ip = "10.70.110.58";
$puerto      = 3307;
```

### Tablas Principales

#### 1. `usuario`
Gestión de usuarios del sistema.
```sql
CREATE TABLE usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(20) NOT NULL,              -- 'cliente' | 'administrador'
    nombre_completo VARCHAR(255) NOT NULL,
    celular VARCHAR(20),
    correo VARCHAR(255) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,       -- ⚠️ Actualmente texto plano
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 2. `eventos`
Información completa de eventos.
```sql
CREATE TABLE eventos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_novio_1 VARCHAR(255),
    nombre_novio_2 VARCHAR(255),
    nombre_evento VARCHAR(255) NOT NULL,
    fecha_evento DATE NOT NULL,
    
    -- Horarios
    hora_inicio_montaje TIME,
    hora_fin_montaje TIME,
    hora_inicio_evento TIME,
    hora_fin_evento TIME,
    
    -- Ubicación
    ubicacion VARCHAR(255),
    direccion_completa TEXT,
    coordenadas_maps VARCHAR(255),
    
    -- Responsable
    nombre_responsable VARCHAR(255),
    numero_responsable VARCHAR(20),
    correo_responsable VARCHAR(255),
    
    -- Detalles
    numero_invitados INT,
    estatus VARCHAR(50) DEFAULT 'Pendiente',  -- Pendiente|Confirmado|En Proceso|Finalizado|Cancelado
    
    -- Financiero
    presupuesto_total DECIMAL(10,2),
    anticipo_pagado DECIMAL(10,2),
    saldo_pendiente DECIMAL(10,2),
    saldo_pagado DECIMAL(10,2),
    
    -- Notas
    notas_internas TEXT,
    notas_cliente TEXT,
    
    -- Media
    imagen_principal VARCHAR(255)
);
```

#### 3. `inventario`
Catálogo de productos disponibles.
```sql
CREATE TABLE inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    existencia INT DEFAULT 0,               -- Stock disponible
    material VARCHAR(100),
    medida VARCHAR(50),
    color VARCHAR(50),
    peso VARCHAR(50),
    precio DECIMAL(10,2),
    descripcion TEXT,
    nota TEXT
);
```

#### 4. `evento_inventario`
Relación entre eventos e inventario asignado.
```sql
CREATE TABLE evento_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evento_id INT NOT NULL,
    inventario_codigo VARCHAR(50) NOT NULL,
    cantidad INT NOT NULL,
    notas TEXT,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (inventario_codigo) REFERENCES inventario(codigo)
);
```

#### 5. `servicios`
Catálogo de servicios (catering, fotografía, música, etc.).

**⚠️ IMPORTANTE**: El campo `activo` fue eliminado. Ahora se utiliza el campo `estado`.

```sql
CREATE TABLE servicios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),                 -- Catering|Fotografía|Música|Decoración|etc.
    proveedor_default VARCHAR(255),
    telefono_default VARCHAR(20),
    email_default VARCHAR(255),
    costo_base DECIMAL(10,2),
    descripcion TEXT,
    estado VARCHAR(20) DEFAULT 'disponible', -- 'disponible' | 'baja_temporal' | 'baja_definitiva'
    INDEX idx_estado (estado)
);
```

**Estados de Servicio:**
- `disponible`: Servicio activo y disponible para asignación a eventos
- `baja_temporal`: Servicio temporalmente no disponible (no se puede asignar)
- `baja_definitiva`: Servicio dado de baja permanentemente (no se puede asignar)

**Validaciones:**
- ✅ Solo servicios en estado `disponible` pueden ser asignados a eventos
- ✅ Validación en frontend (botones deshabilitados) y backend
- ✅ Mensajes específicos según el estado del servicio
- ✅ Indicadores visuales en catálogo (badges de color, bordes)
```

#### 6. `evento_servicio`
Servicios contratados para cada evento.
```sql
CREATE TABLE evento_servicio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evento_id INT NOT NULL,
    servicio_id INT NOT NULL,
    proveedor VARCHAR(255) NOT NULL,
    telefono_proveedor VARCHAR(20),
    email_proveedor VARCHAR(255),
    costo_acordado DECIMAL(10,2),
    horario_servicio VARCHAR(100),
    notas_especiales TEXT,
    notas TEXT,
    confirmado BOOLEAN DEFAULT 0,
    fecha_contratacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id)
);
```

#### 7. `notificaciones`
Sistema de notificaciones globales.
```sql
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50),                       -- Info|Advertencia|Urgente
    texto TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo)
);
```

#### 8. `evento_cronograma`
Cronograma de actividades por evento.
```sql
CREATE TABLE evento_cronograma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evento_id INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    actividad VARCHAR(255) NOT NULL,
    descripcion TEXT,
    responsable VARCHAR(255),
    completado BOOLEAN DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    INDEX idx_evento (evento_id),
    INDEX idx_hora (hora_inicio)
);
```

#### 9. `operaciones_servicios`
Historial de cambios en servicios.
```sql
CREATE TABLE operaciones_servicios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    servicio_id INT NOT NULL,
    razon_motivo TEXT NOT NULL,            -- Cambios + Justificación
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    INDEX idx_servicio_id (servicio_id),
    INDEX idx_fecha (fecha)
);
```

#### 10. `operaciones_inventario` (Opcional)
Historial de cambios en inventario.
```sql
CREATE TABLE operaciones_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inventario_codigo VARCHAR(50) NOT NULL,
    razon_motivo TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventario_codigo) REFERENCES inventario(codigo) ON DELETE CASCADE,
    INDEX idx_inventario (inventario_codigo),
    INDEX idx_fecha (fecha)
);
```

### Relaciones entre Tablas

```
usuario (1) ----< (N) eventos
eventos (1) ----< (N) evento_inventario >---- (1) inventario
eventos (1) ----< (N) evento_servicio >---- (1) servicios
eventos (1) ----< (N) evento_cronograma
servicios (1) ----< (N) operaciones_servicios
inventario (1) ----< (N) operaciones_inventario
```

---

## 🔐 AUTENTICACIÓN Y SEGURIDAD

### Flujo de Login

```php
// login.php
1. Usuario ingresa correo y contraseña
2. Query: SELECT * FROM usuario WHERE correo = ? AND contrasena = ?
3. Si existe:
   - Crear sesión con datos del usuario
   - Redirigir según tipo:
     * Administrador → admin_dashboard.php
     * Cliente → index.php?page=dashboard
4. Si no existe: Mostrar error
```

### Variables de Sesión
```php
$_SESSION['logged_in']   // Boolean
$_SESSION['user_id']     // INT
$_SESSION['user_type']   // 'administrador' | 'cliente'
$_SESSION['user_name']   // VARCHAR
$_SESSION['user_email']  // VARCHAR
$_SESSION['user_phone']  // VARCHAR
```

### Protección de Páginas
```php
// Al inicio de cada página protegida
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}
```

### Medidas de Seguridad Implementadas
✅ **SQL Injection**: Protegido con PDO y prepared statements  
✅ **XSS**: Protegido con `htmlspecialchars()`  
✅ **Session Hijacking**: Validación de sesión en cada request  
⚠️ **Contraseñas**: Actualmente en texto plano (**CRÍTICO**)  
❌ **CSRF**: No implementado  
❌ **Rate Limiting**: No implementado  

### ⚠️ URGENTE: Implementar Hash de Contraseñas

**Registro:**
```php
$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$stmt->execute([$nombre, $correo, $celular, $hash, $tipo]);
```

**Login:**
```php
$stmt = $pdo->prepare("SELECT * FROM usuario WHERE correo = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['contrasena'])) {
    // Login exitoso
}
```

---

## 📦 MÓDULOS PRINCIPALES

### 1. GESTIÓN DE EVENTOS

#### Crear Evento (`pages/evento_create.php`)
**Campos principales:**
- Datos de novios (nombre_novio_1, nombre_novio_2)
- Información del evento (nombre, fecha, ubicación)
- Horarios (montaje y evento)
- Responsable (nombre, teléfono, correo)
- Presupuesto y pagos
- Notas internas y para cliente

**Proceso:**
```php
POST → Validación → INSERT INTO eventos → Redirect a ?page=eventos&saved=1
```

#### Detalle de Evento (`pages/evento_detalle.php`)
**Funcionalidades:**
- Ver todos los datos del evento
- Gestionar inventario asignado (agregar, editar, eliminar)
- Gestionar servicios contratados
- Ver presupuesto desglosado
- Acciones rápidas (llamar, ubicación, WhatsApp)
- Finalizar evento (proceso completo)

**Operaciones principales:**
```javascript
// JavaScript (AJAX)
fetch('api_evento_items.php', {
    method: 'POST',
    body: new URLSearchParams({
        action: 'add_inventario',
        evento_id: 123,
        codigo: 'SILL001',
        cantidad: 50
    })
})
```

#### Finalizar Evento
**Proceso transaccional:**
1. Validar password del usuario
2. Devolver TODO el inventario a bodega
3. Actualizar `inventario.existencia += cantidad`
4. Eliminar registros de `evento_inventario`
5. Cambiar `eventos.estatus` a 'Finalizado'
6. Agregar notas de cierre
7. Commit o Rollback

### 2. GESTIÓN DE INVENTARIO

#### Catálogo (`pages/catalogo.php`)
**Características:**
- Filtros: búsqueda por nombre, categoría
- Paginación: 50 items por página
- Grid responsive: 3 columnas (desktop), 2 (tablet), 1 (móvil)
- Modal con detalles completos

**Query con filtros:**
```php
$sql = "SELECT * FROM inventario WHERE 1=1";
if ($search_nombre) {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%$search_nombre%";
}
if ($search_categoria) {
    $sql .= " AND categoria = ?";
    $params[] = $search_categoria;
}
$sql .= " LIMIT $offset, $perPage";
```

#### Admin Inventario (`admin_inventario.php`)
**Funciones:**
- Listar todo el inventario
- Editar: nombre, descripción, cantidad, precio
- Modal de edición por producto

### 3. SISTEMA DE NOTIFICACIONES

#### Ver Notificaciones (`pages/notificaciones.php`)
```php
SELECT id, tipo, texto, fecha 
FROM notificaciones 
ORDER BY fecha DESC
```

#### Crear Notificación (Admin)
```php
INSERT INTO notificaciones (tipo, texto) 
VALUES (?, ?)
```

#### Eliminar Notificación
```php
DELETE FROM notificaciones WHERE id = ? LIMIT 1
```

### 4. TRACKING DE ENVÍOS

**Estado actual:** Simulado (no conectado a BD)

**Campos:**
- Producto
- Tipo (Entrada/Salida)
- Cantidad
- Destino
- Fecha

### 5. PERFIL Y AJUSTES DE CUENTA

#### Perfil de Usuario (`pages/cuenta.php`)
**Funcionalidades:**
- Ver información del perfil
- Mostrar datos de contacto
- Acceso a configuración de cuenta

#### Ajustes de Cuenta (`pages/cuenta_ajustes.php`)
**Descripción:**
Página que permite a los usuarios actualizar su información personal y cambiar su contraseña.

**Funcionalidades principales:**

##### 1. Actualizar Información Personal
```php
// Campos editables:
- Nombre completo
- Correo electrónico (único)
- Número de celular

// Proceso:
UPDATE usuario 
SET nombre_completo = ?, correo = ?, celular = ?
WHERE id = ?
```

##### 2. Cambio de Contraseña Seguro
**Características:**
- ✅ Requiere contraseña actual para validación
- ✅ Confirmación de nueva contraseña
- ✅ Validación de coincidencia
- ✅ Mensajes de error específicos

**Proceso de validación:**
```php
// 1. Verificar contraseña actual
$stmt = $pdo->prepare("SELECT contrasena FROM usuario WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['contrasena'] !== $contrasena_actual) {
    $_SESSION['toast_message'] = 'La contraseña actual es incorrecta';
    $_SESSION['toast_type'] = 'error';
    exit;
}

// 2. Validar que las nuevas contraseñas coincidan
if ($nueva_contrasena !== $confirmar_contrasena) {
    $_SESSION['toast_message'] = 'Las contraseñas nuevas no coinciden';
    $_SESSION['toast_type'] = 'error';
    exit;
}

// 3. Actualizar contraseña
UPDATE usuario SET contrasena = ? WHERE id = ?
```

**Mensajes del sistema:**
- ✅ **Éxito**: "Contraseña actualizada correctamente"
- ❌ **Error - Contraseña incorrecta**: "La contraseña actual es incorrecta"
- ❌ **Error - No coinciden**: "Las contraseñas nuevas no coinciden"
- ⚠️ **Advertencia**: "Todos los campos son obligatorios"

**Interfaz:**
- Formulario separado para datos personales
- Formulario independiente para cambio de contraseña
- Campos con iconos de Bootstrap Icons
- Validación en frontend y backend
- Sistema de toasts para notificaciones

⚠️ **Nota de seguridad**: Actualmente las contraseñas se guardan en texto plano. Se recomienda implementar hash con `password_hash()` y `password_verify()`.

### 6. CONFIGURACIONES GENERALES

#### Página de Configuraciones (`pages/configuraciones.php`)
**Descripción:**
Página de configuración del sistema con términos y condiciones.

**Características:**
- Términos y condiciones en secciones numeradas
- Diseño con acordeón de Bootstrap
- Estilo moderno con gradientes

**🥚 Easter Egg:**
- Activación: Hacer clic 10 veces en la "Sección 8"
- Efecto: Muestra un modal especial oculto
- Implementación: JavaScript con contador de clicks
- Reseteo automático después de mostrar

```javascript
// Lógica del easter egg
let clickCount = 0;
document.querySelector('.section-8').addEventListener('click', function() {
    clickCount++;
    if (clickCount >= 10) {
        // Mostrar modal secreto
        $('#easterEggModal').modal('show');
        clickCount = 0;
    }
});
```

---

## 🛡️ PANEL DE ADMINISTRACIÓN

### Descripción General
El panel de administración (`pages/admin_gestion.php`) proporciona acceso centralizado a todas las funciones administrativas del sistema.

### Acceso
- **Ruta:** `index.php?page=admin_gestion`
- **Requisito:** Usuario con tipo 'administrador'
- **Protección:** Redirección automática si no es admin

### Módulos Disponibles

#### 1. Ver Usuarios (`admin_usuarios.php`)
**Funcionalidades:**
- Listar todos los usuarios registrados
- Filtrar por tipo (cliente/administrador)
- Ver detalles de cuenta
- Gestionar permisos

**Tabla de información:**
- ID de usuario
- Nombre completo
- Correo electrónico
- Tipo de cuenta
- Fecha de registro

#### 2. Administrar Inventario (`admin_inventario.php`)
**Funcionalidades:**
- Búsqueda avanzada de productos
- Edición de inventario
- Actualización de cantidades
- Modificación de precios
- Gestión de categorías

**Campos editables:**
- Nombre del producto
- Descripción
- Existencia (stock)
- Precio
- Categoría
- Material, medida, color
- Imagen del producto

**Historial:**
- Tabla `operaciones_inventario` (si existe)
- Registro de cambios con timestamp
- Razón justificada de modificaciones

#### 3. Administrar Servicios (`admin_servicios.php`)
**Funcionalidades:**
- Búsqueda de servicios
- Filtros por categoría y estado
- Edición de información
- Gestión de disponibilidad

**Estados de servicio:**
- ✅ **Disponible**: Activo y asignable
- ⚠️ **Baja Temporal**: No disponible temporalmente
- ❌ **Baja Definitiva**: Descontinuado permanentemente

**Campos editables:**
- Nombre del servicio
- Código único
- Categoría
- Proveedor sugerido
- Teléfono y email
- Costo estimado
- Duración aproximada
- Descripción
- Estado

**Sistema de trazabilidad:**
```sql
CREATE TABLE operaciones_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    razon_motivo TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id)
);
```

#### 4. Editar Servicio (`admin_editar_servicio.php`)
**Proceso:**
1. Detecta automáticamente todos los cambios
2. Requiere justificación obligatoria
3. Registra en historial con timestamp
4. Muestra últimas 10 operaciones del servicio

**Formato de historial:**
```
Costo: $500.00 → $600.00 | Estado: "disponible" → "baja_temporal" | Razón: Proveedor en mantenimiento
```

#### 5. Historial de Servicios (`admin_historial_servicios.php`)
**Vista completa:**
- Todas las operaciones de todos los servicios
- Ordenadas por fecha descendente
- Filtros por servicio, categoría o fecha

**Estadísticas:**
- Total de operaciones registradas
- Número de servicios modificados
- Fecha de última modificación

#### 6. Crear Notificaciones (`admin_crear_notificacion.php`)
**Funcionalidades:**
- Crear notificaciones globales
- Asignar eventos a organizadores
- Ver listado completo de eventos

**Tipos de notificación:**
- ℹ️ **Info**: Información general
- ⚠️ **Advertencia**: Avisos importantes
- 🚨 **Urgente**: Notificaciones críticas

**Proceso de notificación:**
```php
INSERT INTO notificaciones (tipo, texto, fecha) 
VALUES (?, ?, NOW())
```

#### 6.1. Historial de Notificaciones (`admin_historial_notificaciones.php`)
**Descripción:**
Vista completa del historial de todas las notificaciones creadas en el sistema. Solo accesible para administradores.

**Funcionalidades:**
- ✅ Visualización de todas las notificaciones creadas
- ✅ Ordenadas por fecha descendente (más recientes primero)
- ✅ Estadísticas generales del sistema de notificaciones
- ✅ Diseño moderno con tarjetas (cards) y gradientes
- ✅ Sin opción de eliminar (registro permanente)

**Estadísticas mostradas:**
- 📊 Total de Notificaciones: Contador de todas las notificaciones
- 👁️ Visibles Actualmente: Cantidad de notificaciones activas
- 📅 Última Creación: Fecha de la notificación más reciente

**Interfaz:**
```php
// Cada notificación se muestra con:
- Icono según tipo (Info/Advertencia/Urgente)
- Badge de color identificador
- Texto completo de la notificación
- Fecha y hora de creación (formato: DD/MM/YYYY HH:MM)
```

**Diseño visual:**
- Header con gradiente verde (#99AA8C)
- Cards individuales por notificación
- Iconos de Bootstrap Icons
- Botón "Volver" para retornar a crear notificaciones
- Responsive con Bootstrap 5

**Query SQL:**
```php
SELECT id, tipo, texto, fecha 
FROM notificaciones 
ORDER BY fecha DESC
```

#### 7. Asignar Eventos (`admin_asignar_eventos.php`)
**Funcionalidades:**
- Listar todos los eventos
- Asignar organizador (cliente) a evento
- Reasignar eventos
- Ver estado de asignación

**Proceso:**
```php
UPDATE eventos 
SET organizador_id = ? 
WHERE id = ?
```

**Filtros de eventos:**
- Todos los eventos
- Eventos asignados
- Eventos sin asignar

**Búsqueda:**
- Por ID de evento
- Por nombre del evento
- Por nombre del responsable
- Por organizador asignado

#### 8. Crear Usuario (`admin_crear_usuario.php`)
**Campos:**
- Nombre completo
- Correo electrónico (único)
- Celular
- Contraseña
- Tipo (cliente/administrador)

⚠️ **Nota de seguridad:** Actualmente las contraseñas se guardan en texto plano. Se recomienda implementar hash con `password_hash()`.

#### 9. Crear Producto/Servicio (`admin_crear_producto.php`)
**Para Inventario:**
- Código único
- Nombre
- Categoría
- Existencia inicial
- Precio
- Material, medida, color
- Descripción y notas

**Para Servicios:**
- Código único
- Nombre
- Categoría
- Proveedor default
- Costo base
- Descripción
- Estado inicial

### Interfaz de Usuario

**Diseño consistente:**
- Gradientes en headers (#99AA8C)
- Iconos Bootstrap para identificación visual
- Cards con hover effects
- Badges de color para estados
- Responsive para todos los dispositivos

**Navegación:**
```
Dashboard → Panel Admin → [Módulo Específico]
                       ↓
                  Admin Gestion
                       ↓
        ┌──────────────┼──────────────┐
        │              │              │
    Usuarios      Inventario     Servicios
```

### Seguridad en Admin

**Verificaciones implementadas:**
```php
// En cada página admin
if (!isset($_SESSION['user_type']) || 
    $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}
```

**Protecciones:**
- ✅ Verificación de rol en todas las páginas admin
- ✅ Prepared statements para SQL
- ✅ Escape HTML con `htmlspecialchars()`
- ✅ Transacciones para integridad de datos
- ✅ Rollback automático en errores
- ✅ Logging de errores

---

## 🔌 API REST

### Archivo: `api_evento_items.php`

### Autenticación
```php
session_start();
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
```

### Endpoints - Inventario

#### 1. Obtener Inventario Disponible
```javascript
POST api_evento_items.php
{
    action: 'get_inventario_disponible'
}

Response:
{
    "success": true,
    "data": [
        {
            "codigo": "SILL001",
            "nombre": "Silla Tiffany Blanca",
            "categoria": "Mobiliario",
            "existencia": 150,
            "material": "Resina",
            "color": "Blanco",
            "medida": "45x90cm"
        }
    ]
}
```

#### 2. Agregar Inventario a Evento
```javascript
POST api_evento_items.php
{
    action: 'add_inventario',
    evento_id: 5,
    codigo: 'SILL001',
    cantidad: 50,
    notas: 'Para ceremonia exterior'
}

Proceso:
1. Verificar stock disponible
2. INSERT INTO evento_inventario
3. UPDATE inventario SET existencia = existencia - 50
4. COMMIT
```

#### 3. Actualizar Inventario de Evento
```javascript
POST api_evento_items.php
{
    action: 'update_inventario',
    evento_id: 5,
    codigo: 'SILL001',
    cantidad: 75,  // Nueva cantidad
    notas: 'Actualizado: 25 más para recepción'
}

Proceso:
1. Obtener cantidad anterior (50)
2. Calcular diferencia: 75 - 50 = 25
3. Verificar stock si diferencia > 0
4. UPDATE evento_inventario
5. UPDATE inventario SET existencia = existencia - 25
```

#### 4. Eliminar Inventario de Evento
```javascript
POST api_evento_items.php
{
    action: 'delete_inventario',
    evento_id: 5,
    codigo: 'SILL001'
}

Proceso:
1. Obtener cantidad asignada
2. DELETE FROM evento_inventario
3. UPDATE inventario SET existencia = existencia + cantidad
4. COMMIT (devuelve stock)
```

### Endpoints - Servicios

#### 5. Obtener Servicios Disponibles
**⚠️ ACTUALIZADO:** Ahora filtra solo servicios con `estado = 'disponible'`

```javascript
POST api_evento_items.php
{
    action: 'get_servicios_disponibles'
}

Query SQL:
SELECT * FROM servicios 
WHERE estado = 'disponible'  // Antes: WHERE activo = 1
ORDER BY nombre

Response:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "SERV001",
            "nombre": "Fotografía Profesional",
            "categoria": "Fotografía",
            "proveedor_default": "Foto Express",
            "costo_base": 15000.00,
            "estado": "disponible"
        }
    ]
}
```

**Validaciones:**
- ✅ Solo retorna servicios disponibles
- ✅ Excluye servicios en baja_temporal y baja_definitiva
- ✅ Usado en modales de asignación de servicios

#### 6. Agregar Servicio a Evento
**⚠️ ACTUALIZADO:** Ahora valida el estado del servicio antes de asignar

```javascript
POST api_evento_items.php
{
    action: 'add_servicio',
    evento_id: 5,
    servicio_id: 1,
    proveedor: 'Foto Express',
    telefono: '5551234567',
    email: 'contacto@fotoexpress.com',
    costo: 18000.00,
    horario: '16:00 - 23:00',
    confirmado: 1
}

Proceso de validación:
1. Verificar que el servicio existe
   SELECT estado FROM servicios WHERE id = ?
   
2. Validar estado del servicio
   if (estado !== 'disponible') {
       return error con mensaje específico
   }
   
3. Verificar que no esté ya asignado
   SELECT COUNT(*) FROM evento_servicio 
   WHERE evento_id = ? AND servicio_id = ?
   
4. Insertar si pasa validaciones
   INSERT INTO evento_servicio (...)

Response - Éxito:
{
    "success": true,
    "message": "Servicio asignado exitosamente al evento"
}

Response - Servicio en baja temporal:
{
    "success": false,
    "message": "Este servicio está en baja temporal y no puede ser asignado"
}

Response - Servicio en baja definitiva:
{
    "success": false,
    "message": "Este servicio está dado de baja y no puede ser asignado"
}

Response - Ya asignado:
{
    "success": false,
    "message": "Este servicio ya está asignado a este evento"
}
```

**Archivos con validación implementada:**
- `api_evento_items.php` (acción: `add_servicio`) - Línea ~250
- `pages/evento_manage_items.php` (acción: `add_servicio`) - Línea ~145
- `pages/servicios.php` (asignación desde catálogo) - Línea ~72
```

#### 7. Actualizar Servicio
```javascript
POST api_evento_items.php
{
    action: 'update_servicio',
    evento_id: 5,
    servicio_id: 1,
    proveedor: 'Foto Express Premium',
    costo: 20000.00,
    confirmado: 1
}
```

#### 8. Eliminar Servicio
```javascript
POST api_evento_items.php
{
    action: 'delete_servicio',
    evento_id: 5,
    servicio_id: 1
}
```

### Endpoints - Cronograma

#### 9. Agregar Actividad al Cronograma
```javascript
POST api_evento_items.php
{
    action: 'add_cronograma',
    evento_id: 5,
    hora_inicio: '08:00',
    hora_fin: '10:00',
    actividad: 'Montaje de mesas',
    descripcion: 'Montar 50 mesas redondas',
    responsable: 'Juan Pérez'
}

Response:
{
    "success": true,
    "message": "Actividad agregada al cronograma"
}
```

#### 10. Actualizar Actividad del Cronograma
```javascript
POST api_evento_items.php
{
    action: 'update_cronograma',
    id: 12,
    evento_id: 5,
    hora_inicio: '08:30',
    hora_fin: '10:30',
    actividad: 'Montaje de mesas y sillas',
    descripcion: 'Montar 50 mesas y 200 sillas',
    responsable: 'Juan Pérez',
    completado: 1
}
```

#### 11. Eliminar Actividad del Cronograma
```javascript
POST api_evento_items.php
{
    action: 'delete_cronograma',
    id: 12,
    evento_id: 5
}
```

### Endpoint Especial - Finalizar Evento

#### 12. Finalizar Evento
```javascript
POST api_evento_items.php
{
    action: 'finalizar_evento',
    evento_id: 5,
    password: 'contraseña_usuario',
    notas_cierre: 'Evento exitoso, cliente satisfecho'
}

Proceso Completo:
1. Validar password del usuario logueado
2. BEGIN TRANSACTION
3. SELECT inventario asignado
4. FOR EACH item:
     UPDATE inventario SET existencia += cantidad
5. DELETE FROM evento_inventario WHERE evento_id = 5
6. UPDATE eventos SET estatus = 'Finalizado'
7. Agregar notas de cierre
8. COMMIT

Response:
{
    "success": true,
    "message": "Evento finalizado exitosamente.\n✓ 15 items devueltos a bodega\n✓ Estado actualizado"
}
```

### Manejo de Errores
```php
try {
    // Operación
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
```

---

## 🔍 SISTEMA DE VERIFICACIÓN

### Verificador de Conexión a Base de Datos

**Archivo:** `db_connection_checkers.js`

#### Descripción
Script JavaScript que verifica automáticamente la conexión a la base de datos al cargar cualquier página del sistema.

#### Características
- ✅ Ejecución automática al cargar la página
- ✅ Verificación mediante endpoint de API
- ✅ Mensajes detallados en consola
- ✅ Información de tablas disponibles
- ✅ Diagnóstico de errores comunes
- ✅ Función manual para re-verificar

#### Uso Automático
```javascript
// Se ejecuta automáticamente al cargar
document.addEventListener('DOMContentLoaded', checkDatabaseConnection);
```

#### Uso Manual
```javascript
// En consola del navegador
recheckDB();  // Verifica nuevamente la conexión
```

#### Endpoint de Verificación
**Archivo:** `config/check_connection.php`

```php
// Respuesta exitosa
{
    "success": true,
    "message": "Conexión exitosa",
    "details": {
        "servidor": "10.70.110.58",
        "puerto": "3307",
        "base_datos": "makadb",
        "charset": "utf8mb4",
        "tablas": ["usuario", "eventos", "inventario", ...]
    },
    "timestamp": "2025-11-24 10:30:45"
}

// Respuesta con error
{
    "success": false,
    "message": "Error de conexión",
    "details": {
        "error": "SQLSTATE[HY000] [2002] Connection refused",
        "servidor": "10.70.110.58",
        "puerto": "3307"
    }
}
```

#### Diagnóstico de Errores

**Error: Connection refused**
- ✅ Verificar que XAMPP esté ejecutándose
- ✅ Verificar que MySQL esté iniciado
- ✅ Revisar el puerto (3306 para XAMPP, no 3307)

**Error: Access denied**
- ✅ Verificar usuario y contraseña en `config/conexion.php`
- ✅ Asegurar que el usuario tenga permisos remotos
- ✅ Verificar privilegios en MySQL

**Error: Unknown database**
- ✅ Verificar que la base de datos "makadb" exista
- ✅ Crear la base de datos en phpMyAdmin
- ✅ Ejecutar scripts de migración

#### Mensajes en Consola

**Conexión exitosa:**
```
🔍 DB Connection Checker
✅ CONEXIÓN EXITOSA

📊 Detalles de la conexión:
┌─────────────┬──────────────────┐
│ servidor    │ 10.70.110.58     │
│ puerto      │ 3307             │
│ base_datos  │ makadb           │
│ charset     │ utf8mb4          │
└─────────────┴──────────────────┘

📋 Tablas disponibles:
  1. usuario
  2. eventos
  3. inventario
  4. servicios
  ...

⏰ Timestamp: 2025-11-24 10:30:45
```

**Error de conexión:**
```
🔍 DB Connection Checker
❌ ERROR DE CONEXIÓN

⚠️ Detalles del error:
┌─────────┬──────────────────────────────┐
│ error   │ Connection refused           │
│ servidor│ 10.70.110.58                 │
│ puerto  │ 3307                         │
└─────────┴──────────────────────────────┘

🔧 Posibles soluciones:
  • Verifica que XAMPP esté ejecutándose
  • Verifica que MySQL esté iniciado en XAMPP
  • Revisa el puerto (3306 para XAMPP por defecto, no 3307)
```

#### Integración en Páginas

**En `index.php`:**
```html
<script src="db_connection_checkers.js"></script>
```

⚠️ **Nota:** El archivo se llama `db_connection_checkers.js` (con 's' al final), pero en `index.php` está referenciado sin la 's'. Esto causa un error 404 en consola.

**Solución:**
```html
<!-- Cambiar esto: -->
<script src="db_connection_checker.js"></script>

<!-- Por esto: -->
<script src="db_connection_checkers.js"></script>
```

#### Funciones Disponibles

```javascript
// Objeto global expuesto
window.dbConnectionChecker = {
    check: checkDatabaseConnection,      // Función principal
    recheckDB: checkDatabaseConnection   // Alias para re-verificar
};

// Shortcut global
window.recheckDB = checkDatabaseConnection;
```

### Scripts SQL de Migración

#### 1. Migración de Servicios
**Archivo:** `config/migracion_servicios.sql`

**Propósito:**
- Agregar campo `estado` a la tabla servicios
- Crear tabla `operaciones_servicios` para historial
- Actualizar servicios existentes

**Ejecutar:**
```sql
SOURCE config/migracion_servicios.sql;
```

**Contenido:**
```sql
-- Agregar campo estado
ALTER TABLE servicios 
ADD COLUMN estado VARCHAR(20) DEFAULT 'disponible' 
COMMENT 'disponible, baja_temporal, baja_definitiva';

-- Tabla de historial
CREATE TABLE IF NOT EXISTS operaciones_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    razon_motivo TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_servicio_id (servicio_id),
    INDEX idx_fecha (fecha),
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE
);

-- Actualizar servicios existentes
UPDATE servicios 
SET estado = 'disponible' 
WHERE estado IS NULL OR estado = '';
```

#### 2. Actualización de Imágenes
**Archivo:** `config/actualizar_imagenes_inventario.sql`

**Propósito:**
- Actualizar rutas de imágenes de productos
- Formato: `imagenes_producto/{codigo}.jpg`

**Ejecutar:**
```sql
SOURCE config/actualizar_imagenes_inventario.sql;
```

**Contenido:**
```sql
UPDATE inventario 
SET imagen = CONCAT('imagenes_producto/', codigo, '.jpg')
WHERE imagen IS NULL OR imagen = '';
```

---

## 🚀 GUÍA DE INSTALACIÓN

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o MariaDB 10.3+
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, pdo_mysql, mbstring

### Paso 1: Clonar/Descargar Proyecto
```bash
git clone https://github.com/andyyquiterio/maka.git
cd maka
```

### Paso 2: Configurar Base de Datos

#### Crear Base de Datos
```sql
CREATE DATABASE makadb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Ejecutar Script SQL
⚠️ **NOTA**: Actualmente no existe archivo SQL. Se debe crear basándose en la estructura documentada.

**Crear archivo:** `database_schema.sql`

```sql
-- Ejecutar en MySQL
SOURCE database_schema.sql;
```

### Paso 3: Configurar Conexión

**Editar:** `config/conexion.php`

```php
$servidor_ip = "localhost";     // Cambiar según tu servidor
$nombre_bd   = "makadb";
$usuario_bd  = "root";           // Usuario MySQL
$password_bd = "";               // Contraseña MySQL
$puerto      = 3306;             // Puerto estándar MySQL
```

### Paso 4: Configurar Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Paso 5: Permisos
```bash
chmod -R 755 /ruta/al/proyecto
chmod -R 777 imagenes/        # Carpeta de uploads
chmod -R 777 logs/            # Si existe
```

### Paso 6: Crear Usuario Administrador

**Opción 1: SQL Manual**
```sql
INSERT INTO usuario (tipo, nombre_completo, celular, correo, contrasena)
VALUES ('administrador', 'Admin Sistema', '5551234567', 'admin@maka.com', '12345678');
```

**Opción 2: Usar registro.php**
1. Registrarse como cliente
2. Actualizar manualmente a administrador:
```sql
UPDATE usuario SET tipo = 'administrador' WHERE correo = 'tu@email.com';
```

### Paso 7: Verificar Instalación

1. **Verificar conexión BD:**
   - Abrir navegador
   - Ir a: `http://tudominio.com/config/check_connection.php`
   - Debe mostrar JSON con `"success": true`

2. **Login:**
   - Ir a: `http://tudominio.com/login.php`
   - Usar credenciales creadas

3. **Verificar consola del navegador:**
   - Abrir DevTools (F12)
   - Debe mostrar: "✅ CONEXIÓN EXITOSA"

---

## 🐛 PROBLEMAS CONOCIDOS

### 🔴 CRÍTICOS

1. **Contraseñas en Texto Plano**
   - **Problema:** Contraseñas sin cifrar en BD
   - **Impacto:** Alto riesgo de seguridad
   - **Solución:** Implementar `password_hash()` y `password_verify()`
   - **Prioridad:** URGENTE

2. **Falta Script SQL**
   - **Problema:** No existe `database_schema.sql`
   - **Impacto:** Dificultad para instalar/migrar
   - **Solución:** Crear script completo con todas las tablas
   - **Prioridad:** Alta

3. **Dashboard Incompleto**
   - **Problema:** `pages/dashboard.php` solo muestra mensaje de construcción
   - **Impacto:** Página principal no funcional
   - **Solución:** Implementar dashboard con estadísticas
   - **Prioridad:** Alta

4. **Tracking Desconectado**
   - **Problema:** `tracking.php` no guarda en BD
   - **Impacto:** Funcionalidad no operativa
   - **Solución:** Conectar a tabla de BD
   - **Prioridad:** Media

5. **Referencia JS Incorrecta**
   - **Problema:** `index.php` referencia `db_connection_checker.js` (sin 's')
   - **Archivo real:** `db_connection_checkers.js` (con 's')
   - **Impacto:** Error 404 en consola, verificador no funciona
   - **Solución:** Corregir línea en `index.php`:
   ```html
   <!-- Cambiar -->
   <script src="db_connection_checker.js"></script>
   <!-- Por -->
   <script src="db_connection_checkers.js"></script>
   ```
   - **Prioridad:** Media

### 🟡 MEJORAS RECOMENDADAS

6. **Sin Sistema de Logs**
   - Implementar logging estructurado
   - Crear carpeta `logs/` con rotación

7. **Sin Recuperación de Contraseña**
   - Implementar reset por email
   - Token temporal con expiración

8. **Upload de Imágenes**
   - Para eventos (foto principal)
   - Para productos de inventario
   - Validación de tipo y tamaño

9. **Validaciones de Formulario**
   - Reforzar validaciones server-side
   - Implementar clase de validación

10. **Notificaciones Toast**
    - Reemplazar `alert()` JavaScript
    - Usar librería como Toastr o SweetAlert

---

## 📞 SOPORTE Y CONTACTO

### Repositorio
- **GitHub:** https://github.com/andyyquiterio/maka
- **Branch principal:** main

### Desarrolladores
- Andy Quiterio (Owner)
- [Agregar más colaboradores]

### Documentación Adicional
- Esta documentación es la versión condensada
- Para documentación completa, consultar archivo extenso (próximamente)

---

## 📝 NOTAS FINALES

### Para Desarrolladores
1. **Antes de hacer cambios importantes:**
   - Crear branch: `git checkout -b feature/nombre-funcionalidad`
   - Hacer commit frecuentemente
   - Pull request para revisión

2. **Convenciones de código:**
   - Usar español para nombres de variables en BD
   - Usar inglés para funciones y métodos
   - Comentar código complejo

3. **Testing:**
   - Probar en navegadores: Chrome, Firefox, Edge
   - Probar en dispositivos móviles
   - Verificar funcionalidad con diferentes roles

### Para Administradores del Sistema
1. **Backup regular de BD:**
   ```bash
   mysqldump -u usuario -p makadb > backup_$(date +%Y%m%d).sql
   ```

2. **Monitoreo:**
   - Revisar logs de error PHP
   - Monitorear uso de BD
   - Verificar espacio en disco

3. **Actualizaciones:**
   - Mantener PHP actualizado
   - Actualizar Bootstrap cuando sea necesario
   - Revisar actualizaciones de seguridad

---

## 📋 ESTRUCTURA DE BASE DE DATOS COMPLETA

### Script SQL Completo Recomendado

```sql
-- Base de datos
CREATE DATABASE IF NOT EXISTS makadb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE makadb;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    celular VARCHAR(20),
    correo VARCHAR(255) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'imagenes/headerimg.jpg',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de eventos
CREATE TABLE IF NOT EXISTS eventos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organizador_id INT DEFAULT NULL,
    nombre_novio_1 VARCHAR(255),
    nombre_novio_2 VARCHAR(255),
    nombre_evento VARCHAR(255) NOT NULL,
    fecha_evento DATE NOT NULL,
    hora_inicio_montaje TIME,
    hora_fin_montaje TIME,
    hora_inicio_evento TIME,
    hora_fin_evento TIME,
    ubicacion VARCHAR(255),
    direccion_completa TEXT,
    coordenadas_maps VARCHAR(255),
    nombre_responsable VARCHAR(255),
    numero_responsable VARCHAR(20),
    correo_responsable VARCHAR(255),
    numero_invitados INT,
    estatus VARCHAR(50) DEFAULT 'Pendiente',
    presupuesto_total DECIMAL(10,2),
    anticipo_pagado DECIMAL(10,2),
    saldo_pendiente DECIMAL(10,2),
    saldo_pagado DECIMAL(10,2),
    notas_internas TEXT,
    notas_cliente TEXT,
    imagen_principal VARCHAR(255) DEFAULT 'imagenes/cover (1).jpg',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizador_id) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_fecha_evento (fecha_evento),
    INDEX idx_estatus (estatus),
    INDEX idx_organizador (organizador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de inventario
CREATE TABLE IF NOT EXISTS inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    existencia INT DEFAULT 0,
    material VARCHAR(100),
    medida VARCHAR(50),
    color VARCHAR(50),
    peso VARCHAR(50),
    precio DECIMAL(10,2),
    descripcion TEXT,
    nota TEXT,
    imagen VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_categoria (categoria),
    INDEX idx_existencia (existencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de relación evento-inventario
CREATE TABLE IF NOT EXISTS evento_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evento_id INT NOT NULL,
    inventario_codigo VARCHAR(50) NOT NULL,
    cantidad INT NOT NULL,
    notas TEXT,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (inventario_codigo) REFERENCES inventario(codigo) ON DELETE CASCADE,
    INDEX idx_evento (evento_id),
    INDEX idx_inventario (inventario_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de servicios
CREATE TABLE IF NOT EXISTS servicios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    proveedor_default VARCHAR(255),
    telefono_default VARCHAR(20),
    email_default VARCHAR(255),
    costo_base DECIMAL(10,2),
    descripcion TEXT,
    duracion_aproximada VARCHAR(100),
    activo BOOLEAN DEFAULT 1,
    estado VARCHAR(20) DEFAULT 'disponible',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_categoria (categoria),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de relación evento-servicio
CREATE TABLE IF NOT EXISTS evento_servicio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evento_id INT NOT NULL,
    servicio_id INT NOT NULL,
    proveedor VARCHAR(255) NOT NULL,
    telefono_proveedor VARCHAR(20),
    email_proveedor VARCHAR(255),
    costo_acordado DECIMAL(10,2),
    horario_servicio VARCHAR(100),
    notas_especiales TEXT,
    notas TEXT,
    confirmado BOOLEAN DEFAULT 0,
    fecha_contratacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    INDEX idx_evento (evento_id),
    INDEX idx_servicio (servicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de cronograma de eventos
CREATE TABLE IF NOT EXISTS evento_cronograma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evento_id INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    actividad VARCHAR(255) NOT NULL,
    descripcion TEXT,
    responsable VARCHAR(255),
    completado BOOLEAN DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    INDEX idx_evento (evento_id),
    INDEX idx_hora (hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50),
    texto TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de historial de operaciones de servicios
CREATE TABLE IF NOT EXISTS operaciones_servicios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    servicio_id INT NOT NULL,
    razon_motivo TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    INDEX idx_servicio_id (servicio_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de historial de operaciones de inventario (opcional)
CREATE TABLE IF NOT EXISTS operaciones_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inventario_codigo VARCHAR(50) NOT NULL,
    razon_motivo TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventario_codigo) REFERENCES inventario(codigo) ON DELETE CASCADE,
    INDEX idx_inventario (inventario_codigo),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🎯 ROADMAP Y MEJORAS FUTURAS

### Corto Plazo (1-2 semanas)
1. ✅ Implementar `password_hash()` en registro y login
2. ✅ Corregir referencia a `db_connection_checkers.js`
3. ✅ Completar dashboard con estadísticas
4. ✅ Conectar sistema de tracking a BD

### Mediano Plazo (1 mes)
1. ⏳ Implementar sistema de recuperación de contraseñas
2. ⏳ Agregar upload de imágenes para eventos
3. ⏳ Sistema de notificaciones push en tiempo real
4. ⏳ Mejorar generación de PDFs con más detalles

### Largo Plazo (3 meses)
1. 📋 API RESTful completa con autenticación JWT
2. 📋 App móvil con React Native
3. 📋 Sistema de reportes avanzados
4. 📋 Integración con pasarelas de pago
5. 📋 Módulo de firmas electrónicas
6. 📋 Sistema de chat en tiempo real

---

## 🏆 MEJORES PRÁCTICAS IMPLEMENTADAS

### Código
- ✅ Separación de concerns (MVC simplificado)
- ✅ Reutilización de código (funciones helper)
- ✅ Prepared statements para todas las queries
- ✅ Escape de HTML en todas las salidas
- ✅ Manejo de errores con try-catch
- ✅ Logging de errores

### Base de Datos
- ✅ Uso de índices para optimización
- ✅ Foreign keys para integridad referencial
- ✅ Charset UTF8MB4 para soporte completo
- ✅ Transacciones para operaciones críticas
- ✅ Timestamps automáticos

### UX/UI
- ✅ Diseño responsive
- ✅ Feedback visual (toasts, alerts)
- ✅ Iconografía consistente
- ✅ Navegación intuitiva
- ✅ Confirmaciones para acciones críticas

### Seguridad
- ✅ Verificación de sesión en todas las páginas
- ✅ Verificación de roles
- ✅ Protección contra SQL Injection
- ✅ Protección contra XSS
- ⚠️ Pendiente: CSRF tokens
- ⚠️ Pendiente: Rate limiting

---

## 📞 SOPORTE Y CONTACTO

### Repositorio
- **GitHub:** https://github.com/andyyquiterio/maka
- **Branch principal:** main
- **Último commit:** 24 de Noviembre 2025

### Equipo de Desarrollo
- **Owner:** Andy Quiterio (@andyyquiterio)
- **Colaboradores:** Ver GitHub contributors

### Reportar Problemas
1. Usar el sistema de Issues en GitHub
2. Incluir:
   - Descripción detallada del problema
   - Pasos para reproducir
   - Screenshots si aplica
   - Versión del navegador
   - Mensajes de error

### Contribuir
1. Fork del repositorio
2. Crear branch: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -m 'Agregar nueva funcionalidad'`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request

---

## 📚 RECURSOS ADICIONALES

### Documentación Externa
- [PHP PDO Documentation](https://www.php.net/manual/es/book.pdo.php)
- [Bootstrap 5.3 Docs](https://getbootstrap.com/docs/5.3/)
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [jsPDF Documentation](https://github.com/parallax/jsPDF)

### Documentación Interna
- `docs/README_ADMIN_SERVICIOS.md` - Gestión de servicios
- `DOCUMENTACION.md` - Este archivo (documentación principal)

### Tutoriales Útiles
- Implementación de password_hash en PHP
- Subida de archivos segura en PHP
- Generación de PDFs avanzados
- Integración de Google Maps API
- WebSockets para notificaciones en tiempo real

---

**Versión del documento:** 2.0  
**Fecha:** 24 de Noviembre de 2025  
**Estado:** Documentación completa y actualizada  
**Autor:** Sistema MAKA - Documentación generada y actualizada por el equipo de desarrollo

---

## 🙏 AGRADECIMIENTOS

Gracias a todos los que han contribuido al desarrollo de MAKA:
- Equipo de desarrollo
- Beta testers
- Usuarios que reportan problemas
- Comunidad open source

**¡Gracias por usar MAKA!** 🎉

---

## 📊 RESUMEN EJECUTIVO

### Estadísticas del Proyecto

| Métrica | Valor |
|---------|-------|
| **Líneas de código CSS** | 2,707+ |
| **Archivos PHP** | 40+ |
| **Tablas en BD** | 10 |
| **Endpoints API** | 12 |
| **Módulos Admin** | 9 |
| **Páginas de usuario** | 15+ |

### Tecnologías Utilizadas

```
Frontend:
├── HTML5
├── CSS3 (Custom)
├── JavaScript (Vanilla)
├── Bootstrap 5.3.2
└── Bootstrap Icons 1.11.1

Backend:
├── PHP 7.4+
├── PDO (MySQL)
└── Session Management

Base de Datos:
├── MySQL 5.7+ / MariaDB 10.3+
├── 10 Tablas principales
└── Relaciones con Foreign Keys

Librerías:
├── jsPDF 2.5.1 (Generación PDF)
├── html2canvas 1.4.1 (Capturas)
└── Google Maps API (Mapas)

Herramientas:
├── XAMPP (Desarrollo local)
├── Git (Control de versiones)
└── GitHub (Repositorio)
```

### Características Destacadas

| Característica | Estado |
|----------------|--------|
| Gestión de eventos | ✅ Completo |
| Control de inventario | ✅ Completo |
| Catálogo de servicios | ✅ Completo |
| Sistema de cronograma | ✅ Completo |
| Panel de administración | ✅ Completo |
| API REST | ✅ Completo |
| Generación de PDF | ✅ Completo |
| Notificaciones | ✅ Completo |
| Historial de cambios | ✅ Completo |
| Verificador de BD | ✅ Completo |
| Dashboard | ⚠️ En construcción |
| Sistema de tracking | ⚠️ Simulado |
| Hash de contraseñas | ❌ Pendiente |
| Recuperar contraseña | ❌ Pendiente |
| Upload de imágenes | ❌ Pendiente |

### Flujo de Trabajo Típico

```
1. ADMINISTRADOR CREA EVENTO
   ├── Ingresar datos básicos
   ├── Establecer horarios
   ├── Definir presupuesto
   └── Guardar evento

2. ASIGNAR ORGANIZADOR
   ├── Admin asigna cliente
   └── Cliente recibe acceso

3. PLANIFICAR EVENTO
   ├── Agregar inventario
   ├── Contratar servicios
   ├── Definir cronograma
   └── Agregar notas

4. DÍA DEL EVENTO
   ├── Revisar cronograma
   ├── Verificar inventario
   ├── Confirmar servicios
   └── Ejecutar plan

5. FINALIZAR EVENTO
   ├── Devolver inventario
   ├── Actualizar pagos
   ├── Cambiar estado
   └── Cerrar evento
```



### Rendimiento

- **Paginación:** 50 items por página
- **Índices en BD:** Optimizado para búsquedas
- **Carga de recursos:** CDN para Bootstrap
- **Caché:** Busting con filemtime()
- **Imágenes:** Optimización manual requerida

---

## 📝 CHANGELOG

### Versión 2.1 (25 Nov 2025) - Mejoras de UX y Validaciones
**🎨 Frontend y UX**
- ✨ Sistema de modales personalizados (`js/custom_models.js`)
  - Reemplazo de `alert()` y `confirm()` nativos
  - Modales con Bootstrap 5 y iconos dinámicos
  - Soporte de tipos: success, error, warning, info
  - Callbacks para confirmaciones
- ✨ Easter Egg en página de configuraciones
  - Activación con 10 clicks en "Sección 8"
  - Modal especial oculto

**🔐 Seguridad y Validaciones**
- ✨ Sistema de cambio de contraseña mejorado (`cuenta_ajustes.php`)
  - Validación de contraseña actual obligatoria
  - Confirmación de nueva contraseña
  - Mensajes de error específicos
  - Formularios separados para datos y contraseña
- ✨ Validación de estado de servicios
  - Campo `activo` eliminado, reemplazado por `estado`
  - Estados: disponible, baja_temporal, baja_definitiva
  - Validación en backend antes de asignar servicios
  - Indicadores visuales en frontend (badges, bordes de color)
  - Botones deshabilitados para servicios no disponibles

**📊 Panel de Administración**
- ✨ Historial de Notificaciones (`admin_historial_notificaciones.php`)
  - Vista completa de notificaciones creadas
  - Estadísticas del sistema (total, visibles, última creación)
  - Diseño moderno con gradientes y cards
  - Ordenación por fecha descendente
  - Sin opción de eliminar (registro permanente)

**🔌 API y Backend**
- ⚡ Actualización de endpoints de servicios
  - `get_servicios_disponibles`: Filtra por `estado = 'disponible'`
  - `add_servicio`: Validación de estado antes de asignar
  - Mensajes de error específicos según estado del servicio
- ⚡ Archivos actualizados con validaciones:
  - `api_evento_items.php` (línea ~234, ~250)
  - `pages/evento_manage_items.php` (línea ~130, ~145)
  - `pages/servicios.php` (línea ~72, ~265)

**📚 Documentación**
- 📖 Nueva sección de Frontend y Utilidades JavaScript
- 📖 Documentación de sistema de modales personalizados
- 📖 Documentación de validación de servicios
- 📖 Actualización de estructura de archivos
- 📖 README_VALIDACION_ESTADO_SERVICIOS.MD en docs/

**🔧 Mejoras Técnicas**
- 🔄 Migración de campo `activo` a `estado` en tabla servicios
- 🔄 Queries actualizadas para usar campo `estado`
- 🔄 Mejoras visuales en catálogo de servicios
- 🔄 Sistema de toasts para notificaciones de usuario

### Versión 2.0 (24 Nov 2025)
- ✨ Sistema completo de administración de servicios
- ✨ Historial de operaciones con trazabilidad
- ✨ Asignación de eventos a organizadores
- ✨ Cronograma detallado por evento
- ✨ Verificador de conexión a BD
- ✨ Generación avanzada de PDFs
- 🐛 Correcciones múltiples de bugs
- 📚 Documentación completa actualizada

### Versión 1.0 (Nov 2025)
- ✨ Release inicial
- ✨ Gestión básica de eventos
- ✨ Control de inventario
- ✨ Catálogo de servicios
- ✨ Sistema de notificaciones
- ✨ Panel administrativo básico
- ✨ API REST inicial

---

**FIN DE LA DOCUMENTACIÓN**

Para más información o soporte, visitar:
- GitHub: https://github.com/andyyquiterio/maka
- Issues: https://github.com/andyyquiterio/maka/issues

---

*Documentación generada y actualizada por el equipo de desarrollo de MAKA*
*© 2025 - Sistema OWS MAKA - Todos los derechos reservados*
