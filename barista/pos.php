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

// 1. Obtener Productos con Imagen
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

// 2. Obtener Reservas DE HOY (Activas y En Curso)
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
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>☕ Terminal POS - Barista v2.0</title>
  <link rel="stylesheet" href="../assets/css/style.css"> 
  <link rel="stylesheet" href="../assets/css/barista.css"> 
</head>
<body>

  <header class="site-header">
    <div class="header-content">
      <h1>☕ Terminal Táctil POS</h1>
      <div class="user-info">
        <button class="btn btn-primary" onclick="abrirModalReservas()" style="margin-right: 1rem;">
            📅 Reservas de Hoy <span style="background:white; color:#6f4e37; border-radius:50%; padding:0 6px; margin-left:5px;"><?php echo count($reservasHoy); ?></span>
        </button>
        <span>Cajero/a: <strong><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <a href="../auth/logout.php" class="btn btn-outline" style="border-color:white; color:white; padding: 0.4rem 1rem; margin-left: 1rem;">🚪 Cerrar Caja</a>
      </div>
    </div>
  </header>

  <div class="main-container">
    <?php if ($successMessage): ?><div class="alert alert-success"><?php echo $successMessage; ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>

    <div class="pos-grid-main">
      <div class="catalog-container">
        <div class="pos-filters">
            <div class="btn-filter active" onclick="filtrarCategoriaPOS('TODOS', this)">Todos</div>
            <?php foreach (array_keys($categoriasUnicas) as $cat): ?>
                <div class="btn-filter" onclick="filtrarCategoriaPOS('<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>', this)"><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php foreach ($productosAgrupados as $categoria => $items): ?>
          <div class="cat-block" data-categoria="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>">
            <h3 class="cat-header"><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="prod-flex-wrapper">
              <?php foreach ($items as $prod): 
                $jsonParam = json_encode(['id' => (int)$prod['id_producto'], 'nombre' => $prod['nombre'], 'precio' => (float)$prod['precio']]);
                $imgUrl = !empty($prod['imagen_url']) ? $prod['imagen_url'] : 'https://loremflickr.com/400/400/coffee';
              ?>
                <div class="prod-pos-card" onclick='agregarLineaPedido(<?php echo htmlspecialchars($jsonParam, ENT_QUOTES, 'UTF-8'); ?>)'>
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
      </div>

      <div class="sidebar-order-container">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0;">🛒 Comanda Actual</h3>
            <button type="button" class="btn" onclick="vaciarComandaBloque()" style="background: transparent; color: #d63031; border: 1px solid #d63031; padding: 0.3rem 0.6rem; font-size: 0.8rem;">🗑️ Vaciar</button>
          </div>
          <div id="msg-empty-pos" style="text-align: center; color: #888; padding: 4rem 0;"><span style="font-size: 2.5rem; display: block;">☕</span>Caja lista.</div>
          <ul class="checkout-scroll-list" id="pos-items-list"></ul>
        </div>
        <div style="background: #f5f1e8; padding: 1.25rem; border-radius: 0.5rem; margin-top: auto;">
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem;"><span>Subtotal:</span><span id="pos-subtotal">S/. 0.00</span></div>
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 0.4rem; margin-bottom: 0.5rem;"><span>IGV (18%):</span><span id="pos-igv">S/. 0.00</span></div>
          <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.3rem; margin-bottom: 1rem;"><span>Total:</span><span id="pos-total">S/. 0.00</span></div>
          <button type="button" class="btn btn-primary" id="pos-action-pay" onclick="procesarMedioPagoModal()" style="width: 100%;" disabled>💳 Cobrar Comanda</button>
        </div>
      </div>
    </div>
  </div>

  <div class="payment-modal" id="pos-payment-modal">
      <div class="modal-content">
        <h3 style="margin-bottom: 0.25rem;">💰 Registrar Cobro</h3>
        <div style="background: #f5f1e8; border-radius: 0.5rem; padding: 0.75rem; font-size: 1.4rem; font-weight: 700; margin: 1rem 0; text-align:center;">Total: <span id="modal-amount-label">S/. 0.00</span></div>
        <div class="method-grid">
            <div class="method-card" id="m-efectivo" onclick="setMetodo('Efectivo')">💵 Efectivo</div>
            <div class="method-card" id="m-tarjeta" onclick="setMetodo('Tarjeta')">💳 Tarjeta</div>
        </div>
        <form action="procesar_venta.php" method="POST" id="pos-form-sender">
            <input type="hidden" name="order_json" id="input-json-order">
            <input type="hidden" name="total_amount" id="input-total-amount">
            <input type="hidden" name="metodo_pago" id="input-payment-method" value="Efectivo">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <button type="button" class="btn" style="border: 2px solid #6f4e37; background: transparent; color: #6f4e37;" onclick="cerrarMedioPagoModal()">❌ Volver</button>
            <button type="submit" class="btn btn-primary" id="pos-submit-final">💾 Confirmar</button>
            </div>
        </form>
      </div>
  </div>

  <div class="payment-modal" id="modal-reservas">
    <div class="modal-content" style="max-width: 600px; max-height: 80vh; overflow-y: auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;">📅 Reservas para Hoy</h3>
        <button class="delete-line-x" onclick="cerrarModalReservas()">×</button>
      </div>
      
      <?php if (empty($reservasHoy)): ?>
        <p class="text-center text-muted">No hay reservas pendientes para el día de hoy.</p>
      <?php else: ?>
        <?php foreach ($reservasHoy as $res): 
            $claseEstado = $res['estado'] === 'En Curso' ? 'estado-encurso' : '';
        ?>
            <div class="reserva-card <?php echo $claseEstado; ?>">
                <div>
                    <div class="r-time"><?php echo date('H:i', strtotime($res['hora'])); ?></div>
                    <div class="r-details">
                        <strong>Mesa <?php echo $res['numero_mesa']; ?></strong> • <?php echo htmlspecialchars($res['cliente'], ENT_QUOTES, 'UTF-8'); ?><br>
                        <em><?php echo htmlspecialchars($res['observaciones'], ENT_QUOTES, 'UTF-8'); ?></em>
                    </div>
                </div>
                <div class="r-actions">
                    <form action="procesar_reserva_barista.php" method="POST" style="margin:0;">
                        <input type="hidden" name="id_reserva" value="<?php echo $res['id_reserva']; ?>">
                        <input type="hidden" name="id_mesa" value="<?php echo $res['mesa_id']; ?>">
                        
                        <?php if ($res['estado'] === 'Activa'): ?>
                            <button type="submit" name="accion" value="llegada" class="btn btn-primary" style="padding:0.4rem 0.8rem; font-size:0.8rem;">🛎️ Llegó</button>
                            <button type="submit" name="accion" value="cancelar" class="btn btn-outline" style="padding:0.4rem 0.8rem; font-size:0.8rem; border-color:#d63031; color:#d63031;" onclick="return confirm('¿Cancelar por +15 min de retraso? La mesa se liberará.')">❌ No Show</button>
                        <?php elseif ($res['estado'] === 'En Curso'): ?>
                            <button type="submit" name="accion" value="finalizar" class="btn btn-primary" style="padding:0.4rem 0.8rem; font-size:0.8rem; background-color:#137333;">✅ Finalizar y Liberar</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script src="../assets/js/barista.js"></script>
</body>
</html>