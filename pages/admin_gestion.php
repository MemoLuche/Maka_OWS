<?php
// Verificar que sea administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ?page=dashboard');
    exit;
}
?>

<div class="container-fluid py-4 admin-gestion-page">
    <!-- Encabezado con diseño mejorado -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-11">
            <div class="card shadow-lg" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); overflow: hidden;">
                <div class="card-body p-4 text-center">
                    <div class="mb-2" style="width: 80px; height: 80px; margin: 0 auto; background: rgba(255,255,255,0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-shield-check" style="font-size: 2.5rem; color: white;"></i>
                    </div>
                    <h1 class="mb-2" style="color: white; font-weight: 700; font-size: 2.2rem;">
                        Panel de Administración
                    </h1>
                    <p class="mb-0" style="color: rgba(255,255,255,0.95); font-size: 1.05rem;">
                        Gestión centralizada del sistema y control de recursos
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Opciones de administración con diseño moderno -->
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="row g-4">
              
              <!-- Ver Todos los Usuarios -->
              <div class="col-md-6 col-lg-4">
                <a href="index.php?page=admin_usuarios" class="text-decoration-none">
                  <div class="card admin-option-card h-100 shadow-sm" style="border: none; border-radius: 18px; background: white; transition: all 0.3s ease; overflow: hidden;">
                    <div class="card-body text-center p-4">
                      <div class="icon-wrapper mb-3" style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(23,162,184,0.3);">
                        <i class="bi bi-people-fill" style="font-size: 2.2rem; color: white;"></i>
                      </div>
                      <h4 class="card-title mb-2" style="color: #333; font-weight: 700; font-size: 1.3rem;">Ver Usuarios</h4>
                      <p class="card-text text-muted mb-0" style="font-size: 0.95rem;">Administra todas las cuentas registradas</p>
                    </div>
                    <div class="card-footer" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: none; padding: 12px; text-align: center;">
                      <span style="color: #17a2b8; font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-arrow-right-circle me-1"></i>Acceder
                      </span>
                    </div>
                  </div>
                </a>
              </div>

              <!-- Crear Notificaciones -->
              <div class="col-md-6 col-lg-4">
                <a href="index.php?page=admin_crear_notificacion" class="text-decoration-none">
                  <div class="card admin-option-card h-100 shadow-sm" style="border: none; border-radius: 18px; background: white; transition: all 0.3s ease; overflow: hidden;">
                    <div class="card-body text-center p-4">
                      <div class="icon-wrapper mb-3" style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(255,193,7,0.3);">
                        <i class="bi bi-bell-fill" style="font-size: 2.2rem; color: white;"></i>
                      </div>
                      <h4 class="card-title mb-2" style="color: #333; font-weight: 700; font-size: 1.3rem;">Crear Notificaciones</h4>
                      <p class="card-text text-muted mb-0" style="font-size: 0.95rem;">Envía mensajes a todos los usuarios</p>
                    </div>
                    <div class="card-footer" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: none; padding: 12px; text-align: center;">
                      <span style="color: #ffc107; font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-arrow-right-circle me-1"></i>Acceder
                      </span>
                    </div>
                  </div>
                </a>
              </div>

              <!-- Editar Inventario -->
              <div class="col-md-6 col-lg-4">
                <a href="index.php?page=admin_inventario" class="text-decoration-none">
                  <div class="card admin-option-card h-100 shadow-sm" style="border: none; border-radius: 18px; background: white; transition: all 0.3s ease; overflow: hidden;">
                    <div class="card-body text-center p-4">
                      <div class="icon-wrapper mb-3" style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #99AA8C 0%, #8a9a75 100%); border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(153,170,140,0.3);">
                        <i class="bi bi-box-seam-fill" style="font-size: 2.2rem; color: white;"></i>
                      </div>
                      <h4 class="card-title mb-2" style="color: #333; font-weight: 700; font-size: 1.3rem;">Administrar Inventario</h4>
                      <p class="card-text text-muted mb-0" style="font-size: 0.95rem;">Busca y modifica productos del inventario</p>
                    </div>
                    <div class="card-footer" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: none; padding: 12px; text-align: center;">
                      <span style="color: #99AA8C; font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-arrow-right-circle me-1"></i>Acceder
                      </span>
                    </div>
                  </div>
                </a>
              </div>

              <!-- Asignar Eventos a Organizadores -->
              <div class="col-md-6 col-lg-4">
                <a href="index.php?page=admin_asignar_eventos" class="text-decoration-none">
                  <div class="card admin-option-card h-100 shadow-sm" style="border: none; border-radius: 18px; background: white; transition: all 0.3s ease; overflow: hidden;">
                    <div class="card-body text-center p-4">
                      <div class="icon-wrapper mb-3" style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #28a745 0%, #218838 100%); border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(40,167,69,0.3);">
                        <i class="bi bi-calendar-check-fill" style="font-size: 2.2rem; color: white;"></i>
                      </div>
                      <h4 class="card-title mb-2" style="color: #333; font-weight: 700; font-size: 1.3rem;">Asignar Eventos</h4>
                      <p class="card-text text-muted mb-0" style="font-size: 0.95rem;">Asigna eventos a organizadores específicos</p>
                    </div>
                    <div class="card-footer" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: none; padding: 12px; text-align: center;">
                      <span style="color: #28a745; font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-arrow-right-circle me-1"></i>Acceder
                      </span>
                    </div>
                  </div>
                </a>
              </div>

              <!-- Administrar Servicios -->
              <div class="col-md-6 col-lg-4">
                <a href="index.php?page=admin_servicios" class="text-decoration-none">
                  <div class="card admin-option-card h-100 shadow-sm" style="border: none; border-radius: 18px; background: white; transition: all 0.3s ease; overflow: hidden;">
                    <div class="card-body text-center p-4">
                      <div class="icon-wrapper mb-3" style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(0,123,255,0.3);">
                        <i class="bi bi-briefcase-fill" style="font-size: 2.2rem; color: white;"></i>
                      </div>
                      <h4 class="card-title mb-2" style="color: #333; font-weight: 700; font-size: 1.3rem;">Administrar Servicios</h4>
                      <p class="card-text text-muted mb-0" style="font-size: 0.95rem;">Gestiona disponibilidad y estado de servicios</p>
                    </div>
                    <div class="card-footer" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: none; padding: 12px; text-align: center;">
                      <span style="color: #007bff; font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-arrow-right-circle me-1"></i>Acceder
                      </span>
                    </div>
                  </div>
                </a>
              </div>

            </div>
        </div>
    </div>

</div>
