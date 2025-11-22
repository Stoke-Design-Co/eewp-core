<?php
/**
 * Plugin Name: Easy English WP
 * Description: Easy English WP (Lite) lets editors author Easy English content with per-page toggles and a floating toolbar.
 * Version: 0.1.0
 * Author: Easy English WP
 * Text Domain: easy-english-wp
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'EEWP_EDITION' ) ) {
	define( 'EEWP_EDITION', 'free' );
}

define( 'EEWP_VERSION', '0.1.0' );
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
