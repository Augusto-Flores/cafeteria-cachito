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

$stmtUser = $pdo->prepare('SELECT direccion, telefono FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUser->execute([$userId]);
$clienteInfo = $stmtUser->fetch();

if (empty($clienteInfo['direccion']) || empty($clienteInfo['telefono'])) {
    header('Location: perfil.php');
    exit;
}

// ==========================================
// PROCESAMIENTO DEL PAGO Y TRANSACCIÓN
// ==========================================
// Ahora PHP busca este valor gracias al input oculto en el formulario
if (isset($_POST['confirmar_pago_final'])) {
    $metodoElegido = sanitize_input($_POST['metodo_pago'] ?? 'Efectivo');
    $orderJson = $_POST['order_json'] ?? '';
    $totalAmount = (float)($_POST['total_amount'] ?? 0.0);
    $order = json_decode($orderJson, true);

    if (empty($order) || $totalAmount <= 0) {
        header('Location: catalogo.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtVenta = $pdo->prepare('INSERT INTO ventas (usuario_id, total, metodo_pago, fecha_creacion) VALUES (?, ?, ?, NOW())');
        $stmtVenta->execute([$userId, $totalAmount, $metodoElegido]);

        $inventoryStmt = $pdo->prepare('SELECT id_insumo, cantidad_actual FROM inventario WHERE nombre = ? LIMIT 1');
        $inventoryUpdateStmt = $pdo->prepare('UPDATE inventario SET cantidad_actual = cantidad_actual - ? WHERE id_insumo = ?');
        
        $recipeMap = [
            'Espresso'           => ['Café en grano' => 0.007, 'Agua' => 0.03],
            'Americano'          => ['Café en grano' => 0.007, 'Agua' => 0.15],
            'Cappuccino'         => ['Café en grano' => 0.007, 'Leche entera' => 0.12],
            'Latte'              => ['Café en grano' => 0.007, 'Leche entera' => 0.18],
            'Iced Latte'         => ['Café en grano' => 0.007, 'Leche entera' => 0.18, 'Vasos desechables' => 1.0],
            'Croissant Clásico'  => ['Croissants' => 1.0],
            'Torta de Chocolate' => ['Harina' => 0.05, 'Azúcar' => 0.02]
        ];

        foreach ($order as $item) {
            $pStmt = $pdo->prepare('SELECT nombre FROM productos WHERE id_producto = ?');
            $pStmt->execute([(int)$item['id']]);
            $pName = $pStmt->fetchColumn();

            if ($pName && isset($recipeMap[$pName])) {
                foreach ($recipeMap[$pName] as $insumo => $gasto) {
                    $inventoryStmt->execute([$insumo]);
                    $inv = $inventoryStmt->fetch();
                    if ($inv) {
                        $gastoTotal = $gasto * (int)$item['cantidad'];
                        $inventoryUpdateStmt->execute([$gastoTotal, $inv['id_insumo']]);
                    }
                }
            }
        }

        $pdo->commit();
        header('Location: catalogo.php?success=1');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error en Transacción de Venta: ' . $e->getMessage());
        $errorTransaccion = "Error al procesar el pedido. Por favor, inténtalo de nuevo.";
    }
}

$orderJson = $_POST['order_json'] ?? '';
$totalAmount = (float)($_POST['total_amount'] ?? 0.0);
$cipFalso = rand(10000000, 99999999); 
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>💳 Checkout - Cafetería Cachito</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body class="bg-cliente-main">

  <div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <main class="catalog-wrapper w-100 checkout-wrapper" role="main">
        
        <h2 class="text-center mb-4 color-primary">💰 Finalizar Pedido</h2>

        <?php if (isset($errorTransaccion)): ?>
            <div class="alert alert-custom-danger alert-danger" role="alert"><?php echo $errorTransaccion; ?></div>
        <?php endif; ?>

        <div class="checkout-box mb-4">
            <h5 class="border-bottom pb-2 mb-3 color-dark">Resumen de Envío</h5>
            <div class="mb-2"><strong>👤 Cliente:</strong> <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="mb-2"><strong>📍 Dirección:</strong> <?php echo htmlspecialchars($clienteInfo['direccion'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="mb-3"><strong>📱 Teléfono:</strong> <?php echo htmlspecialchars($clienteInfo['telefono'], ENT_QUOTES, 'UTF-8'); ?></div>
            
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <span class="fs-5 text-muted">Total a Pagar:</span>
                <span class="fs-4 fw-bold text-success">S/. <?php echo number_format($totalAmount, 2); ?></span>
            </div>
        </div>

        <h5 class="mb-3 color-dark">Selecciona tu Medio de Pago</h5>
        
        <div class="d-flex justify-content-between gap-2 mb-4">
            <div class="method-card active w-100" data-method="Efectivo">
                <div class="fs-3 mb-1">💵</div> Efectivo
            </div>
            <div class="method-card w-100" data-method="Yape">
                <div class="fs-3 mb-1">📱</div> Yape
            </div>
            <div class="method-card w-100" data-method="PagoEfectivo">
                <div class="fs-3 mb-1">🏦</div> PagoEfectivo
            </div>
        </div>

        <div id="panel-efectivo" class="gateway-panel active">
            <p class="text-muted mb-0">El motorizado cobrará el monto exacto al entregar tu pedido en la puerta de tu domicilio.</p>
        </div>

        <div id="panel-yape" class="gateway-panel">
            <img src="../assets/img/pago/qr.jpg" alt="QR Yape" class="img-fluid qr-image mb-3">
            <p class="mb-1">Escanea el QR o yapea al número:</p>
            <h4 class="fw-bold text-purple">987 654 321</h4>
            <p class="text-muted small mb-0">Titular: Cafetería Cachito S.A.C.</p>
        </div>

        <div id="panel-pagoefectivo" class="gateway-panel">
            <img src="../assets/img/pago/pagoefectivo.jpg" alt="Logo PagoEfectivo" class="img-fluid bank-logo mb-3" style="max-width: 180px;">
            <p class="mb-0">Dicta este código CIP en cualquier agente o banca móvil:</p>
            <div class="cip-code"><?php echo $cipFalso; ?></div>
            <p class="text-danger small fw-bold">⏳ Este código expira en 30 minutos.</p>
        </div>

        <form action="pago_simulado.php" method="POST" id="checkoutForm" class="mt-4">
            <input type="hidden" name="confirmar_pago_final" value="1">
            
            <input type="hidden" name="order_json" value="<?php echo htmlspecialchars($orderJson, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
            <input type="hidden" name="metodo_pago" id="txt-metodo-web" value="Efectivo">
            
            <div class="d-flex gap-3">
                <a href="catalogo.php" class="btn btn-outline-secondary w-50 py-2 fw-bold">⬅️ Volver</a>
                <button type="submit" id="client-btn-submit" class="btn btn-cachito-custom w-50 py-2">✅ Confirmar Pedido</button>
            </div>
        </form>
    </main>
  </div>
  
  <script src="../assets/js/cliente.js"></script>
</body>
</html>