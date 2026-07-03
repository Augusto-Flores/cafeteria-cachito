# Cafetería Cachito - Sistema POS y Delivery

Sistema web centralizado para la gestión operativa de una cafetería, desarrollado bajo la arquitectura LAMP. Este proyecto abarca desde el punto de venta en el local (POS) hasta los pedidos online y reservas de los clientes, integrando control de inventario en tiempo real.

## 🛠️ Stack Tecnológico

* **Backend:** PHP 8.x (Nativo, PDO)
* **Base de Datos:** MySQL (Motor InnoDB para soporte de transacciones)
* **Frontend:** HTML5, CSS3, JavaScript Vanilla, Bootstrap 5
* **Herramientas Externas:** Chart.js (Gráficos), Leaflet.js + OpenStreetMap API (Mapas interactivos).

## ⚙️ Módulos del Sistema

1. **Cliente (Frontend Público):** Catálogo interactivo con carrito de compras, simulador de pasarela de pago (PagoEfectivo/Yape), reservas de mesas con validación estricta de horarios y gestión de perfil con geolocalización de envíos.
2. **Barista (Terminal POS):** Punto de venta táctil, descuento automatizado de insumos basado en un mapa de recetas, y panel de control para gestionar el ciclo de vida de las reservas del día (Llegada / Cancelación por retraso / Finalización).
3. **Administrador (Back-office ERP):** Dashboard con indicadores (KPIs), gráficos históricos de ingresos, gestión de compras a proveedores, control de mermas de almacén y un CRUD completo de productos aplicando *Soft Delete* (borrado lógico).

## 🚀 Guía de Despliegue Local (Entorno XAMPP)

### 1. Preparación del Directorio
1. Instalar XAMPP y asegurarse de que los servicios **Apache** y **MySQL** estén en ejecución.
2. Clonar este repositorio o copiar la carpeta del proyecto directamente en el directorio público del servidor local.
   * Ruta típica (Windows): `C:\xampp\htdocs\cafeteria_cachito`

### 2. Configuración de la Base de Datos
1. Ingresar al gestor mediante `http://localhost/phpmyadmin/`.
2. Crear una nueva base de datos con el nombre exacto: **`cafeteria_db`** (Cotejamiento: `utf8mb4_unicode_ci`).
3. Importar el archivo `BD/cafeteria_db.sql` incluido en el proyecto. Este script contiene la estructura de las tablas, claves foráneas y datos semilla (inventario base, croquis de mesas y usuarios de prueba).

### 3. Ejecución y Accesos
Abre el navegador y dirígete a la ruta de inicio: `http://localhost/cafeteria_cachito/auth/login.php`

Para evaluar los distintos roles del sistema, puedes registrar una cuenta nueva de Cliente en la plataforma, o utilizar las siguientes credenciales de prueba preconfiguradas:
* **Administrador:** Usuario: `admin` / Clave: *Admin2026@*
* **Barista:** Usuario: `barista` / Clave: *Barista2026@*
* **Cliente:** Usuario: `augusto` / Clave: *Cliente2026@*

## 🔒 Estándares y Buenas Prácticas Aplicadas

* **Seguridad y Persistencia:** El sistema mitiga ataques de Inyección SQL mediante el uso estricto de `PDO Prepared Statements`. Se utiliza `password_hash()` para la protección de credenciales y `session_regenerate_id()` para evitar el secuestro de sesiones.
* **Manejo de Concurrencia:** Uso de transacciones (`commit` y `rollBack`) en MySQL InnoDB para garantizar la atomicidad en la inserción de ventas y actualización del inventario.
* **Accesibilidad Web:** Interfaz adaptada a la Ley N.º 29973, incluyendo contraste adecuado de lectura, estructuración semántica y compatibilidad básica con atributos ARIA.
* **Gestión de Configuración:** Control de versiones administrado bajo la metodología **GitFlow**, separando el desarrollo en ramas estructuradas (`main`, `develop`, `feature/*`).