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
$metodoPago = $post['metodo_pago'] ?? 'Efectivo';
$observaciones = $post['observaciones'] ?? '';

date_default_timezone_set('America/Lima');
$currentDate = date('Y-m-d');
$currentTime = date('H:i');

$horaTimestamp = strtotime($hora);
$horaApertura = strtotime('08:00');
$horaCierre = strtotime('21:00');

// 1. Validar que esté dentro del horario de atención
if ($horaTimestamp < $horaApertura || $horaTimestamp > $horaCierre) {
    $_SESSION['reserva_error'] = '⚠️ El horario de atención es de 08:00 AM a 09:00 PM.';
    header('Location: reservas.php');
    exit;
}

// 2. Validar que no reserve en una hora que ya pasó (Viajes en el tiempo)
if ($fecha < $currentDate || ($fecha === $currentDate && $hora <= $currentTime)) {
    $_SESSION['reserva_error'] = '⚠️ No puedes realizar una reserva en una fecha u hora que ya transcurrió.';
    header('Location: reservas.php');
    exit;
}

$partes = explode('|', $mesaRaw);
if (count($partes) !== 2) {
    $_SESSION['reserva_error'] = '⚠️ Selecciona una mesa válida desde el croquis del salón.';
    header('Location: reservas.php');
    exit;
}

$idMesaReal = (int)$partes[0];
$capacidadMesa = (int)$partes[1];
$clienteId = (int)$_SESSION['user_id'];

// Formateamos la observación para incluir el método de pago usado en la pasarela
$observacionFinal = "[Pago Garantía: " . strtoupper($metodoPago) . "] " . $observaciones;

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // 3. REGLA DE NEGOCIO: Límite de 1 reserva activa por cliente
    $stmtLimit = $pdo->prepare('SELECT id_reserva FROM reservas WHERE cliente_id = ? AND estado = "Activa" LIMIT 1');
    $stmtLimit->execute([$clienteId]);
    if ($stmtLimit->fetch()) {
        throw new RuntimeException("Ya cuentas con una reserva activa. Solo se permite 1 mesa por cuenta.");
    }

    // 4. CONTROL DE CONCURRENCIA: Comprobar si la mesa ya fue tomada por otro
    $checkStmt = $pdo->prepare('SELECT id_reserva FROM reservas WHERE mesa_id = ? AND fecha = ? AND hora = ? AND estado = "Activa" LIMIT 1');
    $checkStmt->execute([$idMesaReal, $fecha, $hora]);
    if ($checkStmt->fetch()) {
        throw new RuntimeException("La mesa seleccionada acaba de ser ocupada en ese horario. Por favor, elige otra.");
    }

    $stmt = $pdo->prepare('INSERT INTO reservas (fecha, hora, capacidad_mesa, cliente_id, mesa_id, estado, observaciones) VALUES (?, ?, ?, ?, ?, "Activa", ?)');
    $stmt->execute([$fecha, $hora, $capacidadMesa, $clienteId, $idMesaReal, $observacionFinal]);

    $updateMesa = $pdo->prepare('UPDATE mesas SET estado = "ocupada" WHERE id_mesa = ?');
    $updateMesa->execute([$idMesaReal]);

    $pdo->commit();
    
    $_SESSION['reserva_success'] = '¡Reserva confirmada! Hemos procesado tu pago de garantía y bloqueado tu mesa.';
    header('Location: reservas.php');
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['reserva_error'] = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header('Location: reservas.php');
    exit;
}