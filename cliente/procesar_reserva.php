<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

// 1. SEGURIDAD: Solo clientes pueden acceder a este script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservas.php');
    exit;
}

// 2. CAPTURA DE DATOS
$post = sanitize_array($_POST);
$fecha = $post['fecha'] ?? '';
$hora = $post['hora'] ?? '';
$mesaRaw = $post['mesa'] ?? '';
$metodoPago = $post['metodo_pago'] ?? 'Efectivo';
$observaciones = $post['observaciones'] ?? '';

date_default_timezone_set('America/Lima');
$currentDate = date('Y-m-d');
$currentTime = date('H:i');

try {
    $horaTimestamp = strtotime($hora);
    $horaApertura = strtotime('08:00');
    $horaCierre = strtotime('21:00');

    // 3. VALIDACIONES DE NEGOCIO
    if ($horaTimestamp < $horaApertura || $horaTimestamp > $horaCierre) {
        throw new RuntimeException('El horario de atención es de 08:00 AM a 09:00 PM.');
    }

    if ($fecha < $currentDate) {
        throw new RuntimeException('No puedes realizar reservas en una fecha pasada.');
    }

    if ($fecha === $currentDate && $horaTimestamp < strtotime($currentTime)) {
        throw new RuntimeException('No puedes reservar en una hora que ya ha pasado.');
    }

    if (empty($mesaRaw) || strpos($mesaRaw, '|') === false) {
        throw new RuntimeException('Por favor, selecciona una mesa válida desde el croquis.');
    }

    // Desglosar la mesa enviada ("id|capacidad")
    list($idMesaReal, $capacidadMesa) = explode('|', $mesaRaw);
    $idMesaReal = (int) $idMesaReal;
    $capacidadMesa = (int) $capacidadMesa;
    $clienteId = (int) $_SESSION['user_id'];
    
    // Concatenamos el método de pago en la observación para el registro del Barista
    $observacionFinal = "[$metodoPago] " . $observaciones;

    $pdo = getPDO();
    $pdo->beginTransaction();

    // 4. CONTROL DE SPAM: Un cliente no puede tener dos mesas activas a la vez
    $stmtLimit = $pdo->prepare('SELECT id_reserva FROM reservas WHERE cliente_id = ? AND estado = "Activa" LIMIT 1');
    $stmtLimit->execute([$clienteId]);
    if ($stmtLimit->fetch()) {
        throw new RuntimeException("Ya cuentas con una reserva activa. Solo se permite 1 mesa por cuenta.");
    }

    // 5. CONTROL DE CONCURRENCIA: Evitar que dos personas reserven la misma mesa al mismo segundo
    $checkStmt = $pdo->prepare('SELECT id_reserva FROM reservas WHERE mesa_id = ? AND fecha = ? AND hora = ? AND estado = "Activa" LIMIT 1');
    $checkStmt->execute([$idMesaReal, $fecha, $hora]);
    if ($checkStmt->fetch()) {
        throw new RuntimeException("La mesa seleccionada acaba de ser ocupada. Por favor, elige otra del mapa.");
    }

    // 6. INSERCIÓN DE LA RESERVA
    $stmt = $pdo->prepare('INSERT INTO reservas (fecha, hora, capacidad_mesa, cliente_id, mesa_id, estado, observaciones) VALUES (?, ?, ?, ?, ?, "Activa", ?)');
    $stmt->execute([$fecha, $hora, $capacidadMesa, $clienteId, $idMesaReal, $observacionFinal]);

    // 7. BLOQUEO DE LA MESA
    $updateMesa = $pdo->prepare('UPDATE mesas SET estado = "ocupada" WHERE id_mesa = ?');
    $updateMesa->execute([$idMesaReal]);

    $pdo->commit();
    
    // Mensaje Flash de Éxito
    $_SESSION['reserva_success'] = '¡Reserva confirmada! Hemos procesado tu pago de garantía y bloqueado tu mesa.';
    header('Location: reservas.php');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Mensaje Flash de Error
    $_SESSION['reserva_error'] = $e->getMessage();
    header('Location: reservas.php');
    exit;
}