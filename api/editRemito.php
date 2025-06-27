<?php
header("Content-Type: application/json");
include '../Model/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_remito_c'])) {
    echo json_encode(["error" => "ID de voucher requerido"]);
    exit;
}

$id_remito_c = $data['id_remito_c'];

echo json_encode(["message" => "Back Editando voucher con ID: $id_remito_c"]);

$sql = "UPDATE combustible 
        SET Monto = ?, Fecha = ?, patente = ?
        WHERE id_remito_c = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Error en la preparación de la consulta"]);
    exit;
}

$stmt->bind_param("ssss",
    $data['Monto'], 
    $data['Fecha'], 
    $data['patente'], 
    $id_remito_c
);

if ($stmt->execute()) {
    // Verificar cuántas filas fueron afectadas
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
