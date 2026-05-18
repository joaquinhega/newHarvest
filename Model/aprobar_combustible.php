<?php
header('Content-Type: text/plain; charset=utf-8');
include 'conexion.php';

error_log("[aprobar_combustible] POST received: " . json_encode($_POST));

if (!isset($_POST['id'])) {
    http_response_code(400);
    echo "ID no proporcionado.";
    exit();
}

$id = $_POST['id'];

$stmt = $conn->prepare("UPDATE combustible SET aprobado = 1 WHERE id_remito_c = ?");
if (!$stmt) {
    error_log("[aprobar_combustible] prepare error: " . $conn->error);
    http_response_code(500);
    echo "Error interno en la preparación.";
    exit();
}

$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    error_log("[aprobar_combustible] OK updated id: $id");
    echo "OK";
} else {
    error_log("[aprobar_combustible] execute error: " . $stmt->error);
    http_response_code(500);
    echo "Error al ejecutar la actualización: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>