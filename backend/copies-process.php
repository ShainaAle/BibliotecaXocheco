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

// 2. Process the incoming form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Catch the action (delete or modify) and the selected IDs
    $action = $_POST['action'] ?? '';
    $selected_ids = $_POST['ids'] ?? [];

    // If no checkboxes were selected, return with a warning
    if (empty($selected_ids)) {
        echo "<script>
                alert('No seleccionaste ningún ejemplar.'); 
                window.location.href='../inventary.php'; // BUG CORREGIDO: Faltaba el ../ aquí
              </script>";
        exit();
    }

    // Sanitize the IDs (convert them to integers) to prevent SQL Injection
    $clean_ids = array_map('intval', $selected_ids);
    $ids_string = implode(',', $clean_ids); 

    // ==========================================
    // ACTION: DELETE
    // ==========================================
    if ($action === 'delete') {
        
        $query_delete = "DELETE FROM copies WHERE id_copy IN ($ids_string)";

        if (mysqli_query($conn, $query_delete)) {
            echo "<script>
                    alert('¡Ejemplares eliminados correctamente!'); 
                    window.location.href='../inventary.php';
                  </script>";
        } else {
            // Error 1451: Foreign Key Constraint (The copy is currently in a loan or history)
            if (mysqli_errno($conn) == 1451) {
                 echo "<script>
                        alert('Error: No puedes eliminar estos ejemplares porque están vinculados a préstamos activos o en el historial.'); 
                        window.location.href='../inventary.php';
                      </script>";
            } else {
                 echo "<script>
                        alert('Error al eliminar: " . mysqli_error($conn) . "'); 
                        window.location.href='../inventary.php';
                      </script>";
            }
        }
        exit();
    } 
    
    // ==========================================
    // ACTION: MODIFY
    // ==========================================
    elseif ($action === 'modify') {
        // Redirect to the edit form, passing the IDs via URL
        header("Location: ../modify-forms/edit-copies.php?ids=" . $ids_string);
        exit();
    }

} else {
    // Fallback if accessed directly via URL
    header("Location: ../inventary.php");
}
?>