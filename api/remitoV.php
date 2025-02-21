<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include '../Model/conexion.php';

function obtenerUltimoRemitoPorChofer($letra_chofer, $conn) {
    $query = "SELECT id_remito_v FROM voucher WHERE id_remito_v LIKE '$letra_chofer%' ORDER BY id_remito_v DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_row($result);
        if ($row) {
            return $row[0];
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $letra_chofer = $data['letra_chofer'];

    error_log("📥 Letra del chofer recibida: " . $letra_chofer);

    $ultimo_remito = obtenerUltimoRemitoPorChofer($letra_chofer, $conn);

    if ($ultimo_remito) {
        error_log("✅ Último remito encontrado: " . $ultimo_remito);
        echo json_encode(['ultimo_remito' => $ultimo_remito]);
    } else {
        error_log("⚠️ No se encontró ningún remito para la letra del chofer: " . $letra_chofer);
        echo json_encode(['ultimo_remito' => null]);
    }

    $conn->close();
} else {
    error_log("⚠️ Método no permitido");
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>