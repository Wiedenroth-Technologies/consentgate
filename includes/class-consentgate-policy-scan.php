<?php
/**
 * Privacy policy mention check.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Policy_Scan
 */
class ConsentGate_Policy_Scan {

	/**
	 * Extra search aliases per catalog key.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const ALIASES = array(
		'google_ads'       => array( 'google ads', 'google adwords', 'google-werbung', 'doubleclick' ),
		'google_analytics' => array( 'google analytics', 'google-analytics', 'ga4' ),
		'gtm'              => array( 'google tag manager', 'tag manager', 'gtm-' ),
		'google_maps'      => array( 'google maps', 'google-maps', 'google maps platform', 'google karte', 'maps embed' ),
		'meta_pixel'       => array( 'meta pixel', 'facebook pixel', 'facebook', 'instagram' ),
		'tiktok'           => array( 'tiktok', 'tik tok' ),
		'pinterest'        => array( 'pinterest' ),
		'bing'             => array( 'microsoft ads', 'bing ads', 'bing uet' ),
		'clarity'          => array( 'microsoft clarity', 'clarity' ),
		'klaviyo'          => array( 'klaviyo' ),
		'snapchat'         => array( 'snapchat' ),
		'linkedin'         => array( 'linkedin' ),
		'adcell'           => array( 'adcell' ),
		'hotjar'           => array( 'hotjar' ),
		'matomo'           => array( 'matomo', 'piwik' ),
		'mailchimp'        => array( 'mailchimp' ),
		'youtube'          => array( 'youtube', 'youtu.be' ),
		'vimeo'            => array( 'vimeo' ),
		'recaptcha'        => array( 'recaptcha', 'google recaptcha' ),
		'stripe'           => array( 'stripe' ),
		'hubspot'          => array( 'hubspot' ),
		'google_fonts'     => array( 'google fonts', 'google schrift', 'fonts.googleapis' ),
	);

	/**
	 * Keys that do not need a policy mention.
	 *
	 * @var array<int, string>
	 */
	private const SKIP = array( 'wordpress_essential', 'woocommerce_essential' );

	/**
	 * Scan the WP privacy policy page.
	 *
	 * @return array<string, mixed>
	 */
	public static function run(): array {
		$url  = ConsentGate_Options::resolved_privacy_url();

		$services = array();
		foreach ( ConsentGate_Services::included() as $row ) {
			$key = (string) $row['service_key'];
			if ( in_array( $key, self::SKIP, true ) || str_starts_with( $key, 'unknown_' ) || str_starts_with( $key, 'manual_' ) ) {
				continue;
			}
			if ( 'necessary' === $row['category'] ) {
				continue;
			}
			$services[] = array(
				'key'      => $key,
				'name'     => (string) $row['name'],
				'category' => (string) $row['category'],
			);
		}

		if ( '' === $url ) {
			return array(
				'url'       => '',
				'found'     => false,
				'empty'     => true,
				'checked'   => array(),
				'missing'   => $services,
				'mentioned' => array(),
			);
		}

		$body = self::fetch_text( $url );
		$text = self::normalize( $body );
		$checked   = array();
		$missing   = array();
		$mentioned = array();

		foreach ( $services as $service ) {
			$hit = self::mentions( $text, $service['key'], $service['name'] );
			$row = $service + array( 'found' => $hit );
			$checked[] = $row;
			if ( $hit ) {
				$mentioned[] = $row;
			} else {
				$missing[] = $row;
			}
		}

		return array(
			'url'       => $url,
			'found'     => '' !== $text,
			'empty'     => '' === $text,
			'checked'   => $checked,
			'missing'   => $missing,
			'mentioned' => $mentioned,
		);
	}

	/**
	 * Snippet template for missing services.
	 *
	 * @param array<int, array<string, mixed>> $missing Missing services.
	 */
	public static function snippet( array $missing ): string {
		if ( ! $missing ) {
			return '';
		}

		$lines = array( __( 'This website also uses the following services (only with consent):', 'wiedenroth-technologies-consentgate' ) );
		foreach ( $missing as $row ) {
			$locale  = ConsentGate_Options::locale_from_tag( determine_locale() );
			$lines[] = '- ' . ConsentGate_Catalog::label( (string) ( $row['key'] ?? '' ), (string) $row['name'], '' !== $locale ? $locale : 'en' );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Fetch policy as text.
	 *
	 * @param string $url URL.
	 */
	private static function fetch_text( string $url ): string {
		$page_id = url_to_postid( $url );
		if ( $page_id > 0 ) {
			$post = get_post( $page_id );
			if ( $post instanceof WP_Post ) {
				return (string) $post->post_content;
			}
		}

		$res = wp_remote_get( $url, array( 'timeout' => 10, 'sslverify' => true ) );
		if ( is_wp_error( $res ) ) {
			return '';
		}

		$body = wp_remote_retrieve_body( $res );

		return is_string( $body ) ? $body : '';
	}

	/**
	 * Strip HTML to lowercase text.
	 *
	 * @param string $html HTML.
	 */
	private static function normalize( string $html ): string {
		$html = preg_replace( '#<script[\s\S]*?</script>#i', ' ', $html ) ?? $html;
		$html = preg_replace( '#<style[\s\S]*?</style>#i', ' ', $html ) ?? $html;
		$text = wp_strip_all_tags( $html );

		return strtolower( preg_replace( '/\s+/', ' ', $text ) ?? $text );
	}

	/**
	 * Whether the policy mentions a service.
	 *
	 * @param string $text Text.
	 * @param string $key  Key.
	 * @param string $name Name.
	 */
	private static function mentions( string $text, string $key, string $name ): bool {
		$needles = array(
			strtolower( str_replace( '_', ' ', $key ) ),
			strtolower( $name ),
			strtolower( ConsentGate_Catalog::label( $key, $name, 'en' ) ),
			strtolower( ConsentGate_Catalog::label( $key, $name, 'de' ) ),
		);
		foreach ( self::ALIASES[ $key ] ?? array() as $alias ) {
			$needles[] = strtolower( $alias );
		}
		foreach ( $needles as $needle ) {
			if ( strlen( $needle ) >= 4 && str_contains( $text, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
