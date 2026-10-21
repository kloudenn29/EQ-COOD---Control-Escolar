<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['alumno']);

$idAlumno = $_SESSION['id_alumno'];
$paginaActual = 'inscripciones';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar la inscripción a un grupo ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idGrupo = (int) $_POST['id_grupo'];

    // Verificamos que el grupo tenga cupo disponible
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
            $mensaje = 'Te inscribiste correctamente al grupo.';
            $tipoMensaje = 'exito';
        } catch (Exception $e) {
            $mensaje = 'No se pudo completar la inscripción. Es posible que ya estés inscrito en ese grupo.';
            $tipoMensaje = 'error';
        }
    }
}

// ---------- Grupos disponibles (con cupo) en los que el alumno aún no está inscrito ----------
$gruposDisponibles = $conexion->query(
    "SELECT g.id_grupo, m.clave, m.nombre AS nombre_materia, g.nombre_grupo,
            s.nombre_semestre, g.capacidad,
            (SELECT COUNT(*) FROM inscripciones i2 WHERE i2.id_grupo = g.id_grupo) AS inscritos
     FROM grupos g
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     WHERE g.id_grupo NOT IN (
         SELECT id_grupo FROM inscripciones WHERE id_alumno = " . (int) $idAlumno . "
     )
     ORDER BY s.fecha_inicio DESC, m.clave"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Grupos en los que el alumno ya está inscrito ----------
$stmtMisGrupos = $conexion->prepare(
    "SELECT m.clave, m.nombre AS nombre_materia, g.nombre_grupo, s.nombre_semestre, i.fecha_inscripcion
     FROM inscripciones i
     INNER JOIN grupos g ON g.id_grupo = i.id_grupo
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     WHERE i.id_alumno = ?
     ORDER BY i.fecha_inscripcion DESC"
);
$stmtMisGrupos->execute([$idAlumno]);
$misGrupos = $stmtMisGrupos->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Inscripción a grupos';
$modalId = '';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Inscripción a grupos</h1><p>Elige un grupo con cupo disponible y presiona "Inscribirme".</p></div>
</div>

<div class="tarjeta">
    <h3><i class="bi bi-grid"></i>Grupos disponibles</h3>
    <div class="grid-grupos">
    <?php foreach ($gruposDisponibles as $k => $g):
        $lleno = $g['inscritos'] >= $g['capacidad'];
        $pct = $g['capacidad'] > 0 ? min(100, round($g['inscritos'] / $g['capacidad'] * 100)) : 0; ?>
        <div class="grupo-card" style="--d:<?= $k ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div><span class="chip chip-muted mb-2"><?= h($g['clave']) ?></span><h4><?= h($g['nombre_materia']) ?></h4></div>
                <span class="chip">Grupo <?= h($g['nombre_grupo']) ?></span>
            </div>
            <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= h($g['nombre_semestre']) ?></div>
            <div><div class="d-flex justify-content-between small text-muted mb-1"><span>Cupo</span><span><?= (int) $g['inscritos'] ?> / <?= (int) $g['capacidad'] ?></span></div>
                <div class="cupo"><span style="--w:<?= $pct ?>%"></span></div></div>
            <form method="POST" class="form-modal mt-auto">
                <input type="hidden" name="id_grupo" value="<?= $g['id_grupo'] ?>">
                <button type="submit" class="btn btn-accent w-100" <?= $lleno ? 'disabled' : '' ?>><?= $lleno ? 'Grupo lleno' : 'Inscribirme' ?></button>
            </form>
        </div>
    <?php endforeach; ?>
    </div>
    <?php if (empty($gruposDisponibles)): ?>
        <div class="vacio"><i class="bi bi-inbox"></i>No hay grupos disponibles para inscribirte por el momento.</div>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <h3><i class="bi bi-clipboard-check"></i>Mis grupos inscritos</h3>
    <div class="tabla-wrap"><table class="tabla">
        <thead><tr><th>Materia</th><th>Grupo</th><th>Periodo</th><th>Fecha de inscripción</th></tr></thead>
        <tbody>
        <?php foreach ($misGrupos as $m): ?>
            <tr><td class="fw-semibold"><?= h($m['clave'] . ' · ' . $m['nombre_materia']) ?></td>
                <td><span class="chip"><?= h($m['nombre_grupo']) ?></span></td>
                <td><?= h($m['nombre_semestre']) ?></td><td class="text-muted"><?= h($m['fecha_inscripcion']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($misGrupos)): ?><tr><td colspan="4" class="vacio"><i class="bi bi-clipboard"></i>Aún no te has inscrito a ningún grupo.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php include '../includes/layout_fin.php'; ?>
