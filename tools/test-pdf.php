<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

try {
    // Test TCPDF
    require_once(PDF_BUNDLER_PATH . 'lib/tcpdf/tcpdf.php');
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'TCPDF Test', 0, 1);
    
    // Test FPDI
    require_once(PDF_BUNDLER_PATH . 'lib/fpdi/src/autoload.php');
    $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
    $fpdi->AddPage();
    $fpdi->SetFont('helvetica', '', 12);
    $fpdi->Cell(0, 10, 'FPDI Test', 0, 1);
    
    echo '<div style="color: green;">✅ PDF libraries are working correctly!</div>';
    
} catch (Exception $e) {
    echo '<div style="color: red;">❌ Error: ' . $e->getMessage() . '</div>';
} 