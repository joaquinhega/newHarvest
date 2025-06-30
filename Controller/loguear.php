<?php
include '../Model/conexion.php';
session_start();

$user = $_POST['user'];
$pass = md5($_POST['pass']);

$queryExiste = "SELECT COUNT(*) AS contar, Usuario, Rol, Letra, Nombre FROM usuario WHERE Usuario='$user' AND Contrasena ='$pass';";
$resultadoExiste = mysqli_query($conn, $queryExiste);
$array = mysqli_fetch_assoc($resultadoExiste);

if ($array['contar'] > 0) {
    $_SESSION['user'] = $array['Usuario'];
    $_SESSION['rol'] = $array['Rol'];
    $_SESSION['letra'] = $array['Letra'];
    $_SESSION['nombre'] = $array['Nombre'];

    if ($_SESSION['rol'] == 'chofer') {
        header('Location: ../View/chofer.php');
    } else {
        header('Location: ../View/rrhhVoucher.php');
    }
    exit();
} else {
    header("Location: ../index.php?error=Datos%20erroneos");
    exit();
}
?>