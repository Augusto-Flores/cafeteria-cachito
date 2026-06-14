/* =========================================================
   LÓGICA INTERACTIVA: MÓDULO PUNTO DE VENTA (BARISTA)
   Ruta: assets/js/barista.js
   ========================================================= */

let carritoPOS = [];

// 1. Agregar producto desde el catálogo
function agregarLineaPedido(prod) {
    const buscado = carritoPOS.find(item => item.id === prod.id);
    if (buscado) {
        buscado.cantidad += 1;
    } else {
        carritoPOS.push({ id: prod.id, nombre: prod.nombre, precio: prod.precio, cantidad: 1 });
    }
    actualizarPantallaPOS();
}

// 2. Aumentar o disminuir cantidad
function cambiarUnidades(id, delta) {
    const buscado = carritoPOS.find(item => item.id === id);
    if (buscado) {
        buscado.cantidad += delta;
        if (buscado.cantidad <= 0) {
            quitarLineaIndividual(id);
            return;
        }
    }
    actualizarPantallaPOS();
}

// 3. Quitar una fila completa
function quitarLineaIndividual(id) {
    carritoPOS = carritoPOS.filter(item => item.id !== id);
    actualizarPantallaPOS();
}

// 4. Limpiar toda la comanda
function vaciarComandaBloque() {
    if (carritoPOS.length === 0) return;
    if (confirm('¿Vaciar todos los artículos de la comanda actual?')) {
        carritoPOS = [];
        actualizarPantallaPOS();
    }
}

// 5. Renderizar el HTML del carrito y calcular montos
function actualizarPantallaPOS() {
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
                <div style="font-weight: 600; font-size:0.9rem;">${item.nombre}</div>
                <small style="color: #888;">S/. ${item.precio.toFixed(2)} u.</small>
            </div>
            <div style="display: flex; align-items: center; gap: 0.4rem;">
                <button type="button" class="circle-qty-btn" onclick="cambiarUnidades(${item.id}, -1)">-</button>
                <span style="font-weight: 700; min-width: 18px; text-align: center;">${item.cantidad}</span>
                <button type="button" class="circle-qty-btn" onclick="cambiarUnidades(${item.id}, 1)">+</button>
                <span style="font-weight: 600; min-width: 65px; text-align: right; margin-left: 0.4rem;">S/. ${totalLinea.toFixed(2)}</span>
                <button type="button" class="delete-line-x" onclick="quitarLineaIndividual(${item.id})">×</button>
            </div>
        `;
        listContainer.appendChild(li);
    });
    
    let baseImponible = sumadorTotal / 1.18;
    let impuestoIgv = sumadorTotal - baseImponible;
    
    document.getElementById('pos-subtotal').innerText = `S/. ${baseImponible.toFixed(2)}`;
    document.getElementById('pos-igv').innerText = `S/. ${impuestoIgv.toFixed(2)}`;
    document.getElementById('pos-total').innerText = `S/. ${sumadorTotal.toFixed(2)}`;
    
    // Inyectar datos en el form oculto
    document.getElementById('input-json-order').value = JSON.stringify(carritoPOS);
    document.getElementById('input-total-amount').value = sumadorTotal.toFixed(2);
}

// 6. Lógica de la Modal de Pago
function procesarMedioPagoModal() {
    if (carritoPOS.length === 0) return;
    document.getElementById('modal-amount-label').innerText = document.getElementById('pos-total').innerText;
    setMetodo('Efectivo');
    document.getElementById('pos-payment-modal').classList.add('show');
}

function cerrarMedioPagoModal() {
    document.getElementById('pos-payment-modal').classList.remove('show');
}

function setMetodo(tipo) {
    document.getElementById('input-payment-method').value = tipo;
    const cardEf = document.getElementById('m-efectivo');
    const cardTar = document.getElementById('m-tarjeta');
    
    if (tipo === 'Efectivo') {
        cardEf.classList.add('selected');
        cardTar.classList.remove('selected');
    } else {
        cardTar.classList.add('selected');
        cardEf.classList.remove('selected');
    }
}

// 7. Mitigación de Concurrencia (Doble Click)
document.addEventListener('DOMContentLoaded', () => {
    const formSender = document.getElementById('pos-form-sender');
    if(formSender) {
        formSender.addEventListener('submit', function(e) {
            e.preventDefault(); // Pausa el envío nativo
            const btn = document.getElementById('pos-submit-final');
            btn.disabled = true;
            btn.innerText = '💾 Guardando Venta...';
            this.submit(); // Fuerza el envío seguro hacia el PHP
        });
    }
});
