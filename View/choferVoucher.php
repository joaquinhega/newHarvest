<?php 
include '../Model/RemitoV.php'; 
include '../Model/conexion.php';
session_start();

if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
}
# Asegura que los valores de los inputs no esten vacios
$id_remito = isset($_GET['id_remito']) ? $_GET['id_remito'] : '';
$empresa = isset($_GET['empresa']) ? $_GET['empresa'] : '';
$origen = isset($_GET['origen']) ? $_GET['origen'] : '';
$hora_origen = isset($_GET['hora_origen']) ? $_GET['hora_origen'] : '';
$destino = isset($_GET['destino']) ? $_GET['destino'] : '';
$hora_destino = isset($_GET['hora_destino']) ? $_GET['hora_destino'] : '';
$tiempo_espera = isset($_GET['tiempo_espera']) ? $_GET['tiempo_espera'] : '';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$observaciones = isset($_GET['observaciones']) ? $_GET['observaciones'] : '';
$letra_chofer = $_SESSION['letra'];
# Consulta el ultimo id remito que se cargó 
$ultimo_remito = obtenerUltimoRemitoPorChofer($letra_chofer, $conn);
if ($ultimo_remito) {
    $numero = (int)substr($ultimo_remito, 1); 
    $siguiente_remito = $letra_chofer . str_pad($numero + 1, 3, '0', STR_PAD_LEFT); 
} else {
    $siguiente_remito = $letra_chofer . '001'; 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Voucher</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="choferAcc-container">
        <header>
            <h2>Voucher</h2>
        </header>
        <div class="form-container">
            <form action="firma.php" method="post">
                <div>
                    <label for="id_remito">n° Remito:</label>
                    <input type="text" name="id_remito" required style="width: 100px;" readonly value="<?php echo htmlspecialchars($siguiente_remito); ?>">
                </div>
                <div>
                    <label for="empresa">Empresa:</label>
                    <input type="text" name="empresa" required value="<?php echo htmlspecialchars($empresa); ?>">
                </div>
                <div>
                    <label for="origen">Origen:</label>
                    <input type="text" name="origen" autocomplete="off" required value="<?php echo htmlspecialchars($origen); ?>">
                </div>
                <div>
                    <label for="hora_origen">Hora:</label>
                    <input type="text" name="hora_origen" autocomplete="off"  style="width: 150px;" id="hora_origen"required value="<?php echo htmlspecialchars($hora_origen); ?>">
                </div>
                <div>
                    <label for="destino">Destino:</label>
                    <input type="text" name="destino" required value="<?php echo htmlspecialchars($destino); ?>">
                </div>
                <div>
                    <label for="hora_destino">Hora:</label>
                    <input type="text" name="hora_destino" style="width: 150px;" autocomplete="off" required value="<?php echo htmlspecialchars($hora_destino); ?>">
                </div>
                <div>
                    <label for="fecha">Fecha:</label>
                    <input type="date" name="fecha" required value="<?php echo htmlspecialchars($fecha); ?>">
                </div>
                <div>
                    <label for="tiempo_espera">Tiempo de espera(*):</label>
                    <input type="text" name="tiempo_espera" autocomplete="off" value="<?php echo htmlspecialchars($tiempo_espera); ?>">
                </div>
                <div>
                    <label for="observaciones">Observaciones(*):</label>
                    <textarea name="observaciones" rows="5" cols="65" autocomplete="off" ></textarea>
                </div>
                <input type="submit" value="Cargar">
            </form>
            <a href="chofer.php"><button class="boton-volver">Volver</button></a>
        </div>
    </div>
</body>
</html>