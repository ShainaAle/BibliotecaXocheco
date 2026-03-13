<?php
session_start();

// Validar seguridad
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

// Verificar que realmente haya préstamos seleccionados en la sesión
if (!isset($_SESSION['edit_loan_ids']) || empty($_SESSION['edit_loan_ids'])) {
    header("Location: ../loans.php");
    exit();
}

include("../src/conexion/conexion.php");

// Preparamos los IDs para la consulta
$prestamos_seleccionados = $_SESSION['edit_loan_ids'];
$ids_limpios = array_map('intval', $prestamos_seleccionados);
$ids_string = implode(',', $ids_limpios);

// Consultamos la base de datos
$query = "SELECT * FROM loans WHERE id_loan IN ($ids_string)";
$resultado = mysqli_query($conn, $query);

if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Préstamos | Xocheco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../src/styles/styleIndex.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../src/Images/Icon-Simp.png">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h2 class="h5 mb-0">Modificar Préstamos Seleccionados</h2>
            </div>
            <div class="card-body">
                <form action="../backend/update-loans-process.php" method="POST">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Préstamo</th>
                                    <th>ID Ejemplar</th>
                                    <th>Fecha Devolución</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($prestamo = mysqli_fetch_assoc($resultado)) { 
                                    $id = $prestamo['id_loan'];
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo $id; ?>
                                            <input type="hidden" name="id_loan[]" value="<?php echo $id; ?>">
                                        </td>
                                        <td><?php echo $prestamo['id_copy']; ?></td>
                                        <td>
                                            <input type="date" class="form-control" name="return_deadline[<?php echo $id; ?>]" value="<?php echo $prestamo['return_deadline']; ?>" required>
                                        </td>
                                        <td>
                                            <select class="form-select" name="status[<?php echo $id; ?>]">
                                                <option value="Activo" <?php if($prestamo['status'] == 'Activo') echo 'selected'; ?>>Activo</option>
                                                <option value="Finalizado" <?php if($prestamo['status'] == 'Finalizado') echo 'selected'; ?>>Devuelto</option>
                                                <option value="Con adeudo" <?php if($prestamo['status'] == 'Con adeudo') echo 'selected'; ?>>Atrasado</option>
                                                <option value="Cancelado" <?php if($prestamo['status'] == 'Cancelado') echo 'selected'; ?>>Cancelado</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="../loans.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>