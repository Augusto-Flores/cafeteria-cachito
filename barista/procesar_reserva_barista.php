<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barista') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idReserva = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;
    $idMesa = isset($_POST['id_mesa']) ? (int)$_POST['id_mesa'] : 0;
    $accion = $_POST['accion'] ?? '';

    if ($idReserva > 0 && $idMesa > 0) {
        try {
            $pdo = getPDO();
            $pdo->beginTransaction();

            if ($accion === 'llegada') {
                $stmt = $pdo->prepare("UPDATE reservas SET estado = 'En Curso' WHERE id_reserva = ?");
                $stmt->execute([$idReserva]);
                $_SESSION['pos_success'] = '🛎️ El cliente ha llegado. Reserva en curso.';
                
            } elseif ($accion === 'cancelar') {
                $stmt = $pdo->prepare("UPDATE reservas SET estado = 'Cancelada' WHERE id_reserva = ?");
                $stmt->execute([$idReserva]);
                
                $stmtMesa = $pdo->prepare("UPDATE mesas SET estado = 'disponible' WHERE id_mesa = ?");
                $stmtMesa->execute([$idMesa]);
                $_SESSION['pos_success'] = '❌ Reserva cancelada (No Show). La mesa ha sido liberada exitosamente.';
                
            } elseif ($accion === 'finalizar') {
                $stmt = $pdo->prepare("UPDATE reservas SET estado = 'Cumplida' WHERE id_reserva = ?");
                $stmt->execute([$idReserva]);
                
                $stmtMesa = $pdo->prepare("UPDATE mesas SET estado = 'disponible' WHERE id_mesa = ?");
                $stmtMesa->execute([$idMesa]);
                $_SESSION['pos_success'] = '✅ Reserva finalizada. La mesa ha sido liberada exitosamente.';
                
            } else {
                throw new Exception("Acción de sistema no válida.");
            }

            $pdo->commit();
            header('Location: pos.php');
            exit;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['pos_error'] = '❌ Error de Transacción: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: pos.php');
            exit;
        }
    } else {
        $_SESSION['pos_error'] = '❌ Error: Faltan datos de la mesa para procesar la acción.';
        header('Location: pos.php');
        exit;
    }
}

header('Location: pos.php');
exit;