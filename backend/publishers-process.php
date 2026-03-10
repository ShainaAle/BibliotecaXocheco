<?php
session_start();

// Verify if the user is logged and privilages
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit();
}
$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'admin' && $rol !== 'Administrador') {
    header("Location: ../authors-publishers.php");
    exit();
}

include("../src/conexion/conexion.php");

// Recive the data from the form (array of IDs and the action to perform)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verify action to perform (delete or modify)
    $action = $_POST['action'] ?? '';
    
    // Catch the array of selected IDs, if it's empty, we will handle it later
    $ids_selected = $_POST['ids'] ?? [];

    // If the array is empty, it means the user didn't select any checkbox, we will show an alert and return to the previous page
    if (empty($ids_selected)) {
        echo "<script>
                alert('No seleccionaste ninguna editorial.'); 
                window.location.href='../authors-publishers.php';
              </script>";
        exit();
    }

    // Data sanitization
    // All IDs should be integers
    $ids_clean = array_map('intval', $ids_selected);
    
    // Join the cleaned IDs into a string separated by commas
    $ids_string = implode(',', $ids_clean); 

    if ($action === 'delete') {
        if ($rol === 'admin' || $rol === 'Administrador') {
            $query_delete = "DELETE FROM publishers WHERE id_publisher IN ($ids_string)";

            if (mysqli_query($conn, $query_delete)) {
                echo "<script>
                        alert('Editoriales eliminadas correctamente de la base de datos.'); 
                        window.location.href='../authors-publishers.php';
                      </script>";
            } else {
                if (mysqli_errno($conn) == 1451) {
                     echo "<script>
                            alert('Error: No puedes eliminar uno o más de estas editoriales porque ya tienen libros asignados en el catálogo. Elimina los libros primero.'); 
                            window.location.href='../authors-publishers.php';
                          </script>";
                } else {
                     echo "<script>
                            alert('Error al eliminar: " . mysqli_error($conn) . "'); 
                            window.location.href='../authors-publishers.php';
                          </script>";
                }
            }
        } else {
            echo "<script>
                    alert('No tienes permisos para eliminar editoriales.'); 
                    window.location.href='../authors-publishers.php';
                  </script>";
        }
    } elseif ($action === 'modify') { 
        header("Location: ../modify-forms/edit-publishers.php?ids=" . $ids_string);
        exit();
    }

} else {
    header("Location: ../authors-publishers.php");
}
?>