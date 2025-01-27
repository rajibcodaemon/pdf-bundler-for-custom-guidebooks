<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://codaemon.com
 * @since             1.0.0
 * @package           Pdf_Bundler_For_Custom_Guidebooks
 *
 * @wordpress-plugin
 * Plugin Name:       PDF Bundler for Custom Guidebooks
 * Plugin URI:        https://codaemon.com
 * Description:       Create a PDF customization and bundling system for admins
 * Version:           1.0.0
 * Author:            Rajib Naskar
 * Author URI:        https://codaemon.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pdf-bundler-for-custom-guidebooks
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
if (!defined('PDF_BUNDLER_VERSION')) {
	define('PDF_BUNDLER_VERSION', '1.0.1');
}
if (!defined('PDF_BUNDLER_PATH')) {
	define('PDF_BUNDLER_PATH', plugin_dir_path(__FILE__));
}
if (!defined('PDF_BUNDLER_URL')) {
	define('PDF_BUNDLER_URL', plugin_dir_url(__FILE__));
}
if (!defined('PDF_BUNDLER_TCPDF_PATH')) {
	define('PDF_BUNDLER_TCPDF_PATH', PDF_BUNDLER_PATH . 'lib/tcpdf/');
}
if (!defined('PDF_BUNDLER_FPDI_PATH')) {
	define('PDF_BUNDLER_FPDI_PATH', PDF_BUNDLER_PATH . 'lib/fpdi/src/');
}
define('PDF_BUNDLER_POST_TYPE', 'pdf_bundle'); // Update this to match your actual post type
if (!defined('PDF_BUNDLER_DB_VERSION')) {
	define('PDF_BUNDLER_DB_VERSION', '1.0.0');
}
if (!defined('PDF_BUNDLER_CRON_HOOK')) {
	define('PDF_BUNDLER_CRON_HOOK', 'pdf_merge_check_daily');
}

// Load library setup first
require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-library-setup.php';
require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-pdf-handler.php';

// Initialize PDF Bundler
add_action('plugins_loaded', function() {
    if (class_exists('PDF_Bundler_PDF_Handler')) {
        new PDF_Bundler_PDF_Handler();
    } else {
        error_log('PDF_Bundler_PDF_Handler class not found!');
    }
});
// Verify libraries before loading other dependencies
function pdf_bundler_verify_libraries() {
	$library_setup = new PDF_Bundler_Library_Setup();
	return $library_setup->verify_libraries();
}

// Only load other dependencies if libraries are present
if (pdf_bundler_verify_libraries()) {
	require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-verification.php';
	require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-pdf-handler.php';
	
	function activate_pdf_bundler_for_custom_guidebooks() {
		require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-for-custom-guidebooks-activator.php';
		Pdf_Bundler_For_Custom_Guidebooks_Activator::activate();
		
		// Schedule cron job if not already scheduled
		if (!wp_next_scheduled(PDF_BUNDLER_CRON_HOOK)) {
			wp_schedule_event(time(), 'daily', PDF_BUNDLER_CRON_HOOK);
		}
	}

	function deactivate_pdf_bundler_for_custom_guidebooks() {
		require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-for-custom-guidebooks-deactivator.php';
		Pdf_Bundler_For_Custom_Guidebooks_Deactivator::deactivate();
		
		// Clear scheduled hook
		wp_clear_scheduled_hook(PDF_BUNDLER_CRON_HOOK);
	}

	register_activation_hook( __FILE__, 'activate_pdf_bundler_for_custom_guidebooks' );
	register_deactivation_hook( __FILE__, 'deactivate_pdf_bundler_for_custom_guidebooks' );

	require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-for-custom-guidebooks.php';

	function run_pdf_bundler_for_custom_guidebooks() {
		$plugin = new Pdf_Bundler_For_Custom_Guidebooks();
		$plugin->run();
	}
	run_pdf_bundler_for_custom_guidebooks();
} else {
	// Show admin notice if libraries setup failed
	add_action('admin_notices', function() {
		echo '<div class="error"><p>';
		echo 'PDF Bundler: Required libraries could not be set up. Please check the error logs.';
		echo '</p></div>';
	});
}

