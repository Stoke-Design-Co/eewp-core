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
		$icons = array( 'book', 'info' );
		$key   = in_array( $key, $icons, true ) ? $key : 'book';

		$path = EEWP_PLUGIN_DIR . 'assets/icons/icon-' . $key . '.svg';

		if ( file_exists( $path ) ) {
			return file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		return '';
	}
}
