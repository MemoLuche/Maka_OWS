<?php
require_once __DIR__ . '/../config/conexion.php';
// Verificación de sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: ?page=login');
    exit;
}
// helper de escape
function esc($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Obtener datos del usuario desde la base de datos usando el ID de la sesión
$userId = $_SESSION['user_id'] ?? null;
$currentUser = null;

if (!empty($userId)) {
    try {
        $stmt = $pdo->prepare("SELECT nombre_completo, correo, celular FROM usuario WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $currentUser = [
                'name' => $row['nombre_completo'],
                'email' => $row['correo'],
                'phone' => $row['celular'],
                'avatar' => 'imagenes/headerimg.jpg'
            ];
        }
    } catch (PDOException $e) {
        // Error en consulta
        error_log("Error al obtener datos del usuario: " . $e->getMessage());
    }
}

// Si no se encontró el usuario, usar datos por defecto o mostrar error
if ($currentUser === null) {
    $currentUser = [
        'name' => $_SESSION['user_name'] ?? 'Usuario',
        'email' => $_SESSION['user_email'] ?? 'No disponible',
        'phone' => 'No disponible',
        'avatar' => 'imagenes/headerimg.jpg'
    ];
}
?>

  <!-- Header -->
  <div class="account-header">
    <h5 class="account-header-title">Mi Cuenta</h5>
  </div>

  <!-- Main Content -->
  <div class="container-fluid p-3">
    <div class="account-main-container">

    <!-- Avatar Section -->
    <div class="avatar-container">
      <img src="<?php echo esc($currentUser['avatar']); ?>" alt="Avatar" class="avatar">
      <h4 class="avatar-name"><?php echo esc($currentUser['name']); ?></h4>
    </div>

    <!-- Info Card -->
    <div class="account-card">
      <div class="info-item">
        <div class="info-icon">
          <i class="bi bi-person"></i>
        </div>
        <div class="info-content">
          <div class="info-label">Nombre</div>
          <div class="info-value"><?php echo esc($currentUser['name']); ?></div>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">
          <i class="bi bi-envelope"></i>
        </div>
        <div class="info-content">
          <div class="info-label">Email</div>
          <div class="info-value"><?php echo esc($currentUser['email']); ?></div>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">
          <i class="bi bi-telephone"></i>
        </div>
        <div class="info-content">
          <div class="info-label">Teléfono</div>
          <div class="info-value"><?php echo esc($currentUser['phone']); ?></div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="account-actions">
      <a href="?page=cuenta_ajustes" class="btn btn-pill">
        <i class="bi bi-pencil-square"></i>
        <span>Editar Datos</span>
      </a>
      <button class="btn btn-outline-pill" onclick="handleLogout()">
        <i class="bi bi-box-arrow-right"></i>
        <span>Cerrar Sesión</span>
      </button>
    </div>

    </div>
  </div>

  <script>
    // Función para cerrar sesión
    function handleLogout() {
      customConfirm(
        '¿Estás seguro de que quieres cerrar sesión?<br><small class="text-muted">Tendrás que iniciar sesión nuevamente para acceder al sistema.</small>',
        () => {
          window.location.href = '?logout=1';
        },
        'Cerrar Sesión'
      );
    }
  </script>