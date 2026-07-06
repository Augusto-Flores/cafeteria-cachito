<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') { header('Location: ../auth/login.php'); exit; }

$alertaExito = $_SESSION['reserva_success'] ?? null;
$alertaError = $_SESSION['reserva_error'] ?? null;
unset($_SESSION['reserva_success'], $_SESSION['reserva_error']);

try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id_mesa, numero_mesa, capacidad, estado FROM mesas ORDER BY numero_mesa ASC');
    $mesasBD = $stmt->fetchAll();
} catch (PDOException $e) { $mesasBD = []; }

date_default_timezone_set('America/Lima');
$today = date('Y-m-d');
$cipFalso = rand(10000000, 99999999);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>📅 Reservar Mesa - Cafetería Cachito</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body class="bg-cliente-main">
  <header class="site-header">
    <div class="header-content">
      <h1>📅 Reservación de Mesas</h1>
      <nav class="user-info">
        <a href="catalogo.php" class="btn btn-nav-header">🛵 Pedir Delivery</a>
        <a href="../auth/logout.php" class="btn btn-nav-danger">🚪 Salir</a>
      </nav>
    </div>
  </header>

  <main class="main-container">
    <?php if ($alertaExito): ?>
        <div class="alert alert-custom-success alert-success d-flex align-items-center mb-4" role="alert">
            <span class="fs-1 me-3">📅</span>
            <div><h5 class="alert-heading mb-1 fw-bold">¡Mesa Confirmada!</h5><p class="mb-0"><?php echo htmlspecialchars($alertaExito, ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
    <?php endif; ?>
    <?php if ($alertaError): ?>
        <div class="alert alert-custom-danger alert-danger d-flex align-items-center mb-4" role="alert">
            <span class="fs-1 me-3">⚠️</span>
            <div><h5 class="alert-heading mb-1 fw-bold">Atención</h5><p class="mb-0"><?php echo htmlspecialchars($alertaError, ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
    <?php endif; ?>

    <div class="client-layout">
        <div class="catalog-wrapper">
            <h3 class="h4 fw-bold color-dark">🪟 Croquis Interno de Salón</h3>
            <p class="text-muted small">Haz clic sobre una mesa libre para separarla.</p>
            
            <div class="minimap-container">
                <div class="map-wall map-windows">Zona de Ventanas</div>
                <?php foreach ($mesasBD as $m): 
                    $valueRaw = $m['id_mesa'] . '|' . $m['capacidad'];
                    $icon = ($m['capacidad'] <= 2) ? '🪑' : (($m['capacidad'] <= 4) ? '☕' : '🛋️');
                    $claseOcupada = ($m['estado'] !== 'disponible') ? 'ocupada' : '';
                ?>
                    <div class="minimap-mesa <?php echo $claseOcupada; ?>" data-valor="<?php echo $valueRaw; ?>">
                        <span class="m-icon"><?php echo $icon; ?></span>
                        <div class="m-label">Mesa <?php echo $m['numero_mesa']; ?></div>
                        <div class="m-cap"><?php echo $m['capacidad']; ?> px</div>
                    </div>
                <?php endforeach; ?>
                <div class="map-wall map-entrance">🚪 Entrada Principal</div>
                <div class="map-wall map-bathroom">🚻 Baños</div>
            </div>
        </div>

        <div class="cart-sidebar">
            <h3 class="h4 fw-bold color-dark mb-3">📝 Agenda y Paga</h3>
            <form action="procesar_reserva.php" method="POST" id="reservaForm">
                <input type="hidden" name="mesa" id="txt-mesa-select" required>
                <input type="hidden" name="metodo_pago" id="txt-metodo-web" value="Yape">

                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label fw-bold mb-1 small">📅 Fecha:</label><input type="date" name="fecha" class="form-control form-control-sm" min="<?php echo $today; ?>" value="<?php echo $today; ?>" required></div>
                    <div class="col-6"><label class="form-label fw-bold mb-1 small">⏰ Hora:</label><input type="time" name="hora" class="form-control form-control-sm" min="08:00" max="21:00" required></div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold mb-1 small">💬 Observaciones:</label><textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Ej: Cumpleaños..."></textarea></div>

                <div id="box-pago-reserva" style="display:none; margin-top:1.5rem;">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-2 border-bottom pb-2">
                        <span class="fw-bold" style="color:#1a535c;">Garantía a pagar:</span><span class="fw-bold fs-5 color-primary" id="lbl-costo-reserva">S/. 0.00</span>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><div class="method-card active py-1" data-method="Yape"><span class="fs-5">📱</span> Yape</div></div>
                        <div class="col-6"><div class="method-card py-1" data-method="PagoEfectivo"><span class="fs-5">🏦</span> PagoEfectivo</div></div>
                    </div>
                    <div id="panel-yape" class="gateway-panel active p-2">
                        <p class="mb-1 small">Yapea al número:</p><h5 class="fw-bold text-purple mb-0">987 654 321</h5>
                    </div>
                    <div id="panel-pagoefectivo" class="gateway-panel p-2">
                        <p class="mb-0 small">Dicta este código CIP:</p><div class="cip-code fs-4 my-1"><?php echo $cipFalso; ?></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-cachito-custom w-100 mt-3 py-2" id="btn-guardar-reserva" disabled>💾 Confirmar Reserva</button>
            </form>
        </div>
    </div>
  </main>
  <script src="../assets/js/cliente.js"></script>
</body>
</html>