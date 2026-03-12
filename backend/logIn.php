<?php
session_start();
require 'src/conexion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Preparamos la consulta para obtener usuario y nombre del rol
    // Usamos JOIN para traer el nombre del rol (Administrador, Usuario, etc.)
    $sql = "SELECT u.id_user, u.name, u.last_name, u.password, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.id_role = r.id_role 
            WHERE u.email = ? AND u.active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // 2. VERIFICACIÓN DE CONTRASEÑA
        if ($password === $usuario['password']) {
            
            // --- LOGIN EXITOSO ---
            
            // Guardamos datos en sesión
            $_SESSION['id_user'] = $usuario['id_user'];
            $_SESSION['nombre_completo'] = $usuario['name'] . ' ' . $usuario['last_name'];
            
            // 3. NORMALIZAR EL ROL
            $rol_bd = strtolower($usuario['role_name']); // Convierte a minúsculas
            
            if ($rol_bd == 'administrador') {
                $_SESSION['rol'] = 'admin'; 
            } else {
                $_SESSION['rol'] = $rol_bd; 
            }

            // 4. REDIRECCIÓN 
            header("Location: index.php"); 
            exit();

        } else {
            // Contraseña incorrecta
            header("Location: signin.php?error=1");
            exit();
        }
    } else {
        // Usuario no existe o inactivo
        header("Location: signin.php?error=1");
        exit();
    }
} else {
    // Si intentan entrar directo a login.php sin formulario
    header("Location: signin.php");
    exit();
}
?>