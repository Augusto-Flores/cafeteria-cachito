<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];
$mensajeExito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = sanitize_input($_POST['direccion'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    // Capturamos las coordenadas del mapa
    $latitud = isset($_POST['latitud']) && is_numeric($_POST['latitud']) ? (float)$_POST['latitud'] : null;
    $longitud = isset($_POST['longitud']) && is_numeric($_POST['longitud']) ? (float)$_POST['longitud'] : null;
    
    $stmtUpdate = $pdo->prepare('UPDATE usuarios SET direccion = ?, telefono = ?, latitud = ?, longitud = ? WHERE id_usuario = ?');
    $stmtUpdate->execute([$direccion, $telefono, $latitud, $longitud, $userId]);
    $mensajeExito = '✅ Tus datos de despacho y ubicación exacta han sido actualizados.';
}

// Extraemos también la latitud y longitud
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
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <style>
    #mapa-delivery {
        height: 250px;
        border-radius: 0.5rem;
        border: 1px solid #dcd3c4;
        z-index: 1; 
        margin-bottom: 0.5rem;
    }
  </style>
</head>
<body style="background:#fcfbf9;">

  <header class="site-header">
    <div class="header-content">
      <h1>👤 Mi Perfil de Cliente</h1>
      <nav class="user-info">
        <a href="catalogo.php" class="btn btn-outline" style="border-color:white; color:white;">⬅️ Volver al Catálogo</a>
      </nav>
    </div>
  </header>

  <div class="main-container" style="max-width: 650px; margin-top: 2.5rem;">
    <div class="catalog-wrapper" style="padding: 2.5rem;">
      <h2 style="color:var(--color-primary); margin-bottom:0.5rem;">Datos del Usuario</h2>
      <p class="text-muted" style="font-size:0.85rem; margin-bottom:1.5rem;">Administra tus credenciales y tu ruta predeterminada para envíos de Delivery.</p>
      
      <?php if ($mensajeExito): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert" style="border-left: 5px solid var(--color-success);">
            <div><?php echo $mensajeExito; ?></div>
        </div>
      <?php endif; ?>

      <form action="perfil.php" method="POST">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size: 0.9rem;">Nombre Completo:</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>" disabled style="background:#f5f1e8; color:#777;">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size: 0.9rem;">👤 ID de Usuario (Login):</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['usuario'], ENT_QUOTES, 'UTF-8'); ?>" disabled style="background:#f5f1e8; color:#777;">
            </div>
        </div>

        <div class="mb-4 mt-4">
          <label class="form-label fw-bold text-primary" style="font-size: 0.95rem;">📍 Dirección de Delivery Principal:</label>
          <div id="mapa-delivery"></div>
          <div class="form-text mt-0 mb-2 text-muted" style="font-size: 0.8rem;">Arrastra el marcador rojo hacia tu ubicación para afinar la entrega.</div>
          
          <input type="text" name="direccion" id="direccion" class="form-control" value="<?php echo htmlspecialchars($user['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required readonly style="background:#faf8f5;">
          
          <input type="hidden" name="latitud" id="latitud" value="<?php echo htmlspecialchars((string)($user['latitud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="longitud" id="longitud" value="<?php echo htmlspecialchars((string)($user['longitud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold" style="font-size: 0.9rem;">📱 Número de Celular / WhatsApp:</label>
          <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" pattern="9[0-9]{8}" required placeholder="Ej: 912345678">
          <div class="form-text text-danger fw-bold" style="font-size: 0.8rem;">El número debe empezar con 9 y tener exactamente 9 dígitos.</div>
        </div>

        <button type="submit" class="btn btn-cachito w-100 py-2 mt-2" style="background-color: var(--color-primary); color: white; border: none; border-radius: 0.5rem; font-weight: bold;">💾 Guardar Cambios Operativos</button>
      </form>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Leemos las coordenadas guardadas en PHP. Si no existen, usamos Ventanilla por defecto.
        const savedLat = <?php echo json_encode($user['latitud']); ?>;
        const savedLng = <?php echo json_encode($user['longitud']); ?>;
        
        const startLat = savedLat !== null ? parseFloat(savedLat) : -11.8744;
        const startLng = savedLng !== null ? parseFloat(savedLng) : -77.1264;

        const map = L.map('mapa-delivery').setView([startLat, startLng], 15); // Zoom un poco más cercano

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
        const inputDireccion = document.getElementById('direccion');
        const inputLat = document.getElementById('latitud');
        const inputLng = document.getElementById('longitud');

        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            
            // Actualizamos los inputs ocultos con las nuevas coordenadas
            inputLat.value = position.lat;
            inputLng.value = position.lng;

            const oldVal = inputDireccion.value;
            inputDireccion.value = "Calculando nueva dirección...";

            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${position.lat}&lon=${position.lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        let addressParts = data.display_name.split(',');
                        let cleanAddress = addressParts.slice(0, 3).join(',').trim();
                        inputDireccion.value = cleanAddress;
                    } else {
                        inputDireccion.value = "Ubicación no detallada. Escríbela a mano.";
                        inputDireccion.removeAttribute('readonly'); 
                    }
                })
                .catch(error => {
                    console.error("Error al obtener la dirección:", error);
                    inputDireccion.value = oldVal;
                    inputDireccion.removeAttribute('readonly'); 
                });
        });
    });
  </script>
</body>
</html>