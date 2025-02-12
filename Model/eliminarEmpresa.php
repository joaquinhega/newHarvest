<?php
include 'conexion.php';

if (isset($_GET['id_empresa'])) {
    $id = $_GET['id_empresa'];
    
    $sqlEmpresa = "SELECT nombre FROM empresa WHERE id_empresa = '$id' AND borrado = 1;";
    $resultEmpresa = $conn->query($sqlEmpresa);
    $nombreEmpresa = $resultEmpresa->fetch_assoc()['nombre'];

    if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
        mysqli_begin_transaction($conn);

        $sql = "UPDATE empresa SET borrado = 1 WHERE id_empresa = '$id'";
        $resultado = $conn->query($sql);

        if ($resultado) {
            $sqlVouchers = "UPDATE voucher SET id_empresa = NULL WHERE id_empresa = '$id'";
            $resultadoVouchers = $conn->query($sqlVouchers);
            
            mysqli_commit($conn);
            echo "<script>alert('empresa eliminada exitosamente.'); window.location.href='../View/listaEmpresa.php';</script>";
        } else {
            mysqli_rollback($conn);
            echo "Error al eliminar la empresa: " . mysqli_error($conn);
        }
    }
} else {
    echo "Error: No se ha proporcionado un ID de empresa válido.";
}

?>
    <script>
        // Mensaje de confirmación
        if (confirm("Estas a punto de eliminar la empresa '<?php echo $nombreEmpresa; ?>', ¿estás seguro que deseas eliminarlo?")) {
            window.location.href = "eliminarEmpresa.php?id_empresa=<?php echo $id; ?>&confirm=true";
        } else {
            window.location.href = "../View/listaEmpresa.php";
        }
    </script>