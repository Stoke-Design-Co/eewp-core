<?php

defined( 'ABSPATH' ) || exit;

class EEWP_Admin {

	/**
	 * @var EEWP_Loader
	 */
	private $loader;

	/**
	 * @var bool
	 */
	private $limit_blocked = false;

	/**
	 * @var string
	 */
	private $limit_query_arg = 'eewp_limit_reached';

	/**
	 * Constructor.
	 *
	 * @param EEWP_Loader $loader Loader instance.
	 */
	public function __construct( EEWP_Loader $loader ) {
		$this->loader = $loader;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		foreach ( $this->loader->post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_list_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_list_column' ), 10, 2 );
		}
	}

	/**
	 * Add meta boxes for supported post types.
	 */
	public function add_meta_box() {
		foreach ( $this->loader->post_types as $post_type ) {
			add_meta_box(
				'eewp-meta-box',
				__( 'Easy English – Lite', 'easy-english-wp' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->post_type, $this->loader->post_types, true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'eewp-admin',
			EEWP_PLUGIN_URL . 'assets/css/eewp-admin.css',
			array(),
			EEWP_VERSION
		);

		wp_enqueue_script(
			'eewp-admin',
			EEWP_PLUGIN_URL . 'assets/js/eewp-admin.js',
			array( 'media-editor' ),
			EEWP_VERSION,
			true
		);

		wp_localize_script(
			'eewp-admin',
			'eewpAdmin',
			array(
				'mediaTitle'   => __( 'Select image', 'easy-english-wp' ),
				'mediaButton'  => __( 'Use this image', 'easy-english-wp' ),
				'maxEnabled'   => (int) $this->loader->max_enabled,
				'enabledLabel' => __( 'Enable Easy English for this post/page', 'easy-english-wp' ),
			)
		);
	}

	/**
	 * Render the meta box UI.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		$enabled = $this->loader->is_active( $post ) ? 'yes' : '';
		$rows    = $this->loader->get_rows( $post );

		wp_nonce_field( 'eewp_meta_nonce', 'eewp_meta_nonce' );
		$placeholder = esc_attr__( 'No image selected', 'easy-english-wp' );
		?>
		<div class="eewp-meta-box" data-enabled="<?php echo esc_attr( $enabled ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
			<p>
				<label>
					<input type="checkbox" name="eewp_enabled" value="yes" <?php checked( 'yes', $enabled ); ?> />
					<?php esc_html_e( 'Enable Easy English for this post/page', 'easy-english-wp' ); ?>
				</label>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %d: maximum enabled posts */
					esc_html__( 'Free version limit: %d published posts/pages can enable Easy English.', 'easy-english-wp' ),
					(int) $this->loader->max_enabled
				);
				?>
			</p>
			<p class="description">
				<?php
				$link = 'https://www.learningdisabilityservice-leeds.nhs.uk/easy-on-the-i/image-bank/';
				printf(
					/* translators: %s: help URL for images */
					esc_html__( 'Need images? Visit %s as a resource.', 'easy-english-wp' ),
					'<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">learningdisabilityservice-leeds.nhs.uk/easy-on-the-i/image-bank/</a>'
				);
				?>
			</p>

			<div class="eewp-rows-wrapper" <?php echo 'yes' === $enabled ? '' : 'style="display:none;"'; ?>>
				<div class="eewp-rows" data-next-index="<?php echo esc_attr( max( 0, count( $rows ) ) ); ?>">
					<?php
					if ( ! empty( $rows ) ) {
						foreach ( $rows as $index => $row ) {
							$this->render_row_fields( $index, $row );
						}
					}
					?>
				</div>
				<p>
					<button type="button" class="button eewp-add-row"><?php esc_html_e( 'Add row', 'easy-english-wp' ); ?></button>
				</p>
			</div>
		</div>

		<script type="text/template" id="eewp-row-template">
			<?php
			$template_row = array(
				'image_id' => null,
				'text'     => '',
			);
			$this->render_row_fields( '__INDEX__', $template_row );
			?>
		</script>
		<?php
	}

