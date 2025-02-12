<?php 
session_start();
if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Chofer</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="chofer-container">
        <header>
            <img src="../logo-newHarvest.png" alt="New Harvest Logo">
        </header>
        <div class="chofer-botones">
            <a href="choferVoucher.php"><button><b>VOUCHER</b></button></a><br>
            <a href="choferCombustible.php"><button><b>COMBUSTIBLE</b></button></a><br>
            <a href="choferVerVouchers.php"><button><b>VER MIS VOUCHERS</b></button></a><br>
        </div>
    </div>
    <a href="../Controller/cerrarSesion.php"><button class="logout-btn"><b>Cerrar Sesión</b></button></a><br>
    </body>
</html>