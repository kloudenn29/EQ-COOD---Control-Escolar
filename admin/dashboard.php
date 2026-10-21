<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']); // solo el administrador puede ver esta página

// Pequeños conteos para mostrar en el panel principal
$totalAlumnos = $conexion->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$totalProfesores = $conexion->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$totalMaterias = $conexion->query("SELECT COUNT(*) FROM materias")->fetchColumn();
$totalGrupos = $conexion->query("SELECT COUNT(*) FROM grupos")->fetchColumn();
$totalInscripciones = $conexion->query("SELECT COUNT(*) FROM inscripciones")->fetchColumn();

$paginaActual = 'dashboard'; // usado por menu_admin.php para resaltar el enlace activo

// Últimas 5 inscripciones para la tarjeta de actividad reciente
$recientes = $conexion->query(
    "SELECT u.nombre_completo, m.clave, g.nombre_grupo, i.fecha_inscripcion
     FROM inscripciones i
     INNER JOIN alumnos a ON a.id_alumno = i.id_alumno
     INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
     INNER JOIN grupos g ON g.id_grupo = i.id_grupo
     INNER JOIN materias m ON m.id_materia = g.id_materia
     ORDER BY i.fecha_inscripcion DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
$stats = [
    ['Alumnos', $totalAlumnos, 'bi-mortarboard'], ['Profesores', $totalProfesores, 'bi-person-workspace'],
    ['Materias', $totalMaterias, 'bi-journal-bookmark'], ['Grupos abiertos', $totalGrupos, 'bi-collection'],
    ['Inscripciones', $totalInscripciones, 'bi-clipboard-check'],
];

$tituloPagina = 'Inicio';
$modalId = '';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Bienvenido, <?= h(explode(' ', $_SESSION['nombre_completo'])[0]) ?></h1>
    <p>Resumen general del sistema escolar.</p></div>
</div>

<div class="grid-stats">
<?php foreach ($stats as $k => $s): ?>
    <div class="stat" style="--d:<?= $k ?>">
        <div class="stat-icon"><i class="bi <?= $s[2] ?>"></i></div>
        <div><span class="stat-num" data-count="<?= (int) $s[1] ?>">0</span><span class="stat-label"><?= h($s[0]) ?></span></div>
    </div>
<?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="tarjeta h-100">
            <h3><i class="bi bi-clock-history"></i>Actividad reciente</h3>
            <div class="tabla-wrap"><table class="tabla">
                <thead><tr><th>Alumno</th><th>Materia</th><th>Grupo</th><th>Fecha</th></tr></thead>
                <tbody>
                <?php foreach ($recientes as $r): ?>
                    <tr><td class="fw-semibold"><?= h($r['nombre_completo']) ?></td><td><?= h($r['clave']) ?></td>
                        <td><span class="chip"><?= h($r['nombre_grupo']) ?></span></td>
                        <td class="text-muted"><?= h($r['fecha_inscripcion']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($recientes)): ?><tr><td colspan="4" class="vacio"><i class="bi bi-inbox"></i>Todavía no hay inscripciones.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="tarjeta h-100">
            <h3><i class="bi bi-lightning-charge"></i>Accesos rápidos</h3>
            <div class="accesos">
                <a class="acceso" href="alumnos.php"><span><i class="bi bi-mortarboard me-2"></i>Alumnos</span><i class="bi bi-arrow-right"></i></a>
                <a class="acceso" href="profesores.php"><span><i class="bi bi-person-workspace me-2"></i>Profesores</span><i class="bi bi-arrow-right"></i></a>
                <a class="acceso" href="materias.php"><span><i class="bi bi-journal-bookmark me-2"></i>Materias</span><i class="bi bi-arrow-right"></i></a>
                <a class="acceso" href="grupos.php"><span><i class="bi bi-collection me-2"></i>Grupos</span><i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
