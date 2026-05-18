<?php
include '../Model/conexion.php';

$tipo = $_POST['tipo'] ?? '';
$search_term = $_POST['search_term'] ?? '';
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$id_empresa = $_POST['id_empresa'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = isset($_POST['records_per_page']) ? (int)$_POST['records_per_page'] : 20;

$where = "borrado = 0";
$params = [];
$types = "";

if ($id_empresa !== '') {
    $where .= " AND id_empresa = ?";
    $types .= "i";
    $params[] = $id_empresa;
}
if ($tipo === 'noaprobados') $where .= " AND aprobado = 0";
if ($tipo === 'aprobados') $where .= " AND aprobado = 1 AND id_empresa IS NULL";

if ($search_term !== '') {
    $where .= " AND (Empresa LIKE ? OR nombre_pasajero LIKE ? OR Origen LIKE ? OR Destino LIKE ?";

    $where .= " OR LEFT(id_remito_v, 1) LIKE ?";

    $where .= " OR EXISTS (
        SELECT 1 FROM usuario 
        WHERE usuario.letra = LEFT(voucher.id_remito_v, 1) 
        AND usuario.nombre LIKE ?
    )";

    $search_term_like = '%' . $search_term . '%';
    $types .= "ssssss";
    $params[] = $search_term_like; // Empresa
    $params[] = $search_term_like; // Pasajero
    $params[] = $search_term_like; // Origen
    $params[] = $search_term_like; // Destino
    $params[] = $search_term_like; // Letra
    $params[] = $search_term_like; // Nombre chofer
    $where .= ")";
}
if ($fecha_desde !== '') {
    $where .= " AND Fecha >= ?";
    $types .= "s";
    $params[] = $fecha_desde;
}
if ($fecha_hasta !== '') {
    $where .= " AND Fecha <= ?";
    $types .= "s";
    $params[] = $fecha_hasta;
}

$countSql = "SELECT COUNT(*) AS total FROM voucher WHERE $where";
$stmtCount = $conn->prepare($countSql);

if ($stmtCount) {
    if (!empty($params)) {
        $bindParamsCount = array_merge([$types], $params);
        call_user_func_array([$stmtCount, 'bind_param'], refValues($bindParamsCount));
    }
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $stmtCount->close();
} else {
    $totalRecords = 0;
    error_log("Error preparing count statement: " . $conn->error);
}

$totalPages = ceil($totalRecords / $records_per_page);
$offset = ($page - 1) * $records_per_page;

$sql = "SELECT voucher.*, voucher.Empresa, u.nombre AS nombre_chofer 
        FROM voucher 
        LEFT JOIN usuario u ON u.letra = LEFT(voucher.id_remito_v, 1)
        WHERE $where 
        ORDER BY Fecha DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);

