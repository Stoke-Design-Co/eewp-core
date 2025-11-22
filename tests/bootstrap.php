<?php
/**
 * PHPUnit bootstrap file for Easy English WP.
 *
 * @package easy-english-wp
 */

// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
error_reporting( E_ALL );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// TODO: Set WP_TESTS_DIR in your environment or adjust the fallback path.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress tests bootstrap at {$_tests_dir}/includes/functions.php\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin.
 */
function eewp_tests_manually_load_plugin() {
	require dirname( __DIR__ ) . '/easy-english-wp.php';
}
tests_add_filter( 'muplugins_loaded', 'eewp_tests_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
