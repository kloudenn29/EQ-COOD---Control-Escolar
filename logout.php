<?php
require_once __DIR__ . '/includes/init.php';   // exige sesión: solo un usuario autenticado puede cerrarla

// Se cierra solo por POST con token CSRF (evita que otro sitio cierre tu sesión con un enlace)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    cerrar_sesion();
    redirigir('login.php?salio=1');
}
redirigir('index.php');
