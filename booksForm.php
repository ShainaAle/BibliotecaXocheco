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
    // Scape in the text inputs to prevent conflicts
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $isbn = mysqli_real_escape_string($conn, trim($_POST['isbn']));
    $synopsis = mysqli_real_escape_string($conn, trim($_POST['synopsis']));

    //IDs must be integers, so we can cast them directly
    $id_publisher = (int)$_POST['publisher'];
    $id_genre = (int)$_POST['genre']; // Corregido a id_genre

    // Atrapamos el array de autores
    $autores_seleccionados = isset($_POST['author']) ? $_POST['author'] : [];

    // Verify that ISBN and Authors are not empty
    if (empty($title) || empty($isbn) || empty($autores_seleccionados)) {
        $alert_message = "<div class='alert alert-danger mt-3'>El título, ISBN y al menos un autor son obligatorios.</div>";
    } else {
        // Check if ISBN already exists
        $isbn_check_query = "SELECT id_book FROM books WHERE isbn = '$isbn'";
        $isbn_check_result = mysqli_query($conn, $isbn_check_query);

        if (mysqli_num_rows($isbn_check_result) > 0) {
            $alert_message = "<div class='alert alert-danger mt-3'>El ISBN ya existe en la base de datos. Por favor, ingresa un ISBN único.</div>";
        } else {
            // Insert the new book into the database (Corregido id_gender a id_genre)
            $query_books = "INSERT INTO books (title, isbn, id_publisher, id_genre, synopsis) 
                            VALUES ('$title', '$isbn', $id_publisher, $id_genre, '$synopsis')";

            if (mysqli_query($conn, $query_books)) {
                $new_book_id = mysqli_insert_id($conn); // Get the ID of the newly inserted book

                // Insert into book_authors table to link the book with its authors (Ciclo foreach)
                foreach ($autores_seleccionados as $id_author) {
                    $id_author_clean = (int)$id_author;
                    $query_book_authors = "INSERT INTO book_authors (id_book, id_author) 
                                           VALUES ($new_book_id, $id_author_clean)";
                    mysqli_query($conn, $query_book_authors);
                }
                
                $alert_message = "<div class='alert alert-success mt-3'>¡Libro registrado exitosamente con todos sus autores!</div>";
            } else {
                $alert_message = "<div class='alert alert-danger mt-3'>Error al guardar el libro: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

// Querys to populate dropdowns
$result_autors = mysqli_query($conn, "SELECT id_author, full_name FROM authors ORDER BY full_name ASC") or die(mysqli_error($conn));
$result_publishers = mysqli_query($conn, "SELECT id_publisher, name FROM publishers ORDER BY name ASC") or die(mysqli_error($conn));
$result_genres = mysqli_query($conn, "SELECT id_genre, name FROM genres ORDER BY name ASC") or die(mysqli_error($conn));
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de préstamos de la Biblioteca Xocheco.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link href="src\styles\styleIndex.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <link href="src/styles/sign-in.css" rel="stylesheet">
    <title>Nuevo Libro</title>
</head>

<body>
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
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'bibliotecario' || $_SESSION['rol'] === 'Bibliotecario')) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="UsersView.html">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="autorsAndEditorials.html">Autores y Editoriales</a>
                    </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="w-100 m-auto mt-5" style="max-width: 800px; padding: 15px;">
        <form action="" method="POST">
            <div style="text-align: center;">
                <a href="index.php">
                    <img class="mb-4" src="src\Images\Logo.png" alt="" width="72" height="57">
                </a>
                <h1 class="h3 mb-3 fw-normal">Agrega un nuevo libro</h1>
                <p class="text-muted">Inserta los datos del nuevo libro</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="row g-3 mt-2">
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="title" name="title" required placeholder="Título">
                    <label for="title" class="ms-2">Título del libro</label>
                </div>
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="isbn" name="isbn" required placeholder="ISBN">
                    <label for="isbn" class="ms-2">ISBN</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="author" class="form-label text-muted ms-1 mb-1 pb-2">Autor(es) - Deja presionado Ctrl para varios</label>
                    <select class="form-select" id="author" name="author[]" multiple required style="height: 100px;">
                        <?php while($autor = mysqli_fetch_assoc($result_autors)) { ?>
                            <option value="<?php echo $autor['id_author']; ?>"><?php echo $autor['full_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6 form-floating mt-4">
                    <select class="form-select" id="publisher" name="publisher" required>
                        <option value="" disabled selected>Selecciona...</option>
                        <?php while($editorial = mysqli_fetch_assoc($result_publishers)) { ?>
                            <option value="<?php echo $editorial['id_publisher']; ?>"><?php echo $editorial['name']; ?></option>
                        <?php } ?>
                    </select>
                    <label for="publisher">Editorial</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-6 form-floating">
                    <select class="form-select" id="genre" name="genre" required>
                        <option value="" disabled selected>Selecciona...</option>
                        <?php while($genero = mysqli_fetch_assoc($result_genres)) { ?>
                            <option value="<?php echo $genero['id_genre']; ?>"><?php echo $genero['name']; ?></option>
                        <?php } ?>
                    </select>
                    <label for="genre" class="ms-2">Género</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-12 form-floating">
                    <textarea class="form-control" id="synopsis" name="synopsis" placeholder="Sinopsis" style="height: 100px"></textarea>
                    <label for="synopsis" class="ms-2">Sinopsis / Descripción</label>
                </div>
            </div>

            <button class="btn btn-success w-100 py-2 mt-4" type="submit">Guardar Libro</button>
            <a href="books.php" class="btn btn-danger w-100 py-2 mt-2">Cancelar y volver al catálogo</a>
        </form>
    </main>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="small mb-0">&copy; 2026 Xocheco Biblioteca. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>