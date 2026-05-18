<?php
// Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Origin: *");
// Permitir los métodos que se usarán
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
// Permitir los headers necesarios, incluyendo los personalizados
header("Access-Control-Allow-Headers: Content-Type, Authorization, Accept, User, Letra, Nombre");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>