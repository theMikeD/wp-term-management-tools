<?php
/*
Plugin Name: Term Management Tools
Version: 2.0.2
Description: Allows you to merge terms, move terms between taxonomies, and set term parents, individually or in bulk. The "Change Taxonomy" option supports WPML-translated terms.
Author: theMikeD, scribu
Author URI: https://www.codenamemiked.com
Plugin URI: https://www.codenamemiked.com/plugins/term-management-tools/
Text Domain: term-management-tools
Domain Path: /lang
Requires PHP: 7.1
*/

define( 'CNMD_TMT_PLUGIN_URL_RELATIVE', basename( dirname( __FILE__ ) ) );
define( 'CNMD_TMT_URL', plugin_dir_url( __FILE__ ) );

/**
 * The class autoloader.
 */
spl_autoload_register(
	function( $class ) {

		// We only care about CNMD-namespaced classes
		if ( ! preg_match( '/^CNMD\\\TMT/', $class ) ) {
			return;
		}

		$filepath = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		$filename = plugin_dir_path( __FILE__ ) . '/classes/class-' . basename( $filepath );
		if ( file_exists( $filename ) ) {
			include_once $filename;
			return;
		}
	}
);


/**
 * Initialize the plugin.
 */
function init_term_management_tools() {
	$tmt = new \CNMD\TMT\TermManagementTools();
	$tmt->init();
}
init_term_management_tools();
