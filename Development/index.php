<?php
session_start();
require 'conexion.php'; //archivo donde se conecta $pdo a MariaDB

//verifica si se inicio sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$editando = null;

//funcion para eliminar alumno
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM alumnos WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?msg=eliminado');
    exit;
}

//funcion para cargar datos al editar
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE id = ?");
    $stmt->execute([$id]);
    $editando = $stmt->fetch(PDO::FETCH_ASSOC);
}

//funcion para guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = $_POST['id'] ?? '';
    $matricula        = trim($_POST['matricula']);
    $nombre           = trim($_POST['nombre']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']) ?: null;
    $carrera          = trim($_POST['carrera']);
    $semestre         = (int)$_POST['semestre'];
    $correo           = trim($_POST['correo']) ?: null;

    try {
        if ($id) {
            //actualizar alumno existente y registrar quien lo editó
            $sql = "UPDATE alumnos SET 
                    matricula = ?, nombre = ?, apellido_paterno = ?, apellido_materno = ?, 
                    carrera = ?, semestre = ?, correo = ?, 
                    modificado_por = ?, modificado_en = NOW() 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$matricula, $nombre, $apellido_paterno, $apellido_materno, $carrera, $semestre, $correo, $usuario_id, $id]);
            header('Location: index.php?msg=editado');
            exit;
        } else {
            //insertar nuevo alumno y registrar quién lo creo
            $sql = "INSERT INTO alumnos 
                    (matricula, nombre, apellido_paterno, apellido_materno, carrera, semestre, correo, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$matricula, $nombre, $apellido_paterno, $apellido_materno, $carrera, $semestre, $correo, $usuario_id]);
            header('Location: index.php?msg=creado');
            exit;
        }
    } catch (PDOException $e) {
        $mensaje = "Error al guardar (verifica que la matrícula no esté repetida): " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'creado') $mensaje = "Alumno registrado correctamente.";
    if ($_GET['msg'] === 'editado') $mensaje = "Datos del alumno actualizados.";
    if ($_GET['msg'] === 'eliminado') $mensaje = "Alumno eliminado.";
}

//funcion para consultas
$sql = "SELECT a.*, 
               u1.usuario AS creador, 
               u2.usuario AS editor 
        FROM alumnos a
        LEFT JOIN usuarios u1 ON a.creado_por = u1.id
        LEFT JOIN usuarios u2 ON a.modificado_por = u2.id
        ORDER BY a.id DESC";
$alumnos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Tutorías</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php">
            <img class="brand-logo" src="https://www.upslp.edu.mx/Ingreso/ReferenciaBancaria2O24SEDI/_img/logo.png" alt="Logotipo de la Universidad Politécnica de San Luis Potosí">
            <span class="brand-copy">
                <strong>Sistema de Tutorías</strong>
                <span>Universidad Politécnica de San Luis Potosí</span>
            </span>
        </a>
        <div class="header-user">
            <span>Sesión: <strong><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin') ?></strong></span>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </header>

    <main class="page-shell">
        <div class="page-intro">
            <h1>Gestión de alumnos</h1>
            <p>Registra, consulta y administra la información de tutorías.</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <section class="card">
                <div class="card-body">
                    <h2 class="card-heading"><?= $editando ? 'Editar alumno' : 'Registrar alumno' ?></h2>
                    <p class="card-description">Los campos con * son obligatorios.</p>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="id" value="<?= $editando['id'] ?? '' ?>">
                        <div class="form-grid">
                            <div class="field">
                                <label for="matricula">Matrícula *</label>
                                <input id="matricula" type="text" name="matricula" required value="<?= htmlspecialchars($editando['matricula'] ?? '') ?>" placeholder="Ej. 20240001">
                            </div>
                            <div class="field">
                                <label for="nombre">Nombre(s) *</label>
                                <input id="nombre" type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>" placeholder="Nombre(s)">
                            </div>
                            <div class="field">
                                <label for="apellido_paterno">Apellido paterno *</label>
                                <input id="apellido_paterno" type="text" name="apellido_paterno" required value="<?= htmlspecialchars($editando['apellido_paterno'] ?? '') ?>" placeholder="Apellido paterno">
                            </div>
                            <div class="field">
                                <label for="apellido_materno">Apellido materno</label>
                                <input id="apellido_materno" type="text" name="apellido_materno" value="<?= htmlspecialchars($editando['apellido_materno'] ?? '') ?>" placeholder="Apellido materno">
                            </div>
                            <div class="field">
                                <label for="carrera">Carrera *</label>
                                <input id="carrera" type="text" name="carrera" required value="<?= htmlspecialchars($editando['carrera'] ?? '') ?>" placeholder="Nombre de la carrera">
                            </div>
                            <div class="field">
                                <label for="semestre">Semestre *</label>
                                <input id="semestre" type="number" name="semestre" min="1" max="15" required value="<?= htmlspecialchars($editando['semestre'] ?? '') ?>" placeholder="1–15">
                            </div>
                            <div class="field">
                                <label for="correo">Correo</label>
                                <input id="correo" type="email" name="correo" value="<?= htmlspecialchars($editando['correo'] ?? '') ?>" placeholder="alumno@ejemplo.edu.mx">
                            </div>
                        </div>
                        <div class="button-row">
                            <button class="button" type="submit"><?= $editando ? 'Guardar cambios' : 'Registrar alumno' ?></button>
                            <?php if ($editando): ?>
                                <a class="button button-secondary" href="index.php">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card table-card">
                <div class="table-heading">
                    <div>
                        <h2 class="card-heading">Alumnos registrados</h2>
                        <p class="card-description">Listado de alumnos en el sistema.</p>
                    </div>
                    <span class="count-badge"><?= count($alumnos) ?> registro<?= count($alumnos) === 1 ? '' : 's' ?></span>
                </div>
                <?php if ($alumnos): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Alumno</th>
                                    <th>Carrera / semestre</th>
                                    <th>Correo</th>
                                    <th>Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumnos as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['matricula']) ?></td>
                                    <td><span class="student-name"><?= htmlspecialchars(trim($a['nombre'] . ' ' . $a['apellido_paterno'] . ' ' . ($a['apellido_materno'] ?? ''))) ?></span></td>
                                    <td><?= htmlspecialchars($a['carrera']) ?><span class="student-meta"><?= (int)$a['semestre'] ?>° semestre</span></td>
                                    <td><?= htmlspecialchars($a['correo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['creador'] ?? 'Desconocido') ?><span class="student-meta"><?= $a['editor'] ? 'Editó: ' . htmlspecialchars($a['editor']) : 'Sin editar' ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <a href="index.php?editar=<?= (int)$a['id'] ?>">Editar</a>
                                            <a class="delete-link" href="index.php?eliminar=<?= (int)$a['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Aún no hay alumnos registrados. Usa el formulario para agregar el primero.</div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
