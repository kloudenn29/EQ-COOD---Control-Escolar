<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']);

$paginaActual = 'inscripciones';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar una inscripción manual (hecha por el admin) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idAlumno = (int) $_POST['id_alumno'];
    $idGrupo = (int) $_POST['id_grupo'];

    // Verificamos que el grupo todavía tenga lugar disponible
    $stmtCupo = $conexion->prepare(
        "SELECT g.capacidad, (SELECT COUNT(*) FROM inscripciones i WHERE i.id_grupo = g.id_grupo) AS inscritos
         FROM grupos g WHERE g.id_grupo = ?"
    );
    $stmtCupo->execute([$idGrupo]);
    $cupo = $stmtCupo->fetch(PDO::FETCH_ASSOC);

    if ($cupo && $cupo['inscritos'] >= $cupo['capacidad']) {
        $mensaje = 'Ese grupo ya no tiene cupo disponible.';
        $tipoMensaje = 'error';
    } else {
        try {
            $stmt = $conexion->prepare(
                "INSERT INTO inscripciones (id_alumno, id_grupo) VALUES (?, ?)"
            );
            $stmt->execute([$idAlumno, $idGrupo]);
            $mensaje = 'Alumno inscrito correctamente.';
            $tipoMensaje = 'exito';
        } catch (Exception $e) {
            $mensaje = 'No se pudo inscribir. Es posible que el alumno ya esté inscrito en ese grupo. (' . $e->getMessage() . ')';
            $tipoMensaje = 'error';
        }
    }
}

// ---------- Datos para los selectores ----------
$alumnos = $conexion->query(
    "SELECT a.id_alumno, u.nombre_completo, a.matricula
     FROM alumnos a INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
     ORDER BY u.nombre_completo"
)->fetchAll(PDO::FETCH_ASSOC);

$grupos = $conexion->query(
    "SELECT g.id_grupo, m.clave, g.nombre_grupo, s.nombre_semestre
     FROM grupos g
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     ORDER BY s.fecha_inicio DESC, m.clave"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Listado general de inscripciones ----------
$inscripciones = $conexion->query(
    "SELECT i.id_inscripcion, u.nombre_completo, a.matricula,
            m.clave AS clave_materia, g.nombre_grupo, s.nombre_semestre,
            i.fecha_inscripcion
     FROM inscripciones i
     INNER JOIN alumnos a ON a.id_alumno = i.id_alumno
     INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
     INNER JOIN grupos g ON g.id_grupo = i.id_grupo
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     ORDER BY i.fecha_inscripcion DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Inscripciones';
$modalId = 'modalNuevo';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Inscripciones</h1><p>Inscribe manualmente a un alumno a un grupo (los alumnos también pueden inscribirse desde su panel).</p></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="buscador"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar inscripción..." data-filtro="#tablaInsc"></div>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-lg me-1"></i>Nueva inscripción</button>
    </div>
</div>
<div class="tarjeta"><div class="tabla-wrap">
    <table class="tabla" id="tablaInsc">
        <thead><tr><th>Alumno</th><th>Matrícula</th><th>Materia</th><th>Grupo</th><th>Periodo</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($inscripciones as $i): ?>
            <tr><td class="fw-semibold"><?= h($i['nombre_completo']) ?></td>
                <td><span class="chip chip-muted"><?= h($i['matricula']) ?></span></td>
                <td><?= h($i['clave_materia']) ?></td>
                <td><span class="chip"><?= h($i['nombre_grupo']) ?></span></td>
                <td><?= h($i['nombre_semestre']) ?></td>
                <td class="text-muted"><?= h($i['fecha_inscripcion']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($inscripciones)): ?><tr><td colspan="6" class="vacio"><i class="bi bi-clipboard-check"></i>Aún no hay inscripciones registradas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content form-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-plus me-2"></i>Inscribir alumno a un grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Alumno</label>
                    <select class="form-select" name="id_alumno" required>
                        <option value="">-- Selecciona --</option>
                        <?php foreach ($alumnos as $a): ?><option value="<?= $a['id_alumno'] ?>"><?= h($a['nombre_completo'] . ' (' . $a['matricula'] . ')') ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Grupo</label>
                    <select class="form-select" name="id_grupo" required>
                        <option value="">-- Selecciona --</option>
                        <?php foreach ($grupos as $g): ?><option value="<?= $g['id_grupo'] ?>"><?= h($g['clave'] . ' - Grupo ' . $g['nombre_grupo'] . ' (' . $g['nombre_semestre'] . ')') ?></option><?php endforeach; ?>
                    </select></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Inscribir</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
