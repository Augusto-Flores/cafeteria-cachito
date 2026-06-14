<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservas.php');
    exit;
}

$post = sanitize_array($_POST);
$fecha = $post['fecha'] ?? '';
$hora = $post['hora'] ?? '';
$mesaRaw = $post['mesa'] ?? '';
$observaciones = $post['observaciones'] ?? '';
$horaTimestamp = strtotime($hora);
$horaApertura = strtotime('08:00');
$horaCierre = strtotime('21:00');

if ($horaTimestamp < $horaApertura || $horaTimestamp > $horaCierre) {
    echo "<script>alert('⚠️ El horario de atención de la cafetería es de 08:00 AM a 09:00 PM. Por favor, selecciona una hora válida.'); window.location.href='reservas.php';</script>";
    exit;
}

// Desglosar la cadena del JS: Ejemplo "id_mesa|capacidad"
$partes = explode('|', $mesaRaw);
if (count($partes) !== 2) {
    echo "<script>alert('⚠️ Estructura de mesa incorrecta.'); window.location.href='reservas.php';</script>";
    exit;
}

$idMesaReal = (int)$partes[0];
$capacidadMesa = (int)$partes[1];
$clienteId = (int)$_SESSION['user_id'];

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // 🛡️ CONTROL DE CONCURRENCIA: Comprobar si la mesa no ha sido reservada en ese bloque horario por otro cliente
    $checkStmt = $pdo->prepare('SELECT id_reserva FROM reservas WHERE mesa_id = ? AND fecha = ? AND hora = ? AND estado = "Activa" LIMIT 1');
    $checkStmt->execute([$idMesaReal, $fecha, $hora]);
    if ($checkStmt->fetch()) {
        throw new RuntimeException("La ubicación elegida ya ha sido separada en ese bloque horario.");
    }

    // Inserción limpia acorde al DDL v2.0
    $stmt = $pdo->prepare('INSERT INTO reservas (fecha, hora, capacidad_mesa, cliente_id, mesa_id, estado, observaciones) VALUES (?, ?, ?, ?, ?, "Activa", ?)');
    $stmt->execute([$fecha, $hora, $capacidadMesa, $clienteId, $idMesaReal, $observaciones]);

    // Opcional: Cambiar estado a ocupada para que no figure en el mapa de hoy
    $updateMesa = $pdo->prepare('UPDATE mesas SET estado = "ocupada" WHERE id_mesa = ?');
    $updateMesa->execute([$idMesaReal]);

    $pdo->commit();
    echo "<script>alert('📅 ¡Tu reserva y pago de garantía se han registrado exitosamente en phpMyAdmin!'); window.location.href='reservas.php';</script>";
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<script>alert('❌ Error Operativo: " . addslashes($e->getMessage()) . "'); window.location.href='reservas.php';</script>";
    exit;
}