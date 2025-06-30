<?php
include '../Model/conexion.php';

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
}

$letra = $_SESSION['letra'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$sql = "SELECT * FROM voucher WHERE SUBSTRING(id_Remito_v, 1, 1) = '$letra' AND borrado = 0 ORDER BY Fecha DESC LIMIT $limit;";
$resultVouchers = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Vouchers</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
    <script>
        function toggleViewMore() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentLimit = urlParams.get('limit') || 10;
            const newLimit = currentLimit == 10 ? 1000 : 10; // Cambia entre 10 y 1000 registros (o todos).
            urlParams.set('limit', newLimit);
            window.location.search = urlParams.toString();
        }
    </script>
</head>
<body>
    <div class="choferVer-container">
        <header>
            <img src="../assets/logo-newHarvest.png" alt="New Harvest Logo">
        </header>
        <div class="styled-table">
            <h2>Vouchers de <?php echo $_SESSION['nombre']; ?></h2>
            <table>
                <tr>
                    <th>ID Remito</th>
                    <th>Empresa</th>
                    <th>Pasajero</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Fecha</th>
                    <th>Observaciones</th>
                    <th>Tiempo de Espera</th>
                    <th></th>
                </tr>
                <?php while ($row = $resultVouchers->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id_remito_v'] ?></td>
                    <td><?= $row['Empresa'] ?></td>
                    <td><?= $row['nombre_pasajero'] ?></td>
                    <td><?= $row['Origen'] . " ({$row['hora_origen']}hs)" ?></td>
                    <td><?= $row['Destino'] . " ({$row['hora_destino']}hs)" ?></td>
                    <td><?= $row['Fecha'] ?></td>
                    <td><?= $row['observaciones'] ?></td>
                    <td><?= $row['tiempo_espera'] ?></td>
                    <td>
                        <div class="boton-acciones">
                            <a href="../Model/editarVoucher.php?id_remito_v=<?= $row['id_remito_v'] ?>"><button id="boton-editar"><img src="../assets/boton-editar.png" width="20px" height="20px"></button></a>
                            <a href="../Model/eliminarVoucher.php?id_remito_v=<?= $row['id_remito_v'] ?>"><button id="boton-eliminar"><img src="../assets/boton-eliminar.png" width="20px" height="20px"></button></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <button onclick="toggleViewMore()" class="ver-mas-btn">
            <?php echo $limit == 10 ? "Ver más" : "Ocultar"; ?>
        </button>
        <a href="chofer.php"><button class="logout-btn">Volver</button></a>
    </div>
</body>
</html>