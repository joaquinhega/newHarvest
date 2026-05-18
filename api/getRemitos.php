<?php
header("Content-Type: application/json");
include '../Model/conexion.php';
include_once 'cors.php';

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

$nombre = isset($_SERVER['HTTP_NOMBRE']) ? $_SERVER['HTTP_NOMBRE'] : '';
$page = isset($input['page']) ? intval($input['page']) : 1;
$limit = isset($input['limit']) ? intval($input['limit']) : 100;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM combustible WHERE nombre = ? AND borrado = 0 ORDER BY Fecha DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $nombre, $limit, $offset);
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