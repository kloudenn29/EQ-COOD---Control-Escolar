<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']);

$paginaActual = 'semestres';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar el registro de un nuevo periodo ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreSemestre = trim($_POST['nombre_semestre']);
    $fechaInicio = $_POST['fecha_inicio'];
    $fechaFin = $_POST['fecha_fin'];

    try {
        $stmt = $conexion->prepare(
            "INSERT INTO semestres_periodos (nombre_semestre, fecha_inicio, fecha_fin)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$nombreSemestre, $fechaInicio, $fechaFin]);

        $mensaje = 'Periodo registrado correctamente.';
        $tipoMensaje = 'exito';
    } catch (Exception $e) {
        $mensaje = 'No se pudo registrar el periodo. (' . $e->getMessage() . ')';
        $tipoMensaje = 'error';
    }
}

// ---------- Consultar todos los periodos para la tabla ----------
$semestres = $conexion->query(
    "SELECT id_semestre, nombre_semestre, fecha_inicio, fecha_fin
     FROM semestres_periodos ORDER BY fecha_inicio DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Semestres';
$modalId = 'modalNuevo';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Semestres / Periodos</h1><p>Cada periodo escolar (ej. "Enero-Junio 2026") se usa para abrir los grupos de cada materia.</p></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="buscador"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar periodo..." data-filtro="#tablaSemestres"></div>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-lg me-1"></i>Nuevo periodo</button>
    </div>
</div>
<div class="tarjeta"><div class="tabla-wrap">
    <table class="tabla" id="tablaSemestres">
        <thead><tr><th>Nombre</th><th>Inicio</th><th>Fin</th></tr></thead>
        <tbody>
        <?php foreach ($semestres as $s): ?>
            <tr><td class="fw-semibold"><?= h($s['nombre_semestre']) ?></td>
                <td><?= h($s['fecha_inicio']) ?></td><td><?= h($s['fecha_fin']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($semestres)): ?><tr><td colspan="3" class="vacio"><i class="bi bi-calendar3"></i>Aún no hay periodos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content form-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Registrar nuevo periodo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-12"><label class="form-label">Nombre del periodo</label>
                    <input type="text" class="form-control" name="nombre_semestre" value="<?= old('nombre_semestre') ?>" placeholder="Ej. Enero-Junio 2026" required></div>
                <div class="col-md-6"><label class="form-label">Fecha de inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" value="<?= old('fecha_inicio') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Fecha de fin</label>
                    <input type="date" class="form-control" name="fecha_fin" value="<?= old('fecha_fin') ?>" required></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Guardar periodo</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
