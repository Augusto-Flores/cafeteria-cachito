<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize_input($_POST['nombre'] ?? '');
    $usuario = sanitize_input($_POST['usuario'] ?? '');
    $direccion = sanitize_input($_POST['direccion'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($nombre === '' || $usuario === '' || $direccion === '' || $telefono === '' || $password === '') {
        $errors[] = 'Completa todos los campos obligatorios.';
    } else {
        try {
            $pdo = getPDO();
            // Comprobar si el usuario ya existe
            $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            
            if ($stmt->fetch()) {
                $errors[] = 'El usuario elegido ya está en uso. Intenta con otro.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // El rol "Cliente" es automático
                $insert = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol, estado, direccion, telefono, fecha_creacion) VALUES (?, ?, ?, "Cliente", "Activo", ?, ?, NOW())');
                $insert->execute([$nombre, $usuario, $hash, $direccion, $telefono]);
                
                echo "<script>alert('✅ ¡Cuenta de cliente creada exitosamente!'); window.location.href='login.php';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Error de servidor: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>☕ Registro Cliente - Cachito</title>
  <style>
    :root { --color-primary: #6f4e37; font-family: sans-serif; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #fcfbf9; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 1rem; }
    .card { background: white; padding: 2.5rem; border-radius: 1rem; box-shadow: 0 10px 30px rgba(61,40,23,0.12); width: 100%; max-width: 500px; }
    .form-control { width: 100%; padding: 0.85rem; border: 1px solid #ddd; border-radius: 0.5rem; margin-bottom: 1rem; }
    .btn { width: 100%; padding: 0.85rem; background: var(--color-primary); color: white; border: none; border-radius: 0.5rem; font-weight: bold; cursor: pointer; }
    .alert { background: #fce8e6; color: #c5221f; padding: 0.85rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: center; }
  </style>
</head>
<body>
  <div class="card">
    <h2 style="text-align: center; color: var(--color-primary); margin-bottom: 1.5rem;">📝 Registro de Cliente</h2>
    
    <?php foreach ($errors as $err): ?><div class="alert"><?php echo $err; ?></div><?php endforeach; ?>

    <form action="register.php" method="POST">
      <label>👤 Nombre Completo:</label>
      <input type="text" name="nombre" class="form-control" placeholder="Ej. Juan Pérez" required>

      <label>👤 Crear un Usuario (Login):</label>
      <input type="text" name="usuario" class="form-control" placeholder="Ej. juanperez" required>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div>
            <label>📍 Dirección Delivery:</label>
            <input type="text" name="direccion" class="form-control" required>
          </div>
          <div>
            <label>📱 Celular:</label>
            <input type="text" name="telefono" class="form-control" required>
          </div>
      </div>

      <label>🔐 Contraseña:</label>
      <input type="password" name="password" class="form-control" required>

      <button type="submit" class="btn">Crear mi cuenta</button>
    </form>
    
    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
      <a href="login.php" style="color: var(--color-primary); font-weight: bold; text-decoration:none;">⬅️ Volver al Login</a>
    </p>
  </div>
</body>
</html>