<?php

defined( 'ABSPATH' ) || exit;

/**
 * Handles free edition enablement limits.
 */
class EEWP_Limit {

	/**
	 * @var EEWP_Loader
	 */
	private $loader;

	/**
	 * Constructor.
	 *
	 * @param EEWP_Loader $loader Loader instance.
	 */
	public function __construct( EEWP_Loader $loader ) {
		$this->loader = $loader;
	}

	/**
	 * Count how many published posts are enabled.
	 *
	 * @param int $exclude_post_id Optional post ID to exclude from the count.
	 *
	 * @return int
	 */
	public function count_enabled_posts( $exclude_post_id = 0 ) {
		$args = array(
			'post_type'              => $this->loader->post_types,
			'post_status'            => 'publish',
			'meta_key'               => $this->loader->enabled_key,
			'meta_value'             => 'yes',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( $exclude_post_id ) {
			$args['post__not_in'] = array( (int) $exclude_post_id );
		}

		$query = new WP_Query( $args );

		return (int) $query->post_count;
	}

	/**
	 * Whether the free limit has been reached.
	 *
	 * @param int $exclude_post_id Optional post ID to exclude from the count.
	 *
	 * @return bool
	 */
	public function has_reached_limit( $exclude_post_id = 0 ) {
		return $this->count_enabled_posts( $exclude_post_id ) >= $this->loader->max_enabled;
	}
}
