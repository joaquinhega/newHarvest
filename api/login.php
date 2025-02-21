<?php
include '../Model/conexion.php';

// Obtener los datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));
$user = $data->user;
$pass = md5($data->pass); // Asegúrate de que la contraseña esté cifrada igual que en la base de datos

// Consulta para verificar el usuario y contraseña
$queryExiste = "SELECT COUNT(*) AS contar, Usuario, Rol, Letra, Nombre FROM usuario WHERE Usuario='$user' AND Contrasena ='$pass';";
$resultadoExiste = mysqli_query($conn, $queryExiste);
$array = mysqli_fetch_assoc($resultadoExiste);

// Verificar si el usuario existe
if ($array['contar'] > 0) {
    // Si el login es correcto, devolver los datos en formato JSON
    echo json_encode([
        'success' => true,
        'user' => $array['Usuario'],
        'rol' => $array['Rol'],
        'letra' => $array['Letra'],
        'nombre' => $array['Nombre'],
    ]);
} else {
    // Si las credenciales son incorrectas
    echo json_encode([
        'success' => false,
        'message' => 'Credenciales incorrectas',
    ]);
}

// Cerrar la conexión
mysqli_close($conn);
?>