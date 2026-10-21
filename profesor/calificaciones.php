<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['profesor']);

$paginaActual = 'calificaciones';
$idProfesor = $_SESSION['id_profesor'];
$mensaje = '';
$tipoMensaje = 'exito';

// ---------- Grupos que pertenecen a este profesor ----------
$stmtGrupos = $conexion->prepare(
    "SELECT g.id_grupo, g.nombre_grupo, m.clave, m.nombre AS nombre_materia, s.nombre_semestre
     FROM grupos g
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     WHERE g.id_profesor = ?
     ORDER BY s.fecha_inicio DESC, m.clave"
);
$stmtGrupos->execute([$idProfesor]);
$gruposProfesor = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

// Grupo seleccionado (por GET, o el que venga del formulario por POST)
$idGrupoSeleccionado = (int) ($_POST['id_grupo'] ?? $_GET['id_grupo'] ?? 0);
$tipoEvaluacion = $_POST['tipo_evaluacion'] ?? $_GET['tipo_evaluacion'] ?? 'ORDINARIO';

// ---------- Guardar/actualizar calificaciones capturadas ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calificacion'])) {

    // Seguridad: confirmamos que el grupo realmente pertenezca a este profesor
    $esSuGrupo = false;
    foreach ($gruposProfesor as $g) {
        if ((int) $g['id_grupo'] === $idGrupoSeleccionado) {
            $esSuGrupo = true;
        }
    }

    if ($esSuGrupo) {
        // "ON DUPLICATE KEY UPDATE" hace un UPSERT: si ya existe una
        // calificación de ese tipo para esa inscripción, la actualiza;
        // si no existe, la inserta.
        $stmtGuardar = $conexion->prepare(
            "INSERT INTO calificaciones (id_inscripcion, calificacion_final, tipo_evaluacion)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE calificacion_final = VALUES(calificacion_final)"
        );

        foreach ($_POST['calificacion'] as $idInscripcion => $valor) {
            if ($valor !== '') { // solo guardamos si el profesor capturó un valor
                $stmtGuardar->execute([(int) $idInscripcion, (float) $valor, $tipoEvaluacion]);
            }
        }
        $mensaje = 'Calificaciones guardadas correctamente.';
    } else {
        $mensaje = 'No tienes permiso para calificar ese grupo.';
        $tipoMensaje = 'error';
    }
}

// ---------- Alumnos inscritos en el grupo elegido, con su calificación actual ----------
$inscritos = [];
if ($idGrupoSeleccionado) {
    $stmtInscritos = $conexion->prepare(
        "SELECT i.id_inscripcion, u.nombre_completo, a.matricula, c.calificacion_final
         FROM inscripciones i
         INNER JOIN alumnos a ON a.id_alumno = i.id_alumno
         INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
         LEFT JOIN calificaciones c
                ON c.id_inscripcion = i.id_inscripcion
               AND c.tipo_evaluacion = ?
         WHERE i.id_grupo = ?
         ORDER BY u.nombre_completo"
    );
    $stmtInscritos->execute([$tipoEvaluacion, $idGrupoSeleccionado]);
    $inscritos = $stmtInscritos->fetchAll(PDO::FETCH_ASSOC);
}

$tituloPagina = 'Calificaciones';
$modalId = '';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Calificaciones</h1><p>Selecciona un grupo y el tipo de evaluación para capturar las calificaciones.</p></div>
</div>

<div class="tarjeta">
    <h3><i class="bi bi-funnel"></i>Selecciona grupo y evaluación</h3>
    <form method="GET" class="row g-3">
        <div class="col-md-8"><label class="form-label">Grupo</label>
            <select name="id_grupo" class="form-select" onchange="this.form.submit()">
                <option value="">-- Selecciona --</option>
                <?php foreach ($gruposProfesor as $g): ?>
                    <option value="<?= $g['id_grupo'] ?>" <?= $g['id_grupo'] == $idGrupoSeleccionado ? 'selected' : '' ?>>
                        <?= h($g['clave'] . ' - ' . $g['nombre_materia'] . ' (Grupo ' . $g['nombre_grupo'] . ', ' . $g['nombre_semestre'] . ')') ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Tipo de evaluación</label>
            <select name="tipo_evaluacion" class="form-select" onchange="this.form.submit()">
                <option value="ORDINARIO" <?= $tipoEvaluacion === 'ORDINARIO' ? 'selected' : '' ?>>Ordinario</option>
                <option value="EXTRA" <?= $tipoEvaluacion === 'EXTRA' ? 'selected' : '' ?>>Extraordinario</option>
            </select></div>
    </form>
</div>

<?php if ($idGrupoSeleccionado): ?>
<div class="tarjeta">
    <h3><i class="bi bi-pencil-square"></i>Capturar calificaciones — <?= h($tipoEvaluacion) ?></h3>
    <form method="POST" class="form-modal">
        <input type="hidden" name="id_grupo" value="<?= $idGrupoSeleccionado ?>">
        <input type="hidden" name="tipo_evaluacion" value="<?= h($tipoEvaluacion) ?>">
        <div class="tabla-wrap"><table class="tabla">
            <thead><tr><th>Alumno</th><th>Matrícula</th><th>Calificación</th></tr></thead>
            <tbody>
            <?php foreach ($inscritos as $al): ?>
                <tr><td class="fw-semibold"><?= h($al['nombre_completo']) ?></td>
                    <td><span class="chip chip-muted"><?= h($al['matricula']) ?></span></td>
                    <td><input type="number" step="0.1" min="0" max="100" class="form-control input-calif"
                               name="calificacion[<?= $al['id_inscripcion'] ?>]" value="<?= h($al['calificacion_final'] ?? '') ?>"></td></tr>
            <?php endforeach; ?>
            <?php if (empty($inscritos)): ?><tr><td colspan="3" class="vacio"><i class="bi bi-people"></i>Aún no hay alumnos inscritos en este grupo.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <div class="mt-4"><button type="submit" class="btn btn-accent"><i class="bi bi-check2 me-1"></i>Guardar calificaciones</button></div>
    </form>
</div>
<?php endif; ?>
<?php include '../includes/layout_fin.php'; ?>
