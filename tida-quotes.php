<?php
/*
Plugin Name: Tida Quotes
Description: This plugin is showing custom user quotes in WordPress Dashboard
Version: 1.0.1
Author: Tida Web
Author URI: https://tidaweb.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: tida-quotes
*/

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

if (!class_exists('TidaQuotes')) {
	class TidaQuotes
	{

		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{

			define( 'TIDAQUOTES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			define( 'TIDAQUOTES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

			// includes files
			require_once( TIDAQUOTES_PLUGIN_DIR . 'inc/class-quote-post-type.php');
			require_once( TIDAQUOTES_PLUGIN_DIR . 'inc/class-quote-meta.php');
			require_once( TIDAQUOTES_PLUGIN_DIR . 'inc/class-quote-import.php');

			do_action('tida_quotes_plugin_hooks');
			
		} // END public function __construct()
		
		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			// Do nothing
		} // END public static function activate

		/**
		 * Deactivate the plugin
		 */
		public static function deactivate()
		{
            // Delete option
            TidaWeb\TidaQuotes\TidaQuotesMeta::delete_meta_option();
            
		} // END public static function deactivate	

	} // END class TidaQuotes
} // END if(!class_exists('TidaQuotes'))

if (class_exists('TidaQuotes')) {
	// instantiate the plugin class
	new TidaQuotes();

    register_activation_hook( __FILE__, array( 'TidaQuotes', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'TidaQuotes', 'deactivate' ) );
}