<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://codaemon.com
 * @since      1.0.0
 *
 * @package    Pdf_Bundler_For_Custom_Guidebooks
 * @subpackage Pdf_Bundler_For_Custom_Guidebooks/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pdf_Bundler_For_Custom_Guidebooks
 * @subpackage Pdf_Bundler_For_Custom_Guidebooks/admin
 * @author     Rajib Naskar <rajib.naskar@codaemonsoftwares.com>
 */
class Pdf_Bundler_For_Custom_Guidebooks_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add AJAX handlers
		add_action('wp_ajax_handle_city_pdf_upload', array($this, 'handle_city_pdf_upload'));
		add_action('wp_ajax_handle_delete_city_pdf', array($this, 'handle_delete_city_pdf'));
		add_action('wp_ajax_load_customer_details', array($this, 'load_customer_details'));
		add_action('wp_ajax_handle_customer_pdf_upload', array($this, 'handle_customer_pdf_upload'));
		add_action('wp_ajax_delete_city_pdf', array($this, 'handle_delete_city_pdf'));
		add_action('wp_ajax_delete_customer_pdf', array($this, 'handle_delete_customer_pdf'));

		// Add script and style enqueue actions
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_select2_assets'));

		add_action('wp_ajax_get_cities', array($this, 'get_cities'));
		add_action('wp_ajax_nopriv_get_cities', array($this, 'get_cities'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pdf_Bundler_For_Custom_Guidebooks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pdf_Bundler_For_Custom_Guidebooks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pdf-bundler-for-custom-guidebooks-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {
		// Debug log
		error_log('Hook: ' . $hook);

		// Only load on our plugin pages
		if (strpos($hook, 'pdf-bundler') === false) {
			return;
		}

		// Enqueue WordPress dashicons
		wp_enqueue_style('dashicons');

		// Enqueue our script
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/pdf-bundler-for-custom-guidebooks-admin.js',
			array('jquery'),
			$this->version,
			true  // Load in footer
		);

		// Add the WordPress AJAX URL and nonce to our script
		wp_localize_script(
			$this->plugin_name,
			'pdfBundlerAdmin',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'adminUrl' => admin_url(),
				'pluginPage' => admin_url('admin.php?page=pdf-bundler-customer'),
				'nonce' => wp_create_nonce('pdf_bundler_nonce')
			)
		);
	}

	function enqueue_select2_assets() {
		// Enqueue Select2 CSS
		wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css');

		// Enqueue Select2 JS
		wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', ['jquery'], null, true);
	
		// Custom JS to initialize Select2
		
		wp_add_inline_script(
			'select2-js',
			'jQuery(document).ready(function($) {
				$(".searchable-dropdown").select2({
					placeholder: "Search for a city...",
					ajax: {
						url: "' . admin_url('admin-ajax.php') . '?action=get_cities",
						dataType: "json",
						delay: 250,
						data: function (params) {
							return {
								q: params.term // Search term
							};
						},
						processResults: function (data) {
							return {
								results: data.results
							};
						},
						cache: true
					},
					minimumInputLength: 2 // Start searching after 2 characters
				});
			});'
		);
	}
	

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	public function add_plugin_admin_menu() {
		// Main menu
		add_menu_page(
			'PDF Bundler',
			'PDF Bundler',
			'manage_options',
			'pdf-bundler',
			array($this, 'display_customer_list_page'),
			'dashicons-pdf',
			30
		);

		// Customer List submenu
		add_submenu_page(
			'pdf-bundler',
			'Customer List',
			'Customer List',
			'manage_options',
			'pdf-bundler',
			array($this, 'display_customer_list_page')
		);

		// Upload Bio PDF submenu
		add_submenu_page(
			'pdf-bundler',
			'Upload Bio PDF',
			'Upload Bio PDF',
			'manage_options',
			'pdf-bundler-upload-bio',
			array($this, 'display_plugin_setup_page')
		);

		// Upload City PDF submenu
		add_submenu_page(
			'pdf-bundler',
			'Upload City PDF',
			'Upload City PDF',
			'manage_options',
			'pdf-bundler-city-pdf',
			array($this, 'display_city_pdf_page')
		);

		// PDF Tools submenu
		add_submenu_page(
			'pdf-bundler',
			'PDF Tools',
			'PDF Tools',
			'manage_options',
			'pdf-bundler-tools',
			array($this, 'display_pdf_tools_page')
		);

		// Remove old tools page
		remove_submenu_page('tools.php', 'pdf-bundler-tools');
	}

	/**
	 * Display the customer list page
	 */
	public function display_customer_list_page() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/customer-list.php';
	}

	/**
	 * Render the customer selection page
	 */
	public function display_plugin_customer_page() {
		// Get all customers with the 'customer' role
		$roles = array('customer', 'subscriber', 'agent');
		//$customers = get_users(array('role' => 'customer'));
		$customers = get_users(array(
			'role__in' => $roles, // Specify multiple roles
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		));
		include_once 'partials/customer-selection.php';
	}

	/**
	 * Render the PDF management page
	 */
	public function display_plugin_pdf_page() {
		include_once 'partials/pdf-management.php';
	}

	/**
	 * Ajax handler for loading customer details
	 */
	public function load_customer_details() {
		error_log('AJAX load_customer_details called');
		error_log('POST data: ' . print_r($_POST, true));

		try {
			check_ajax_referer('pdf_bundler_nonce', 'nonce');
			
			$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
			error_log('Customer ID: ' . $customer_id);
			
			if (!$customer_id) {
				throw new Exception('Invalid customer ID');
			}

			// Get customer details
			$customer = get_user_by('id', $customer_id);
			if (!$customer) {
				throw new Exception('Customer not found');
			}

			$response_data = array(
				'profile_picture' => get_avatar_url($customer_id, array('size' => 150)),
				'first_name' => get_user_meta($customer_id, 'first_name', true),
				'last_name' => get_user_meta($customer_id, 'last_name', true),
				'billing_email' => $customer->user_email,
				'billing_phone' => get_user_meta($customer_id, 'billing_phone', true),
				'billing_address_1' => get_user_meta($customer_id, 'billing_address_1', true),
				'billing_city' => get_user_meta($customer_id, 'billing_city', true),
				'billing_state' => get_user_meta($customer_id, 'billing_state', true),
				'billing_postcode' => get_user_meta($customer_id, 'billing_postcode', true)
			);

			error_log('Response data: ' . print_r($response_data, true));
			wp_send_json_success($response_data);

		} catch (Exception $e) {
			error_log('Error in load_customer_details: ' . $e->getMessage());
			wp_send_json_error($e->getMessage());
		}
	}

	/**
	 * Handle PDF upload and merging
	 */
	public function handle_pdf_upload() {
		try {
			error_log('Starting PDF upload handler');
			
			// Verify nonce
			check_ajax_referer('pdf_bundler_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				throw new Exception('Unauthorized access');
			}

			// Validate inputs
			if (!isset($_FILES['pdf1']) || !isset($_FILES['pdf2'])) {
				throw new Exception('Missing PDF files');
			}

			// Get and validate customer ID
			$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
			if (!$customer_id) {
				throw new Exception('Customer ID is required');
			}

			// Verify customer exists
			$customer = get_user_by('id', $customer_id);
			if (!$customer) {
				throw new Exception('Invalid customer ID');
			}

			// Initialize PDF handler
			error_log('Initializing PDF handler...');
			$pdf_handler = new PDF_Bundler_PDF_Handler();

			// Set up upload directory
			$upload_dir = wp_upload_dir();
			$pdf_dir = $upload_dir['basedir'] . '/pdf-bundler';
			
			// Add directory creation logging
			error_log('Creating directories...');
			error_log('PDF Directory: ' . $pdf_dir);
			
			if (!wp_mkdir_p($pdf_dir . '/custom-pdfs')) {
				throw new Exception('Failed to create custom-pdfs directory');
			}
			if (!wp_mkdir_p($pdf_dir . '/merged-pdfs')) {
				throw new Exception('Failed to create merged-pdfs directory');
			}

			// Check directory permissions
			error_log('Directory Permissions:');
			error_log('Custom PDFs dir: ' . substr(sprintf('%o', fileperms($pdf_dir . '/custom-pdfs')), -4));
			error_log('Merged PDFs dir: ' . substr(sprintf('%o', fileperms($pdf_dir . '/merged-pdfs')), -4));

			// Handle PDF uploads with logging
			error_log('Uploading PDF 1...');
			$pdf1_path = $this->handle_single_pdf_upload($_FILES['pdf1'], $pdf_dir . '/custom-pdfs/');
			error_log('PDF 1 path: ' . $pdf1_path);

			error_log('Uploading PDF 2...');
			$pdf2_path = $this->handle_single_pdf_upload($_FILES['pdf2'], $pdf_dir . '/custom-pdfs/');
			error_log('PDF 2 path: ' . $pdf2_path);

			if (!$pdf1_path || !$pdf2_path) {
				throw new Exception('Failed to upload PDFs');
			}

			// Check if PDF handler class exists
			if (!class_exists('PDF_Bundler_PDF_Handler')) {
				require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pdf-bundler-pdf-handler.php';
			}

			// Initialize PDF handler and merge PDFs
			error_log('Initializing PDF handler...');
			$pdf_handler = new PDF_Bundler_PDF_Handler();
			
			$merged_filename = 'merged_' . uniqid() . '.pdf';
			$merged_path = $pdf_dir . '/merged-pdfs/' . $merged_filename;
			error_log('Merging PDFs to: ' . $merged_path);

			// Use customer_id as user_id for merging
			$merged_result = $pdf_handler->merge_pdfs($pdf1_path, $pdf2_path, $merged_path, $customer_id);
			if (!$merged_result) {
				throw new Exception('Failed to merge PDFs');
			}

			// Insert record into database
			global $wpdb;
			$table_name = $wpdb->prefix . 'pdf_bundler_merge_queue';
			
			error_log('Inserting into database...');
			$insert_result = $wpdb->insert(
				$table_name,
				array(
					'user_id' => $customer_id,
					'custom_pdf_path' => $merged_path,
					'status' => 'completed',
					'created_at' => current_time('mysql')
				),
				array('%d', '%s', '%s', '%s')
			);

			if ($insert_result === false) {
				error_log('Database Error: ' . $wpdb->last_error);
				throw new Exception('Database error: ' . $wpdb->last_error);
			}

			error_log('Upload process completed successfully');
			wp_send_json_success(array(
				'message' => 'PDFs processed successfully',
				'redirect_url' => admin_url('admin.php?page=pdf-bundler-pdf-management')
			));

		} catch (Exception $e) {
			error_log('PDF Upload Error: ' . $e->getMessage());
			wp_send_json_error($e->getMessage());
		}
	}

	private function handle_single_pdf_upload($file, $target_dir) {
		error_log('Handling single PDF upload...');
		error_log('File data: ' . print_r($file, true));
		error_log('Target directory: ' . $target_dir);

		if ($file['error'] !== UPLOAD_ERR_OK) {
			throw new Exception('Upload error: ' . $this->get_upload_error_message($file['error']));
		}

		// Check file type
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime_type = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		error_log('File MIME type: ' . $mime_type);
		if ($mime_type !== 'application/pdf') {
			throw new Exception('Invalid file type. Only PDF files are allowed.');
		}

		$filename = uniqid() . '_' . sanitize_file_name($file['name']);
		$target_path = $target_dir . $filename;
		error_log('Target path: ' . $target_path);

		if (!move_uploaded_file($file['tmp_name'], $target_path)) {
			error_log('Failed to move uploaded file. PHP error: ' . error_get_last()['message']);
			throw new Exception('Failed to move uploaded file');
		}

		return $target_path;
	}

	private function get_upload_error_message($error_code) {
		switch ($error_code) {
			case UPLOAD_ERR_INI_SIZE:
				return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case UPLOAD_ERR_FORM_SIZE:
				return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
			case UPLOAD_ERR_PARTIAL:
				return 'The uploaded file was only partially uploaded';
			case UPLOAD_ERR_NO_FILE:
				return 'No file was uploaded';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Missing a temporary folder';
			case UPLOAD_ERR_CANT_WRITE:
				return 'Failed to write file to disk';
			case UPLOAD_ERR_EXTENSION:
				return 'A PHP extension stopped the file upload';
			default:
				return 'Unknown upload error';
		}
	}

	public function display_city_pdf_page() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/city-pdf-management.php';
	}

	public function display_plugin_setup_page() {
		// Debug log
		error_log('Displaying setup page');
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/customer-selection.php';
	}

	public function handle_customer_pdf_upload() {
		try {
			check_ajax_referer('pdf_bundler_nonce', 'nonce');
			
			if (!current_user_can('manage_options')) {
				throw new Exception('Unauthorized access');
			}

			// Validate inputs
			if (!isset($_FILES['customer_pdf'])) {
				throw new Exception('No PDF file uploaded');
			}

			$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
			if (!$customer_id) {
				throw new Exception('Customer ID is required');
			}

			// Set up upload directory
			$upload_dir = wp_upload_dir();
			$pdf_dir = $upload_dir['basedir'] . '/pdf-bundler/customer-pdfs';
			wp_mkdir_p($pdf_dir);

			// Handle PDF upload
			$file = $_FILES['customer_pdf'];
			
			// Validate file type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $file['tmp_name']);
			finfo_close($finfo);

			if ($mime_type !== 'application/pdf') {
				throw new Exception('Invalid file type. Only PDF files are allowed.');
			}

			$filename = uniqid() . '_' . sanitize_file_name($file['name']);
			$filepath = $pdf_dir . '/' . $filename;

			if (!move_uploaded_file($file['tmp_name'], $filepath)) {
				throw new Exception('Failed to upload file');
			}

			// Get customer's city from meta
			$customer_city = get_user_meta($customer_id, 'billing_city', true);
			if (empty($customer_city)) {
				throw new Exception('Customer billing city not found');
			}

			// Get city PDF
			global $wpdb;
			$city_pdf = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pdf_bundler_cities WHERE city = %s AND status = 'active'",
				$customer_city
			));

			if (!$city_pdf) {
				throw new Exception('No matching city PDF found for ' . $customer_city);
			}

			// Create merged-pdfs directory if it doesn't exist
			$merged_pdfs_dir = $upload_dir['basedir'] . '/pdf-bundler/merged-pdfs';
			wp_mkdir_p($merged_pdfs_dir);

			// Set up merged PDF path
			$merged_filename = 'merged_' . uniqid() . '.pdf';
			$merged_path = $merged_pdfs_dir . '/' . $merged_filename;

			// First insert record with 'in_progress' status
			$insert_result = $wpdb->insert(
				$wpdb->prefix . 'pdf_bundler_merge_queue',
				array(
					'user_id' => $customer_id,
					'customer_bio_pdf' => $filepath,
					'city' => $customer_city,
					'status' => 'in_progress',
					'created_at' => current_time('mysql'),
					'updated_at' => current_time('mysql')
				),
				array('%d', '%s', '%s', '%s', '%s', '%s')
			);

			if ($insert_result === false) {
				throw new Exception('Database error: ' . $wpdb->last_error);
			}

			$record_id = $wpdb->insert_id;

			// Attempt to merge PDFs
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pdf-bundler-pdf-handler.php';
			$pdf_handler = new PDF_Bundler_PDF_Handler();

			$merge_result = $pdf_handler->merge_pdfs($filepath, $city_pdf->pdf_path, $merged_path, $customer_id);

			if ($merge_result) {
				// Generate flipbook URL (modify this according to your flipbook generation logic)
				$flipbook_url = $this->generate_flipbook_url($merged_path, $customer_id);

				// Update record with success status and paths
				$wpdb->update(
					$wpdb->prefix . 'pdf_bundler_merge_queue',
					array(
						'merged_pdf_path' => $merged_path,
						'flipbook_url' => $flipbook_url,
						'status' => 'completed',
						'merged_at' => current_time('mysql'),
						'updated_at' => current_time('mysql')
					),
					array('id' => $record_id),
					array('%s', '%s', '%s', '%s', '%s'),
					array('%d')
				);
			} else {
				// Update record with error status
				$wpdb->update(
					$wpdb->prefix . 'pdf_bundler_merge_queue',
					array(
						'status' => 'failed',
						'updated_at' => current_time('mysql')
					),
					array('id' => $record_id),
					array('%s', '%s'),
					array('%d')
				);
				throw new Exception('Failed to merge PDFs');
			}

			wp_send_json_success(array(
				'message' => 'Customer Bio PDF uploaded successfully' . ($merge_result ? ' and merged with city PDF' : ''),
				'redirect' => admin_url('admin.php?page=pdf-bundler')
			));

		} catch (Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

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
			
			return $flipbook_url;
			
		} catch (Exception $e) {
			error_log('Flipbook URL Generation Error: ' . $e->getMessage());
			return false;
		}
	}

	public function handle_city_pdf_upload() {
		try {
			error_log('=== Starting City PDF Upload ===');
			error_log('POST data: ' . print_r($_POST, true));
			error_log('FILES data: ' . print_r($_FILES, true));
			error_log('Nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));

			// Verify nonce
			if (!check_ajax_referer('pdf_bundler_nonce', 'nonce', false)) {
				error_log('Nonce verification failed');
				throw new Exception('Security check failed');
			}

			// Check user capabilities
			if (!current_user_can('manage_options')) {
				error_log('User does not have required capabilities');
				throw new Exception('Unauthorized access');
			}

			// Validate file upload
			if (!isset($_FILES['city_pdf'])) {
				error_log('No file uploaded');
				throw new Exception('No PDF file uploaded');
			}

			$file = $_FILES['city_pdf'];
			if ($file['error'] !== UPLOAD_ERR_OK) {
				error_log('File upload error: ' . $file['error']);
				throw new Exception('File upload error: ' . $this->get_upload_error_message($file['error']));
			}

			// Validate city
			$city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
			if (empty($city)) {
				error_log('City not specified');
				throw new Exception('City is required');
			}

			// Set up upload directory
			$upload_dir = wp_upload_dir();
			$base_dir = $upload_dir['basedir'] . '/pdf-bundler/city-pdfs';
			
			error_log('Upload directory: ' . $base_dir);
			error_log('Directory exists: ' . (is_dir($base_dir) ? 'yes' : 'no'));
			error_log('Directory writable: ' . (is_writable($base_dir) ? 'yes' : 'no'));

			if (!is_dir($base_dir)) {
				if (!wp_mkdir_p($base_dir)) {
					error_log('Failed to create directory');
					throw new Exception('Failed to create upload directory');
				}
				chmod($base_dir, 0755);
			}

			// Validate file type
			$file_type = wp_check_filetype($file['name']);
			error_log('File type: ' . print_r($file_type, true));
			
			if ($file_type['type'] !== 'application/pdf') {
				throw new Exception('Invalid file type. Only PDF files are allowed.');
			}

			// Set up file path
			$filename = sanitize_title($city) . '.pdf';
			$filepath = $base_dir . '/' . $filename;
			error_log('Target filepath: ' . $filepath);

			// Delete existing file
			if (file_exists($filepath)) {
				unlink($filepath);
			}

			// Move uploaded file
			if (!move_uploaded_file($file['tmp_name'], $filepath)) {
				error_log('Move uploaded file failed');
				error_log('PHP error: ' . error_get_last()['message']);
				throw new Exception('Failed to save uploaded file');
			}

			chmod($filepath, 0644);

			// Update database
			global $wpdb;
			$cities_table = $wpdb->prefix . 'pdf_bundler_cities';

			// Check if table exists
			$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$cities_table'") === $cities_table;
			if (!$table_exists) {
				error_log('Cities table does not exist');
				throw new Exception('Database table not found');
			}

			// Update or insert record
			$existing = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM $cities_table WHERE city = %s",
				$city
			));

			if ($existing) {
				$result = $wpdb->update(
					$cities_table,
					array(
						'pdf_path' => $filepath,
						'status' => 'active',
						'updated_at' => current_time('mysql')
					),
					array('city' => $city),
					array('%s', '%s', '%s'),
					array('%s')
				);
				error_log('Updated existing record. Result: ' . ($result !== false ? 'success' : 'failed'));
			} else {
				$result = $wpdb->insert(
					$cities_table,
					array(
						'city' => $city,
						'pdf_path' => $filepath,
						'status' => 'active',
						'created_at' => current_time('mysql'),
						'updated_at' => current_time('mysql')
					),
					array('%s', '%s', '%s', '%s', '%s')
				);
				error_log('Inserted new record. Result: ' . ($result !== false ? 'success' : 'failed'));
			}

			if ($result === false) {
				error_log('Database error: ' . $wpdb->last_error);
				throw new Exception('Database error: ' . $wpdb->last_error);
			}

			error_log('=== City PDF Upload Completed Successfully ===');
			wp_send_json_success(array(
				'message' => 'City PDF uploaded successfully',
				'redirect' => admin_url('admin.php?page=pdf-bundler-city-pdf')
			));

		} catch (Exception $e) {
			error_log('City PDF upload error: ' . $e->getMessage());
			error_log('Stack trace: ' . $e->getTraceAsString());
			wp_send_json_error($e->getMessage());
		}
	}

	public function handle_delete_city_pdf() {
		try {
			// Verify nonce
			check_ajax_referer('delete_city_pdf_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				throw new Exception('Unauthorized access');
			}

			$city_id = isset($_POST['city_id']) ? intval($_POST['city_id']) : 0;
			if (!$city_id) {
				throw new Exception('Invalid city ID');
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'pdf_bundler_cities';

			// Get the PDF path before deletion
			$city_pdf = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$city_id
			));

			if (!$city_pdf) {
				throw new Exception('City PDF not found');
			}

			// Delete the file
			if (file_exists($city_pdf->pdf_path)) {
				unlink($city_pdf->pdf_path);
			}

			// Delete from database
			$result = $wpdb->delete(
				$table_name,
				array('id' => $city_id),
				array('%d')
			);

			if ($result === false) {
				throw new Exception('Failed to delete from database');
			}

			wp_send_json_success('City PDF deleted successfully');

		} catch (Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	private function debug_tables() {
		global $wpdb;
		
		// Check if tables exist
		$cities_table = $wpdb->prefix . 'pdf_bundler_cities';
		$merge_table = $wpdb->prefix . 'pdf_bundler_merge_queue';
		
		$cities_exists = $wpdb->get_var("SHOW TABLES LIKE '$cities_table'") == $cities_table;
		$merge_exists = $wpdb->get_var("SHOW TABLES LIKE '$merge_table'") == $merge_table;
		
		error_log('Tables exist check:');
		error_log('Cities table exists: ' . ($cities_exists ? 'Yes' : 'No'));
		error_log('Merge queue table exists: ' . ($merge_exists ? 'Yes' : 'No'));
		
		if ($cities_exists) {
			$columns = $wpdb->get_results("DESCRIBE $cities_table");
			error_log('Cities table structure:');
			error_log(print_r($columns, true));
		}
	}

	/**
	 * Display the PDF tools page
	 */
	public function display_pdf_tools_page() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/pdf-tools.php';
	}

	/**
	 * Handle customer PDF deletion
	 */
	public function handle_delete_customer_pdf() {
		try {
			// Verify nonce
			check_ajax_referer('delete_pdf_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				throw new Exception('Unauthorized access');
			}

			$pdf_id = isset($_POST['pdf_id']) ? intval($_POST['pdf_id']) : 0;
			if (!$pdf_id) {
				throw new Exception('Invalid PDF ID');
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'pdf_bundler_merge_queue';

			// Get the record before deletion
			$record = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$pdf_id
			));

			if (!$record) {
				throw new Exception('PDF record not found');
			}

			// Delete the bio PDF file
			if (!empty($record->customer_bio_pdf) && file_exists($record->customer_bio_pdf)) {
				unlink($record->customer_bio_pdf);
			}

			// Delete the merged PDF file if it exists
			if (!empty($record->merged_pdf_path) && file_exists($record->merged_pdf_path)) {
				unlink($record->merged_pdf_path);
			}

			// Delete the flipbook PDF if it exists
			$flipbook_path = get_user_meta($record->user_id, '_flipbook_path', true);
			if (!empty($flipbook_path) && file_exists($flipbook_path)) {
				unlink($flipbook_path);
			}

			// Delete from database
			$result = $wpdb->delete(
				$table_name,
				array('id' => $pdf_id),
				array('%d')
			);

			if ($result === false) {
				throw new Exception('Failed to delete from database');
			}

			// Clean up user meta
			delete_user_meta($record->user_id, '_flipbook_path');
			delete_user_meta($record->user_id, '_flipbook_url');

			wp_send_json_success('PDF deleted successfully');

		} catch (Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	/**
	 * Ajax get cities
	 */
	function get_cities() {
		global $wpdb;

		// Get the search term from AJAX request
		$search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

		// Fetch distinct cities that match the search term, prioritizing exact matches
		$table_name = $wpdb->prefix . 'us_cities';
		$query = $wpdb->prepare(
			"SELECT DISTINCT city 
			FROM $table_name 
			WHERE city LIKE %s 
			ORDER BY 
			CASE 
				WHEN city = %s THEN 0  -- Exact matches come first
				WHEN city LIKE %s THEN 1  -- Starts with the search term next
				ELSE 2  -- Other matches
			END, 
			city ASC  -- Alphabetical order for ties
			LIMIT 50",
			'%' . $wpdb->esc_like($search) . '%',
			$search,
			$wpdb->esc_like($search) . '%'
		);
		$cities = $wpdb->get_results($query);

		// Format data for Select2
		$results = [];
		foreach ($cities as $city) {
			$results[] = [
				'id' => $city->city,
				'text' => $city->city,
			];
		}

		// Return JSON response
		wp_send_json(['results' => $results]);
	}

}
