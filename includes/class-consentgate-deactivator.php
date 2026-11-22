<?php
/**
 * Plugin deactivator.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Deactivator
 */
class ConsentGate_Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'consentgate_cleanup_logs' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'consentgate_cleanup_logs' );
		}
		foreach ( array( 'consentgate_weekly_scan', 'consentgate_weekly_scan_continue' ) as $hook ) {
			$next = wp_next_scheduled( $hook );
			if ( $next ) {
				wp_unschedule_event( $next, $hook );
			}
		}
	}
}
