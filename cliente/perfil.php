<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') { header('Location: ../auth/login.php'); exit; }

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];
$mensajeExito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = sanitize_input($_POST['direccion'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    $latitud = isset($_POST['latitud']) && is_numeric($_POST['latitud']) ? (float)$_POST['latitud'] : null;
    $longitud = isset($_POST['longitud']) && is_numeric($_POST['longitud']) ? (float)$_POST['longitud'] : null;
    
    $stmtUpdate = $pdo->prepare('UPDATE usuarios SET direccion = ?, telefono = ?, latitud = ?, longitud = ? WHERE id_usuario = ?');
    $stmtUpdate->execute([$direccion, $telefono, $latitud, $longitud, $userId]);
    $mensajeExito = '✅ Tus datos de despacho y ubicación exacta han sido actualizados.';
}

$stmtUser = $pdo->prepare('SELECT nombre, usuario, direccion, telefono, latitud, longitud FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>👤 Mi Perfil - Cafetería Cachito</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body class="bg-cliente-main">
  <header class="site-header">
    <div class="header-content">
      <h1>👤 Mi Perfil de Cliente</h1>
      <nav class="user-info"><a href="catalogo.php" class="btn btn-nav-header">⬅️ Volver al Catálogo</a></nav>
    </div>
  </header>

  <main class="profile-container">
    <div class="catalog-wrapper">
      <h2 class="color-primary mb-2">Datos del Usuario</h2>
      <p class="text-muted small mb-4">Administra tus credenciales y ruta de envíos.</p>
      
      <?php if ($mensajeExito): ?>
        <div class="alert alert-custom-success alert-success d-flex align-items-center" role="alert">
            <div><?php echo $mensajeExito; ?></div>
        </div>
      <?php endif; ?>

      <form action="perfil.php" method="POST">
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label fw-bold">Nombre Completo:</label><input type="text" class="form-control input-readonly-custom" value="<?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>" disabled></div>
            <div class="col-md-6"><label class="form-label fw-bold">ID de Usuario:</label><input type="text" class="form-control input-readonly-custom" value="<?php echo htmlspecialchars($user['usuario'], ENT_QUOTES, 'UTF-8'); ?>" disabled></div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold color-primary">📍 Dirección de Delivery Principal:</label>
          <div id="mapa-delivery"></div>
          <div class="form-text mt-1 mb-2 text-muted">Arrastra el marcador rojo hacia tu ubicación.</div>
          <input type="text" name="direccion" id="direccion" class="form-control input-map-custom fw-bold" value="<?php echo htmlspecialchars($user['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required readonly>
          <input type="hidden" name="latitud" id="latitud" value="<?php echo htmlspecialchars((string)($user['latitud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="longitud" id="longitud" value="<?php echo htmlspecialchars((string)($user['longitud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold">📱 Celular:</label>
          <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" pattern="9[0-9]{8}" required placeholder="Ej: 912345678">
        </div>
        <button type="submit" class="btn btn-cachito-custom w-100 py-2 mt-2">💾 Guardar Cambios</button>
      </form>
    </div>
  </main>
  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="../assets/js/cliente.js"></script>
</body>
</html>