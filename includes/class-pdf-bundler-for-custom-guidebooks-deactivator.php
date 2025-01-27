<?php

/**
 * PDF Bundler for Custom Guidebooks Deactivator
 *
 * @package    PDF_Bundler_For_Custom_Guidebooks
 * @subpackage PDF_Bundler_For_Custom_Guidebooks/includes
 */

class PDF_Bundler_For_Custom_Guidebooks_Deactivator {

	/**
	 * Clean up when plugin is deactivated.
	 */
	public static function deactivate() {
		// Remove scheduled CRON jobs
		wp_clear_scheduled_hook('pdf_bundler_merge_pdfs_cron');
		wp_clear_scheduled_hook('pdf_bundler_generate_flipbooks_cron');

		// Don't remove tables or directories on deactivation
		// Only remove them on uninstall
	}
}
