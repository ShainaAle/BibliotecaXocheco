<?php
session_start();

// 1. Verify user session and privileges
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}
$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../inventary.php");
    exit();
}

// 2. Include database connection (adjusting path to go up one folder)
include("../src/conexion/conexion.php");

$alert_message = "";

// ==========================================
// 3. PROCESS THE UPDATE (When form is submitted via POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Catch the array of copies. Structure: $_POST['copies'][id_copy]['status']
    $copies_data = $_POST['copies'] ?? [];
    $has_errors = false;

    // Loop through each submitted copy and execute an UPDATE query
    foreach ($copies_data as $id_copy => $data) {
        $id_clean = (int)$id_copy;
        
        // Sanitize the inputs to prevent SQL Injection
        $location_clean = mysqli_real_escape_string($conn, trim($data['location']));
        $status_clean = mysqli_real_escape_string($conn, trim($data['status']));
        $notes_clean = mysqli_real_escape_string($conn, trim($data['notes']));

        $query_update = "UPDATE copies SET 
                            location = '$location_clean', 
                            status = '$status_clean', 
                            notes = '$notes_clean' 
                         WHERE id_copy = $id_clean";
        
        if (!mysqli_query($conn, $query_update)) {
            $has_errors = true; // Mark if any query fails
        }
    }

    if (!$has_errors) {
        // Success: Redirect back to inventory
        echo "<script>
                alert('¡Ejemplares actualizados correctamente!'); 
                window.location.href='../inventary.php';
              </script>";
        exit();
    } else {
        $alert_message = "<div class='alert alert-danger mt-3'>Hubo un error al actualizar uno o más ejemplares. Revisa la base de datos.</div>";
    }
}

// ==========================================
// 4. PREPARE THE FORM DATA (GET Request)
// ==========================================
// Retrieve the IDs passed from the URL (e.g., ?ids=1,3,5)
$ids_get = $_GET['ids'] ?? '';

if (empty($ids_get)) {
    header("Location: ../inventary.php");
    exit();
}

// Sanitize the URL data
$ids_array = array_map('intval', explode(',', $ids_get));
$ids_string = implode(',', $ids_array);

// Fetch current data for the selected copies, including the book title for context
$query_select = "
    SELECT c.id_copy, c.location, c.status, c.notes, c.code, b.title 
    FROM copies c
    INNER JOIN books b ON c.id_book = b.id_book
    WHERE c.id_copy IN ($ids_string)";

$result_copies = mysqli_query($conn, $query_select);

// If no records match, send user back
if (mysqli_num_rows($result_copies) == 0) {
    header("Location: ../inventary.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Ejemplares | Xocheco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../src/styles/styleIndex.css" rel="stylesheet">
    <link href="../src/styles/sign-in.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
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
                        <a class="nav-link" href="../UsersView.html">Usuarios</a>
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

    <main class="container mt-5 mb-5" style="max-width: 900px;">
        <form action="" method="POST">
            <div style="text-align: center;">
                <a href="../index.php">
                    <img class="mb-4" src="../src/Images/Logo.png" alt="" width="72" height="57">
                </a>
                <h1 class="h3 mb-3 fw-normal">Actualizar Ejemplares</h1>
                <p class="mb-3">Modifica la ubicación, estado o notas de los ejemplares físicos.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="accordion" id="copiesAccordion">
                <?php while ($copy = mysqli_fetch_assoc($result_copies)) { ?>
                    <div class="card mb-3 border-secondary">
                        <div class="card-header bg-dark text-white d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($copy['title']); ?></strong>
                            <span>Código: <?php echo htmlspecialchars($copy['code']); ?> (ID: <?php echo $copy['id_copy']; ?>)</span>
                        </div>
                        <div class="card-body row g-3">
                            
                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-1">Ubicación (Ej. Pasillo 3)</label>
                                <input type="text" class="form-control" 
                                       name="copies[<?php echo $copy['id_copy']; ?>][location]" 
                                       value="<?php echo htmlspecialchars($copy['location']); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-1">Estado Físico</label>
                                <select class="form-select" name="copies[<?php echo $copy['id_copy']; ?>][status]" required>
                                    <option value="Disponible" <?php echo ($copy['status'] == 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                                    <option value="Prestado" <?php echo ($copy['status'] == 'Prestado') ? 'selected' : ''; ?>>Prestado</option>
                                    <option value="Mantenimiento" <?php echo ($copy['status'] == 'Mantenimiento') ? 'selected' : ''; ?>>En Mantenimiento</option>
                                    <option value="Perdido" <?php echo ($copy['status'] == 'Perdido') ? 'selected' : ''; ?>>Perdido / Dado de baja</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-1">Notas (Daños, etc.)</label>
                                <input type="text" class="form-control" 
                                       name="copies[<?php echo $copy['id_copy']; ?>][notes]" 
                                       value="<?php echo htmlspecialchars($copy['notes']); ?>">
                            </div>

                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-warning px-4">Guardar Cambios</button>
                <a href="../inventary.php" class="btn btn-outline-secondary me-2">Cancelar</a>
            </div>
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