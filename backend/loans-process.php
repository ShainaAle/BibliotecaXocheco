<?php
session_start();

// 1. Verify user session and privileges
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../index.php");
    exit();
}


include("../src/conexion/conexion.php");

if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}
$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../loans.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'modify') {
        if (!empty($_POST['loan_ids'])) {
            $_SESSION['edit_loan_ids'] = $_POST['loan_ids'];
            
            header("Location: ../modify-forms/edit-loans.php");
            exit();
        } else {
            echo "<script>
                    alert('Por favor, selecciona al menos un préstamo.');
                    window.location.href = '../loans.php';
                  </script>";
            exit();
        }
    }
}
?>