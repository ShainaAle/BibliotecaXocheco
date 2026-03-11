<?php
session_start();
//Verify if user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit();
}

//Verify if user has permission to access this page, if not, redirect to index.php
$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'admin' && $rol !== 'bibliotecario' && $rol !== 'Administrador' && $rol !== 'Bibliotecario') {
    header("Location: index.php");
    exit();
}

include('src/conexion/conexion.php');

$alert_message = "";

//Insert processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify that name is not empty
    if (empty($publisher_name = mysqli_real_escape_string($conn, $_POST['publisher_name']))) {
        $alert_message = "<div class='alert alert-danger mt-3'>El nombre de la editorial es obligatorio.</div>";
    } else {
        // Check if publisher with the same name already exists to prevent duplicates
        $publisher_check_query = "SELECT id_publisher FROM publishers WHERE name = '$publisher_name'";
        $publisher_check_result = mysqli_query($conn, $publisher_check_query);

        if (mysqli_num_rows($publisher_check_result) > 0) {
            $alert_message = "<div class='alert alert-danger mt-3'>La editorial ya existe en la base de datos. Por favor, ingresa una editorial única.</div>";
        } else {
            // Insert the new publisher into the database
            $query_publishers = "INSERT INTO publishers (name) VALUES ('$publisher_name')";

            if (mysqli_query($conn, $query_publishers)) {
                $alert_message = "<div class='alert alert-success mt-3'>¡Editorial registrada exitosamente!</div>";
            } else {
                $alert_message = "<div class='alert alert-danger mt-3'>Error al guardar la editorial: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}
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
    <link href="src/styles/sign-in.css" rel="stylesheet">
    <title>Nueva Editorial</title>
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
                        <a class="nav-link" href="loans.php">Préstamos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="UsersView.html">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="authors-publishers.php">Autores y Editoriales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventary.php">Inventario</a>
                    </li>
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
                <a href="index.php">
                    <img class="mb-4" src="src\Images\Logo.png" alt="" width="72" height="57">
                </a>
                <h1 class="h3 mb-3 fw-normal">Nueva Editorial</h1>
                <p class="mb-3">Ingresa el nombre de la editorial que deseas agregar.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="cointainer-fluid bg-light">
                <div class="row">
                    <div class="col-md-12 form-floating">
                        <input type="text" class="form-control" id="floatingInput" name="publisher_name">
                        <label for="floatingInput">Nombre</label>
                    </div>
                </div>
            </div>
            <button class="btn btn-success w-100 py-2 mt-4" type="submit">Guardar Editorial</button>
            <a href="authors-publishers.php" class="btn btn-danger w-100 py-2 mt-2">Cancelar y volver al catálogo</a>
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