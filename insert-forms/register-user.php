<?php
session_start();

// 1. Verify user session and privileges
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

$role = $_SESSION['rol'] ?? '';
if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../index.php");
    exit();
}

// Ensure the path to your connection file is correct
include("../src/conexion/conexion.php");

$alert_message = "";

// ==========================================
// 2. PROCESS THE INSERT (When form is submitted)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize basic info
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    
    $id_user_type = (int)$_POST['id_user_type'];
    $id_role = (int)$_POST['id_role'];
    $active = (int)$_POST['active'];

    // Sanitize address info
    $street = mysqli_real_escape_string($conn, trim($_POST['street']));
    $extNumber = mysqli_real_escape_string($conn, trim($_POST['extNumber']));
    $intNumber = mysqli_real_escape_string($conn, trim($_POST['intNumber']));
    $neighborhood = mysqli_real_escape_string($conn, trim($_POST['neighborhood']));
    $postalCode = mysqli_real_escape_string($conn, trim($_POST['postalCode']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));

    if (empty($name) || empty($last_name) || empty($email) || empty($password)) {
        $alert_message = "<div class='alert alert-danger mt-3'>Por favor llena todos los campos obligatorios.</div>";
    } elseif ($password !== $confirmPassword) {
        $alert_message = "<div class='alert alert-danger mt-3'>Las contraseñas no coinciden. Intenta de nuevo.</div>";
    } else {
        $email_check = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '$email'");
        if (mysqli_num_rows($email_check) > 0) {
            $alert_message = "<div class='alert alert-danger mt-3'>El correo electrónico ya está registrado.</div>";
        } else {
            // START TRANSACTION: Ensure both tables update successfully, or neither does
            mysqli_begin_transaction($conn);

            try {
                // Step A: Insert into the 'addresses' table
                $query_insert_address = "INSERT INTO addresses (street, ext_num, int_num, neighborhood, zip_code, city, state) 
                                         VALUES ('$street', '$extNumber', '$intNumber', '$neighborhood', '$postalCode', '$city', '$state')";
                
                if (!mysqli_query($conn, $query_insert_address)) {
                    throw new Exception("Error al registrar datos del domicilio: " . mysqli_error($conn));
                }

                // Step B: Get the ID of the address we just created
                $new_address_id = mysqli_insert_id($conn);

                // Step C: Insert into the 'users' table linked by id_address
                $query_insert_user = "INSERT INTO users (name, last_name, email, password, id_user_type, id_address, id_role, active) 
                                      VALUES ('$name', '$last_name', '$email', '$password', $id_user_type, $new_address_id, $id_role, $active)";

                if (!mysqli_query($conn, $query_insert_user)) {
                    throw new Exception("Error al registrar el usuario: " . mysqli_error($conn));
                }

                // Step D: If both succeeded, COMMIT the transaction to save them permanently
                mysqli_commit($conn);
                $alert_message = "<div class='alert alert-success mt-3'>¡Usuario y domicilio registrados exitosamente!</div>";

            } catch (Exception $e) {
                // If anything failed, ROLLBACK (undo) the transaction
                mysqli_rollback($conn);
                $alert_message = "<div class='alert alert-danger mt-3'>" . $e->getMessage() . "</div>";
            }
        }
    }
}

