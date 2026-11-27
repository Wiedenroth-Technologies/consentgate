<?php
/**
 * Build storefront block rules from catalog + detected services.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Blocker
 */
class ConsentGate_Blocker {

	/**
	 * Block rules for the frontend blocker.
	 *
	 * @return array<int, array{id: string, cat: string, h: array<int, string>, c: array<int, string>}>
	 */
	public static function rules(): array {
		$policies = ConsentGate_Options::purpose_policies();
		$by_key   = array();
		foreach ( ConsentGate_Services::all() as $row ) {
			$by_key[ ConsentGate_Catalog::canonical_key( (string) $row['service_key'] ) ] = $row;
		}

		$rules = array();

		foreach ( ConsentGate_Catalog::entries() as $entry ) {
			if ( ! empty( $entry['locked'] ) || 'necessary' === $entry['category'] ) {
				continue;
			}
			if ( empty( $entry['hosts'] ) && empty( $entry['cookies'] ) ) {
				continue;
			}

			$stored = $by_key[ $entry['key'] ] ?? null;
			if ( is_array( $stored ) ) {
				// Respect an explicit exclusion from the services list.
				if ( empty( $stored['included'] ) ) {
					continue;
				}
				$category = ConsentGate_Options::sanitize_category( (string) $stored['category'] );
				$policy   = ConsentGate_Options::sanitize_policy( (string) $stored['policy'] );
			} else {
				// Known catalog services stay blocked even if the last scan missed them.
				$category = ConsentGate_Options::sanitize_category( (string) $entry['category'] );
				$policy   = ConsentGate_Options::sanitize_policy( (string) $entry['policy'] );
			}

			if ( 'necessary' === $category || 'without_consent' === $policy ) {
				continue;
			}
			if ( ( $policies[ $category ] ?? 'with_consent' ) === 'without_consent' ) {
				continue;
			}
			$rules[] = array(
				'id'  => $entry['key'],
				'cat' => $category,
				'h'   => array_values( $entry['hosts'] ),
				'c'   => array_values( $entry['cookies'] ),
			);
		}

		foreach ( $by_key as $key => $service ) {
			if ( self::has_rule( $rules, $key ) ) {
				continue;
			}
			if ( empty( $service['included'] ) ) {
				continue;
			}
			$category = ConsentGate_Options::sanitize_category( (string) $service['category'] );
			$policy   = ConsentGate_Options::sanitize_policy( (string) $service['policy'] );
			if ( 'necessary' === $category || 'without_consent' === $policy ) {
				continue;
			}
			if ( ( $policies[ $category ] ?? 'with_consent' ) === 'without_consent' ) {
				continue;
			}
			$hosts   = self::hosts_from_evidence( (string) $service['evidence'] );
			$cookies = self::cookies_for_unknown( $key, $hosts );
			if ( ! $hosts && ! $cookies ) {
				continue;
			}
			$rules[] = array(
				'id'  => $key,
				'cat' => $category,
				'h'   => $hosts,
				'c'   => $cookies,
			);
		}

		return $rules;
	}

	/**
	 * Whether a script URL should be gated, and with which category.
	 *
	 * @param string $src Script src.
	 * @return string Empty or category.
	 */
	public static function category_for_src( string $src ): string {
		if ( '' === $src ) {
			return '';
		}
		$src = strtolower( $src );
		foreach ( self::rules() as $rule ) {
			foreach ( $rule['h'] as $host ) {
				$host = strtolower( str_replace( '*', '', (string) $host ) );
				if ( '' !== $host && str_contains( $src, $host ) ) {
					return (string) $rule['cat'];
				}
			}
		}

		return '';
	}

