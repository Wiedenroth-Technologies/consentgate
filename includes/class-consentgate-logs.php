<?php
/**
 * Consent log table, analytics, CSV, retention.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Logs
 */
class ConsentGate_Logs {

	/**
	 * Table name.
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'consentgate_logs';
	}

	/**
	 * Insert a consent decision.
	 *
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function insert( array $payload ): bool {
		global $wpdb;

		$visitor = sanitize_text_field( (string) ( $payload['visitorId'] ?? $payload['visitor_id'] ?? '' ) );
		if ( '' === $visitor ) {
			return false;
		}

		$decision = sanitize_key( (string) ( $payload['decision'] ?? 'custom' ) );
		if ( ! in_array( $decision, array( 'accept_all', 'reject_all', 'custom' ), true ) ) {
			$decision = 'custom';
		}

		$categories = $payload['categories'] ?? array();
		if ( ! is_array( $categories ) ) {
			$categories = array();
		}
		$categories = array(
			'necessary'   => true,
			'statistics'  => ! empty( $categories['statistics'] ),
			'marketing'   => ! empty( $categories['marketing'] ),
			'preferences' => ! empty( $categories['preferences'] ),
		);

		$country = ConsentGate_Geo::visitor_country();
		if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
			$country = '';
		}

		$locale = $payload['locale'] ?? 'de';
		$locale = is_string( $locale ) && in_array( $locale, array( 'de', 'en' ), true ) ? $locale : 'de';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert(
			self::table(),
			array(
				'visitor_id'     => substr( $visitor, 0, 64 ),
				'decision'       => $decision,
				'categories'     => wp_json_encode( $categories ),
				'banner_version' => substr( sanitize_text_field( (string) ( $payload['bannerVersion'] ?? $payload['banner_version'] ?? '1' ) ), 0, 32 ),
				'locale'         => $locale,
				'ip_country'     => '' !== $country ? $country : null,
				'created_at'     => current_time( 'mysql' ),
			)
		);

		return false !== $ok;
	}

	/**
	 * Paginated logs.
	 *
	 * @param int    $page   Page.
	 * @param int    $per    Per page.
	 * @param string $search Optional visitor id / decision.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function page( int $page = 1, int $per = 50, string $search = '' ): array {
		global $wpdb;

		$page  = max( 1, $page );
		$per   = max( 1, min( 100, $per ) );
		$off   = ( $page - 1 ) * $per;

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE (visitor_id LIKE %s OR decision LIKE %s OR ip_country LIKE %s)',
					self::table(),
					$like,
					$like,
					$like
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE (visitor_id LIKE %s OR decision LIKE %s OR ip_country LIKE %s) ORDER BY created_at DESC LIMIT %d OFFSET %d',
					self::table(),
					$like,
					$like,
					$like,
					$per,
					$off
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i',
					self::table()
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d',
					self::table(),
					$per,
					$off
				),
				ARRAY_A
			);
		}

		return array(
			'rows'  => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}

	/**
	 * Analytics for the last N days.
	 *
	 * @param int $days Days.
	 * @return array<string, int>
	 */
	public static function stats( int $days = 30 ): array {
		global $wpdb;

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$base = array(
			'total'      => 0,
			'accept_all' => 0,
			'reject_all' => 0,
			'custom'     => 0,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT decision, COUNT(*) AS c FROM %i WHERE created_at >= %s GROUP BY decision',
				self::table(),
				$since
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return $base;
		}

		foreach ( $rows as $row ) {
			$key = (string) $row['decision'];
			$c   = (int) $row['c'];
			$base['total'] += $c;
			if ( isset( $base[ $key ] ) ) {
				$base[ $key ] = $c;
			}
		}

		return $base;
	}

	/**
	 * Export up to 20k rows as CSV.
	 */
	public static function export_csv(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d',
				self::table(),
				20000
			),
			ARRAY_A
		);
		$rows = is_array( $rows ) ? $rows : array();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consentgate-logs-' . gmdate( 'Ymd-His' ) . '.csv' );

		echo self::csv_line( array( 'id', 'created_at', 'visitor_id', 'decision', 'statistics', 'marketing', 'preferences', 'banner_version', 'locale', 'ip_country' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download.

		foreach ( $rows as $row ) {
			$cats = json_decode( (string) $row['categories'], true );
			$cats = is_array( $cats ) ? $cats : array();
			$line = self::csv_line(
				array(
					(string) $row['id'],
					(string) $row['created_at'],
					(string) $row['visitor_id'],
					(string) $row['decision'],
					empty( $cats['statistics'] ) ? '0' : '1',
					empty( $cats['marketing'] ) ? '0' : '1',
					empty( $cats['preferences'] ) ? '0' : '1',
					(string) $row['banner_version'],
					(string) $row['locale'],
					(string) $row['ip_country'],
				)
			);
			echo $line; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download. csv_line() quotes the cells.
		}

		exit;
	}

	/**
	 * One CSV line.
	 *
	 * @param array<int, mixed> $fields Fields.
	 */
	private static function csv_line( array $fields ): string {
		$out = array();
		foreach ( $fields as $field ) {
			$field = (string) $field;
			if ( false !== strpbrk( $field, "\",\n\r" ) ) {
				$out[] = '"' . str_replace( '"', '""', $field ) . '"';
			} else {
				$out[] = $field;
			}
		}

		return implode( ',', $out ) . "\r\n";
	}

	/**
	 * Delete logs older than retention.
	 */
	public static function cleanup_expired(): void {
		global $wpdb;

		$days = max( 30, min( 2555, (int) ConsentGate_Options::get( 'retention_days', 365 ) ) );
		$cut  = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE created_at < %s',
				self::table(),
				$cut
			)
		);
	}
}
