<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']);

$paginaActual = 'alumnos';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar el registro de un nuevo alumno ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreCompleto = trim($_POST['nombre_completo']);
    $matricula = trim($_POST['matricula']);
    $carrera = trim($_POST['carrera']);
    $usuarioLogin = trim($_POST['usuario_login']);
    $contrasena = $_POST['contrasena'];
    $correo = trim($_POST['correo']);

    try {
        // Usamos una transacción porque hay que insertar en 2 tablas
        // (usuarios y alumnos) y ambas deben tener éxito juntas.
        $conexion->beginTransaction();

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmtUsuario = $conexion->prepare(
            "INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, correo, rol)
             VALUES (?, ?, ?, ?, 'alumno')"
        );
        $stmtUsuario->execute([$usuarioLogin, $hash, $nombreCompleto, $correo]);
        $idUsuarioNuevo = $conexion->lastInsertId();

        $stmtAlumno = $conexion->prepare(
            "INSERT INTO alumnos (id_usuario, matricula, carrera)
             VALUES (?, ?, ?)"
        );
        $stmtAlumno->execute([$idUsuarioNuevo, $matricula, $carrera]);

        $conexion->commit();
        $mensaje = 'Alumno registrado correctamente.';
        $tipoMensaje = 'exito';

    } catch (Exception $e) {
        $conexion->rollBack();
        $mensaje = 'No se pudo registrar el alumno. Verifica que la matrícula y el usuario no existan ya. (' . $e->getMessage() . ')';
        $tipoMensaje = 'error';
    }
}

// ---------- Consultar todos los alumnos para la tabla ----------
$alumnos = $conexion->query(
    "SELECT a.id_alumno, u.nombre_completo, a.matricula, a.carrera, u.correo
     FROM alumnos a
     INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
     ORDER BY u.nombre_completo"
)->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Alumnos';
$modalId = 'modalNuevo';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Alumnos</h1><p>Administra los alumnos registrados y sus accesos al sistema.</p></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="buscador"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar alumno..." data-filtro="#tablaAlumnos"></div>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-lg me-1"></i>Nuevo alumno</button>
    </div>
</div>
<div class="tarjeta"><div class="tabla-wrap">
    <table class="tabla" id="tablaAlumnos">
        <thead><tr><th>Nombre</th><th>Matrícula</th><th>Carrera</th><th>Correo</th></tr></thead>
        <tbody>
        <?php foreach ($alumnos as $a): ?>
            <tr><td class="fw-semibold"><?= h($a['nombre_completo']) ?></td>
                <td><span class="chip chip-muted"><?= h($a['matricula']) ?></span></td>
                <td><?= h($a['carrera']) ?></td><td class="text-muted"><?= h($a['correo']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($alumnos)): ?><tr><td colspan="4" class="vacio"><i class="bi bi-mortarboard"></i>Aún no hay alumnos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content form-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Registrar nuevo alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" name="nombre_completo" value="<?= old('nombre_completo') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Matrícula</label>
                    <input type="text" class="form-control" name="matricula" value="<?= old('matricula') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Carrera</label>
                    <input type="text" class="form-control" name="carrera" value="<?= old('carrera') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Correo</label>
                    <input type="email" class="form-control" name="correo" value="<?= old('correo') ?>" ></div>
                <div class="col-md-6"><label class="form-label">Usuario para iniciar sesión</label>
                    <input type="text" class="form-control" name="usuario_login" value="<?= old('usuario_login') ?>" required autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" name="contrasena" required autocomplete="new-password"></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Guardar alumno</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
