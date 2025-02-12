<?php
include 'conexion.php';
$id = $_POST['id_remito_v'];
$id_empresa = $_POST['id_empresa'];    

if ($id_empresa === 'no_autorizar') {
    $sql = "UPDATE voucher SET aprobado = 0, Monto = NULL WHERE id_remito_v='$id'";
} else if (is_numeric($id_empresa) && $id_empresa > 0) {
    $sql = "UPDATE voucher SET id_empresa = '$id_empresa' WHERE id_remito_v='$id'";
} else {
    echo "<script>alert('id_empresa ingresado invalido'); window.location.href='../View/rrhh.php';</script>";
    exit;
}

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Voucher guardado exitosamente.'); window.location.href='../View/rrhhVoucher.php';</script>";
} else {
    echo "<script>alert('Error: " . $conn->error . "'); window.location.href='../View/rrhhVoucher.php';</script>";
}

$conn->close();
?>