	/**
	 * Consent category for a media/embed URL, even if the scan never saw this page.
	 *
	 * Maps/YouTube/Vimeo are always gated unless the matching service is set to
	 * “Sofort laden” or the whole category loads without consent.
	 *
	 * @param string $src URL.
	 * @return string Empty or category.
	 */
	public static function category_for_embed( string $src ): string {
		$src = html_entity_decode( $src, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$src = trim( $src );
		if ( '' === $src ) {
			return '';
		}

		$entry = ConsentGate_Catalog::entry_for_src( $src );
		if ( ! $entry ) {
			return self::category_for_src( $src );
		}

		return self::category_for_key( (string) $entry['key'] );
	}

	/**
	 * Category for a known embed, or empty when it may load immediately.
	 * Respects the category chosen for that service.
	 *
	 * @param string $key Catalog key.
	 */
	public static function category_for_key( string $key ): string {
		$entry = ConsentGate_Catalog::by_key( $key );
		if ( ! $entry ) {
			return '';
		}

		$category = ConsentGate_Options::sanitize_category( (string) $entry['category'] );
		$policy   = ConsentGate_Options::sanitize_policy( (string) $entry['policy'] );

		foreach ( ConsentGate_Services::included() as $row ) {
			if ( ConsentGate_Catalog::canonical_key( (string) $row['service_key'] ) !== $key ) {
				continue;
			}
			$category = ConsentGate_Options::sanitize_category( (string) $row['category'] );
			$policy   = ConsentGate_Options::sanitize_policy( (string) $row['policy'] );
			break;
		}

		if ( 'necessary' === $category || 'without_consent' === $policy ) {
			return '';
		}

		$policies = ConsentGate_Options::purpose_policies();
		if ( ( $policies[ $category ] ?? 'with_consent' ) === 'without_consent' ) {
			return '';
		}

		return $category;
	}

	/**
	 * Embed widgets that stay blocked until their chosen category is allowed.
	 *
	 * @return array<string, string>
	 */
	public static function embed_gates(): array {
		$gates = array();
		foreach ( ConsentGate_Widgets::embed_keys() as $key ) {
			$gates[ $key ] = self::category_for_key( $key );
		}

		return $gates;
	}

	/**
	 * Rule already exists.
	 *
	 * @param array<int, array<string, mixed>> $rules Rules.
	 * @param string                           $key   Key.
	 */
	private static function has_rule( array $rules, string $key ): bool {
		foreach ( $rules as $rule ) {
			if ( $rule['id'] === $key ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Hosts from evidence string.
	 *
	 * @param string $evidence Evidence.
	 * @return array<int, string>
	 */
	public static function hosts_from_evidence( string $evidence ): array {
		$hosts = array();
		if ( ! preg_match_all( '#https?://[^\s"\'<>]+#i', $evidence, $matches ) ) {
			$host = wp_parse_url( 'https://' . ltrim( $evidence, '/' ), PHP_URL_HOST );
			if ( is_string( $host ) && '' !== $host ) {
				$hosts[] = strtolower( preg_replace( '/^www\./', '', $host ) ?? $host );
			}
			return array_values( array_unique( $hosts ) );
		}

		foreach ( $matches[0] as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( ! is_string( $host ) || '' === $host ) {
				continue;
			}
			$hosts[] = strtolower( preg_replace( '/^www\./', '', $host ) ?? $host );
		}

		return array_values( array_unique( $hosts ) );
	}

	/**
	 * Cookie prefixes for unknown services.
	 *
	 * @param string             $key   Key.
	 * @param array<int, string> $hosts Hosts.
	 * @return array<int, string>
	 */
	private static function cookies_for_unknown( string $key, array $hosts ): array {
		$cookies = array();
		$slug    = str_replace( array( 'unknown_', '_' ), array( '', '' ), $key );
		if ( strlen( $slug ) >= 4 ) {
			$cookies[] = substr( $slug, 0, 24 );
		}
		foreach ( $hosts as $host ) {
			$name = preg_replace( '/\.[a-z]{2,}$/i', '', $host );
			$name = preg_replace( '/[^a-z0-9]/i', '', (string) $name );
			if ( is_string( $name ) && strlen( $name ) >= 4 ) {
				$cookies[] = $name;
			}
		}

		return array_values( array_unique( $cookies ) );
	}
}
