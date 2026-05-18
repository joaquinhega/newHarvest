<?php
header("Content-Type: application/json");
include '../Model/conexion.php';
include_once 'cors.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_remito_c'])) {
    echo json_encode(["success" => false, "error" => "ID de voucher requerido"]);
    exit;
}

$id_remito_c = $data['id_remito_c'];

$sql = "UPDATE combustible
        SET Monto = ?, Fecha = ?, patente = ?
        WHERE id_remito_c = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Error en la preparación de la consulta"]);
    exit;
}

$stmt->bind_param("ssss",
    $data['Monto'],
    $data['Fecha'],
    $data['patente'],
    $id_remito_c
);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Remito actualizado correctamente"]);
    } else {
        echo json_encode(["success" => false, "error" => "No se actualizó ningún remito, verifica el ID"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Error al actualizar el remito"]);
}

$stmt->close();
$conn->close();
?>