<?php

defined( 'ABSPATH' ) || exit;

class EEWP_Loader {

	/**
	 * @var EEWP_Loader|null
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	public $enabled_key = 'eewp_enabled';

	/**
	 * @var string
	 */
	public $rows_key = 'eewp_rows';

	/**
	 * @var int
	 */
	public $max_enabled = 5;

	/**
	 * @var array
	 */
	public $post_types = array( 'post', 'page' );

	/**
	 * @var EEWP_Admin
	 */
	public $admin;

	/**
	 * @var EEWP_Frontend
	 */
	public $frontend;

	/**
	 * @var EEWP_Settings
	 */
	public $settings;

	/**
	 * @var EEWP_Limit
	 */
	public $limit;

	/**
	 * @var EEWP_Editor
	 */
	public $editor;

	/**
	 * Singleton constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );

		$this->limit    = new EEWP_Limit( $this );
		$this->admin    = new EEWP_Admin( $this );
		$this->frontend = new EEWP_Frontend( $this );
		$this->settings = new EEWP_Settings( $this );
		$this->editor   = new EEWP_Editor( $this );
	}

	/**
	 * Get singleton instance.
	 *
	 * @return EEWP_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register custom post meta keys.
	 */
	public function register_meta() {
		foreach ( $this->post_types as $post_type ) {
			register_post_meta(
				$post_type,
				$this->enabled_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'default'           => '',
					'auth_callback'     => array( $this, 'meta_auth_callback' ),
					'sanitize_callback' => array( $this, 'sanitize_enabled_meta' ),
				)
			);

			register_post_meta(
				$post_type,
				$this->rows_key,
				array(
					'type'              => 'array',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => array( $this, 'meta_auth_callback' ),
					'sanitize_callback' => array( $this, 'sanitize_rows_meta' ),
				)
			);
		}
	}

	/**
	 * Capability gate for meta.
	 *
	 * @param bool   $allowed  Whether the user can add the post meta. Default false.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 *
	 * @return bool
	 */
	public function meta_auth_callback( $allowed = false, $meta_key = '', $post_id = 0 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Normalize enabled meta.
	 *
	 * @param mixed  $value       Incoming value.
	 * @param string $meta_key    Meta key.
	 * @param string $object_type Object type.
	 *
	 * @return string
	 */
	public function sanitize_enabled_meta( $value, $meta_key = '', $object_type = '' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		unset( $meta_key, $object_type );

		return ( ! empty( $value ) && 'yes' === $value ) ? 'yes' : '';
	}

	/**
	 * Sanitize rows meta.
	 *
	 * @param mixed  $value       Incoming value.
	 * @param string $meta_key    Meta key.
	 * @param string $object_type Object type.
	 *
	 * @return array
	 */
	public function sanitize_rows_meta( $value, $meta_key = '', $object_type = '' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		unset( $meta_key, $object_type );

		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $row ) {
			$prepared = $this->prepare_row( $row );

			if ( empty( $prepared ) ) {
				continue;
			}

			$clean[] = $prepared;
		}

		return $clean;
	}

	/**
	 * Prepare a single row array.
	 *
	 * @param array $row Row data.
	 *
	 * @return array|null
	 */
	public function prepare_row( $row ) {
		if ( ! is_array( $row ) ) {
			return null;
		}

		$image_id = isset( $row['image_id'] ) ? absint( $row['image_id'] ) : 0;
		$text     = isset( $row['text'] ) ? sanitize_textarea_field( wp_unslash( $row['text'] ) ) : '';

		if ( '' === $text && 0 === $image_id ) {
			return null;
		}

		return array(
			'image_id' => $image_id > 0 ? $image_id : null,
			'text'     => $text,
		);
	}

	/**
	 * Check whether Easy English is active for a post.
	 *
	 * @param null|int|\WP_Post $post Optional post object or ID.
	 *
	 * @return bool
	 */
	public function is_active( $post = null ) {
		$post = get_post( $post );

		if ( ! $post || ! in_array( $post->post_type, $this->post_types, true ) ) {
			return false;
		}

		return 'yes' === get_post_meta( $post->ID, $this->enabled_key, true );
	}

	/**
	 * Get sanitized rows for a post.
	 *
	 * @param null|int|\WP_Post $post Optional post object or ID.
	 *
	 * @return array
	 */
	public function get_rows( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$rows = get_post_meta( $post->ID, $this->rows_key, true );

		return $this->sanitize_rows_meta( $rows );
	}

	/**
	 * Count how many published posts are enabled.
	 *
	 * @param int $exclude_post_id Optional post ID to exclude from the count.
	 *
	 * @return int
	 */
	public function count_enabled_posts( $exclude_post_id = 0 ) {
		return $this->limit->count_enabled_posts( $exclude_post_id );
	}

	/**
	 * Whether the free limit has been reached.
	 *
	 * @param int $exclude_post_id Optional post ID to exclude from the count.
	 *
	 * @return bool
	 */
	public function has_reached_limit( $exclude_post_id = 0 ) {
		return $this->limit->has_reached_limit( $exclude_post_id );
	}

	/**
	 * Build Easy English HTML for a post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public function get_easy_content_html( $post_id ) {
		$rows = $this->get_rows( $post_id );

		if ( empty( $rows ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="eewp-container" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<?php foreach ( $rows as $row ) : ?>
				<div class="eewp-row">
					<?php if ( ! empty( $row['image_id'] ) ) : ?>
						<div class="eewp-row-image">
							<?php echo wp_kses_post( wp_get_attachment_image( (int) $row['image_id'], 'medium' ) ); ?>
						</div>
					<?php endif; ?>
					<div class="eewp-row-text">
						<?php echo wp_kses_post( wpautop( $row['text'] ) ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
