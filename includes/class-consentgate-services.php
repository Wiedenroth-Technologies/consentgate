<?php
/**
 * Detected services table.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Services
 */
class ConsentGate_Services {

	/**
	 * Table name.
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'consentgate_services';
	}

	/**
	 * All services.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY locked DESC, name ASC',
				self::table()
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Included services for the banner.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function for_banner(): array {
		$out    = array();
		$locale = ConsentGate_Options::frontend_locale();
		foreach ( self::all() as $row ) {
			if ( empty( $row['included'] ) ) {
				continue;
			}
			$key   = (string) $row['service_key'];
			$names = ConsentGate_Catalog::names( $key, (string) $row['name'] );
			$out[] = array(
				'id'    => $key,
				'name'  => $names[ $locale ] ?? $names['de'],
				'names' => $names,
				'cat'   => ConsentGate_Options::sanitize_category( (string) $row['category'] ),
				'file'  => ConsentGate_Catalog::logo_file( $key ),
			);
		}

		return $out;
	}

	/**
	 * Included rows for blocking.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function included(): array {
		return array_values(
			array_filter(
				self::all(),
				static function ( $row ) {
					return ! empty( $row['included'] );
				}
			)
		);
	}

	/**
	 * Upsert a scanned service.
	 *
	 * @param array<string, mixed> $data Service data.
	 */
	public static function upsert( array $data ): void {
		global $wpdb;

		$key  = ConsentGate_Catalog::canonical_key( (string) ( $data['service_key'] ?? '' ) );
		$name = sanitize_text_field( (string) ( $data['name'] ?? $key ) );
		if ( '' === $key ) {
			return;
		}

		$existing = self::get( $key );
		$now      = current_time( 'mysql' );
		$row      = array(
			'service_key'         => $key,
			'name'                => $name,
			'source'              => sanitize_text_field( (string) ( $data['source'] ?? 'scan' ) ),
			'evidence'            => sanitize_textarea_field( (string) ( $data['evidence'] ?? '' ) ),
			'suggested_category'  => ConsentGate_Options::sanitize_category( (string) ( $data['suggested_category'] ?? 'marketing' ) ),
			'suggested_policy'    => ConsentGate_Options::sanitize_policy( (string) ( $data['suggested_policy'] ?? 'with_consent' ) ),
			'updated_at'          => $now,
		);

		if ( $existing ) {
			if ( ! empty( $existing['locked'] ) ) {
				$row['category'] = 'necessary';
				$row['policy']   = 'without_consent';
				$row['included'] = 1;
				$row['locked']   = 1;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( self::table(), $row, array( 'service_key' => $key ) );
			return;
		}

		$row['category']   = $row['suggested_category'];
		$row['policy']     = $row['suggested_policy'];
		$row['included']   = isset( $data['included'] ) ? (int) (bool) $data['included'] : 1;
		$row['locked']     = empty( $data['locked'] ) ? 0 : 1;
		$row['status']     = sanitize_key( (string) ( $data['status'] ?? 'detected' ) );
		$row['notes']      = sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) );
		$row['created_at'] = $now;
		if ( $row['locked'] ) {
			$row['category'] = 'necessary';
			$row['policy']   = 'without_consent';
			$row['included'] = 1;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( self::table(), $row );
	}

	/**
	 * Get one service.
	 *
	 * @param string $key Service key.
	 * @return array<string, mixed>|null
	 */
	public static function get( string $key ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE service_key = %s',
				self::table(),
				$key
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Pages where a service was found during the last manual scan.
	 *
	 * @param array<string, mixed> $row Service row.
	 * @return array{count: int, pages: array<int, array{title: string, url: string}>}
	 */
	public static function locations( array $row ): array {
		$empty = array(
			'count' => 0,
			'pages' => array(),
		);
		$raw   = $row['locations'] ?? '';
		if ( ! is_string( $raw ) || '' === $raw ) {
			return $empty;
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return $empty;
		}

		$pages = array();
		foreach ( is_array( $decoded['pages'] ?? null ) ? $decoded['pages'] : array() as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}
			$title = sanitize_text_field( (string) ( $page['title'] ?? '' ) );
			$url   = esc_url_raw( (string) ( $page['url'] ?? '' ) );
			if ( '' === $url ) {
				continue;
			}
			$pages[] = array(
				'title' => '' !== $title ? $title : $url,
				'url'   => $url,
			);
			if ( count( $pages ) >= 3 ) {
				break;
			}
		}

		$count = isset( $decoded['count'] ) ? (int) $decoded['count'] : count( $pages );

		return array(
			'count' => max( $count, count( $pages ) ),
			'pages' => $pages,
		);
	}

