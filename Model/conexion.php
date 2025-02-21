<?php

$server = "localhost";
// usuario: c2112119_vbd
$user = "root";
// Contraseña: pnartcqsedn6Rzo
$pass = "";
$db = "sistema_vouchers";

$conn = mysqli_connect($server, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>