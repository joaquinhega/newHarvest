<?php
include 'conexion.php';

session_start(); 

if (isset($_GET['id_remito_v'])) {
    $id = $_GET['id_remito_v'];
} else {
    echo "Error: No se ha proporcionado un ID de evento válido.";
    exit();
}
$rol = $_SESSION['rol'];

// Consultar el voucher
$sql = "SELECT * FROM voucher WHERE id_remito_v='$id'";
$resultado = $conn->query($sql);

if ($fila = mysqli_fetch_assoc($resultado)) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Voucher</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="choferAcc-container">
        <header>
            <h2>Editar Voucher</h2>
        </header>
        <div class="form-container">
            <form action="" method="POST">
                <!-- Campos del formulario -->
                <div>
                    <label for="id_remito">n° Remito:</label>
                    <input type="text" name="id_remito" required style="width: 100px;" value="<?php echo $fila['id_remito_v']; ?>" readonly>
                </div>
                <div>
                    <label for="empresa">Empresa:</label>
                    <input type="text" name="empresa" required value="<?php echo $fila['Empresa']; ?>">
                </div>
                <div>
                    <label for="origen">Origen:</label>
                    <input type="text" name="origen" required value="<?php echo $fila['Origen']; ?>">
                </div>
                <div>
                    <label for="hora_origen">Hora de Origen:</label>
                    <input type="text" name="hora_origen" style="width: 100px;" autocomplete="off" value="<?php echo $fila['hora_origen']; ?>">
                </div>
                <div>
                    <label for="destino">Destino:</label>
                    <input type="text" name="destino" required value="<?php echo $fila['Destino']; ?>">
                </div>
                <div>
                    <label for="hora_destino">Hora de Destino:</label>
                    <input type="text" name="hora_destino" style="width: 100px;" autocomplete="off" value="<?php echo $fila['hora_destino']; ?>">
                </div>
                <div>
                    <label for="fecha">Fecha:</label>
                    <input type="date" name="fecha" required value="<?php echo $fila['Fecha']; ?>">
                </div>
                <div>
                    <label for="tiempo_espera">Tiempo de espera(*):</label>
                    <input type="text" name="tiempo_espera" value="<?php echo $fila['tiempo_espera']; ?>">
                </div>
                <div>
                    <label for="observaciones">Observaciones:</label>
                    <textarea name="observaciones" rows="5" cols="50"><?php echo $fila['observaciones']; ?></textarea>
                </div>
                <input type="submit" value="Editar">
            </form>
            <a href="<?php echo ($rol == 'chofer') ? '../View/choferVerVouchers.php' : '../View/rrhhVoucher.php'; ?>">
            <button class="boton-volver">Cancelar</button> </a>
        </div>    
    </div>
</body>
</html>
<?php 
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_remito = $_POST['id_remito'];
    $empresa = $_POST['empresa'];
    $origen = $_POST['origen'];
    $hora_origen = $_POST['hora_origen'];
    $destino = $_POST['destino'];
    $hora_destino = $_POST['hora_destino'];
    $fecha = $_POST['fecha'];
    $tiempo_espera = $_POST['tiempo_espera'];
    $observaciones = $_POST['observaciones'];

    if ($id_remito && $empresa && $origen && $hora_origen && $destino && $hora_destino && $fecha) {
        mysqli_begin_transaction($conn); // Inicia la transacción

        $sql_update = "UPDATE voucher 
                       SET id_remito_v='$id_remito', empresa='$empresa', origen='$origen', 
                           hora_origen='$hora_origen', destino='$destino', hora_destino='$hora_destino', 
                           fecha='$fecha', tiempo_espera='$tiempo_espera', observaciones='$observaciones' 
                       WHERE id_remito_v='$id'";

        $resultado_update = mysqli_query($conn, $sql_update);

        if ($resultado_update) {
            mysqli_commit($conn); // Realiza commit

            // Redirigir según el rol
            if ($rol === 'chofer') {
                header('Location: ../View/choferVerVouchers.php');
                exit(); // Detener el script después de redirigir
            } else {
                header('Location: ../View/rrhhVoucher.php');
                exit();
            }
        } else {
            mysqli_rollback($conn); // Deshace cambios si hay error
            echo "Error al actualizar el voucher: " . mysqli_error($conn);
        }
    } else {
        echo "Por favor, completa todos los campos obligatorios.";
    }
}
?>