// Load dependencies
require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-pdf-handler.php';
function init_pdf_bundler_library() {
    $library_setup = new PDF_Bundler_Library_Setup();
    $library_setup->init();
}
add_action('plugins_loaded', 'init_pdf_bundler_library');

// Add PDF Management column to Users list
function add_pdf_management_user_column($columns) {
    $columns['pdf_management'] = 'PDF Management';
    return $columns;
}
add_filter('manage_users_columns', 'add_pdf_management_user_column');

// Display PDF Management actions in Users list
function display_pdf_management_user_column($value, $column_name, $user_id) {
    if ($column_name !== 'pdf_management') {
        return $value;
    }

    $merged_pdf = get_user_meta($user_id, '_merged_pdf_path', true);
    $flipbook_url = get_user_meta($user_id, '_flipbook_url', true);
    
    if ($merged_pdf && file_exists($merged_pdf)) {
        $pdf_url = str_replace(ABSPATH, site_url('/'), $merged_pdf);
        echo '<div class="pdf-actions-container">';
        echo '<a href="' . esc_url($pdf_url) . '" target="_blank" class="button button-small pdf-action-btn view-pdf"><span class="dashicons dashicons-visibility"></span> View PDF</a>';
        
        if (!$flipbook_url) {
            echo '<a href="#" class="button button-small pdf-action-btn add-to-flipbook" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-book"></span> Add to Flipbook</a>';
        }
        
        echo '<a href="#" class="button button-small pdf-action-btn delete-pdf" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-trash"></span> Delete PDF</a>';
        echo '</div>';
    } else {
        echo '<em>No PDF available</em>';
    }
}
add_action('manage_users_custom_column', 'display_pdf_management_user_column', 10, 3);

// Update the JavaScript for handling actions
function add_pdf_management_scripts() {
    $screen = get_current_screen();
    if ($screen->base !== 'users') return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Delete PDF action
        $('.delete-pdf').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var userId = button.data('user-id');
            
            Swal.fire({
                title: 'Delete PDF?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3232',
                cancelButtonColor: '#6c7781',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delete_user_pdf',
                            user_id: userId
                        },
                        success: function(response) {
                            if (response.success) {
                                button.closest('.pdf-actions-container').html('<em>No PDF available</em>');
                                Swal.fire(
                                    'Deleted!',
                                    'The PDF has been deleted.',
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.data,
                                    'error'
                                );
                            }
                        }
                    });
                }
            });
        });

        // Add to Flipbook action
        $('.add-to-flipbook').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var userId = button.data('user-id');
            
            Swal.fire({
                title: 'Add to Flipbook?',
                text: 'This will make the PDF available in the customer flipbook.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2ea2cc',
                cancelButtonColor: '#6c7781',
                confirmButtonText: 'Yes, add it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'add_to_user_flipbook',
                            user_id: userId
                        },
                        success: function(response) {
                            if (response.success) {
                                button.remove();
                                Swal.fire(
                                    'Added!',
                                    'The PDF has been added to the flipbook.',
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.data,
                                    'error'
                                );
                            }
                        }
                    });
                }
            });
        });
    });
    </script>
    <?php
}

// Add AJAX handlers for user PDF management
function handle_delete_user_pdf() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
    }

    $merged_pdf = get_user_meta($user_id, '_merged_pdf_path', true);
    if ($merged_pdf && file_exists($merged_pdf)) {
        unlink($merged_pdf);
        delete_user_meta($user_id, '_merged_pdf_path');
        delete_user_meta($user_id, '_flipbook_url');
        wp_send_json_success('PDF deleted successfully');
    }

    wp_send_json_error('PDF not found');
}
add_action('wp_ajax_delete_user_pdf', 'handle_delete_user_pdf');

