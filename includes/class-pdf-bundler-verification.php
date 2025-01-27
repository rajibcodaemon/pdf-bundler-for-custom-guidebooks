<?php
if (!class_exists('PDF_Bundler_Verification')):

class PDF_Bundler_Verification {
    public static function verify_requirements() {
        $errors = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            $errors[] = 'PHP 7.2 or higher is required. Current version: ' . PHP_VERSION;
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            $errors[] = 'WordPress 5.0 or higher is required. Current version: ' . $wp_version;
        }
        
        // Check required directories
        $required_dirs = array(
            'lib/tcpdf' => 'TCPDF library directory',
            'lib/fpdi' => 'FPDI library directory'
        );
        
        foreach ($required_dirs as $dir => $description) {
            if (!is_dir(PDF_BUNDLER_PATH . $dir)) {
                $errors[] = "$description is missing: $dir";
            }
        }
        
        // Check required files
        $required_files = array(
            'lib/tcpdf/tcpdf.php' => 'TCPDF main file',
            'lib/tcpdf/config/tcpdf_config.php' => 'TCPDF config file',
            'lib/tcpdf/fonts/helvetica.php' => 'TCPDF font file',
            'lib/fpdi/src/autoload.php' => 'FPDI autoloader',
            'lib/fpdi/src/Fpdi.php' => 'FPDI main file'
        );
        
        foreach ($required_files as $file => $description) {
            if (!file_exists(PDF_BUNDLER_PATH . $file)) {
                $errors[] = "$description is missing: $file";
            }
        }
        
        // Check FPDI files
        $fpdi_files = array(
            'autoload.php',
            'Fpdi.php',
            'FpdiTrait.php',
            'TcpdfFpdi.php',
            'PdfParser/PdfParser.php',
            'PdfParser/StreamReader.php',
            'PdfParser/Filter/FilterException.php',
            'PdfParser/Type/PdfType.php',
            'PdfParser/Type/PdfDictionary.php'
        );
        
        foreach ($fpdi_files as $file) {
            $path = PDF_BUNDLER_FPDI_PATH . $file;
            if (!file_exists($path)) {
                $errors[] = "Missing FPDI file: {$file}";
            } elseif (!is_readable($path)) {
                $errors[] = "FPDI file not readable: {$file}";
            }
        }
        
        // Try to load FPDI
        if (empty($errors)) {
            try {
                require_once(PDF_BUNDLER_FPDI_PATH . 'autoload.php');
                if (!class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
                    $errors[] = 'FPDI class not found after loading autoloader';
                }
            } catch (Exception $e) {
                $errors[] = 'Error loading FPDI: ' . $e->getMessage();
            }
        }
        
        return $errors;
    }

    public static function test_pdf_creation() {
        try {
            // Verify TCPDF exists
            if (!file_exists(PDF_BUNDLER_TCPDF_PATH . 'tcpdf.php')) {
                throw new Exception('TCPDF main file not found');
            }

            // Verify TCPDF images file exists
            if (!file_exists(PDF_BUNDLER_TCPDF_PATH . 'include/tcpdf_images.php')) {
                throw new Exception('TCPDF images file not found');
            }

            require_once(PDF_BUNDLER_TCPDF_PATH . 'tcpdf.php');
            
            // Create test PDF
            $pdf = new TCPDF();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'PDF Creation Test', 0, 1);
            
            // Try to generate PDF
            $output = $pdf->Output('', 'S');
            if (empty($output)) {
                throw new Exception('Failed to generate PDF output');
            }

            return true;

        } catch (Exception $e) {
            error_log('PDF Creation Test Error: ' . $e->getMessage());
            return false;
        }
    }

    private function verify_fpdi_setup() {
        // Check autoloader first
        $autoloader = $this->lib_dir . '/fpdi/src/autoload.php';
        if (!file_exists($autoloader)) {
            throw new Exception("FPDI autoloader missing");
        }
        if (!is_readable($autoloader)) {
            throw new Exception("FPDI autoloader not readable");
        }

        // Test autoloader
        require_once $autoloader;
        if (!class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
            throw new Exception("FPDI autoloader not working correctly");
        }

        // Check other required files
        $required_files = array(
            '/fpdi/src/Fpdi.php',
            '/fpdi/src/FpdiTrait.php',
            '/fpdi/src/TcpdfFpdi.php',
            '/fpdi/src/PdfParser/PdfParser.php'
        );

        foreach ($required_files as $file) {
            if (!file_exists($this->lib_dir . $file)) {
                throw new Exception("Required FPDI file missing: {$file}");
            }
        }
    }
}

endif; 