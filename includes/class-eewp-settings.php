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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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
		$toolbar = $this->get_toolbar_settings();
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

			<hr />
			<h2><?php esc_html_e( 'Toolbar Styling', 'easy-english-wp' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'eewp_settings' );
				do_settings_sections( 'easy-english-wp' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'eewp_settings',
			'eewp_toolbar',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_toolbar_settings' ),
				'default'           => self::get_toolbar_defaults(),
			)
		);

		add_settings_section(
			'eewp_toolbar_section',
			__( 'Toolbar Styling', 'easy-english-wp' ),
			'__return_null',
			'easy-english-wp'
		);

		add_settings_field(
			'eewp_toolbar_icon',
			__( 'Toolbar Icon', 'easy-english-wp' ),
			array( $this, 'render_icon_field' ),
			'easy-english-wp',
			'eewp_toolbar_section'
		);

		add_settings_field(
			'eewp_toolbar_bg',
			__( 'Background Colour', 'easy-english-wp' ),
			array( $this, 'render_bg_field' ),
			'easy-english-wp',
			'eewp_toolbar_section'
		);

		add_settings_field(
			'eewp_toolbar_text',
			__( 'Text & Icon Colour', 'easy-english-wp' ),
			array( $this, 'render_text_field' ),
			'easy-english-wp',
			'eewp_toolbar_section'
		);

		add_settings_field(
			'eewp_toolbar_position',
			__( 'Toolbar Position', 'easy-english-wp' ),
			array( $this, 'render_position_field' ),
			'easy-english-wp',
			'eewp_toolbar_section'
		);

		add_settings_field(
			'eewp_toolbar_offset_desktop',
			__( 'Offset From Top (Desktop)', 'easy-english-wp' ),
			array( $this, 'render_offset_desktop_field' ),
			'easy-english-wp',
			'eewp_toolbar_section'
		);

		add_settings_field(
			'eewp_toolbar_offset_mobile',
			__( 'Offset From Top (Mobile)', 'easy-english-wp' ),
			array( $this, 'render_offset_mobile_field' ),
			'easy-english-wp',
			'eewp_toolbar_section'
		);
	}

	/**
	 * Default toolbar settings.
	 *
	 * @return array
	 */
	public static function get_toolbar_defaults() {
		return array(
			'icon'            => 'book',
			'bg_color'        => '#1e73be',
			'text_color'      => '#ffffff',
			'position'        => 'right',
			'offset_desktop'  => '20px',
			'offset_mobile'   => '12px',
		);
	}

	/**
	 * Get merged toolbar settings.
	 *
	 * @return array
	 */
	public function get_toolbar_settings() {
		$saved = get_option( 'eewp_toolbar', array() );

		return $this->merge_toolbar_settings( $saved );
	}

	/**
	 * Merge and sanitize toolbar settings.
	 *
	 * @param array $value Raw value.
	 *
	 * @return array
	 */
	public function merge_toolbar_settings( $value ) {
		return wp_parse_args( $this->sanitize_toolbar_settings( $value ), self::get_toolbar_defaults() );
	}

	/**
	 * Sanitize toolbar settings.
	 *
	 * @param array $value Raw value.
	 *
	 * @return array
	 */
	public function sanitize_toolbar_settings( $value ) {
		$defaults = self::get_toolbar_defaults();

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$icon      = isset( $value['icon'] ) ? sanitize_key( $value['icon'] ) : $defaults['icon'];
		$icons     = array( 'book', 'info', 'list' );
		$icon      = in_array( $icon, $icons, true ) ? $icon : $defaults['icon'];

		$bg_color   = isset( $value['bg_color'] ) ? sanitize_hex_color( $value['bg_color'] ) : '';
		$text_color = isset( $value['text_color'] ) ? sanitize_hex_color( $value['text_color'] ) : '';

		$position = isset( $value['position'] ) ? sanitize_key( $value['position'] ) : $defaults['position'];
		$position = in_array( $position, array( 'left', 'right' ), true ) ? $position : $defaults['position'];

		$offset_desktop = isset( $value['offset_desktop'] ) ? $this->sanitize_offset( $value['offset_desktop'] ) : $defaults['offset_desktop'];
		$offset_mobile  = isset( $value['offset_mobile'] ) ? $this->sanitize_offset( $value['offset_mobile'] ) : $defaults['offset_mobile'];

		return array(
			'icon'           => $icon,
			'bg_color'       => $bg_color ? $bg_color : $defaults['bg_color'],
			'text_color'     => $text_color ? $text_color : $defaults['text_color'],
			'position'       => $position,
			'offset_desktop' => $offset_desktop,
			'offset_mobile'  => $offset_mobile,
		);
	}

	/**
	 * Sanitize offset values (ensure px).
	 *
	 * @param string $value Raw value.
	 *
	 * @return string
	 */
	private function sanitize_offset( $value ) {
		$value = trim( (string) $value );

		if ( preg_match( '/^[0-9]+(px)?$/', $value ) ) {
			return rtrim( $value, 'px' ) . 'px';
		}

		return '20px';
	}

	/**
	 * Render icon radio field.
	 */
	public function render_icon_field() {
		$settings = $this->get_toolbar_settings();
		$icons    = array(
			'book' => __( 'Book icon', 'easy-english-wp' ),
			'info' => __( 'Info icon', 'easy-english-wp' ),
			'list' => __( 'List icon', 'easy-english-wp' ),
		);
		?>
		<div class="eewp-toolbar-icons">
			<?php foreach ( $icons as $key => $label ) : ?>
				<label style="display:inline-block;margin-right:16px;text-align:center;">
					<div style="border:1px solid #ccd0d4;border-radius:4px;padding:8px;margin-bottom:6px;"><?php echo wp_kses_post( $this->render_icon( $key ) ); ?></div>
					<input type="radio" name="eewp_toolbar[icon]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['icon'], $key ); ?> />
					<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render background colour field.
	 */
	public function render_bg_field() {
		$settings = $this->get_toolbar_settings();
		?>
		<input type="text" name="eewp_toolbar[bg_color]" value="<?php echo esc_attr( $settings['bg_color'] ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render text colour field.
	 */
	public function render_text_field() {
		$settings = $this->get_toolbar_settings();
		?>
		<input type="text" name="eewp_toolbar[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render position field.
	 */
	public function render_position_field() {
		$settings = $this->get_toolbar_settings();
		?>
		<select name="eewp_toolbar[position]">
			<option value="left" <?php selected( $settings['position'], 'left' ); ?>><?php esc_html_e( 'Left', 'easy-english-wp' ); ?></option>
			<option value="right" <?php selected( $settings['position'], 'right' ); ?>><?php esc_html_e( 'Right', 'easy-english-wp' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render desktop offset.
	 */
	public function render_offset_desktop_field() {
		$settings = $this->get_toolbar_settings();
		?>
		<input type="text" name="eewp_toolbar[offset_desktop]" value="<?php echo esc_attr( $settings['offset_desktop'] ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render mobile offset.
	 */
	public function render_offset_mobile_field() {
		$settings = $this->get_toolbar_settings();
		?>
		<input type="text" name="eewp_toolbar[offset_mobile]" value="<?php echo esc_attr( $settings['offset_mobile'] ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render SVG icon markup.
	 *
	 * @param string $key Icon key.
	 *
	 * @return string
	 */
	public function render_icon( $key ) {
		switch ( $key ) {
			case 'info':
				return '<svg width="36" height="36" viewBox="0 0 24 24" role="presentation" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 4a1.25 1.25 0 1 1-1.25 1.25A1.25 1.25 0 0 1 12 6Zm1.5 11h-3v-1h1v-4h-1v-1h2v5h1Z"/></svg>';
			case 'list':
				return '<svg width="36" height="36" viewBox="0 0 24 24" role="presentation" aria-hidden="true"><path fill="currentColor" d="M4 6h2v2H4zm0 5h2v2H4zm0 5h2v2H4zm4-10h12v2H8zm0 5h12v2H8zm0 5h12v2H8z"/></svg>';
			case 'book':
			default:
				return '<svg width="36" height="36" viewBox="0 0 24 24" role="presentation" aria-hidden="true"><path fill="currentColor" d="M18 2H8a2 2 0 0 0-2 2v15.5a2.5 2.5 0 0 0 3.356 2.329L12 20.5l2.644 1.329A2.5 2.5 0 0 0 18 19.5V4a2 2 0 0 0-2-2Zm0 17.5a.5.5 0 0 1-.724.447L12 18l-5.276 1.947A.5.5 0 0 1 6 19.5V4a.5.5 0 0 1 .5-.5H17a1 1 0 0 1 1 1Z"/></svg>';
		}
	}
}
