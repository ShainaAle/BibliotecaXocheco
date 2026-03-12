<?php
session_start();

// 1. Verify user session
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit();
}

include("src/conexion/conexion.php");

$role = $_SESSION['rol'] ?? '';
$id_current_user = $_SESSION['id_user'];

// 2. Define if the current user is an admin/librarian
$is_admin = in_array($role, ['admin', 'Administrador', 'bibliotecario', 'Bibliotecario']);

// 3. Build the query dynamically
$query_bookings = "SELECT bk.id_booking, bk.booking_date, bk.status, CONCAT(u.name, ' ', u.last_name) as user, u.email, b.title, b.isbn
    FROM bookings bk
    INNER JOIN users u ON bk.id_user = u.id_user
    INNER JOIN books b ON bk.id_book = b.id_book";

// If the user is NOT an admin, filter the results to only show their own reservations
if (!$is_admin) {
    // Make sure $id_current_user is treated as an integer for security
    $id_safe = (int)$id_current_user;
    $query_bookings .= " WHERE bk.id_user = $id_safe";
}

$query_bookings .= " ORDER BY bk.booking_date DESC";

$result_bookings = mysqli_query($conn, $query_bookings) or die("Error SQL: " . mysqli_error($conn));
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservaciones | Xocheco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="src\styles\styleIndex.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleCheckboxes(source, className) {
            let checkboxes = document.getElementsByClassName(className);
            for(let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Xocheco</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="books.php">Libros</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamosView.php">Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservationsView.php">Mis Reservaciones</a></li>
                    
                    <?php if ($is_admin) { ?>
                        <li class="nav-item"><a class="nav-link" href="inventary.php">Inventario</a></li>
                        <li class="nav-item"><a class="nav-link" href="UsersView.php">Usuarios</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid mt-5 mb-5 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <?php if ($is_admin) { ?>
                    <h1 class="h3 mb-0 text-gray-800">Dashboard de Reservaciones</h1>
                    <p class="text-muted">Gestiona todas las solicitudes de libros en el sistema.</p>
                <?php } else { ?>
                    <h1 class="h3 mb-0 text-gray-800">Mis Reservaciones</h1>
                    <p class="text-muted">Revisa el estado de los libros que has solicitado.</p>
                <?php } ?>
            </div>
        </div>

        <form action="backend/bookings-process.php" method="POST" class="bg-white p-4 rounded-3 shadow-sm border">
            
            <div class="mb-3 d-flex gap-2">
                <?php if ($is_admin) { ?>
                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                        Aprobar y Convertir a Préstamo
                    </button>
                <?php } else { ?>
                    <a href="books.php" class="btn btn-primary btn-sm">Nueva Reservación</a>
                <?php } ?>
                
                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas cancelar las reservaciones seleccionadas?');">
                    Cancelar Reservación
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle border">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" onClick="toggleCheckboxes(this, 'bookingCheckbox')"></th>
                            <th>ID Reservación</th>
                            <th>Fecha de Solicitud</th>
                            <?php if ($is_admin) { ?><th>Usuario</th><?php } ?>
                            <th>Libro Solicitado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_bookings) > 0) { ?>
                            <?php while ($booking = mysqli_fetch_assoc($result_bookings)) { ?>
                                <tr>
                                    <td><input type="checkbox" class="bookingCheckbox" name="ids[]" value="<?php echo $booking['id_booking']; ?>"></td>
                                    <td><span class="badge bg-secondary">#<?php echo $booking['id_booking']; ?></span></td>
                                    <td><?php echo date('d M Y - H:i', strtotime($booking['booking_date'])); ?></td>
                                    
                                    <?php if ($is_admin) { ?>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['user']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                    </td>
                                    <?php } ?>
                                    
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['title']); ?></strong><br>
                                        <small class="text-muted">ISBN: <?php echo htmlspecialchars($booking['isbn']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            // Dynamic badge colors based on status (fixed case sensitivity)
                                            $status = strtolower(trim($booking['status']));
                                            if ($status == 'en espera') {
                                                echo '<span class="badge bg-warning text-dark">En Espera</span>';
                                            } elseif ($status == 'listo para entrega') {
                                                echo '<span class="badge bg-success">Listo para entrega</span>';
                                            } elseif ($status == 'cancelado' || $status == 'cancelada') {
                                                echo '<span class="badge bg-danger">Cancelada</span>';
                                            } else {
                                                echo '<span class="badge bg-info">'.htmlspecialchars($booking['status']).'</span>';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="<?php echo $is_admin ? '6' : '5'; ?>" class="text-center py-4 text-muted">No hay reservaciones para mostrar.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </form>
    </main>

    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container text-center">
            <p class="small mb-0">&copy; 2026 Xocheco Biblioteca. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>