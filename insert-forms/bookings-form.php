<?php
session_start();

// 1. Verify user session
if (!isset($_SESSION['id_user'])) {
    header("Location: signin.php");
    exit();
}

include("src/conexion/conexion.php");

$alert_message = "";
$id_user = (int)$_SESSION['id_user'];

// ==========================================
// 2. PROCESS THE RESERVATION (When form is submitted via POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_book_post = isset($_POST['id_book']) ? (int)$_POST['id_book'] : 0;

    if ($id_book_post > 0) {
        // Prevent duplicate pending reservations for the exact same book and user
        $check_query = "SELECT id_booking FROM bookings WHERE id_user = $id_user AND id_book = $id_book_post AND status = 'En Espera'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $alert_message = "<div class='alert alert-warning mt-3'>Ya tienes una reservación 'En Espera' para este libro. Por favor, espera a que sea aprobada.</div>";
        } else {
            // Insert the new reservation
            $insert_query = "INSERT INTO bookings (id_user, id_book, booking_date, status) 
                             VALUES ($id_user, $id_book_post, NOW(), 'En Espera')";
            
            if (mysqli_query($conn, $insert_query)) {
                echo "<script>
                        alert('¡Reservación solicitada exitosamente! Puedes ver el estado en tu panel.'); 
                        window.location.href='bookings.php';
                      </script>";
                exit();
            } else {
                $alert_message = "<div class='alert alert-danger mt-3'>Error al procesar la reservación: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

// ==========================================
// 3. FETCH BOOK DATA FOR THE CONFIRMATION VIEW (GET Request)
// ==========================================
// Get the ID from the URL (or from the POST if there was an error and we stayed on the page)
$id_book_get = isset($_GET['id_book']) ? (int)$_GET['id_book'] : (isset($_POST['id_book']) ? (int)$_POST['id_book'] : 0);

if ($id_book_get === 0) {
    header("Location: books.php");
    exit();
}

// Fetch basic book details to show the user what they are reserving
$query_book = "SELECT title, isbn, synopsis FROM books WHERE id_book = $id_book_get";
$result_book = mysqli_query($conn, $query_book);

if (mysqli_num_rows($result_book) == 0) {
    // If someone changes the ID in the URL to a book that doesn't exist
    header("Location: books.php");
    exit();
}

$book = mysqli_fetch_assoc($result_book);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmar Reservación | Xocheco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="src\styles\styleIndex.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Xocheco</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="books.php">Libros</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservationsView.php">Mis Reservaciones</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="w-100 m-auto mt-5" style="max-width: 600px; padding: 15px;">
        <form action="" method="POST" class="p-4 border rounded-3 bg-white shadow-sm">
            <div class="text-center mb-4">
                <h1 class="h3 mb-3 fw-normal">Confirmar Reservación</h1>
                <p class="text-muted">Estás a un paso de solicitar este libro.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="card mb-4 border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo htmlspecialchars($book['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></h6>
                    <hr>
                    <p class="card-text small text-muted">
                        <?php echo htmlspecialchars(substr($book['synopsis'], 0, 150)) . '...'; ?>
                    </p>
                </div>
            </div>

            <div class="alert alert-info small">
                <strong>Importante:</strong> Al confirmar, tu solicitud pasará a estado "En Espera". El personal de la biblioteca la revisará y te notificará cuando esté lista para entrega.
            </div>

            <input type="hidden" name="id_book" value="<?php echo $id_book_get; ?>">

            <div class="d-grid gap-2 mt-4">
                <button class="btn btn-success py-2" type="submit">Confirmar Solicitud</button>
                <a href="books.php" class="btn btn-outline-secondary py-2">Cancelar y volver al catálogo</a>
            </div>
        </form>
    </main>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="small mb-0">&copy; 2026 Xocheco Biblioteca. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>