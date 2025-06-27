<?php
header("Content-Type: application/json");
include '../Model/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['id_remito_c'])) {
    echo json_encode(["error" => "ID de voucher requerido"]);
    exit;
}

$sql = "UPDATE combustible SET borrado = 1 WHERE id_remito_c = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $data['id_remito_c']);

if ($stmt->execute()) {
    echo json_encode(["message" => "Combustible eliminado"]);
} else {
    echo json_encode(["error" => "Error al eliminar el combustible"]);
}

$conn->close();
?>
