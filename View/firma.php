<?php 
session_start();
if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
    exit();
}
# Obtiene valores del formulario anterior para cargar toda la data junta
$id_remito = $_POST['id_remito'];
$empresa = $_POST['empresa'];
$origen = $_POST['origen'];
$hora_origen = $_POST['hora_origen'];
$destino = $_POST['destino'];
$hora_destino = $_POST['hora_destino'];
$tiempo_espera = $_POST['tiempo_espera'];
$fecha = $_POST['fecha'];
$observaciones = $_POST['observaciones'];
$nombre_pasajero = isset($_GET['nombre_pasajero']) ? $_GET['nombre_pasajero'] : '';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma del Voucher</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="choferAcc-container">
        <header>
            <h2>Voucher</h2>
        </header>
        <div class="form-container">
        <h2>Pasajero</h2>
        <form action="../Model/guardar_voucher.php" method="post" id="voucherForm">
            <div>
                <label for="nombre_pasajero">Nombre:</label>
                <input type="text" name="nombre_pasajero" autocomplete="off" value="<?php echo htmlspecialchars($nombre_pasajero); ?>">
            </div>
            <canvas id="signature-pad" width=400 height=300 style="border:1px solid black;"></canvas><br>
            <input type="hidden" id="signature-data" name="signature">
            <!-- Asigna los valores del formulario anterior a este -->
            <input type="hidden" name="id_remito" value="<?php echo $id_remito; ?>">
            <input type="hidden" name="empresa" value="<?php echo $empresa; ?>">
            <input type="hidden" name="origen" value="<?php echo $origen; ?>">
            <input type="hidden" name="hora_origen" value="<?php echo $hora_origen; ?>">
            <input type="hidden" name="destino" value="<?php echo $destino; ?>">
            <input type="hidden" name="hora_destino" value="<?php echo $hora_destino; ?>">
            <input type="hidden" name="tiempo_espera" value="<?php echo $tiempo_espera; ?>">
            <input type="hidden" name="fecha" value="<?php echo $fecha; ?>">
            <input type="hidden" name="observaciones" value="<?php echo htmlspecialchars($observaciones); ?>">

            <input type="submit" value="Confirmar Firma y Enviar">
            <button id="clear" type="button">Limpiar</button>
            <button id="null-signature" type="button">Cargar sin Firma</button>
        </form>
        <!-- Si vuelve, se lleva los valores del formulario para que no se borren -->
        <a href="choferVoucher.php?id_remito=<?php echo urlencode($id_remito); ?>&empresa=<?php echo urlencode($empresa); ?>&origen=<?php echo urlencode($origen); ?>&hora_origen=<?php echo urlencode($hora_origen); ?>&destino=<?php echo urlencode($destino); ?>&hora_destino=<?php echo urlencode($hora_destino); ?>&tiempo_espera=<?php echo urlencode($tiempo_espera); ?>&fecha=<?php echo urlencode($fecha); ?>&observaciones=<?php echo urlencode($observaciones); ?>">
        <button type="button" class="boton-volver">Volver</button></a>
        </div>
    </div>

<script>
    // Espacio de trabajo de firma, guarda el dibujo o lo limpia (con JS)
    var canvas = document.getElementById('signature-pad');
    var signaturePad = new SignaturePad(canvas);
    var signatureDataInput = document.getElementById('signature-data');
    var isNullSignature = false; 

    document.getElementById('clear').addEventListener('click', function () {
        signaturePad.clear();
    });

    document.getElementById('null-signature').addEventListener('click', function () {
        isNullSignature = true;
        signatureDataInput.value = null;
        document.getElementById('voucherForm').submit(); 
    });

    document.querySelector('form').addEventListener('submit', function (event) {
        if (!isNullSignature && signaturePad.isEmpty()) {
            alert('Por favor, dibuja la firma.');
            event.preventDefault();
        } else if (!isNullSignature) {
            signatureDataInput.value = signaturePad.toDataURL();
        }
    });
</script>
</body>
</html>