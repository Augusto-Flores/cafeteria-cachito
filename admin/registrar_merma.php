<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

// Si se recibe POST, procesar la inserción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $insumo_id = isset($_POST['insumo_id']) ? (int) $_POST['insumo_id'] : 0;
    $cantidad = isset($_POST['cantidad']) ? (float) $_POST['cantidad'] : 0.0;
    $motivo = isset($_POST['motivo']) ? sanitize_input((string) $_POST['motivo']) : '';
    $fecha_registro = isset($_POST['fecha_registro']) ? sanitize_input((string) $_POST['fecha_registro']) : date('Y-m-d');

    if ($insumo_id <= 0 || $cantidad <= 0) {
        header('Location: dashboard_admin.php?error=1');
        exit;
    }

    $pdo = null;
    try {
        $pdo = getPDO();
        $pdo->beginTransaction();

        $insert = $pdo->prepare('INSERT INTO registro_mermas (insumo_id, cantidad, motivo, fecha_registro, fecha_creacion) VALUES (:insumo_id, :cantidad, :motivo, :fecha_registro, NOW())');
        $insert->execute([
            ':insumo_id' => $insumo_id,
            ':cantidad' => $cantidad,
            ':motivo' => $motivo,
            ':fecha_registro' => $fecha_registro,
        ]);

        $update = $pdo->prepare('UPDATE inventario SET cantidad_actual = cantidad_actual - :cantidad, fecha_actualizacion = NOW() WHERE id_insumo = :insumo_id');
        $update->execute([
            ':cantidad' => $cantidad,
            ':insumo_id' => $insumo_id,
        ]);

        $pdo->commit();
        header('Location: dashboard_admin.php?success=1');
        exit;
    } catch (PDOException $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: dashboard_admin.php?error=1');
        exit;
    }
}

// Si no es POST, renderizar el modal (para incluir en dashboard_admin.php)
?>

<!-- Modal Registrar Merma -->
<div class="modal fade" id="mermaModal" tabindex="-1" aria-labelledby="mermaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="mermaForm" method="post" action="registrar_merma.php">
        <div class="modal-header">
          <h5 class="modal-title" id="mermaModalLabel">Registrar merma</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="insumo_id" id="merma_insumo_id" value="">

          <div class="mb-3">
            <label for="merma_insumo_nombre" class="form-label">Insumo</label>
            <input type="text" id="merma_insumo_nombre" class="form-control" readonly>
          </div>

          <div class="mb-3">
            <label for="cantidad" class="form-label">Cantidad (ej: 0.5)</label>
            <input type="number" step="0.01" min="0.01" name="cantidad" id="cantidad" class="form-control" required aria-label="Cantidad de merma">
          </div>

          <div class="mb-3">
            <label for="motivo" class="form-label">Motivo</label>
            <input type="text" name="motivo" id="motivo" class="form-control" required aria-label="Motivo de la merma">
          </div>

          <div class="mb-3">
            <label for="fecha_registro" class="form-label">Fecha</label>
            <input type="date" name="fecha_registro" id="fecha_registro" class="form-control" value="<?php echo date('Y-m-d'); ?>" required aria-label="Fecha de la merma">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="mermaSubmit" class="btn btn-primary">Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Evitar envíos duplicados: deshabilitar el botón submit en el primer clic
  document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('mermaForm');
    if (!form) return;
    var submit = document.getElementById('mermaSubmit');
    form.addEventListener('submit', function(e){
      if (submit) {
        submit.disabled = true;
        submit.innerText = 'Registrando...';
      }
    });
  });
</script>
