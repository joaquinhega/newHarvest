<?php 
include '../Model/RemitoC.php'; 
include '../Model/conexion.php';

session_start();
if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
}
$letra_chofer = $_SESSION['letra'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Combustible</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <header>
        <h1>Combustible</h1>
    </header>

    <div class="choferAcc-container"> 
        <div class="form-container"> 
            <form action="../Model/guardar_combustible.php" method="post">
                <div>
                    <label for="id_remito">n° Remito:</label>
                    <input type="text" name="id_remito" style="width: 100px;" autocomplete="off" required>
                </div>
                <div>
                    <label for="monto">Monto:</label>
                    <input type="number" name="monto" autocomplete="off" required>
                </div>
                <div>
                    <label for="patente">Patente:</label>
                    <input type="text" name="patente" required>
                </div>
                <div>
                    <label for="fecha">Fecha:</label>
                    <input type="date" name="fecha" required>
                </div>

                <input type="submit" value="Guardar">
            </form>
            <a href="chofer.php"><button class="boton-volver">Volver</button></a>
        </div>
    </div>
</body>
</html>