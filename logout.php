<?php
session_start(); // Start to destroy

// 1. Destroy every session variable
$_SESSION = array();

// 2. Delete the cookie (if exist)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy();

// 4. Redirect to login/index
header("Location: signin.php");
exit();
?>