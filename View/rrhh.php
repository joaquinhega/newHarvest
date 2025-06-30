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
    <title>Menu RRHH</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="rrhh-container">
        <header class="rrhh-header">
            <img src="../assets/logo-newHarvest.png" alt="New Harvest Logo">
            <h1>Panel de Administración RRHH</h1>
            <a href="../Controller/cerrarSesion.php" class="logout-btn"><b>Cerrar Sesión</b></a>
        </header>

        <div class="dashboard-cards">
            <a href="rrhhVoucher.php" class="dashboard-card">
                <div class="card-icon"></div> <h2>VOUCHER</h2>
                <p>Gestión de vouchers de pago y beneficios.</p>
            </a>
            <a href="rrhhCombustible.php" class="dashboard-card">
                <div class="card-icon"></div> <h2>COMBUSTIBLE</h2>
                <p>Administración de registros y asignaciones de combustible.</p>
            </a>
            <a href="listaEmpresa.php" class="dashboard-card">
                <div class="card-icon"></div> <h2>EMPRESA</h2>
                <p>Configuración y gestión de datos de la empresa.</p>
            </a>
        </div>
    </div>
</body>
</html>