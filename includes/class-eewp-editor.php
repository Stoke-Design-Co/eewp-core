<?php

defined( 'ABSPATH' ) || exit;

class EEWP_Editor {

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

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Enqueue assets for the block editor sidebar.
	 */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, $this->loader->post_types, true ) ) {
			return;
		}

		wp_enqueue_script(
			'eewp-block-editor',
			EEWP_PLUGIN_URL . 'assets/js/eewp-block-editor.js',
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-i18n',
			),
			EEWP_VERSION,
			true
		);

		wp_localize_script(
			'eewp-block-editor',
			'eewpBlockEditor',
			array(
				'postTypes'    => $this->loader->post_types,
				'enabledKey'   => $this->loader->enabled_key,
				'rowsKey'      => $this->loader->rows_key,
				'metaBoxId'    => 'eewp-meta-box',
				'maxEnabled'   => (int) $this->loader->max_enabled,
				'strings'      => array(
					'panelTitle'     => __( 'Easy English – Lite', 'easy-english-wp' ),
					'enableLabel'    => __( 'Enable Easy English for this post/page', 'easy-english-wp' ),
					'rowsLabel'      => __( 'Rows', 'easy-english-wp' ),
					'editRowsButton' => __( 'Edit Easy English rows in meta box below', 'easy-english-wp' ),
					'limitNotice'    => sprintf(
						/* translators: %d: maximum enabled posts */
						__( 'Free version limit: %d published posts/pages can enable Easy English.', 'easy-english-wp' ),
						(int) $this->loader->max_enabled
					),
				),
			)
		);
	}
}
