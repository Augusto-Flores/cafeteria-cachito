# ☕ Cafetería Cachito - Sistema ERP, POS y Delivery

Este proyecto es una solución web integral desarrollada bajo la arquitectura **LAMP** (Linux, Apache, MySQL, PHP), diseñada para resolver la gestión operativa, logística y de ventas de una cafetería real. 

El sistema centraliza tres grandes módulos: **Back-office (Administrador), Punto de Venta (Barista) y E-commerce/Reservas (Cliente)**, aplicando buenas prácticas de Ingeniería de Software, Clean Code y normativas de accesibilidad peruana.

---

## 🚀 Guía de Exploración (Cómo probar el sistema)

Para la sustentación o pruebas del sistema, recomendamos seguir este flujo de demostración divido por roles:

### 1. Rol Cliente (Frontend Público)
* **Delivery con Geolocalización:** Entra a "Mi Perfil" y arrastra el pin en el mapa interactivo (construido con Leaflet.js y OpenStreetMap API). El sistema convertirá las coordenadas en una dirección legible automáticamente.
* **Catálogo Optimizado:** Navega por las categorías. Implementamos un sistema de pestañas por JS para evitar el "scroll infinito" y mejorar el rendimiento de carga (ya no existe el botón "Todos").
* **Reservas Interactivas:** Ve a reservar mesa. Selecciona una mesa libre en el croquis (minimapa CSS Grid). El sistema calcula dinámicamente el abono de garantía (S/. 5.00 por asiento) y genera un código CIP o QR dependiendo del método de pago elegido.

### 2. Rol Barista (Terminal POS)
* **Turno Automatizado:** Al iniciar sesión, el sistema lee la hora del servidor (timezone Lima) y asigna automáticamente "Turno Mañana", "Turno Tarde" o "Turno Noche" en la cabecera.
* **Comanda y Recetas:** Selecciona productos para la comanda. Al dar clic en "Confirmar", el sistema no solo registra la venta, sino que **descuenta el inventario base según un mapa de recetas** (Ej: Un "Frappuccino" descuenta gramos de café, mililitros de leche y 1 vaso físico).
* **Gestión de Reservas (Modales Custom):** Abre la agenda del día. Al marcar un "No Show" (cliente no llegó), no usamos el feo `window.alert` del navegador; implementamos un modal de confirmación dinámico y estilizado para mantener la inmersión UX. Al confirmar, la mesa se libera en tiempo real.

### 3. Rol Administrador (Back-office)
* **Dashboard y Analítica:** Visualiza los KPIs de ventas del día, productos en alerta de stock y gráficos de barras/donas renderizados con `Chart.js` leyendo datos en vivo de la BD.
* **Búsqueda Instantánea:** Ve al catálogo. Usa la barra de búsqueda; filtramos los nodos del DOM en tiempo real mediante JavaScript puro sin hacer peticiones innecesarias al servidor.
* **Auditoría de Almacén:** Ingresa a Inventario, registra una "Merma" (pérdida por caducidad) y luego solicita un insumo al proveedor. Todo se registra en un historial inmutable.

---

## 🛠️ Arquitectura y Métodos de Ingeniería Aplicados

Para garantizar que el software sea escalable y mantenible, estructuramos el código bajo las siguientes normativas técnicas:

### 1. Separación Estricta de Responsabilidades (SoC)
Refactorizamos todo el proyecto para cumplir el estándar de desarrollo moderno:
* **PHP / HTML:** Exclusivo para renderizado estructural y obtención de datos.
* **CSS:** Centralizado en archivos estáticos (`admin.css`, `cliente.css`, `barista.css`). Cero uso de `<style>` o CSS inline.
* **JavaScript:** Lógica de negocio visual extraída a archivos `.js`. Usamos **Event Delegation** para manejar clics dinámicos, eliminando por completo los obsoletos atributos `onclick` en el HTML.

### 2. Transacciones ACID en MySQL (InnoDB)
Para el procesamiento de pagos y reservas, mitigamos los errores de concurrencia. Usamos `PDO::beginTransaction()`, `commit()` y `rollBack()`. Si ocurre un fallo en el servidor justo después de insertar la venta pero antes de descontar el stock, la base de datos revierte toda la operación, evitando descuadres financieros.

### 3. Seguridad y Prevención de Concurrencia
* **Prepared Statements:** Toda consulta a la BD utiliza sentencias preparadas de PDO para anular cualquier intento de Inyección SQL.
* **Bloqueo de Doble Click:** Interceptamos los eventos `submit` en JavaScript para deshabilitar los botones de pago inmediatamente después del primer clic (con Progressive Enhancement usando inputs ocultos para asegurar el envío de datos).
* **Alertas Efímeras:** Las notificaciones de éxito o error desaparecen automáticamente mediante un `setTimeout` con transiciones de opacidad, manteniendo la interfaz limpia.

### 4. Accesibilidad e Inclusión (Ley N.º 29973)
El frontend superó la auditoría de **Google Lighthouse con puntuación 100/100**. Se utilizaron etiquetas semánticas (`<main>`, `<header>`), contrastes de color validados por normativas WCAG y compatibilidad con lectores de pantalla.

---

## ⚙️ Guía de Instalación (Entorno Local)

1. Instala **XAMPP** y asegúrate de que los servicios **Apache** y **MySQL** estén en ejecución.
2. Clona este repositorio o copia la carpeta del proyecto en `C:\xampp\htdocs\CAFETERIA_CACHITO`.
3. Ingresa a `http://localhost/phpmyadmin/`.
4. Crea una nueva base de datos llamada exactamente **`cafeteria_db`** (Cotejamiento: `utf8mb4_unicode_ci`).
5. Importa el archivo `BD/cafeteria_db.sql` incluido en el repositorio.
6. Abre el navegador y dirígete a: `http://localhost/CAFETERIA_CACHITO/auth/login.php`

**Credenciales de Prueba:**
* **Administrador:** Usuario: `admin` / Clave: *Admin2026@*
* **Barista:** Usuario: `barista` / Clave: *Barista2026@*
* **Cliente:** Usuario: `augusto` / Clave: *Cliente2026@*

> **Nota:** Las contraseñas en la base de datos están fuertemente encriptadas mediante el algoritmo BCRYPT (`password_hash()`).