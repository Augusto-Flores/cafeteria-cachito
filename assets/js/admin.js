function switchTab(tabId) {
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
    document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

// MODAL DE MERMAS
function abrirMermaModal(id, nombre) {
    document.getElementById('merma_insumo_id').value = id;
    document.getElementById('merma_insumo_nombre').value = nombre;
    document.getElementById('mermaModal').classList.add('active');
}

// MODAL DE SOLICITAR INSUMOS AL PROVEEDOR
function abrirSolicitarModal(id, nombre) {
    document.getElementById('sol_insumo_id').value = id;
    document.getElementById('sol_insumo_nombre').value = nombre;
    document.getElementById('solicitarModal').classList.add('active');
}

// MODAL DE PRODUCTOS (Crear / Editar)
function abrirProductoModal(id = 0, nombre = '', cat = '', precio = '', desc = '', img = '') {
    document.getElementById('prod_id').value = id;
    document.getElementById('prod_nombre').value = nombre;
    document.getElementById('prod_cat').value = cat;
    document.getElementById('prod_precio').value = precio;
    document.getElementById('prod_desc').value = desc;
    document.getElementById('prod_img').value = img;
    
    document.getElementById('modalProdTitle').innerText = id === 0 ? '➕ Nuevo Producto' : '✏️ Editar Producto';
    document.getElementById('productoModal').classList.add('active');
}

// CERRAR TODOS LOS MODALES
function cerrarModales() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
}

document.addEventListener('DOMContentLoaded', () => {
    // GRÁFICO 1: VENTAS HISTÓRICAS (Líneas)
    const ctxVentas = document.getElementById('ventasChart');
    if (ctxVentas) {
        new Chart(ctxVentas, {
            type: 'line',
            data: chartDataVentas,
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f5f1e8' } }, x: { grid: { display: false } } } }
        });
    }

    // GRÁFICO 2: DISTRIBUCIÓN DE PRODUCTOS (Torta/Doughnut)
    const ctxCat = document.getElementById('categoriasChart');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: chartDataCategorias,
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
        });
    }
});