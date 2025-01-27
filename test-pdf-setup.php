<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

function test_directory_permissions($dir) {
    if (!file_exists($dir)) {
        echo "❌ Directory does not exist: {$dir}\n";
        return false;
    }
    if (!is_readable($dir)) {
        echo "❌ Directory not readable: {$dir}\n";
        return false;
    }
    if (!is_writable($dir)) {
        echo "❌ Directory not writable: {$dir}\n";
        return false;
    }
    echo "✅ Directory permissions OK: {$dir}\n";
    return true;
}

function test_file_permissions($file) {
    if (!file_exists($file)) {
        echo "❌ File does not exist: {$file}\n";
        return false;
    }
    if (!is_readable($file)) {
        echo "❌ File not readable: {$file}\n";
        return false;
    }
    echo "✅ File permissions OK: {$file}\n";
    return true;
}

// Test directories
$plugin_dir = plugin_dir_path(dirname(__FILE__));
$lib_dir = $plugin_dir . 'lib';
$tcpdf_dir = $lib_dir . '/tcpdf';
$fpdi_dir = $lib_dir . '/fpdi';

echo "\nTesting Directory Permissions:\n";
test_directory_permissions($plugin_dir);
test_directory_permissions($lib_dir);
test_directory_permissions($tcpdf_dir);
test_directory_permissions($fpdi_dir);

// Test essential files
echo "\nTesting Essential Files:\n";
$essential_files = array(
    $tcpdf_dir . '/tcpdf.php',
    $tcpdf_dir . '/tcpdf_autoconfig.php',
    $tcpdf_dir . '/config/tcpdf_config.php',
    $tcpdf_dir . '/include/tcpdf_font_data.php',
    $tcpdf_dir . '/include/tcpdf_fonts.php',
    $tcpdf_dir . '/include/tcpdf_colors.php',
    $tcpdf_dir . '/include/tcpdf_static.php',
    $tcpdf_dir . '/include/tcpdf_images.php',
    $tcpdf_dir . '/fonts/helvetica.php',
    $fpdi_dir . '/src/autoload.php',
    $fpdi_dir . '/src/Fpdi.php'
);

foreach ($essential_files as $file) {
    test_file_permissions($file);
}

// Try to create a PDF
echo "\nTesting PDF Creation:\n";
try {
    require_once($tcpdf_dir . '/tcpdf.php');
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test PDF', 0, 1);
    $output = $pdf->Output('', 'S');
    echo "✅ PDF creation successful\n";
} catch (Exception $e) {
    echo "❌ PDF creation failed: " . $e->getMessage() . "\n";
} 