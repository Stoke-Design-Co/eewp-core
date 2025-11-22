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

		$toolbar = $this->loader->get_toolbar_settings();

		wp_enqueue_style(
			'eewp-frontend',
			EEWP_PLUGIN_URL . 'assets/css/eewp-frontend.css',
			array(),
			EEWP_VERSION
		);

		$inline_css  = ':root{';
		$inline_css .= '--eewp-toolbar-bg:' . esc_html( $toolbar['bg_color'] ) . ';';
		$inline_css .= '--eewp-toolbar-color:' . esc_html( $toolbar['text_color'] ) . ';';
		$inline_css .= '--eewp-toolbar-top-desktop:' . esc_html( $toolbar['offset_desktop'] ) . ';';
		$inline_css .= '--eewp-toolbar-top-mobile:' . esc_html( $toolbar['offset_mobile'] ) . ';';
		$inline_css .= '}';
		wp_add_inline_style( 'eewp-frontend', $inline_css );

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
				'toolbar' => array(
					'position' => $toolbar['position'],
					'icon'     => $toolbar['icon'],
				),
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
		$toolbar = $this->loader->get_toolbar_settings();
		$position_class = 'eewp-toolbar--' . ( 'left' === $toolbar['position'] ? 'left' : 'right' );
		$icon_markup    = $this->render_icon( $toolbar['icon'] );
		?>
		<div class="eewp-toolbar <?php echo esc_attr( $position_class ); ?>" aria-label="<?php esc_attr_e( 'Easy English', 'easy-english-wp' ); ?>" role="region">
			<button type="button" class="eewp-toggle" aria-pressed="false">
				<span class="eewp-toggle-icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
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

	/**
	 * Render toolbar icon SVG.
	 *
	 * @param string $key Icon key.
	 *
	 * @return string
	 */
	private function render_icon( $key ) {
		switch ( $key ) {
			case 'info':
				return '<svg width="20" height="20" viewBox="0 0 24 24" role="presentation" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 4a1.25 1.25 0 1 1-1.25 1.25A1.25 1.25 0 0 1 12 6Zm1.5 11h-3v-1h1v-4h-1v-1h2v5h1Z"/></svg>';
			case 'list':
				return '<svg width="20" height="20" viewBox="0 0 24 24" role="presentation" aria-hidden="true"><path fill="currentColor" d="M4 6h2v2H4zm0 5h2v2H4zm0 5h2v2H4zm4-10h12v2H8zm0 5h12v2H8zm0 5h12v2H8z"/></svg>';
			case 'book':
			default:
				return '<svg width="20" height="20" viewBox="0 0 24 24" role="presentation" aria-hidden="true"><path fill="currentColor" d="M18 2H8a2 2 0 0 0-2 2v15.5a2.5 2.5 0 0 0 3.356 2.329L12 20.5l2.644 1.329A2.5 2.5 0 0 0 18 19.5V4a2 2 0 0 0-2-2Zm0 17.5a.5.5 0 0 1-.724.447L12 18l-5.276 1.947A.5.5 0 0 1 6 19.5V4a.5.5 0 0 1 .5-.5H17a1 1 0 0 1 1 1Z"/></svg>';
		}
	}
}
