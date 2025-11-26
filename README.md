# MAKA Wedding

Sistema web para la gestión integral de eventos sociales (bodas, celebraciones), permitiendo administrar inventario, servicios, proveedores, clientes y seguimiento de operaciones.

---

## ¿Qué es MAKA Wedding?

MAKA Wedding es una plataforma desarrollada para facilitar la organización y administración de eventos, especialmente bodas, abarcando desde la gestión de inventario y servicios hasta la asignación de responsables y la generación de reportes. El sistema está pensado para equipos administrativos y organizadores que requieren control total sobre cada aspecto logístico y operativo de sus eventos.

---

## Módulos principales

- **Gestión de eventos:** Crear, editar, asignar y finalizar eventos con cronogramas, presupuestos y responsables.
- **Inventario:** Control de stock en tiempo real, historial de movimientos, asignación de ítems a eventos y alertas de disponibilidad.
- **Catálogo de servicios:** Registro y administración de servicios, proveedores, precios y disponibilidad.
- **Notificaciones:** Sistema de alertas y recordatorios para usuarios y administradores.
- **Panel administrativo:** Gestión de usuarios, roles y permisos, historial de operaciones y trazabilidad.
- **Reportes:** Generación de informes PDF y exportación de datos relevantes.
- **API REST:** Endpoints para integración y automatización de procesos.

---

## Tecnologías utilizadas

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5
- **Backend:** PHP 7.4+, PDO (MySQL)
- **Base de datos:** MySQL/MariaDB, 13 tablas principales, relaciones con claves foráneas
- **Librerías:** jsPDF, html2canvas, Google Maps API
- **Herramientas:** XAMPP, Git & GitHub, InfinityFree Hosting

---

## Estructura del proyecto

```
maka/
├── config/                # Configuración y conexión a base de datos
├── pages/                 # Vistas y módulos principales (eventos, inventario, servicios, usuarios)
├── js/                    # Scripts JavaScript personalizados
├── imagenes/              # Recursos gráficos
├── imagenes_producto/     # Imágenes de productos
├── DOCUMENTACION.md       # Documentación técnica completa
├── style.css              # Estilos personalizados
├── index.php              # Front controller principal
├── login.php              # Autenticación de usuarios
├── registro.php           # Registro de usuarios
└── ...otros archivos
```

---

## Funcionamiento general

1. **Administradores** pueden crear y gestionar eventos, asignar inventario y servicios, controlar el estado y responsables, y acceder a reportes.
2. **Organizadores/Clientes** visualizan y gestionan sus eventos asignados, consultan el catálogo y reciben notificaciones.
3. El sistema mantiene trazabilidad de todas las operaciones, permitiendo auditoría y control histórico.
4. La API REST permite integrar el sistema con otras herramientas o automatizar procesos.

---

## Instalación básica

1. Clonar el repositorio y configurar la base de datos (ver `DOCUMENTACION.md` para estructura recomendada).
2. Editar credenciales en `config/conexion.php`.
3. Configurar el servidor web y permisos de carpetas.
4. Crear el usuario administrador y verificar la instalación.

---

## Contacto y soporte

- Repositorio: [GitHub](https://github.com/andyyquiterio/maka)
- Documentación técnica: `DOCUMENTACION.md`
- Para dudas o reportes, usar Issues en GitHub o contactar vía [Portfolio](https://memoluche.github.io/index.html#contact)

---

© 2025 Guillermo Gordillo y equipo MAKA. Todos los derechos reservados.