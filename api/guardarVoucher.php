<?php
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 

include '../Model/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    error_log("📥 Datos recibidos: " . print_r($data, true));

    $id_remito = $data['id_remito'];
    $empresa = $data['empresa'];
    $origen = $data['origen'];
    $hora_origen = $data['hora_origen'];
    $destino = $data['destino'];
    $hora_destino = $data['hora_destino'];
    $tiempo_espera = $data['tiempo_espera'];
    $signature = $data['signature'];
    $fecha = $data['fecha'];
    $observaciones = $data['observaciones'];
    $nombre_pasajero = $data['nombre_pasajero'];

    if (empty($id_remito) || empty($empresa) || empty($origen) || empty($destino) || empty($fecha) || empty($hora_origen) || empty($hora_destino)) {
        error_log("⚠️ Campos obligatorios faltantes");
        echo json_encode(['status' => 'error', 'message' => 'Por favor complete todos los campos obligatorios.']);
        exit();
    }

    if (!empty($signature)) {
        $signature = str_replace('data:image/png;base64,', '', $signature);
        $signature = str_replace(' ', '+', $signature);
        $data = base64_decode($signature);
        $file = '../firmas/' . uniqid() . '.png';
        
        if (!file_put_contents($file, $data)) {
            error_log("⚠️ Error al guardar la firma");
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la firma.']);
            exit(); 
        }
    } else {
        $file = NULL; 
    }

    $sql = "INSERT INTO voucher (id_remito_v, Empresa, Origen, Destino, fecha, firma, aprobado, observaciones, hora_origen, hora_destino, nombre_pasajero, tiempo_espera, borrado) 
        VALUES ('$id_remito', '$empresa', '$origen', '$destino', '$fecha', '$file', 0, '$observaciones', '$hora_origen', '$hora_destino', '$nombre_pasajero', '$tiempo_espera', 0)";

    if ($conn->query($sql) === TRUE) {
        error_log("✅ Voucher guardado exitosamente");
        echo json_encode(['status' => 'success', 'message' => 'Voucher guardado exitosamente.']);
    } else {
        error_log("⚠️ Error al guardar el voucher: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }

    $conn->close();
} else {
    error_log("⚠️ Método no permitido");
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>