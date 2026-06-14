<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
session_start();

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
            // Ya no buscamos por email, buscamos por la columna `usuario`
            $stmt = $pdo->prepare('SELECT id_usuario, nombre, password_hash, rol, estado FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['estado'] !== 'Activo') {
                    $error = 'Tu cuenta está inactiva.';
                } else {
                    $_SESSION['user_id'] = (int) $user['id_usuario'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['role'] = $user['rol'];
                    redirigirPorRol($user['rol']);
                }
            } else {
                $error = '⚠️ Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de servidor: ' . $e->getMessage();
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
  <title>☕ Iniciar Sesión - Cachito</title>
  <style>
    :root { --color-primary: #6f4e37; --color-dark: #3d2817; font-family: sans-serif; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #fcfbf9; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .card { background: white; padding: 2.5rem; border-radius: 1rem; box-shadow: 0 10px 30px rgba(61,40,23,0.12); width: 100%; max-width: 400px; }
    .form-control { width: 100%; padding: 0.85rem; border: 1px solid #ddd; border-radius: 0.5rem; margin-bottom: 1rem; }
    .btn { width: 100%; padding: 0.85rem; background: var(--color-primary); color: white; border: none; border-radius: 0.5rem; font-weight: bold; cursor: pointer; }
    .btn:hover { background: #553b29; }
    .alert { background: #fce8e6; color: #c5221f; padding: 0.85rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: center; }
  </style>
</head>
<body>
  <div class="card">
    <h2 style="text-align: center; color: var(--color-primary); margin-bottom: 0.5rem;">☕ Cachito</h2>
    <p style="text-align: center; color: #888; margin-bottom: 1.5rem;">Ingresa a tu cuenta</p>
    
    <?php if ($error): ?><div class="alert"><?php echo $error; ?></div><?php endif; ?>

    <form action="login.php" method="POST" id="loginForm">
      <label style="font-weight: bold; font-size: 0.9rem;">👤 Usuario:</label>
      <input type="text" name="usuario" class="form-control" placeholder="Ej. augusto" required>

      <label style="font-weight: bold; font-size: 0.9rem;">🔐 Contraseña:</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>

      <button type="submit" class="btn" id="btnEntrar">Ingresar al Sistema</button>
    </form>
    
    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
      ¿Eres un cliente nuevo? <a href="register.php" style="color: var(--color-primary); font-weight: bold;">Regístrate</a>
    </p>
  </div>
</body>
</html>