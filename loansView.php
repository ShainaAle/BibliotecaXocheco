<?php
session_start();
// Verify if the user is logged, if not, redirect to the login page.
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit(); 
}

include("src/conexion/conexion.php");

$id_current_user = $_SESSION['id_user'];
$user_role = $_SESSION['rol'] ?? 'normal_user'; // Default to 'normal_user' if 'rol' is not set

// Prepare the SQL query based on the user's role
if ($user_role === 'Administrador' || $user_role === 'Bibliotecario' || $user_role === 'admin' || $user_role === 'bibliotecario') {
    // Admins and librarians can see all loans
    $active_query = "SELECT l.id_loan, b.title, c.id_copy, CONCAT(u.name, ' ', u.last_name) as usuario, l.start_date, l.status, l.return_deadline
        FROM loans l
        INNER JOIN copies c ON l.id_copy = c.id_copy
        INNER JOIN books b ON c.id_book = b.id_book
        INNER JOIN users u ON l.id_user = u.id_user
        WHERE l.status IN ('Activo', 'Con adeudo')";

    $historical_query = "SELECT l.id_loan, b.title, c.id_copy, CONCAT(u.name, ' ', u.last_name) as usuario, l.start_date, l.return_deadline, l.status, r.return_date
        FROM loans l
        INNER JOIN copies c ON l.id_copy = c.id_copy
        INNER JOIN books b ON c.id_book = b.id_book
        INNER JOIN users u ON l.id_user = u.id_user
        INNER JOIN returns r ON l.id_loan = r.id_loan
        WHERE l.status IN ('Finalizado', 'Cancelado')";

    $fines_query = "SELECT f.id_fine, b.title, c.id_copy, CONCAT(u.name, ' ', u.last_name) as usuario, f.fine_date, f.amount, f.status, f.payment_date
        FROM fines f
        INNER JOIN loans l ON f.id_loan = l.id_loan
        INNER JOIN copies c ON l.id_copy = c.id_copy
        INNER JOIN books b ON c.id_book = b.id_book
        INNER JOIN users u ON l.id_user = u.id_user";    

} else {
    // Normal users can only see their own loans
    $active_query = "SELECT l.id_loan, b.title, c.id_copy, l.start_date, l.return_deadline, l.status 
        FROM loans l
        INNER JOIN copies c ON l.id_copy = c.id_copy
        INNER JOIN books b ON c.id_book = b.id_book
        INNER JOIN users u ON l.id_user = u.id_user
        WHERE l.status IN ('Activo', 'Con adeudo') AND l.id_user = $id_current_user";

    $historical_query = "SELECT l.id_loan, b.title, l.start_date, l.return_deadline, l.status, r.return_date
        FROM loans l
        INNER JOIN copies c ON l.id_copy = c.id_copy
        INNER JOIN books b ON c.id_book = b.id_book
        INNER JOIN users u ON l.id_user = u.id_user
        INNER JOIN returns r ON l.id_loan = r.id_loan
        WHERE l.status IN ('Finalizado', 'Cancelado') AND l.id_user = $id_current_user";

    $fines_query = "SELECT f.id_fine, b.title, c.id_copy, f.fine_date, f.amount, f.status, f.payment_date
        FROM fines f
        INNER JOIN loans l ON f.id_loan = l.id_loan
        INNER JOIN copies c ON l.id_copy = c.id_copy
        INNER JOIN books b ON c.id_book = b.id_book
        INNER JOIN users u ON l.id_user = u.id_user
        WHERE l.id_user = $id_current_user";  
}

$active_result = mysqli_query($conn, $active_query);
$historical_result = mysqli_query($conn, $historical_query);
$fines_result = mysqli_query($conn, $fines_query);
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de préstamos de la Biblioteca Xocheco.">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link href="src\styles\styleIndex.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>


    <title>Préstamos</title>
</head>