// ==========================================
// 3. FETCH DROPDOWN DATA
// ==========================================
$result_roles = mysqli_query($conn, "SELECT id_role, name FROM roles ORDER BY name ASC");
$result_types = mysqli_query($conn, "SELECT id_user_type, name FROM user_types ORDER BY name ASC");

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de préstamos de la Biblioteca Xocheco.">
    <title>Nuevo Usuario | Xocheco</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../src/styles/styleIndex.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">Xocheco</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="../books.php">Libros</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../UsersView.php">Usuarios</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 mb-5" style="max-width: 800px;">
        <form action="" method="POST" class="p-4 border rounded-3 bg-white shadow-sm">
            <div style="text-align: center;">
                <h1 class="h3 mb-3 fw-normal">Agrega un nuevo usuario</h1>
                <p class="text-muted">Completa la información para registrar a una nueva persona en el sistema.</p>
            </div>

            <?php echo $alert_message; ?>

            <h5 class="mt-4 mb-3 border-bottom pb-2">Información Personal</h5>
            <div class="row g-3">
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="name" name="name" required placeholder="Nombre">
                    <label for="name" class="ms-2">Nombre(s) *</label>
                </div>
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="last_name" name="last_name" required placeholder="Apellidos">
                    <label for="last_name" class="ms-2">Apellidos *</label>
                </div>
            </div>
            
            <div class="row g-3 mt-1">
                <div class="col-md-12 form-floating">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Correo">
                    <label for="email" class="ms-2">Correo Electrónico *</label>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6 form-floating">
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Contraseña">
                    <label for="password" class="ms-2">Contraseña *</label>
                </div>
                <div class="col-md-6 form-floating">
                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required placeholder="Confirmar">
                    <label for="confirmPassword" class="ms-2">Confirmar Contraseña *</label>
                </div>
            </div>

            <h5 class="mt-4 mb-3 border-bottom pb-2">Permisos del Sistema</h5>
            <div class="row g-3">
                <div class="col-md-4 form-floating">
                    <select class="form-select" id="id_user_type" name="id_user_type" required>
                        <option value="" disabled selected>Selecciona...</option>
                        <?php while($type = mysqli_fetch_assoc($result_types)) { ?>
                            <option value="<?php echo $type['id_user_type']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php } ?>
                    </select>
                    <label for="id_user_type" class="ms-2">Tipo de Usuario *</label>
                </div>
                
                <div class="col-md-4 form-floating">
                    <select class="form-select" id="id_role" name="id_role" required>
                        <option value="" disabled selected>Selecciona...</option>
                        <?php while($rol = mysqli_fetch_assoc($result_roles)) { ?>
                            <option value="<?php echo $rol['id_role']; ?>"><?php echo htmlspecialchars($rol['name']); ?></option>
                        <?php } ?>
                    </select>
                    <label for="id_role" class="ms-2">Rol del Sistema *</label>
                </div>

                <div class="col-md-4 form-floating">
                    <select class="form-select" id="active" name="active" required>
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    <label for="active" class="ms-2">Estado de Cuenta *</label>
                </div>
            </div>

            <h5 class="mt-4 mb-3 border-bottom pb-2">Domicilio (Opcional)</h5>
            <div class="row g-3">
                <div class="col-md-6 form-floating">
                    <input type="text" class="form-control" id="street" name="street" placeholder="Calle">
                    <label for="street" class="ms-2">Calle</label>
                </div>
                <div class="col-md-3 form-floating">
                    <input type="text" class="form-control" id="extNumber" name="extNumber" placeholder="No. Ext">
                    <label for="extNumber" class="ms-2">No. Exterior</label>
                </div>
                <div class="col-md-3 form-floating">
                    <input type="text" class="form-control" id="intNumber" name="intNumber" placeholder="No. Int">
                    <label for="intNumber" class="ms-2">No. Interior</label>
                </div>
            </div>
            
            <div class="row g-3 mt-1">
                <div class="col-md-4 form-floating">
                    <input type="text" class="form-control" id="neighborhood" name="neighborhood" placeholder="Colonia">
                    <label for="neighborhood" class="ms-2">Colonia</label>
                </div>
                <div class="col-md-4 form-floating">
                    <input type="text" class="form-control" id="postalCode" name="postalCode" placeholder="CP">
                    <label for="postalCode" class="ms-2">Código Postal</label>
                </div>
                <div class="col-md-4 form-floating">
                    <input type="text" class="form-control" id="city" name="city" placeholder="Ciudad">
                    <label for="city" class="ms-2">Ciudad</label>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4 form-floating">
                    <input type="text" class="form-control" id="state" name="state" placeholder="Estado">
                    <label for="state" class="ms-2">Estado</label>
                </div>
            </div>
            
            <div class="d-grid gap-2 mt-5">
                <button class="btn btn-primary py-2" type="submit">Registrar Usuario</button>
                <a href="../users.php" class="btn btn-outline-secondary py-2">Cancelar y volver a Usuarios</a>
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