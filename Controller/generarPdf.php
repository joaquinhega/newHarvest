<?php
require('../fpdf186/fpdf.php');
include '../Model/conexion.php';

if (!isset($_POST['id'])) { 
    die("No se recibió ningún ID.");
}

$id_remito = $_POST['id'];
$sql = "SELECT * FROM voucher WHERE id_remito_v = '$id_remito'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if (!$row) {
    die("No se encontró el voucher.");
}

$id_empresa_voucher = $row['id_empresa']; 

$nombre_empresa = '';
if (!empty($id_empresa_voucher)) {
    $sqlEmpresaNombre = "SELECT nombre FROM empresa WHERE id_empresa = '$id_empresa_voucher' AND borrado = 0";
    $resEmpresaNombre = $conn->query($sqlEmpresaNombre);
    if ($resEmpresaNombre && $empresaRowNombre = $resEmpresaNombre->fetch_assoc()) {
        $nombre_empresa = $empresaRowNombre['nombre'];
    }
}

$nombre_cliente = $row['nombre_pasajero'];
$codigo_voucher = $row['id_remito_v'];
$fecha_emision = date('d/m/Y', strtotime($row['Fecha']));
$fecha_vencimiento = isset($row['Vencimiento']) ? date('d/m/Y', strtotime($row['Vencimiento'])) . '' : '';
$ubicacion = isset($row['Destino']) ? $row['Destino'] : '';
$monto = isset($row['Monto']) ? $row['Monto'] : '';
$tiempo_espera = isset($row['tiempo_espera']) ? $row['tiempo_espera'] : 'N/A';

$hora_origen_formatted = '';
if (isset($row['hora_origen']) && $row['hora_origen'] !== '00:00:00' && $row['hora_origen'] !== '') {
    $hora_origen_formatted = ' (' . date('H:i', strtotime($row['hora_origen'])) . 'hs)';
}

$hora_destino_formatted = '';
if (isset($row['hora_destino']) && $row['hora_destino'] !== '00:00:00' && $row['hora_destino'] !== '') {
    $hora_destino_formatted = ' (' . date('H:i', strtotime($row['hora_destino'])) . 'hs)';
}

// --- OBTENER LOGO DE LA EMPRESA ---
$logo_empresa = null;
if (!empty($id_empresa_voucher)) {
    $sqlEmpresaPath = "SELECT path FROM empresa WHERE id_empresa = '$id_empresa_voucher' AND borrado = 0";
    $resEmpresaPath = $conn->query($sqlEmpresaPath);
    if ($resEmpresaPath && $empresaRowPath = $resEmpresaPath->fetch_assoc()) {
        if (!empty($empresaRowPath['path']) && file_exists('../' . $empresaRowPath['path'])) {
            $logo_empresa = '../' . $empresaRowPath['path'];
        }
    }
}

class VoucherPDF extends FPDF
{
    public $logo_empresa_path = null;

    function Header()
    {
        $this->Image('../assets/logo-newHarvest-negro.png', 18, 13, 45);
        if ($this->logo_empresa_path) {
            $this->Image($this->logo_empresa_path, 240, 13, 40, 0, '', '', '', false, 300, '', false, false, 0);
        }
    }
}

$pdf = new VoucherPDF('L', 'mm', 'A4');
$pdf->logo_empresa_path = $logo_empresa;
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(18, 18, 18);

// --- ENCABEZADO ---
$pdf->SetFont('Arial', 'B', 28);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0, 20);
$pdf->Cell(297, 15, utf8_decode('VOUCHER'), 0, 1, 'C');

// Número de voucher
$pdf->SetFont('Arial', '', 16);
$pdf->SetXY(0, 35);
$pdf->Cell(297, 8, utf8_decode('N°: ') . $codigo_voucher, 0, 1, 'C');

// --- FECHA ENCUADRADA CENTRADA ---
$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(0, 45);
$pdf->Cell(297, 7, utf8_decode('Fecha:'), 0, 1, 'C');

// Calcular el ancho total de los bloques de fecha (sin gap)
$fecha_parts = explode('/', $fecha_emision);
$block_width = 18;
$block_height = 12;
$block_gap = 0; 
$total_width = $block_width * 3 + $block_gap * 2;
$start_x = (297 - $total_width) / 2;

$pdf->SetXY($start_x, 53);
$pdf->SetFont('Arial', '', 14);
foreach ($fecha_parts as $i => $parte) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($block_width, $block_height, $parte, 1, 0, 'C', true);
}
$pdf->Ln(18);

// --- LÍNEA DIVISORIA ---
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.7);
$pdf->Line(18, 70, 279, 70);

// --- SECCIÓN CENTRAL: DATOS PRINCIPALES ---
$pdf->SetY(78);
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);

// Etiquetas a la izquierda
$labels = [
    'EMPRESA:' => $nombre_empresa,
    'ORIGEN:' => utf8_decode($row['Origen']) . $hora_origen_formatted, 
    'DESTINO:' => utf8_decode($row['Destino']) . $hora_destino_formatted,
    'TIEMPO ESPERA:' => $tiempo_espera . ' min'
];
$y = 78;
foreach ($labels as $label => $value) {
    $pdf->SetXY(30, $y);
    $pdf->Cell(45, 10, utf8_decode($label), 0, 0, 'L', true);
    $pdf->SetFont('Arial', '', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(90, 10, $value, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(255, 255, 255);
    $y += 13;
}

// --- MONTO Y FIRMA EN LA PARTE INFERIOR ---
$pdf->SetY(145);
$pdf->SetFont('Arial', '', 15);
$pdf->SetTextColor(0, 0, 0);

// Monto centrado
$pdf->SetXY(70, 145);
$pdf->Cell(60, 10, '$' . number_format($monto, 0, '', '.'), 0, 2, 'C');

// Cuadro "MONTO:"
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetX(70);
$pdf->Cell(60, 10, 'MONTO:', 0, 0, 'C', true);

// Cuadro "FIRMA:"
$pdf->SetXY(180, 155);
$pdf->Cell(60, 10, 'FIRMA:', 0, 0, 'C', true);

// Imagen de firma (más pequeña y alineada con el cuadro)
$pdf->SetTextColor(0, 0, 0);
if (!empty($row['Firma']) && file_exists($row['Firma'])) {
    $pdf->Image($row['Firma'], 195, 105, 40); 
}

// --- FOOTER ---
$pdf->SetY(200);
$pdf->SetFont('Arial', 'I', 12);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'New HARVEST - Mendoza Experience | www.newharvest.com.ar', 0, 0, 'C');

// --- SALIDA DEL PDF ---
$pdf->Output('I', 'voucher_' . $codigo_voucher . '.pdf');
?>