<?php
include("src/conexion/conexion.php");

$sql = "SELECT * FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo $row['name'] . " " . $row['last_name'] . "<br>";
    }
} else {
    echo "No hay usuarios.";
}

$conn->close();
?>