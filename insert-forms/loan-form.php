<?php
session_start();
//Verify if user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

//Verify if user has permission to access this page, if not, redirect to index.php
$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'admin' && $rol !== 'bibliotecario' && $rol !== 'Administrador' && $rol !== 'Bibliotecario') {
    header("Location: index.php");
    exit();
}

include('../src/conexion/conexion.php');

$alert_message = "";

//Insert processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_copy = $_POST['copy'];
    $id_user = $_POST['user'];
    // Verify if reservation ID is provided, if not, set it to null
    $id_booking = !empty($_POST['reservation']) && trim($_POST['reservation']) !== '' ? (int)$_POST['reservation'] : 'NULL';
    $start_date = date('Y-m-d');

    // The return date is calculated by the 'trg_before_loan_insert' trigger in the database
    $insert_query = "INSERT INTO loans (id_user, id_booking, id_copy, start_date, return_deadline, status)
        VALUES ($id_user, $id_booking, $id_copy, '$start_date' , '$start_date', 'Activo')";
    
    if (mysqli_query($conn, $insert_query)) {
        $alert_message = "<div class='alert alert-success mt-3'>¡Préstamo registrado exitosamente! La base de datos calculó la fecha de devolución automáticamente.</div>";
    } else {
        $alert_message = "<div class='alert alert-danger mt-3'>Error al registrar el préstamo: " . mysqli_error($conn) . "</div>";
    }
}

// Querys to populate dropdowns
// Get ONLY available copies
$query_copies = "SELECT c.id_copy, b.title, c.edition, c.code
    FROM copies c 
    INNER JOIN books b ON c.id_book = b.id_book
    WHERE c.status = 'Disponible'
    ORDER BY b.title ASC";

$result_copies = mysqli_query($conn, $query_copies) or die("Error SQL in Copies: " . mysqli_error($conn));

// Get ONLY active users
$query_users = "SELECT id_user, name, last_name, email
    FROM users 
    WHERE active = 1 AND (id_role = (SELECT id_role FROM roles WHERE name = 'Usuario'))
    ORDER BY name ASC";

$result_users = mysqli_query($conn, $query_users) or die("Error SQL in Users: " . mysqli_error($conn));
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

    <link rel="icon" type="image/png" href="src/Images/Icon-Simp.png">

    <link href="../src/styles/styleIndex.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <link href="../src/styles/sign-in.css" rel="stylesheet">
    <title>Nuevo Préstamo | Xocheco</title>
</head>

<body>
    <!-- Nav Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a href="index.php" class="logo">
                <img src="src/Images/Icon-Simp.png" alt="Logo" style="height: 40px;">
            </a>
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
                        <a class="nav-link" href="loans.php">Préstamos</a>
                    </li>
                    <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="authors-publishers.php">Autores y Editoriales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventary.php">Inventario</a>
                    </li>
                    <?php } ?>
                </ul>
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="form-signin w-100 m-auto">
        <form action="" method="POST">
            <div style="text-align: center;">
                <a href="../index.php">
                    <img class="mb-4" src="../src/Images/Logo.png" alt="" width="72" height="57">
                </a>
                <h1 class="h3 mb-3 fw-normal">Nuevo Préstamo</h1>
                <p class="text-muted">Selecciona el libro, el usuario y el sistema hará el resto.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class= "row g-3 mt-2">
                <div class= "col-md-12 form-floating">
                    <select class= "form-select" id="copy" name="copy" required>
                        <option value="" disabled selected>Selecciona un libro disponible</option>
                        <?php while ($copy = mysqli_fetch_assoc($result_copies)) { ?>
                            <option value="<?php echo $copy['id_copy']; ?>">
                                [ID: <?php echo $copy['id_copy']; ?>] - <?php echo $copy['title']; ?> (<?php echo $copy['edition']; ?>) - Cód: <?php echo $copy['code']; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <label for="copy">Libro a prestar</label>
                </div>

                <div class= "col-md-12 form-floating">
                    <select class= "form-select" id="user" name="user" required>
                        <option value="" disabled selected>Selecciona un usuario</option>
                        <?php while ($user = mysqli_fetch_assoc($result_users)) { ?>
                            <option value="<?php echo $user['id_user']; ?>">
                                [ID: <?php echo $user['id_user']; ?>] - <?php echo $user['name'] . ' ' . $user['last_name']; ?> (<?php echo $user['email']; ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <label for="user">Usuario que solicita el préstamo</label>
                </div>

                <div class="col-md-12 form-floating">
                    <input type="number" class="form-control" id="reservation" name="reservation" placeholder="Ej. 15">
                    <label for="reservation">ID de Reserva (opcional)</label>
                </div>
            </div>

            <button class="btn btn-success w-100 py-2" type="submit">Crear Préstamo</button>
            <a href="../loans.php" class="btn btn-danger w-100 py-2 mt-2">Cancelar y volver a Préstamos</a>
        </form>
    </main>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <div class="row">
                <!-- Columna izquierda -->
                <div class="col-md-4 mb-3">
                    <h5>Xocheco Biblioteca</h5>
                    <p class="small">Conocimiento y comunidad al alcance de todos.</p>
                </div>
                <!-- Columna central -->
                <div class="col-md-4 mb-3">
                    <h6>Enlaces útiles</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Inicio</a></li>
                        <li><a href="books.php" class="text-light text-decoration-none">Libros</a></li>
                        <li><a href="loans.php" class="text-light text-decoration-none">Préstamos</a></li>
                    </ul>
                </div>
                <!-- Columna derecha -->
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