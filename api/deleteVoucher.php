<?php
header("Content-Type: application/json");
include '../Model/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['id_remito_v'])) {
    echo json_encode(["error" => "ID de voucher requerido"]);
    exit;
}

$sql = "UPDATE voucher SET borrado = 1 WHERE id_remito_v = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $data['id_remito_v']);

if ($stmt->execute()) {
    echo json_encode(["message" => "Voucher eliminado"]);
} else {
    echo json_encode(["error" => "Error al eliminar el voucher"]);
}

$conn->close();
?>
