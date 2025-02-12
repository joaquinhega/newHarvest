<?php
// Activar la visualización de errores en PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php'; 
session_start();

// Capturando los datos del formulario
$id_remito = $_POST['id_remito'];
$empresa = $_POST['empresa'];
$origen = $_POST['origen'];
$hora_origen = $_POST['hora_origen'];
$destino = $_POST['destino'];
$hora_destino = $_POST['hora_destino'];
$tiempo_espera = $_POST['tiempo_espera'];
$fecha = $_POST['fecha'];
$signature = $_POST['signature'];
$observaciones = $_POST['observaciones'];
$nombre_pasajero = $_POST['nombre_pasajero'];


// Validación de campos obligatorios
if (empty($id_remito) || empty($empresa) || empty($origen) || empty($destino) || empty($fecha) || empty($hora_origen) || empty($hora_destino)) {
    echo "<script>alert('Por favor complete todos los campos obligatorios.'); window.location.href='../View/chofer.php';</script>";
    exit(); // Detener la ejecución si los campos están vacíos
}

// Procesamiento de la firma
if (!empty($signature)) {
    $signature = str_replace('data:image/png;base64,', '', $signature);
    $signature = str_replace(' ', '+', $signature);
    $data = base64_decode($signature);
    $file = '../firmas/' . uniqid() . '.png';
    
    if (!file_put_contents($file, $data)) {
        echo "<script>alert('Error al guardar la firma.'); window.location.href='../View/chofer.php';</script>";
        exit(); // Detener la ejecución si no se puede guardar la firma
    }
} else {
    $file = NULL; // Si no hay firma, se pone NULL
}

// Verificación de conexión a la base de datos
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta SQL para insertar los datos
$sql = "INSERT INTO voucher (id_remito_v, Empresa, Origen, Destino, fecha, firma, aprobado, observaciones, hora_origen, hora_destino, nombre_pasajero, tiempo_espera, borrado) 
        VALUES ('$id_remito', '$empresa', '$origen', '$destino', '$fecha', '$file', 0, '$observaciones', '$hora_origen', '$hora_destino', '$nombre_pasajero', '$tiempo_espera', 0)";
// Ejecutar la consulta y verificar el resultado
if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Voucher guardado exitosamente.'); window.location.href='../View/chofer.php';</script>";
} else {
    echo "<script>alert('Error: " . $conn->error . "'); window.location.href='../View/chofer.php';</script>";
}

// Cerrar la conexión
$conn->close();
?>