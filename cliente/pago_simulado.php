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

// Obtener los datos del perfil del cliente para validación y resumen
$stmtUser = $pdo->prepare('SELECT direccion, telefono FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUser->execute([$userId]);
$clienteInfo = $stmtUser->fetch();

if (empty($clienteInfo['direccion']) || empty($clienteInfo['telefono'])) {
    header('Location: perfil.php');
    exit;
}

// PROCESAR GUARDADO FORMAL AL DAR CLIC EN CONFIRMAR PEDIDO
if (isset($_POST['confirmar_pago_final'])) {
    $metodoElegido = $_POST['metodo_pago'] ?? 'Efectivo';
    $orderJson = $_POST['order_json'] ?? '';
    $totalAmount = (float)($_POST['total_amount'] ?? 0.0);
    $order = json_decode($orderJson, true);

    if (empty($order) || $totalAmount <= 0) {
        header('Location: catalogo.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. REGISTRO EN LA TABLA VENTAS CON TU ID DE CLIENTE (usuario_id v2.0)
        $stmtVenta = $pdo->prepare('INSERT INTO ventas (usuario_id, total, metodo_pago, fecha_creacion) VALUES (?, ?, ?, NOW())');
        $stmtVenta->execute([$userId, $totalAmount, $metodoElegido]);

        // 2. DESCUENTO DE STOCK MEDIANTE MAPA DE RECETAS EN BARRA
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
        echo "<script>alert('🛒 ¡Delivery registrado con éxito! El motorizado llegará pronto.'); window.location.href='catalogo.php?success=1';</script>";
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<div style='color:red; padding:20px; font-family:sans-serif;'>Error en la transacción: " . htmlspecialchars($e->getMessage()) . "</div>";
        exit;
    }
}

// CAPTURA INICIAL DESDE EL CATÁLOGO
$orderJson = $_POST['order_json'] ?? '';
$totalAmount = (float)($_POST['total_amount'] ?? 0.0);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>💳 Confirmar Caja Virtual - Cafetería Cachito</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body style="background:#fcfbf9;">

  <div class="main-container" style="display:flex; justify-content:center; align-items:center; min-height:90vh;">
    <div class="catalog-wrapper" style="width:100%; max-width:520px; padding:2.5rem;">
        <h2 class="text-center" style="color:var(--color-primary); margin-bottom:1.5rem;">💰 Finalizar Despacho</h2>

        <div style="background:#faf8f5; border:1px solid #ebdccb; padding:1.25rem; border-radius:0.5rem; margin-bottom:1.5rem; font-size:0.95rem; line-height:1.6;">
            <div style="margin-bottom:0.3rem;"><strong>👤 Destinatario:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <div style="margin-bottom:0.3rem;"><strong>📍 Dirección de Envío:</strong> <?php echo htmlspecialchars($clienteInfo['direccion']); ?></div>
            <div style="margin-bottom:0.3rem;"><strong>📱 Teléfono de Alerta:</strong> <?php echo htmlspecialchars($clienteInfo['telefono']); ?></div>
            <hr style="margin:0.75rem 0; border:none; border-top:1px solid #ebdccb;">
            <div style="display:flex; justify-content:between; font-weight:700; font-size:1.15rem; color:var(--color-dark);">
                <span>Monto Neto de la Venta:</span><span>S/. <?php echo number_format($totalAmount, 2); ?></span>
            </div>
        </div>

        <h4 style="font-size:0.9rem; margin-bottom:0.75rem; text-transform:uppercase;">Selecciona tu Medio de Pago:</h4>
        <div class="payment-method-selector-web">
            <div class="method-web-card active" id="w-efectivo" onclick="setMetodoWeb('Efectivo')">💵 Efectivo Contraentrega</div>
            <div class="method-web-card" id="w-yape" onclick="setMetodoWeb('Yape')">📱 Yape (987654321)</div>
        </div>

        <form action="pago_simulado.php" method="POST" id="formFinalWeb">
            <input type="hidden" name="order_json" value="<?php echo htmlspecialchars($orderJson); ?>">
            <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
            <input type="hidden" name="metodo_pago" id="txt-metodo-web" value="Efectivo">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1.5rem;">
                <a href="catalogo.php" class="btn btn-outline" style="text-align:center;">❌ Volver</a>
                <button type="submit" name="confirmar_pago_final" id="btnSubmitWeb" class="btn btn-primary">💾 Confirmar Pedido</button>
            </div>
        </form>
    </div>
  </div>

  <script>
    function setMetodoWeb(tipo) {
        document.getElementById('txt-metodo-web').value = tipo;
        document.getElementById('w-efectivo').className = tipo === 'Efectivo' ? 'method-web-card active' : 'method-web-card';
        document.getElementById('w-yape').className = tipo === 'Yape' ? 'method-web-card active' : 'method-web-card';
    }
  </script>
</body>
</html>