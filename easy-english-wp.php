<?php
/**
 * Plugin Name: Easy English WP
 * Plugin URI: https://example.com/easy-english-wp/
 * Description: Easy English WP (Lite) lets editors add Easy English/Easy Read rows (image + short text) to posts/pages and provides a front-end toggle to switch between normal and Easy English content. Free version limited to 5 posts/pages.
 * Version: 1.0.0
 * Author: Stoke Design Co
 * Author URI: https://stokedesign.co/
 * Text Domain: easy-english-wp
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.6
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'EEWP_EDITION' ) ) {
	define( 'EEWP_EDITION', 'free' );
}

define( 'EEWP_VERSION', '1.0.0' );
define( 'EEWP_PLUGIN_FILE', __FILE__ );
define( 'EEWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EEWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/eewp-core.php';

/**
 * Load translations.
 */
function eewp_load_textdomain() {
	load_plugin_textdomain( 'easy-english-wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'eewp_load_textdomain' );

/**
 * Bootstrap the core plugin on plugins_loaded.
 */
function eewp_bootstrap() {
	if ( function_exists( 'eewp_core' ) ) {
		eewp_core();
	}
}
add_action( 'plugins_loaded', 'eewp_bootstrap', 20 );
