<?php
include 'conexion.php'; 
session_start();

$id_remito = $_POST['id_remito'];
$fecha = $_POST['fecha'];
$monto = $_POST['monto'];
$patente = $_POST['patente'];
$nombre = $_SESSION['nombre'];

$sql = "INSERT INTO combustible (id_remito_c, monto, fecha, patente, nombre, aprobado)
        VALUES ('$id_remito','$monto','$fecha', '$patente', '$nombre', 0);"; 

if($monto > 0){
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Remito guardado exitosamente.'); window.location.href='../View/chofer.php';</script>";
    } else {
        echo "<script>alert('Error. " . $conn->error . "'); window.location.href='../View/chofer.php';</script>";
    }
$conn->close();
} else {
    echo "<script>alert('Error, Número invalido. '); window.location.href='../View/chofer.php';</script>";
    
}
?>