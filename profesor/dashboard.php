<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['profesor']);

$paginaActual = 'dashboard';

// Datos del perfil del profesor
$stmt = $conexion->prepare(
    "SELECT u.nombre_completo, u.correo, p.numero_empleado, p.especialidad
     FROM profesores p
     INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
     WHERE p.id_profesor = ?"
);
$stmt->execute([$_SESSION['id_profesor']]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

// Grupos que imparte este profesor
$stmtMaterias = $conexion->prepare(
    "SELECT m.clave, m.nombre, g.nombre_grupo, s.nombre_semestre,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.id_grupo = g.id_grupo) AS inscritos
     FROM grupos g
     INNER JOIN materias m ON m.id_materia = g.id_materia
     INNER JOIN semestres_periodos s ON s.id_semestre = g.id_semestre
     WHERE g.id_profesor = ?
     ORDER BY s.fecha_inicio DESC, m.clave"
);
$stmtMaterias->execute([$_SESSION['id_profesor']]);
$materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

$totalGrupos = count($materias);
$totalAlumnos = array_sum(array_map(fn($m) => (int) $m['inscritos'], $materias));

$tituloPagina = 'Inicio';
$modalId = '';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Panel del profesor</h1><p>Consulta tus grupos y captura calificaciones.</p></div>
    <a href="calificaciones.php" class="btn btn-accent"><i class="bi bi-pencil-square me-1"></i>Capturar calificaciones</a>
</div>

<div class="tarjeta">
    <div class="perfil">
        <div class="avatar-lg"><?= h($iniciales) ?></div>
        <dl>
            <div><dt>Nombre</dt><dd><?= h($perfil['nombre_completo']) ?></dd></div>
            <div><dt>No. de empleado</dt><dd><?= h($perfil['numero_empleado']) ?></dd></div>
            <div><dt>Especialidad</dt><dd><?= h($perfil['especialidad']) ?></dd></div>
            <div><dt>Correo</dt><dd><?= h($perfil['correo']) ?></dd></div>
        </dl>
    </div>
</div>

<div class="grid-stats">
    <div class="stat" style="--d:0"><div class="stat-icon"><i class="bi bi-collection"></i></div><div><span class="stat-num" data-count="<?= $totalGrupos ?>">0</span><span class="stat-label">Grupos asignados</span></div></div>
    <div class="stat" style="--d:1"><div class="stat-icon"><i class="bi bi-people"></i></div><div><span class="stat-num" data-count="<?= $totalAlumnos ?>">0</span><span class="stat-label">Alumnos inscritos</span></div></div>
</div>

<div class="tarjeta">
    <h3><i class="bi bi-collection"></i>Grupos que imparto</h3>
    <div class="tabla-wrap"><table class="tabla">
        <thead><tr><th>Clave</th><th>Materia</th><th>Grupo</th><th>Periodo</th><th>Inscritos</th></tr></thead>
        <tbody>
        <?php foreach ($materias as $m): ?>
            <tr><td><span class="chip chip-muted"><?= h($m['clave']) ?></span></td>
                <td class="fw-semibold"><?= h($m['nombre']) ?></td>
                <td><span class="chip"><?= h($m['nombre_grupo']) ?></span></td>
                <td><?= h($m['nombre_semestre']) ?></td><td><?= (int) $m['inscritos'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($materias)): ?><tr><td colspan="5" class="vacio"><i class="bi bi-collection"></i>Aún no tienes grupos asignados.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php include '../includes/layout_fin.php'; ?>
