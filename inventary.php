<?php
session_start();
// Verify if the user is logged, if not, redirect to the login page.
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit(); 
}

include("src/conexion/conexion.php");

//Verify if user has permission to access this page, if not, redirect to index.php
$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'admin' && $rol !== 'bibliotecario' && $rol !== 'Administrador' && $rol !== 'Bibliotecario') {
    header("Location: index.php");
    exit();
}

// Queries to fill the tables with data from the database
$copy_query = "SELECT c.id_copy, b.title, 
               GROUP_CONCAT(a.full_name SEPARATOR ', ') as full_name, 
               c.year, c.edition, c.code, c.location, c.status, c.notes 
        FROM copies c
        LEFT JOIN books b ON c.id_book = b.id_book
        LEFT JOIN book_authors ba ON b.id_book = ba.id_book
        LEFT JOIN authors a ON ba.id_author = a.id_author
        GROUP BY c.id_copy";

$copy_result = mysqli_query($conn, $copy_query) or die("Error al ejecutar la consulta: " . mysqli_error($conn));

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

    <link href="src/styles/styleIndex.css" rel="stylesheet">

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

    <title>Ejemplares | Xocheco</title>
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
                        <a class="nav-link" href="inventary.php">Inventario</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prestamosView.php">Préstamos</a>
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
    <h1 class="mb-4">Ejemplares</h1>
    <form action="backend/copies-process.php" method="POST">
        <div class="mb-3">
            <a href="insert-forms/copy-form.php" class="btn btn-sm btn-success me-2">Nuevo ejemplar</a>
            <button type="submit" name="action" value="modify" class="btn btn-sm btn-warning me-2">Modificar Seleccionados</button>
            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar los ejemplares seleccionados?');">Eliminar Seleccionados</button>
        </div>
        <div class="table-responsive mb-5">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" onClick="toggleCheckboxes(this, 'authorCheckbox')"></th>
                        <th>ID</th>
                        <th>Titulo</th>
                        <th>Autor</th>
                        <th>Año</th>
                        <th>Edición</th>
                        <th>Código</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($copy = mysqli_fetch_assoc($copy_result)) { ?>
                    <tr>
                        <td><input type="checkbox" class="authorCheckbox" name="ids[]" value="<?php echo $copy['id_copy']; ?>"></td>
                        <td><?php echo $copy['id_copy']; ?></td>
                        <td><?php echo $copy['title']; ?></td>
                        <td><?php echo $copy['full_name']; ?></td>
                        <td><?php echo $copy['year']; ?></td>
                        <td><?php echo $copy['edition']; ?></td>
                        <td><?php echo $copy['code']; ?></td>
                        <td><?php echo $copy['location']; ?></td>
                        <td><?php echo $copy['status']; ?></td>
                        <td><?php echo $copy['notes']; ?></td>
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