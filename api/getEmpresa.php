<?php
header("Content-Type: application/json");
include '../Model/conexion.php';
include_once 'cors.php';

file_put_contents('php://stderr', "[getEmpresa.php] HEADERS: " . json_encode(getallheaders()) . "\n");

$sql = "SELECT id_empresa, nombre FROM empresa WHERE borrado = 0 ORDER BY nombre ASC";
$result = $conn->query($sql);

$empresas = array();
while ($row = $result->fetch_assoc()) {
    $empresas[] = $row;
}

echo json_encode($empresas);

$conn->close();
?>