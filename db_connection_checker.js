/**
 * DB Connection Checker
 * Verifica el estado de la conexión a la base de datos
 * Uso: Se ejecuta automáticamente al cargar la página
 */

(function() {
    'use strict';
    
    const DB_CHECK_URL = 'config/check_connection.php';
    
    // Estilos para la consola
    const styles = {
        success: 'background: #10b981; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;',
        error: 'background: #ef4444; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;',
        info: 'background: #3b82f6; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;',
        warning: 'background: #f59e0b; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;',
        header: 'font-size: 16px; font-weight: bold; color: #1f2937;'
    };
    
    /**
     * Verifica la conexión a la base de datos
     */
    async function checkDatabaseConnection() {
        console.log('%c🔍 DB Connection Checker', styles.header);
        console.log('%cIniciando verificación de conexión...', styles.info);
        console.log('─'.repeat(60));
        
        try {
            const response = await fetch(DB_CHECK_URL, {
                method: 'GET',
                cache: 'no-cache'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Mostrar resultado
            if (data.success) {
                console.log('%c✅ CONEXIÓN EXITOSA', styles.success);
                console.log('\n📊 Detalles de la conexión:');
                console.table(data.details);
                
                if (data.details.tablas && data.details.tablas.length > 0) {
                    console.log('\n📋 Tablas disponibles:');
                    data.details.tablas.forEach((tabla, index) => {
                        console.log(`  ${index + 1}. ${tabla}`);
                    });
                }
                
                console.log('\n⏰ Timestamp:', data.timestamp);
                
            } else {
                console.log('%c❌ ERROR DE CONEXIÓN', styles.error);
                console.log('\n⚠️ Detalles del error:');
                console.table(data.details);
                console.log('\n💡 Mensaje:', data.message);
                
                // Sugerencias de solución
                console.log('\n🔧 Posibles soluciones:');
                if (data.details.error && data.details.error.includes('Connection refused')) {
                    console.log('  • Verifica que XAMPP esté ejecutándose');
                    console.log('  • Verifica que MySQL esté iniciado en XAMPP');
                    console.log('  • Revisa el puerto (3306 para XAMPP por defecto, no 3307)');
                } else if (data.details.error && data.details.error.includes('Access denied')) {
                    console.log('  • Verifica el usuario y contraseña en config/conexion.php');
                    console.log('  • Asegúrate que el usuario tenga permisos remotos');
                } else if (data.details.error && data.details.error.includes('Unknown database')) {
                    console.log('  • Verifica que la base de datos "makadb" exista');
                    console.log('  • Crea la base de datos en phpMyAdmin');
                }
            }
            
            console.log('─'.repeat(60));
            
            // Exponer función global para verificar manualmente
            window.recheckDB = checkDatabaseConnection;
            console.log('\n💡 Tip: Ejecuta recheckDB() para verificar nuevamente');
            
            return data;
            
        } catch (error) {
            console.log('%c❌ ERROR FATAL', styles.error);
            console.error('Error al verificar conexión:', error);
            console.log('\n⚠️ No se pudo contactar con el servidor de verificación');
            console.log('Asegúrate que config/check_connection.php existe y es accesible');
            console.log('─'.repeat(60));
            
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Verificar automáticamente al cargar la página
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkDatabaseConnection);
    } else {
        checkDatabaseConnection();
    }
    
    /**
     * También exponer en el objeto window para uso manual
     */
    window.dbConnectionChecker = {
        check: checkDatabaseConnection,
        recheckDB: checkDatabaseConnection
    };
    
})();
