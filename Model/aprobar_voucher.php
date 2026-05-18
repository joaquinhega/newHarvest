<?php
include 'conexion.php';

$id = $_POST['id'] ?? '';

if (!$id) {
    echo "ERROR: ID vacío";
    exit;
}

$sql = "SELECT empresa FROM voucher WHERE id_remito_v='$id' LIMIT 1";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $nombreEmpresa = $conn->real_escape_string($row['empresa']);

    $sqlEmpresa = "SELECT id_empresa FROM empresa WHERE nombre='$nombreEmpresa' AND borrado=0 LIMIT 1";
    $resultEmpresa = $conn->query($sqlEmpresa);

    if ($resultEmpresa && $rowEmpresa = $resultEmpresa->fetch_assoc()) {
        $idEmpresa = $rowEmpresa['id_empresa'];

        $sqlUpdate = "UPDATE voucher SET aprobado=1, id_empresa='$idEmpresa' WHERE id_remito_v='$id'";
        if ($conn->query($sqlUpdate) === TRUE) {
            echo "OK";
        } else {
            echo "ERROR: " . $conn->error;
        }
    } else {
        $sqlUpdate = "UPDATE voucher SET aprobado=1 WHERE id_remito_v='$id'";
        if ($conn->query($sqlUpdate) === TRUE) {
            echo "OK";
        } else {
            echo "ERROR: " . $conn->error;
        }
    }
} else {
    echo "ERROR: Voucher no encontrado";
}

$conn->close();
?>