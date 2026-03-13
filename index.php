<?php
session_start();
include("src/conexion/conexion.php");
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

        <link rel="icon" type="image/png" href="src/Images/Icon-Simp.png">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>


    <title>Biblioteca Xocheco</title>
</head>

<!-- Nav Bar -->

<body>
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
                    <img src="src/Images/nav-divider.png" class="nav-sep">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Inicio</a>
                    </li><img src="src/Images/nav-divider.png" class="nav-sep">
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Libros</a>
                    </li><img src="src/Images/nav-divider.png" class="nav-sep">
                    <li class="nav-item">
                        <a class="nav-link" href="loans.php">Préstamos</a>
                    </li><img src="src/Images/nav-divider.png" class="nav-sep">
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Usuarios</a>
                    </li><img src="src/Images/nav-divider.png" class="nav-sep">
                    <li class="nav-item">
                        <a class="nav-link" href="authors-publishers.php">Autores y Editoriales</a>
                    </li><img src="src/Images/nav-divider.png" class="nav-sep">
                    <li class="nav-item">
                        <a class="nav-link" href="inventary.php">Inventario</a>
                    </li><img src="src/Images/nav-divider.png" class="nav-sep">
                    <?php } ?>
                </ul>
                <div class="d-flex">
                <?php if (isset($_SESSION['id_user'])) { ?>
                        <span class="navbar-text me-3">
                            Hola, <?php echo isset($_SESSION['nombre_completo']) ? explode(' ', $_SESSION['nombre_completo'])[0] : 'Usuario'; ?>
                        </span>
                        <a href="logout.php" class="btn btn-outline-danger">Cerrar Sesión</a>
                    <?php } else { ?>
                        <a href="signin.php" class="btn btn-primary">Iniciar Sesión</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Carrusel -->
    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active"
                aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"
                aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"
                aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="src\Images\library1.jpg" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img src="src\Images\books1.jpg" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img src="src\Images\library2.jpg" class="d-block w-100" alt="...">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
            data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
            data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- About us -->
    <div class="cointainer-fluid bg-light">
        <h1 class="text-center mt-2">Acerca de nosotros</h1>
        <div class="row justify-content-center">
            <div class="col-md-3 mx-4 mb-4 mt-4 text-justify">
                En Biblioteca Xocheco creemos que los libros son puentes hacia nuevas ideas, emociones y mundos. Somos
                una pareja de lectores que decidió transformar su pasión por la lectura en un espacio abierto para la
                comunidad de Querétaro.
            </div>
            <div class="col-md-3 mx-4 mb-4 mt-4 text-justify">
                Nuestra misión es fomentar el amor por la lectura y proporcionar un lugar acogedor donde las personas
                puedan descubrir, compartir y disfrutar de los libros. En nuestra biblioteca, encontrarás una amplia
                variedad de géneros literarios, desde clásicos hasta contemporáneos, para satisfacer los gustos de todos
                los lectores.
            </div>
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

<script>
    document.addEventListener('mousemove', (e) => {
        document.querySelectorAll('.nav-sep').forEach(sep => {
            const rect = sep.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            const distance = Math.sqrt(
                Math.pow(e.clientX - centerX, 2) + 
                Math.pow(e.clientY - centerY, 2)
            );
            
            const maxDistance = 80; // distancia máxima en px para que aparezca
            
            if (distance < maxDistance) {
                const opacity = 0.5 - (distance / maxDistance);
                sep.style.opacity = opacity;
            } else {
                sep.style.opacity = 0;
            }
        });
    });
</script>