	/**
	 * Store the pages a manual scan associated with a service.
	 *
	 * @param string              $key    Service key.
	 * @param array<string, mixed> $bundle Count plus up to three pages.
	 */
	public static function set_locations( string $key, array $bundle ): void {
		global $wpdb;

		$key = ConsentGate_Catalog::canonical_key( $key );
		if ( '' === $key || ! self::get( $key ) ) {
			return;
		}

		$pages = array();
		foreach ( is_array( $bundle['pages'] ?? null ) ? $bundle['pages'] : array() as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}
			$title = sanitize_text_field( (string) ( $page['title'] ?? '' ) );
			$url   = esc_url_raw( (string) ( $page['url'] ?? '' ) );
			if ( '' === $url ) {
				continue;
			}
			$pages[] = array(
				'title' => '' !== $title ? $title : $url,
				'url'   => $url,
			);
			if ( count( $pages ) >= 3 ) {
				break;
			}
		}

		$count = isset( $bundle['count'] ) ? (int) $bundle['count'] : count( $pages );
		$count = max( $count, count( $pages ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			self::table(),
			array(
				'locations'  => wp_json_encode(
					array(
						'count' => $count,
						'pages' => $pages,
					)
				),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'service_key' => $key )
		);
	}

	/**
	 * Update category/policy/included.
	 *
	 * @param string $key      Service key.
	 * @param string $category Category.
	 * @param string $policy   Policy.
	 * @param bool   $included Included.
	 * @param bool   $bump     Whether to bump the banner version on change.
	 */
	public static function save_assignment( string $key, string $category, string $policy, bool $included, bool $bump = true ): bool {
		global $wpdb;

		$existing = self::get( $key );
		if ( ! $existing ) {
			return false;
		}

		if ( ! empty( $existing['locked'] ) ) {
			$category = 'necessary';
			$policy   = 'without_consent';
			$included = true;
		}

		$category = ConsentGate_Options::sanitize_category( $category );
		$policy   = ConsentGate_Options::sanitize_policy( $policy );
		$changed  = (string) $existing['category'] !== $category
			|| (string) $existing['policy'] !== $policy
			|| (int) $existing['included'] !== ( $included ? 1 : 0 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$updated = $wpdb->update(
			self::table(),
			array(
				'category'   => $category,
				'policy'     => $policy,
				'included'   => $included ? 1 : 0,
				'status'     => 'configured',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'service_key' => $key )
		);

		if ( false !== $updated && $bump && $changed ) {
			ConsentGate_Options::bump_banner_version();
		}

		return false !== $updated;
	}

	/**
	 * Add a manual service.
	 *
	 * @param string $name     Name.
	 * @param string $host     Host or URL evidence.
	 * @param string $category Category.
	 */
	public static function add_manual( string $name, string $host, string $category ): bool {
		$name = sanitize_text_field( $name );
		$host = sanitize_text_field( $host );
		if ( '' === $name ) {
			return false;
		}

		$slug = sanitize_title( $name );
		$key  = 'manual_' . ( '' !== $slug ? $slug : wp_generate_password( 8, false, false ) );

		self::upsert(
			array(
				'service_key'        => $key,
				'name'               => $name,
				'source'             => 'manual',
				'evidence'           => $host,
				'suggested_category' => $category,
				'suggested_policy'   => 'necessary' === $category ? 'without_consent' : 'with_consent',
				'status'             => 'manual',
			)
		);

		ConsentGate_Options::bump_banner_version();

		return true;
	}

	/**
	 * Count included services.
	 */
	public static function count_included(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE included = 1',
				self::table()
			)
		);
	}

	/**
	 * Remove unlocked services (used before a fresh scan).
	 */
	public static function delete_unlocked(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE locked = 0',
				self::table()
			)
		);
	}
}
