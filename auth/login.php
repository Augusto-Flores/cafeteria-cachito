<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

// Mejora de seguridad: Evitar acceso a cookies mediante JavaScript (Mitigación XSS)
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']), // True si usas HTTPS
    'cookie_samesite' => 'Strict'
]);

if (isset($_SESSION['role'])) {
    redirigirPorRol($_SESSION['role']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim((string)$_POST['usuario']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($usuario === '' || $password === '') {
        $error = 'Por favor, ingresa tu usuario y contraseña.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT id_usuario, nombre, password_hash, rol, estado FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['estado'] !== 'Activo') {
                    $error = 'Tu cuenta está inactiva. Contacta al administrador.';
                } else {
                    // Prevención de Fixation de Sesión
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = (int) $user['id_usuario'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['role'] = $user['rol'];
                    redirigirPorRol($user['rol']);
                }
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            // En producción, nunca mostrar el error real de PDO al usuario. Se registra en un log.
            error_log('Error en Login: ' . $e->getMessage());
            $error = 'Error interno del servidor. Inténtalo más tarde.';
        }
    }
}

function redirigirPorRol(string $role): void {
    $r = strtolower($role);
    if ($r === 'administrador' || $r === 'admin') header('Location: ../admin/dashboard_admin.php');
    elseif ($r === 'barista') header('Location: ../barista/pos.php');
    elseif ($r === 'cliente') header('Location: ../cliente/catalogo.php');
    else header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar Sesión | Cachito</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-bg">

  <main class="auth-card" role="main">
    <div class="auth-logo" aria-label="Logotipo de la Cafetería Cachito">☕ Cachito</div>
    <p class="auth-subtitle">Ingresa con tus credenciales</p>
    
    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center" role="alert" aria-live="assertive">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle-fill me-2" viewBox="0 0 16 16">
          <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST" id="loginForm" class="needs-validation" novalidate>
      
      <div class="mb-3">
        <label for="usuario" class="form-label">Usuario</label>
        <div class="input-group">
            <span class="input-group-text bg-white" aria-hidden="true">👤</span>
            <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Ej. augusto" required aria-required="true">
            <div class="invalid-feedback">Por favor, ingresa tu usuario.</div>
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
            <span class="input-group-text bg-white" aria-hidden="true">🔐</span>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required aria-required="true">
            <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="togglePassword" aria-label="Mostrar contraseña">👁️</button>
            <div class="invalid-feedback">Por favor, ingresa tu contraseña.</div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-cachito" id="btnEntrar">Ingresar al Sistema</button>
      </div>
    </form>
    
    <div class="auth-links text-center mt-4">
      <p class="mb-0">¿Eres un cliente nuevo? <a href="register.php">Regístrate aquí</a></p>
    </div>
  </main>

  <script src="../assets/js/auth.js"></script>
</body>
</html>