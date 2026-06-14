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

try {
    // Jalamos los productos activos ordenados
    $stmt = $pdo->query('SELECT id_producto, nombre, descripcion, precio, categoria FROM productos WHERE disponible = 1 ORDER BY categoria ASC, nombre ASC');
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
}

// Agrupar productos de la versión 2.0 por categoría
$categoriasAgrupadas = [];
foreach ($productos as $p) {
    $categoriasAgrupadas[$p['categoria']][] = $p;
}

// Consultar si el cliente tiene su Dirección y Teléfono completos en su Perfil
$stmtUser = $pdo->prepare('SELECT direccion, telefono FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUser->execute([$userId]);
$clienteInfo = $stmtUser->fetch();

$perfilIncompleto = (empty($clienteInfo['direccion']) || empty($clienteInfo['telefono']));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>☕ Catálogo de Delivery - Cafetería Cachito</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>

  <header class="site-header">
    <div class="header-content">
      <h1>☕ Cafetería Cachito - Menú Web</h1>
      <nav class="user-info">
        <span style="margin-right:1rem;">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
        <a href="reservas.php" class="btn btn-outline" style="border-color:white; color:white; padding:0.4rem 1rem; font-size:0.85rem;">📅 Reservar Mesa</a>
        <a href="perfil.php" class="btn btn-outline" style="border-color:var(--color-accent); color:var(--color-accent); padding:0.4rem 1rem; font-size:0.85rem;">👤 Mi Perfil</a>
        <a href="../auth/logout.php" class="btn btn-outline" style="border-color:#ffcccc; color:#ffcccc; padding:0.4rem 1rem; font-size:0.85rem;">🚪 Salir</a>
      </nav>
    </div>
  </header>

  <div class="main-container">
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">🛒 ¡Tu pedido de Delivery fue registrado de forma exitosa en la Base de Datos!</div>
    <?php endif; ?>

    <div class="client-layout">
        
        <div class="catalog-wrapper">
            <h2 class="h4 fw-bold" style="color:var(--color-dark); margin-bottom:0.5rem;">🛵 Realiza tu Pedido</h2>
            <p class="text-muted" style="font-size:0.85rem; margin-bottom:1.5rem;">Selecciona tus bebidas y postres favoritos. El motorizado saldrá inmediatamente.</p>
            
            <?php foreach ($categoriasAgrupadas as $categoria => $items): ?>
                <h3 style="color:var(--color-primary); margin-top:2rem; font-size:1.25rem; border-bottom:2px solid #ebdccb; padding-bottom:0.25rem; text-transform:uppercase; font-weight:700;"><?php echo htmlspecialchars($categoria); ?></h3>
                <div class="products-market-grid">
                    <?php foreach ($items as $prod): 
                        $jsonObj = json_encode(['id' => (int)$prod['id_producto'], 'nombre' => $prod['nombre'], 'precio' => (float)$prod['precio']]);
                    ?>
                        <div class="product-market-card">
                            <div class="p-info">
                                <div class="p-name"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                                <div class="p-desc"><?php echo htmlspecialchars($prod['descripcion'] ?? ''); ?></div>
                            </div>
                            <div class="p-action-area">
                                <span style="font-weight:700; color:var(--color-primary); font-size:1.1rem;">S/. <?php echo number_format((float)$prod['precio'], 2); ?></span>
                                <button type="button" class="btn btn-primary" onclick='comprarProducto(<?php echo htmlspecialchars($jsonObj, ENT_QUOTES, 'UTF-8'); ?>)' style="padding:0.4rem 0.8rem; font-size:0.8rem;">🛒 Agregar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-sidebar">
            <h3 class="h4 fw-bold" style="color:var(--color-dark); margin-bottom:1rem;">🛒 Tu Canasta</h3>
            
            <div id="client-cart-empty" style="text-align:center; padding:3rem 0; color:#888;">
                <span style="font-size:2.5rem; display:block;">🛵</span>
                No has agregado productos.
            </div>

            <ul id="client-cart-list" style="list-style:none; padding:0; margin:0;">
                </ul>

            <div style="background:#faf8f5; padding:1rem; border-radius:0.5rem; margin-top:1.5rem; font-size:0.85rem; border:1px solid #ebdccb;">
                <div style="display:flex; justify-content:between; margin-bottom:0.25rem;"><span>Subtotal:</span><span id="lbl-subtotal">S/. 0.00</span></div>
                <div style="display:flex; justify-content:between; margin-bottom:0.5rem; border-bottom:1px solid #ddd; padding-bottom:0.3rem;"><span>Motorizado:</span><span id="lbl-delivery">S/. 0.00</span></div>
                <div style="display:flex; justify-content:between; font-weight:700; font-size:1.2rem; color:var(--color-dark);"><span>Total General:</span><span id="lbl-total">S/. 0.00</span></div>
            </div>

            <form action="pago_simulado.php" method="POST" id="checkoutForm" style="margin-top:1.5rem;">
                <input type="hidden" name="order_json" id="hid-order-json">
                <input type="hidden" name="subtotal" id="hid-subtotal">
                <input type="hidden" name="total_amount" id="hid-total">
                
                <?php if ($perfilIncompleto): ?>
                    <div style="background:#fff0f0; border:1px solid #ffb3b3; color:#a80000; padding:1rem; border-radius:0.5rem; font-size:0.85rem; font-weight:600; text-align:center;">
                        ⚠️ Dirección incompleta.<br>Actualízala en tu <a href="perfil.php" style="color:var(--color-primary); text-decoration:underline;">Perfil</a> para habilitar el Delivery.
                    </div>
                <?php else: ?>
                    <div style="background:#f0f7ff; border:1px solid #bddeff; padding:0.85rem; border-radius:0.5rem; font-size:0.85rem; color:#2a52be; margin-bottom:1rem;">
                        <strong>📍 Envío configurado a:</strong><br>
                        <?php echo htmlspecialchars($clienteInfo['direccion']); ?>
                    </div>
                    <button type="submit" class="btn btn-primary" id="client-btn-submit" style="width:100%; padding:0.85rem;" disabled>💳 Proceder al Pago</button>
                <?php endif; ?>
            </form>
        </div>

    </div>
  </div>

  <script src="../assets/js/cliente.js"></script>
</body>
</html>