<?php
session_start();

// 1. Verify user privileges
if (!isset($_SESSION['id_user'])) {
    header("Location: ../signin.php");
    exit();
}

$role = $_SESSION['rol'] ?? '';

if ($role !== 'admin' && $role !== 'Administrador' && $role !== 'bibliotecario' && $role !== 'Bibliotecario') {
    header("Location: ../users.php");
    exit();
}

include("../src/conexion/conexion.php");

$alert_message = "";

// ==========================================
// 2. PROCESS THE UPDATE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users_data = $_POST['users'] ?? [];
    $has_errors = false;

    foreach ($users_data as $id_user => $data) {
        $id_clean = (int)$id_user;
        
        $id_role_clean = (int)$data['id_role'];
        $id_type_clean = (int)$data['id_user_type'];
        $active_clean = (int)$data['active'];

        $query_update = "UPDATE users SET 
                            id_role = $id_role_clean, 
                            id_user_type = $id_type_clean, 
                            active = $active_clean 
                         WHERE id_user = $id_clean";
        
        if (!mysqli_query($conn, $query_update)) {
            $has_errors = true;
        }
    }

    if (!$has_errors) {
        echo "<script>
                alert('¡Usuarios actualizados correctamente!'); 
                window.location.href='../users.php';
              </script>";
        exit();
    } else {
        $alert_message = "<div class='alert alert-danger mt-3'>Hubo un error al actualizar uno o más usuarios.</div>";
    }
}

// ==========================================
// 3. FETCH CURRENT DATA (GET)
// ==========================================
$ids_get = $_GET['ids'] ?? '';

if (empty($ids_get)) {
    header("Location: ../users.php");
    exit();
}

$ids_array = array_map('intval', explode(',', $ids_get));
$ids_string = implode(',', $ids_array);

// Fetch selected users
$query_users = "SELECT id_user, name, last_name, email, id_role, id_user_type, active 
                FROM users WHERE id_user IN ($ids_string)";
$result_users = mysqli_query($conn, $query_users);

if (mysqli_num_rows($result_users) == 0) {
    header("Location: ../users.php");
    exit();
}

// Fetch data for dropdowns
$result_roles = mysqli_query($conn, "SELECT id_role, name FROM roles ORDER BY id_role ASC");
$roles_array = [];
while ($row = mysqli_fetch_assoc($result_roles)) { $roles_array[] = $row; }

$result_types = mysqli_query($conn, "SELECT id_user_type, name FROM user_types ORDER BY id_user_type ASC");
$types_array = [];
while ($row = mysqli_fetch_assoc($result_types)) { $types_array[] = $row; }
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modificar Usuarios | Xocheco</title>
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
                    <li class="nav-item"><a class="nav-link active" href="../users.php">Usuarios</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 mb-5" style="max-width: 900px;">
        <form action="" method="POST" class="p-4 border rounded-3 bg-white shadow-sm">
            <div class="text-center mb-4">
                <h1 class="h3 fw-normal">Modificar Privilegios</h1>
                <p class="text-muted">Actualiza el rol, tipo o estado de los usuarios seleccionados.</p>
            </div>

            <?php echo $alert_message; ?>

            <div class="accordion" id="usersAccordion">
                <?php while ($user = mysqli_fetch_assoc($result_users)) { ?>
                    <div class="card mb-3 border-secondary">
                        <div class="card-header bg-dark text-white d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($user['name'] . ' ' . $user['last_name']); ?></strong>
                            <span><?php echo htmlspecialchars($user['email']); ?> (ID: <?php echo $user['id_user']; ?>)</span>
                        </div>
                        <div class="card-body row g-3">
                            
                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-1">Nivel de Acceso (Rol)</label>
                                <select class="form-select" name="users[<?php echo $user['id_user']; ?>][id_role]" required>
                                    <?php foreach ($roles_array as $rol) { 
                                        $selected = ($user['id_role'] == $rol['id_role']) ? 'selected' : '';
                                        echo "<option value='{$rol['id_role']}' $selected>{$rol['name']}</option>";
                                    } ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-1">Tipo de Lector</label>
                                <select class="form-select" name="users[<?php echo $user['id_user']; ?>][id_user_type]" required>
                                    <?php foreach ($types_array as $type) { 
                                        $selected = ($user['id_user_type'] == $type['id_user_type']) ? 'selected' : '';
                                        echo "<option value='{$type['id_user_type']}' $selected>{$type['name']}</option>";
                                    } ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-1">Estado de la Cuenta</label>
                                <select class="form-select" name="users[<?php echo $user['id_user']; ?>][active]" required>
                                    <option value="1" <?php echo ($user['active'] == 1) ? 'selected' : ''; ?>>Activo (Permitir acceso)</option>
                                    <option value="0" <?php echo ($user['active'] == 0) ? 'selected' : ''; ?>>Inactivo (Bloquear)</option>
                                </select>
                            </div>

                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="../users.php" class="btn btn-danger me-2">Cancelar</a>
                <button type="submit" class="btn btn-success px-4">Guardar Cambios</button>
            </div>
        </form>
    </main>
</body>
</html>