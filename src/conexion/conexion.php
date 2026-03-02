<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "mydb"; // nombre exacto de la base

$conn = new mysqli($host, $user, $password, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Opcional: establecer charset
$conn->set_charset("utf8mb4");
?>