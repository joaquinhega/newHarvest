<?php
header("Content-Type: application/json");
include '../Model/conexion.php';

$user = $_SERVER['HTTP_USER'] ?? '';
$letra = $_SERVER['HTTP_LETRA'] ?? '';

$sql = "SELECT * FROM voucher WHERE SUBSTRING(id_Remito_v, 1, 1) = '$letra' AND borrado = 0 ORDER BY Fecha DESC";
$result = $conn->query($sql);

$vouchers = [];
while ($row = $result->fetch_assoc()) {
    $vouchers[] = $row;
}

echo json_encode($vouchers);
$conn->close();
?>