<?php
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 

include '../Model/conexion.php';
session_start();
include_once 'cors.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $id_remito = $data['id'];
    $fecha = $data['fecha'];
    $monto = $data['monto'];
    $patente = $data['patente'];
    $nombre = $data['nombre'];

    if ($monto <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Error, Número invalido.']);
        exit();
    }

    $checkSql = "SELECT COUNT(*) as count FROM combustible WHERE id_remito_c = '$id_remito'";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Error: El id_remito_c ya existe.']);
        exit();
    }

    $sql = "INSERT INTO combustible (id_remito_c, monto, fecha, patente, nombre, aprobado, borrado)
            VALUES ('$id_remito','$monto','$fecha', '$patente', '$nombre', 0, 0);";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Remito guardado exitosamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>