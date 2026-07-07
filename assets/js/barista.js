/* =========================================================
   LÓGICA INTERACTIVA: MÓDULO PUNTO DE VENTA (BARISTA)
   ========================================================= */

let carritoPOS = [];
let funcionConfirmacionPendiente = null;

// ==========================================
// 1. INICIALIZACIÓN DE EVENTOS (DOM)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    initFiltrosCategoria();
    initBotonesProductos();
    initBotonesCarrito();
    initModalPagos();
    initModalReservas();
    initModalConfirmacionCustom();
    initBotonesAccionReservas();
    initProteccionConcurrencia();
    autoOcultarAlertas(); // <-- NUEVA FUNCIÓN AÑADIDA
});

// ==========================================
// 2. FUNCIONES DEL CATÁLOGO Y FILTROS
// ==========================================
function initFiltrosCategoria() {
    const btnFiltros = document.querySelectorAll('.btn-cat-filter');
    const catBlocks = document.querySelectorAll('.cat-block');

    btnFiltros.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const categoria = e.currentTarget.getAttribute('data-cat');
            btnFiltros.forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');

            catBlocks.forEach(block => {
                if (block.getAttribute('data-cat-block') === categoria) {
                    block.classList.remove('d-none');
                } else {
                    block.classList.add('d-none');
                }
            });
        });
    });
}

function initBotonesProductos() {
    const botonesAdd = document.querySelectorAll('.btn-add-prod');
    botonesAdd.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const dataStr = e.currentTarget.getAttribute('data-prod');
            if (dataStr) agregarProducto(JSON.parse(dataStr));
        });
    });
}

// ==========================================
// 3. FUNCIONES DEL CARRITO (COMANDA)
// ==========================================
function agregarProducto(prod) {
    const buscado = carritoPOS.find(item => item.id === prod.id);
    if (buscado) buscado.cantidad += 1;
    else carritoPOS.push({ id: prod.id, nombre: prod.nombre, precio: prod.precio, cantidad: 1 });
    calcularTotal();
}

function actualizarCantidad(id, delta) {
    const buscado = carritoPOS.find(item => item.id === id);
    if (buscado) {
        buscado.cantidad += delta;
        if (buscado.cantidad <= 0) {
            eliminarProducto(id);
            return;
        }
    }
    calcularTotal();
}

function eliminarProducto(id) {
    carritoPOS = carritoPOS.filter(item => item.id !== id);
    calcularTotal();
}

function vaciarCarrito() {
    if (carritoPOS.length === 0) return;
    
    mostrarConfirmacion(
        'Vaciar Comanda',
        '¿Estás seguro de que deseas eliminar todos los artículos de la comanda actual?',
        '🗑️',
        'Sí, Vaciar',
        'btn-danger',
        () => {
            carritoPOS = [];
            calcularTotal();
        }
    );
}

function calcularTotal() {
    const listContainer = document.getElementById('pos-items-list');
    const emptyAlert = document.getElementById('msg-empty-pos');
    const btnPay = document.getElementById('pos-action-pay');
    
    listContainer.innerHTML = '';
    
    if (carritoPOS.length === 0) {
        emptyAlert.style.display = 'block';
        btnPay.disabled = true;
        document.getElementById('pos-subtotal').innerText = 'S/. 0.00';
        document.getElementById('pos-igv').innerText = 'S/. 0.00';
        document.getElementById('pos-total').innerText = 'S/. 0.00';
        return;
    }
    
    emptyAlert.style.display = 'none';
    btnPay.disabled = false;
    
    let sumadorTotal = 0;
    
    carritoPOS.forEach(item => {
        const totalLinea = item.precio * item.cantidad;
        sumadorTotal += totalLinea;
        
        const li = document.createElement('li');
        li.className = 'checkout-row';
        li.innerHTML = `
            <div style="max-width: 55%;">
                <div class="fw-bold" style="font-size:0.9rem;">${item.nombre}</div>
                <small class="text-muted">S/. ${item.precio.toFixed(2)} u.</small>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="circle-qty-btn btn-qty-minus" data-id="${item.id}">-</button>
                <span class="fw-bold text-center" style="min-width: 20px;">${item.cantidad}</span>
                <button type="button" class="circle-qty-btn btn-qty-plus" data-id="${item.id}">+</button>
                <span class="fw-bold text-end ms-2" style="min-width: 65px;">S/. ${totalLinea.toFixed(2)}</span>
                <button type="button" class="delete-line-x btn-remove-line" data-id="${item.id}">×</button>
            </div>
        `;
        listContainer.appendChild(li);
    });
    
    let baseImponible = sumadorTotal / 1.18;
    let impuestoIgv = sumadorTotal - baseImponible;
    
    document.getElementById('pos-subtotal').innerText = `S/. ${baseImponible.toFixed(2)}`;
    document.getElementById('pos-igv').innerText = `S/. ${impuestoIgv.toFixed(2)}`;
    document.getElementById('pos-total').innerText = `S/. ${sumadorTotal.toFixed(2)}`;
    
    document.getElementById('input-json-order').value = JSON.stringify(carritoPOS);
    document.getElementById('input-total-amount').value = sumadorTotal.toFixed(2);
}

function initBotonesCarrito() {
    const posItemsList = document.getElementById('pos-items-list');
    if(posItemsList) {
        posItemsList.addEventListener('click', (e) => {
            const id = parseInt(e.target.getAttribute('data-id'));
            if (e.target.classList.contains('btn-qty-minus')) actualizarCantidad(id, -1);
            else if (e.target.classList.contains('btn-qty-plus')) actualizarCantidad(id, 1);
            else if (e.target.classList.contains('btn-remove-line')) eliminarProducto(id);
        });
    }

    const btnVaciar = document.getElementById('btn-vaciar-comanda');
    if(btnVaciar) btnVaciar.addEventListener('click', vaciarCarrito);
}

