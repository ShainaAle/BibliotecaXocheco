<?php
session_start();
require 'src/conexion/conexion.php';

$sql = "SELECT b.*, 
        GROUP_CONCAT(a.full_name SEPARATOR ', ') as nombre_autor
        FROM books b
        LEFT JOIN book_authors ba ON b.id_book = ba.id_book
        LEFT JOIN authors a ON ba.id_author = a.id_author
        GROUP BY b.id_book";

$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link href="src\styles\styleIndex.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <title>Libros</title>
</head>

<body>
    <!-- Nav Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.html">Xocheco</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.html">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Libros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prestamosView.html">Préstamos</a>
                    </li>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="UsersView.html">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="autorsAndEditorials.html">Autores y Editoriales</a>
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

    <div class="container-fluid">
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
        <div class="row mt-3">
        <div class="row">
            <div class="col-md-12 p-2 md-2">
                <a href="booksForm.html" class="btn btn-sm btn-success">Agregar libro</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 p-2 md-4">
                <button class="btn btn-sm btn-danger">Eliminar libro</button>
            </div>
        </div>
        <?php } ?>
    </div>
    <!-- Book cards -->
    <div class="container mt-5">
        <div class="row">
            <?php IF ($result->num_rows > 0) {
                while ($libro = $result->fetch_assoc()) { ?>
            <div class="col-md-4 p-2"> 
                <div class="card border-1 h-100" style="width: 18rem;"> <img src="src\Images\book1.jpg" 
                             class="card-img-top" 
                             style="height: 300px; object-fit: cover;"> 

                        <div class="card-body">
                            <h5 class="card-title"><?php echo $libro['title']; ?></h5>
                            <h6 class="card-subtitle mb-3 text-muted" style="font-size: 0.9rem;">
                                <?php 
                                    // Si hay autor lo muestra, si no pone "Autor desconocido"
                                    echo !empty($libro['nombre_autor']) ? $libro['nombre_autor'] : 'Autor desconocido'; 
                                ?>
                            </h6>
                            <p class="card-text">
                                <?php echo substr($libro['synopsis'], 0, 100) . '...'; ?>
                            </p>

                            <?php 
                                // --- PEGA AQUÍ EL BLOQUE DE LÓGICA DE ARRIBA ---
                                $rutaSolicitud = "signin.html"; 
                                $textoBoton = "Solicitar";
                            
                                if (isset($_SESSION['rol'])) {
                                    if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario') {
                                        // Ajusta el nombre de tu archivo de préstamos aquí
                                        $rutaSolicitud = "prestamosView.html?id_book=" . $libro['id_book'];
                                    } else {
                                        // Ajusta el nombre de tu archivo de reservaciones aquí
                                        $rutaSolicitud = "reservacion.php?id_book=" . $libro['id_book'];
                                    }
                                } else {
                                     $textoBoton = "Inicia sesión para solicitar";
                                }
                            ?>

                            <a href="<?php echo $rutaSolicitud; ?>" class="btn btn-primary w-100 mb-2">
                                <?php echo $textoBoton; ?>
                            </a>

                            <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                            <div class="row">
                                <div class="col-6 p-1">
                                    <a href="editar_libro.php?id=<?php echo $libro['id']; ?>" class="btn btn-sm btn-warning w-100">Editar</a>
                                </div>
                                <div class="col-6 p-1">
                                    <form action="eliminar_libro.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este libro?');">
                                        <input type="hidden" name="id" value="<?php echo $libro['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger w-100">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php }
            } else {
                echo "<p>No hay libros disponibles.</p>";
            } ?>
        </div>
    </div>

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
                        <li><a href="index.html" class="text-light text-decoration-none">Inicio</a></li>
                        <li><a href="books.php" class="text-light text-decoration-none">Libros</a></li>
                        <li><a href="signin.html" class="text-light text-decoration-none">Préstamos</a></li>
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