	/**
	 * Output a single row's fields.
	 *
	 * @param int|string $index Row index.
	 * @param array      $row   Row data.
	 */
	private function render_row_fields( $index, $row ) {
		$image_id   = isset( $row['image_id'] ) ? absint( $row['image_id'] ) : 0;
		$text_value = isset( $row['text'] ) ? $row['text'] : '';
		?>
		<div class="eewp-row" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="eewp-row__actions">
				<button type="button" class="button button-small eewp-move-up" aria-label="<?php esc_attr_e( 'Move row up', 'easy-english-wp' ); ?>"><?php esc_html_e( 'Up', 'easy-english-wp' ); ?></button>
				<button type="button" class="button button-small eewp-move-down" aria-label="<?php esc_attr_e( 'Move row down', 'easy-english-wp' ); ?>"><?php esc_html_e( 'Down', 'easy-english-wp' ); ?></button>
				<button type="button" class="button-link eewp-delete-row"><?php esc_html_e( 'Delete row', 'easy-english-wp' ); ?></button>
			</div>

			<div class="eewp-field">
				<input type="hidden" class="eewp-image-id" name="eewp_rows[<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( $image_id ); ?>" />
				<div class="eewp-image-preview">
					<?php
					if ( $image_id ) {
						echo wp_kses_post( wp_get_attachment_image( $image_id, 'thumbnail' ) );
					} else {
						echo '<span class="eewp-image-placeholder">' . esc_html__( 'No image selected', 'easy-english-wp' ) . '</span>';
					}
					?>
				</div>
				<div class="eewp-image-buttons">
					<button type="button" class="button eewp-select-image"><?php esc_html_e( 'Choose image', 'easy-english-wp' ); ?></button>
					<button type="button" class="button button-link-delete eewp-remove-image"><?php esc_html_e( 'Remove image', 'easy-english-wp' ); ?></button>
				</div>
			</div>

			<div class="eewp-field">
				<label class="screen-reader-text" for="eewp-row-text-<?php echo esc_attr( $index ); ?>">
					<?php esc_html_e( 'Easy English text', 'easy-english-wp' ); ?>
				</label>
				<textarea id="eewp-row-text-<?php echo esc_attr( $index ); ?>" name="eewp_rows[<?php echo esc_attr( $index ); ?>][text]" rows="3"><?php echo esc_textarea( $text_value ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta when a post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post   Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function save_meta( $post_id, $post, $update ) {
		unset( $update ); // Unused.

		if ( ! isset( $_POST['eewp_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eewp_meta_nonce'] ) ), 'eewp_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->loader->post_types, true ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$enabled_value    = isset( $_POST['eewp_enabled'] ) ? 'yes' : '';
		$previous_enabled = get_post_meta( $post_id, $this->loader->enabled_key, true );
		$was_enabled      = 'yes' === $previous_enabled;

		// Enforce 5-item limit only for publishes and when turning on.
		if ( 'yes' === $enabled_value && ! $was_enabled && 'publish' === $post->post_status && $this->loader->has_reached_limit( $post_id ) ) {
			$enabled_value     = '';
			$this->limit_blocked = true;
			add_filter( 'redirect_post_location', array( $this, 'add_limit_query_arg' ) );
		}

		update_post_meta( $post_id, $this->loader->enabled_key, $enabled_value );

		$raw_rows = isset( $_POST['eewp_rows'] ) ? (array) $_POST['eewp_rows'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$rows     = $this->sanitize_rows_from_request( $raw_rows );

		update_post_meta( $post_id, $this->loader->rows_key, $rows );
	}

	/**
	 * Sanitize rows array from request.
	 *
	 * @param array $raw_rows Raw request rows.
	 *
	 * @return array
	 */
	private function sanitize_rows_from_request( $raw_rows ) {
		$clean = array();

		foreach ( $raw_rows as $row ) {
			$prepared = $this->loader->prepare_row( $row );

			if ( empty( $prepared ) ) {
				continue;
			}

			$clean[] = $prepared;
		}

		return $clean;
	}

	/**
	 * Add query arg to redirect URL when limit reached.
	 *
	 * @param string $location Redirect URL.
	 *
	 * @return string
	 */
	public function add_limit_query_arg( $location ) {
		if ( ! $this->limit_blocked ) {
			return $location;
		}

		return add_query_arg( $this->limit_query_arg, '1', $location );
	}

	/**
	 * Show admin notice when limit reached.
	 */
	public function admin_notices() {
		if ( empty( $_GET[ $this->limit_query_arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Easy English WP (Lite) can only be enabled on 5 posts/pages in the free version. Please upgrade to unlock more.', 'easy-english-wp' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add Easy English column to lists.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_list_column( $columns ) {
		$columns['eewp_enabled'] = __( 'Easy English', 'easy-english-wp' );
		return $columns;
	}

	/**
	 * Render Easy English column.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_list_column( $column, $post_id ) {
		if ( 'eewp_enabled' !== $column ) {
			return;
		}

		$enabled = get_post_meta( $post_id, $this->loader->enabled_key, true );

		if ( 'yes' === $enabled ) {
			echo '<span class="dashicons dashicons-yes" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Easy English enabled', 'easy-english-wp' ) . '</span>';
		} else {
			echo '&#8212;'; // em dash placeholder.
		}
	}
}
