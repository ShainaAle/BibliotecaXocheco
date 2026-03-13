<?php
session_start();

// Verify user privileges
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit();
}
$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'bibliotecario' && $role !== 'Administrador' && $role !== 'Bibliotecario') {
    header("Location: ../books.php");
    exit();
}

include('../src/conexion/conexion.php');

$alert_message = "";

// Get the book ID from the URL 
$id_book = isset($_GET['id_book']) ? (int)$_GET['id_book'] : 0;

if ($id_book === 0) {
    header("Location: ../books.php");
    exit();
}

// ==========================================
// PROCESS THE UPDATE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize text inputs
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $isbn = mysqli_real_escape_string($conn, trim($_POST['isbn']));
    $synopsis = mysqli_real_escape_string($conn, trim($_POST['synopsis']));

    // Cast IDs to integers
    $id_publisher = (int)$_POST['publisher'];
    $id_genre = (int)$_POST['genre'];

    // Authors array
    $selected_authors = isset($_POST['author']) ? $_POST['author'] : [];

    // Validation
    if (empty($title) || empty($isbn) || empty($selected_authors)) {
        $alert_message = "<div class='alert alert-danger mt-3'>El título, ISBN, y al menos un autor son requeridos.</div>";
    } else {
        // Update the main books table
        $query_update = "UPDATE books SET 
                            title = '$title', 
                            isbn = '$isbn', 
                            id_publisher = $id_publisher, 
                            id_genre = $id_genre, 
                            synopsis = '$synopsis' 
                         WHERE id_book = $id_book";

        if (mysqli_query($conn, $query_update)) {
            // Update the authors: 
            // The easiest way is to delete the old relations and insert the new ones
            mysqli_query($conn, "DELETE FROM book_authors WHERE id_book = $id_book");

            foreach ($selected_authors as $id_author) {
                $id_author_clean = (int)$id_author;
                $query_insert_author = "INSERT INTO book_authors (id_book, id_author) VALUES ($id_book, $id_author_clean)";
                mysqli_query($conn, $query_insert_author);
            }
            
            echo "<script>
                    alert('Book updated successfully!'); 
                    window.location.href='../books.php';
                  </script>";
            exit();
        } else {
            $alert_message = "<div class='alert alert-danger mt-3'>Error al actualizar el libro: " . mysqli_error($conn) . "</div>";
        }
    }
}

// ==========================================
// 2. FETCH CURRENT DATA TO PRE-FILL FORM
// ==========================================

// Fetch main book data
$query_book_data = "SELECT * FROM books WHERE id_book = $id_book";
$result_book_data = mysqli_query($conn, $query_book_data);

if (mysqli_num_rows($result_book_data) == 0) {
    header("Location: ../books.php");
    exit();
}
$current_book = mysqli_fetch_assoc($result_book_data);

// Fetch current authors assigned to this book and store them in a simple array
$current_authors_array = [];
$query_current_authors = "SELECT id_author FROM book_authors WHERE id_book = $id_book";
$result_current_authors = mysqli_query($conn, $query_current_authors);
while ($row = mysqli_fetch_assoc($result_current_authors)) {
    $current_authors_array[] = $row['id_author'];
}

// Querys to populate dropdowns
$result_autors = mysqli_query($conn, "SELECT id_author, full_name FROM authors ORDER BY full_name ASC");
$result_publishers = mysqli_query($conn, "SELECT id_publisher, name FROM publishers ORDER BY name ASC");
$result_genres = mysqli_query($conn, "SELECT id_genre, name FROM genres ORDER BY name ASC");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar Libro | Xocheco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../src/Images/Icon-Simp.png">
    <link href="src\styles\styleIndex.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a href="index.php" class="logo">
                <img src="../src/Images/Icon-Simp.png" alt="Logo" style="height: 40px;">
            </a>
            <a class="navbar-brand" href="../index.php">Xocheco</a>
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
                        <a class="nav-link" href="../books.php">Libros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../loans.php">Préstamos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../users.php">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../authors-publishers.php">Autores y Editoriales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../inventary.php">Inventario</a>
                    </li>
                </ul>
                <div>
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
        </div>
    </nav>

    <main class="w-100 m-auto mt-5" style="max-width: 800px; padding: 15px;">
        <form action="" method="POST" class="p-4 border rounded-3 bg-white shadow-sm">
            <div style="text-align: center;">
                <a href="index.php">
                    <img class="mb-4" src="..\src\Images\Logo.png" alt="" width="72" height="57">
                </a>
                <h1 class="h3 mb-3 fw-normal">Nuevo autor</h1>
                <p class="mb-3">Ingresa el nombre completo del autor que deseas agregar.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="row g-3 mt-2">
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($current_book['title']); ?>">
                    <label for="title" class="ms-2">Título del libro</label>
                </div>
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="isbn" name="isbn" required value="<?php echo htmlspecialchars($current_book['isbn']); ?>">
                    <label for="isbn" class="ms-2">ISBN</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="author" class="form-label text-muted ms-1 mb-1 pb-2">Autor(es) - Deja presionado Ctrl para varios</label>
                    <select class="form-select" id="author" name="author[]" multiple required style="height: 100px;">
                        <?php 
                        while($autor = mysqli_fetch_assoc($result_autors)) { 
                            // Check if this author's ID is inside the array of currently assigned authors
                            $is_selected = in_array($autor['id_author'], $current_authors_array) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $autor['id_author']; ?>" <?php echo $is_selected; ?>>
                                <?php echo $autor['full_name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6 form-floating mt-4">
                    <select class="form-select" id="publisher" name="publisher" required>
                        <option value="" disabled>Selecciona...</option>
                        <?php 
                        while($editorial = mysqli_fetch_assoc($result_publishers)) { 
                            $is_selected = ($editorial['id_publisher'] == $current_book['id_publisher']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $editorial['id_publisher']; ?>" <?php echo $is_selected; ?>>
                                <?php echo $editorial['name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <label for="publisher" class="ms-2">Editorial</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-6 form-floating">
                    <select class="form-select" id="genre" name="genre" required>
                        <option value="" disabled>Selecciona...</option>
                        <?php 
                        while($genero = mysqli_fetch_assoc($result_genres)) { 
                            $is_selected = ($genero['id_genre'] == $current_book['id_genre']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $genero['id_genre']; ?>" <?php echo $is_selected; ?>>
                                <?php echo $genero['name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <label for="genre" class="ms-2">Género</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-12 form-floating">
                    <textarea class="form-control" id="synopsis" name="synopsis" style="height: 100px"><?php echo htmlspecialchars($current_book['synopsis']); ?></textarea>
                    <label for="synopsis" class="ms-2">Sinopsis / Descripción</label>
                </div>
            </div>

            <button class="btn btn-success w-100 py-2 mt-4" type="submit">Guardar Cambios</button>
            <a href="../books.php" class="btn btn-danger w-100 py-2 mt-2">Cancelar</a>
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
                        <li><a href="../index.php" class="text-light text-decoration-none">Inicio</a></li>
                        <li><a href="../books.php" class="text-light text-decoration-none">Libros</a></li>
                        <li><a href="../loans.php" class="text-light text-decoration-none">Préstamos</a></li>
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