<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'Administrador') {
    $id_pedido = (int)$_POST['id_pedido'];
    $insumo_id = (int)$_POST['insumo_id'];
    $cantidad = (float)$_POST['cantidad'];
    
    $pdo = getPDO();
    $pdo->beginTransaction();
    try {
        // Cambiar estado del pedido
        $stmt1 = $pdo->prepare("UPDATE pedidos_insumos SET estado='Recibido', fecha_recepcion=NOW() WHERE id_pedido=?");
        $stmt1->execute([$id_pedido]);
        // Sumar al inventario
        $stmt2 = $pdo->prepare("UPDATE inventario SET cantidad_actual = cantidad_actual + ?, fecha_actualizacion=NOW() WHERE id_insumo=?");
        $stmt2->execute([$cantidad, $insumo_id]);
        
        $pdo->commit();
        $_SESSION['admin_success'] = '✅ Insumo ingresado al almacén.';
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_error'] = '❌ Error al ingresar insumo.';
    }
}
header('Location: dashboard_admin.php');
exit;