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
    $stmt = $pdo->query('SELECT id_producto, nombre, descripcion, precio, categoria, imagen_url FROM productos WHERE disponible = 1 ORDER BY categoria ASC, nombre ASC');
    $productos = $stmt->fetchAll();
} catch (PDOException $e) { $productos = []; }

$categoriasAgrupadas = [];
foreach ($productos as $p) { $categoriasAgrupadas[$p['categoria']][] = $p; }

$stmtUser = $pdo->prepare('SELECT direccion, telefono FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUser->execute([$userId]);
$clienteInfo = $stmtUser->fetch();
$perfilIncompleto = (empty($clienteInfo['direccion']) || empty($clienteInfo['telefono']));

$firstCat = !empty($categoriasAgrupadas) ? array_key_first($categoriasAgrupadas) : '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>☕ Catálogo de Delivery - Cafetería Cachito</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body class="bg-cliente-main">
  <header class="site-header">
    <div class="header-content">
      <h1>☕ Cafetería Cachito - Menú Web</h1>
      <nav class="user-info">
        <span class="me-3">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <a href="reservas.php" class="btn btn-nav-header">📅 Reservar Mesa</a>
        <a href="perfil.php" class="btn btn-nav-accent">👤 Mi Perfil</a>
        <a href="../auth/logout.php" class="btn btn-nav-danger">🚪 Salir</a>
      </nav>
    </div>
  </header>

  <main class="main-container">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-custom-success alert-success d-flex align-items-center mb-4" role="alert">
            <span class="fs-1 me-3">🛵</span>
            <div><h5 class="alert-heading mb-1 fw-bold">¡Tu pedido está en camino!</h5><p class="mb-0">Hemos recibido tu orden exitosamente. Llegará en 20 a 30 minutos.</p></div>
        </div>
    <?php endif; ?>

    <div class="client-layout">
        <div class="catalog-wrapper">
            <h2 class="h4 fw-bold color-dark mb-2">🛵 Realiza tu Pedido</h2>
            <p class="text-muted small mb-4">Selecciona tus productos por categoría.</p>
            
            <div class="cat-filters-client">
                <?php foreach (array_keys($categoriasAgrupadas) as $cat): 
                    $isActive = ($cat === $firstCat) ? 'active' : '';
                ?>
                    <button class="btn-filter <?php echo $isActive; ?>" data-categoria="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($categoriasAgrupadas as $categoria => $items): 
                $displayMode = ($categoria === $firstCat) ? '' : 'd-none';
            ?>
                <div class="cat-block-cliente <?php echo $displayMode; ?>" data-categoria="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>">
                    <h3 class="category-title"><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="products-market-grid">
                        <?php foreach ($items as $prod): 
                            $imgUrl = !empty($prod['imagen_url']) ? $prod['imagen_url'] : 'https://loremflickr.com/400/400/food,coffee';
                        ?>
                            <div class="product-market-card">
                                <img src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid w-100" style="height: 180px; object-fit: cover;" loading="lazy">
                                <div class="p-info">
                                    <div class="p-name"><?php echo htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="p-desc"><?php echo htmlspecialchars($prod['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="p-action-area">
                                    <span class="fw-bold color-primary fs-5">S/. <?php echo number_format((float)$prod['precio'], 2); ?></span>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold btn-add-cart" data-id="<?php echo (int)$prod['id_producto']; ?>" data-nombre="<?php echo htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-precio="<?php echo (float)$prod['precio']; ?>">🛒 Agregar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-sidebar">
            <h3 class="h4 fw-bold color-dark mb-3">🛒 Tu Canasta</h3>
            <div id="client-cart-empty" class="cart-empty-msg">
                <span class="display-3 d-block">🛵</span>No has agregado productos.
            </div>
            <ul id="client-cart-list" class="list-unstyled p-0 m-0"></ul>
            <div class="cart-summary-box">
                <div class="d-flex justify-content-between mb-1"><span>Subtotal:</span><span id="lbl-subtotal">S/. 0.00</span></div>
                <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span>Motorizado:</span><span id="lbl-delivery">S/. 0.00</span></div>
                <div class="d-flex justify-content-between fw-bold fs-5 color-dark mt-2"><span>Total:</span><span id="lbl-total">S/. 0.00</span></div>
            </div>
            <form action="pago_simulado.php" method="POST" id="checkoutForm" class="mt-4">
                <input type="hidden" name="order_json" id="hid-order-json">
                <input type="hidden" name="subtotal" id="hid-subtotal">
                <input type="hidden" name="total_amount" id="hid-total">
                <?php if ($perfilIncompleto): ?>
                    <div class="address-warning">⚠️ Dirección incompleta.<br>Actualízala en tu <a href="perfil.php">Perfil</a>.</div>
                <?php else: ?>
                    <div class="address-info"><strong>📍 Envío a:</strong><br><?php echo htmlspecialchars($clienteInfo['direccion'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <button type="submit" class="btn btn-cachito-custom w-100 py-2" id="client-btn-submit" disabled>💳 Proceder al Pago</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
  </main>
  <script src="../assets/js/cliente.js"></script>
</body>
</html>