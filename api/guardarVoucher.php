<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    $id_remito = $data['id_remito_v'] ?? $data['id'] ?? null; 
    $empresa = $data['Empresa'] ?? null;
    $origen = $data['Origen'] ?? null;
    $hora_origen = $data['hora_origen'] ?? null;
    $destino = $data['Destino'] ?? null;
    $hora_destino = $data['hora_destino'] ?? null;
    $tiempo_espera = $data['tiempo_espera'] ?? null;
    $fecha = $data['Fecha'] ?? null;
    $observaciones = $data['observaciones'] ?? null;
    $nombre_pasajero = $data['nombre_pasajero'] ?? null;
    $id_empresa = $data['id_empresa'] ?? null;

    $firmaData = $data['firma'] ?? null;
    $signaturePath = null;

    if (is_array($firmaData)) {
        $binarySignature = '';
        foreach ($firmaData as $byte) {
            $binarySignature .= chr($byte);
        }
        $firmaData = base64_encode($binarySignature);
        add_log("Firma convertida a Base64 en PHP. Longitud: " . strlen($firmaData));
    } elseif (is_string($firmaData) && strlen($firmaData) > 100) {
        add_log("Firma recibida como string base64. Longitud: " . strlen($firmaData));
    } else {
        $firmaData = null;
        add_log("Firma no es un array de enteros ni un string base64 válido o es nula.");
    }

    add_log("Extracted variables: ID Remito: {$id_remito}, Empresa: {$empresa}, Origen: {$origen}, Destino: {$destino}, Fecha: {$fecha}, Hora Origen: {$hora_origen}, Hora Destino: {$hora_destino}, Tiempo Espera: {$tiempo_espera}, Nombre Pasajero: {$nombre_pasajero}, Firma recibida: " . (isset($firmaData) ? 'Sí' : 'No'));

    if (empty($id_remito) || empty($origen) || empty($destino) || empty($fecha) || empty($hora_origen) || empty($hora_destino) || empty($nombre_pasajero) || empty($empresa)) {
        add_log("Campos obligatorios faltantes (id, Origen, Destino, Fecha, hora_origen, hora_destino, nombre_pasajero, Empresa): " . json_encode($data));
        echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios incompletos.', 'logs' => $logs]);
        exit();
    }
    add_log("All required fields are present.");

    if (isset($firmaData) && !empty($firmaData)) {
        $imageData = base64_decode($firmaData);
        $clean_id_remito = preg_replace('/[^a-zA-Z0-9_-]/', '', $id_remito);
        $signatureFileName = 'firma_' . $clean_id_remito . '_' . uniqid() . '.png';
        $uploadDir = '../firmas/';
        $signatureFullPath = $uploadDir . $signatureFileName;
        $signaturePath = '../firmas/' . $signatureFileName;

        add_log("Firma Nombre: " . $signatureFileName);
        add_log("Firma path para DB: ". $signaturePath);
        add_log("Firma Full Path para guardar: ". $signatureFullPath);

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                add_log("Error creating directory: " . $uploadDir);
                echo json_encode(['status' => 'error', 'message' => 'Error al crear el directorio de firmas.', 'logs' => $logs]);
                exit();
            }
            add_log("Directory created: " . $uploadDir);
        }

        if (file_put_contents($signatureFullPath, $imageData)) {
            add_log("Firma guardada correctamente en: " . $signatureFullPath);
        } else {
            add_log("Error al guardar la imagen de la firma en: " . $signatureFullPath);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la imagen de la firma.', 'logs' => $logs]);
            exit();
        }
    } else {
        add_log("No se recibió la firma o está vacía. Proceeding without signature path.");
    }

    $tiempo_espera_float = null;
    if ($tiempo_espera !== null && is_numeric($tiempo_espera)) {
        $tiempo_espera_float = (float)$tiempo_espera;
        add_log("Converted tiempo_espera to float: " . $tiempo_espera_float);
    } else {
        add_log("tiempo_espera is null or not numeric. Setting to NULL for DB.");
    }

    $borrado = 0;
    add_log("'borrado' value set to: " . $borrado);

    if (isset($data['id_empresa']) && $data['id_empresa'] !== '') {
        $id_empresa = (int)$data['id_empresa'];
        add_log("id_empresa is set: " . $id_empresa);
    }

    $sql = "INSERT INTO voucher (
        id_remito_v, empresa, origen, destino, fecha, observaciones, aprobado, 
        hora_origen, hora_destino, nombre_pasajero, tiempo_espera, borrado,
        Firma" .
        ($id_empresa !== null ? ", id_empresa" : "") .
    ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" .
        ($id_empresa !== null ? ", ?" : "") .
    ")";

    $types = "ssssssdsssdds";
    if ($id_empresa !== null) {
        $types .= "i";
    }
    $aprobado = 0;
    add_log("Final SQL query: " . $sql);
    add_log("Final types string: " . $types);

    mysqli_begin_transaction($conn);
    add_log("MySQLi transaction started.");

    if ($stmt = $conn->prepare($sql)) {
        add_log("SQL statement prepared successfully.");

        $params_to_bind = [
            $id_remito,
            $empresa,
            $origen,
            $destino,
            $fecha,
            $observaciones,
            $aprobado, 
            $hora_origen,
            $hora_destino,
            $nombre_pasajero,
            $tiempo_espera_float,
            $borrado,
            $signaturePath
        ];

        if ($id_empresa !== null) {
            $params_to_bind[] = $id_empresa;
        }

        add_log("Parameters to bind: " . json_encode($params_to_bind));

        $bind_args = array_merge([$types], $params_to_bind);
        $refs = [];
        foreach($bind_args as $key => $value){
            $refs[$key] = &$bind_args[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $refs);
        add_log("Parameters successfully bound.");

        if ($stmt->execute()) {
            mysqli_commit($conn);
            add_log("Voucher saved successfully. Transaction committed.");
            echo json_encode(['status' => 'success', 'message' => 'Voucher guardado exitosamente.', 'logs' => $logs]);
        } else {
            mysqli_rollback($conn);
            add_log("Error executing statement: " . $stmt->error . ". Transaction rolled back.");
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el voucher: ' . $stmt->error, 'logs' => $logs]);
        }
        $stmt->close();
        add_log("Statement closed.");
    } else {
        mysqli_rollback($conn);
        add_log("Error preparing query: " . $conn->error . ". Transaction rolled back.");
        echo json_encode(['status' => 'error', 'message' => 'Error de preparación de la consulta: ' . $conn->error, 'logs' => $logs]);
    }
} else {
    add_log("Non-POST request received. Method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no permitido.', 'logs' => $logs]);
}
add_log("guardarVoucher.php finished execution.");
$conn->close();
?>