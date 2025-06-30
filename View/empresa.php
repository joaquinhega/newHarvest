<?php
include '../Model/conexion.php';

session_start();
if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
}
if (isset($_GET['id_empresa'])) {
    $id = $_GET['id_empresa'];
} else {
    echo "Error: No se ha proporcionado un ID de evento válido.";
    exit();
}

$sql = "SELECT * FROM voucher WHERE borrado = 0 AND id_empresa = '$id' ORDER BY Fecha DESC ";
$resultVouchers = $conn->query($sql);

$sqlEmpresa = "SELECT nombre FROM empresa WHERE id_empresa = '$id';";
$resultEmpresa = $conn->query($sqlEmpresa);
$nombreEmpresa = $resultEmpresa->fetch_assoc()['nombre'];?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Vouchers</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="rrhh-container">
        <header>
            <img src="../assets/logo-newHarvest.png" alt="New Harvest Logo">
        </header>
        <div class="styled-table">
            <h2><?php echo $nombreEmpresa;?></h2>
            <table>
                <tr>
                    <th>ID Remito</th>
                    <th>Pasajero</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Observaciones</th>
                    <th>Tiempo de Espera</th>
                    <th>Generar PDF</th>
                </tr>
                <?php while($row = $resultVouchers->fetch_assoc()): 
                    if($row['aprobado'] == 1){ ?>
                <tr>
                    <td><?= $row['id_remito_v'] ?></td>
                    <td><?= $row['nombre_pasajero'] ?></td>
                    <td><?= $row['Origen'] . "({$row['hora_origen']}hs)" ?></td>
                    <td><?= $row['Destino'] . "({$row['hora_destino']}hs)" ?></td>
                    <td><?= $row['Fecha'] ?></td>
                    <td><?= $row['Monto'] ?></td>
                    <td><?= $row['observaciones'] ?></td>
                    <td><?= $row['tiempo_espera'] ?></td>
<td>
                        <form action="../Controller/generarPdf.php" method="post" target="_blank">
                            <input type="hidden" name="id" value="<?= $row['id_remito_v'] ?>">
                            <input type="submit" value="PDF">
                        </form>
                    </td>
                </tr>
                <?php } endwhile; ?>
            </table>
        </div>
        <a href="../Model/eliminarEmpresa.php?id_empresa=<?= $id?>"><button class="eliminar-btn">Eliminar Empresa</button></a>
        <a href="listaEmpresa.php"> <button class="logout-btn">Volver</button></a>
    </div>
</body>
</html>