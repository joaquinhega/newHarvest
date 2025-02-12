<?php
include 'conexion.php';
// Obtener datos
$id = $_POST['id'];
$monto = $_POST['monto'];    

if($monto > 0){
    $sql = "UPDATE voucher SET monto = '$monto', aprobado = 1 WHERE id_remito_v='$id'";
    if ($conn->query($sql) === TRUE ) {
        echo "<script>alert('Voucher guardado exitosamente.'); window.location.href='../View/rrhhVoucher.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.location.href='../View/rrhhVoucher.php';</script>";
    }
$conn->close();
} else {
    echo "<script>alert('Monto ingresado invalido'); window.location.href='../View/rrhh.php';</script>";
}
?>
