<?php
declare(strict_types=1);

// 1. Inicializar la sesión para tener acceso a ella y poder destruirla
session_start();

// 2. Vaciar el arreglo global de la sesión (elimina variables como 'user_id', 'role', etc.)
$_SESSION = [];

// 3. Destruir la cookie de sesión en el navegador

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión físicamente en el servidor
session_destroy();

// 5. Redirigir al inicio de sesión
header('Location: login.php');
exit;