<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empresa'])) {
    $id = $_POST['id_empresa'];

    mysqli_begin_transaction($conn);

    $sql = "UPDATE empresa SET borrado = 1 WHERE id_empresa = '$id'";
    $resultado = $conn->query($sql);

    if ($resultado) {
        $sqlVouchers = "UPDATE voucher SET id_empresa = NULL WHERE id_empresa = '$id'";
        $resultadoVouchers = $conn->query($sqlVouchers);

        mysqli_commit($conn);
        echo "success";
    } else {
        mysqli_rollback($conn);
        echo "error";
    }
    exit();
}
echo "invalid";
exit();