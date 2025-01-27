<?php
/**
 * PDF Bundler for Custom Guidebooks Activator
 *
 * @package    PDF_Bundler_For_Custom_Guidebooks
 * @subpackage PDF_Bundler_For_Custom_Guidebooks/includes
 */

if (!class_exists('PDF_Bundler_For_Custom_Guidebooks_Activator')):

class PDF_Bundler_For_Custom_Guidebooks_Activator {

	/**
	 * Fired during plugin activation.
	 */
	public static function activate() {
		try {
			// Create necessary directories
			$upload_dir = wp_upload_dir();
			$dirs = array(
				'/pdf-bundler',
				'/pdf-bundler/city-pdfs',
				'/pdf-bundler/customer-pdfs'
			);

			foreach ($dirs as $dir) {
				$path = $upload_dir['basedir'] . $dir;
				if (!file_exists($path)) {
					if (!wp_mkdir_p($path)) {
						throw new Exception("Failed to create directory: $path");
					}
					chmod($path, 0755);
				}
			}

			// Create tables
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pdf-bundler-library-setup.php';
			$setup = new PDF_Bundler_Library_Setup();
			$setup->create_tables();

		} catch (Exception $e) {
			error_log('PDF Bundler activation error: ' . $e->getMessage());
			wp_die('Failed to activate PDF Bundler plugin: ' . $e->getMessage());
		}
	}
}

endif;
