<?php

defined( 'ABSPATH' ) || exit;

class EEWP_Elementor {

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

		add_action( 'elementor/init', array( $this, 'register_hooks' ) );
	}

	/**
	 * Register Elementor integration hooks.
	 */
	public function register_hooks() {
		if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
			return;
		}

		add_action( 'elementor/element/container/section_effects/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/element/container/section_advanced/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/element/section/section_effects/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/frontend/element/before_render', array( $this, 'add_retain_class' ) );
	}

	/**
	 * Add the Easy English WP control section to Elementor elements.
	 *
	 * @param \Elementor\Element_Base $element Element instance.
	 * @param array                   $args    Section arguments.
	 */
	public function register_controls( $element, $args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Avoid duplicate registration if another hook already added the control.
		if ( $element->get_controls( 'eewp_retain' ) ) {
			return;
		}

		$element->start_controls_section(
			'eewp_section',
			array(
				'label' => __( 'Easy English WP', 'easy-english-wp' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'eewp_retain',
			array(
				'label'        => __( 'Retain when Easy English is enabled', 'easy-english-wp' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Retain', 'easy-english-wp' ),
				'label_off'    => __( 'Hide', 'easy-english-wp' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Keep this container visible when visitors switch to Easy English mode.', 'easy-english-wp' ),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Add marker class for elements opted to stay visible in Easy mode.
	 *
	 * @param \Elementor\Element_Base $element Element instance.
	 */
	public function add_retain_class( $element ) {
		$retain = $element->get_settings_for_display( 'eewp_retain' );

		if ( 'yes' !== $retain ) {
			return;
		}

		$element->add_render_attribute( '_wrapper', 'class', 'eewp-keep' );
	}
}
