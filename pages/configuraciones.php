<?php
require_once __DIR__ . '/../config/conexion.php';
// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}
// helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

  <div class="container-fluid p-4">
    <div class="row g-4">

      <!-- Única tarjeta de Configuraciones que agrupa las secciones -->
      <div class="col-12">
        <div class="card config-card h-100" style="width:700px; margin:0 auto;">
          <div class="card-header config-header-custom text-white text-center">
            <h4 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Configuraciones</h4>
          </div>
          <div class="card-body">
            <div class="d-flex flex-column gap-22">
              <a href="?page=cuenta" class="d-block config-btn">
                <i class="bi bi-person config-icon"></i>Perfil
              </a>
              <a href="?page=config_notificaciones" class="d-block config-btn">
                <i class="bi bi-bell config-icon"></i>Configuracion de Notificaciones
              </a>
              <a href="?logout=1" class="d-block config-btn">
                <i class="bi bi-box-arrow-right config-icon"></i>Cerrar Sesión
              </a>
              <a href="?page=reportar_falla" class="d-block config-btn">
                <i class="bi bi-exclamation-triangle config-icon"></i>Reportar Falla
              </a>
              <a href="#" class="d-block config-btn" data-bs-toggle="modal" data-bs-target="#terminosModal">
                <i class="bi bi-file-text config-icon"></i>Términos y Condiciones
              </a>
            </div>
           </div>
        </div>
      </div>
 
    </div>
  </div>

  <!-- Modal de Términos y Condiciones -->
  <div class="modal fade" id="terminosModal" tabindex="-1" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content terminos-modal-content">
        <div class="modal-header terminos-modal-header">
          <h5 class="modal-title" id="terminosModalLabel">
            <i class="bi bi-file-text-fill me-2"></i>Términos y Condiciones
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body terminos-modal-body">
          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-check-circle-fill me-2"></i>1. Aceptación de los Términos
            </h6>
            <p class="terminos-text mb-3">
              <strong>Aceptación Obligatoria:</strong> El uso del software o la creación de una cuenta implica la aceptación total y sin reservas de estos T&C, el Aviso de Privacidad y cualquier política adicional.
            </p>
            <p class="terminos-text">
              <strong>Capacidad Legal:</strong> El usuario declara ser mayor de edad y tener la capacidad legal para contratar. Si actúa en nombre de una empresa (Wedding Planner, proveedor, etc.), garantiza tener la autoridad para obligar a dicha entidad a cumplir estos T&C.
            </p>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-box-seam-fill me-2"></i>2. Descripción del Servicio
            </h6>
            <p class="terminos-text mb-3">
              <strong>Objeto:</strong> La plataforma proporciona un software en la nube (SaaS) para la gestión de inventarios, almacenes, control de stock, seguimiento de activos (mobiliario, decoración, etc.), gestión de proveedores y herramientas de planificación relacionadas con eventos y bodas.
            </p>
            <p class="terminos-text">
              <strong>Limitación:</strong> El servicio se proporciona "tal cual". La plataforma no garantiza la ausencia total de errores o interrupciones. El usuario es el único responsable de la exactitud de los datos introducidos.
            </p>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-person-check-fill me-2"></i>3. Cuentas de Usuario y Acceso
            </h6>
            <p class="terminos-text mb-3">
              <strong>Registro:</strong> El usuario debe proporcionar información precisa y completa.
            </p>
            <p class="terminos-text mb-3">
              <strong>Confidencialidad:</strong> El usuario es responsable de mantener la confidencialidad de su contraseña y de toda la actividad que ocurra bajo su cuenta. La plataforma no será responsable por el acceso no autorizado resultante de la negligencia del usuario.
            </p>
            <p class="terminos-text">
              <strong>Uso Personal/Empresarial:</strong> El acceso está limitado al usuario registrado (y sus empleados autorizados, si aplica según el plan contratado). Se prohíbe compartir credenciales con terceros no autorizados.
            </p>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-credit-card-fill me-2"></i>4. Tarifas, Pagos y Cancelación
            </h6>
            <p class="terminos-text mb-3">
              <strong>Planes y Precios:</strong> Se detallarán los diferentes planes de suscripción (mensual, anual) y las tarifas aplicables.
            </p>
            <p class="terminos-text mb-3">
              <strong>Facturación y Pago:</strong> Los pagos son recurrentes y se realizarán a través de un proveedor de pagos externo (ej. Stripe). El impago puede resultar en la suspensión o cancelación del servicio y el acceso a los datos.
            </p>
            <p class="terminos-text mb-3">
              <strong>Cancelación por el Usuario:</strong> Se especificará el proceso y los plazos para la cancelación. Es crucial aclarar si existen reembolsos por periodos pagados y no utilizados (lo común es que no haya reembolso).
            </p>
            <p class="terminos-text">
              <strong>Cancelación por la Plataforma:</strong> La plataforma se reserva el derecho de suspender o cancelar la cuenta por incumplimiento de los T&C (ej. impago, uso indebido).
            </p>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-shield-lock-fill me-2"></i>5. Propiedad Intelectual y Contenido
            </h6>
            <p class="terminos-text mb-3">
              <strong>Propiedad de la Plataforma:</strong> Todo el software, código, diseño, marcas, logotipos y la plataforma en sí son propiedad de la empresa. El usuario solo obtiene una licencia de uso no exclusiva y no transferible del software.
            </p>
            <p class="terminos-text mb-3">
              <strong>Propiedad del Usuario (Contenido):</strong> El usuario conserva la propiedad de los datos e inventarios que introduce en el sistema.
            </p>
            <p class="terminos-text">
              <strong>Licencia de Contenido:</strong> El usuario otorga a la plataforma una licencia limitada y global para usar, almacenar y procesar su contenido con el único fin de prestar el servicio y realizar mejoras técnicas.
            </p>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-x-circle-fill me-2"></i>6. Uso Prohibido del Servicio
            </h6>
            <p class="terminos-text mb-2">El usuario se compromete a no:</p>
            <ul class="terminos-list">
              <li class="terminos-text mb-2"><strong>Realizar Ingeniería Inversa,</strong> descompilar o intentar acceder al código fuente del software.</li>
              <li class="terminos-text mb-2"><strong>Utilizar el Servicio para Fines Ilícitos</strong> o actividades prohibidas por la ley.</li>
              <li class="terminos-text mb-2"><strong>Cargar Contenido Dañino</strong> (virus, malware) o que infrinja los derechos de propiedad intelectual de terceros.</li>
              <li class="terminos-text"><strong>Interferir</strong> with el funcionamiento o la seguridad de la plataforma.</li>
            </ul>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>7. Limitación de Responsabilidad y Garantías
            </h6>
            <p class="terminos-text mb-3">
              <strong>Ausencia de Garantía:</strong> La plataforma no garantiza que el servicio será ininterrumpido, oportuno, seguro o libre de errores.
            </p>
            <p class="terminos-text mb-3">
              <strong>Limitación de Daños:</strong> La plataforma no será responsable por daños indirectos, pérdida de datos, lucro cesante o cualquier pérdida financiera derivada del uso o la imposibilidad de usar el servicio. En caso de responsabilidad, esta se limitará al monto total pagado por el usuario en los últimos [ej. tres (3) o seis (6)] meses.
            </p>
            <p class="terminos-text">
              <strong>Diligencia del Usuario:</strong> El usuario reconoce que la herramienta es un apoyo y no exime su responsabilidad profesional en la gestión y planificación de las bodas.
            </p>
          </div>

          <div class="terminos-section mb-4">
            <h6 class="terminos-subtitle" id="punto8" style="cursor: pointer; transition: all 0.3s;" onclick="activarEasterEgg()" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
              <i class="bi bi-lock-fill me-2"></i>8. Privacidad y Protección de Datos
            </h6>
            <p class="terminos-text">
              Se establecerá un enlace al Aviso de Privacidad para detallar cómo se recopilan, usan y protegen los datos personales de los usuarios.
            </p>
          </div>

          <div class="terminos-section">
            <h6 class="terminos-subtitle">
              <i class="bi bi-file-earmark-text-fill me-2"></i>9. Modificaciones y Jurisdicción
            </h6>
            <p class="terminos-text mb-3">
              <strong>Cambios en los T&C:</strong> La plataforma se reserva el derecho de modificar estos términos. Las modificaciones serán notificadas con antelación y su uso continuado de la plataforma constituirá la aceptación de los nuevos términos.
            </p>
            <p class="terminos-text">
              <strong>Legislación Aplicable y Jurisdicción:</strong> Se especificará la legislación que regirá el contrato y la jurisdicción (los tribunales de una ciudad o país específico) en caso de disputa.
            </p>
          </div>
        </div>
        <div class="modal-footer terminos-modal-footer">
          <button type="button" class="btn btn-terminos-close" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i>Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

  
  <div class="modal fade" id="easterEggModal" tabindex="-1" aria-labelledby="easterEggModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%); border: none; border-radius: 20px; overflow: hidden;">
        <div class="modal-header" style="border: none; padding: 30px 30px 20px;">
          <h5 class="modal-title" id="easterEggModalLabel" style="color: white; font-weight: 700; font-size: 1.5em;">
            <i class="bi bi-star-fill me-2" style="animation: rotate 2s linear infinite;"></i>¡Encontraste el secreto!
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center" style="padding: 30px;">
          <div style="animation: fadeInUp 0.8s ease-out;">
            <img src="imagenes/secreto.jpg" alt="Secreto" class="img-fluid" style="max-width: 100%; height: auto; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); margin-bottom: 20px;">
            <h3 style="color: white; font-weight: 600; margin-top: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
              🎉 ¡Felicidades! 🎉
            </h3>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.1em; margin-top: 15px;">
              Has descubierto uno de los secretos mejor guardados de nuestra plataforma.
            </p>
          </div>
        </div>
        <div class="modal-footer" style="border: none; justify-content: center; padding: 20px 30px 30px;">
          <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal" style="border-radius: 15px; padding: 12px 40px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <i class="bi bi-emoji-wink me-2"></i>Genial
          </button>
        </div>
      </div>
    </div>
  </div>

  <style>
  @keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  #punto8:active {
    transform: scale(0.98) !important;
  }
  </style>

  <script>
  let clickCount = 0;
  let clickTimer = null;

  function activarEasterEgg() {
    clickCount++;
    
    // Resetear contador después de 2 segundos de inactividad
    clearTimeout(clickTimer);
    clickTimer = setTimeout(() => {
      clickCount = 0;
    }, 2000);
    
    // Si hace 8 clics, mostrar el easter egg
    if (clickCount === 8) {
      clickCount = 0;
      
      // Cerrar el modal de términos
      const terminosModal = bootstrap.Modal.getInstance(document.getElementById('terminosModal'));
      if (terminosModal) {
        terminosModal.hide();
      }
      
      // Pequeño delay para la transición
      setTimeout(() => {
        // Mostrar el modal del easter egg
        const easterEggModal = new bootstrap.Modal(document.getElementById('easterEggModal'));
        easterEggModal.show();
        
        // Efecto de confetti (opcional)
        if (typeof confetti !== 'undefined') {
          confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
          });
        }
      }, 500);
    }
  }
  </script>