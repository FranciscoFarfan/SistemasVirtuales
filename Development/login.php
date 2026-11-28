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
</head>
<body>
    <header class="site-header">
        <a class="brand" href="login.php">
            <img class="brand-logo" src="https://www.upslp.edu.mx/Ingreso/ReferenciaBancaria2O24SEDI/_img/logo.png" alt="Logotipo de la Universidad Politécnica de San Luis Potosí">
            <span class="brand-copy">
                <strong>Sistema de Tutorías</strong>
                <span>Universidad Politécnica de San Luis Potosí</span>
            </span>
        </a>
    </header>
    <main class="login-page">
        <section class="card login-card">
            <div class="card-body">
                <h1 class="card-heading">Bienvenido</h1>
                <p class="card-description">Inicia sesión para administrar las tutorías.</p>
                <?php if ($error): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
                <form method="post" class="form-grid">
                    <div class="field">
                        <label for="usuario">Usuario</label>
                        <input id="usuario" name="usuario" type="text" autocomplete="username" required>
                    </div>
                    <div class="field">
                        <label for="password">Contraseña</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="button" type="submit">Iniciar sesión</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
