## 📋 Descripción del Proyecto

Este sistema es una solución centralizada bajo la arquitectura **LAMP**, diseñada para la gestión operativa de la Cafetería Cachito. La versión **v1.1** integra ventas físicas (POS), delivery web, reservas de mesa con garantía y auditoría de inventario.

### Módulos Principales

* **Administración:** Control de inventarios y registro de mermas.
* **POS Barista:** Interfaz táctil de despacho con cálculo automático de recetas.
* **Delivery Cliente:** Catálogo web con gestión de perfil y carrito de compras.
* **Reservas:** Sistema de reserva de mesas con abono de garantía (Yape/Efectivo).

---

## 🚀 Guía de Instalación Rápida

### 1. Preparación del Entorno

1. Instala **XAMPP** (Asegúrate de que Apache y MySQL estén activos).
2. Descarga el repositorio y coloca la carpeta `cafeteria_cachito` en:
`C:\xampp\htdocs\`

### 2. Base de Datos (Integridad Relacional)

1. Abre `http://localhost/phpmyadmin/`.
2. Crea una base de datos llamada **`cafeteria_db`** (Cotejamiento: `utf8mb4_unicode_ci`).
3. Ve a la pestaña **Importar**, selecciona el archivo `cafeteria_db.sql` adjunto y pulsa **Importar**.

### 3. Ejecución

1. Abre tu navegador y dirígete a: `http://localhost/cafeteria_cachito/auth/login.php`

---

## 🔐 Credenciales de Acceso (Entorno de Pruebas)

| Rol | Usuario | Contraseña |
| --- | --- | --- |
| **Administrador** | `admin` | `Admin2026@` |
| **Barista** | `barista` | `Barista2026@` |
| **Cliente** | `augusto` | `Cliente2026@` |

---