function handle_add_to_user_flipbook() {
    check_ajax_referer('pdf_bundler_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $pdf_path = isset($_POST['pdf_path']) ? sanitize_text_field($_POST['pdf_path']) : '';

    if (!$user_id || !$pdf_path || !file_exists($pdf_path)) {
        wp_send_json_error('Invalid parameters');
    }

    $library_setup = new PDF_Bundler_Library_Setup();
    $flipbook_url = $library_setup->push_to_flipbook($pdf_path, $user_id);
    
    if ($flipbook_url) {
        update_user_meta($user_id, '_flipbook_url', $flipbook_url);
        
        // Update the database record
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'pdf_bundler_merge_queue',
            array('flipbook_url' => $flipbook_url),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => 'Added to flipbook successfully',
            'flipbook_url' => $flipbook_url
        ));
    }

    wp_send_json_error('Failed to add to flipbook');
}
add_action('wp_ajax_add_to_user_flipbook', 'handle_add_to_user_flipbook');

// Add rewrite rule for PDF viewing
function add_pdf_view_endpoint() {
    add_rewrite_rule(
        'view-pdf/([^/]+)/?$',
        'index.php?pdf_view=1&pdf_id=$matches[1]',
        'top'
    );
}
add_action('init', 'add_pdf_view_endpoint');

// Add query var
function add_pdf_query_vars($vars) {
    $vars[] = 'pdf_view';
    $vars[] = 'pdf_id';
    return $vars;
}
add_filter('query_vars', 'add_pdf_query_vars');

// Handle PDF viewing
function handle_pdf_view() {
    if (get_query_var('pdf_view')) {
        $pdf_id = get_query_var('pdf_id');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pdf_bundler_merge_queue';
        $pdf = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $pdf_id
        ));
        
        if ($pdf && file_exists($pdf->custom_pdf_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($pdf->custom_pdf_path) . '"');
            readfile($pdf->custom_pdf_path);
            exit;
        }
        
        wp_die('PDF not found');
    }
}
add_action('template_redirect', 'handle_pdf_view');

// Add this function
function pdf_bundler_check_db_version() {
    $current_db_version = get_option('pdf_bundler_db_version', '0');
    
    if (version_compare($current_db_version, PDF_BUNDLER_DB_VERSION, '<')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-pdf-bundler-library-setup.php';
        $setup = new PDF_Bundler_Library_Setup();
        $setup->upgrade_database($current_db_version);
        update_option('pdf_bundler_db_version', PDF_BUNDLER_DB_VERSION);
    }
}
add_action('plugins_loaded', 'pdf_bundler_check_db_version');

