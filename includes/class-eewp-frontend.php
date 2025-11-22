<?php

defined( 'ABSPATH' ) || exit;

class EEWP_Frontend {

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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
		add_action( 'wp_footer', array( $this, 'render_toolbar' ) );
	}

	/**
	 * Enqueue front-end assets when needed.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_display() ) {
			return;
		}

		wp_enqueue_style(
			'eewp-frontend',
			EEWP_PLUGIN_URL . 'assets/css/eewp-frontend.css',
			array(),
			EEWP_VERSION
		);

		wp_enqueue_script(
			'eewp-frontend',
			EEWP_PLUGIN_URL . 'assets/js/eewp-frontend.js',
			array(),
			EEWP_VERSION,
			true
		);

		$post_id = get_queried_object_id();

		wp_localize_script(
			'eewp-frontend',
			'eewpFrontend',
			array(
				'postId' => $post_id,
				'strings' => array(
					'toggle' => __( 'Toggle Easy English', 'easy-english-wp' ),
				),
			)
		);
	}

	/**
	 * Filter content to include Easy English markup.
	 *
	 * @param string $content Content.
	 *
	 * @return string
	 */
	public function filter_content( $content ) {
		if ( ! $this->should_display( true ) ) {
			return $content;
		}

		$post_id = get_queried_object_id() ? get_queried_object_id() : get_the_ID();
		$easy    = $this->loader->get_easy_content_html( $post_id );

		if ( '' === $easy ) {
			return $content;
		}

		ob_start();
		?>
		<div class="eewp-content-wrapper">
			<div class="eewp-normal-content">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="eewp-easy-content">
				<?php echo $easy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Output floating toolbar markup.
	 */
	public function render_toolbar() {
		if ( ! $this->should_display() ) {
			return;
		}
		?>
		<div class="eewp-toolbar" aria-label="<?php esc_attr_e( 'Easy English', 'easy-english-wp' ); ?>" role="region">
			<button type="button" class="eewp-toggle" aria-pressed="false">
				<span class="eewp-toggle-icon" aria-hidden="true">EE</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle Easy English', 'easy-english-wp' ); ?></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Determine if front-end features should load.
	 *
	 * @param bool $require_main_query Whether to require main query context.
	 *
	 * @return bool
	 */
	private function should_display( $require_main_query = false ) {
		// Avoid admin screens and feeds/previews.
		if ( is_admin() || is_feed() || is_preview() ) {
			return false;
		}

		// Defensive: skip builder preview contexts.
		if ( function_exists( 'doing_action' ) && ( doing_action( 'elementor/preview/enqueue_styles' ) || doing_action( 'elementor/frontend/the_content' ) ) ) {
			return false;
		}

		if ( ! is_singular( $this->loader->post_types ) ) {
			return false;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return false;
		}

		if ( $require_main_query ) {
			if ( ! in_the_loop() || ! is_main_query() ) {
				return false;
			}
		}

		if ( ! $this->loader->is_active( $post_id ) ) {
			return false;
		}

		$rows = $this->loader->get_rows( $post_id );

		return ! empty( $rows );
	}
}
