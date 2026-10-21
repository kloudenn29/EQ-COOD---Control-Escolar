<?php
require_once __DIR__ . '/includes/init.php';

// Tarjetas de resumen (una sola consulta con subconsultas)
$r = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM alumno WHERE status = 1)      AS alumnos,
        (SELECT COUNT(*) FROM materia WHERE status = 1)     AS materias,
        (SELECT COUNT(*) FROM inscripcion WHERE status = 1) AS inscripciones,
        (SELECT COALESCE(ROUND(AVG(calificacion), 1), 0) FROM kardex WHERE status = 1) AS promedio"
)->fetch();

// Gráfica 1: alumnos activos por carrera
$porCarrera = $pdo->query(
    "SELECT c.carrera, COUNT(a.matricula) AS total
     FROM carrera c LEFT JOIN alumno a ON a.id_carrera = c.id_carrera AND a.status = 1
     GROUP BY c.id_carrera, c.carrera ORDER BY total DESC"
)->fetchAll();

// Gráfica 2: materias más inscritas
$topMaterias = $pdo->query(
    "SELECT m.nombre_materia, COUNT(*) AS total
     FROM detalle_inscripcion d INNER JOIN materia m ON m.id_materia = d.id_materia
     WHERE d.status = 1 GROUP BY m.id_materia, m.nombre_materia ORDER BY total DESC LIMIT 6"
)->fetchAll();

// Últimas inscripciones
$ultimas = $pdo->query(
    "SELECT i.id_inscripcion, i.periodo, CONCAT(a.nombre, ' ', a.apellidos) AS alumno, c.carrera,
            (SELECT COUNT(*) FROM detalle_inscripcion d WHERE d.id_inscripcion = i.id_inscripcion AND d.status = 1) AS materias
     FROM inscripcion i
     INNER JOIN alumno a ON a.matricula = i.matricula
     INNER JOIN carrera c ON c.id_carrera = a.id_carrera
     ORDER BY i.id_inscripcion DESC LIMIT 6"
)->fetchAll();

$datosGraficas = [
    'carreras' => ['etiquetas' => array_column($porCarrera, 'carrera'), 'valores' => array_map('intval', array_column($porCarrera, 'total'))],
    'materias' => ['etiquetas' => array_column($topMaterias, 'nombre_materia'), 'valores' => array_map('intval', array_column($topMaterias, 'total'))],
];

$titulo = 'Dashboard';
$subtitulo = 'Resumen general del sistema';
$activo = 'dashboard';
$js_extra = ['assets/vendor/chart.umd.js'];
$js_modulo = ['assets/js/dashboard.js'];
require __DIR__ . '/includes/layout_top.php';

$tarjetas = [
    ['Alumnos activos', $r['alumnos'], 'bi-people-fill'],
    ['Materias', $r['materias'], 'bi-journal-bookmark-fill'],
    ['Inscripciones', $r['inscripciones'], 'bi-clipboard2-check-fill'],
    ['Promedio general', $r['promedio'], 'bi-award-fill'],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$lbl, $num, $ico]): ?>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="stat-num"><?= e($num) ?></div><div class="stat-lbl"><?= e($lbl) ?></div></div>
            <i class="bi <?= $ico ?> stat-ico"></i>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5"><div class="card h-100">
        <div class="card-header">Alumnos por carrera</div>
        <div class="card-body"><canvas id="graficaCarreras" height="230"></canvas></div>
    </div></div>
    <div class="col-lg-7"><div class="card h-100">
        <div class="card-header">Materias más inscritas</div>
        <div class="card-body"><canvas id="graficaMaterias" height="150"></canvas></div>
    </div></div>
</div>

<div class="card">
    <div class="card-header">Últimas inscripciones</div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Folio</th><th>Alumno</th><th>Carrera</th><th>Periodo</th><th class="text-center">Materias</th></tr></thead>
        <tbody>
        <?php foreach ($ultimas as $u): ?>
            <tr>
                <td>#<?= (int) $u['id_inscripcion'] ?></td>
                <td><?= e($u['alumno']) ?></td>
                <td><?= e($u['carrera']) ?></td>
                <td><?= e($u['periodo']) ?></td>
                <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $u['materias'] ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<script type="application/json" id="datos-graficas"><?= json_encode($datosGraficas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
