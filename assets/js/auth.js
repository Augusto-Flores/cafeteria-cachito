/**
 * CAFETERÍA CACHITO - SCRIPTS DE AUTENTICACIÓN
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const btnEntrar = document.getElementById('btnEntrar');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Validación básica en el frontend para evitar submits vacíos
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            if (!loginForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                // Prevenir doble clic mientras carga
                btnEntrar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ingresando...';
                btnEntrar.disabled = true;
            }
            loginForm.classList.add('was-validated');
        });
    }

    // Funcionalidad para mostrar/ocultar contraseña (Mejora de UX y Accesibilidad)
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            // Cambiar el icono (emoji en este caso, o clase de FontAwesome si usas)
            this.textContent = type === 'password' ? '👁️' : '🙈';
            
            // Atributo ARIA para lectores de pantalla
            this.setAttribute('aria-label', type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    }
});