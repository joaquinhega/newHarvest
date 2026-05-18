<?php
header("Content-Type: application/json");
include '../Model/conexion.php';
include_once 'cors.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_remito_v'])) {
    echo json_encode(["error" => "ID de voucher requerido"]);
    exit;
}

$id_remito_v = $data['id_remito_v'];

$sql = "UPDATE voucher 
        SET Empresa = ?, nombre_pasajero = ?, Origen = ?, hora_origen = ?, Destino = ?, hora_destino = ?, Fecha = ?, observaciones = ?, tiempo_espera = ? 
        WHERE id_remito_v = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Error en la preparación de la consulta"]);
    exit;
}

$stmt->bind_param("ssssssssss",
    $data['Empresa'], 
    $data['nombre_pasajero'], 
    $data['Origen'], 
    $data['hora_origen'], 
    $data['Destino'], 
    $data['hora_destino'], 
    $data['Fecha'], 
    $data['observaciones'], 
    $data['tiempo_espera'], 
    $id_remito_v
);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["message" => "Voucher actualizado correctamente"]);
    } else {
        echo json_encode(["error" => "No se actualizó ningún voucher, verifica el ID"]);
    }
} else {
    echo json_encode(["error" => "Error al actualizar el voucher"]);
}

$stmt->close();
$conn->close();
?>
