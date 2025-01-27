<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://codaemon.com
 * @since      1.0.0
 *
 * @package    Pdf_Bundler_For_Custom_Guidebooks
 * @subpackage Pdf_Bundler_For_Custom_Guidebooks/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Pdf_Bundler_For_Custom_Guidebooks
 * @subpackage Pdf_Bundler_For_Custom_Guidebooks/includes
 * @author     Rajib Naskar <rajib.naskar@codaemonsoftwares.com>
 */
class Pdf_Bundler_For_Custom_Guidebooks_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'pdf-bundler-for-custom-guidebooks',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