// Add the cron job handler
function pdf_bundler_merge_check() {
    global $wpdb;
    
    error_log('Starting PDF Bundler merge check cron job at ' . current_time('mysql'));
    update_option('pdf_bundler_last_cron_run', time());
    
    // Log table names for debugging
    $merge_queue_table = $wpdb->prefix . 'pdf_bundler_merge_queue';
    $cities_table = $wpdb->prefix . 'pdf_bundler_cities';
    error_log("Checking tables: {$merge_queue_table} and {$cities_table}");
    
    // First, check if tables exist
    $merge_queue_exists = $wpdb->get_var("SHOW TABLES LIKE '{$merge_queue_table}'");
    $cities_exists = $wpdb->get_var("SHOW TABLES LIKE '{$cities_table}'");
    
    if (!$merge_queue_exists || !$cities_exists) {
        error_log("Missing tables - Merge Queue exists: " . ($merge_queue_exists ? 'yes' : 'no') . 
                 ", Cities exists: " . ($cities_exists ? 'yes' : 'no'));
        return;
    }
    
    // Log total records in each table
    $total_queue = $wpdb->get_var("SELECT COUNT(*) FROM {$merge_queue_table}");
    $total_cities = $wpdb->get_var("SELECT COUNT(*) FROM {$cities_table}");
    error_log("Total records - Merge Queue: {$total_queue}, Cities: {$total_cities}");
    
    // Check records that need merging with detailed diagnostics
    error_log("=== Detailed Diagnostics ===");
    
    // Check city match
    $city_match_query = "
        SELECT mq.id, mq.city as queue_city, c.city as cities_city
        FROM {$merge_queue_table} mq
        LEFT JOIN {$cities_table} c ON mq.city = c.city
        WHERE mq.is_merged = 0
    ";
    $city_match = $wpdb->get_results($city_match_query);
    error_log("City match check: " . print_r($city_match, true));
    
    // Check PDF paths
    $pdf_paths_query = "
        SELECT mq.id, mq.customer_bio_pdf, c.pdf_path as city_pdf_path
        FROM {$merge_queue_table} mq
        LEFT JOIN {$cities_table} c ON mq.city = c.city
        WHERE mq.is_merged = 0
    ";
    $pdf_paths = $wpdb->get_results($pdf_paths_query);
    error_log("PDF paths check: " . print_r($pdf_paths, true));
    
    // Original query for pending merges
    $query = "
        SELECT mq.*, c.pdf_path as city_pdf_path 
        FROM {$merge_queue_table} mq
        INNER JOIN {$cities_table} c 
        WHERE (mq.is_merged = 0 OR mq.merged_pdf_path IS NULL OR mq.flipbook_url IS NULL)
        AND c.pdf_path IS NOT NULL
        AND mq.customer_bio_pdf IS NOT NULL
        LIMIT 1
    ";
    error_log("Running main query: {$query}");
    
    $pending_merges = $wpdb->get_results($query);
    $found_count = is_array($pending_merges) ? count($pending_merges) : 0;
    error_log("Found {$found_count} pending merges after applying all conditions");
    
    // Show raw data from both tables
    $merge_queue_data = $wpdb->get_results("SELECT * FROM {$merge_queue_table} LIMIT 1");
    $cities_data = $wpdb->get_results("SELECT * FROM {$cities_table} LIMIT 1");
    error_log("Merge Queue first record: " . print_r($merge_queue_data, true));
    error_log("Cities first record: " . print_r($cities_data, true));

    if (empty($pending_merges)) {
        error_log('No pending merges or mismatches found that meet all criteria');
        return;
    }

    foreach ($pending_merges as $merge) {
        try {
            error_log("Processing merge ID: {$merge->id}");
            error_log("Customer Bio PDF: {$merge->customer_bio_pdf}");
            error_log("City PDF: {$merge->city_pdf_path}");
            
            // Skip if either PDF is missing
            if (!file_exists($merge->customer_bio_pdf) || !file_exists($merge->city_pdf_path)) {
                error_log("Missing PDF files for merge ID: {$merge->id}");
                $wpdb->update(
                    $wpdb->prefix . 'pdf_bundler_merge_queue',
                    array(
                        'status' => 'failed',
                        'last_error' => 'Missing PDF files'
                    ),
                    array('id' => $merge->id),
                    array('%s', '%s'),
                    array('%d')
                );
                continue;
            }

            // Set up merged PDF path
            $upload_dir = wp_upload_dir();
            $merged_dir = $upload_dir['basedir'] . '/pdf-bundler/merged-pdfs';
            if (!file_exists($merged_dir)) {
                wp_mkdir_p($merged_dir);
            }

            $merged_filename = 'merged_' . $merge->user_id . '_' . time() . '.pdf';
            $merged_path = $merged_dir . '/' . $merged_filename;

            error_log("Attempting to merge PDFs to: {$merged_path}");

            // Merge PDFs
            require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-pdf-handler.php';
            $pdf_handler = new PDF_Bundler_PDF_Handler();
            $merged = $pdf_handler->merge_pdfs(
                $merge->customer_bio_pdf,
                $merge->city_pdf_path,
                $merged_path,
                $merge->user_id
            );

            if ($merged) {
                error_log("PDFs merged successfully");
                
                // Create flipbook
                require_once PDF_BUNDLER_PATH . 'includes/class-pdf-bundler-library-setup.php';
                $library_setup = new PDF_Bundler_Library_Setup();
                $flipbook_url = $library_setup->push_to_flipbook($merged_path, $merge->user_id);

                error_log("Flipbook URL: {$flipbook_url}");

                // Update merge queue record
                $update_result = $wpdb->update(
                    $wpdb->prefix . 'pdf_bundler_merge_queue',
                    array(
                        'is_merged' => 1,
                        'merged_pdf_path' => $merged_path,
                        'flipbook_url' => $flipbook_url,
                        'merged_at' => current_time('mysql'),
                        'status' => 'completed'
                    ),
                    array('id' => $merge->id),
                    array('%d', '%s', '%s', '%s', '%s'),
                    array('%d')
                );

                error_log("Database update result: " . ($update_result !== false ? "Success" : "Failed - " . $wpdb->last_error));

                // Store the merged PDF path and flipbook URL in user meta
                update_user_meta($merge->user_id, '_merged_pdf_path', $merged_path);
                update_user_meta($merge->user_id, '_flipbook_url', $flipbook_url);

                error_log("Successfully merged PDFs for merge ID: {$merge->id}");
            } else {
                error_log("Failed to merge PDFs");
                throw new Exception("Failed to merge PDFs");
            }

        } catch (Exception $e) {
            error_log("Error merging PDFs for merge ID {$merge->id}: " . $e->getMessage());
            
            // Update status to failed
            $wpdb->update(
                $wpdb->prefix . 'pdf_bundler_merge_queue',
                array(
                    'status' => 'failed',
                    'last_error' => $e->getMessage()
                ),
                array('id' => $merge->id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }
}

// Register the cron schedule
register_activation_hook(__FILE__, 'activate_pdf_bundler_cron');
function activate_pdf_bundler_cron() {
    if (!wp_next_scheduled('pdf_merge_check_daily')) {
        wp_schedule_event(time(), 'daily', 'pdf_merge_check_daily');
    }
}

// Clean up cron job on deactivation
register_deactivation_hook(__FILE__, 'deactivate_pdf_bundler_cron');
function deactivate_pdf_bundler_cron() {
    wp_clear_scheduled_hook('pdf_merge_check_daily');
}

// Add admin menu for manual CRON trigger
function pdf_bundler_admin_menu() {
    add_submenu_page(
        'tools.php',
        'PDF Bundler Tools',
        'PDF Bundler Tools',
        'manage_options',
        'pdf-bundler-tools',
        'pdf_bundler_tools_page'
    );
}
add_action('admin_menu', 'pdf_bundler_admin_menu');

// Admin page callback
function pdf_bundler_tools_page() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submission
    if (isset($_POST['trigger_pdf_merge'])) {
        check_admin_referer('pdf_bundler_manual_merge');
        
        error_log('Manual PDF merge triggered');
        
        // Run the CRON job manually
        pdf_bundler_merge_check();
        
        // Show success message
        echo '<div class="notice notice-success is-dismissible"><p>PDF merge check completed. Check error logs for details.</p></div>';
    }

    // Display the admin interface
    ?>
    <div class="wrap">
        <h1>PDF Bundler Tools</h1>
        
        <div class="card">
            <h2>Manual PDF Merge</h2>
            <form method="post" action="">
                <?php wp_nonce_field('pdf_bundler_manual_merge'); ?>
                <p>Click the button below to manually trigger the PDF merge check:</p>
                <p><input type="submit" name="trigger_pdf_merge" class="button button-primary" value="Run PDF Merge Check Now"></p>
            </form>
        </div>
        
        <div class="card">
            <h2>Merge Queue Status</h2>
            <?php
            global $wpdb;
            $pending_count = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}pdf_bundler_merge_queue 
                WHERE is_merged = 0 OR merged_pdf_path IS NULL OR flipbook_url IS NULL
            ");
            echo "<p>Pending merges: {$pending_count}</p>";
            
            $last_run = get_option('pdf_bundler_last_cron_run');
            if ($last_run) {
                echo '<p>Last run: ' . date('Y-m-d H:i:s', $last_run) . '</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Handle CRON rescheduling
function pdf_bundler_handle_admin_actions() {
    if (isset($_GET['action']) && $_GET['action'] === 'reschedule_cron' 
        && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'pdf_bundler_reschedule_cron')) {
        
        // Clear existing schedule
        wp_clear_scheduled_hook('pdf_merge_check_daily');
        
        // Reschedule
        if (!wp_next_scheduled('pdf_merge_check_daily')) {
            wp_schedule_event(time(), 'daily', 'pdf_merge_check_daily');
        }
        
        // Redirect back with success message
        wp_redirect(add_query_arg('rescheduled', 'true', admin_url('tools.php?page=pdf-bundler-tools')));
        exit;
    }
}
add_action('admin_init', 'pdf_bundler_handle_admin_actions');

// Add success message for rescheduling
function pdf_bundler_admin_notices() {
    if (isset($_GET['page']) && $_GET['page'] === 'pdf-bundler-tools' && isset($_GET['rescheduled'])) {
        echo '<div class="notice notice-success"><p>CRON job rescheduled successfully!</p></div>';
    }
}
add_action('admin_notices', 'pdf_bundler_admin_notices');

// Add admin bar menu for quick CRON access
function pdf_bundler_admin_bar_menu($admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $admin_bar->add_menu(array(
        'id'    => 'pdf-bundler-tools',
        'title' => 'PDF Bundler',
        'href'  => admin_url('tools.php?page=pdf-bundler-tools'),
    ));

    // Use direct link to tools page instead of action URL
    $admin_bar->add_menu(array(
        'id'     => 'pdf-bundler-run-cron',
        'parent' => 'pdf-bundler-tools',
        'title'  => 'Run PDF Merge Check',
        'href'   => admin_url('tools.php?page=pdf-bundler-tools'),
    ));
}
add_action('admin_bar_menu', 'pdf_bundler_admin_bar_menu', 500);

// Handle the quick run action
function pdf_bundler_handle_quick_run() {
    if (isset($_GET['action']) && $_GET['action'] === 'run_merge_check' 
        && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'pdf_bundler_run_merge')) {
        
        do_action('pdf_merge_check_daily');
        
        wp_redirect(add_query_arg('merge_triggered', 'true', admin_url('tools.php?page=pdf-bundler-tools')));
        exit;
    }
}
add_action('admin_init', 'pdf_bundler_handle_quick_run');

// Add success message for quick run
function pdf_bundler_merge_notices() {
    if (isset($_GET['page']) && $_GET['page'] === 'pdf-bundler-tools' && isset($_GET['merge_triggered'])) {
        echo '<div class="notice notice-success is-dismissible"><p>PDF merge check has been triggered successfully!</p></div>';
    }
}
add_action('admin_notices', 'pdf_bundler_merge_notices');

// Add Merged PDF column to Users list
function add_merged_pdf_user_column($columns) {
    $columns['merged_pdf'] = 'Merged PDF';
    return $columns;
}
add_filter('manage_users_columns', 'add_merged_pdf_user_column');

// Display Merged PDF column content
function display_merged_pdf_user_column($value, $column_name, $user_id) {
    if ($column_name !== 'merged_pdf') {
        return $value;
    }

    global $wpdb;
    $merge_data = $wpdb->get_row($wpdb->prepare(
        "SELECT merged_pdf_path, flipbook_url, status, last_error 
        FROM {$wpdb->prefix}pdf_bundler_merge_queue 
        WHERE user_id = %d 
        ORDER BY id DESC 
        LIMIT 1",
        $user_id
    ));

    $output = '<div class="pdf-bundler-status">';
    
    if ($merge_data) {
        if ($merge_data->merged_pdf_path && file_exists($merge_data->merged_pdf_path)) {
            $pdf_url = str_replace(ABSPATH, site_url('/'), $merge_data->merged_pdf_path);
            $output .= '<div class="pdf-actions">';
            $output .= '<a href="' . esc_url($pdf_url) . '" target="_blank" class="button button-small"><span class="dashicons dashicons-pdf"></span> View PDF</a> ';
            
            if ($merge_data->flipbook_url) {
                $output .= '<a href="' . esc_url($merge_data->flipbook_url) . '" target="_blank" class="button button-small"><span class="dashicons dashicons-book"></span> View Flipbook</a> ';
            }
            
            $output .= '<button type="button" class="button button-small pdf-regenerate" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-update"></span> Regenerate</button>';
            $output .= '</div>';
            
            if ($merge_data->status === 'completed') {
                $output .= '<span class="pdf-status success">✓ Merged</span>';
            }
        } else {
            if ($merge_data->status === 'failed') {
                $output .= '<span class="pdf-status error">✗ Failed</span>';
                if ($merge_data->last_error) {
                    $output .= '<span class="pdf-error">' . esc_html($merge_data->last_error) . '</span>';
                }
            } else {
                $output .= '<span class="pdf-status pending">⟳ Pending</span>';
            }
            $output .= ' <button type="button" class="button button-small pdf-regenerate" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-update"></span> Generate</button>';
        }
    } else {
        $output .= '<button type="button" class="button button-small pdf-regenerate" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-update"></span> Generate</button>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_action('manage_users_custom_column', 'display_merged_pdf_user_column', 10, 3);

// Add styles for the PDF status column
function add_pdf_status_styles() {
    $screen = get_current_screen();
    if ($screen->base !== 'users') return;
    ?>
    <style>
        .pdf-bundler-status {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .pdf-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .pdf-status {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            line-height: 1.4;
        }
        .pdf-status.success {
            background: #d4edda;
            color: #155724;
        }
        .pdf-status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .pdf-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .pdf-error {
            color: #721c24;
            font-size: 12px;
            margin-top: 4px;
        }
        .pdf-regenerate .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            margin-top: 3px;
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.pdf-regenerate').on('click', function() {
            var button = $(this);
            var userId = button.data('user-id');
            
            button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-spin');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'regenerate_user_pdf',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('pdf_bundler_regenerate'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to regenerate PDF: ' + response.data);
                        button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-spin');
                    }
                },
                error: function() {
                    alert('Server error occurred');
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-spin');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_head', 'add_pdf_status_styles');

// Handle PDF regeneration
function handle_pdf_regeneration() {
    check_ajax_referer('pdf_bundler_regenerate', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
    }
    
    global $wpdb;
    
    // Reset merge status
    $wpdb->update(
        $wpdb->prefix . 'pdf_bundler_merge_queue',
        array(
            'is_merged' => 0,
            'status' => 'pending',
            'last_error' => null
        ),
        array('user_id' => $user_id),
        array('%d', '%s', '%s'),
        array('%d')
    );
    
    // Trigger immediate merge
    pdf_bundler_merge_check();
    
    wp_send_json_success();
}
add_action('wp_ajax_regenerate_user_pdf', 'handle_pdf_regeneration');

// Add Merged PDF column to Customer List table
function add_merged_pdf_customer_column($columns) {
    $columns['merged_pdf'] = 'Merged PDF';
    return $columns;
}
add_filter('manage_wpwc_customer_posts_columns', 'add_merged_pdf_customer_column');

// Display Merged PDF column content in Customer List
function display_merged_pdf_customer_column($column_name, $post_id) {
    if ($column_name !== 'merged_pdf') {
        return;
    }

    // Get user ID from customer post
    $user_id = get_post_meta($post_id, 'user_id', true);
    if (!$user_id) {
        echo '<em>No user associated</em>';
        return;
    }

    global $wpdb;
    // First check if there's a merge record
    $merge_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pdf_bundler_merge_queue 
        WHERE user_id = %d 
        ORDER BY id DESC 
        LIMIT 1",
        $user_id
    ));

    // If no merge record exists, create one
    if (!$merge_data) {
        // Get customer city
        $city = get_post_meta($post_id, 'city', true);
        
        // Get customer bio PDF
        $customer_bio_pdf = get_post_meta($post_id, 'customer_bio_pdf', true);
        
        // Insert new merge queue record
        $wpdb->insert(
            $wpdb->prefix . 'pdf_bundler_merge_queue',
            array(
                'user_id' => $user_id,
                'city' => $city,
                'customer_bio_pdf' => $customer_bio_pdf,
                'is_merged' => 0,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );
        
        // Get the newly created record
        $merge_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pdf_bundler_merge_queue 
            WHERE user_id = %d 
            ORDER BY id DESC 
            LIMIT 1",
            $user_id
        ));
    }

    echo '<div class="pdf-bundler-container">';
    echo '<div class="pdf-bundler-status">';
    
    if ($merge_data) {
        if ($merge_data->merged_pdf_path && file_exists($merge_data->merged_pdf_path)) {
            $pdf_url = str_replace(ABSPATH, site_url('/'), $merge_data->merged_pdf_path);
            echo '<div class="pdf-actions">';
            echo '<a href="' . esc_url($pdf_url) . '" target="_blank" class="button button-small"><span class="dashicons dashicons-pdf"></span> View PDF</a> ';
            
            if ($merge_data->flipbook_url) {
                echo '<a href="' . esc_url($merge_data->flipbook_url) . '" target="_blank" class="button button-small"><span class="dashicons dashicons-book"></span> View Flipbook</a> ';
            }
            
            echo '<button type="button" class="button button-small pdf-regenerate" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-update"></span> Regenerate</button>';
            echo '</div>';
            
            if ($merge_data->status === 'completed') {
                echo '<span class="pdf-status success">✓ Merged</span>';
            }
        } else {
            if ($merge_data->status === 'failed') {
                echo '<span class="pdf-status error">✗ Failed</span>';
                if ($merge_data->last_error) {
                    echo '<span class="pdf-error">' . esc_html($merge_data->last_error) . '</span>';
                }
            } else {
                echo '<span class="pdf-status pending">⟳ Pending</span>';
            }
            echo ' <button type="button" class="button button-small pdf-regenerate" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-update"></span> Generate</button>';
        }
    } else {
        echo '<button type="button" class="button button-small pdf-regenerate" data-user-id="' . esc_attr($user_id) . '"><span class="dashicons dashicons-update"></span> Generate</button>';
    }
    
    echo '</div>';
    echo '</div>';
}
add_action('manage_wpwc_customer_posts_custom_column', 'display_merged_pdf_customer_column', 10, 2);

