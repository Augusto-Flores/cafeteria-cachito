<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

$alertaExito = $_SESSION['reserva_success'] ?? null;
$alertaError = $_SESSION['reserva_error'] ?? null;
unset($_SESSION['reserva_success'], $_SESSION['reserva_error']);

try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id_mesa, numero_mesa, capacidad, estado FROM mesas ORDER BY numero_mesa ASC');
    $mesasBD = $stmt->fetchAll();
} catch (PDOException $e) {
    $mesasBD = [];
}

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
<body>

  <header class="site-header">
    <div class="header-content">
      <h1>📅 Reservación de Mesas</h1>
      <nav class="user-info">
        <a href="catalogo.php" class="btn btn-outline" style="border-color:white; color:white; padding:0.4rem 1rem; font-size:0.85rem;">🛵 Pedir Delivery</a>
        <a href="../auth/logout.php" class="btn btn-outline" style="border-color:#ffcccc; color:#ffcccc; padding:0.4rem 1rem; font-size:0.85rem;">🚪 Salir</a>
      </nav>
    </div>
  </header>

  <div class="main-container">
    
    <?php if ($alertaExito): ?>
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-left: 5px solid var(--color-success);">
            <span style="font-size: 2rem; margin-right: 1rem;">📅</span>
            <div><h5 class="alert-heading mb-1 fw-bold">¡Mesa Confirmada!</h5><p class="mb-0"><?php echo htmlspecialchars($alertaExito, ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
    <?php endif; ?>

    <?php if ($alertaError): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-left: 5px solid var(--color-danger);">
            <span style="font-size: 2rem; margin-right: 1rem;">⚠️</span>
            <div><h5 class="alert-heading mb-1 fw-bold">Atención</h5><p class="mb-0"><?php echo htmlspecialchars($alertaError, ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
    <?php endif; ?>

    <div class="client-layout">
        
        <div class="catalog-wrapper">
            <h3 class="h4 fw-bold" style="color:var(--color-dark);">🪟 Croquis Interno de Salón (v2.0)</h3>
            <p class="text-muted small">Haz clic sobre una mesa libre para separarla.</p>
            
            <div class="minimap-container">
                <!-- Ventanales -->
                <div class="map-wall map-windows">Zona de Ventanas (Vista a la Calle)</div>

                <!-- Dibujado de Mesas Dinámico -->
                <?php foreach ($mesasBD as $m): 
                    $valueRaw = $m['id_mesa'] . '|' . $m['capacidad'];
                    $icon = ($m['capacidad'] <= 2) ? '🪑' : (($m['capacidad'] <= 4) ? '☕' : '🛋️');
                    $claseOcupada = ($m['estado'] !== 'disponible') ? 'ocupada' : '';
                    $onClick = ($m['estado'] === 'disponible') ? "onclick=\"seleccionarMesaCard(this, '{$valueRaw}')\"" : '';
                ?>
                    <div class="minimap-mesa <?php echo $claseOcupada; ?>" <?php echo $onClick; ?>>
                        <span class="m-icon"><?php echo $icon; ?></span>
                        <div class="m-label">Mesa <?php echo $m['numero_mesa']; ?></div>
                        <div class="m-cap"><?php echo $m['capacidad']; ?> px</div>
                    </div>
                <?php endforeach; ?>

                <!-- Entrada y Baños -->
                <div class="map-wall map-entrance">🚪 Entrada Principal</div>
                <div class="map-wall map-bathroom">🚻 Baños</div>
            </div>
        </div>

        <div class="cart-sidebar">
            <h3 class="h4 fw-bold" style="color:var(--color-dark); margin-bottom:1rem;">📝 Agenda y Paga</h3>
            
            <form action="procesar_reserva.php" method="POST" id="reservaForm">
                <input type="hidden" name="mesa" id="txt-mesa-select" required>
                <input type="hidden" name="metodo_pago" id="txt-metodo-web" value="Yape">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold mb-1" style="font-size:0.85rem;">📅 Fecha:</label>
                        <input type="date" name="fecha" class="form-control form-control-sm" min="<?php echo $today; ?>" value="<?php echo $today; ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold mb-1" style="font-size:0.85rem;">⏰ Hora:</label>
                        <input type="time" name="hora" class="form-control form-control-sm" min="08:00" max="21:00" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold mb-1" style="font-size:0.85rem;">💬 Observaciones:</label>
                    <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Ej: Cumpleaños..."></textarea>
                </div>

                <!-- PASARELA DE PAGO FLOTANTE -->
                <div id="box-pago-reserva" style="display:none; margin-top:1.5rem;">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-2 border-bottom pb-2">
                        <span class="fw-bold" style="color:#1a535c;">Garantía a pagar:</span>
                        <span class="fw-bold fs-5" style="color:#6f4e37;" id="lbl-costo-reserva">S/. 0.00</span>
                    </div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6"><div class="method-card active py-1" id="btn-yape" onclick="selectPaymentMethod('Yape')"><span class="fs-5">📱</span> Yape</div></div>
                        <div class="col-6"><div class="method-card py-1" id="btn-pagoefectivo" onclick="selectPaymentMethod('PagoEfectivo')"><span class="fs-5">🏦</span> PagoEfectivo</div></div>
                    </div>

                    <div id="panel-yape" class="gateway-panel active p-2">
                        <p class="mb-1 small">Yapea al número:</p>
                        <h5 class="fw-bold text-purple mb-0">987 654 321</h5>
                    </div>

                    <div id="panel-pagoefectivo" class="gateway-panel p-2">
                        <p class="mb-0 small">Dicta este código CIP:</p>
                        <div class="cip-code fs-4 my-1"><?php echo $cipFalso; ?></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-cachito w-100 mt-3 py-2" id="btn-guardar-reserva" style="background-color: var(--color-primary); color: white; border: none; border-radius: 0.5rem; font-weight: bold;" disabled>💾 Confirmar Pago y Reserva</button>
            </form>
        </div>

    </div>
  </div>

  <script>
    // Sobrescribimos el selector de mesa para habilitar el botón y la pasarela
    function seleccionarMesaCard(elemento, valorRaw) {
        document.getElementById('txt-mesa-select').value = valorRaw;
        
        document.querySelectorAll('.minimap-mesa').forEach(card => card.classList.remove('selected'));
        elemento.classList.add('selected');

        const partes = valorRaw.split('|');
        if(partes.length === 2) {
            const capacidad = parseInt(partes[1], 10);
            const costoGarantia = capacidad * 5.00;
            document.getElementById('lbl-costo-reserva').innerText = `S/. ${costoGarantia.toFixed(2)}`;
            document.getElementById('box-pago-reserva').style.display = 'block';
            document.getElementById('btn-guardar-reserva').disabled = false; // Se activa al elegir mesa
        }
    }

    // Funcionalidad de la pasarela
    function selectPaymentMethod(tipo) {
        document.getElementById('txt-metodo-web').value = tipo;
        document.getElementById('btn-yape').classList.remove('active');
        document.getElementById('btn-pagoefectivo').classList.remove('active');
        document.getElementById('panel-yape').classList.remove('active');
        document.getElementById('panel-pagoefectivo').classList.remove('active');
        
        document.getElementById(`btn-${tipo.toLowerCase()}`).classList.add('active');
        document.getElementById(`panel-${tipo.toLowerCase()}`).classList.add('active');
    }
  </script>
</body>
</html>