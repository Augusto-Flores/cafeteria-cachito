<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrador') { header('Location: ../auth/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = (int)($_POST['id_producto'] ?? 0);
    $estado_actual = (int)($_POST['estado_actual'] ?? 0);
    $nuevo_estado = $estado_actual === 1 ? 0 : 1;

    if ($id_producto > 0) {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('UPDATE productos SET disponible = ? WHERE id_producto = ?');
            $stmt->execute([$nuevo_estado, $id_producto]);
            $_SESSION['admin_success'] = '✅ La disponibilidad del producto ha sido actualizada al instante.';
        } catch (Exception $e) {
            $_SESSION['admin_error'] = '❌ Ocurrió un error al cambiar la disponibilidad.';
        }
    }
}
header('Location: dashboard_admin.php');
exit;