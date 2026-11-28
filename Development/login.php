<?php
session_start();
require 'conexion.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, nombre, apellido_paterno, password_hash FROM usuarios WHERE usuario = ?');
    $stmt->execute([$usuario]);
    $cuenta = $stmt->fetch();

    if ($cuenta && password_verify($password, $cuenta['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $cuenta['id'];
        $_SESSION['usuario_nombre'] = trim($cuenta['nombre'] . ' ' . $cuenta['apellido_paterno']);
        header('Location: index.php');
        exit;
    }

    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Sistema de Tutorías</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .contenedor { max-width: 420px; width: calc(100% - 64px); margin-top: 10vh; }
        input { width: 100%; padding: 10px; margin-top: 6px; box-sizing: border-box; }
        .error { color: #a12622; background: #fde8e7; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <header class="encabezado"><h1>Sistema de Tutorías</h1></header>
    <main class="contenedor">
        <h2>Iniciar sesión</h2>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" type="text" autocomplete="username" required>
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
