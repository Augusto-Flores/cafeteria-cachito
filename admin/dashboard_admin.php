<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrador') {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = getPDO();

// 1. DATA ANALÍTICA: KPIs MODERNOS
// Ventas
$ventasHoy = (float)$pdo->query("SELECT SUM(total) FROM ventas WHERE DATE(fecha_creacion) = CURRENT_DATE")->fetchColumn();
$ventasSemana = (float)$pdo->query("SELECT SUM(total) FROM ventas WHERE YEARWEEK(fecha_creacion, 1) = YEARWEEK(CURRENT_DATE, 1)")->fetchColumn();
$ventasMes = (float)$pdo->query("SELECT SUM(total) FROM ventas WHERE MONTH(fecha_creacion) = MONTH(CURRENT_DATE) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE)")->fetchColumn();

// Productos
$prodTotal = (int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$prodActivos = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE disponible = 1")->fetchColumn();
$prodInactivos = $prodTotal - $prodActivos;

// Inventario y Solicitudes
$insumosTotal = (int)$pdo->query("SELECT COUNT(*) FROM inventario")->fetchColumn();
$insumosAlerta = (int)$pdo->query("SELECT COUNT(*) FROM inventario WHERE cantidad_actual <= stock_minimo")->fetchColumn();
$solicitudesPendientes = (int)$pdo->query("SELECT COUNT(*) FROM pedidos_insumos WHERE estado = 'Pendiente'")->fetchColumn();

// Mermas
$mermasMes = (float)$pdo->query("SELECT SUM(cantidad) FROM registro_mermas WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE)")->fetchColumn();

// 2. DATA PARA GRÁFICOS (JSON Export)
$chartVentasDias = array_reverse($pdo->query("SELECT DATE(fecha_creacion) as fecha, SUM(total) as total FROM ventas GROUP BY DATE(fecha_creacion) ORDER BY fecha DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC));
$chartCategorias = $pdo->query("SELECT categoria, COUNT(*) as qty FROM productos GROUP BY categoria")->fetchAll(PDO::FETCH_ASSOC);

$chartDataExport = json_encode([
    'ventasDias' => $chartVentasDias,
    'categorias' => $chartCategorias
]);

// 3. RECUPERACIÓN DE DATOS PARA TABLAS/CATÁLOGO
$productos = $pdo->query('SELECT * FROM productos ORDER BY categoria ASC, nombre ASC')->fetchAll();
$inventario = $pdo->query('SELECT * FROM inventario ORDER BY nombre ASC')->fetchAll();
$mermas = $pdo->query('SELECT m.*, i.nombre as insumo_nombre FROM registro_mermas m JOIN inventario i ON m.insumo_id = i.id_insumo ORDER BY m.fecha_registro DESC LIMIT 50')->fetchAll();
$pedidos = $pdo->query('SELECT p.*, i.nombre as insumo_nombre FROM pedidos_insumos p JOIN inventario i ON p.insumo_id = i.id_insumo ORDER BY p.id_pedido DESC')->fetchAll();

// Agrupación y conteo de categorías para el catálogo
$categoriasDisponibles = [];
foreach ($productos as $p) {
    if (!isset($categoriasDisponibles[$p['categoria']])) {
        $categoriasDisponibles[$p['categoria']] = 0;
    }
    $categoriasDisponibles[$p['categoria']]++;
}

$msgSuccess = $_SESSION['admin_success'] ?? '';
$msgError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Gerencia | Cafetería Cachito</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <script id="chartDataObj" type="application/json"><?php echo $chartDataExport; ?></script>

  <div class="admin-layout">
    
    <aside class="admin-sidebar">
        <div class="admin-brand">Cachito Admin</div>
        <nav class="admin-nav">
            <button class="nav-btn active" data-target="sec-dashboard">📊 Dashboard Analytics</button>
            <button class="nav-btn" data-target="sec-productos">🍔 Catálogo y Precios</button>
            <button class="nav-btn" data-target="sec-inventario">📦 Inventario (Almacén)</button>
            <button class="nav-btn" data-target="sec-mermas">🗑️ Control de Mermas</button>
            <button class="nav-btn" data-target="sec-pedidos">🚚 Órdenes a Proveedor</button>
            <a href="../auth/logout.php" class="nav-btn nav-logout mt-5">🚪 Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="admin-main-content">
        
        <?php if ($msgSuccess): ?><div class="alert alert-success fw-bold"><?php echo $msgSuccess; ?></div><?php endif; ?>
        <?php if ($msgError): ?><div class="alert alert-danger fw-bold"><?php echo $msgError; ?></div><?php endif; ?>

        <section id="sec-dashboard" class="admin-section active">
            <h2 class="fw-bold mb-4" style="color: var(--color-primary);">📊 Rendimiento General</h2>
            
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon blue">💰</div>
                    <div class="kpi-data"><h4>Ventas (Día)</h4><div class="val">S/. <?php echo number_format($ventasHoy, 2); ?></div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon green">📈</div>
                    <div class="kpi-data"><h4>Ventas (Mes)</h4><div class="val">S/. <?php echo number_format($ventasMes, 2); ?></div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon orange">🍔</div>
                    <div class="kpi-data"><h4>Productos Carta</h4><div class="val"><?php echo $prodActivos; ?> <span class="text-muted fs-6">/ <?php echo $prodTotal; ?></span></div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon red">⚠️</div>
                    <div class="kpi-data"><h4>Alertas Stock</h4><div class="val text-danger"><?php echo $insumosAlerta; ?> <span class="text-muted fs-6">Insumos</span></div></div>
                </div>
            </div>

            <div class="chart-grid">
                <div class="chart-container">
                    <div class="chart-title">Ingresos de los últimos 7 días</div>
                    <div class="chart-wrapper"><canvas id="chartVentasDias"></canvas></div>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Distribución del Catálogo</div>
                    <div class="chart-wrapper"><canvas id="chartCategorias"></canvas></div>
                </div>
            </div>
        </section>

        <section id="sec-productos" class="admin-section">
            <h2 class="fw-bold mb-4" style="color: var(--color-primary);">🍔 Gestión de Catálogo</h2>
            
            <div class="toolbar-productos">
                <div class="cat-filters">
                    <?php foreach($categoriasDisponibles as $cat => $count): ?>
                        <button class="cat-btn" data-cat="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?> <span class="cat-count"><?php echo $count; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <div class="d-flex gap-3">
                    <input type="text" id="searchProduct" class="form-control" placeholder="🔍 Buscar producto..." style="width: 250px;">
                    <button class="btn btn-success fw-bold btn-modal-producto">➕ Nuevo Producto</button>
                </div>
            </div>

            <div class="product-grid" id="productGridWrapper">
                <?php foreach ($productos as $p): 
                    $estadoClase = $p['disponible'] ? '' : 'inactive';
                    $badgeClase = $p['disponible'] ? 'badge-active' : 'badge-inactive';
                    $badgeTexto = $p['disponible'] ? 'Activo' : 'Inactivo';
                    $imgUrl = !empty($p['imagen_url']) ? $p['imagen_url'] : 'https://loremflickr.com/400/400/food';
                    $jsonData = htmlspecialchars(json_encode([
                        'id' => $p['id_producto'], 'nombre' => $p['nombre'], 'cat' => $p['categoria'],
                        'precio' => $p['precio'], 'desc' => $p['descripcion'], 'img' => $p['imagen_url']
                    ]));
                ?>
                    <div class="product-card product-item <?php echo $estadoClase; ?>" 
                         data-cat="<?php echo htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8'); ?>" 
                         data-name="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="product-status-badge <?php echo $badgeClase; ?>"><?php echo $badgeTexto; ?></div>
                        <img src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>" class="product-img" loading="lazy">
                        
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="product-price">S/. <?php echo number_format((float)$p['precio'], 2); ?></div>
                            
                            <div class="product-actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-modal-producto" 
                                    data-id="<?php echo $p['id_producto']; ?>" data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-cat="<?php echo htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8'); ?>" data-precio="<?php echo (float)$p['precio']; ?>"
                                    data-desc="<?php echo htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-img="<?php echo htmlspecialchars($p['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    ✏️ Editar
                                </button>
                                
                                <form action="toggle_producto.php" method="POST" class="m-0">
                                    <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                                    <input type="hidden" name="estado_actual" value="<?php echo $p['disponible']; ?>">
                                    <button type="submit" class="btn <?php echo $p['disponible'] ? 'btn-outline-danger' : 'btn-outline-success'; ?> btn-sm w-100">
                                        <?php echo $p['disponible'] ? 'Apagar' : 'Prender'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="sec-inventario" class="admin-section">
            <h2 class="fw-bold mb-4" style="color: var(--color-primary);">📦 Almacén Central</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>ID</th><th>Insumo</th><th>Unidad</th><th>Stock Actual</th><th>Mínimo</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($inventario as $inv): 
                            $esCritico = (float)$inv['cantidad_actual'] <= (float)$inv['stock_minimo'];
                            $badgeColor = $esCritico ? 'bg-danger text-white' : 'bg-success text-white';
                            $estadoText = $esCritico ? 'Crítico / Bajo' : 'Óptimo';
                        ?>
                            <tr>
                                <td><?php echo $inv['id_insumo']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($inv['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($inv['unidad_medida'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="fw-bold fs-5"><?php echo (float)$inv['cantidad_actual']; ?></td>
                                <td><?php echo (float)$inv['stock_minimo']; ?></td>
                                <td><span class="badge-stock <?php echo $badgeColor; ?>"><?php echo $estadoText; ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger fw-bold btn-modal-merma" data-id="<?php echo $inv['id_insumo']; ?>" data-nombre="<?php echo htmlspecialchars($inv['nombre'], ENT_QUOTES, 'UTF-8'); ?>">📉 Merma</button>
                                    <button class="btn btn-sm btn-outline-primary fw-bold btn-modal-solicitar" data-id="<?php echo $inv['id_insumo']; ?>" data-nombre="<?php echo htmlspecialchars($inv['nombre'], ENT_QUOTES, 'UTF-8'); ?>">🛒 Solicitar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="sec-mermas" class="admin-section">
            <h2 class="fw-bold mb-4" style="color: var(--color-primary);">🗑️ Registro Histórico de Mermas</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Insumo</th><th>Cant. Perdida</th><th>Motivo</th></tr></thead>
                    <tbody>
                        <?php foreach ($mermas as $m): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($m['fecha_registro'])); ?></td>
                                <td class="fw-bold text-danger"><?php echo htmlspecialchars($m['insumo_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (float)$m['cantidad']; ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($m['motivo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="sec-pedidos" class="admin-section">
            <h2 class="fw-bold mb-4" style="color: var(--color-primary);">🚚 Órdenes de Compra y Proveedores</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>ID</th><th>Insumo Requerido</th><th>Cant.</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($pedidos as $ped): ?>
                            <tr>
                                <td>#<?php echo $ped['id_pedido']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($ped['insumo_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (float)$ped['cantidad']; ?></td>
                                <td>
                                    <?php if ($ped['estado'] === 'Pendiente'): ?>
                                        <span class="badge-stock bg-warning text-dark">En Tránsito</span>
                                    <?php else: ?>
                                        <span class="badge-stock bg-success text-white">Recibido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ped['estado'] === 'Pendiente'): ?>
                                        <form action="recibir_insumo.php" method="POST" class="m-0">
                                            <input type="hidden" name="id_pedido" value="<?php echo $ped['id_pedido']; ?>">
                                            <input type="hidden" name="insumo_id" value="<?php echo $ped['insumo_id']; ?>">
                                            <input type="hidden" name="cantidad" value="<?php echo $ped['cantidad']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold">📦 Ingresar a Almacén</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($ped['fecha_recepcion'])); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
  </div>

  <div class="modal-overlay" id="productoModal">
    <div class="modal-box">
        <h3 class="modal-title" id="modalProdTitle">➕ Producto</h3>
        <form action="guardar_producto.php" method="POST">
            <input type="hidden" name="id_producto" id="prod_id" value="0">
            <div class="mb-3"><label class="form-label fw-bold">Nombre del Producto</label><input type="text" name="nombre" id="prod_nombre" class="form-control" required></div>
            <div class="row mb-3">
                <div class="col-6"><label class="form-label fw-bold">Categoría</label>
                    <select name="categoria" id="prod_cat" class="form-control" required>
                        <?php foreach(array_keys($categoriasDisponibles) as $c): ?>
                            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6"><label class="form-label fw-bold">Precio (S/.)</label><input type="number" step="0.10" min="0.10" name="precio" id="prod_precio" class="form-control" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Descripción Breve</label><textarea name="descripcion" id="prod_desc" class="form-control" rows="2"></textarea></div>
            <div class="mb-3"><label class="form-label fw-bold">URL de la Imagen</label><input type="text" name="imagen_url" id="prod_img" class="form-control"></div>
            <div class="d-flex gap-3 mt-4">
                <button type="button" class="btn btn-outline-secondary w-50 btn-close-modal">Cancelar</button>
                <button type="submit" class="btn btn-success w-50 fw-bold">💾 Guardar</button>
            </div>
        </form>
    </div>
  </div>

  <div class="modal-overlay" id="mermaModal">
    <div class="modal-box">
        <h3 class="modal-title text-danger">📉 Descontar Merma</h3>
        <form action="registrar_merma.php" method="POST">
            <input type="hidden" name="insumo_id" id="merma_insumo_id">
            <div class="mb-3"><label class="form-label fw-bold">Insumo Afectado</label><input type="text" id="merma_insumo_nombre" class="form-control bg-light" readonly></div>
            <div class="mb-3"><label class="form-label fw-bold text-danger">Cantidad a dar de baja</label><input type="number" step="0.001" min="0.001" name="cantidad" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Motivo (Caducidad, Accidente, etc.)</label><input type="text" name="motivo" class="form-control" required></div>
            <div class="d-flex gap-3 mt-4">
                <button type="button" class="btn btn-outline-secondary w-50 btn-close-modal">Cancelar</button>
                <button type="submit" class="btn btn-danger w-50 fw-bold">🗑️ Descontar Stock</button>
            </div>
        </form>
    </div>
  </div>

  <div class="modal-overlay" id="solicitarModal">
    <div class="modal-box">
        <h3 class="modal-title text-primary">🛒 Orden de Compra</h3>
        <form action="solicitar_insumo.php" method="POST">
            <input type="hidden" name="insumo_id" id="sol_insumo_id">
            <div class="mb-3"><label class="form-label fw-bold">Insumo Requerido</label><input type="text" id="sol_insumo_nombre" class="form-control bg-light" readonly></div>
            <div class="mb-3"><label class="form-label fw-bold text-primary">Cantidad a Solicitar al Proveedor</label><input type="number" step="0.01" min="0.1" name="cantidad" class="form-control" required></div>
            <div class="d-flex gap-3 mt-4">
                <button type="button" class="btn btn-outline-secondary w-50 btn-close-modal">Cancelar</button>
                <button type="submit" class="btn btn-primary w-50 fw-bold">Enviar Solicitud</button>
            </div>
        </form>
    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>