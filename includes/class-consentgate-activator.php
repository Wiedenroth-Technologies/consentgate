<?php
/**
 * Plugin activator.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Activator
 */
class ConsentGate_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
		self::seed_essentials();
		self::schedule_events();
		self::prepare_setup();

		update_option( 'consentgate_db_version', CONSENTGATE_DB_VERSION );
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset  = $wpdb->get_charset_collate();
		$services = $wpdb->prefix . 'consentgate_services';
		$logs     = $wpdb->prefix . 'consentgate_logs';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_services = "CREATE TABLE {$services} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_key varchar(191) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			source varchar(64) NOT NULL DEFAULT '',
			evidence text NULL,
			suggested_category varchar(32) NOT NULL DEFAULT 'marketing',
			suggested_policy varchar(32) NOT NULL DEFAULT 'with_consent',
			category varchar(32) NOT NULL DEFAULT 'marketing',
			policy varchar(32) NOT NULL DEFAULT 'with_consent',
			included tinyint(1) NOT NULL DEFAULT 1,
			locked tinyint(1) NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'pending',
			notes text NULL,
			locations longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY service_key (service_key),
			KEY category (category),
			KEY included (included)
		) {$charset};";

		$sql_logs = "CREATE TABLE {$logs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			decision varchar(32) NOT NULL DEFAULT '',
			categories longtext NOT NULL,
			banner_version varchar(32) NOT NULL DEFAULT '1',
			locale varchar(8) NOT NULL DEFAULT 'de',
			ip_country varchar(8) NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY created_at (created_at),
			KEY visitor_id (visitor_id),
			KEY decision (decision)
		) {$charset};";

		dbDelta( $sql_services );
		dbDelta( $sql_logs );
	}

	/**
	 * Seed locked essential services so the banner is never empty.
	 */
	private static function seed_essentials(): void {
		foreach ( ConsentGate_Catalog::entries() as $entry ) {
			if ( empty( $entry['always'] ) && 'woocommerce_essential' !== $entry['key'] ) {
				continue;
			}
			if ( 'woocommerce_essential' === $entry['key'] && ! ConsentGate_WooCommerce::is_active() ) {
				continue;
			}
			ConsentGate_Services::upsert(
				array(
					'service_key'        => $entry['key'],
					'name'               => $entry['name'],
					'source'             => 'core',
					'evidence'           => '',
					'suggested_category' => $entry['category'],
					'suggested_policy'   => $entry['policy'],
					'locked'             => ! empty( $entry['locked'] ),
					'included'           => true,
					'status'             => 'detected',
				)
			);
		}
	}

	/**
	 * Persist default settings if none exist.
	 */
	private static function set_defaults(): void {
		if ( false === get_option( CONSENTGATE_OPTION_KEY ) ) {
			add_option( CONSENTGATE_OPTION_KEY, ConsentGate_Options::defaults() );
		}
	}

	/**
	 * First-run setup: new sites start the wizard, scanned sites skip it.
	 */
	private static function prepare_setup(): void {
		if ( false === get_option( 'consentgate_setup_completed', false ) ) {
			$done = '' !== get_option( 'consentgate_last_scan_at', '' ) ? '1' : '0';
			add_option( 'consentgate_setup_completed', $done, '', false );
		}

		if ( '1' !== (string) get_option( 'consentgate_setup_completed', '0' ) ) {
			set_transient( 'consentgate_activation_redirect', '1', 30 );
		}
	}

	/**
	 * Schedule recurring cleanup.
	 */
	private static function schedule_events(): void {
		if ( ! wp_next_scheduled( 'consentgate_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'consentgate_cleanup_logs' );
		}
		ConsentGate_Scan::sync_weekly_schedule();
	}
}
