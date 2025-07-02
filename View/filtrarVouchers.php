<?php
include '../Model/conexion.php';

// Parámetros de filtrado
$tipo = $_POST['tipo'] ?? '';
$search_term = $_POST['search_term'] ?? '';
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$id_empresa = $_POST['id_empresa'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = 10;

// WHERE base
$where = "borrado = 0";
if ($id_empresa !== '') {
    $id_empresa_escaped = $conn->real_escape_string($id_empresa);
    $where .= " AND id_empresa = '$id_empresa_escaped'";
}
if ($tipo === 'noaprobados') $where .= " AND aprobado = 0";
if ($tipo === 'aprobados') $where .= " AND aprobado = 1";

if ($search_term !== '') {
    $search_term_escaped = $conn->real_escape_string($search_term);
    $where .= " AND (Empresa LIKE '%{$search_term_escaped}%' OR nombre_pasajero LIKE '%{$search_term_escaped}%' OR Origen LIKE '%{$search_term_escaped}%' OR Destino LIKE '%{$search_term_escaped}%')";
}
if ($fecha_desde !== '') {
    $fecha_desde_escaped = $conn->real_escape_string($fecha_desde);
    $where .= " AND Fecha >= '{$fecha_desde_escaped}'";
}
if ($fecha_hasta !== '') {
    $fecha_hasta_escaped = $conn->real_escape_string($fecha_hasta);
    $where .= " AND Fecha <= '{$fecha_hasta_escaped}'";
}

// Paginación
$countSql = "SELECT COUNT(*) AS total FROM voucher WHERE $where";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $records_per_page);
$offset = ($page - 1) * $records_per_page;

$sql = "SELECT * FROM voucher WHERE $where ORDER BY Fecha DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

// Obtener empresas para el select (si hace falta)
$empresas = [];
if ($tipo === 'aprobados' && $id_empresa === '') {
    $empresasResult = $conn->query("SELECT id_empresa, nombre FROM empresa WHERE borrado = 0 ORDER BY nombre ASC");
    while ($e = $empresasResult->fetch_assoc()) {
        $empresas[] = $e;
    }
}

$htmlOutput = '';
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()):
        $fechaFormateada = date('d-m-Y', strtotime($row['Fecha']));
        $htmlOutput .= '<tr>';
        $htmlOutput .= '<td class="fecha-col">' . $fechaFormateada . '</td>';
        $htmlOutput .= '<td>' . $row['id_remito_v'] . '</td>';
        $htmlOutput .= '<td>' . $row['nombre_pasajero'] . '</td>';
        // Solo muestra la columna Empresa si NO se está filtrando por empresa
        if ($id_empresa === '') {
            $htmlOutput .= '<td>' . $row['Empresa'] . '</td>';
        }
        $htmlOutput .= '<td>' . $row['Origen'] . ' (' . $row['hora_origen'] . 'hs)</td>';
        $htmlOutput .= '<td>' . $row['Destino'] . ' (' . $row['hora_destino'] . 'hs)</td>';
        $htmlOutput .= '<td>' . $row['observaciones'] . '</td>';
        $htmlOutput .= '<td>' . $row['tiempo_espera'] . '</td>';

        // Acciones
        if ($tipo === 'noaprobados' && $id_empresa === '') {
            // Solo aprobar
            $htmlOutput .= '<td>';
            $htmlOutput .= '<form action="../Model/aprobar_voucher.php" method="post">';
            $htmlOutput .= '<input type="hidden" name="id" value="' . $row['id_remito_v'] . '">';
            $htmlOutput .= '<input type="submit" value="Aprobar">';
            $htmlOutput .= '</form>';
            $htmlOutput .= '</td>';
        } elseif ($tipo === 'aprobados' && $id_empresa === '') {
            // Botón Mover (abre mini modal JS)
            $htmlOutput .= '<td>';
            $htmlOutput .= '<button class="btn-mover" data-id="' . $row['id_remito_v'] . '">Mover</button>';
            $htmlOutput .= '</td>';
        } else {
            // Solo PDF (empresa.php)
            $monto = is_null($row['Monto']) ? '' : $row['Monto'];
            $montoMostrar = ($monto === '' || $monto === null) ? 'N/A' : '$' . number_format($monto, 0, '', '.');
            $htmlOutput .= '<td>' . $montoMostrar . '</td>';
            $htmlOutput .= '<td>';
            $htmlOutput .= '<button type="button" style=" width:100px " class="btn-monto-pdf" data-id="' . $row['id_remito_v'] . '" data-monto="' . htmlspecialchars($monto) . '">PDF</button>';
            $htmlOutput .= '</td>';
        }
        $htmlOutput .= '</tr>';
    endwhile;
} else {
    $colspan = ($id_empresa === '') ? 10 : 9;
    $htmlOutput .= '<tr><td colspan="' . $colspan . '" style="text-align:center;">No hay resultados</td></tr>';
}

// Mini modal para mover (solo una vez, fuera del loop)
$miniModal = '';
if ($tipo === 'aprobados' && $id_empresa === '') {
    $miniModal = '
<div id="mini-modal-mover" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);justify-content:center;align-items:center;">
    <div style="background:#fff;padding:24px 18px 18px 18px;border-radius:8px;min-width:260px;max-width:90vw;box-shadow:0 8px 32px #0002;position:relative;">
        <span id="close-mini-modal" style="position:absolute;right:12px;top:8px;font-size:1.5em;cursor:pointer;">&times;</span>
        <form id="form-mover-voucher" method="post" action="../Model/asignarEmpresa.php" target="_self">
            <input type="hidden" name="id_remito_v" id="mini-modal-id-remito">
            <label for="mini-modal-select-empresa">Seleccionar empresa:</label>
            <select name="id_empresa" id="mini-modal-select-empresa" required>
                <option value="">Seleccionar empresa</option>';
    foreach ($empresas as $empresa) {
        $miniModal .= '<option value="' . $empresa['id_empresa'] . '">' . htmlspecialchars($empresa['nombre']) . '</option>';
    }
    $miniModal .= '
                <option value="desaprobar">Desaprobar voucher</option>
            </select>
            <br><br>
            <input type="submit" value="Mover">
        </form>
    </div>
</div>
';
}

$htmlOutput .= $miniModal;

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