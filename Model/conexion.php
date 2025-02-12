<?php

$server = "localhost";
// usuario: c2112119_vbd
$user = "c2112119_vbd";
// Contraseña: pnartcqsedn6Rzo
$pass = "pnartcqsedn6Rzo";
$db = "c2112119_vbd";

$conn = mysqli_connect($server, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>