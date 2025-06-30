<?php
include 'conexion.php';
// Obtener datos
$id = $_POST['id'];

// Aprobar el voucher sin requerir monto
$sql = "UPDATE voucher SET aprobado = 1 WHERE id_remito_v='$id'";
if ($conn->query($sql) === TRUE ) {
    echo "<script>alert('Voucher aprobado exitosamente.'); window.location.href='../View/rrhhVoucher.php';</script>";
} else {
    echo "<script>alert('Error: " . $conn->error . "'); window.location.href='../View/rrhhVoucher.php';</script>";
}
$conn->close();
?>