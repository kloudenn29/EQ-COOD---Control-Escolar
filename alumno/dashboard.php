<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['alumno']);

$idAlumno = $_SESSION['id_alumno'];
$paginaActual = 'dashboard';

// Datos del perfil del alumno
$stmt = $conexion->prepare(
    "SELECT u.nombre_completo, u.correo, a.matricula, a.carrera
     FROM alumnos a
     INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
     WHERE a.id_alumno = ?"
);
$stmt->execute([$idAlumno]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

// Calificaciones del alumno en todos los grupos en los que está inscrito
$stmtCalif = $conexion->prepare(
    "SELECT m.clave, m.nombre, g.nombre_grupo, s.nombre_semestre,
            c.calificacion_final, c.tipo_evaluacion
     FROM inscripciones i
     INNER JOIN grupos g ON g.id_grupo = i.id_grupo
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     LEFT JOIN calificaciones c ON c.id_inscripcion = i.id_inscripcion
     WHERE i.id_alumno = ?
     ORDER BY s.fecha_inicio DESC, m.nombre"
);
$stmtCalif->execute([$idAlumno]);
$calificaciones = $stmtCalif->fetchAll(PDO::FETCH_ASSOC);

// Resumen para las tarjetas de estadísticas
$totalGrupos = count(array_unique(array_map(fn($c) => $c['clave'] . $c['nombre_grupo'] . $c['nombre_semestre'], $calificaciones)));
$notas = array_filter(array_column($calificaciones, 'calificacion_final'), fn($n) => $n !== null);
$promedio = count($notas) ? array_sum($notas) / count($notas) : 0;

$tituloPagina = 'Mi información';
$modalId = '';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Hola, <?= h(explode(' ', $perfil['nombre_completo'])[0]) ?></h1><p>Este es tu resumen académico.</p></div>
    <a href="inscripciones.php" class="btn btn-accent"><i class="bi bi-clipboard-plus me-1"></i>Inscribirme a un grupo</a>
</div>

<div class="tarjeta">
    <div class="perfil">
        <div class="avatar-lg"><?= h($iniciales) ?></div>
        <dl>
            <div><dt>Nombre</dt><dd><?= h($perfil['nombre_completo']) ?></dd></div>
            <div><dt>Matrícula</dt><dd><?= h($perfil['matricula']) ?></dd></div>
            <div><dt>Carrera</dt><dd><?= h($perfil['carrera']) ?></dd></div>
            <div><dt>Correo</dt><dd><?= h($perfil['correo']) ?></dd></div>
        </dl>
    </div>
</div>

<div class="grid-stats">
    <div class="stat" style="--d:0"><div class="stat-icon"><i class="bi bi-collection"></i></div><div><span class="stat-num" data-count="<?= $totalGrupos ?>">0</span><span class="stat-label">Grupos inscritos</span></div></div>
    <div class="stat" style="--d:1"><div class="stat-icon"><i class="bi bi-graph-up"></i></div><div><span class="stat-num" data-count="<?= round($promedio, 1) ?>" data-dec="1">0</span><span class="stat-label">Promedio general</span></div></div>
</div>

<div class="tarjeta">
    <h3><i class="bi bi-award"></i>Mis calificaciones</h3>
    <div class="tabla-wrap"><table class="tabla">
        <thead><tr><th>Materia</th><th>Grupo</th><th>Periodo</th><th>Evaluación</th><th>Calificación</th></tr></thead>
        <tbody>
        <?php foreach ($calificaciones as $c): ?>
            <tr><td class="fw-semibold"><?= h($c['clave'] . ' · ' . $c['nombre']) ?></td>
                <td><span class="chip chip-muted"><?= h($c['nombre_grupo']) ?></span></td>
                <td><?= h($c['nombre_semestre']) ?></td>
                <td><?= h($c['tipo_evaluacion'] ?? '—') ?></td>
                <td><?php if ($c['calificacion_final'] === null): ?><span class="chip chip-muted">Sin calificar</span>
                    <?php elseif ($c['calificacion_final'] >= 70): ?><span class="chip"><i class="bi bi-check2"></i><?= h($c['calificacion_final']) ?></span>
                    <?php else: ?><span class="chip chip-fail"><?= h($c['calificacion_final']) ?></span><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($calificaciones)): ?><tr><td colspan="5" class="vacio"><i class="bi bi-award"></i>Aún no tienes calificaciones registradas.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php include '../includes/layout_fin.php'; ?>
