<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include '../Model/conexion.php';
session_start();
include_once 'cors.php';

$logs = [];
function add_log($msg) {
    global $logs;
    $logs[] = $msg;
    error_log($msg);
}

add_log("▶️ getLatestVoucherId.php started. Request method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $letra_chofer = $_GET['letraChofer'] ?? null;

    if (empty($letra_chofer)) {
        add_log("⚠️ Missing 'letraChofer' parameter.");
        echo json_encode(['status' => 'error', 'message' => 'Parámetro letraChofer es obligatorio.', 'logs' => $logs]);
        exit();
    }

    add_log("🔎 Fetching latest voucher ID for letraChofer: " . $letra_chofer);

    $sql = "SELECT id_remito_v FROM voucher 
            WHERE id_remito_v LIKE ? AND borrado = 0
            ORDER BY CAST(SUBSTRING(id_remito_v, 2) AS UNSIGNED) DESC 
            LIMIT 1";
            
    $search_pattern = $letra_chofer . '%';
    $types = "s";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, $search_pattern);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $latest_id = $row['id_remito_v'];
                $last_number = (int)substr($latest_id, 1);
                add_log("Latest ID found: " . $latest_id . ", extracted number: " . $last_number);
                echo json_encode(['status' => 'success', 'last_number' => $last_number, 'logs' => $logs]);
            } else {
                add_log("ℹ️ No record found for letraChofer: " . $letra_chofer . ". Returning 0.");
                echo json_encode(['status' => 'success', 'last_number' => 0, 'logs' => $logs]);
            }
        } else {
            add_log("❌ Error executing statement: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta: ' . $stmt->error, 'logs' => $logs]);
        }
        $stmt->close();
    } else {
        add_log("❗ Error preparing query: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Error de preparación de la consulta: ' . $conn->error, 'logs' => $logs]);
    }
} else {
    add_log("🚫 Non-GET request received. Method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no permitido.', 'logs' => $logs]);
}
add_log("🏁 getLatestVoucherId.php finished execution.");
$conn->close();
?>