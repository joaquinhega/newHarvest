<?php
header("Access-Control-Allow-Origin: *"); // Permitir acceso desde cualquier origen
header("Content-Type: application/json; charset=UTF-8"); // Tipo de contenido JSON
header("Access-Control-Allow-Methods: POST"); // Solo permitir POST
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Permitir ciertos headers

include '../Model/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $id_remito = $data['id_remito'];
    $fecha = $data['fecha'];
    $monto = $data['monto'];
    $patente = $data['patente'];
    $nombre = $data['nombre'];

    if ($monto <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Error, Número invalido.']);
        exit();
    }

    $sql = "INSERT INTO combustible (id_remito_c, monto, fecha, patente, nombre, aprobado)
            VALUES ('$id_remito','$monto','$fecha', '$patente', '$nombre', 0);";

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