<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrador') { header('Location: ../auth/login.php'); exit; }

$pdo = getPDO();
$msgSuccess = $_SESSION['admin_success'] ?? '';
$msgError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// 1. DATA KPIs
$ventasHoy = (float)$pdo->query("SELECT SUM(total) FROM ventas WHERE DATE(fecha_creacion) = CURRENT_DATE")->fetchColumn();
$ventasMes = (float)$pdo->query("SELECT SUM(total) FROM ventas WHERE MONTH(fecha_creacion) = MONTH(CURRENT_DATE) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE)")->fetchColumn();
$insumosAlerta = (int)$pdo->query("SELECT COUNT(*) FROM inventario WHERE cantidad_actual <= stock_minimo")->fetchColumn();
$productosActivos = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE disponible = 1")->fetchColumn();

// 2. DATA GRÁFICO VENTAS
$ventasHistorico = array_reverse($pdo->query("SELECT DATE(fecha_creacion) as fecha, SUM(total) as total_dia FROM ventas GROUP BY DATE(fecha_creacion) ORDER BY fecha DESC LIMIT 7")->fetchAll());
$labelsVentas = []; $dataVentas = [];
foreach ($ventasHistorico as $row) { $labelsVentas[] = date('d/m', strtotime($row['fecha'])); $dataVentas[] = (float)$row['total_dia']; }

// 3. DATA GRÁFICO CATEGORÍAS
$catHistorico = $pdo->query("SELECT categoria, COUNT(*) as cantidad FROM productos WHERE disponible = 1 GROUP BY categoria")->fetchAll();
$labelsCat = []; $dataCat = [];
foreach ($catHistorico as $row) { $labelsCat[] = $row['categoria']; $dataCat[] = (int)$row['cantidad']; }

// 4. DATA TABLAS
$insumos = $pdo->query('SELECT * FROM inventario ORDER BY nombre ASC')->fetchAll();
$productosCRUD = $pdo->query('SELECT * FROM productos ORDER BY categoria ASC, nombre ASC')->fetchAll();
$pedidosPendientes = $pdo->query("SELECT p.id_pedido, p.cantidad, p.fecha_solicitud, i.id_insumo, i.nombre, i.unidad_medida FROM pedidos_insumos p JOIN inventario i ON p.insumo_id = i.id_insumo WHERE p.estado = 'Pendiente' ORDER BY p.fecha_solicitud ASC")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel de Gerencia - Cachito</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
      const chartDataVentas = { labels: <?php echo json_encode($labelsVentas); ?>, datasets: [{ label: 'Ingresos (S/.)', data: <?php echo json_encode($dataVentas); ?>, borderColor: '#c4a77d', backgroundColor: 'rgba(196, 167, 125, 0.2)', borderWidth: 3, fill: true, tension: 0.3 }] };
      const chartDataCategorias = { labels: <?php echo json_encode($labelsCat); ?>, datasets: [{ data: <?php echo json_encode($dataCat); ?>, backgroundColor: ['#6f4e37', '#a0937d', '#c4a77d', '#e6dfd3', '#3d2817'] }] };
  </script>
