<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

// 1. CONTROL DE SEGURIDAD ESTRICTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barista') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit;
}

// 2. CAPTURA Y SANITIZACIÓN
$orderJson = $_POST['order_json'] ?? '';
$totalAmount = isset($_POST['total_amount']) ? (float) $_POST['total_amount'] : 0.0;
$metodoPago = isset($_POST['metodo_pago']) ? trim((string)$_POST['metodo_pago']) : 'Efectivo';

$order = json_decode($orderJson, true);

if (!is_array($order) || count($order) === 0 || $totalAmount <= 0) {
    $_SESSION['pos_error'] = '❌ La comanda está vacía o el monto a cobrar es inválido.';
    header('Location: pos.php');
    exit;
}

$baristaId = (int) $_SESSION['user_id'];

try {
    $pdo = getPDO();
    
    // 🛡️ CONTROL DE CONCURRENCIA AVANZADO (Bloqueo de Doble Click)
    $duplicateStmt = $pdo->prepare(
        'SELECT id_venta FROM ventas WHERE usuario_id = :usuario_id AND total = :total AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 SECOND) LIMIT 1'
    );
    $duplicateStmt->execute([
        ':usuario_id' => $baristaId,
        ':total'      => $totalAmount
    ]);

    if ($duplicateStmt->fetch()) {
        $_SESSION['pos_error'] = '⚠️ Cobro bloqueado: Se detectó una transacción idéntica hace menos de 30 segundos.';
        header('Location: pos.php');
        exit;
    }

    $pdo->beginTransaction();

    // 3. INSERCIÓN DE LA CABECERA DE VENTA
    $salesStmt = $pdo->prepare(
        'INSERT INTO ventas (usuario_id, total, metodo_pago, fecha_creacion) VALUES (:usuario_id, :total, :metodo_pago, NOW())'
    );
    $salesStmt->execute([
        ':usuario_id' => $baristaId,
        ':total'      => $totalAmount,
        ':metodo_pago'=> $metodoPago
    ]);
    
    $productStmt = $pdo->prepare('SELECT nombre FROM productos WHERE id_producto = :id LIMIT 1');
    $inventoryStmt = $pdo->prepare('SELECT id_insumo, cantidad_actual FROM inventario WHERE nombre = :nombre LIMIT 1');
    $inventoryUpdateStmt = $pdo->prepare('UPDATE inventario SET cantidad_actual = cantidad_actual - :qty, fecha_actualizacion = NOW() WHERE id_insumo = :id_insumo');

    // 4. MAPA DE RECETAS EN BARRA (Gestión de Inventario en Tiempo Real)
    $recipeMap = [
        'Espresso'            => ['Café en grano' => 0.007, 'Agua' => 0.03], 
        'Americano'           => ['Café en grano' => 0.007, 'Agua' => 0.15],
        'Cappuccino'          => ['Café en grano' => 0.007, 'Leche entera' => 0.12],
        'Latte'               => ['Café en grano' => 0.007, 'Leche entera' => 0.18],
        'Macchiato'           => ['Café en grano' => 0.007, 'Leche entera' => 0.05],
        'Mocha'               => ['Café en grano' => 0.007, 'Leche entera' => 0.15, 'Cacao en polvo' => 0.02],
        'Frappuccino Clásico' => ['Café en grano' => 0.007, 'Leche entera' => 0.20, 'Hielo' => 0.15, 'Vasos desechables' => 1.0],
        'Iced Latte'          => ['Café en grano' => 0.007, 'Leche entera' => 0.18, 'Hielo' => 0.15, 'Vasos desechables' => 1.0],
        'Croissant Clásico'   => ['Croissants' => 1.0],
        'Torta de Chocolate'  => ['Harina' => 0.05, 'Azúcar' => 0.02, 'Cacao en polvo' => 0.02]
    ];

    $calculatedSubtotal = 0.0;

    foreach ($order as $item) {
        $idProducto = (int) ($item['id'] ?? 0);
        $cantidad   = (int) ($item['cantidad'] ?? 0);
        $precioUnit = (float) ($item['precio'] ?? 0.0);

        if ($idProducto <= 0 || $cantidad <= 0 || $precioUnit <= 0.0) {
            throw new RuntimeException('La estructura del ítem fue corrompida.');
        }

        $calculatedSubtotal += ($precioUnit * $cantidad);

        $productStmt->execute([':id' => $idProducto]);
        $productData = $productStmt->fetch();

        if ($productData) {
            $productName = $productData['nombre'];

            // Descontar inventario si el producto tiene receta vinculada
            if (isset($recipeMap[$productName])) {
                foreach ($recipeMap[$productName] as $insumoNombre => $usoPorUnidad) {
                    $inventoryStmt->execute([':nombre' => $insumoNombre]);
                    $inventoryItem = $inventoryStmt->fetch();

                    if ($inventoryItem) {
                        $cantidadRequerida = $usoPorUnidad * $cantidad;
                        
                        // Validación matemática de stock suficiente
                        if ((float) $inventoryItem['cantidad_actual'] >= $cantidadRequerida) {
                            $inventoryUpdateStmt->execute([
                                ':qty'       => $cantidadRequerida,
                                ':id_insumo' => $inventoryItem['id_insumo']
                            ]);
                        } else {
                            throw new RuntimeException("Stock insuficiente de [{$insumoNombre}] para preparar el pedido.");
                        }
                    }
                }
            }
        }
    }

    // 5. ANTI-MANIPULACIÓN: Validación cruzada final del dinero
    if (abs($calculatedSubtotal - $totalAmount) > 0.05) {
        throw new RuntimeException('Los importes calculados en el servidor difieren del total enviado. Posible manipulación del cliente.');
    }

    $pdo->commit();
    $_SESSION['pos_success'] = '✅ ¡Venta registrada y stock descontado exitosamente!';
    header('Location: pos.php');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['pos_error'] = '❌ ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header('Location: pos.php');
    exit;
}