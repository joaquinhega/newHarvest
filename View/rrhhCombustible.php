<?php
include '../Model/conexion.php';
session_start();
$mostrarAprobados = isset($_POST['toggle_aprobados']) ? $_POST['toggle_aprobados'] === 'mostrar' : false;
$sql = "SELECT * FROM combustible WHERE aprobado = 0 ORDER BY Fecha DESC";
$resultNoaprobados = $conn->query($sql);

if ($mostrarAprobados) {
    $sql2 = "SELECT * FROM combustible WHERE aprobado = 1 ORDER BY Fecha DESC";
    $resultAprobados = $conn->query($sql2);
}
if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Combustibles</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="rrhh-container">
        <header>
            <img src="../logo-newHarvest.png" alt="New Harvest Logo">
        </header>
        <div class="styled-table">
            <h2>Remitos No Aprobados</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Monto</th>
                    <th>Patente</th>
                    <th>Fecha</th>
                    <th>Nombre del Chofer</th>
                    <th>Aprobar</th>
                </tr>
                <?php while($row = $resultNoaprobados->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id_remito_c'] ?></td>
                    <td><?= $row['Monto'] ?></td>
                    <td><?= $row['patente'] ?></td>
                    <td><?= $row['Fecha'] ?></td>
                    <td><?= $row['nombre']?></td> 
                    <td>
                        <form action="../Model/aprobar_combustible.php" method="post">
                            <input type="hidden" name="id" value="<?= $row['id_remito_c'] ?>">
                            <input type="submit" value="Aprobar">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="toggle_aprobados" value="<?= $mostrarAprobados ? 'ocultar' : 'mostrar' ?>">
            <input type="submit" class="boton-aprobados" value="<?= $mostrarAprobados ? 'Ocultar remitos aprobados' : 'Ver remitos aprobados' ?>">
        </form>

        <?php if ($mostrarAprobados): ?>
            <div class="styled-table">
                <h2>Remitos Aprobados</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Monto</th>
                        <th>Patente</th>
                        <th>Fecha</th>
                        <th>Nombre del Chofer</th>
                    </tr>
                    <?php while($row = $resultAprobados->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id_remito_c'] ?></td>
                        <td><?= $row['Monto'] ?></td>
                        <td><?= $row['patente'] ?></td>
                        <td><?= $row['Fecha'] ?></td>
                        <td><?= $row['nombre'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>
       <a href="rrhh.php"><button class="logout-btn">Volver</button></a>
    </div>
</body>
</html>