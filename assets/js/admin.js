/**
 * Lógica de interacción para el Panel Administrativo
 */

// Abrir el modal asignando los datos del insumo seleccionado
function abrirMermaModal(insumoId, insumoNombre) {
    const modal = document.getElementById('mermaModal');
    const inputId = document.getElementById('merma_insumo_id');
    const inputNombre = document.getElementById('merma_insumo_nombre');

    if (modal && inputId && inputNombre) {
        inputId.value = insumoId;
        inputNombre.value = insumoNombre;
        modal.classList.add('active');
    }
}

// Cerrar el modal limpiando el formulario
function cerrarMermaModal() {
    const modal = document.getElementById('mermaModal');
    const form = document.getElementById('mermaForm');
    
    if (modal) {
        modal.classList.remove('active');
    }
    if (form) {
        form.reset();
    }
}

// Eventos globales al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('mermaModal');
    const form = document.getElementById('mermaForm');

    // Cerrar el modal si se hace clic fuera de la caja blanca
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            cerrarMermaModal();
        }
    });

    // Controlar el estado de carga al enviar el formulario para evitar duplicados
    if (form) {
        form.addEventListener('submit', function() {
            const btnSubmit = this.querySelector('button[type="submit"]');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '⏳ Registrando...';
            }
        });
    }
});