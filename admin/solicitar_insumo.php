<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'Administrador') {
    $insumo_id = (int)$_POST['insumo_id'];
    $cantidad = (float)$_POST['cantidad'];
    
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO pedidos_insumos (insumo_id, cantidad, estado) VALUES (?, ?, 'Pendiente')");
        $stmt->execute([$insumo_id, $cantidad]);
        $_SESSION['admin_success'] = '📦 La orden de compra ha sido enviada al sistema de logística.';
    } catch (Exception $e) {
        $_SESSION['admin_error'] = '❌ Ocurrió un error al intentar despachar la solicitud.';
    }
}
header('Location: dashboard_admin.php');
exit;