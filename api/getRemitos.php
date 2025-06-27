<?php
header("Content-Type: application/json");
include '../Model/conexion.php';

$nombre = $_SERVER['HTTP_NOMBRE'];

$sql = "SELECT * FROM combustible WHERE nombre = ? AND aprobado = 0 AND borrado = 0 ORDER BY Fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$result = $stmt->get_result();

$remitos = array();
while ($row = $result->fetch_assoc()) {
    $remitos[] = $row;
}

echo json_encode($remitos);

$stmt->close();
$conn->close();
?>