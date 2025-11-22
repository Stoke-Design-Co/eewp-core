<?php
/**
 * Core library bootstrap for Easy English WP.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-eewp-loader.php';
require_once __DIR__ . '/includes/class-eewp-admin.php';
require_once __DIR__ . '/includes/class-eewp-frontend.php';
require_once __DIR__ . '/includes/class-eewp-settings.php';
require_once __DIR__ . '/includes/class-eewp-limit.php';
require_once __DIR__ . '/includes/class-eewp-editor.php';
require_once __DIR__ . '/includes/class-eewp-elementor.php';

/**
 * Bootstrap the Easy English WP core loader.
 *
 * @return EEWP_Loader
 */
function eewp_core() {
	return EEWP_Loader::instance();
}

/**
 * Public helper to check whether Easy English is active for a post.
 *
 * @param null|int|\WP_Post $post Optional post object or ID.
 *
 * @return bool
 */
function eewp_is_active( $post = null ) {
	return EEWP_Loader::instance()->is_active( $post );
}

/**
 * Get Easy English HTML for a post.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function eewp_get_easy_english_content( $post_id ) {
	return EEWP_Loader::instance()->get_easy_content_html( $post_id );
}
