/* =========================================================
   LÓGICA INTERACTIVA: MÓDULO CLIENTE (Event Driven)
   ========================================================= */

let carritoCliente = [];
const COSTO_DELIVERY = 1.50;

document.addEventListener('DOMContentLoaded', () => {

    // 1. LÓGICA DEL CATÁLOGO: FIlTROS DE CATEGORÍA
    const btnFiltros = document.querySelectorAll('.cat-filters-client .btn-filter');
    const catBlocks = document.querySelectorAll('.cat-block-cliente');

    btnFiltros.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const cat = e.currentTarget.getAttribute('data-categoria');
            
            // Cambiar botón activo
            btnFiltros.forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            
            // Ocultar/Mostrar bloques
            catBlocks.forEach(block => {
                if (block.getAttribute('data-categoria') === cat) {
                    block.classList.remove('d-none');
                } else {
                    block.classList.add('d-none');
                }
            });
        });
    });

    // 2. LÓGICA DEL CARRITO: AGREGAR PRODUCTOS
    const btnsAddCart = document.querySelectorAll('.btn-add-cart');
    btnsAddCart.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const idProducto = parseInt(btn.getAttribute('data-id'));
            const nombreProducto = btn.getAttribute('data-nombre');
            const precioProducto = parseFloat(btn.getAttribute('data-precio'));
            
            const buscado = carritoCliente.find(item => item.id === idProducto);
            if (buscado) buscado.cantidad += 1;
            else carritoCliente.push({ id: idProducto, nombre: nombreProducto, precio: precioProducto, cantidad: 1 });
            
            renderizarCarritoCliente();
        });
    });

    // 3. LÓGICA DEL MAPA (LEAFLET)
    const mapaContainer = document.getElementById('mapa-delivery');
    if (mapaContainer && typeof L !== 'undefined') {
        const inputLat = document.getElementById('latitud');
        const inputLng = document.getElementById('longitud');
        const inputDireccion = document.getElementById('direccion');

        const startLat = (inputLat && inputLat.value) ? parseFloat(inputLat.value) : -11.8744;
        const startLng = (inputLng && inputLng.value) ? parseFloat(inputLng.value) : -77.1264;

        const map = L.map('mapa-delivery').setView([startLat, startLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

        marker.on('dragend', function () {
            const position = marker.getLatLng();
            inputLat.value = position.lat;
            inputLng.value = position.lng;

            const oldVal = inputDireccion.value;
            inputDireccion.value = "Calculando nueva dirección...";

            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${position.lat}&lon=${position.lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        inputDireccion.value = data.display_name.split(',').slice(0, 3).join(',').trim();
                    } else {
                        inputDireccion.value = "Ubicación no detallada. Escríbela a mano.";
                        inputDireccion.removeAttribute('readonly'); 
                    }
                })
                .catch(() => {
                    inputDireccion.value = oldVal;
                    inputDireccion.removeAttribute('readonly'); 
                });
        });
    }

    // 4. LÓGICA DE RESERVAS: SELECCIÓN DE MESA
    const mesas = document.querySelectorAll('.minimap-mesa:not(.ocupada)');
    mesas.forEach(mesa => {
        mesa.addEventListener('click', (e) => {
            const valorRaw = e.currentTarget.getAttribute('data-valor');
            document.getElementById('txt-mesa-select').value = valorRaw;
            
            document.querySelectorAll('.minimap-mesa').forEach(m => m.classList.remove('selected'));
            e.currentTarget.classList.add('selected');

            const partes = valorRaw.split('|');
            if(partes.length === 2) {
                const capacidad = parseInt(partes[1], 10);
                const costo = capacidad * 5.00;
                document.getElementById('lbl-costo-reserva').innerText = `S/. ${costo.toFixed(2)}`;
                document.getElementById('box-pago-reserva').style.display = 'block';
                document.getElementById('btn-guardar-reserva').disabled = false;
            }
        });
    });

    // 5. LÓGICA DE PASARELAS DE PAGO (Reservas y Checkout)
    const paymentMethods = document.querySelectorAll('.method-card');
    paymentMethods.forEach(card => {
        card.addEventListener('click', (e) => {
            const tipo = e.currentTarget.getAttribute('data-method');
            document.getElementById('txt-metodo-web').value = tipo;
            
            paymentMethods.forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.gateway-panel').forEach(p => p.classList.remove('active'));
            
            e.currentTarget.classList.add('active');
            const panel = document.getElementById('panel-' + tipo.toLowerCase());
            if (panel) panel.classList.add('active');
        });
    });

    // 6. PROTECCIÓN CONTRA CONCURRENCIA (Doble Click)
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', () => {
            const btn = document.getElementById('client-btn-submit');
            if (btn) { btn.disabled = true; btn.innerText = '⏳ Procesando...'; }
        });
    }

    const reservaForm = document.getElementById('reservaForm');
    if (reservaForm) {
        reservaForm.addEventListener('submit', () => {
            const btn = document.getElementById('btn-guardar-reserva');
            if (btn) { btn.disabled = true; btn.innerText = '⏳ Procesando...'; }
        });
    }
});

// Función auxiliar: Alterar unidades renderizadas
window.alterarUnidades = function(id, delta) {
    const buscado = carritoCliente.find(item => item.id === id);
    if (buscado) {
        buscado.cantidad += delta;
        if (buscado.cantidad <= 0) carritoCliente = carritoCliente.filter(item => item.id !== id);
    }
    renderizarCarritoCliente();
};

function renderizarCarritoCliente() {
    const listContainer = document.getElementById('client-cart-list');
    const emptyMsg = document.getElementById('client-cart-empty');
    const btnSubmit = document.getElementById('client-btn-submit');
    
    if (!listContainer) return;
    listContainer.innerHTML = '';
    
    if (carritoCliente.length === 0) {
        if(emptyMsg) emptyMsg.classList.remove('d-none');
        if(btnSubmit) btnSubmit.disabled = true;
        document.getElementById('lbl-subtotal').innerText = 'S/. 0.00';
        document.getElementById('lbl-delivery').innerText = 'S/. 0.00';
        document.getElementById('lbl-total').innerText = 'S/. 0.00';
        document.getElementById('hid-order-json').value = '';
        return;
    }
    
    if(emptyMsg) emptyMsg.classList.add('d-none');
    if(btnSubmit) btnSubmit.disabled = false;
    
    let subtotal = 0;
    
    carritoCliente.forEach(item => {
        subtotal += (item.precio * item.cantidad);
        const li = document.createElement('li');
        li.className = 'cart-row-item';
        li.innerHTML = `
            <div class="w-50">
                <div class="fw-bold" style="font-size:0.9rem;">${item.nombre}</div>
                <small class="text-muted">S/. ${item.precio.toFixed(2)} c/u</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="alterarUnidades(${item.id}, -1)">-</button>
                <span class="fw-bold text-center" style="width: 20px;">${item.cantidad}</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="alterarUnidades(${item.id}, 1)">+</button>
            </div>
        `;
        listContainer.appendChild(li);
    });
    
    let total = subtotal + COSTO_DELIVERY;
    
    document.getElementById('lbl-subtotal').innerText = `S/. ${subtotal.toFixed(2)}`;
    document.getElementById('lbl-delivery').innerText = `S/. ${COSTO_DELIVERY.toFixed(2)}`;
    document.getElementById('lbl-total').innerText = `S/. ${total.toFixed(2)}`;
    
    document.getElementById('hid-order-json').value = JSON.stringify(carritoCliente);
    document.getElementById('hid-subtotal').value = subtotal.toFixed(2);
    document.getElementById('hid-total').value = total.toFixed(2);
}