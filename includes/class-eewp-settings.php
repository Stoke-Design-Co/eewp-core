<?php

defined( 'ABSPATH' ) || exit;

class EEWP_Settings {

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

		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Register settings page.
	 */
	public function register_page() {
		add_options_page(
			__( 'Easy English WP (Lite)', 'easy-english-wp' ),
			__( 'Easy English WP', 'easy-english-wp' ),
			'manage_options',
			'easy-english-wp',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render settings page content.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Easy English WP (Lite)', 'easy-english-wp' ); ?></h1>
			<p><?php esc_html_e( 'Easy English WP helps you add simple, supportive content (image + short text) for your posts and pages, with a visitor toggle on the front end.', 'easy-english-wp' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %d: maximum enabled posts */
					esc_html__( 'Free version limit: up to %d published posts/pages can enable Easy English.', 'easy-english-wp' ),
					(int) $this->loader->max_enabled
				);
				?>
			</p>
			<p><strong><?php esc_html_e( 'Pro is coming soon.', 'easy-english-wp' ); ?></strong></p>
		</div>
		<?php
	}
}
