<?php
/**
 * Main plugin class.
 *
 * @package Import_From_Pixelfed
 */

namespace Import_From_Pixelfed;

/**
 * Main plugin class.
 */
class Import_From_Pixelfed {
	/**
	 * Import handler instance.
	 *
	 * @var Import_Handler $import_handler
	 */
	private $import_handler;

	/**
	 * Options handler instance.
	 *
	 * @var Options_Handler $options_handler
	 */
	private $options_handler;

	/**
	 * This plugin's single instance.
	 *
	 * @var Import_From_Pixelfed $instance
	 */
	private static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Import_From_Pixelfed Single class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * (Private) constructor.
	 */
	private function __construct() {
		$this->options_handler = new Options_Handler();
		$this->options_handler->register();

		// Enable polling Pixelfed.
		$this->import_handler = new Import_Handler( $this->options_handler );
		$this->import_handler->register();
	}

	/**
	 * Registers hook callbacks and such.
	 *
	 * @return void
	 */
	public function register() {
		// Register 15-minute cron interval.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );

		// Ensure cron events are registered.
		add_action( 'init', array( $this, 'activate' ) );
		register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/import-from-pixelfed.php', array( $this, 'deactivate' ) );

		// Allow i18n.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Define a new WP Cron interval.
	 *
	 * @param array $schedules WP Cron schedules.
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules['every_15_minutes'] = array(
			'interval' => 900,
			'display'  => __( 'Once every 15 minutes', 'import-from-pixelfed' ),
		);

		return $schedules;
	}

	/**
	 * Schedules the Pixelfed API call.
	 */
	public function activate() {
		if ( false === wp_next_scheduled( 'import_from_pixelfed_get_statuses' ) ) {
			wp_schedule_event( time() + 900, 'every_15_minutes', 'import_from_pixelfed_get_statuses' );
		}

		if ( false === wp_next_scheduled( 'import_from_pixelfed_refresh_token' ) ) {
			wp_schedule_event( time() + 3600, 'daily', 'import_from_pixelfed_refresh_token' );
		}
	}

	/**
	 * Unschedules any cron jobs.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'import_from_pixelfed_get_statuses' );
		wp_clear_scheduled_hook( 'import_from_pixelfed_refresh_token' );
	}

	/**
	 * Enables localization.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'import-from-pixelfed', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
	}

	/**
	 * Returns `Options_Handler` instance.
	 *
	 * @return Options_Handler This plugin's `Options_Handler` instance.
	 */
	public function get_options_handler() {
		return $this->options_handler;
	}

	/**
	 * Returns `Import_Handler` instance.
	 *
	 * @return Import_Handler This plugin's `Import_Handler` instance.
	 */
	public function get_import_handler() {
		return $this->import_handler;
	}
}
