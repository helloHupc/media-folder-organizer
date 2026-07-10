<?php

defined( 'ABSPATH' ) || exit;

final class MFO_Plugin {
	/**
	 * @var MFO_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var MFO_Taxonomy
	 */
	private $taxonomy;

	/**
	 * @var MFO_REST_Controller
	 */
	private $rest_controller;

	/**
	 * @var MFO_Admin
	 */
	private $admin;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->taxonomy        = new MFO_Taxonomy();
		$this->rest_controller = new MFO_REST_Controller( $this->taxonomy );
		$this->admin           = new MFO_Admin( $this->taxonomy );

		add_action( 'init', array( $this->taxonomy, 'register' ) );
		add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );
		$this->admin->register_hooks();
	}

	public static function activate() {
		$taxonomy = new MFO_Taxonomy();
		$taxonomy->register();
		update_option( 'mfo_version', MFO_VERSION, false );
		flush_rewrite_rules( false );
	}
}
