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
$tipoMensaje = 'ok'; // 'ok' o 'error'
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
        $tipoMensaje = 'error';
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

//datos para el resumen y las iniciales
function iniciales($texto) {
    $partes = preg_split('/\s+/', trim($texto));
    $letras = mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1);
    return mb_strtoupper($letras) ?: '?';
}
$totalAlumnos  = count($alumnos);
$totalCarreras = count(array_unique(array_map(fn($a) => mb_strtolower(trim($a['carrera'])), $alumnos)));
$ultimo        = $alumnos[0] ?? null;
$nombreSesion  = $_SESSION['usuario_nombre'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Tutorías</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css?v=20261128b">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php">
            <img class="brand-logo" src="https://www.upslp.edu.mx/Ingreso/ReferenciaBancaria2O24SEDI/_img/logo.png" alt="Logotipo de la Universidad Politécnica de San Luis Potosí" width="150" height="58">
            <span class="brand-copy">
                <strong>Sistema de Tutorías</strong>
                <span>Universidad Politécnica de San Luis Potosí</span>
            </span>
        </a>
        <div class="header-user">
            <div class="user-chip">
                <span class="user-avatar" aria-hidden="true"><?= htmlspecialchars(iniciales($nombreSesion)) ?></span>
                <span class="user-text"><small>Sesión iniciada</small><strong><?= htmlspecialchars($nombreSesion) ?></strong></span>
            </div>
            <a class="logout-link" href="logout.php">Cerrar sesión</a>
        </div>
    </header>

    <main class="page-shell">
        <div class="page-intro">
            <h1>Gestión de alumnos</h1>
            <p>Registra, consulta y administra la información de tutorías.</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta <?= $tipoMensaje === 'error' ? 'alerta-error' : '' ?>" role="<?= $tipoMensaje === 'error' ? 'alert' : 'status' ?>">
                <p><?= htmlspecialchars($mensaje) ?></p>
                <button type="button" class="alerta-cerrar" aria-label="Cerrar mensaje" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat"><span>Alumnos registrados</span><strong><?= $totalAlumnos ?></strong></div>
            <div class="stat"><span>Carreras</span><strong><?= $totalCarreras ?></strong></div>
            <div class="stat"><span>Último registro</span><strong><?= $ultimo ? htmlspecialchars($ultimo['nombre'] . ' ' . $ultimo['apellido_paterno']) : '—' ?></strong></div>
        </div>

        <div class="dashboard-grid">
            <section class="card form-card <?= $editando ? 'is-editing' : '' ?>">
                <div class="card-body">
                    <h2 class="card-heading"><?= $editando ? 'Editar alumno' : 'Registrar alumno' ?></h2>
                    <p class="card-description"><?= $editando ? 'Modifica los datos y guarda los cambios.' : 'Los campos con * son obligatorios.' ?></p>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="id" value="<?= $editando['id'] ?? '' ?>">
                        <div class="form-grid">
                            <div class="field full">
                                <label for="matricula">Matrícula <span class="req">*</span></label>
                                <input id="matricula" type="text" name="matricula" required value="<?= htmlspecialchars($editando['matricula'] ?? '') ?>" placeholder="Ej. 20240001">
                            </div>
                            <div class="field full">
                                <label for="nombre">Nombre(s) <span class="req">*</span></label>
                                <input id="nombre" type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>" placeholder="Nombre(s)">
                            </div>
                            <div class="field">
                                <label for="apellido_paterno">Apellido paterno <span class="req">*</span></label>
                                <input id="apellido_paterno" type="text" name="apellido_paterno" required value="<?= htmlspecialchars($editando['apellido_paterno'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label for="apellido_materno">Apellido materno</label>
                                <input id="apellido_materno" type="text" name="apellido_materno" value="<?= htmlspecialchars($editando['apellido_materno'] ?? '') ?>">
                            </div>
                            <div class="field full">
                                <label for="carrera">Carrera <span class="req">*</span></label>
                                <input id="carrera" type="text" name="carrera" required value="<?= htmlspecialchars($editando['carrera'] ?? '') ?>" placeholder="Nombre de la carrera">
                            </div>
                            <div class="field">
                                <label for="semestre">Semestre <span class="req">*</span></label>
                                <input id="semestre" type="number" name="semestre" min="1" max="15" required value="<?= htmlspecialchars($editando['semestre'] ?? '') ?>" placeholder="1–15">
                            </div>
                            <div class="field full">
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
                    <div class="table-tools">
                        <?php if ($alumnos): ?>
                        <div class="search">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                            <input id="buscador" type="search" placeholder="Buscar por nombre, matrícula o carrera" aria-label="Buscar alumnos">
                        </div>
                        <?php endif; ?>
                        <span class="count-badge" id="contador"><?= $totalAlumnos ?> registro<?= $totalAlumnos === 1 ? '' : 's' ?></span>
                    </div>
                </div>
                <?php if ($alumnos): ?>
                    <div class="table-wrap">
                        <table id="tabla-alumnos">
                            <thead>
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Alumno</th>
                                    <th>Carrera</th>
                                    <th>Correo</th>
                                    <th>Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumnos as $a):
                                    $nombreCompleto = trim($a['nombre'] . ' ' . $a['apellido_paterno'] . ' ' . ($a['apellido_materno'] ?? ''));
                                ?>
                                <tr class="<?= ($editando && (int)$editando['id'] === (int)$a['id']) ? 'is-editing' : '' ?>">
                                    <td class="matricula"><?= htmlspecialchars($a['matricula']) ?></td>
                                    <td>
                                        <div class="student">
                                            <span class="avatar" aria-hidden="true"><?= htmlspecialchars(iniciales($a['nombre'] . ' ' . $a['apellido_paterno'])) ?></span>
                                            <span class="student-name"><?= htmlspecialchars($nombreCompleto) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($a['carrera']) ?><br><span class="semester-pill"><?= (int)$a['semestre'] ?>° semestre</span></td>
                                    <td><?= htmlspecialchars($a['correo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['creador'] ?? 'Desconocido') ?><span class="student-meta"><?= $a['editor'] ? 'Editó: ' . htmlspecialchars($a['editor']) : 'Sin editar' ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <a class="edit-link" href="index.php?editar=<?= (int)$a['id'] ?>">Editar</a>
                                            <a class="delete-link" href="index.php?eliminar=<?= (int)$a['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="empty-state no-results" id="sin-resultados">
                        <strong>Sin coincidencias</strong>
                        Prueba con otro nombre, matrícula o carrera.
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <strong>Aún no hay alumnos registrados</strong>
                        Usa el formulario para agregar el primero.
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="site-footer">Universidad Politécnica de San Luis Potosí · Sistema de Tutorías</footer>

    <script>
    // Buscador de la tabla (filtra en el navegador)
    (function () {
        var input = document.getElementById('buscador');
        if (!input) return;
        var filas = document.querySelectorAll('#tabla-alumnos tbody tr');
        var contador = document.getElementById('contador');
        var vacio = document.getElementById('sin-resultados');
        var norm = function (t) { return t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); };
        input.addEventListener('input', function () {
            var q = norm(input.value.trim()), visibles = 0;
            filas.forEach(function (f) {
                var ok = norm(f.textContent).indexOf(q) !== -1;
                f.style.display = ok ? '' : 'none';
                if (ok) visibles++;
            });
            contador.textContent = visibles + (visibles === 1 ? ' registro' : ' registros');
            vacio.classList.toggle('visible', visibles === 0);
        });
    })();
    </script>
</body>
</html>
