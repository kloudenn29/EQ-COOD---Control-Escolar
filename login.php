<?php
define('SIN_LOGIN', true);              // esta página es la única que no exige sesión
require_once __DIR__ . '/includes/init.php';

if (usuario_actual()) {                 // si ya inició sesión, va directo al sistema
    redirigir('index.php');
}

const MAX_INTENTOS = 5;                 // intentos fallidos antes de bloquear
const SEGUNDOS_BLOQUEO = 60;

$error = '';
$usuarioEscrito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioEscrito = trim($_POST['usuario'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = 'La página expiró. Intenta de nuevo.';
    } elseif (($_SESSION['bloqueo_hasta'] ?? 0) > time()) {
        $error = 'Demasiados intentos fallidos. Espera ' . ($_SESSION['bloqueo_hasta'] - time()) . ' segundos.';
    } else {
        $st = $pdo->prepare('SELECT id_usuario, usuario, nombre, rol, password_hash FROM usuario WHERE usuario = :u AND status = 1');
        $st->execute([':u' => $usuarioEscrito]);
        $fila = $st->fetch();

        // Se verifica siempre un hash (aunque el usuario no exista) para que el tiempo de respuesta no delate si existe
        $hash = $fila['password_hash'] ?? '$2y$10$1kkVgElHOHxV7YQSVAU3g.4qVS4de5WtZ/SPf0UYmbNtwMjM25Wwa';
        if (password_verify($password, $hash) && $fila) {
            session_regenerate_id(true);           // nuevo ID de sesión: evita la fijación de sesión
            unset($_SESSION['csrf'], $_SESSION['intentos'], $_SESSION['bloqueo_hasta']);
            $_SESSION['usuario'] = [
                'id' => (int) $fila['id_usuario'], 'usuario' => $fila['usuario'],
                'nombre' => $fila['nombre'], 'rol' => $fila['rol'],
            ];
            $_SESSION['ultima_actividad'] = time();
            redirigir('index.php');
        }

        $_SESSION['intentos'] = ($_SESSION['intentos'] ?? 0) + 1;
        if ($_SESSION['intentos'] >= MAX_INTENTOS) {
            $_SESSION['bloqueo_hasta'] = time() + SEGUNDOS_BLOQUEO;
            $_SESSION['intentos'] = 0;
            $error = 'Demasiados intentos fallidos. Espera ' . SEGUNDOS_BLOQUEO . ' segundos.';
        } else {
            usleep(400000);                         // pequeño retraso que frena los ataques de fuerza bruta
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}

$aviso = '';
foreach (['salio', 'expiro', 'baja'] as $a) {
    if (isset($_GET[$a])) { $aviso = $a; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · control_escolar</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="login-body" data-aviso="<?= e($aviso) ?>">
<div class="login-wrap">
    <section class="login-brand d-none d-lg-flex">
        <div>
            <i class="bi bi-mortarboard-fill"></i>
            <h1>control_escolar</h1>
            <p>Gestión de alumnos, materias, inscripciones y kardex en un solo lugar.</p>
            <ul class="login-puntos">
                <li><i class="bi bi-shield-lock-fill"></i> Acceso protegido con sesión</li>
                <li><i class="bi bi-people-fill"></i> Alumnos y materias</li>
                <li><i class="bi bi-award-fill"></i> Historial académico</li>
            </ul>
        </div>
    </section>

    <section class="login-lado">
        <form class="login-card needs-validation" method="post" action="<?= url('login.php') ?>" novalidate id="formLogin">
            <div class="text-center mb-4">
                <span class="login-logo"><i class="bi bi-person-lock"></i></span>
                <h2 class="h4 mt-3 mb-1">Iniciar sesión</h2>
                <p class="text-muted mb-0">Ingresa tus credenciales para continuar</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i><div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="usuario" name="usuario" maxlength="30" required autofocus
                           autocomplete="username" value="<?= e($usuarioEscrito) ?>" placeholder="Usuario">
                    <div class="invalid-feedback">Escribe tu usuario.</div>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required
                           autocomplete="current-password" placeholder="Contraseña">
                    <button class="btn btn-outline-secondary" type="button" id="verPassword" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                    <div class="invalid-feedback">Escribe tu contraseña.</div>
                </div>
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit" id="btnEntrar"><i class="bi bi-box-arrow-in-right"></i> Entrar al sistema</button>
            <p class="text-center text-muted small mt-4 mb-0"><i class="bi bi-lock-fill"></i> Conexión de uso interno</p>
        </form>
    </section>
</div>
<script src="<?= url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/vendor/sweetalert2.all.min.js') ?>"></script>
<script src="<?= url('assets/js/login.js') ?>"></script>
</body>
</html>
