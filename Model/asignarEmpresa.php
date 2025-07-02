<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remito_v']) && isset($_POST['id_empresa'])) {
    $id = $conn->real_escape_string($_POST['id_remito_v']);
    $id_empresa = $_POST['id_empresa'];

    if ($id_empresa === 'desaprobar') {
        // Desaprobar: quitar empresa y poner aprobado en 0
        $sql = "UPDATE voucher SET aprobado = 0, id_empresa = NULL, Monto = NULL WHERE id_remito_v='$id'";
    } else if (is_numeric($id_empresa) && $id_empresa > 0) {
        // Asignar empresa
        $id_empresa = $conn->real_escape_string($id_empresa);
        $sql = "UPDATE voucher SET id_empresa = '$id_empresa' WHERE id_remito_v='$id'";
    } else {
        echo "<script>alert('id_empresa ingresado invalido'); window.location.href='../View/rrhhVoucher.php';</script>";
        exit;
    }

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Voucher guardado exitosamente.'); window.location.href='../View/rrhhVoucher.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.location.href='../View/rrhhVoucher.php';</script>";
    }
} else {
    header('Location: ../View/rrhhVoucher.php');
    exit();
}

$conn->close();
?>