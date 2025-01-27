<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-pdf-bundler-verification.php';

echo "<h1>PDF Bundler Plugin Verification</h1>";

$errors = PDF_Bundler_Verification::verify_requirements();

if (empty($errors)) {
    echo "<p style='color: green;'>✅ All requirements are met!</p>";
    
    // Try to create a test PDF
    try {
        require_once PDF_BUNDLER_TCPDF_PATH . 'tcpdf.php';
        require_once PDF_BUNDLER_FPDI_PATH . 'autoload.php';
        
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 14);
        $pdf->Cell(0, 10, 'PDF Generation Test - Success!', 0, 1, 'C');
        
        echo "<p style='color: green;'>✅ PDF generation test successful!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ PDF generation test failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Some requirements are not met:</p>";
    echo "<ul style='color: red;'><li>" . implode("</li><li>", $errors) . "</li></ul>";
} 