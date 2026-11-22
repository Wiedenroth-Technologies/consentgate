<?php
/**
 * Geo helpers (country from trusted CDN headers only, no IP storage).
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Geo
 */
class ConsentGate_Geo {

	/**
	 * GDPR-relevant country codes (EU/EEA/UK/CH).
	 *
	 * @var array<int, string>
	 */
	private const GDPR = array(
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT',
		'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
		'IS', 'LI', 'NO', 'GB', 'CH',
	);

	/**
	 * Visitor country from trusted CDN headers, or empty.
	 */
	public static function visitor_country(): string {
		if ( ! ConsentGate_Options::get( 'trust_geo_headers', false ) ) {
			return '';
		}

		$keys = array(
			'HTTP_CF_IPCOUNTRY',
			'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
		);

		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}
			$code = strtoupper( substr( sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) ), 0, 2 ) );
			if ( preg_match( '/^[A-Z]{2}$/', $code ) && 'XX' !== $code && 'T1' !== $code ) {
				return $code;
			}
		}

		return '';
	}

	/**
	 * Whether the visitor is treated as in a GDPR region.
	 * Unknown country or untrusted headers → true (safer: show the banner).
	 */
	public static function visitor_in_eu(): bool {
		if ( ! ConsentGate_Options::get( 'trust_geo_headers', false ) ) {
			return true;
		}

		$country = self::visitor_country();
		if ( '' === $country ) {
			return true;
		}

		return in_array( $country, self::GDPR, true );
	}
}
