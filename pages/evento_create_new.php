<?php
require_once __DIR__ . '/../config/conexion.php';
// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}

// helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Verificar si es admin
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';

// Obtener lista de organizadores si es admin
$organizadores = [];
if ($isAdmin) {
    try {
        $stmt = $pdo->query("SELECT id, nombre_completo, correo FROM usuario WHERE tipo = 'cliente' ORDER BY nombre_completo ASC");
        $organizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo organizadores: " . $e->getMessage());
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Recolectar y validar campos
  $nombre_novio_1 = trim($_POST['nombre_novio_1'] ?? '');
  $nombre_novio_2 = trim($_POST['nombre_novio_2'] ?? '');
  $nombre_evento = trim($_POST['nombre_evento'] ?? '');
  $fecha_evento = trim($_POST['fecha_evento'] ?? '');
  $hora_inicio_montaje = trim($_POST['hora_inicio_montaje'] ?? '');
  $hora_fin_montaje = trim($_POST['hora_fin_montaje'] ?? '');
  $hora_inicio_evento = trim($_POST['hora_inicio_evento'] ?? '');
  $hora_fin_evento = trim($_POST['hora_fin_evento'] ?? '');
  $ubicacion = trim($_POST['ubicacion'] ?? '');
  $direccion_completa = trim($_POST['direccion_completa'] ?? '');
  $coordenadas_maps = trim($_POST['coordenadas_maps'] ?? '');
  $numero_invitados = trim($_POST['numero_invitados'] ?? '0');
  $nombre_responsable = trim($_POST['nombre_responsable'] ?? '');
  $numero_responsable = trim($_POST['numero_responsable'] ?? '');
  $correo_responsable = trim($_POST['correo_responsable'] ?? '');
  $presupuesto_total = trim($_POST['presupuesto_total'] ?? '0');
  $anticipo_pagado = trim($_POST['anticipo_pagado'] ?? '0');
  $saldo_pendiente = trim($_POST['saldo_pendiente'] ?? '0');
  $notas_internas = trim($_POST['notas_internas'] ?? '');
  $notas_cliente = trim($_POST['notas_cliente'] ?? '');
  $estatus = trim($_POST['estatus'] ?? 'Pendiente');
  
  // Asignación de organizador
  $organizador_id = null;
  if ($isAdmin) {
      // Admin puede asignar a cualquier organizador o dejar sin asignar
      $organizador_id = !empty($_POST['organizador_id']) ? (int)$_POST['organizador_id'] : null;
  } else {
      // Organizador se autoasigna el evento
      $organizador_id = $_SESSION['user_id'];
  }

  // Validaciones
  if ($nombre_novio_1 === '') { $errors[] = 'El nombre del novio 1 es obligatorio.'; }
  if ($nombre_novio_2 === '') { $errors[] = 'El nombre del novio 2 es obligatorio.'; }
  if ($nombre_evento === '') { $errors[] = 'El nombre del evento es obligatorio.'; }
  if ($nombre_responsable === '') { $errors[] = 'El nombre del responsable es obligatorio.'; }
  if ($numero_responsable === '') { $errors[] = 'El número del responsable es obligatorio.'; }
  if ($ubicacion === '') { $errors[] = 'La ubicación es obligatoria.'; }
  if ($fecha_evento === '') { $errors[] = 'La fecha del evento es obligatoria.'; }

  if (empty($errors)) {
    try {
      // Insertar en la tabla `eventos`
      $sql = "INSERT INTO eventos (
        nombre_novio_1, nombre_novio_2, nombre_evento, fecha_evento, 
        hora_inicio_montaje, hora_fin_montaje, hora_inicio_evento, hora_fin_evento,
        ubicacion, direccion_completa, coordenadas_maps, numero_invitados,
        nombre_responsable, numero_responsable, correo_responsable,
        presupuesto_total, anticipo_pagado, saldo_pendiente,
        notas_internas, notas_cliente, estatus, organizador_id
      ) VALUES (
        :nombre_novio_1, :nombre_novio_2, :nombre_evento, :fecha_evento,
        :hora_inicio_montaje, :hora_fin_montaje, :hora_inicio_evento, :hora_fin_evento,
        :ubicacion, :direccion_completa, :coordenadas_maps, :numero_invitados,
        :nombre_responsable, :numero_responsable, :correo_responsable,
        :presupuesto_total, :anticipo_pagado, :saldo_pendiente,
        :notas_internas, :notas_cliente, :estatus, :organizador_id
      )";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':nombre_novio_1' => $nombre_novio_1,
        ':nombre_novio_2' => $nombre_novio_2,
        ':nombre_evento' => $nombre_evento,
        ':fecha_evento' => $fecha_evento,
        ':hora_inicio_montaje' => $hora_inicio_montaje ?: null,
        ':hora_fin_montaje' => $hora_fin_montaje ?: null,
        ':hora_inicio_evento' => $hora_inicio_evento ?: null,
        ':hora_fin_evento' => $hora_fin_evento ?: null,
        ':ubicacion' => $ubicacion,
        ':direccion_completa' => $direccion_completa,
        ':coordenadas_maps' => $coordenadas_maps,
        ':numero_invitados' => (int)$numero_invitados,
        ':nombre_responsable' => $nombre_responsable,
        ':numero_responsable' => $numero_responsable,
        ':correo_responsable' => $correo_responsable,
        ':presupuesto_total' => (float)$presupuesto_total,
        ':anticipo_pagado' => (float)$anticipo_pagado,
        ':saldo_pendiente' => (float)$saldo_pendiente,
        ':notas_internas' => $notas_internas,
        ':notas_cliente' => $notas_cliente,
        ':estatus' => $estatus,
        ':organizador_id' => $organizador_id,
      ]);

      // Redirigir a la lista de eventos con bandera saved
      header('Location: ?page=eventos&saved=1');
      exit;
    } catch (PDOException $e) {
      $errors[] = 'Error al guardar en la base de datos: ' . $e->getMessage();
    }
  }
}
?>