<body>
    <!-- Nav Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Xocheco</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Libros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prestamosView.php">Préstamos</a>
                    </li>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="UsersView.html">Usuarios</a>
                    </li>
                    <?php } ?>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="autorsAndEditorials.html">Autores y Editoriales</a>
                    </li>
                    <?php } ?>
                </ul>
                <div>
                    <div class="d-flex">
                        <?php if (isset($_SESSION['id_user'])) { ?>
                            <span class="navbar-text me-3">
                                Hola, <?php echo isset($_SESSION['nombre_completo']) ? explode(' ', $_SESSION['nombre_completo'])[0] : 'Usuario'; ?>
                            </span>
                            <a href="logout.php" class="btn btn-sm btn-outline-secondary">Cerrar sesión</a>
                        <?php } else { ?>
                            <a href="signin.php" class="btn btn-sm btn-outline-primary">Iniciar sesión</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
        <div class="row">
            <div class="col-md-2 p-2 md-2">
                <a href="loanForm.php" class="btn btn-sm btn-success" id="btnAgregar">Nuevo Préstamo</a>
            </div>
            <div class="col-md-3 p-2 md-2">
                <button class="btn btn-sm btn-warning">Modificar Préstamo</button>
            </div>
        </div>
        <?php } ?>
    </div>

    <h1 class="my-4">Multas</h1>
    <div class="table-responsive mb-5">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <th><input type="checkbox" id="selectAll"></th>
                    <?php } ?>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>No. Ejemplar</th>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <th>Usuario</th>
                    <?php } ?>
                    <th>Fecha de multa</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Fecha de pago</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($fila = mysqli_fetch_assoc($fines_result)) { ?>
                <tr>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <td><input type="checkbox" class="selectItem"></td>
                    <?php } ?>
                    <td><?php echo $fila['id_fine']; ?></td>
                    <td><?php echo $fila['title']; ?></td>
                    <td><?php echo $fila['id_copy']; ?></td>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <td><?php echo $fila['usuario']; ?></td>
                    <?php } ?>
                    <td><?php echo $fila['fine_date']; ?></td>
                    <td><?php echo $fila['amount']; ?></td>
                    <td>
                        <?php 
                        $badge_class = ($fila['status'] == 'Pagada') ? 'bg-success' : 'bg-warning text-dark';
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $fila['status']; ?></span>
                    </td>
                    <td><?php echo $fila['payment_date']; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <h1 class="my-4">Préstamos activos</h1>
    <div class="table-responsive mb-5">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <th><input type="checkbox" id="selectAll"></th>
                    <?php } ?>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>No. Ejemplar</th>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <th>Usuario</th>
                    <?php } ?>
                    <th>Fecha de préstamo</th>
                    <th>Fecha de devolución</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($fila = mysqli_fetch_assoc($active_result)) { ?>
                <tr>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <td><input type="checkbox" class="selectItem"></td>
                    <?php } ?>
                    <td><?php echo $fila['id_loan']; ?></td>
                    <td><?php echo $fila['title']; ?></td>
                    <td><?php echo $fila['id_copy']; ?></td>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <td><?php echo $fila['usuario']; ?></td>
                    <?php } ?>
                    <td><?php echo $fila['start_date']; ?></td>
                    <td><?php echo $fila['return_deadline']; ?></td>
                    <td>
                        <?php 
                        $badge_class = ($fila['status'] == 'Activo') ? 'bg-success' : 'bg-warning text-dark';
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $fila['status']; ?></span>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <h1 class="mb-4">Historial de préstamos</h1>
    <div class="table-responsive mb-5">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <th><input type="checkbox" id="selectAll"></th>
                    <?php } ?>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>No. Ejemplar</th>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <th>Usuario</th>
                    <?php } ?>
                    <th>Fecha de préstamo</th>
                    <th>Fecha de devolución</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($fila = mysqli_fetch_assoc($historical_result)) { ?>
                <tr>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <td><input type="checkbox" class="selectItem"></td>
                    <?php } ?>
                    <td><?php echo $fila['id_loan']; ?></td>
                    <td><?php echo $fila['title']; ?></td>
                    <td><?php echo $fila['id_copy']; ?></td>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <td><?php echo $fila['usuario']; ?></td>
                    <?php } ?>
                    <td><?php echo $fila['start_date']; ?></td>
                    <td><?php echo $fila['return_date']; ?></td>
                    <td>
                        <?php $badge_class = ($fila['status'] == 'Finalizado') ? 'bg-success' : 'bg-warning text-dark';?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $fila['status']; ?></span>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Xocheco Biblioteca</h5>
                    <p class="small">Conocimiento y comunidad al alcance de todos.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6>Enlaces útiles</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Inicio</a></li>
                        <li><a href="books.php" class="text-light text-decoration-none">Libros</a></li>
                        <li><a href="prestamosView.php" class="text-light text-decoration-none">Préstamos</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h6>Contacto</h6>
                    <p class="small mb-1">Correo: contacto@xocheco.com</p>
                    <p class="small">Tel: +52 442 123 4567</p>
                </div>
            </div>
            <hr class="border-light">
            <p class="small mb-0">&copy; 2026 Xocheco Biblioteca. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>

</html>