// Add styles for Customer List table
function add_pdf_status_styles_customer_list() {
    $screen = get_current_screen();
    if ($screen->base !== 'edit' || $screen->post_type !== 'wpwc_customer') return;
    ?>
    <style>
        .pdf-bundler-container {
            max-width: 100%;
            overflow: hidden;
        }
        .pdf-bundler-status {
            display: flex;
            flex-direction: column;
            gap: 5px;
            width: 100%;
        }
        .pdf-actions {
            display: flex;
            gap: 5px;
            align-items: center;
            flex-wrap: wrap;
        }
        .pdf-status {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            line-height: 1.4;
        }
        .pdf-status.success {
            background: #d4edda;
            color: #155724;
        }
        .pdf-status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .pdf-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .pdf-error {
            color: #721c24;
            font-size: 12px;
            margin-top: 4px;
        }
        .pdf-regenerate .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            margin-top: 3px;
        }
        .button.button-small {
            white-space: nowrap;
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.pdf-regenerate').on('click', function() {
            var button = $(this);
            var userId = button.data('user-id');
            
            button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-spin');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'regenerate_user_pdf',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('pdf_bundler_regenerate'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to regenerate PDF: ' + response.data);
                        button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-spin');
                    }
                },
                error: function() {
                    alert('Server error occurred');
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-spin');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_head', 'add_pdf_status_styles_customer_list');