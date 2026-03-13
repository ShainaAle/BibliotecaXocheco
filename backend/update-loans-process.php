<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}
$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../loans.php");
    exit();
}

include("../src/conexion/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_loan']) && is_array($_POST['id_loan'])) {
        
        $ids_prestamos = $_POST['id_loan'];
        $fechas_devolucion = $_POST['return_deadline'] ?? [];
        $estados = $_POST['status'] ?? [];
        
        $hubo_errores = false;

        // 1. PREPARAMOS TODAS LAS CONSULTAS UNA SOLA VEZ (Más rápido y eficiente)
        $query_loan = "UPDATE loans SET return_deadline = ?, status = ? WHERE id_loan = ?";
        $stmt_loan = mysqli_prepare($conn, $query_loan);

        $query_multa = "UPDATE fines SET status = 'Pagado' WHERE id_loan = ? AND status = 'Pendiente'";
        $stmt_multa = mysqli_prepare($conn, $query_multa);

        $query_ejemplar = "UPDATE copies SET status = 'Disponible' WHERE id_copy = (SELECT id_copy FROM loans WHERE id_loan = ?)";
        $stmt_ejemplar = mysqli_prepare($conn, $query_ejemplar);

        // Estructura exacta que me pasaste para la tabla de devoluciones
        $query_devolucion = "INSERT INTO returns (id_loan, return_date, notes) VALUES (?, NOW(), ?)";
        $stmt_devolucion = mysqli_prepare($conn, $query_devolucion);

        // Verificamos que todas las consultas se prepararon correctamente
        if ($stmt_loan && $stmt_multa && $stmt_ejemplar && $stmt_devolucion) {
            
            foreach ($ids_prestamos as $id_loan) {
                $id = intval($id_loan);
                
                $fecha = $fechas_devolucion[$id] ?? null;
                $estado = $estados[$id] ?? null;

                if ($fecha && $estado) {
                    
                    // Actualizamos el préstamo normal
                    mysqli_stmt_bind_param($stmt_loan, "ssi", $fecha, $estado, $id);
                    if (!mysqli_stmt_execute($stmt_loan)) {
                        $hubo_errores = true;
                    }

                    // Si el estado cambia a Finalizado/Devuelto, disparamos las demás acciones
                    if ($estado === 'Finalizado' || $estado === 'Devuelto') {
                        
                        // 1. Pagar la multa
                        mysqli_stmt_bind_param($stmt_multa, "i", $id);
                        mysqli_stmt_execute($stmt_multa);

                        // 2. Liberar el libro
                        mysqli_stmt_bind_param($stmt_ejemplar, "i", $id);
                        mysqli_stmt_execute($stmt_ejemplar);
                        
                        // 3. Registrar en tu tabla 'returns' (La fecha se pone sola con NOW())
                        $nota_default = "Devolución masiva.";
                        mysqli_stmt_bind_param($stmt_devolucion, "is", $id, $nota_default);
                        mysqli_stmt_execute($stmt_devolucion);
                    }
                }
            }
            
            // Cerramos todas las conexiones
            mysqli_stmt_close($stmt_loan);
            mysqli_stmt_close($stmt_multa);
            mysqli_stmt_close($stmt_ejemplar);
            mysqli_stmt_close($stmt_devolucion);
            
        } else {
            $hubo_errores = true; // Si falló la preparación de alguna consulta
        }

        // Limpiamos la sesión
        if (isset($_SESSION['edit_loan_ids'])) {
            unset($_SESSION['edit_loan_ids']);
        }

        // Mensajes de éxito o error
        if (!$hubo_errores) {
            echo "<script>
                    alert('¡Los préstamos se actualizaron correctamente!');
                    window.location.href = '../loans.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Hubo un error al actualizar algunos préstamos. Revisa la base de datos.');
                    window.location.href = '../loans.php';
                  </script>";
        }
        exit();

    } else {
        header("Location: ../loans.php");
        exit();
    }
} else {
    header("Location: ../loans.php");
    exit();
}
?>