$htmlOutput = '';
if ($stmt) {
    $limit_types = $types . "ii";
    $limit_params = array_merge($params, [$offset, $records_per_page]);

    call_user_func_array([$stmt, 'bind_param'], refValues($limit_params, $limit_types));

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()):
            $id_remito_v = htmlspecialchars($row['id_remito_v'] ?? '');
            $nombre_pasajero = htmlspecialchars($row['nombre_pasajero'] ?? '');
            $id_empresa_val = htmlspecialchars($row['id_empresa'] ?? '');
            $empresa_nombre = htmlspecialchars($row['Empresa'] ?? '');
            $origen = htmlspecialchars($row['Origen'] ?? '');
            $destino = htmlspecialchars($row['Destino'] ?? '');
            $observaciones = htmlspecialchars($row['observaciones'] ?? '');
            $tiempo_espera = htmlspecialchars($row['tiempo_espera'] ?? '');
            $hora_origen = htmlspecialchars($row['hora_origen'] ?? '');
            $hora_destino = htmlspecialchars($row['hora_destino'] ?? '');
            $nombre_chofer = htmlspecialchars($row['nombre_chofer'] ?? '');

            $fecha_db = $row['Fecha'] ?? '';
            $fecha_data_attr = ($fecha_db === '0000-00-00' || $fecha_db === '') ? '' : $fecha_db;
            $fechaFormateada = ($fecha_db === '0000-00-00' || $fecha_db === '') ? 'N/A' : date('d-m-Y', strtotime($fecha_db));

            $htmlOutput .= '<tr data-id="' . $id_remito_v . '" ' .
                           'data-pasajero="' . $nombre_pasajero . '" ' .
                           'data-id-empresa="' . $id_empresa_val . '" ' .
                           'data-empresa-nombre="' . $empresa_nombre . '" ' .
                           'data-origen="' . $origen . '" ' .
                           'data-destino="' . $destino . '" ' .
                           'data-observaciones="' . $observaciones . '" ' .
                           'data-tiempoespera="' . $tiempo_espera . '" ' .
                           'data-horaorigen="' . $hora_origen . '" ' .
                           'data-horadestino="' . $hora_destino . '" ' .
                           'data-fecha="' . $fecha_data_attr . '" ' .
                           'data-chofer="' . $nombre_chofer . '">';

            $htmlOutput .= '<td class="fecha-col">' . $fechaFormateada . '</td>';
            $htmlOutput .= '<td>' . $id_remito_v . '</td>';
            $htmlOutput .= '<td>' . $nombre_chofer . '</td>'; 
            $htmlOutput .= '<td>' . $nombre_pasajero . '</td>';
            if ($id_empresa === '') {
                $htmlOutput .= '<td>' . $empresa_nombre . '</td>';
            }
            $htmlOutput .= '<td>' . $origen . ' (' . $hora_origen . 'hs)</td>';
            $htmlOutput .= '<td>' . $destino . ' (' . $hora_destino . 'hs)</td>';
            $htmlOutput .= '<td>' . $observaciones . '</td>';
            $htmlOutput .= '<td>' . $tiempo_espera . '</td>';

            // Acciones
            if ($tipo === 'noaprobados') {
                $htmlOutput .= '<td><div style="display: flex; gap: 5px; justify-content: center; align-items: center;">';
                $htmlOutput .= '<button type="button" class="aprobar-btn" data-id="' . $id_remito_v . '"><img src="../assets/check-blanco.png" alt="Aprobar" style="width: 20px; height: 20px;"></button>';
                $htmlOutput .= '<button type="button" class="edit-voucher-btn" data-id="' . $id_remito_v . '"><img src="../assets/boton-editar-blanco.png" alt="Editar" style="width: 20px; height: 20px;"></button>';
                $htmlOutput .= '<button type="button" class="delete-voucher-btn" data-id="' . $id_remito_v . '"><img src="../assets/boton-eliminar-blanco.png" alt="Eliminar" style="width: 20px; height: 20px;"></button>';
                $htmlOutput .= '</div></td>';
            } elseif ($tipo === 'aprobados') {
                $htmlOutput .= '<td><div style="display: flex; gap: 5px; justify-content: center; align-items: center;">';
                $htmlOutput .= '<button class="btn-mover" data-id="' . $id_remito_v . '"><img src="../assets/mover-blanco.png" alt="Mover" style="width: 20px; height: 20px;"></button>';
                $htmlOutput .= '<button type="button" class="edit-voucher-btn" data-id="' . $id_remito_v . '"><img src="../assets/boton-editar-blanco.png" alt="Editar" style="width: 20px; height: 20px;"></button>';
                $htmlOutput .= '<button type="button" class="delete-voucher-btn" data-id="' . $id_remito_v . '"><img src="../assets/boton-eliminar-blanco.png" alt="Eliminar" style="width: 20px; height: 20px;"></button>';
                $htmlOutput .= '</div></td>';
            } else {
                $monto = $row['Monto'] ?? '';
                $montoMostrar = ($monto === '' || $monto === null) ? 'N/A' : '$' . number_format($monto, 0, '', '.');
                $htmlOutput .= '<td>' . htmlspecialchars($montoMostrar) . '</td>';
                $htmlOutput .= '<td>';
                $htmlOutput .= '<button type="button" class="btn-monto-pdf" data-id="' . $id_remito_v . '" data-monto="' . htmlspecialchars($row['Monto'] ?? '') . '" style="width:100%; padding: 8px 12px; border: none; border-radius: 4px; background-color: #28a745; color: white; cursor: pointer; font-size: 0.9em;">PDF</button>';
                $htmlOutput .= '</td>';
            }
            $htmlOutput .= '</tr>';
        endwhile;
    } else {
        $htmlOutput .= '<tr><td colspan="10">No hay vouchers para mostrar.</td></tr>';
    }
    $stmt->close();
} else {
    $htmlOutput .= '<tr><td colspan="10">Error al preparar la consulta: ' . htmlspecialchars($conn->error) . '</td></tr>';
}

function refValues($arr, $types = null){
    if (strnatcmp(phpversion(),'5.3') >= 0) {
        $refs = [];
        if ($types) {
            $refs[] = $types;
        }
        foreach($arr as $key => $value) {
            $refs[$key + ($types ? 1 : 0)] = &$arr[$key];
        }
        return $refs;
    }
    return $arr;
}

$paginationHtml = '<div class="pagination-controls">';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active-page' : '';
    $paginationHtml .= '<a href="#" class="page-link ' . htmlspecialchars($activeClass) . '" data-page="' . htmlspecialchars($i) . '">' . htmlspecialchars($i) . '</a>';
}
$paginationHtml .= '</div>';

$miniModal = '';

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'html' => $htmlOutput,
    'pagination' => $paginationHtml,
    'totalRecords' => $totalRecords,
    'miniModal' => $miniModal
]);
?>