// ==========================================
// 4. FUNCIONES DEL MODAL DE PAGOS
// ==========================================
function initModalPagos() {
    const btnCobrar = document.getElementById('pos-action-pay');
    const btnCerrar = document.getElementById('btn-cerrar-pago');
    const metodosPago = document.querySelectorAll('.method-card');

    if(btnCobrar) {
        btnCobrar.addEventListener('click', () => {
            if (carritoPOS.length === 0) return;
            document.getElementById('modal-amount-label').innerText = document.getElementById('pos-total').innerText;
            seleccionarMetodoPago('Efectivo');
            document.getElementById('pos-payment-modal').classList.add('show');
        });
    }

    if(btnCerrar) {
        btnCerrar.addEventListener('click', () => {
            document.getElementById('pos-payment-modal').classList.remove('show');
        });
    }

    metodosPago.forEach(card => {
        card.addEventListener('click', (e) => {
            seleccionarMetodoPago(e.currentTarget.getAttribute('data-metodo'));
        });
    });
}

function seleccionarMetodoPago(tipo) {
    document.getElementById('input-payment-method').value = tipo;
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    
    const cardSelected = document.querySelector(`.method-card[data-metodo="${tipo}"]`);
    if(cardSelected) cardSelected.classList.add('selected');
}

// ==========================================
// 5. FUNCIONES DE MODALES RESERVAS
// ==========================================
function initModalReservas() {
    const btnAbrir = document.getElementById('btn-abrir-reservas');
    const btnsCerrar = document.querySelectorAll('.btn-cerrar-reservas');

    if(btnAbrir) btnAbrir.addEventListener('click', () => document.getElementById('modal-reservas').classList.add('show'));
    btnsCerrar.forEach(btn => btn.addEventListener('click', () => document.getElementById('modal-reservas').classList.remove('show')));
}

function initBotonesAccionReservas() {
    const btnsAction = document.querySelectorAll('.btn-accion-reserva');
    
    btnsAction.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const idReserva = btn.getAttribute('data-id');
            const accion = btn.getAttribute('data-accion');
            const form = document.getElementById(`form-reserva-${idReserva}`);
            document.getElementById(`accion-${idReserva}`).value = accion;

            if (accion === 'cancelar') {
                mostrarConfirmacion(
                    'Registrar No Show',
                    '¿Confirmas cancelar por retraso del cliente? La mesa quedará libre al instante.',
                    '❌', 'Sí, Cancelar Reserva', 'btn-danger',
                    () => { form.submit(); }
                );
            } else if (accion === 'finalizar') {
                mostrarConfirmacion(
                    'Finalizar Reserva',
                    '¿El cliente ya se retiró? Al aceptar, la mesa volverá a estar disponible para el público.',
                    '✅', 'Sí, Finalizar y Liberar', 'btn-success',
                    () => { form.submit(); }
                );
            } else {
                form.submit();
            }
        });
    });
}

// ==========================================
// 6. MODAL DE CONFIRMACIÓN (CUSTOM ALERT)
// ==========================================
function initModalConfirmacionCustom() {
    const btnCancel = document.getElementById('btn-confirm-cancel');
    const btnAccept = document.getElementById('btn-confirm-accept');

    if(btnCancel) {
        btnCancel.addEventListener('click', () => {
            document.getElementById('modal-confirm').classList.remove('show');
            funcionConfirmacionPendiente = null;
        });
    }

    if(btnAccept) {
        btnAccept.addEventListener('click', () => {
            document.getElementById('modal-confirm').classList.remove('show');
            if (funcionConfirmacionPendiente) {
                funcionConfirmacionPendiente();
                funcionConfirmacionPendiente = null;
            }
        });
    }
}

function mostrarConfirmacion(titulo, mensaje, icono, txtBoton, claseBoton, callback) {
    document.getElementById('confirm-title').innerText = titulo;
    document.getElementById('confirm-message').innerText = mensaje;
    document.getElementById('confirm-icon').innerText = icono;
    
    const btnAceptar = document.getElementById('btn-confirm-accept');
    btnAceptar.innerText = txtBoton;
    btnAceptar.className = `btn ${claseBoton} w-50 py-2 fw-bold`;
    
    funcionConfirmacionPendiente = callback;
    document.getElementById('modal-confirm').classList.add('show');
}

// ==========================================
// 7. PROTECCIÓN Y SEGURIDAD
// ==========================================
function initProteccionConcurrencia() {
    const formSender = document.getElementById('pos-form-sender');
    if(formSender) {
        formSender.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('pos-submit-final');
            btn.disabled = true;
            btn.innerText = '⏳ Procesando...';
            this.submit();
        });
    }
}

// ==========================================
// 8. EFECTOS UX: AUTO-OCULTAR ALERTAS
// ==========================================
function autoOcultarAlertas() {
    // Buscamos todas las alertas que imprime PHP en el HTML
    const alertas = document.querySelectorAll('.alert');
    
    alertas.forEach(alerta => {
        // Configuramos un temporizador de 5000 milisegundos (5 segundos)
        setTimeout(() => {
            // Le damos una transición CSS por JavaScript para que se desvanezca suavemente
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            
            // Esperamos medio segundo a que termine la animación y eliminamos el nodo del DOM
            setTimeout(() => {
                alerta.remove();
            }, 500);
            
        }, 5000); 
    });
}