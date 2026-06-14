<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cliente') {
    header('Location: ../auth/login.php');
    exit;
}

try {
    $pdo = getPDO();
    // Jalamos las mesas de la versión v2.0 desde phpMyAdmin
    $stmt = $pdo->query('SELECT id_mesa, numero_mesa, capacidad, estado FROM mesas WHERE estado = "disponible" ORDER BY numero_mesa ASC');
    $mesasBD = $stmt->fetchAll();
} catch (PDOException $e) {
    $mesasBD = [];
}

date_default_timezone_set('America/Lima');
$today = date('Y-m-d');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>📅 Reservar Mesa - Cafetería Cachito</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>

  <header class="site-header">
    <div class="header-content">
      <h1>📅 Reservación de Mesas</h1>
      <nav class="user-info">
        <a href="catalogo.php" class="btn btn-outline" style="border-color:white; color:white; padding:0.4rem 1rem; font-size:0.85rem;">🛵 Pedir Delivery</a>
        <a href="../auth/logout.php" class="btn btn-outline" style="border-color:#ffcccc; color:#ffcccc; padding:0.4rem 1rem; font-size:0.85rem;">🚪 Salir</a>
      </nav>
    </div>
  </header>

  <div class="main-container">
    <div class="client-layout">
        
        <div class="catalog-wrapper">
            <h3 class="h4 fw-bold" style="color:var(--color-dark);">🪟 Croquis Interno de Salón (v2.0)</h3>
            <p class="text-muted small">Selecciona una mesa disponible del mapa. Se aplica una garantía reembolsable de S/. 5.00 por cada asiento.</p>
            
            <div style="display:flex; gap:0.5rem; margin:1.25rem 0;">
                <button type="button" class="btn btn-outline" onclick="filtrarCapacidadMesas(0)" style="padding:0.3rem 0.7rem; font-size:0.8rem;">Ver Todas (20)</button>
                <button type="button" class="btn btn-outline" onclick="filtrarCapacidadMesas(2)" style="padding:0.3rem 0.7rem; font-size:0.8rem;">Dúos (2 Asientos)</button>
                <button type="button" class="btn btn-outline" onclick="filtrarCapacidadMesas(4)" style="padding:0.3rem 0.7rem; font-size:0.8rem;">Medianas (4 Asientos)</button>
                <button type="button" class="btn btn-outline" onclick="filtrarCapacidadMesas(6)" style="padding:0.3rem 0.7rem; font-size:0.8rem;">Familiares (6 Asientos)</button>
            </div>

            <div class="mesa-grid-selection">
                <?php foreach ($mesasBD as $m): 
                    $valueRaw = $m['id_mesa'] . '|' . $m['capacidad'];
                    $icon = ($m['capacidad'] <= 2) ? '🪑' : (($m['capacidad'] <= 4) ? '☕' : ' Couch 🛋️');
                ?>
                    <div class="mesa-item-wrapper" data-capacidad="<?php echo $m['capacidad']; ?>">
                        <div class="mesa-box-card" onclick="seleccionarMesaCard(this, '<?php echo $valueRaw; ?>')">
                            <span class="m-icon"><?php echo $icon; ?></span>
                            <div class="m-label">Mesa N° <?php echo $m['numero_mesa']; ?></div>
                            <div class="m-cap">(Capacidad: <?php echo $m['capacidad']; ?>)</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cart-sidebar">
            <h3 class="h4 fw-bold" style="color:var(--color-dark); margin-bottom:1rem;">📝 Agenda tu Visita</h3>
            
            <form action="procesar_reserva.php" method="POST" id="reservaForm">
                <div class="checkout-form-group">
                    <label>📅 Elegir Fecha:</label>
                    <input type="date" name="fecha" class="form-control" min="<?php echo $today; ?>" value="<?php echo $today; ?>" required>
                </div>
                <div class="checkout-form-group">
                    <label>⏰ Definir Hora de Llegada:</label>
                    <input type="time" name="hora" class="form-control" min="08:00" max="21:00" required>
                    <p style="font-size:0.75rem; color:var(--color-primary); margin-top:0.25rem;">
                        * Horario de atención: 08:00 AM a 09:00 PM.
                    </p>
                </div>
                <div class="checkout-form-group">
                    <label>🪑 Código de Mesa Seleccionada:</label>
                    <input type="text" name="mesa" id="txt-mesa-select" class="form-control" readonly placeholder="Elige del mapa de la izquierda" required style="background:#faf8f5; font-weight:700; color:var(--color-primary);">
                </div>
                <div class="checkout-form-group">
                    <label>💬 Requerimientos / Observaciones:</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Ej: Cerca a la ventana / Festejo de cumpleaños..."></textarea>
                </div>

                <div id="box-pago-reserva" style="display:none; background:#f0f7ff; border:1px solid #bddeff; padding:1rem; border-radius:0.5rem; margin-top:1rem;">
                    <div style="font-weight:700; color:#1a535c; margin-bottom:0.25rem;">📱 Abono de Reserva Requerido:</div>
                    <div style="display:flex; justify-content:between; font-size:1.15rem; font-weight:800; color:#6f4e37; margin-bottom:0.4rem;">
                        <span>Monto de la Garantía:</span>
                        <span id="lbl-costo-reserva">S/. 0.00</span>
                    </div>
                    <p style="font-size:0.75rem; color:#555; margin:0;">Envía el Yape al número de la Cafetería <strong>987 654 321</strong> para blindar el salón.</p>
                </div>

                <button type="submit" class="btn btn-primary" id="btn-guardar-reserva" style="width:100%; padding:0.85rem; margin-top:1.25rem;">💾 Agendar Reserva</button>
            </form>
        </div>

    </div>
  </div>

  <script src="../assets/js/cliente.js"></script>
</body>
</html>