/* =========================================================
   LÓGICA INTERACTIVA: MÓDULO CLIENTE (DELIVERY & RESERVAS)
   ========================================================= */

let carritoCliente = [];
const COSTO_DELIVERY = 1.50;

// INICIALIZACIÓN Y EVENTOS (Buena práctica de Arquitectura)
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Eventos para el Catálogo (Agregar al carrito)
    const btnsAddCart = document.querySelectorAll('.btn-add-cart');
    btnsAddCart.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const idProducto = parseInt(btn.getAttribute('data-id'));
            const nombreProducto = btn.getAttribute('data-nombre');
            const precioProducto = parseFloat(btn.getAttribute('data-precio'));
            
            comprarProducto({ id: idProducto, nombre: nombreProducto, precio: precioProducto });
        });
    });

    // 2. Bloqueo de doble envío por concurrencia (Formulario de Checkout)
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', () => {
            const btn = document.getElementById('client-btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerText = '⏳ Redirigiendo a Pasarela...';
            }
        });
    }

    // 3. Bloqueo de doble envío por concurrencia (Formulario de Reservas)
    const reservaForm = document.getElementById('reservaForm');
    if (reservaForm) {
        reservaForm.addEventListener('submit', () => {
            const btn = document.getElementById('btn-guardar-reserva');
            if (btn) {
                btn.disabled = true;
                btn.innerText = '⏳ Registrando Reserva...';
            }
        });
    }
});

// ==========================================
// FUNCIONES DEL CARRITO DE COMPRAS
// ==========================================

function comprarProducto(prod) {
    const buscado = carritoCliente.find(item => item.id === prod.id);
    if (buscado) {
        buscado.cantidad += 1;
    } else {
        carritoCliente.push({ id: prod.id, nombre: prod.nombre, precio: prod.precio, cantidad: 1 });
    }
    renderizarCarritoCliente();
}

// Modificar cantidades (Llamado desde los botones renderizados en el DOM)
function alterarUnidades(id, delta) {
    const buscado = carritoCliente.find(item => item.id === id);
    if (buscado) {
        buscado.cantidad += delta;
        if (buscado.cantidad <= 0) {
            carritoCliente = carritoCliente.filter(item => item.id !== id);
        }
    }
    renderizarCarritoCliente();
}

function renderizarCarritoCliente() {
    const listContainer = document.getElementById('client-cart-list');
    const emptyMsg = document.getElementById('client-cart-empty');
    const btnSubmit = document.getElementById('client-btn-submit');
    
    if (!listContainer) return;
    listContainer.innerHTML = '';
    
    if (carritoCliente.length === 0) {
        if(emptyMsg) emptyMsg.style.display = 'block';
        if(btnSubmit) btnSubmit.disabled = true;
        document.getElementById('lbl-subtotal').innerText = 'S/. 0.00';
        document.getElementById('lbl-delivery').innerText = 'S/. 0.00';
        document.getElementById('lbl-total').innerText = 'S/. 0.00';
        // Limpiar inputs ocultos por seguridad
        document.getElementById('hid-order-json').value = '';
        return;
    }
    
    if(emptyMsg) emptyMsg.style.display = 'none';
    if(btnSubmit) btnSubmit.disabled = false;
    
    let subtotalAcumulado = 0;
    
    carritoCliente.forEach(item => {
        const importe = item.precio * item.cantidad;
        subtotalAcumulado += importe;
        
        const li = document.createElement('li');
        li.className = 'checkout-row';
        li.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:0.85rem 0; border-bottom:1px solid #f5f1e8;';
        li.innerHTML = `
            <div style="max-width:60%;">
                <div style="font-weight:600; font-size:0.9rem;">${item.nombre}</div>
                <small style="color:#888;">S/. ${item.precio.toFixed(2)} c/u</small>
            </div>
            <div style="display:flex; align-items:center; gap:0.4rem;">
                <button type="button" class="btn btn-outline" style="padding: 0 0.5rem;" onclick="alterarUnidades(${item.id}, -1)">-</button>
                <span style="font-weight:700; width: 20px; text-align: center;">${item.cantidad}</span>
                <button type="button" class="btn btn-outline" style="padding: 0 0.5rem;" onclick="alterarUnidades(${item.id}, 1)">+</button>
            </div>
        `;
        listContainer.appendChild(li);
    });
    
    let totalGeneral = subtotalAcumulado + COSTO_DELIVERY;
    
    document.getElementById('lbl-subtotal').innerText = `S/. ${subtotalAcumulado.toFixed(2)}`;
    document.getElementById('lbl-delivery').innerText = `S/. ${COSTO_DELIVERY.toFixed(2)}`;
    document.getElementById('lbl-total').innerText = `S/. ${totalGeneral.toFixed(2)}`;
    
    document.getElementById('hid-order-json').value = JSON.stringify(carritoCliente);
    document.getElementById('hid-subtotal').value = subtotalAcumulado.toFixed(2);
    document.getElementById('hid-total').value = totalGeneral.toFixed(2);
}

// ==========================================
// FUNCIONES DE RESERVAS DE MESAS
// ==========================================

function seleccionarMesaCard(elemento, valorRaw) {
    document.getElementById('txt-mesa-select').value = valorRaw;
    
    document.querySelectorAll('.mesa-box-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    elemento.classList.add('selected');

    // Desglosar mesa y capacidad: Ejemplo "Mesa 3|4"
    const partes = valorRaw.split('|');
    if(partes.length === 2) {
        const capacidad = parseInt(partes[1], 10);
        const costoGarantia = capacidad * 5.00; // S/. 5 por asiento
        document.getElementById('lbl-costo-reserva').innerText = `S/. ${costoGarantia.toFixed(2)}`;
        document.getElementById('hid-costo-reserva')?.setAttribute('value', costoGarantia.toFixed(2));
        document.getElementById('box-pago-reserva').style.display = 'block';
    }
}

function filtrarCapacidadMesas(capacidad) {
    document.querySelectorAll('.mesa-item-wrapper').forEach(wrapper => {
        if (capacidad === 0) {
            wrapper.style.display = 'block';
        } else {
            const wrapperCap = parseInt(wrapper.getAttribute('data-capacidad'), 10);
            wrapper.style.display = (wrapperCap === capacidad) ? 'block' : 'none';
        }
    });
}