<style>
.wizard-container {
    max-width: 900px;
    margin: 0 auto;
}

.wizard-header {
    background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 15px 15px 0 0;
    text-align: center;
    box-shadow: 0 4px 15px rgba(153, 170, 140, 0.3);
}

.wizard-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.wizard-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.95;
    font-size: 1.05rem;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    padding: 2rem 1rem;
    background: white;
    border-bottom: 2px solid #f0f0f0;
    position: relative;
}

.wizard-steps::before {
    content: '';
    position: absolute;
    top: 3.2rem;
    left: 10%;
    right: 10%;
    height: 3px;
    background: #e9ecef;
    z-index: 0;
}

.wizard-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.wizard-step-number {
    width: 50px;
    height: 50px;
    line-height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    margin: 0 auto 0.5rem;
    font-weight: 700;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.wizard-step.active .wizard-step-number {
    background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(153, 170, 140, 0.4);
}

.wizard-step.completed .wizard-step-number {
    background: #28a745;
    color: white;
}

.wizard-step-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
}

.wizard-step.active .wizard-step-label {
    color: #99AA8C;
}

.wizard-content {
    background: white;
    padding: 2.5rem;
    min-height: 400px;
}

.wizard-section {
    display: none;
}

.wizard-section.active {
    display: block;
    animation: fadeIn 0.4s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-title {
    color: #99AA8C;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid #99AA8C;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control, .form-select {
    border: 2px solid #e5e8e0;
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #99AA8C;
    box-shadow: 0 0 0 0.2rem rgba(153, 170, 140, 0.25);
}

.wizard-actions {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem 2.5rem;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
}

.btn-wizard {
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.btn-wizard-prev {
    background: #6c757d;
    color: white;
}

.btn-wizard-prev:hover {
    background: #5a6268;
    transform: translateX(-3px);
}

.btn-wizard-next {
    background: linear-gradient(135deg, #99AA8C 0%, #7d8f74 100%);
    color: white;
}

.btn-wizard-next:hover {
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(153, 170, 140, 0.4);
}

.btn-wizard-submit {
    background: linear-gradient(135deg, #28a745 0%, #20873a 100%);
    color: white;
    padding: 0.75rem 2.5rem;
}

.btn-wizard-submit:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.info-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-left: 4px solid #99AA8C;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.info-box i {
    color: #99AA8C;
    font-size: 1.3rem;
    margin-right: 0.75rem;
}

.required-mark {
    color: #dc3545;
    margin-left: 3px;
}
</style>

<div class="wizard-container my-5">
    <div class="card shadow-lg border-0">
        <!-- Header -->
        <div class="wizard-header">
            <h1><i class="bi bi-calendar-heart me-3"></i>Crear Nuevo Evento</h1>
            <p>Completa la información paso a paso para crear un evento inolvidable</p>
        </div>

        <!-- Progress Steps -->
        <div class="wizard-steps">
            <div class="wizard-step active" data-step="1">
                <div class="wizard-step-number">1</div>
                <div class="wizard-step-label">Información Básica</div>
            </div>
            <div class="wizard-step" data-step="2">
                <div class="wizard-step-number">2</div>
                <div class="wizard-step-label">Fecha y Ubicación</div>
            </div>
            <div class="wizard-step" data-step="3">
                <div class="wizard-step-number">3</div>
                <div class="wizard-step-label">Contacto</div>
            </div>
            <div class="wizard-step" data-step="4">
                <div class="wizard-step-number">4</div>
                <div class="wizard-step-label">Presupuesto</div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="wizard-step" data-step="5">
                <div class="wizard-step-number">5</div>
                <div class="wizard-step-label">Asignación</div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger m-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0">
                <?php foreach($errors as $err): ?>
                    <li><?php echo esc($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" id="wizardForm" novalidate>
            <!-- Content -->
            <div class="wizard-content">
                
                <!-- Step 1: Información Básica -->
                <div class="wizard-section active" data-step="1">
                    <h3 class="section-title">
                        <i class="bi bi-people-fill"></i>
                        Información Básica del Evento
                    </h3>
                    
                    <div class="info-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Proporciona los datos principales del evento y los novios</span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre Novio 1<span class="required-mark">*</span></label>
                            <input class="form-control" name="nombre_novio_1" required value="<?php echo esc($_POST['nombre_novio_1'] ?? ''); ?>" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre Novio 2<span class="required-mark">*</span></label>
                            <input class="form-control" name="nombre_novio_2" required value="<?php echo esc($_POST['nombre_novio_2'] ?? ''); ?>" placeholder="Ej: María García">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre del Evento<span class="required-mark">*</span></label>
                        <input class="form-control" name="nombre_evento" required placeholder="Ej: Boda Jardín Primavera" value="<?php echo esc($_POST['nombre_evento'] ?? ''); ?>">
                        <small class="text-muted">Este será el título que identifique el evento</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Número de Invitados</label>
                        <input type="number" class="form-control" name="numero_invitados" min="0" value="<?php echo esc($_POST['numero_invitados'] ?? ''); ?>" placeholder="Ej: 150">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estatus del Evento</label>
                        <select class="form-select" name="estatus">
                            <option value="Pendiente" <?php echo (($_POST['estatus'] ?? '') === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Confirmado" <?php echo (($_POST['estatus'] ?? '') === 'Confirmado') ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="En Proceso" <?php echo (($_POST['estatus'] ?? '') === 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                        </select>
                    </div>
                </div>

                <!-- Step 2: Fecha y Ubicación -->
                <div class="wizard-section" data-step="2">
                    <h3 class="section-title">
                        <i class="bi bi-geo-alt-fill"></i>
                        Fecha y Ubicación
                    </h3>

                    <div class="info-box">
                        <i class="bi bi-calendar-check-fill"></i>
                        <span>Define cuándo y dónde se llevará a cabo el evento</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Fecha del Evento<span class="required-mark">*</span></label>
                        <input type="date" class="form-control" name="fecha_evento" required value="<?php echo esc($_POST['fecha_evento'] ?? ''); ?>">
                    </div>

                    <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-clock me-2"></i>Horarios del Evento</h6>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Inicio Montaje</label>
                            <input type="time" class="form-control" name="hora_inicio_montaje" value="<?php echo esc($_POST['hora_inicio_montaje'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fin Montaje</label>
                            <input type="time" class="form-control" name="hora_fin_montaje" value="<?php echo esc($_POST['hora_fin_montaje'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Inicio Evento</label>
                            <input type="time" class="form-control" name="hora_inicio_evento" value="<?php echo esc($_POST['hora_inicio_evento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fin Evento</label>
                            <input type="time" class="form-control" name="hora_fin_evento" value="<?php echo esc($_POST['hora_fin_evento'] ?? ''); ?>">
                        </div>
                    </div>

                    <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-pin-map me-2"></i>Ubicación</h6>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Lugar<span class="required-mark">*</span></label>
                        <input class="form-control" name="ubicacion" required placeholder="Ej: Hacienda Los Robles" value="<?php echo esc($_POST['ubicacion'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección Completa</label>
                        <input class="form-control" name="direccion_completa" placeholder="Ej: Av. Principal 123, Colonia Centro" value="<?php echo esc($_POST['direccion_completa'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coordenadas Google Maps</label>
                        <input class="form-control" name="coordenadas_maps" placeholder="Ej: 19.4326,-99.1332 o URL de Google Maps" value="<?php echo esc($_POST['coordenadas_maps'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Step 3: Contacto -->
                <div class="wizard-section" data-step="3">
                    <h3 class="section-title">
                        <i class="bi bi-person-badge-fill"></i>
                        Información de Contacto
                    </h3>

                    <div class="info-box">
                        <i class="bi bi-telephone-fill"></i>
                        <span>Datos de la persona responsable del evento</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre del Responsable<span class="required-mark">*</span></label>
                        <input class="form-control" name="nombre_responsable" required value="<?php echo esc($_POST['nombre_responsable'] ?? ''); ?>" placeholder="Ej: Elena Ramírez">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono<span class="required-mark">*</span></label>
                            <input class="form-control" name="numero_responsable" required placeholder="5551234567" value="<?php echo esc($_POST['numero_responsable'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo_responsable" placeholder="correo@ejemplo.com" value="<?php echo esc($_POST['correo_responsable'] ?? ''); ?>">
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3 fw-bold text-secondary"><i class="bi bi-journal-text me-2"></i>Notas Adicionales</h6>
                    <div class="mb-3">
                        <label class="form-label">Notas Internas (solo equipo)</label>
                        <textarea class="form-control" name="notas_internas" rows="3" placeholder="Comentarios privados del equipo organizador..."><?php echo esc($_POST['notas_internas'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas para el Cliente</label>
                        <textarea class="form-control" name="notas_cliente" rows="3" placeholder="Información visible para el cliente..."><?php echo esc($_POST['notas_cliente'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Step 4: Presupuesto -->
                <div class="wizard-section" data-step="4">
                    <h3 class="section-title">
                        <i class="bi bi-cash-coin"></i>
                        Presupuesto y Pagos
                    </h3>

                    <div class="info-box">
                        <i class="bi bi-calculator-fill"></i>
                        <span>Define el presupuesto total y los pagos relacionados</span>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-wallet2 me-1"></i>Presupuesto Total</label>
                            <input type="number" step="0.01" class="form-control" name="presupuesto_total" min="0" placeholder="0.00" value="<?php echo esc($_POST['presupuesto_total'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-check-circle me-1"></i>Anticipo Pagado</label>
                            <input type="number" step="0.01" class="form-control" name="anticipo_pagado" min="0" placeholder="0.00" value="<?php echo esc($_POST['anticipo_pagado'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-hourglass-split me-1"></i>Saldo Pendiente</label>
                            <input type="number" step="0.01" class="form-control" name="saldo_pendiente" min="0" placeholder="0.00" value="<?php echo esc($_POST['saldo_pendiente'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="bi bi-lightbulb-fill me-2"></i>
                        <strong>Tip:</strong> Puedes actualizar estos valores más adelante en la página de detalles del evento
                    </div>
                </div>

                <!-- Step 5: Asignación (Solo Admin) -->
                <?php if ($isAdmin): ?>
                <div class="wizard-section" data-step="5">
                    <h3 class="section-title">
                        <i class="bi bi-person-check-fill"></i>
                        Asignar Organizador
                    </h3>

                    <div class="info-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Selecciona el organizador que gestionará este evento. Puedes dejarlo sin asignar y asignarlo más tarde.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Organizador Asignado</label>
                        <select class="form-select" name="organizador_id">
                            <option value="">-- Sin asignar (asignar después) --</option>
                            <?php foreach ($organizadores as $org): ?>
                                <option value="<?php echo $org['id']; ?>"><?php echo esc($org['nombre_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Puedes cambiar la asignación en cualquier momento desde los detalles del evento</small>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Actions -->
            <div class="wizard-actions">
                <button type="button" class="btn btn-wizard btn-wizard-prev" id="prevBtn" style="display: none;">
                    <i class="bi bi-arrow-left me-2"></i>Anterior
                </button>
                <div></div>
                <button type="button" class="btn btn-wizard btn-wizard-next" id="nextBtn">
                    Siguiente<i class="bi bi-arrow-right ms-2"></i>
                </button>
                <button type="submit" class="btn btn-wizard btn-wizard-submit" id="submitBtn" style="display: none;">
                    <i class="bi bi-check-circle me-2"></i>Crear Evento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Wizard Navigation
let currentStep = 1;
const totalSteps = <?php echo $isAdmin ? '5' : '4'; ?>;

document.getElementById('nextBtn').addEventListener('click', function() {
    if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
});

document.getElementById('prevBtn').addEventListener('click', function() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
});

function showStep(step) {
    // Hide all sections
    document.querySelectorAll('.wizard-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show current section
    const currentSection = document.querySelector(`.wizard-section[data-step="${step}"]`);
    if (currentSection) {
        currentSection.classList.add('active');
    }
    
    // Update step indicators
    document.querySelectorAll('.wizard-step').forEach(stepEl => {
        const stepNum = parseInt(stepEl.getAttribute('data-step'));
        stepEl.classList.remove('active', 'completed');
        
        if (stepNum === step) {
            stepEl.classList.add('active');
        } else if (stepNum < step) {
            stepEl.classList.add('completed');
        }
    });
    
    // Update buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    if (step === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'block';
    }
    
    if (step === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'block';
    } else {
        nextBtn.style.display = 'block';
        submitBtn.style.display = 'none';
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Initialize
showStep(1);
</script>
