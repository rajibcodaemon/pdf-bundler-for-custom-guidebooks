<?php
/**
 * PDF Handler Class
 */

if (!defined('WPINC')) {
    die;
}

// Check for required libraries before including them
if (!file_exists(PDF_BUNDLER_TCPDF_PATH . 'tcpdf.php') || 
    !file_exists(PDF_BUNDLER_FPDI_PATH . 'autoload.php')) {
    return;
}

// Include required libraries in correct order
require_once PDF_BUNDLER_TCPDF_PATH . 'tcpdf.php';
require_once PDF_BUNDLER_FPDI_PATH . 'autoload.php';
require_once PDF_BUNDLER_FPDI_PATH . 'PdfReader/PdfReader.php';
require_once PDF_BUNDLER_FPDI_PATH . 'Tcpdf/Fpdi.php';

use setasign\Fpdi\Tcpdf\Fpdi;
use Mpdf\Mpdf;

class PDF_Bundler_PDF_Handler {
    private $pdf;
    
    public function __construct() {
        try {
            if (!class_exists('setasign\\Fpdi\\Tcpdf\\Fpdi')) {
                throw new Exception('FPDI class not found. Please reinstall the plugin.');
            }
            
            // Initialize FPDI with TCPDF
            $this->pdf = new Fpdi();
            
            // Set default properties
            $this->pdf->SetCreator('PDF Bundler');
            $this->pdf->SetAuthor('PDF Bundler');
            $this->pdf->SetTitle('Merged PDF Document');
            //add_action('init', [$this, 'register_shortcodes']);
            //$this->register_shortcodes();

            // add_action('wp_loaded', function() {
            //     if (!shortcode_exists('user_flipbook')) {
            //         error_log('Shortcode user_flipbook not found. Re-registering.');
            //         add_shortcode('user_flipbook', [$this, 'display_user_flipbook']);
            //     }
            // });
            
        } catch (Exception $e) {
            error_log('PDF Handler Initialization Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // public function register_shortcodes() {
    //     add_shortcode('user_flipbook', [$this, 'display_user_flipbook']);
    // }

    /**
     * Generate flipbook URL for the merged PDF
     */
    private function generate_flipbook_url($pdf_path, $user_id) {
        try {
            // Set up flipbook directory
            $upload_dir = wp_upload_dir();
            $flipbook_dir = $upload_dir['basedir'] . '/pdf-bundler/flipbook-viewer';
            
            // Create directory if it doesn't exist
            if (!is_dir($flipbook_dir)) {
                wp_mkdir_p($flipbook_dir);
                chmod($flipbook_dir, 0755);
            }

            // Get the PDF filename and create a unique name for flipbook
            $original_filename = basename($pdf_path);
            $unique_id = uniqid();
            $flipbook_filename = $unique_id . '_' . $original_filename;
            
            // Copy the PDF to flipbook directory
            $flipbook_path = $flipbook_dir . '/' . $flipbook_filename;
            if (!copy($pdf_path, $flipbook_path)) {
                throw new Exception('Failed to copy PDF to flipbook directory');
            }
            
            // Create flipbook URL using the upload directory URL
            $flipbook_url = $upload_dir['baseurl'] . '/pdf-bundler/flipbook-viewer/' . $flipbook_filename;
            
            // Store the flipbook path and URL in user meta
            update_user_meta($user_id, '_flipbook_path', $flipbook_path);
            update_user_meta($user_id, '_flipbook_url', $flipbook_url);

            // Generate a unique shadow ID
            $shadow_id = uniqid('flipbook_', true);

            // Save the shadow ID to user meta
            //update_user_meta($user_id, '_flipbook_shadow_id', $shadow_id);

            // Send WooCommerce email with instructions
            $user = get_userdata($user_id);
            if ($user && !empty($flipbook_url)) {
                //$shortcode = sprintf('[user_flipbook shadow_id="%s"]', esc_html($shadow_id));
                $shortcode = sprintf('[dflip source="%s"][/dflip]', esc_url($flipbook_url));
                $email_heading = 'Your Flipbook is Ready!';
                $email_content = sprintf(
                    '<p>Hi %s,</p>
                    <p>Your flipbook has been successfully generated. You can display it using the following shortcode:</p>
                    <p><code>%s</code></p>
                    <p>Place this shortcode in any page or post to embed your flipbook.</p>
                    <p>Thank you for using our service!</p>',
                    esc_html($user->display_name),
                    $shortcode
                );

                // Generate email body using WooCommerce templates
                ob_start();
                wc_get_template(
                    'emails/email-header.php',
                    ['email_heading' => $email_heading]
                );
                echo wp_kses_post($email_content);
                wc_get_template('emails/email-footer.php');
                $email_body = ob_get_clean();

                // Send email
                wp_mail(
                    $user->user_email,
                    'Your Flipbook is Ready',
                    $email_body,
                    ['Content-Type: text/html; charset=UTF-8']
                );
            }
            
            return $flipbook_url;
            
        } catch (Exception $e) {
            error_log('Flipbook URL Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    public function display_user_flipbook($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            ['shadow_id' => null],
            $atts,
            'user_flipbook'
        );
    
        $shadow_id = sanitize_text_field($atts['shadow_id']);
    
        if (empty($shadow_id)) {
            error_log('No shadow ID provided.');
            return '<p>No shadow ID provided. Please contact support for assistance.</p>';
        }
    
        // Find user by shadow ID
        $user_query = new WP_User_Query([
            'meta_key'   => '_flipbook_shadow_id',
            'meta_value' => $shadow_id,
            'number'     => 1,
            'fields'     => 'ID',
        ]);
    
        $user_ids = $user_query->get_results();
        error_log('User Query Results: ' . print_r($user_ids, true));
    
        if (empty($user_ids)) {
            error_log('Invalid shadow ID.');
            return '<p>Invalid shadow ID. Please contact support for assistance.</p>';
        }
    
        $user_id = $user_ids[0];
    
        // Fetch the flipbook URL
        $flipbook_url = get_user_meta($user_id, '_flipbook_url', true);
    
        if ($flipbook_url) {
            // Embed the flipbook
            return do_shortcode('[dflip source="' . esc_url($flipbook_url) . '"][/dflip]');
        }
        return '<p>No flipbook available for this user. Please contact support for assistance.</p>';
    }
    
    //add_shortcode('user_flipbook', 'display_user_flipbook');

    /**
     * Merge two PDFs into one
     */
    public function merge_pdfs($pdf1_path, $pdf2_path, $output_path, $user_id) {
        try {
            error_log('Starting PDF merge process...');
            error_log('PDF 1: ' . $pdf1_path);
            error_log('PDF 2: ' . $pdf2_path);
            error_log('Output: ' . $output_path);

            // Validate input files exist
            if (!file_exists($pdf1_path) || !file_exists($pdf2_path)) {
                error_log('PDF Merger Error: One or both input files do not exist');
                return false;
            }

            // Get customer's billing city
            $billing_city = get_user_meta($user_id, 'billing_city', true);
            if (empty($billing_city)) {
                error_log('PDF Merger Error: Customer billing city not found');
                return false;
            }

            // Check if city PDF exists for the billing city
            global $wpdb;
            $city_pdf = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pdf_bundler_cities WHERE city = %s AND status = 'active'",
                $billing_city
            ));

            if (!$city_pdf) {
                error_log('PDF Merger Error: No matching city PDF found for ' . $billing_city);
                return false;
            }

            // Verify the city PDF path matches
            if ($pdf2_path !== $city_pdf->pdf_path) {
                error_log('PDF Merger Error: City PDF mismatch. Expected: ' . $city_pdf->pdf_path . ', Got: ' . $pdf2_path);
                return false;
            }

            // Check if pdftk is installed
            exec('which pdftk', $output, $return_var);
            if ($return_var !== 0) {
                error_log('PDF Merger Error: pdftk is not installed on the server');
                return false;
            }

            // Clean and escape the file paths
            $pdf1_path = str_replace("'", '', $pdf1_path);
            $pdf2_path = str_replace("'", '', $pdf2_path);
            $output_path = str_replace("'", '', $output_path);
            
            // Build the command without extra escaping
            $command = sprintf(
                'pdftk "%s" "%s" cat output "%s" 2>&1',
                $pdf1_path,
                $pdf2_path,
                $output_path
            );
            
            // Execute command and capture output
            $output = array();
            $return_var = 0;
            exec($command, $output, $return_var);
            
            // Log the command output for debugging
            error_log('PDF Merger Command: ' . $command);
            error_log('PDF Merger Output: ' . print_r($output, true));
            error_log('PDF Merger Return Code: ' . $return_var);
            
            if ($return_var !== 0) {
                error_log('PDF Merger Error: Command failed with return code ' . $return_var);
                return false;
            }
            
            // Verify the output file was created
            if (!file_exists($output_path)) {
                error_log('PDF Merger Error: Output file was not created');
                return false;
            }
            
            // Verify the output file is a valid PDF
            if (!$this->validate_pdf($output_path)) {
                error_log('PDF Merger Error: Output file is not a valid PDF');
                @unlink($output_path); // Clean up invalid file
                return false;
            }
            
            if ($return_var === 0 && file_exists($output_path)) {
                // Store the merged PDF path in user meta
                update_user_meta($user_id, '_merged_pdf_path', $output_path);
                
                // Generate flipbook URL
                $flipbook_url = $this->generate_flipbook_url($output_path, $user_id);
                
                if ($flipbook_url) {
                    // Update database with flipbook URL and merged PDF path
                    $wpdb->update(
                        $wpdb->prefix . 'pdf_bundler_merge_queue',
                        array(
                            'merged_pdf_path' => $output_path,
                            'flipbook_url' => $flipbook_url,
                            'updated_at' => current_time('mysql')
                        ),
                        array('user_id' => $user_id),
                        array('%s', '%s', '%s'),
                        array('%d')
                    );
                    //update_user_meta( $user_id, '_agent_merged_pdf_path', $output_path );
                    //update_user_meta( $user_id, '_agent_flipbook_url', $flipbook_url );
                }
                
                error_log('PDF merge completed successfully');
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('PDF Merger Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate PDF file
     */
    public function validate_pdf($file_path) {
        try {
            if (!file_exists($file_path)) {
                return false;
            }

            // Check file mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            if ($mime_type !== 'application/pdf') {
                return false;
            }

            // Try to load the PDF
            $page_count = $this->pdf->setSourceFile($file_path);
            return $page_count > 0;

        } catch (Exception $e) {
            error_log('PDF Validation Error: ' . $e->getMessage());
            return false;
        }
    }
} 