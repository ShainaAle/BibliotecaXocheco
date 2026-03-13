<?php
session_start();

// 1. Verify user session and privileges
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

$role = $_SESSION['rol'] ?? '';

if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../users.php");
    exit();
}

// 2. Process form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';
    $selected_ids = $_POST['ids'] ?? [];

    if (empty($selected_ids)) {
        echo "<script>
                alert('No seleccionaste ningún usuario.'); 
                window.location.href='../users.php';
              </script>";
        exit();
    }

    // Sanitize IDs
    $clean_ids = array_map('intval', $selected_ids);
    $ids_string = implode(',', $clean_ids); 

    if ($action === 'modify') {
        // Redirect to the edit form
        header("Location: ../modify-forms/edit-users.php?ids=" . $ids_string);
        exit();
    } 
    /* elseif ($action === 'delete') {
        // Lógica de eliminación dura si algún día la necesitas
    }
    */
} else {
    header("Location: ../users.php");
}
?>