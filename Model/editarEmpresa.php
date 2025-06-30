<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

// Solo procesa si es POST y tiene los datos necesarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empresa']) && isset($_POST['nombre_empresa'])) {
    $id_empresa = $_POST['id_empresa'];
    $nuevoNombre = $_POST['nombre_empresa'];

    $updateSql = "UPDATE empresa SET nombre = '$nuevoNombre' WHERE id_empresa = '$id_empresa'";
    if ($conn->query($updateSql)) {
        echo "success";
    } else {
        echo "error";
    }
    exit();
}
echo "invalid";
exit();