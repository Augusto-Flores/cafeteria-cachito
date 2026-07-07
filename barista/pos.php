<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barista') {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = getPDO();

// 1. Obtener Productos
try {
    $stmt = $pdo->query('SELECT id_producto, nombre, precio, categoria, imagen_url FROM productos WHERE disponible = 1 ORDER BY categoria ASC, nombre ASC');
    $productos = $stmt->fetchAll();
} catch (PDOException $e) { $productos = []; }

$productosAgrupados = [];
$categoriasUnicas = [];
foreach ($productos as $prod) {
    $productosAgrupados[$prod['categoria']][] = $prod;
    $categoriasUnicas[$prod['categoria']] = true;
}

// 2. Obtener Reservas DE HOY
try {
    $stmtRes = $pdo->query("SELECT r.id_reserva, r.hora, r.estado, r.observaciones, r.mesa_id, u.nombre as cliente, m.numero_mesa 
                            FROM reservas r 
                            JOIN usuarios u ON r.cliente_id = u.id_usuario 
                            JOIN mesas m ON r.mesa_id = m.id_mesa 
                            WHERE r.fecha = CURRENT_DATE AND r.estado IN ('Activa', 'En Curso') 
                            ORDER BY r.hora ASC");
    $reservasHoy = $stmtRes->fetchAll();
} catch (PDOException $e) { $reservasHoy = []; }

$successMessage = $_SESSION['pos_success'] ?? '';
$errorMessage = $_SESSION['pos_error'] ?? '';
unset($_SESSION['pos_success'], $_SESSION['pos_error']);

// Determinar la categoría inicial (Ocultar el resto)
$firstCat = !empty($categoriasUnicas) ? array_key_first($categoriasUnicas) : '';

// 3. LÓGICA DE TURNOS AUTOMÁTICOS (Según hora de Lima, Perú)
date_default_timezone_set('America/Lima');
$horaActual = (int) date('G'); // Formato 24 horas (0 a 23)

if ($horaActual >= 8 && $horaActual < 12) {
    $turnoAsignado = 'Turno Mañana';
} elseif ($horaActual >= 12 && $horaActual < 17) {
    $turnoAsignado = 'Turno Tarde';
} else {
    $turnoAsignado = 'Turno Noche';
}

// Extraemos solo la primera palabra del nombre del usuario para evitar duplicidades
// Si el usuario se llama "Barista Turno Mañana", esto extraerá solo "Barista"
$nombrePila = explode(' ', $_SESSION['user_name'])[0];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>☕ Terminal POS - Barista</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css"> 
  <link rel="stylesheet" href="../assets/css/barista.css"> 
</head>
<body class="bg-light">

  <header class="site-header">
    <div class="header-content d-flex justify-content-between align-items-center w-100">
      <h1 class="m-0 text-white">☕ Terminal Táctil POS</h1>
      <div class="d-flex align-items-center">
        <button class="btn btn-primary me-3 fw-bold" id="btn-abrir-reservas">
            📅 Reservas Hoy <span class="badge-pos-reserva"><?php echo count($reservasHoy); ?></span>
        </button>
        
        <span class="text-white me-3">
            Cajero: <strong><?php echo htmlspecialchars($nombrePila, ENT_QUOTES, 'UTF-8'); ?> - <?php echo $turnoAsignado; ?></strong>
        </span>
        
        <a href="../auth/logout.php" class="btn btn-outline-light btn-logout-pos">🚪 Cerrar Caja</a>
      </div>
    </div>
  </header>

  <main class="main-container container-fluid px-4">
    <?php if ($successMessage): ?><div class="alert alert-success mt-3"><?php echo $successMessage; ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="alert alert-danger mt-3"><?php echo $errorMessage; ?></div><?php endif; ?>

    <div class="pos-grid-main">
      <section class="catalog-container">
        
        <div class="pos-filters">
            <?php foreach (array_keys($categoriasUnicas) as $cat): 
                $isActive = ($cat === $firstCat) ? 'active' : '';
            ?>
                <button class="btn-filter btn-cat-filter <?php echo $isActive; ?>" data-cat="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <?php foreach ($productosAgrupados as $categoria => $items): 
            $displayMode = ($categoria === $firstCat) ? '' : 'd-none';
        ?>
          <div class="cat-block <?php echo $displayMode; ?>" data-cat-block="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>">
            <h3 class="cat-header"><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="prod-flex-wrapper">
              <?php foreach ($items as $prod): 
                $jsonParam = htmlspecialchars(json_encode(['id' => (int)$prod['id_producto'], 'nombre' => $prod['nombre'], 'precio' => (float)$prod['precio']]), ENT_QUOTES, 'UTF-8');
                $imgUrl = !empty($prod['imagen_url']) ? $prod['imagen_url'] : 'https://loremflickr.com/400/400/coffee';
              ?>
                <div class="prod-pos-card btn-add-prod" data-prod="<?php echo $jsonParam; ?>">
                  <div class="p-img-wrapper"><img src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" alt="img"></div>
                  <div class="p-content">
                    <div class="p-title"><?php echo htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="p-price">S/. <?php echo number_format((float)$prod['precio'], 2); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </section>

      <aside class="sidebar-order-container">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h3 class="m-0 fs-5 fw-bold">🛒 Comanda Actual</h3>
            <button type="button" class="btn-vaciar-pos" id="btn-vaciar-comanda">🗑️ Vaciar</button>
          </div>
          
          <div id="msg-empty-pos" class="empty-pos-msg">
            <span class="empty-pos-icon">☕</span>Caja lista para operar.
          </div>
          
          <ul class="checkout-scroll-list" id="pos-items-list"></ul>
        </div>
        
        <div class="pos-summary-box">
          <div class="d-flex justify-content-between text-muted mb-1"><span>Subtotal:</span><span id="pos-subtotal">S/. 0.00</span></div>
          <div class="d-flex justify-content-between text-muted border-bottom pb-2 mb-2"><span>IGV (18%):</span><span id="pos-igv">S/. 0.00</span></div>
          <div class="d-flex justify-content-between fw-bold fs-4 mb-3 color-dark"><span>Total:</span><span id="pos-total">S/. 0.00</span></div>
          <button type="button" class="btn btn-primary w-100 py-2 fw-bold fs-5" id="pos-action-pay" disabled>💳 Cobrar Comanda</button>
        </div>
      </aside>
    </div>
  </main>

  <div class="payment-modal" id="pos-payment-modal">
      <div class="modal-content-custom">
        <h3 class="mb-1 text-center color-dark fw-bold">💰 Registrar Cobro</h3>
        <p class="text-muted text-center mb-3 small">Selecciona la forma de pago del cliente.</p>
        
        <div class="modal-total-display">Total a Pagar: <span id="modal-amount-label">S/. 0.00</span></div>
        
        <div class="method-grid">
            <div class="method-card" data-metodo="Efectivo"><span class="icon-method">💵</span> Efectivo</div>
            <div class="method-card" data-metodo="Tarjeta"><span class="icon-method">💳</span> Tarjeta</div>
            <div class="method-card" data-metodo="Yape"><span class="icon-method">📱</span> Yape/Plin</div>
        </div>

        <form action="procesar_venta.php" method="POST" id="pos-form-sender" class="m-0">
            <input type="hidden" name="order_json" id="input-json-order">
            <input type="hidden" name="total_amount" id="input-total-amount">
            <input type="hidden" name="metodo_pago" id="input-payment-method" value="Efectivo">
            
            <div class="d-flex gap-3 mt-4">
                <button type="button" class="btn btn-outline-pos w-50 py-2" id="btn-cerrar-pago">❌ Volver</button>
                <button type="submit" class="btn btn-primary w-50 py-2 fw-bold" id="pos-submit-final">💾 Confirmar</button>
            </div>
        </form>
      </div>
  </div>

  <div class="payment-modal" id="modal-reservas">
    <div class="modal-content-custom modal-content-lg">
      <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="m-0 fs-4 color-dark fw-bold">📅 Agenda del Día</h3>
        <button class="delete-line-x btn-cerrar-reservas fs-2">&times;</button>
      </div>
      
      <?php if (empty($reservasHoy)): ?>
        <p class="text-center text-muted py-4">No hay reservas pendientes para el día de hoy.</p>
      <?php else: ?>
        <?php foreach ($reservasHoy as $res): 
            $claseEstado = $res['estado'] === 'En Curso' ? 'estado-encurso' : '';
        ?>
            <div class="reserva-card <?php echo $claseEstado; ?>">
                <div>
                    <div class="r-time"><?php echo date('H:i', strtotime($res['hora'])); ?></div>
                    <div class="r-details">
                        <strong>Mesa <?php echo $res['numero_mesa']; ?></strong> • <?php echo htmlspecialchars($res['cliente'], ENT_QUOTES, 'UTF-8'); ?><br>
                        <em class="text-muted"><?php echo htmlspecialchars($res['observaciones'], ENT_QUOTES, 'UTF-8'); ?></em>
                    </div>
                </div>
                <div class="r-actions">
                    <form action="procesar_reserva_barista.php" method="POST" class="m-0" id="form-reserva-<?php echo $res['id_reserva']; ?>">
                        <input type="hidden" name="id_reserva" value="<?php echo $res['id_reserva']; ?>">
                        <input type="hidden" name="id_mesa" value="<?php echo $res['mesa_id']; ?>">
                        <input type="hidden" name="accion" id="accion-<?php echo $res['id_reserva']; ?>" value="">
                        
                        <?php if ($res['estado'] === 'Activa'): ?>
                            <button type="button" class="btn btn-primary btn-sm fw-bold btn-accion-reserva" data-id="<?php echo $res['id_reserva']; ?>" data-accion="llegada">🛎️ Llegó</button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-accion-reserva" data-id="<?php echo $res['id_reserva']; ?>" data-accion="cancelar">❌ No Show</button>
                        <?php elseif ($res['estado'] === 'En Curso'): ?>
                            <button type="button" class="btn btn-success btn-sm fw-bold btn-accion-reserva" data-id="<?php echo $res['id_reserva']; ?>" data-accion="finalizar">✅ Finalizar y Liberar</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="payment-modal" id="modal-confirm">
    <div class="modal-content-custom modal-confirm-box">
      <span class="icon-confirm-modal" id="confirm-icon">⚠️</span>
      <h3 class="mb-2 color-dark fw-bold" id="confirm-title">¿Estás seguro?</h3>
      <p class="text-muted mb-4" id="confirm-message">Esta acción no se puede deshacer.</p>
      
      <div class="d-flex gap-3">
          <button type="button" class="btn btn-outline-pos w-50 py-2" id="btn-confirm-cancel">Cancelar</button>
          <button type="button" class="btn w-50 py-2 fw-bold" id="btn-confirm-accept">Aceptar</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/barista.js"></script>
</body>
</html>