</head>
<body class="admin-layout">

  <aside class="admin-sidebar">
      <div class="admin-brand">☕ CACHITO PRO</div>
      <nav class="admin-nav">
          <button class="nav-btn active" onclick="switchTab('sec-dashboard')">📊 Inteligencia de Negocio</button>
          <button class="nav-btn" onclick="switchTab('sec-inventario')">📦 Inventario y Proveedores</button>
          <button class="nav-btn" onclick="switchTab('sec-productos')">🍔 Catálogo (CRUD)</button>
      </nav>
      <div style="margin-top: auto;"><a href="../auth/logout.php" class="btn btn-outline" style="width:100%; border-color:#d63031; color:#fce8e6; text-align:center;">🚪 Cerrar Sesión</a></div>
  </aside>

  <main class="admin-content">
      <div class="admin-header">
          <div><h2 style="color:#3d2817; font-size:1.8rem;">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h2><p style="color:#888;">Panel de control avanzado ERP.</p></div>
      </div>

      <?php if ($msgSuccess): ?><div class="alert alert-success"><?php echo $msgSuccess; ?></div><?php endif; ?>
      <?php if ($msgError): ?><div class="alert alert-danger"><?php echo $msgError; ?></div><?php endif; ?>

      <section id="sec-dashboard" class="admin-section active">
          <div class="kpi-grid">
              <div class="kpi-card"><div class="kpi-icon">💰</div><div class="kpi-data"><h4>Ingresos de Hoy</h4><div class="val">S/. <?php echo number_format($ventasHoy, 2); ?></div></div></div>
              <div class="kpi-card"><div class="kpi-icon">📈</div><div class="kpi-data"><h4>Ventas del Mes</h4><div class="val">S/. <?php echo number_format($ventasMes, 2); ?></div></div></div>
              <div class="kpi-card"><div class="kpi-icon">⚠️</div><div class="kpi-data"><h4>Insumos Críticos</h4><div class="val" style="color:<?php echo $insumosAlerta > 0 ? '#d63031' : '#137333'; ?>;"><?php echo $insumosAlerta; ?></div></div></div>
              <div class="kpi-card"><div class="kpi-icon">🍔</div><div class="kpi-data"><h4>Menú Activo</h4><div class="val"><?php echo $productosActivos; ?> Platos</div></div></div>
          </div>
          <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
              <div><h3 style="margin-bottom:1rem; color:#3d2817;">Curva de Ingresos (7 días)</h3><div class="chart-container"><canvas id="ventasChart"></canvas></div></div>
              <div><h3 style="margin-bottom:1rem; color:#3d2817;">Distribución de Carta</h3><div class="chart-container"><canvas id="categoriasChart"></canvas></div></div>
          </div>
      </section>

      <section id="sec-inventario" class="admin-section">
          <h3 style="margin-bottom:1rem; color:#3d2817; border-bottom: 2px solid #ebdccb; padding-bottom:0.5rem;">🚚 Órdenes de Compra en Tránsito (Proveedores)</h3>
          <div class="table-responsive" style="margin-bottom: 2rem;">
              <table class="table">
                  <thead><tr><th>Insumo Esperado</th><th>Cantidad Solicitada</th><th>Fecha de Solicitud</th><th>Acción</th></tr></thead>
                  <tbody>
                      <?php if(empty($pedidosPendientes)): ?><tr><td colspan="4" style="text-align:center; color:#888;">No hay pedidos pendientes de entrega.</td></tr><?php endif; ?>
                      <?php foreach ($pedidosPendientes as $ped): ?>
                      <tr style="background: #fffcf2;">
                          <td><strong><?php echo htmlspecialchars($ped['nombre']); ?></strong></td>
                          <td><span style="color:#f7b731; font-weight:bold; font-size:1.1rem;">⏳ <?php echo $ped['cantidad'] . ' ' . $ped['unidad_medida']; ?></span></td>
                          <td><?php echo date('d/m/Y H:i', strtotime($ped['fecha_solicitud'])); ?></td>
                          <td>
                              <form action="recibir_insumo.php" method="POST" style="margin:0;">
                                  <input type="hidden" name="id_pedido" value="<?php echo $ped['id_pedido']; ?>">
                                  <input type="hidden" name="insumo_id" value="<?php echo $ped['id_insumo']; ?>">
                                  <input type="hidden" name="cantidad" value="<?php echo $ped['cantidad']; ?>">
                                  <button type="submit" class="btn btn-primary" style="background:#137333; padding:0.4rem 1rem; font-size:0.8rem;">📦 ¡Ya Llegó! (Sumar Stock)</button>
                              </form>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>

          <h3 style="margin-bottom:1rem; color:#3d2817; border-bottom: 2px solid #ebdccb; padding-bottom:0.5rem;">📦 Control de Almacén Actual</h3>
          <div class="table-responsive">
              <table class="table">
                  <thead><tr><th>Insumo</th><th>Stock Actual</th><th>Unidad</th><th>Operaciones</th></tr></thead>
                  <tbody>
                      <?php foreach ($insumos as $i): $low = (float)$i['cantidad_actual'] <= (float)$i['stock_minimo']; ?>
                      <tr style="<?php echo $low ? 'background: rgba(214, 48, 49, 0.05);' : ''; ?>">
                          <td><strong><?php echo htmlspecialchars($i['nombre']); ?></strong></td>
                          <td>
                              <span style="font-weight:bold; color:<?php echo $low ? '#d63031' : '#3d2817'; ?>;"><?php echo $i['cantidad_actual']; ?></span>
                              <?php if ($low) echo ' <span style="font-size:0.7rem; background:#d63031; color:white; padding:2px 5px; border-radius:3px;">¡Crítico!</span>'; ?>
                          </td>
                          <td><?php echo $i['unidad_medida']; ?></td>
                          <td style="display:flex; gap:0.5rem;">
                              <button class="btn btn-primary" style="background:#4a7ba7; padding:0.4rem 1rem; font-size:0.8rem;" onclick="abrirSolicitarModal(<?php echo $i['id_insumo']; ?>, '<?php echo htmlspecialchars($i['nombre']); ?>')">🛒 Solicitar</button>
                              <button class="btn btn-outline" style="border-color:#d63031; color:#d63031; padding:0.4rem 1rem; font-size:0.8rem;" onclick="abrirMermaModal(<?php echo $i['id_insumo']; ?>, '<?php echo htmlspecialchars($i['nombre']); ?>')">📉 Merma</button>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </section>

      <section id="sec-productos" class="admin-section">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom: 2px solid #ebdccb; padding-bottom:0.5rem;">
            <h3 style="color:#3d2817; margin:0;">Catálogo (Menú Web y POS)</h3>
            <button class="btn btn-primary" onclick="abrirProductoModal()">➕ Agregar Nuevo Producto</button>
          </div>
          
          <div class="table-responsive">
              <table class="table">
                  <thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acciones CRUD</th></tr></thead>
                  <tbody>
                      <?php foreach ($productosCRUD as $p): $activo = (int)$p['disponible'] === 1; ?>
                      <tr style="<?php echo !$activo ? 'opacity: 0.6; background: #f5f5f5;' : ''; ?>">
                          <td>
                              <img src="<?php echo htmlspecialchars($p['imagen_url']); ?>" alt="img" style="width:40px; height:40px; object-fit:cover; border-radius:4px; vertical-align:middle; margin-right:10px;">
                              <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                          </td>
                          <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                          <td style="font-weight:bold; color:#6f4e37;">S/. <?php echo number_format((float)$p['precio'], 2); ?></td>
                          <td><?php echo $activo ? '<span style="color:#137333; font-weight:bold;">🟢 Activo</span>' : '<span style="color:#d63031; font-weight:bold;">🔴 Oculto</span>'; ?></td>
                          <td style="display:flex; gap:0.5rem; align-items:center;">
                              <button class="btn btn-outline" style="padding:0.4rem 1rem; font-size:0.8rem;" 
                                      onclick="abrirProductoModal(<?php echo $p['id_producto']; ?>, '<?php echo addslashes($p['nombre']); ?>', '<?php echo addslashes($p['categoria']); ?>', '<?php echo $p['precio']; ?>', '<?php echo addslashes($p['descripcion'] ?? ''); ?>', '<?php echo addslashes($p['imagen_url'] ?? ''); ?>')">✏️ Editar</button>
                              
                              <form action="toggle_producto.php" method="POST" style="margin:0;">
                                  <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                                  <input type="hidden" name="estado_actual" value="<?php echo $p['disponible']; ?>">
                                  <button type="submit" class="btn btn-outline" style="border-color:<?php echo $activo ? '#d63031' : '#137333'; ?>; color:<?php echo $activo ? '#d63031' : '#137333'; ?>; padding:0.4rem 1rem; font-size:0.8rem;"><?php echo $activo ? '👁️ Ocultar' : '👁️ Mostrar'; ?></button>
                              </form>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </section>

  </main>

  <div class="modal" id="mermaModal">
    <div class="modal-content">
      <form id="mermaForm" method="post" action="registrar_merma.php">
        <h2 style="color: #d63031; margin-bottom: 1.5rem;">📉 Registrar Merma</h2>
        <input type="hidden" name="insumo_id" id="merma_insumo_id">
        <div class="form-group"><label>Insumo Afectado</label><input type="text" id="merma_insumo_nombre" class="form-control" readonly></div>
        <div class="form-group"><label>Cantidad Perdida</label><input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required></div>
        <div class="form-group"><label>Motivo de Baja</label><input type="text" name="motivo" class="form-control" required></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" class="btn btn-outline" onclick="cerrarModales()">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="background:#d63031;">Guardar Merma</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal" id="solicitarModal">
    <div class="modal-content">
      <form method="post" action="solicitar_insumo.php">
        <h2 style="color: #4a7ba7; margin-bottom: 1.5rem;">🛒 Orden a Proveedor</h2>
        <input type="hidden" name="insumo_id" id="sol_insumo_id">
        <div class="form-group"><label>Insumo a Solicitar</label><input type="text" id="sol_insumo_nombre" class="form-control" readonly></div>
        <div class="form-group"><label>Cantidad Requerida</label><input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required></div>
        <p style="font-size:0.8rem; color:#888;">El pedido quedará en estado "Pendiente" hasta que confirmes su llegada física al local.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" class="btn btn-outline" onclick="cerrarModales()">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="background:#4a7ba7;">Enviar Solicitud</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal" id="productoModal">
    <div class="modal-content" style="max-width:600px;">
      <form method="post" action="guardar_producto.php">
        <h2 id="modalProdTitle" style="color: #6f4e37; margin-bottom: 1.5rem;">Gestión de Producto</h2>
        <input type="hidden" name="id_producto" id="prod_id" value="0">
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group"><label>Nombre del Plato/Bebida</label><input type="text" name="nombre" id="prod_nombre" class="form-control" required></div>
            <div class="form-group">
                <label>Categoría</label>
                <select name="categoria" id="prod_cat" class="form-control" required>
                    <option value="Bebidas Calientes">Bebidas Calientes</option>
                    <option value="Bebidas Frías">Bebidas Frías</option>
                    <option value="Postres">Postres</option>
                    <option value="Panadería">Panadería</option>
                    <option value="Combos">Combos (Nuevo)</option>
                </select>
            </div>
        </div>
        
        <div class="form-group"><label>Precio de Venta (S/.)</label><input type="number" step="0.10" min="0.10" name="precio" id="prod_precio" class="form-control" required></div>
        <div class="form-group"><label>Descripción Breve</label><textarea name="descripcion" id="prod_desc" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label>Ruta de Imagen (URL o ../assets/img/...)</label><input type="text" name="imagen_url" id="prod_img" class="form-control"></div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" class="btn btn-outline" onclick="cerrarModales()">Cancelar</button>
          <button type="submit" class="btn btn-primary">💾 Guardar Producto</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>