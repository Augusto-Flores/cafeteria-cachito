<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

// Control de acceso estricto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];

// Obtener los datos del perfil del cliente para validación y resumen
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

        // 1. Registro en la tabla ventas
        $stmtVenta = $pdo->prepare('INSERT INTO ventas (usuario_id, total, metodo_pago, fecha_creacion) VALUES (?, ?, ?, NOW())');
        $stmtVenta->execute([$userId, $totalAmount, $metodoElegido]);

        // 2. Descuento de stock mediante mapa de recetas
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
        
        // Redirección limpia sin usar JavaScript alert()
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

// CAPTURA INICIAL DESDE EL CATÁLOGO (Si no es un POST de confirmación)
$orderJson = $_POST['order_json'] ?? '';
$totalAmount = (float)($_POST['total_amount'] ?? 0.0);
$cipFalso = rand(10000000, 99999999); // Generamos un CIP aleatorio para la simulación
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
  
  <style>
    /* Estilos adicionales para la pasarela */
    .checkout-box { background: #faf8f5; border: 1px solid #ebdccb; border-radius: 0.75rem; padding: 1.5rem; }
    .method-card { border: 2px solid #eae1d4; border-radius: 0.5rem; padding: 1rem; text-align: center; cursor: pointer; transition: 0.2s; background: white; }
    .method-card.active { border-color: var(--color-primary); background: #faf6f0; color: var(--color-primary); font-weight: bold; box-shadow: 0 4px 10px rgba(111,78,55,0.1); }
    .gateway-panel { display: none; background: white; border: 1px solid #eae1d4; border-radius: 0.5rem; padding: 1.5rem; margin-top: 1rem; text-align: center; animation: fadeIn 0.3s; }
    .gateway-panel.active { display: block; }
    .cip-code { font-size: 2rem; font-weight: 900; letter-spacing: 2px; color: #d63031; margin: 1rem 0; }
  </style>
</head>
<body style="background:#fcfbf9;">

  <div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <main class="catalog-wrapper w-100" style="max-width: 600px; padding: 2.5rem;" role="main">
        
        <h2 class="text-center mb-4" style="color:var(--color-primary);">💰 Finalizar Pedido</h2>

        <?php if (isset($errorTransaccion)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $errorTransaccion; ?></div>
        <?php endif; ?>

        <div class="checkout-box mb-4">
            <h5 class="border-bottom pb-2 mb-3" style="color:var(--color-dark);">Resumen de Envío</h5>
            <div class="mb-2"><strong>👤 Cliente:</strong> <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="mb-2"><strong>📍 Dirección:</strong> <?php echo htmlspecialchars($clienteInfo['direccion'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="mb-3"><strong>📱 Teléfono:</strong> <?php echo htmlspecialchars($clienteInfo['telefono'], ENT_QUOTES, 'UTF-8'); ?></div>
            
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <span class="fs-5 text-muted">Total a Pagar:</span>
                <span class="fs-4 fw-bold text-success">S/. <?php echo number_format($totalAmount, 2); ?></span>
            </div>
        </div>

        <h5 class="mb-3" style="color:var(--color-dark);">Selecciona tu Medio de Pago</h5>
        
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="method-card active" id="btn-efectivo" onclick="selectPaymentMethod('Efectivo')">
                    <div class="fs-3 mb-1">💵</div> Efectivo
                </div>
            </div>
            <div class="col-4">
                <div class="method-card" id="btn-yape" onclick="selectPaymentMethod('Yape')">
                    <div class="fs-3 mb-1">📱</div> Yape
                </div>
            </div>
            <div class="col-4">
                <div class="method-card" id="btn-pagoefectivo" onclick="selectPaymentMethod('PagoEfectivo')">
                    <div class="fs-3 mb-1">🏦</div> PagoEfectivo
                </div>
            </div>
        </div>

        <div id="panel-efectivo" class="gateway-panel active">
            <p class="text-muted mb-0">El motorizado cobrará el monto exacto al entregar tu pedido en la puerta de tu domicilio.</p>
        </div>

        <div id="panel-yape" class="gateway-panel">
            <img src="https://loremflickr.com/150/150/qr,code" alt="QR Yape" class="img-fluid rounded mb-3" style="width: 120px;">
            <p class="mb-1">Escanea el QR o yapea al número:</p>
            <h4 class="fw-bold text-purple">987 654 321</h4>
            <p class="text-muted small mb-0">Titular: Cafetería Cachito S.A.C.</p>
        </div>

        <div id="panel-pagoefectivo" class="gateway-panel">
            <img src="https://loremflickr.com/200/50/logo,bank" alt="Bancos" class="img-fluid mb-2" style="opacity: 0.6;">
            <p class="mb-0">Dicta este código CIP en cualquier agente, bodega o banca móvil:</p>
            <div class="cip-code"><?php echo $cipFalso; ?></div>
            <p class="text-danger small fw-bold">⏳ Este código expira en 30 minutos.</p>
        </div>

        <form action="pago_simulado.php" method="POST" id="formFinalWeb" class="mt-4">
            <input type="hidden" name="order_json" value="<?php echo htmlspecialchars($orderJson, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
            <input type="hidden" name="metodo_pago" id="txt-metodo-web" value="Efectivo">
            
            <div class="d-flex gap-3">
                <a href="catalogo.php" class="btn btn-outline-secondary w-50 py-2 fw-bold">⬅️ Volver</a>
                <button type="submit" name="confirmar_pago_final" id="btnSubmitWeb" class="btn btn-cachito w-50 py-2 fw-bold" style="background-color: var(--color-primary); color: white; border: none;">✅ Confirmar Pedido</button>
            </div>
        </form>
        
    </main>
  </div>

  <script>
    function selectPaymentMethod(tipo) {
        // 1. Actualizar el input oculto para enviarlo a la BD
        document.getElementById('txt-metodo-web').value = tipo;
        
        // 2. Resetear clases de los botones
        document.getElementById('btn-efectivo').classList.remove('active');
        document.getElementById('btn-yape').classList.remove('active');
        document.getElementById('btn-pagoefectivo').classList.remove('active');
        
        // 3. Ocultar todos los paneles
        document.getElementById('panel-efectivo').classList.remove('active');
        document.getElementById('panel-yape').classList.remove('active');
        document.getElementById('panel-pagoefectivo').classList.remove('active');
        
        // 4. Activar el botón y panel seleccionado
        if (tipo === 'Efectivo') {
            document.getElementById('btn-efectivo').classList.add('active');
            document.getElementById('panel-efectivo').classList.add('active');
        } else if (tipo === 'Yape') {
            document.getElementById('btn-yape').classList.add('active');
            document.getElementById('panel-yape').classList.add('active');
        } else if (tipo === 'PagoEfectivo') {
            document.getElementById('btn-pagoefectivo').classList.add('active');
            document.getElementById('panel-pagoefectivo').classList.add('active');
        }
    }
  </script>
</body>
</html>