<?php
include '../Model/conexion.php';

$tipo = $_POST['tipo'] ?? '';
$search_term = $_POST['search_term'] ?? '';
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = 20;

$where = "1=1";
if ($tipo === 'noaprobados') $where .= " AND aprobado = 0";
if ($tipo === 'aprobados') $where .= " AND aprobado = 1";
if ($search_term !== '') {
    $search_term_escaped = $conn->real_escape_string($search_term);
    $where .= " AND (patente LIKE '%{$search_term_escaped}%' OR nombre LIKE '%{$search_term_escaped}%' OR id_remito_c LIKE '%{$search_term_escaped}%')";
}
if ($fecha_desde !== '') {
    $fecha_desde_escaped = $conn->real_escape_string($fecha_desde);
    $where .= " AND Fecha >= '{$fecha_desde_escaped}'";
}
if ($fecha_hasta !== '') {
    $fecha_hasta_escaped = $conn->real_escape_string($fecha_hasta);
    $where .= " AND Fecha <= '{$fecha_hasta_escaped}'";
}

$countSql = "SELECT COUNT(*) AS total FROM combustible WHERE $where";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ($records_per_page > 0) ? ceil($totalRecords / $records_per_page) : 0;
$offset = ($page - 1) * $records_per_page;

$sql = "SELECT * FROM combustible WHERE $where ORDER BY Fecha DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

$htmlOutput = '';
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()):
        $id = htmlspecialchars($row['id_remito_c'] ?? '');
        $monto = htmlspecialchars($row['Monto'] ?? '');
        $patente = htmlspecialchars($row['patente'] ?? '');
        $fecha = htmlspecialchars($row['Fecha'] ?? '');
        $nombre = htmlspecialchars($row['nombre'] ?? '');

        $htmlOutput .= '<tr>';
        $htmlOutput .= '<td>' . $id . '</td>';        // ID
        $htmlOutput .= '<td>' . $monto . '</td>';     // Monto
        $htmlOutput .= '<td>' . $patente . '</td>';   // Patente
        $htmlOutput .= '<td>' . $fecha . '</td>';     // Fecha
        $htmlOutput .= '<td>' . $nombre . '</td>';    // Nombre del Chofer

        if ($tipo === 'noaprobados') {
            $htmlOutput .= '<td>
                <button type="button" class="aprobar-btn" data-id="' . $id . '">Aprobar</button>
            </td>';
        }
        $htmlOutput .= '</tr>';
    endwhile;
} else {
    $colspan = ($tipo === 'noaprobados') ? 6 : 5;
    $htmlOutput .= '<tr><td colspan="' . $colspan . '" class="no-results">No hay resultados</td></tr>';
}

$pagesPerRow = 20;
$paginationHtml = '<div class="pagination-container" style="padding:10px 6px;">';
if ($totalPages > 0) {
    $paginationHtml .= '<table class="pagination-grid" style="width:100%; border-collapse:separate; border-spacing:6px;">';
    $pageNumber = 1;
    while ($pageNumber <= $totalPages) {
        $paginationHtml .= '<tr>';
        for ($col = 0; $col < $pagesPerRow && $pageNumber <= $totalPages; $col++, $pageNumber++) {
            $isActive = ($pageNumber == $page);
            $buttonClass = $isActive ? 'active-page' : 'page-number';
            $paginationHtml .= '<td style="padding:2px;">';
            $paginationHtml .= '<a href="#" class="page-link ' . $buttonClass . '" data-page="' . htmlspecialchars($pageNumber) . '">' . htmlspecialchars($pageNumber) . '</a>';
            $paginationHtml .= '</td>';
        }
        $paginationHtml .= '</tr>';
    }
    $paginationHtml .= '</table>';
}
$paginationHtml .= '</div>';

header('Content-Type: application/json');
echo json_encode([
    'html' => $htmlOutput,
    'pagination' => $paginationHtml,
    'totalRecords' => $totalRecords
]);
?>