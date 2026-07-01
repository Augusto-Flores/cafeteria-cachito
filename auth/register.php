<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize_input($_POST['nombre'] ?? '');
    $usuario = sanitize_input($_POST['usuario'] ?? '');
    $direccion = sanitize_input($_POST['direccion'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($nombre === '' || $usuario === '' || $direccion === '' || $telefono === '' || $password === '') {
        $errors[] = 'Completa todos los campos obligatorios.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            
            if ($stmt->fetch()) {
                $errors[] = 'El usuario elegido ya está en uso. Intenta con otro.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol, estado, direccion, telefono, fecha_creacion) VALUES (?, ?, ?, "Cliente", "Activo", ?, ?, NOW())');
                $insert->execute([$nombre, $usuario, $hash, $direccion, $telefono]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            error_log('Error en Registro: ' . $e->getMessage());
            $errors[] = 'Error interno del servidor. Inténtalo más tarde.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro de Cliente | Cachito</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/auth.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  
  <style>
    /* Ajuste para que el mapa se vea integrado con tu diseño */
    #mapa-delivery {
        height: 220px;
        border-radius: 0.5rem;
        border: 1px solid #dcd3c4;
        z-index: 1; /* Evita superposiciones con inputs */
        margin-bottom: 0.5rem;
    }
  </style>
</head>
<body class="auth-bg">

  <main class="auth-card" role="main" style="max-width: 500px;"> <div class="auth-logo" aria-label="Logotipo de la Cafetería Cachito">☕ Cachito</div>
    <p class="auth-subtitle">Crea tu cuenta como cliente</p>
    
    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center flex-column text-center" role="alert" aria-live="polite">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-check-circle-fill mb-2" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <div>
                <strong>¡Cuenta creada exitosamente!</strong>
                <p class="mb-3 mt-1" style="font-size: 0.9rem;">Ya puedes iniciar sesión para realizar tus pedidos.</p>
                <a href="login.php" class="btn btn-success w-100 fw-bold">Ir al Login</a>
            </div>
        </div>
    <?php else: ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger d-flex align-items-center" role="alert" aria-live="assertive">
            <div>
                <ul class="mb-0 ps-3" style="font-size: 0.9rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
          </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="needs-validation" id="loginForm" novalidate>
          
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre Completo</label>
            <div class="input-group">
                <span class="input-group-text bg-white">👤</span>
                <input type="text" name="nombre" id="nombre" class="form-control" value="<?php echo htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="usuario" class="form-label">Usuario (Login)</label>
            <div class="input-group">
                <span class="input-group-text bg-white">@</span>
                <input type="text" name="usuario" id="usuario" class="form-control" value="<?php echo htmlspecialchars($_POST['usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
          </div>

          <div class="col-12 mb-3">
            <label class="form-label text-primary fw-bold">📍 Ubica tu dirección de entrega</label>
            <div id="mapa-delivery"></div>
            <div class="form-text mt-0 mb-2 text-muted" style="font-size: 0.8rem;">Arrastra el marcador rojo hacia tu casa para autocompletar la dirección.</div>
            
            <div class="input-group">
                <input type="text" name="direccion" id="direccion" class="form-control bg-light" placeholder="La dirección aparecerá aquí..." value="<?php echo htmlspecialchars($_POST['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required readonly>
            </div>
          </div>

          <div class="row">
              <div class="col-12 mb-3">
                <label for="telefono" class="form-label">Celular</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">📱</span>
                    <input type="text" name="telefono" id="telefono" class="form-control" placeholder="999888777" pattern="[0-9]{9}" value="<?php echo htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-white">🔐</span>
                <input type="password" name="password" id="password" class="form-control" minlength="6" required>
                <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="togglePassword">👁️</button>
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-cachito" id="btnEntrar">Crear mi cuenta</button>
          </div>
        </form>

    <?php endif; ?>
    
    <div class="auth-links text-center mt-4">
      <p class="mb-0"><a href="login.php">⬅️ Volver al Login</a></p>
    </div>
  </main>

  <script src="../assets/js/auth.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Coordenadas centrales de Ventanilla, Callao
        const ventanillaLat = -11.8744;
        const ventanillaLng = -77.1264;

        // Inicializar el mapa
        const map = L.map('mapa-delivery').setView([ventanillaLat, ventanillaLng], 14);

        // Cargar las texturas de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Crear el marcador arrastrable (Draggable)
        const marker = L.marker([ventanillaLat, ventanillaLng], {
            draggable: true
        }).addTo(map);

        const inputDireccion = document.getElementById('direccion');

        // Función que consume la API Nominatim cuando el usuario suelta el marcador
        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            
            // Efecto visual de carga en el input
            inputDireccion.value = "Obteniendo dirección...";

            // Petición a la API pública de OpenStreetMap (Reverse Geocoding)
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${position.lat}&lon=${position.lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        // Limpiar un poco la cadena (quitar país para hacerlo más local)
                        let addressParts = data.display_name.split(',');
                        // Tomamos las partes más relevantes
                        let cleanAddress = addressParts.slice(0, 3).join(',').trim();
                        inputDireccion.value = cleanAddress;
                    } else {
                        inputDireccion.value = "Dirección no encontrada. Mueve el pin un poco.";
                    }
                })
                .catch(error => {
                    console.error("Error al obtener la dirección:", error);
                    inputDireccion.value = "Error al conectar con el mapa.";
                    // Si falla la API, permitimos que el usuario lo escriba a mano desbloqueando el input
                    inputDireccion.removeAttribute('readonly'); 
                });
        });
    });
  </script>
</body>
</html>