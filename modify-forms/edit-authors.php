<?php
session_start();

// Verify if the user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit();
}

// Verify privileges
$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador') {
    header("Location: authors-publishers.php");
    exit();
}

include("../src/conexion/conexion.php");

$alert_message = "";

// ==========================================
// 1. PROCESS THE UPDATE (When form is submitted)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Catch the array of authors. Format will be: $_POST['authors'][id_author] = "New Name"
    $authors_data = $_POST['authors'] ?? [];
    $has_errors = false;

    foreach ($authors_data as $id => $name) {
        $id_clean = (int)$id;
        $name_clean = mysqli_real_escape_string($conn, trim($name));

        // Only update if the name is not empty
        if (!empty($name_clean)) {
            $query_update = "UPDATE authors SET full_name = '$name_clean' WHERE id_author = $id_clean";
            
            if (!mysqli_query($conn, $query_update)) {
                $has_errors = true; // Flag if SQL fails
            }
        } else {
            $has_errors = true; // Flag if a field was submitted empty
        }
    }

    if (!$has_errors) {
        echo "<script>
                alert('Autores actualizados correctamente!'); 
                window.location.href='../authors-publishers.php';
              </script>";
        exit();
    } else {
        $alert_message = "<div class='alert alert-danger mt-3'>Hubo un error al actualizar uno o más autores, o se dejó un campo en blanco.</div>";
    }
}

// ==========================================
// 2. DISPLAY THE FORM (GET request)
// ==========================================
// Get the IDs passed via URL (e.g., ?ids=1,3,5)
$ids_get = $_GET['ids'] ?? '';

// If no IDs were passed, redirect back to the catalog
if (empty($ids_get)) {
    header("Location: authors-publishers.php");
    exit();
}

// Sanitize the IDs from the URL to prevent SQL Injection
$ids_array = array_map('intval', explode(',', $ids_get));
$ids_string = implode(',', $ids_array);

// Fetch the current data of the selected authors
$query_select = "SELECT id_author, full_name FROM authors WHERE id_author IN ($ids_string)";
$result_authors = mysqli_query($conn, $query_select);

// If no valid authors were found with those IDs, redirect back
if (mysqli_num_rows($result_authors) == 0) {
    header("Location: ../authors-publishers.php");
    exit();
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Library Management System">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="src\styles\styleIndex.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>Editar Autores | Xocheco</title>
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
                        <a class="nav-link" href="../books.php">Libros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../loans.php">Préstamos</a>
                    </li>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../UsersView.html">Usuarios</a>
                    </li>
                    <?php } ?>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'bibliotecario')) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../authors-publishers.php">Autores y Editoriales</a>
                    </li>
                    <?php } ?>
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

    <main class="w-100 m-auto mt-5" style="max-width: 600px; padding: 15px;">
        <form action="" method="POST" class="p-4 border rounded-3">
            <div style="text-align: center;">
                <h1 class="h3 mb-3 fw-normal">Actualizar Autores</h1>
                <p class="text-muted">Modifica los nombres de los autores seleccionados.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="container-fluid p-0 mt-4">
                <?php while ($author = mysqli_fetch_assoc($result_authors)) { ?>
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-2 text-end text-muted">
                            <small>ID: <?php echo $author['id_author']; ?></small>
                        </div>
                        <div class="col-md-10 form-floating">
                            <input type="text" class="form-control" 
                                   id="author_<?php echo $author['id_author']; ?>" 
                                   name="authors[<?php echo $author['id_author']; ?>]" 
                                   value="<?php echo htmlspecialchars($author['full_name']); ?>" required>
                            <label for="author_<?php echo $author['id_author']; ?>" class="ms-2">Nombre del Autor</label>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <button class="btn btn-success w-100 py-2 mt-4" type="submit">Guardar Cambios</button>
            <a href="../authors-publishers.php" class="btn btn-danger w-100 py-2 mt-2">Cancelar</a>
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