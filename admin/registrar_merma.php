<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrador') { header('Location: ../auth/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $insumo_id = isset($_POST['insumo_id']) ? (int) $_POST['insumo_id'] : 0;
    $cantidad = isset($_POST['cantidad']) ? (float) $_POST['cantidad'] : 0.0;
    $motivo = isset($_POST['motivo']) ? sanitize_input((string) $_POST['motivo']) : '';
    $fecha_registro = date('Y-m-d');

    if ($insumo_id <= 0 || $cantidad <= 0) {
        $_SESSION['admin_error'] = '❌ Los datos enviados para la merma no son válidos.';
        header('Location: dashboard_admin.php');
        exit;
    }

    try {
        $pdo = getPDO();
        $pdo->beginTransaction();

        $insert = $pdo->prepare('INSERT INTO registro_mermas (insumo_id, cantidad, motivo, fecha_registro, fecha_creacion) VALUES (?, ?, ?, ?, NOW())');
        $insert->execute([$insumo_id, $cantidad, $motivo, $fecha_registro]);

        $update = $pdo->prepare('UPDATE inventario SET cantidad_actual = cantidad_actual - ?, fecha_actualizacion = NOW() WHERE id_insumo = ?');
        $update->execute([$cantidad, $insumo_id]);

        $pdo->commit();
        $_SESSION['admin_success'] = '✅ La pérdida por merma ha sido descontada del stock principal.';
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['admin_error'] = '❌ Error crítico al intentar registrar la merma.';
    }
}
header('Location: dashboard_admin.php');
exit;