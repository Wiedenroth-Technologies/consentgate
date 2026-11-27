<?php
/**
 * Data-driven embed/widget registry for placeholders and deferred init.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Widgets
 */
class ConsentGate_Widgets {

	/**
	 * Widget definitions keyed for the frontend blocker.
	 *
	 * Each entry:
	 * - id: catalog service key
	 * - library: substrings matched against script src
	 * - containers: CSS selectors for placeholder targets
	 * - inline: JS regex source that marks deferred inline bootstraps
	 * - mapTarget: optional JS regex source with a capture group for element ids
	 * - reinit: frontend hook key (instagram|twitter|facebook|…)
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function registry(): array {
		return array(
			array(
				'id'         => 'apple_maps',
				'library'    => array( 'apple-mapkit.com', 'cdn.apple-mapkit.com', 'mapkit.js' ),
				'containers' => array(),
				'inline'     => '\\bmapkit\\s*\\.\\s*(?:init|Map)\\b',
				'mapTarget'  => 'mapkit\\.Map\\s*\\(\\s*["\']([^"\']+)["\']',
				'reinit'     => '',
			),
			array(
				'id'         => 'linexo',
				'library'    => array( 'site-connect.services.linexo.com' ),
				'containers' => array( '#linexo-site-connect' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => '',
			),
			array(
				'id'         => 'instagram',
				'library'    => array( 'instagram.com/embed.js', 'platform.instagram.com' ),
				'containers' => array( 'blockquote.instagram-media' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => 'instagram',
			),
			array(
				'id'         => 'twitter',
				'library'    => array( 'platform.twitter.com/widgets.js', 'platform.x.com/widgets.js' ),
				'containers' => array( 'blockquote.twitter-tweet', 'blockquote.twitter-video' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => 'twitter',
			),
			array(
				'id'         => 'facebook',
				'library'    => array( 'connect.facebook.net', 'facebook.com/plugins' ),
				'containers' => array( '.fb-post', '.fb-page', '.fb-video', '.fb-comment-embed' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => 'facebook',
			),
			array(
				'id'         => 'tiktok',
				'library'    => array( 'tiktok.com/embed.js', 'www.tiktok.com/embed.js' ),
				'containers' => array( 'blockquote.tiktok-embed' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => 'tiktok',
			),
			array(
				'id'         => 'pinterest',
				'library'    => array( 'assets.pinterest.com/js/pinit.js', 'assets.pinterest.com/js/pinit_main.js' ),
				'containers' => array( 'a[data-pin-do]', '.pinterest-embed' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => 'pinterest',
			),
			array(
				'id'         => 'linkedin',
				'library'    => array( 'platform.linkedin.com', 'linkedin.com/embed' ),
				'containers' => array( '.linkedin-embed', 'iframe[data-cg-kind="linkedin"]' ),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => '',
			),
			array(
				'id'         => 'calendly',
				'library'    => array( 'assets.calendly.com/assets/external/widget.js', 'calendly.com/assets/external/widget.js' ),
				'containers' => array( '.calendly-inline-widget', '.calendly-badge-widget', '.calendly-inline-widget-container' ),
				'inline'     => '\\bCalendly\\.init(?:InlineWidget|PopupWidget)\\b',
				'mapTarget'  => '',
				'reinit'     => 'calendly',
			),
			array(
				'id'         => 'spotify',
				'library'    => array( 'open.spotify.com/embed', 'spotify.com/embed' ),
				'containers' => array(),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => '',
			),
			array(
				'id'         => 'soundcloud',
				'library'    => array( 'w.soundcloud.com/player', 'soundcloud.com/player' ),
				'containers' => array(),
				'inline'     => '',
				'mapTarget'  => '',
				'reinit'     => '',
			),
		);
	}

	/**
	 * Catalog keys that participate in embed gating (iframes + widgets).
	 *
	 * @return array<int, string>
	 */
	public static function embed_keys(): array {
		$keys = array( 'google_maps', 'youtube', 'vimeo', 'external_content' );
		foreach ( self::registry() as $widget ) {
			$keys[] = (string) $widget['id'];
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Whether a script src belongs to a widget library.
	 *
	 * @param string $src Script URL.
	 * @return array<string, mixed>|null
	 */
	public static function widget_for_library_src( string $src ): ?array {
		$src = strtolower( $src );
		if ( '' === $src ) {
			return null;
		}
		foreach ( self::registry() as $widget ) {
			foreach ( $widget['library'] as $token ) {
				$token = strtolower( (string) $token );
				if ( '' !== $token && str_contains( $src, $token ) ) {
					return $widget;
				}
			}
		}

		return null;
	}

	/**
	 * Whether inline script text matches a widget bootstrap pattern.
	 *
	 * @param string $text Inline JS.
	 * @return array<string, mixed>|null
	 */
	public static function widget_for_inline( string $text ): ?array {
		if ( '' === $text ) {
			return null;
		}
		if ( preg_match( '/window\.__CONSENTGATE__|__CONSENTGATE_BLOCKER__|__CONSENTGATE_RESTORED__/i', $text ) ) {
			return null;
		}
		foreach ( self::registry() as $widget ) {
			$pattern = (string) ( $widget['inline'] ?? '' );
			if ( '' === $pattern ) {
				continue;
			}
			if ( @preg_match( '/' . $pattern . '/i', $text ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return $widget;
			}
		}

		return null;
	}
}
