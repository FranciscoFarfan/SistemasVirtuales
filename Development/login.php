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

    $error = 'Usuario o contraseña incorrectos. Revisa tus datos e inténtalo de nuevo.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Sistema de Tutorías</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css?v=20261128b">
</head>
<body>
    <div class="login-layout">
        <aside class="login-aside">
            <a class="brand" href="login.php">
                <img class="brand-logo" src="https://www.upslp.edu.mx/Ingreso/ReferenciaBancaria2O24SEDI/_img/logo.png" alt="Universidad Politécnica de San Luis Potosí" width="150" height="58">
            </a>
            <div>
                <h1>Sistema de Tutorías</h1>
                <p>Registra y consulta la información de los alumnos que acompañas durante su trayectoria académica.</p>
            </div>
            <small>Universidad Politécnica de San Luis Potosí</small>
        </aside>

        <main class="login-main">
            <section class="card login-card">
                <div class="card-body">
                    <h2 class="card-heading">Iniciar sesión</h2>
                    <p class="card-description">Ingresa con tu usuario institucional.</p>
                    <?php if ($error): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
                    <form method="post" class="form-grid">
                        <div class="field">
                            <label for="usuario">Usuario</label>
                            <input id="usuario" name="usuario" type="text" autocomplete="username" required autofocus value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
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
    </div>
</body>
</html>
