<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrador') {
    header('Location: ../auth/login.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id_insumo, nombre, cantidad_actual, unidad_medida, stock_minimo, fecha_actualizacion FROM inventario ORDER BY nombre ASC');
    $insumos = $stmt->fetchAll();
} catch (PDOException $e) {
    $insumos = [];
}

$msg = '';
if (isset($_GET['success'])) {
    $msg = 'Merma registrada correctamente.';
} elseif (isset($_GET['error'])) {
    $msg = 'Ocurrió un error al registrar la merma.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin - Inventario</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <!-- ENCABEZADO -->
  <header class="site-header">
    <div class="header-content">
      <div>
        <h1>☕ Cafetería Web - Panel Administrativo</h1>
        <p>📦 Gestión de inventario y mermas</p>
      </div>
      <a href="../auth/logout.php" class="btn btn-outline">🚪 Cerrar sesión</a>
    </div>
  </header>

  <!-- CONTENIDO PRINCIPAL -->
  <div class="main-container">
    <div class="mb-3">
      <h2 style="color: var(--color-primary); font-size: 1.5rem;">📊 Inventario</h2>
      <p style="color: var(--color-secondary);">Monitorea el stock y registra mermas</p>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-info">
        ℹ️ <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h3>📦 Tabla de insumos</h3>
      </div>
      <div class="card-body" style="padding: 0; overflow-x: auto;">
        <table class="table">
          <thead>
            <tr>
              <th>🏷️ Insumo</th>
              <th>📊 Cantidad actual</th>
              <th>⚖️ Unidad</th>
              <th>⚠️ Stock mínimo</th>
              <th>🕐 Última actualización</th>
              <th>⚙️ Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($insumos)): ?>
              <tr><td colspan="6" class="text-center">No hay insumos registrados.</td></tr>
            <?php else: ?>
              <?php foreach ($insumos as $i): 
                $low = is_numeric($i['cantidad_actual']) && (float)$i['cantidad_actual'] < 5.0;
              ?>
                <tr <?php echo $low ? 'style="background: rgba(214, 48, 49, 0.08);"' : ''; ?>>
                  <td><strong><?php echo htmlspecialchars($i['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                  <td>
                    <?php echo htmlspecialchars($i['cantidad_actual'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($low): ?>
                      <span class="badge badge-danger">⚠️ Bajo</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($i['unidad_medida'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($i['stock_minimo'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td style="font-size: 0.9rem;"><?php echo htmlspecialchars($i['fecha_actualizacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <button
                      class="btn btn-primary btn-sm"
                      onclick="abrirMermaModal(<?php echo (int)$i['id_insumo']; ?>, '<?php echo htmlspecialchars($i['nombre'], ENT_QUOTES, 'UTF-8'); ?>')"
                    >📋 Registrar merma</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL MERMA -->
  <div class="modal" id="mermaModal">
    <div class="modal-content">
      <form id="mermaForm" method="post" action="registrar_merma.php">
        <h2 style="color: var(--color-primary); margin-bottom: 1.5rem;">📋 Registrar Merma</h2>
        
        <input type="hidden" name="insumo_id" id="merma_insumo_id" value="">

        <div class="form-group">
          <label for="merma_insumo_nombre">🏷️ Insumo</label>
          <input type="text" id="merma_insumo_nombre" class="form-control" readonly style="background: var(--color-light);">
        </div>

        <div class="form-group">
          <label for="cantidad">⚖️ Cantidad (ej: 0.5)</label>
          <input type="number" step="0.01" min="0.01" name="cantidad" id="cantidad" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="motivo">💬 Motivo</label>
          <input type="text" name="motivo" id="motivo" class="form-control" required placeholder="Ej: Vencimiento, rotura, etc.">
        </div>

        <div class="form-group">
          <label for="fecha_registro">📅 Fecha</label>
          <input type="date" name="fecha_registro" id="fecha_registro" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <button type="button" class="btn btn-outline" onclick="cerrarMermaModal()">❌ Cancelar</button>
          <button type="submit" class="btn btn-primary">✅ Registrar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- PIE DE PÁGINA -->
  <footer class="site-footer">
    <p>☕ Cafetería Web © 2026 - Panel de Administración</p>
    <p style="margin-top: 0.5rem; font-size: 0.85rem;">Gestión eficiente de inventario y mermas</p>
  </footer>

  <script>
    function abrirMermaModal(insumoId, insumoNombre) {
      document.getElementById('merma_insumo_id').value = insumoId;
      document.getElementById('merma_insumo_nombre').value = insumoNombre;
      document.getElementById('mermaModal').classList.add('active');
    }

    function cerrarMermaModal() {
      document.getElementById('mermaModal').classList.remove('active');
    }

    window.addEventListener('click', function(event) {
      const modal = document.getElementById('mermaModal');
      if (event.target === modal) {
        cerrarMermaModal();
      }
    });

    document.getElementById('mermaForm').addEventListener('submit', function(event) {
      const btn = this.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = '⏳ Registrando...';
    });
  </script>
</body>
</html>
