<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['rol'])) {
    echo "Error: Sesión no válida. Por favor, inicie sesión nuevamente.";
    exit();
}

$rol = $_SESSION['rol'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remito_v'])) {
    $id = $_POST['id_remito_v'];

    mysqli_begin_transaction($conn);

    $sql = "UPDATE voucher SET borrado = 1 WHERE id_remito_v = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $id);
        $resultado = $stmt->execute();

        if ($resultado) {
            mysqli_commit($conn);
            echo "OK";
        } else {
            mysqli_rollback($conn);
            echo "Error al eliminar el voucher: " . $stmt->error;
        }
        $stmt->close();
    } else {
        mysqli_rollback($conn);
        echo "Error de preparación de la consulta: " . $conn->error;
    }
} else {
    echo "Error: No se ha proporcionado un ID de voucher válido o el método de solicitud es incorrecto.";
}

$conn->close();
?>