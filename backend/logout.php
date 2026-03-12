<?php
session_start(); // Iniciar para poder destruir

// 1. Destruir todas las variables de sesión
$_SESSION = array();

// 2. Borrar la cookie de sesión (si existe)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión completamente
session_destroy();

// 4. Redirigir al usuario al login o al index
header("Location: signin.php");
exit();
?>