<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

// Control de seguridad por rol unificado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];
$mensajeExito = '';

// Procesar actualización de datos de envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = sanitize_input($_POST['direccion'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    
    $stmtUpdate = $pdo->prepare('UPDATE usuarios SET direccion = ?, telefono = ? WHERE id_usuario = ?');
    $stmtUpdate->execute([$direccion, $telefono, $userId]);
    $mensajeExito = '✅ Tus datos de despacho han sido actualizados correctamente.';
}

// Consultar los datos vigentes en la versión de BD v2.0
$stmtUser = $pdo->prepare('SELECT nombre, usuario, direccion, telefono FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>👤 Mi Perfil - Cafetería Cachito</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>

  <header class="site-header">
    <div class="header-content">
      <h1>👤 Mi Perfil de Cliente</h1>
      <nav class="user-info">
        <a href="catalogo.php" class="btn btn-outline" style="border-color:white; color:white;">⬅️ Volver al Catálogo</a>
      </nav>
    </div>
  </header>

  <div class="main-container" style="max-width: 600px; margin-top: 2.5rem;">
    <div class="catalog-wrapper" style="padding: 2rem;">
      <h2 style="color:var(--color-primary); margin-bottom:0.5rem;">Datos del Usuario</h2>
      <p class="text-muted" style="font-size:0.85rem; margin-bottom:1.5rem;">Administra tus credenciales y rutas predeterminadas para envíos de Delivery.</p>
      
      <?php if ($mensajeExito): ?>
        <div class="alert alert-success"><?php echo $mensajeExito; ?></div>
      <?php endif; ?>

      <form action="perfil.php" method="POST">
        <div class="checkout-form-group">
          <label>Nombre Completo:</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" disabled style="background:#f5f1e8; color:#777;">
        </div>

        <div class="checkout-form-group">
          <label>👤 ID de Usuario (Login):</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['usuario']); ?>" disabled style="background:#f5f1e8; color:#777;">
        </div>

        <div class="checkout-form-group">
          <label>📍 Dirección de Delivery Principal:</label>
          <input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($user['direccion'] ?? ''); ?>" required placeholder="Ej: Av. Universitaria 1250, Los Olivos">
        </div>

        <div class="checkout-form-group">
          <label>📱 Número de Celular / WhatsApp:</label>
          <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" required placeholder="Ej: 912345678">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; margin-top:1rem;">💾 Guardar Cambios Operativos</button>
      </form>
    </div>
  </div>

</body>
</html>