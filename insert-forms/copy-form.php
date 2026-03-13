<?php
session_start();

// 1. Verify if the user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

// 2. Verify privileges (Only Admins and Librarians can add copies)
$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../index.php");
    exit();
}

include('../src/conexion/conexion.php');

$alert_message = "";

// ==========================================
// 3. PROCESS THE INSERT (When form is submitted)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize texts to prevent SQL injection
    $year = mysqli_real_escape_string($conn, trim($_POST['year']));
    $edition = mysqli_real_escape_string($conn, trim($_POST['edition']));
    $code = mysqli_real_escape_string($conn, trim($_POST['code']));
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    
    // Cast Book ID to integer
    $id_book = (int)$_POST['id_book'];

    // Basic validation: Book ID and Code are mandatory
    if (empty($id_book) || empty($code)) {
        $alert_message = "<div class='alert alert-danger mt-3'>El libro y el código del ejemplar son obligatorios.</div>";
    } else {
        // Insert the new physical copy into the database
        $query_insert = "INSERT INTO copies (id_book, year, edition, code, location, status, notes) 
                         VALUES ($id_book, '$year', '$edition', '$code', '$location', '$status', '$notes')";

        if (mysqli_query($conn, $query_insert)) {
            $alert_message = "<div class='alert alert-success mt-3'>¡Ejemplar agregado exitosamente al inventario!</div>";
        } else {
            $alert_message = "<div class='alert alert-danger mt-3'>Error al guardar el ejemplar: " . mysqli_error($conn) . "</div>";
        }
    }
}

// ==========================================
// 4. FETCH DATA FOR DROPDOWNS
// ==========================================
// We need the list of books so the user can assign this copy to a specific book
$query_books = "SELECT id_book, title FROM books ORDER BY title ASC";
$result_books = mysqli_query($conn, $query_books) or die(mysqli_error($conn));
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

    <link href="../src/styles/styleIndex.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <link href="../src/styles/sign-in.css" rel="stylesheet">
    <title>Nuevo Ejemplar</title>
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
                <div>
                    <div class="d-flex">
                        <?php if (isset($_SESSION['id_user'])) { ?>
                            <span class="navbar-text me-3">
                                Hola, <?php echo isset($_SESSION['nombre_completo']) ? explode(' ', $_SESSION['nombre_completo'])[0] : 'Usuario'; ?>
                            </span>

                            <a href="../logout.php" class="btn btn-outline-danger">Cerrar Sesión</a>

                        <?php } else { ?>
                            <a href="../signin.php" class="btn btn-primary">Iniciar Sesión</a>

                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="form-signin w-100 m-auto">
        <form action="" method="POST" class="p-4 border rounded-3 bg-light shadow-sm">
            <div style="text-align: center;">
                <a href="../index.php">
                    <img class="mb-4" src="../src/Images/Logo.png" alt="" width="72" height="57">
                </a>
                <h1 class="h3 mb-3 fw-normal">Agrega un nuevo ejemplar</h1>
                <p class="text-muted">Registra una copia física para un libro existente.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="cointainer-fluid bg-light">
                <div class="row g-3 mt-2">
                <div class="col-md-12 form-floating">
                    <select class="form-select" id="id_book" name="id_book" required>
                        <option value="" disabled selected>Selecciona un libro...</option>
                        <?php while($book = mysqli_fetch_assoc($result_books)) { ?>
                            <option value="<?php echo $book['id_book']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                        <?php } ?>
                    </select>
                    <label for="id_book" class="ms-2">Libro al que pertenece</label>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6 form-floating">
                    <input type="number" class="form-control" id="year" name="year" placeholder="2023">
                    <label for="year" class="ms-2">Año de impresión</label>
                </div>
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="edition" name="edition" placeholder="1ra Edición">
                    <label for="edition" class="ms-2">Edición</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="code" name="code" required placeholder="Código interno">
                    <label for="code" class="ms-2">Código interno (Ej. BIO-001)</label>
                </div>
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="location" name="location" placeholder="Pasillo A">
                    <label for="location" class="ms-2">Ubicación física</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-12 form-floating">
                    <select class="form-select" id="status" name="status" required>
                        <option value="Disponible" selected>Disponible</option>
                        <option value="Prestado">Prestado</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Perdido">Perdido</option>
                    </select>
                    <label for="status" class="ms-2">Estado inicial</label>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-12 form-floating">
                    <textarea class="form-control" id="notes" name="notes" placeholder="Observaciones" style="height: 100px"></textarea>
                    <label for="notes" class="ms-2">Observaciones (detalles de daños, etc.)</label>
                </div>
            </div>
            
            <button class="btn btn-success w-100 py-2 mt-4" type="submit">Guardar Ejemplar</button>
            <a href="../inventary.php" class="btn btn-danger w-100 py-2 mt-2">Cancelar y volver al Inventario</a>
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