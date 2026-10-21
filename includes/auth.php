<?php
/**
 * auth.php
 * ------------------------------------------------------------
 * Controla la sesión del usuario. Se incluye al inicio de
 * cada página que requiera que el usuario haya iniciado sesión.
 *
 * Uso:
 *   require_once '../includes/auth.php';
 *   verificarSesion(['admin']);            // solo admin
 *   verificarSesion(['profesor','admin']); // profesor o admin
 * ------------------------------------------------------------
 */

session_start();

function verificarSesion(array $rolesPermitidos = []) {
    // Si no hay una sesión activa, regresa al login
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: /sistema_escolar/index.php?error=sesion');
        exit;
    }

    // Si se especificaron roles y el rol del usuario no está permitido
    if (!empty($rolesPermitidos) && !in_array($_SESSION['rol'], $rolesPermitidos, true)) {
        header('Location: /sistema_escolar/index.php');
        exit;
    }
}
