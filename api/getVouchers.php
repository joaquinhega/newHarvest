<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Model/conexion.php';
include_once 'cors.php';

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

$letra = isset($input['letraChofer']) ? strtoupper(trim($input['letraChofer'])) : '';
$user = isset($input['user']) ? $input['user'] : '';
$page = isset($input['page']) ? intval($input['page']) : 1;
$limit = isset($input['limit']) ? intval($input['limit']) : 100;

$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM voucher WHERE SUBSTRING(id_remito_v, 1, 1) = '$letra' AND borrado = 0 ORDER BY CAST(SUBSTRING(id_remito_v, 2) AS UNSIGNED) DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$vouchers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
}

echo json_encode([
    'vouchers' => $vouchers
], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$conn->close();
?>