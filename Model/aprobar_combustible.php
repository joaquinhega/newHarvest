<?php
include 'conexion.php';
$id = $_POST['id'];

    $sql = "UPDATE combustible SET aprobado = 1 WHERE id_remito_c='$id'";
    if ($conn->query($sql) === TRUE ) {
        echo "<script>alert('Remito guardado exitosamente.'); window.location.href='../View/rrhhCombustible.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.location.href='../View/rrhhCombustible.php';</script>";
    }
$conn->close();
?>
