<?php
include '../Model/conexion.php';

$tipo = $_POST['tipo'] ?? '';
$search_term = $_POST['search_term'] ?? '';
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Página actual
$records_per_page = 10; // Número de registros por página

$where = "borrado = 0 AND id_empresa IS NULL";
if ($tipo === 'noaprobados') $where .= " AND aprobado = 0";
if ($tipo === 'aprobados') $where .= " AND aprobado = 1";
if ($search_term !== '') {
    $search_term_escaped = $conn->real_escape_string($search_term);
    $where .= " AND (Empresa LIKE '%{$search_term_escaped}%' OR nombre_pasajero LIKE '%{$search_term_escaped}%')";
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
$countSql = "SELECT COUNT(*) AS total FROM voucher WHERE $where";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $records_per_page);
$offset = ($page - 1) * $records_per_page;

$sql = "SELECT * FROM voucher WHERE $where ORDER BY Fecha DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);


$htmlOutput = '';
if ($tipo === 'noaprobados') {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()):
            $fechaFormateada = date('d-m-Y', strtotime($row['Fecha']));

            $htmlOutput .= '<tr>';
            $htmlOutput .= '<td class="fecha-col">' . $fechaFormateada . '</td>';
            $htmlOutput .= '<td>' . $row['id_remito_v'] . '</td>';
            $htmlOutput .= '<td>' . $row['nombre_pasajero'] . '</td>';
            $htmlOutput .= '<td>' . $row['Empresa'] . '</td>';
            $htmlOutput .= '<td>' . $row['Origen'] . ' (' . $row['hora_origen'] . 'hs)</td>';
            $htmlOutput .= '<td>' . $row['Destino'] . ' (' . $row['hora_destino'] . 'hs)</td>';
            $htmlOutput .= '<td>' . $row['observaciones'] . '</td>';
            $htmlOutput .= '<td>' . $row['tiempo_espera'] . '</td>';
            $htmlOutput .= '<td>';
            $htmlOutput .= '<form action="../Model/aprobar_voucher.php" method="post">';
            $htmlOutput .= '<input type="hidden" name="id" value="' . $row['id_remito_v'] . '">';
            $htmlOutput .= '<input type="submit" value="Aprobar">';
            $htmlOutput .= '</form>';
            $htmlOutput .= '</td>';
            $htmlOutput .= '<td>';
            $htmlOutput .= '<div class="boton-acciones">';
            $htmlOutput .= '<a href="../Model/editarVoucher.php?id_remito_v=' . $row['id_remito_v'] . '"><button id="boton-editar"><img src="../assets/boton-editar.png" width="20px" height="20px" alt="Editar"></button></a>';
            $htmlOutput .= '<a href="../Model/eliminarVoucher.php?id_remito_v=' . $row['id_remito_v'] . '"><button id="boton-eliminar"><img src="../assets/boton-eliminar.png" width="20px" height="20px" alt="Eliminar"></button></a>';
            $htmlOutput .= '</div>';
            $htmlOutput .= '</td>';
            $htmlOutput .= '</tr>';
        endwhile;
    } else {
        $htmlOutput .= '<tr><td colspan="10" style="text-align:center;">No hay resultados</td></tr>';
    }
} elseif ($tipo === 'aprobados') {
    $empresasSql = "SELECT id_empresa, nombre FROM empresa WHERE borrado = 0";
    $resultEmpresas = $conn->query($empresasSql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()):
            $fechaFormateada = date('d-m-Y', strtotime($row['Fecha']));
            
            $htmlOutput .= '<tr>';
            $htmlOutput .= '<td class="fecha-col">' . $fechaFormateada . '</td>';
            $htmlOutput .= '<td>' . $row['id_remito_v'] . '</td>';
            $htmlOutput .= '<td>' . $row['nombre_pasajero'] . '</td>';
            $htmlOutput .= '<td>' . $row['Empresa'] . '</td>';
            $htmlOutput .= '<td>' . $row['Origen'] . ' (' . $row['hora_origen'] . 'hs)</td>';
            $htmlOutput .= '<td>' . $row['Destino'] . ' (' . $row['hora_destino'] . 'hs)</td>';
            $htmlOutput .= '<td>' . $row['observaciones'] . '</td>';
            $htmlOutput .= '<td>' . $row['tiempo_espera'] . '</td>';
            $htmlOutput .= '<td>';
            $htmlOutput .= '<button class="mover-btn" onclick="mostrarFormulario(\'' . $row['id_remito_v'] . '\')">Mover</button>';
            $htmlOutput .= '<div id="form-popup-' . $row['id_remito_v'] . '" class="form-popup">';
            $htmlOutput .= '<h3>Mover a Empresa</h3>';
            $htmlOutput .= '<form action="../Model/asignarEmpresa.php" method="post">';
            $htmlOutput .= '<input type="hidden" name="id_remito_v" value="' . $row['id_remito_v'] . '">';
            $htmlOutput .= '<select name="id_empresa" required>';
            $htmlOutput .= '<option value="no_autorizar">No autorizar</option>';
            if ($resultEmpresas->num_rows > 0) {
                $resultEmpresas->data_seek(0); 
                while($empresa = $resultEmpresas->fetch_assoc()):
                    $htmlOutput .= '<option value="' . $empresa['id_empresa'] . '">' . $empresa['nombre'] . '</option>';
                endwhile;
            }
            $htmlOutput .= '</select><br>';
            $htmlOutput .= '<button type="submit">Confirmar</button>';
            $htmlOutput .= '<button type="button" onclick="ocultarFormulario(\'' . $row['id_remito_v'] . '\')">Cancelar</button>';
            $htmlOutput .= '</form>';
            $htmlOutput .= '</div>';
            $htmlOutput .= '</td>';
            $htmlOutput .= '</tr>';
        endwhile;
    } else {
        $htmlOutput .= '<tr><td colspan="10" style="text-align:center;">No hay resultados</td></tr>';
    }
}

// Generar controles de paginación
$paginationHtml = '<div class="pagination-controls">';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active-page' : '';
    $paginationHtml .= '<a href="#" class="page-link ' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
}
$paginationHtml .= '</div>';


// Devolver la respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode([
    'html' => $htmlOutput,
    'pagination' => $paginationHtml,
    'totalRecords' => $totalRecords
]);
?>