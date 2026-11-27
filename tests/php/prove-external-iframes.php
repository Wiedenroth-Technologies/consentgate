<?php
/**
 * Proof for unknown-iframe gating + allowlist (no WordPress bootstrap).
 *
 * Run: php tests/php/prove-external-iframes.php
 *
 * @package ConsentGate
 */

declare(strict_types=1);

/**
 * @param string $value Raw host.
 */
function cg_prove_sanitize_host( string $value ): string {
	$value = trim( strtolower( $value ) );
	if ( '' === $value ) {
		return '';
	}
	if ( str_contains( $value, '://' ) ) {
		$host  = parse_url( $value, PHP_URL_HOST );
		$value = is_string( $host ) ? $host : '';
	} else {
		$value = preg_replace( '#/.*$#', '', $value ) ?? $value;
	}

	$wildcard = str_starts_with( $value, '*.' );
	if ( $wildcard ) {
		$value = substr( $value, 2 );
	}

	$value = preg_replace( '/^www\./', '', $value ) ?? $value;
	$value = preg_replace( '/[^a-z0-9.-]/', '', $value ) ?? '';
	if ( '' === $value || ! str_contains( $value, '.' ) ) {
		return '';
	}

	return $wildcard ? '*.' . $value : $value;
}

/**
 * @param string $host  Host.
 * @param string $entry Allowlist entry.
 */
function cg_prove_host_matches( string $host, string $entry ): bool {
	$host  = strtolower( preg_replace( '/^www\./', '', $host ) ?? $host );
	$entry = cg_prove_sanitize_host( $entry );
	if ( '' === $host || '' === $entry ) {
		return false;
	}
	if ( str_starts_with( $entry, '*.' ) ) {
		$base = substr( $entry, 2 );
		return $host === $base || str_ends_with( $host, '.' . $base );
	}

	return $host === $entry || str_ends_with( $host, '.' . $entry );
}

/**
 * Mirror of ConsentGate_Frontend::iframe_gate_decision with injectable catalog.
 *
 * @param string               $src           URL.
 * @param string               $site_host     Site host.
 * @param bool                 $block_unknown Toggle.
 * @param array<int, string>   $allowlist     Allowlist.
 * @param array<string,string> $catalog_hosts Map of host substring => kind.
 * @return array{category: string, kind: string, host: string}|null
 */
function cg_prove_iframe_decision( string $src, string $site_host, bool $block_unknown, array $allowlist, array $catalog_hosts ): ?array {
	$src = trim( html_entity_decode( $src, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' === $src || str_starts_with( $src, 'about:' ) || str_starts_with( $src, 'javascript:' ) || str_starts_with( $src, '#' ) || str_starts_with( $src, 'data:' ) ) {
		return null;
	}
	$host = parse_url( $src, PHP_URL_HOST );
	if ( ! is_string( $host ) || '' === $host ) {
		return null;
	}
	$host      = strtolower( preg_replace( '/^www\./', '', $host ) ?? $host );
	$site_host = strtolower( preg_replace( '/^www\./', '', $site_host ) ?? $site_host );
	if ( '' !== $site_host && ( $host === $site_host || str_ends_with( $host, '.' . $site_host ) ) ) {
		return null;
	}

	foreach ( $catalog_hosts as $token => $kind ) {
		if ( str_contains( strtolower( $src ), strtolower( $token ) ) ) {
			return array(
				'category' => 'marketing',
				'kind'     => $kind,
				'host'     => $host,
			);
		}
	}

	if ( ! $block_unknown ) {
		return null;
	}
	foreach ( $allowlist as $allowed ) {
		if ( cg_prove_host_matches( $host, (string) $allowed ) ) {
			return null;
		}
	}

	return array(
		'category' => 'marketing',
		'kind'     => 'external_content',
		'host'     => $host,
	);
}

function cg_assert( bool $cond, string $msg ): void {
	if ( ! $cond ) {
		fwrite( STDERR, "FAIL: {$msg}\n" );
		exit( 1 );
	}
}

$catalog = array(
	'youtube.com'         => 'youtube',
	'maps.googleapis.com' => 'google_maps',
	'open.spotify.com'    => 'spotify',
	'instagram.com/p/'    => 'instagram',
	'platform.twitter.com'=> 'twitter',
);

cg_assert( null === cg_prove_iframe_decision( '/local-frame', 'example.com', true, array(), $catalog ), 'relative iframe stays open' );
cg_assert( null === cg_prove_iframe_decision( 'https://www.example.com/oembed', 'example.com', true, array(), $catalog ), 'same-origin stays open' );
cg_assert( null === cg_prove_iframe_decision( 'https://cdn.example.com/x', 'example.com', true, array(), $catalog ), 'subdomain of site stays open' );

$yt = cg_prove_iframe_decision( 'https://www.youtube.com/embed/abc', 'example.com', true, array(), $catalog );
cg_assert( is_array( $yt ) && 'youtube' === $yt['kind'], 'youtube maps to catalog' );

$ig = cg_prove_iframe_decision( 'https://www.instagram.com/p/demo/embed/captioned/', 'example.com', true, array(), $catalog );
cg_assert( is_array( $ig ) && 'instagram' === $ig['kind'], 'instagram post embed maps to catalog' );

$unknown = cg_prove_iframe_decision( 'https://weird-tracker.example/embed', 'example.com', true, array(), $catalog );
cg_assert( is_array( $unknown ) && 'external_content' === $unknown['kind'], 'unknown host blocked' );
cg_assert( 'weird-tracker.example' === $unknown['host'], 'host recorded' );

$allowed = cg_prove_iframe_decision( 'https://cdn.partner.com/player', 'example.com', true, array( 'cdn.partner.com' ), $catalog );
cg_assert( null === $allowed, 'allowlisted host stays open' );

$wild = cg_prove_iframe_decision( 'https://media.cdn-partner.test/player', 'example.com', true, array( '*.cdn-partner.test' ), $catalog );
cg_assert( null === $wild, 'wildcard allowlist matches subdomain' );

$wild_base = cg_prove_iframe_decision( 'https://cdn-partner.test/player', 'example.com', true, array( '*.cdn-partner.test' ), $catalog );
cg_assert( null === $wild_base, 'wildcard allowlist matches apex' );

$disabled = cg_prove_iframe_decision( 'https://weird-tracker.example/embed', 'example.com', false, array(), $catalog );
cg_assert( null === $disabled, 'toggle off disables catch-all' );

cg_assert( 'cdn.partner.com' === cg_prove_sanitize_host( 'https://www.cdn.partner.com/path' ), 'sanitize host from URL' );
cg_assert( '*.cdn-partner.test' === cg_prove_sanitize_host( '*.cdn-partner.test' ), 'sanitize keeps wildcard' );
cg_assert( '*.cdn-partner.test' === cg_prove_sanitize_host( '*.www.cdn-partner.test' ), 'sanitize wildcard strips www' );
cg_assert( '' === cg_prove_sanitize_host( 'localhost' ), 'reject host without dot' );

echo "PASS\n";
