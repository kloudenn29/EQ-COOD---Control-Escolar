<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
verificarSesion(['admin']);

$paginaActual = 'materias';
$mensaje = '';
$tipoMensaje = '';

// ---------- Procesar el registro de una nueva materia ----------
// Nota: la materia ya NO guarda profesor ni semestre directo;
// eso ahora se define al crear un "grupo" (ver grupos.php).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = trim($_POST['clave']);
    $nombre = trim($_POST['nombre']);
    $creditos = (int) $_POST['creditos'];

    try {
        $stmt = $conexion->prepare(
            "INSERT INTO materias (clave, nombre, creditos) VALUES (?, ?, ?)"
        );
        $stmt->execute([$clave, $nombre, $creditos]);

        $mensaje = 'Materia registrada correctamente.';
        $tipoMensaje = 'exito';
    } catch (Exception $e) {
        $mensaje = 'No se pudo registrar la materia. Verifica que la clave no exista ya. (' . $e->getMessage() . ')';
        $tipoMensaje = 'error';
    }
}

// ---------- Consultar todas las materias para la tabla ----------
$materias = $conexion->query(
    "SELECT id_materia, clave, nombre, creditos FROM materias ORDER BY clave"
)->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Materias';
$modalId = 'modalNuevo';
include '../includes/layout_inicio.php';
?>
<div class="pagina-head">
    <div><h1>Materias</h1><p>Aquí solo se define la materia. Para asignarle profesor, periodo y cupo, crea un <a href="grupos.php">grupo</a>.</p></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="buscador"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar materia..." data-filtro="#tablaMaterias"></div>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-lg me-1"></i>Nueva materia</button>
    </div>
</div>
<div class="tarjeta"><div class="tabla-wrap">
    <table class="tabla" id="tablaMaterias">
        <thead><tr><th>Clave</th><th>Nombre</th><th>Créditos</th></tr></thead>
        <tbody>
        <?php foreach ($materias as $m): ?>
            <tr><td><span class="chip"><?= h($m['clave']) ?></span></td>
                <td class="fw-semibold"><?= h($m['nombre']) ?></td><td><?= h($m['creditos']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($materias)): ?><tr><td colspan="3" class="vacio"><i class="bi bi-journal-bookmark"></i>Aún no hay materias registradas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content form-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Registrar nueva materia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Clave</label>
                    <input type="text" class="form-control" name="clave" value="<?= old('clave') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Nombre de la materia</label>
                    <input type="text" class="form-control" name="nombre" value="<?= old('nombre') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Créditos</label>
                    <input type="number" class="form-control" name="creditos" value="<?= old('creditos') ?>" min="0" max="20" required></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Guardar materia</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/layout_fin.php'; ?>
