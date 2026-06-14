<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

// Control de seguridad
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barista') {
    header('Location: ../auth/login.php');
    exit;
}

try {
    $pdo = getPDO();
    // Consulta a DB: Productos ordenados por categoría
    $stmt = $pdo->query('SELECT id_producto, nombre, descripcion, precio, categoria FROM productos WHERE disponible = 1 ORDER BY categoria ASC, nombre ASC');
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
}

// Organización de productos por categorías (Solución al menú desordenado)
$productosAgrupados = [];
foreach ($productos as $prod) {
    $productosAgrupados[$prod['categoria']][] = $prod;
}

// Mensajes operativos
$successMessage = isset($_GET['success']) ? '✅ ¡Venta procesada con éxito!' : '';
$errorMessage = isset($_GET['error']) ? '❌ Error de Transacción.' : (isset($_GET['duplicate']) ? '⚠️ Alerta: Doble click bloqueado (30s).' : '');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>☕ Terminal POS - Barista v1.1</title>
  
  <link rel="stylesheet" href="../assets/css/style.css"> <link rel="stylesheet" href="../assets/css/barista.css"> </head>
<body>

  <header class="site-header">
    <div class="header-content">
      <h1>☕ Terminal Táctil POS</h1>
      <div class="user-info">
        <span>Cajero/a: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
        <a href="../auth/logout.php" class="btn btn-outline" style="border-color:white; color:white; padding: 0.4rem 1rem; margin-left: 1rem;">🚪 Cerrar Caja</a>
      </div>
    </div>
  </header>

  <div class="main-container">
    
    <?php if ($successMessage): ?><div class="alert alert-success"><?php echo $successMessage; ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>

    <div class="pos-grid-main">
      
      <div class="catalog-container">
        <h2 style="margin-bottom: 1rem;">📋 Productos Disponibles</h2>
        
        <?php foreach ($productosAgrupados as $categoria => $items): ?>
          <div class="cat-block">
            <h3 class="cat-header"><?php echo htmlspecialchars($categoria); ?></h3>
            <div class="prod-flex-wrapper">
              <?php foreach ($items as $prod): 
                $jsonParam = json_encode(['id' => (int)$prod['id_producto'], 'nombre' => $prod['nombre'], 'precio' => (float)$prod['precio']]);
              ?>
                <div class="prod-pos-card" onclick='agregarLineaPedido(<?php echo htmlspecialchars($jsonParam, ENT_QUOTES, 'UTF-8'); ?>)'>
                  <div>
                    <div class="p-title"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                  </div>
                  <div class="p-price">S/. <?php echo number_format((float)$prod['precio'], 2); ?></div>
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
            <button type="button" class="btn" onclick="vaciarComandaBloque()" style="background: transparent; color: #d63031; border: 1px solid #d63031; padding: 0.3rem 0.6rem; font-size: 0.8rem;">🗑️ Vaciar Todo</button>
          </div>
          
          <div id="msg-empty-pos" style="text-align: center; color: #888; padding: 4rem 0;">
            <span style="font-size: 2.5rem; display: block;">☕</span>
            Caja lista para recibir ítems.
          </div>

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
      <p style="color: #888; font-size: 0.85rem;">Declara cómo cancelará el cliente.</p>
      
      <div style="background: #f5f1e8; border-radius: 0.5rem; padding: 0.75rem; font-size: 1.4rem; font-weight: 700; margin: 1rem 0; text-align:center;">
        Total: <span id="modal-amount-label">S/. 0.00</span>
      </div>

      <div class="method-grid">
        <div class="method-card" id="m-efectivo" onclick="setMetodo('Efectivo')"><span style="font-size: 1.8rem; display:block;">💵</span>Efectivo</div>
        <div class="method-card" id="m-tarjeta" onclick="setMetodo('Tarjeta')"><span style="font-size: 1.8rem; display:block;">💳</span>Tarjeta</div>
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

  <script src="../assets/js/barista.js"></script>

</body>
</html>