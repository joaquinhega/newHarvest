<?php
// filepath: c:\xampp\htdocs\newHarvestDes\View\filtrarCombustible.php
include '../Model/conexion.php';

$tipo = $_POST['tipo'] ?? '';
$search_term = $_POST['search_term'] ?? '';
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = 10;

$where = "1=1";
if ($tipo === 'noaprobados') $where .= " AND aprobado = 0";
if ($tipo === 'aprobados') $where .= " AND aprobado = 1";
if ($search_term !== '') {
    $search_term_escaped = $conn->real_escape_string($search_term);
    $where .= " AND (patente LIKE '%{$search_term_escaped}%' OR nombre LIKE '%{$search_term_escaped}%')";
}
if ($fecha_desde !== '') {
    $fecha_desde_escaped = $conn->real_escape_string($fecha_desde);
    $where .= " AND Fecha >= '{$fecha_desde_escaped}'";
}
if ($fecha_hasta !== '') {
    $fecha_hasta_escaped = $conn->real_escape_string($fecha_hasta);
    $where .= " AND Fecha <= '{$fecha_hasta_escaped}'";
}

// Contar el total de registros para la paginación
$countSql = "SELECT COUNT(*) AS total FROM combustible WHERE $where";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $records_per_page);
$offset = ($page - 1) * $records_per_page;

$sql = "SELECT * FROM combustible WHERE $where ORDER BY Fecha DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

$htmlOutput = '';
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()):
        $htmlOutput .= '<tr>';
        $htmlOutput .= '<td>' . $row['id_remito_c'] . '</td>';
        $htmlOutput .= '<td>' . $row['Monto'] . '</td>';
        $htmlOutput .= '<td>' . $row['patente'] . '</td>';
        $htmlOutput .= '<td>' . $row['Fecha'] . '</td>';
        $htmlOutput .= '<td>' . $row['nombre'] . '</td>';
        if ($tipo === 'noaprobados') {
            $htmlOutput .= '<td>
                <form action="../Model/aprobar_combustible.php" method="post">
                    <input type="hidden" name="id" value="' . $row['id_remito_c'] . '">
                    <input type="submit" value="Aprobar">
                </form>
            </td>';
        }
        $htmlOutput .= '</tr>';
    endwhile;
} else {
    $colspan = ($tipo === 'noaprobados') ? 6 : 5;
    $htmlOutput .= '<tr><td colspan="' . $colspan . '" class="no-results">No hay resultados</td></tr>';
}

// Paginación
$paginationHtml = '<div class="pagination-controls">';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active-page' : '';
    $paginationHtml .= '<a href="#" class="page-link ' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
}
$paginationHtml .= '</div>';

header('Content-Type: application/json');
echo json_encode([
    'html' => $htmlOutput,
    'pagination' => $paginationHtml,
    'totalRecords' => $totalRecords
]);
?>