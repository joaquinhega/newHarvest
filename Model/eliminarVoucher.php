<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['rol'])) {
    header('Location: ../index.php'); 
    exit();
}

$rol = $_SESSION['rol'];

if (isset($_GET['id_remito_v'])) {
    $id = $_GET['id_remito_v'];

    if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
        // Iniciar la transacción
        mysqli_begin_transaction($conn);

        // Marcar el voucher como borrado
        $sql = "UPDATE voucher SET borrado = 1 WHERE id_remito_v = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $resultado = $stmt->execute();

        if ($resultado) {
            mysqli_commit($conn);
            echo "<script>alert('Voucher eliminado exitosamente.');</script>";

            // Redirigir según el rol del usuario
            if ($rol === 'chofer') {
                header('Location: ../View/choferVerVouchers.php'); // Vista del chofer
            } elseif ($rol === 'rrhh') {
                header('Location: ../View/rrhhVoucher.php'); // Vista de RRHH
            }
            exit();
        } else {
            mysqli_rollback($conn);
            echo "<script>alert('Error al eliminar el voucher: " . $conn->error . "');</script>";
        }
    }
} else {
    echo "<script>alert('Error: No se ha proporcionado un ID de voucher válido.');</script>";
    echo "<script>window.location.href = '../View/choferVerVouchers.php';</script>"; // Redirección por defecto
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Voucher</title>
</head>
<body>
<script>
    // Confirmación antes de eliminar
    if (confirm("Estas a punto de eliminar el voucher '<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>', ¿estás seguro que deseas eliminarlo?")) {
        window.location.href = "eliminarVoucher.php?id_remito_v=<?php echo $id; ?>&confirm=true";
    } else {
        // Redirección según el rol si se cancela
        if ("<?php echo $rol; ?>" === 'chofer') {
            window.location.href = "../View/choferVerVouchers.php";
        } else if ("<?php echo $rol; ?>" === 'rrhh') {
            window.location.href = "../View/rrhhVoucher.php";
        } else {
            window.location.href = "../index.php"; // Redirección por defecto
        }
    }
</script>
</body>
</html>