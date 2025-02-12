<?php
header('Content-Type: application/json');

// Conectar a la base de datos
$servername = "localhost"; // Usualmente 'localhost'
$username = "c2112119_vbd"; // Tu usuario de MySQL
$password = "pnartcqsedn6Rzo"; // Tu contraseña de MySQL
$dbname = "c2112119_vbd"; // El nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta a la tabla "vouchers"
$sql = "SELECT * FROM voucher";
$result = $conn->query($sql);

$vouchers = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
}

echo json_encode($vouchers);

$conn->close();
?>