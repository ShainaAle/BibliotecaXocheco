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

// Queries to fill the tables with data from the database
$authors_query = "SELECT id_author, full_name FROM authors";
$publishers_query = "SELECT id_publisher, name FROM publishers";

$authors_result = mysqli_query($conn, $authors_query);
$publishers_result = mysqli_query($conn, $publishers_query);
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

    <script>
        function toggleCheckboxes(source, className) {
            checkboxes = document.getElementsByClassName(className);
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>

    <title>Autores y Editoriales</title>
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
    <h1 class="mb-4">Autores</h1>
    <form action="backend\authors-process.php" method="POST">
        <div class="mb-3">
            <a href="author-form.php" class="btn btn-sm btn-success me-2">Nuevo Autor</a>
            <button type="submit" name="action" value="modify" class="btn btn-sm btn-warning me-2">Modificar Seleccionados</button>
            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar los autores seleccionados?');">Eliminar Seleccionados</button>
        </div>
        <div class="table-responsive mb-5">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" onClick="toggleCheckboxes(this, 'authorCheckbox')"></th>
                        <th>ID</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($author = mysqli_fetch_assoc($authors_result)) { ?>
                    <tr>
                        <td><input type="checkbox" class="authorCheckbox" name="ids[]" value="<?php echo $author['id_author']; ?>"></td>
                        <td><?php echo $author['id_author']; ?></td>
                        <td><?php echo $author['full_name']; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </form>

    <h1 class="mb-4">Editoriales</h1>
    <form action="backend/publishers-process.php" method="POST">
        <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Bibliotecario') { ?>
        <div class="mb-3">
            <a href="publisher-form.php" class="btn btn-sm btn-success me-2">Nueva Editorial</a>
            <button type="submit" name="action" value="modify" class="btn btn-sm btn-warning me-2">Modificar Seleccionadas</button>
            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar las editoriales seleccionadas?');">Eliminar Seleccionadas</button>
        </div>
        <?php } ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" onClick="toggleCheckboxes(this, 'publisherCheckbox')"></th>
                        <th>ID</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($publisher = mysqli_fetch_assoc($publishers_result)) { ?>
                    <tr>
                        <td><input type="checkbox" class="publisherCheckbox" name="ids[]" value="<?php echo $publisher['id_publisher']; ?>"></td>
                        <td><?php echo $publisher['id_publisher']; ?></td>
                        <td><?php echo $publisher['name']; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </form>

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