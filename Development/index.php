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
    <title>Gestión de Tutorías</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f4f4f9; }
        .caja { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input { padding: 8px; margin: 5px 0 15px 0; width: 100%; box-sizing: border-box; }
        button { padding: 10px 15px; background: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #0056b3; color: white; }
        .alerta { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="caja">
        <p>Usuario activo: <strong><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin') ?></strong> | <a href="logout.php">Cerrar sesión</a></p>
        <?php if ($mensaje): ?>
            <div class="alerta"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <h3><?= $editando ? 'Editar Alumno' : 'Registrar Nuevo Alumno' ?></h3>
        <form method="POST" action="index.php">
            <input type="hidden" name="id" value="<?= $editando['id'] ?? '' ?>">

            <label>Matrícula *</label>
            <input type="text" name="matricula" required value="<?= htmlspecialchars($editando['matricula'] ?? '') ?>">

            <label>Nombre(s) *</label>
            <input type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">

            <label>Apellido Paterno *</label>
            <input type="text" name="apellido_paterno" required value="<?= htmlspecialchars($editando['apellido_paterno'] ?? '') ?>">

            <label>Apellido Materno</label>
            <input type="text" name="apellido_materno" value="<?= htmlspecialchars($editando['apellido_materno'] ?? '') ?>">

            <label>Carrera *</label>
            <input type="text" name="carrera" required value="<?= htmlspecialchars($editando['carrera'] ?? '') ?>">

            <label>Semestre *</label>
            <input type="number" name="semestre" min="1" max="15" required value="<?= htmlspecialchars($editando['semestre'] ?? '') ?>">

            <label>Correo</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($editando['correo'] ?? '') ?>">

            <button type="submit"><?= $editando ? 'Actualizar' : 'Guardar' ?></button>
            <?php if ($editando): ?>
                <a href="index.php" style="margin-left: 10px;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="caja">
        <h3>Alumnos Registrados</h3>
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nombre Completo</th>
                    <th>Carrera / Semestre</th>
                    <th>Correo</th>
                    <th>Registrado por</th>
                    <th>Última edición por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alumnos as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['matricula']) ?></td>
                    <td><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido_paterno'] . ' ' . $a['apellido_materno']) ?></td>
                    <td><?= htmlspecialchars($a['carrera']) ?> (<?= $a['semestre'] ?>°)</td>
                    <td><?= htmlspecialchars($a['correo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($a['creador'] ?? 'Desconocido') ?></td>
                    <td><?= $a['editor'] ? htmlspecialchars($a['editor']) . ' (' . $a['modificado_en'] . ')' : 'Sin editar' ?></td>
                    <td>
                        <a href="index.php?editar=<?= $a['id'] ?>">Editar</a> | 
                        <a href="index.php?eliminar=<?= $a['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
  </div>

</body>
</html>
