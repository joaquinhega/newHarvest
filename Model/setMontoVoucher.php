<?php
include '../Model/conexion.php';
session_start();
if (!isset($_SESSION['user'])) { http_response_code(403); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remito_v'], $_POST['monto'])) {
    $id = $conn->real_escape_string($_POST['id_remito_v']);
    $monto = floatval($_POST['monto']);
    $sql = "UPDATE voucher SET Monto = '$monto' WHERE id_remito_v = '$id'";
    if ($conn->query($sql)) {
        echo 'OK';
    } else {
        http_response_code(500);
        echo 'ERROR';
    }
} else {
    http_response_code(400);
    echo 'ERROR';
}
?>