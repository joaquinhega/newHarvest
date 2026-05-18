<?php
include '../Model/conexion.php';
include_once 'cors.php';

$rawData = file_get_contents("php://input");

$data = json_decode($rawData);

$user = $data->user;
$pass = md5($data->pass);

$queryExiste = "SELECT COUNT(*) AS contar, Usuario, Rol, Letra, Nombre FROM usuario WHERE Usuario='$user' AND Contrasena ='$pass';";

$resultadoExiste = mysqli_query($conn, $queryExiste);

$array = mysqli_fetch_assoc($resultadoExiste);

if ($array['contar'] > 0) {
    error_log("[LOGIN OK] Login exitoso para el usuario: " . $array['Usuario'] . " con rol: " . $array['Rol']);
    echo json_encode([
        'success' => true,
        'user' => $array['Usuario'],
        'rol' => $array['Rol'],
        'letra' => $array['Letra'],
        'nombre' => $array['Nombre'],
        'message' => 'Login exitoso',
    ]);
} else {
    error_log("[LOGIN FALLO] Intento de login fallido para el usuario: " . $user . ". Credenciales incorrectas.");
    echo json_encode([
        'success' => false,
        'message' => 'Credenciales incorrectas',
        'error' => 'Credenciales incorrectas',
    ]);
}

mysqli_close($conn);
?>