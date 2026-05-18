<?php
include 'conexion.php';

header('Content-Type: application/json'); 

$companies = []; 
$sql = "SELECT id_empresa, nombre FROM empresa WHERE borrado = 0 ORDER BY nombre ASC"; 
$result = $conn->query($sql); 

if ($result) { 
    while ($row = $result->fetch_assoc()) { 
        $companies[] = $row; 
    }
}

echo json_encode($companies); 
$conn->close(); 
?>