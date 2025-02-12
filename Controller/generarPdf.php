<?php
require('../fpdf186/fpdf.php');
include '../Model/conexion.php';

if (isset($_POST['id'])) {
    $id_remito = $_POST['id'];
    
    $sql = "SELECT * FROM voucher WHERE id_remito_v = '$id_remito'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if ($row) {
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('Arial', 'B', 18); 
        $pdf->Cell(0, 10, 'New HARVEST', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Mendoza Experience', 0, 1, 'C');
        
        // Date and Voucher Number
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(30, 10, 'Fecha: ' . date('d/m/Y', strtotime($row['Fecha'])), 0, 0);
        $pdf->Cell(0, 10, 'No: ' . $row['id_remito_v'], 0, 1, 'R');
        
        // Empresa, Origen, Destino
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'Empresa: ' . $row['Empresa']);
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Origen: ' . $row['Origen']);
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Destino: ' . $row['Destino']);
        $pdf->Ln();
        
        // Monto
        $pdf->Cell(40, 10, 'Monto: $' . number_format($row['Monto'], 2));
        $pdf->Ln(20);
        
        // Signature line (Firma)
        $pdf->Cell(40, 10, 'Firma: ');
        if (!empty($row['Firma'])) {
            if (file_exists($row['Firma'])) {
                $pdf->Ln(10); 
                $pdf->Image($row['Firma'], 10, $pdf->GetY(), 40); 
            } else {
                $pdf->Cell(40, 10, 'Firma no disponible');
            }
        } else {
            $pdf->Cell(40, 10, 'No hay firma registrada');
        }
        
        // Output PDF
        $pdf->Output('I', 'voucher_'.$row['id_remito_v'].'.pdf');
    } else {
        echo "No se encontró el voucher.";
    }
} else {
    echo "No se recibió ningún ID.";
}
?>
