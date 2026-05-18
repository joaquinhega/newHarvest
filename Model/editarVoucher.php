<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['rol'])) {
    error_log("Error: Sesión no válida. Intento de acceso a editarVoucher.php sin sesión.");
    echo "Error: Sesión no válida. Por favor, inicie sesión nuevamente.";
    exit();
}

$rol = $_SESSION['rol'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remito_v'])) {
    $id = $_POST['id_remito_v'];
    $nombre_pasajero = $_POST['nombre_pasajero'] ?? '';
    $origen = $_POST['Origen'] ?? '';
    $hora_origen = $_POST['hora_origen'] ?? '';
    $destino = $_POST['Destino'] ?? '';
    $hora_destino = $_POST['hora_destino'] ?? '';
    $fecha = $_POST['Fecha'] ?? ''; 
    $tiempo_espera = $_POST['tiempo_espera'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    error_log("Datos recibidos para edición de voucher ID: " . $id);
    error_log("  Pasajero: " . $nombre_pasajero);
    error_log("  Origen: " . $origen); 
    error_log("  Hora Origen: " . $hora_origen);
    error_log("  Destino: " . $destino); 
    error_log("  Hora Destino: " . $hora_destino);
    error_log("  Fecha: " . $fecha);
    error_log("  Tiempo Espera: " . $tiempo_espera);
    error_log("  Observaciones: " . $observaciones);

    mysqli_begin_transaction($conn);

    $sql_update = "UPDATE voucher 
                   SET nombre_pasajero = ?, 
                       Origen = ?, 
                       hora_origen = ?, 
                       Destino = ?, 
                       hora_destino = ?, 
                       Fecha = ?, 
                       tiempo_espera = ?, 
                       observaciones = ? 
                   WHERE id_remito_v = ?";
    
    $stmt = $conn->prepare($sql_update);

    if ($stmt) {
        $bindFecha = ($fecha === '') ? NULL : $fecha;
        
        $bindTiempoEspera = ($tiempo_espera === '' || $tiempo_espera === null) ? NULL : (int)$tiempo_espera;

        $types = "sssssssss"; 
        $bindParams = [
            &$nombre_pasajero,
            &$origen,
            &$hora_origen,
            &$destino,
            &$hora_destino,
            &$bindFecha, 
            &$bindTiempoEspera, 
            &$observaciones,
            &$id
        ];
        
        array_unshift($bindParams, $types);
        call_user_func_array([$stmt, 'bind_param'], $bindParams);

        $resultado_update = $stmt->execute();

        if ($resultado_update) {
            mysqli_commit($conn); 
            error_log("Voucher ID " . $id . " actualizado correctamente.");
            echo "OK";
        } else {
            mysqli_rollback($conn); 
            error_log("Error al actualizar el voucher ID " . $id . ": " . $stmt->error);
            echo "Error al actualizar el voucher: " . $stmt->error;
        }
        $stmt->close();
    } else {
        mysqli_rollback($conn);
        error_log("Error de preparación de la consulta de actualización: " . $conn->error);
        echo "Error de preparación de la consulta: " . $conn->error;
    }
} else {
    error_log("Error: Solicitud inválida para editarVoucher.php. No es POST o falta id_remito_v.");
    echo "Error: Solicitud inválida.";
}
$conn->close();
?>