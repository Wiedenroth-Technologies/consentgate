<?php
/**
 * Main plugin bootstrap.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate
 */
final class ConsentGate {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Hook loader.
	 *
	 * @var ConsentGate_Loader
	 */
	private ConsentGate_Loader $loader;

	/**
	 * Shared admin UI helper.
	 *
	 * @var ConsentGate_Admin_Ui
	 */
	private ConsentGate_Admin_Ui $ui;

	/**
	 * Get singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->loader = new ConsentGate_Loader();
		$this->ui     = new ConsentGate_Admin_Ui(
			CONSENTGATE_SLUG,
			CONSENTGATE_PLUGIN_DIR . 'assets/css/wt-admin.css',
			CONSENTGATE_PLUGIN_URL . 'assets/js/wt-admin.js',
			CONSENTGATE_VERSION
		);
		$this->define_admin_hooks();
		$this->define_front_hooks();
		$this->define_cron_hooks();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialize.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Register admin hooks.
	 */
	private function define_admin_hooks(): void {
		$this->ui->register_admin_hooks();

		$admin = new ConsentGate_Admin( $this->ui );
		$this->loader->add_action( 'admin_menu', $admin, 'add_menu' );
		$this->loader->add_action( 'admin_init', $admin, 'register_settings' );
		$this->loader->add_action( 'admin_init', $admin, 'maybe_redirect_setup' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->loader->add_action( 'in_admin_footer', $admin, 'render_wp_footer_brand' );
		$this->loader->add_action( 'wp_ajax_consentgate_scan', $admin, 'ajax_scan' );
		$this->loader->add_action( 'wp_ajax_consentgate_save_service', $admin, 'ajax_save_service' );
		$this->loader->add_action( 'wp_ajax_consentgate_add_service', $admin, 'ajax_add_service' );
		$this->loader->add_action( 'wp_ajax_consentgate_save_google_consent', $admin, 'ajax_save_google_consent' );
		$this->loader->add_action( 'wp_ajax_consentgate_complete_setup', $admin, 'ajax_complete_setup' );
		$this->loader->add_action( 'admin_post_consentgate_export_logs', $admin, 'handle_export_logs' );
		$this->loader->add_action( 'admin_post_consentgate_restart_setup', $admin, 'handle_restart_setup' );
		$this->loader->add_filter( 'plugin_action_links_' . CONSENTGATE_PLUGIN_BASENAME, $admin, 'action_links' );
	}

	/**
	 * Register frontend / REST / integrations.
	 */
	private function define_front_hooks(): void {
		$frontend = new ConsentGate_Frontend();
		$frontend->register( $this->loader );

		$nav = new ConsentGate_Nav();
		$nav->register( $this->loader );

		$rest = new ConsentGate_REST();
		$this->loader->add_action( 'rest_api_init', $rest, 'register_routes' );

		$wp_api = new ConsentGate_WP_Consent_API();
		$wp_api->register( $this->loader );

		$woo = new ConsentGate_WooCommerce();
		$woo->register( $this->loader );

		$privacy = new ConsentGate_Privacy();
		$privacy->register( $this->loader );
	}

	/**
	 * Cron cleanup.
	 */
	private function define_cron_hooks(): void {
		$this->loader->add_action( 'consentgate_cleanup_logs', 'ConsentGate_Logs', 'cleanup_expired' );
		$this->loader->add_action( 'consentgate_weekly_scan', 'ConsentGate_Scan', 'weekly_tick' );
		$this->loader->add_action( 'consentgate_weekly_scan_continue', 'ConsentGate_Scan', 'weekly_tick' );
		$this->loader->add_action( 'update_option_' . CONSENTGATE_OPTION_KEY, 'ConsentGate_Scan', 'sync_weekly_schedule', 10, 0 );
	}

	/**
	 * Run the loader.
	 */
	public function run(): void {
		$this->loader->run();

		add_action(
			'plugins_loaded',
			static function (): void {
				if ( get_option( 'consentgate_db_version' ) !== CONSENTGATE_DB_VERSION ) {
					ConsentGate_Activator::activate();
				}
				ConsentGate_Scan::sync_weekly_schedule();
				ConsentGate_Options::maybe_complete_legacy_setup();
			},
			5
		);
	}

	/**
	 * Expose loader.
	 */
	public function get_loader(): ConsentGate_Loader {
		return $this->loader;
	}

	/**
	 * Expose the UI helper.
	 */
	public function ui(): ConsentGate_Admin_Ui {
		return $this->ui;
	}
}
