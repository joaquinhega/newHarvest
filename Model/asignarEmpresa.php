<?php
include 'conexion.php';
session_start();

error_log("===> asignarEmpresa.php llamado: " . json_encode($_POST));

if (!isset($_SESSION['user'])) {
    error_log("===> ERROR: Sesión no iniciada");
    echo "ERROR: Sesión no iniciada";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remito_v']) && isset($_POST['id_empresa'])) {
    $id = $conn->real_escape_string($_POST['id_remito_v']);
    $id_empresa = $_POST['id_empresa'];

    error_log("===> id_remito_v: $id | id_empresa: $id_empresa");

    if ($id_empresa === 'desaprobar') {
        $sql = "UPDATE voucher SET aprobado = 0, id_empresa = NULL, Monto = NULL WHERE id_remito_v='$id'";
        error_log("===> SQL desaprobar: $sql");
    } else if (is_numeric($id_empresa) && $id_empresa > 0) {
        $id_empresa = $conn->real_escape_string($id_empresa);
        $sql = "UPDATE voucher SET id_empresa = '$id_empresa' WHERE id_remito_v='$id'";
        error_log("===> SQL asignar: $sql");
    } else {
        error_log("===> ERROR: id_empresa ingresado invalido");
        echo "ERROR: id_empresa ingresado invalido";
        exit;
    }

    if ($conn->query($sql) === TRUE) {
        error_log("===> OK: Empresa asignada/desaprobada correctamente");
        echo "OK";
    } else {
        error_log("===> ERROR SQL: " . $conn->error);
        echo "ERROR: " . $conn->error;
    }
} else {
    error_log("===> ERROR: Datos incompletos");
    echo "ERROR: Datos incompletos";
    exit();
}

$conn->close();
?>