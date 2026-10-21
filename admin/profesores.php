<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']);

$paginaActual = 'profesores';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar el registro de un nuevo profesor ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreCompleto = trim($_POST['nombre_completo']);
    $numeroEmpleado = trim($_POST['numero_empleado']);
    $especialidad = trim($_POST['especialidad']);
    $usuarioLogin = trim($_POST['usuario_login']);
    $contrasena = $_POST['contrasena'];
    $correo = trim($_POST['correo']);

    try {
        $conexion->beginTransaction();

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmtUsuario = $conexion->prepare(
            "INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, correo, rol)
             VALUES (?, ?, ?, ?, 'profesor')"
        );
        $stmtUsuario->execute([$usuarioLogin, $hash, $nombreCompleto, $correo]);
        $idUsuarioNuevo = $conexion->lastInsertId();

        $stmtProfesor = $conexion->prepare(
            "INSERT INTO profesores (id_usuario, numero_empleado, especialidad)
             VALUES (?, ?, ?)"
        );
        $stmtProfesor->execute([$idUsuarioNuevo, $numeroEmpleado, $especialidad]);

        $conexion->commit();
        $mensaje = 'Profesor registrado correctamente.';
        $tipoMensaje = 'exito';

    } catch (Exception $e) {
        $conexion->rollBack();
        $mensaje = 'No se pudo registrar el profesor. Verifica que el número de empleado y el usuario no existan ya. (' . $e->getMessage() . ')';
        $tipoMensaje = 'error';
    }
}

// ---------- Consultar todos los profesores para la tabla ----------
$profesores = $conexion->query(
    "SELECT p.id_profesor, u.nombre_completo, p.numero_empleado, p.especialidad, u.correo
     FROM profesores p
     INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
     ORDER BY u.nombre_completo"
)->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Profesores';
$modalId = 'modalNuevo';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Profesores</h1><p>Da de alta a los profesores y crea sus credenciales de acceso.</p></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="buscador"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar profesor..." data-filtro="#tablaProfesores"></div>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-lg me-1"></i>Nuevo profesor</button>
    </div>
</div>
<div class="tarjeta"><div class="tabla-wrap">
    <table class="tabla" id="tablaProfesores">
        <thead><tr><th>Nombre</th><th>No. empleado</th><th>Especialidad</th><th>Correo</th></tr></thead>
        <tbody>
        <?php foreach ($profesores as $p): ?>
            <tr><td class="fw-semibold"><?= h($p['nombre_completo']) ?></td>
                <td><span class="chip chip-muted"><?= h($p['numero_empleado']) ?></span></td>
                <td><?= h($p['especialidad']) ?></td><td class="text-muted"><?= h($p['correo']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($profesores)): ?><tr><td colspan="4" class="vacio"><i class="bi bi-person-workspace"></i>Aún no hay profesores registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content form-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Registrar nuevo profesor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" name="nombre_completo" value="<?= old('nombre_completo') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Número de empleado</label>
                    <input type="text" class="form-control" name="numero_empleado" value="<?= old('numero_empleado') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Especialidad</label>
                    <input type="text" class="form-control" name="especialidad" value="<?= old('especialidad') ?>" ></div>
                <div class="col-md-6"><label class="form-label">Correo</label>
                    <input type="email" class="form-control" name="correo" value="<?= old('correo') ?>" ></div>
                <div class="col-md-6"><label class="form-label">Usuario para iniciar sesión</label>
                    <input type="text" class="form-control" name="usuario_login" value="<?= old('usuario_login') ?>" required autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" name="contrasena" required autocomplete="new-password"></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Guardar profesor</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
