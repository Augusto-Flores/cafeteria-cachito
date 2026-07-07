<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrador') { header('Location: ../auth/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_producto'] ?? 0);
    $nombre = $_POST['nombre'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $precio = (float)($_POST['precio'] ?? 0);
    $descripcion = $_POST['descripcion'] ?? '';
    $imagen_url = $_POST['imagen_url'] ?? '';

    $pdo = getPDO();
    if ($id === 0) {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, categoria, precio, descripcion, imagen_url, disponible) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$nombre, $categoria, $precio, $descripcion, $imagen_url]);
        $_SESSION['admin_success'] = '✅ Nuevo producto agregado a la carta exitosamente.';
    } else {
        $stmt = $pdo->prepare("UPDATE productos SET nombre=?, categoria=?, precio=?, descripcion=?, imagen_url=? WHERE id_producto=?");
        $stmt->execute([$nombre, $categoria, $precio, $descripcion, $imagen_url, $id]);
        $_SESSION['admin_success'] = '✅ Datos del producto actualizados.';
    }
}
header('Location: dashboard_admin.php');
exit;