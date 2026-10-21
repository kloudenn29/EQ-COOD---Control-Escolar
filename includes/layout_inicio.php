<?php
/**
 * layout_inicio.php
 * ------------------------------------------------------------
 * Cabecera HTML + menú lateral compartidos por TODOS los paneles.
 * El tema visual (colores/acentos) cambia según el rol de la sesión:
 *   admin    -> verde musgo
 *   profesor -> blanco/negro formal
 *   alumno   -> gris oscuro + verde oliva
 *
 * Variables que debe definir la página ANTES de incluir este archivo:
 *   $tituloPagina (string), $paginaActual (string)
 * Opcionales: $mensaje, $tipoMensaje ('exito'|'error'), $modalId
 * ------------------------------------------------------------
 */

// Escapa texto para imprimirlo de forma segura en HTML
if (!function_exists('h')) {
    function h($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); }
}
// Devuelve lo que el usuario escribió si el formulario falló (para no perderlo)
if (!function_exists('old')) {
    function old($campo) {
        global $tipoMensaje;
        return ($tipoMensaje ?? '') === 'error' ? h($_POST[$campo] ?? '') : '';
    }
}

$rol = $_SESSION['rol'] ?? 'alumno';
$BASE = '/sistema_escolar/';
$etiquetaRol = ['admin' => 'Administrador', 'profesor' => 'Profesor', 'alumno' => 'Alumno'][$rol] ?? 'Usuario';
$iconoRol = ['admin' => 'bi-shield-check', 'profesor' => 'bi-person-badge', 'alumno' => 'bi-person'][$rol] ?? 'bi-person';

$nombre = $_SESSION['nombre_completo'] ?? 'Usuario';
$partes = preg_split('/\s+/', trim($nombre));
// Primera letra (UTF-8) de las dos primeras palabras del nombre, sin depender de mbstring
$primera = function ($t) {
    preg_match('/^./u', (string) $t, $m);
    $c = $m[0] ?? '';
    return function_exists('mb_strtoupper') ? mb_strtoupper($c) : strtoupper($c);
};
$iniciales = $primera($partes[0]) . (isset($partes[1]) ? $primera($partes[1]) : '');

// Opciones del menú por rol: [clave, texto, icono, ruta]
$menus = [
    'admin' => [
        ['dashboard', 'Inicio', 'bi-grid-1x2', 'admin/dashboard.php'],
        ['alumnos', 'Alumnos', 'bi-mortarboard', 'admin/alumnos.php'],
        ['profesores', 'Profesores', 'bi-person-workspace', 'admin/profesores.php'],
        ['materias', 'Materias', 'bi-journal-bookmark', 'admin/materias.php'],
        ['semestres', 'Semestres', 'bi-calendar3', 'admin/semestres.php'],
        ['grupos', 'Grupos', 'bi-collection', 'admin/grupos.php'],
        ['inscripciones', 'Inscripciones', 'bi-clipboard-check', 'admin/inscripciones.php'],
    ],
    'profesor' => [
        ['dashboard', 'Inicio', 'bi-grid-1x2', 'profesor/dashboard.php'],
        ['calificaciones', 'Calificaciones', 'bi-pencil-square', 'profesor/calificaciones.php'],
    ],
    'alumno' => [
        ['dashboard', 'Mi información', 'bi-person-vcard', 'alumno/dashboard.php'],
        ['inscripciones', 'Inscripción a grupos', 'bi-clipboard-check', 'alumno/inscripciones.php'],
    ],
];

$mensaje = $mensaje ?? '';
$tipoMensaje = $tipoMensaje ?? 'exito';
$modalId = $modalId ?? '';
$reabrir = ($mensaje !== '' && $tipoMensaje === 'error' && $modalId !== '') ? $modalId : '';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($tituloPagina ?? 'Panel') ?> · Sistema Escolar</title>
    <link href="<?= $BASE ?>vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $BASE ?>vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $BASE ?>css/style.css" rel="stylesheet">
</head>
<body class="app-body tema-<?= h($rol) ?>" <?= $reabrir ? 'data-reabrir="' . h($reabrir) . '"' : '' ?>>
<script>
    // Recuerda si el menú estaba colapsado (se aplica antes de pintar para evitar parpadeos)
    if (localStorage.getItem('menuColapsado') === '1') document.body.classList.add('menu-colapsado');
</script>

<!-- Cortina negra: se desvanece al entrar y cubre la pantalla al salir -->
<div class="cortina" id="cortina"><i class="bi bi-mortarboard-fill"></i></div>
<div class="overlay-menu" id="overlayMenu"></div>

<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="brand-text"><strong>Sistema Escolar</strong><small></small></div>
        </div>
        <div class="rol-chip"><i class="bi <?= $iconoRol ?>"></i><span>Panel <?= h($etiquetaRol) ?></span></div>

        <nav class="nav-app">
            <?php foreach ($menus[$rol] as $i => $op): ?>
                <a href="<?= $BASE . $op[3] ?>" title="<?= h($op[1]) ?>"
                   class="<?= ($paginaActual ?? '') === $op[0] ? 'activo' : '' ?>" style="--i:<?= $i ?>">
                    <i class="bi <?= $op[2] ?>"></i><span><?= h($op[1]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <button type="button" class="logout-btn" data-bs-toggle="modal" data-bs-target="#modalLogout" title="Cerrar sesión">
            <i class="bi bi-box-arrow-left"></i><span>Cerrar sesión</span>
        </button>
    </aside>

    <main class="contenido">
        <header class="topbar">
            <button type="button" class="btn-menu" id="btnMenu" aria-label="Menú"><i class="bi bi-layout-sidebar-inset"></i></button>
            <div class="topbar-user">
                <div class="topbar-user-text">
                    <strong><?= h($nombre) ?></strong>
                    <small><?= h($etiquetaRol) ?></small>
                </div>
                <div class="avatar"><?= h($iniciales) ?></div>
            </div>
        </header>

        <section class="pagina">
