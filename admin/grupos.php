<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']);

$paginaActual = 'grupos';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar el registro de un nuevo grupo ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMateria = (int) $_POST['id_materia'];
    $idSemestre = (int) $_POST['id_semestre'];
    $idProfesor = $_POST['id_profesor'] !== '' ? (int) $_POST['id_profesor'] : null;
    $nombreGrupo = trim($_POST['nombre_grupo']);
    $capacidad = (int) $_POST['capacidad'];

    try {
        $stmt = $conexion->prepare(
            "INSERT INTO grupos (id_materia, id_semestre, id_profesor, nombre_grupo, capacidad)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idMateria, $idSemestre, $idProfesor, $nombreGrupo, $capacidad]);

        $mensaje = 'Grupo creado correctamente.';
        $tipoMensaje = 'exito';
    } catch (Exception $e) {
        $mensaje = 'No se pudo crear el grupo. Verifica que no exista ya un grupo con ese nombre para esa materia y periodo. (' . $e->getMessage() . ')';
        $tipoMensaje = 'error';
    }
}

// ---------- Datos para los selectores del formulario ----------
$materias = $conexion->query("SELECT id_materia, clave, nombre FROM materias ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);
$semestres = $conexion->query("SELECT id_semestre, nombre_semestre FROM semestres_periodos ORDER BY fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$profesores = $conexion->query(
    "SELECT p.id_profesor, u.nombre_completo
     FROM profesores p INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
     ORDER BY u.nombre_completo"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Consultar todos los grupos, con sus datos relacionados ----------
$grupos = $conexion->query(
    "SELECT g.id_grupo, g.nombre_grupo, g.capacidad,
            m.clave AS clave_materia, m.nombre AS nombre_materia,
            s.nombre_semestre,
            u.nombre_completo AS profesor,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.id_grupo = g.id_grupo) AS inscritos
     FROM grupos g
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     LEFT JOIN profesores p ON p.id_profesor = g.id_profesor
     LEFT JOIN usuarios u ON u.id_usuario = p.id_usuario
     ORDER BY s.fecha_inicio DESC, m.clave"
)->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Grupos';
$modalId = 'modalNuevo';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Grupos</h1><p>Un grupo es una materia impartida por un profesor durante un periodo. Los alumnos se inscriben a un grupo.</p></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="buscador"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar grupo..." data-filtro="#tablaGrupos"></div>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-lg me-1"></i>Nuevo grupo</button>
    </div>
</div>
<div class="tarjeta"><div class="tabla-wrap">
    <table class="tabla" id="tablaGrupos">
        <thead><tr><th>Materia</th><th>Grupo</th><th>Periodo</th><th>Profesor</th><th style="min-width:150px">Cupo</th></tr></thead>
        <tbody>
        <?php foreach ($grupos as $g): $pct = $g['capacidad'] > 0 ? min(100, round($g['inscritos'] / $g['capacidad'] * 100)) : 0; ?>
            <tr><td class="fw-semibold"><?= h($g['clave_materia'] . ' · ' . $g['nombre_materia']) ?></td>
                <td><span class="chip"><?= h($g['nombre_grupo']) ?></span></td>
                <td><?= h($g['nombre_semestre']) ?></td>
                <td><?= h($g['profesor'] ?? 'Sin asignar') ?></td>
                <td><div class="small text-muted mb-1"><?= (int) $g['inscritos'] ?> / <?= (int) $g['capacidad'] ?></div>
                    <div class="cupo"><span style="--w:<?= $pct ?>%"></span></div></td></tr>
        <?php endforeach; ?>
        <?php if (empty($grupos)): ?><tr><td colspan="5" class="vacio"><i class="bi bi-collection"></i>Aún no hay grupos creados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content form-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-collection me-2"></i>Crear nuevo grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Materia</label>
                    <select class="form-select" name="id_materia" required>
                        <option value="">-- Selecciona --</option>
                        <?php foreach ($materias as $m): ?><option value="<?= $m['id_materia'] ?>"><?= h($m['clave'] . ' - ' . $m['nombre']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Periodo</label>
                    <select class="form-select" name="id_semestre" required>
                        <option value="">-- Selecciona --</option>
                        <?php foreach ($semestres as $s): ?><option value="<?= $s['id_semestre'] ?>"><?= h($s['nombre_semestre']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Profesor</label>
                    <select class="form-select" name="id_profesor">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($profesores as $p): ?><option value="<?= $p['id_profesor'] ?>"><?= h($p['nombre_completo']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Nombre del grupo</label>
                    <input type="text" class="form-control" name="nombre_grupo" value="<?= old('nombre_grupo') ?>" placeholder="Ej. 001" required></div>
                <div class="col-md-6"><label class="form-label">Capacidad</label>
                    <input type="number" class="form-control" name="capacidad" min="1" value="30" required></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Crear grupo</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
