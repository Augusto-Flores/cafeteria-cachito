/* =========================================================
   LÓGICA INTERACTIVA: MÓDULO ADMINISTRADOR (CLEAN CODE)
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    initNavegacion();
    initCatalogoProductos();
    initModales();
    initGraficosDashboard();
    autoOcultarAlertas();
});

// ==========================================
// 1. NAVEGACIÓN (TABS)
// ==========================================
function initNavegacion() {
    const navBtns = document.querySelectorAll('.nav-btn[data-target]');
    const sections = document.querySelectorAll('.admin-section');

    navBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetId = e.currentTarget.getAttribute('data-target');
            
            navBtns.forEach(b => b.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            e.currentTarget.classList.add('active');
            document.getElementById(targetId).classList.add('active');
        });
    });
}

// ==========================================
// 2. CATÁLOGO DE PRODUCTOS (Filtros y Búsqueda)
// ==========================================
function initCatalogoProductos() {
    const catBtns = document.querySelectorAll('.cat-btn');
    const searchInput = document.getElementById('searchProduct');
    const productItems = document.querySelectorAll('.product-item');

    if (!catBtns.length) return;

    // Categoría activa por defecto (la primera)
    let activeCat = catBtns[0].getAttribute('data-cat');
    catBtns[0].classList.add('active');

    function aplicarFiltros() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        
        productItems.forEach(item => {
            const itemCat = item.getAttribute('data-cat');
            const itemName = item.getAttribute('data-name').toLowerCase();
            
            const matchCategory = (itemCat === activeCat);
            const matchSearch = itemName.includes(searchTerm);

            if (matchCategory && matchSearch) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    }

    catBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            catBtns.forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            activeCat = e.currentTarget.getAttribute('data-cat');
            aplicarFiltros();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', aplicarFiltros);
    }

    // Ejecutar filtro inicial
    aplicarFiltros();
}

// ==========================================
// 3. GESTIÓN DE MODALES
// ==========================================
function initModales() {
    // Cerrar modales
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
        });
    });

    // Abrir Modal de Producto (Nuevo o Editar)
    document.querySelectorAll('.btn-modal-producto').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.currentTarget.getAttribute('data-id') || '0';
            const nombre = e.currentTarget.getAttribute('data-nombre') || '';
            const cat = e.currentTarget.getAttribute('data-cat') || 'Bebidas Calientes';
            const precio = e.currentTarget.getAttribute('data-precio') || '';
            const desc = e.currentTarget.getAttribute('data-desc') || '';
            const img = e.currentTarget.getAttribute('data-img') || '';

            document.getElementById('prod_id').value = id;
            document.getElementById('prod_nombre').value = nombre;
            document.getElementById('prod_cat').value = cat;
            document.getElementById('prod_precio').value = precio;
            document.getElementById('prod_desc').value = desc;
            document.getElementById('prod_img').value = img;

            document.getElementById('modalProdTitle').innerText = (id === '0') ? '➕ Nuevo Producto' : '✏️ Editar Producto';
            document.getElementById('productoModal').classList.add('active');
        });
    });

    // Abrir Modal de Merma
    document.querySelectorAll('.btn-modal-merma').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.getElementById('merma_insumo_id').value = e.currentTarget.getAttribute('data-id');
            document.getElementById('merma_insumo_nombre').value = e.currentTarget.getAttribute('data-nombre');
            document.getElementById('mermaModal').classList.add('active');
        });
    });

    // Abrir Modal Solicitar Proveedor
    document.querySelectorAll('.btn-modal-solicitar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.getElementById('sol_insumo_id').value = e.currentTarget.getAttribute('data-id');
            document.getElementById('sol_insumo_nombre').value = e.currentTarget.getAttribute('data-nombre');
            document.getElementById('solicitarModal').classList.add('active');
        });
    });
}

// ==========================================
// 4. GRÁFICOS DEL DASHBOARD (Chart.js)
// ==========================================
function initGraficosDashboard() {
    const dataNode = document.getElementById('chartDataObj');
    if (!dataNode || typeof Chart === 'undefined') return;

    try {
        const dbData = JSON.parse(dataNode.textContent);

        // Gráfico 1: Ventas últimos 7 días
        const ctxVentas = document.getElementById('chartVentasDias');
        if (ctxVentas && dbData.ventasDias) {
            new Chart(ctxVentas, {
                type: 'line',
                data: {
                    labels: dbData.ventasDias.map(d => d.fecha),
                    datasets: [{
                        label: 'Ingresos (S/.)',
                        data: dbData.ventasDias.map(d => d.total),
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        // Gráfico 2: Distribución del Catálogo
        const ctxCat = document.getElementById('chartCategorias');
        if (ctxCat && dbData.categorias) {
            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: dbData.categorias.map(c => c.categoria),
                    datasets: [{
                        data: dbData.categorias.map(c => c.qty),
                        backgroundColor: ['#6f4e37', '#c4a77d', '#137333', '#d63031', '#f39c12']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    } catch (e) {
        console.error("Error cargando gráficos: ", e);
    }
}

// ==========================================
// 5. UX: ALERTAS EFÍMERAS
// ==========================================
function autoOcultarAlertas() {
    document.querySelectorAll('.alert').forEach(alerta => {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 500);
        }, 5000);
    });
}