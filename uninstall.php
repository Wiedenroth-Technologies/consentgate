<?php
/**
 * Uninstall routine – removes all ConsentGate data.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'consentgate_settings' );
delete_option( 'consentgate_last_scan_at' );
delete_option( 'consentgate_setup_completed' );
delete_option( 'consentgate_db_version' );
delete_site_option( 'consentgate_settings' );
delete_site_option( 'consentgate_last_scan_at' );
delete_site_option( 'consentgate_setup_completed' );
delete_site_option( 'consentgate_db_version' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}consentgate_services" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}consentgate_logs" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_consentgate\_%' OR option_name LIKE '\_transient\_timeout\_consentgate\_%'"
);

$consentgate_timestamp = wp_next_scheduled( 'consentgate_cleanup_logs' );
if ( $consentgate_timestamp ) {
	wp_unschedule_event( $consentgate_timestamp, 'consentgate_cleanup_logs' );
}
