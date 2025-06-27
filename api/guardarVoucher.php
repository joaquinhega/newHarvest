<?php
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 

include '../Model/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    error_log("📥 Datos recibidos: " . print_r($data, true));

    $id_remito = $data['id_remito_v'];
    $empresa = $data['Empresa'];
    $origen = $data['Origen'];
    $hora_origen = $data['hora_origen'];
    $destino = $data['Destino'];
    $hora_destino = $data['hora_destino'];
    $tiempo_espera = $data['tiempo_espera'];
    $fecha = $data['Fecha'];
    $observaciones = $data['observaciones'];
    $nombre_pasajero = $data['nombre_pasajero'];

    if (empty($id_remito) || empty($empresa) || empty($origen) || empty($destino) || empty($fecha) || empty($hora_origen) || empty($hora_destino)) {
        error_log("⚠️ Campos obligatorios faltantes");
        echo json_encode(['status' => 'error', 'message' => 'Por favor complete todos los campos obligatorios.']);
        exit();
    }

    // Manejar la imagen de la firma
    if (isset($_FILES['signature'])) {
        $signature = $_FILES['signature'];
        $signaturePath = '../firmas/' . basename($signature['name']);
        if (move_uploaded_file($signature['tmp_name'], $signaturePath)) {
            error_log("✅ Firma guardada en: " . $signaturePath);
        } else {
            error_log("⚠️ Error al guardar la firma.");
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la firma.']);
            exit;
        }
    } else {
        $signaturePath = null;
    }

    $sql = "INSERT INTO voucher (id_remito_v, Empresa, Origen, Destino, fecha, firma, aprobado, observaciones, hora_origen, hora_destino, nombre_pasajero, tiempo_espera, borrado) 
        VALUES ('$id_remito', '$empresa', '$origen', '$destino', '$fecha', '$signaturePath', 0, '$observaciones', '$hora_origen', '$hora_destino', '$nombre_pasajero', '$tiempo_espera', 0)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Voucher guardado exitosamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>