<?php
/**
 * Lightweight proof for MapKit inline-script gating (no WordPress bootstrap).
 *
 * Run: php tests/php/prove-mapkit-inline.php
 *
 * @package ConsentGate
 */

declare(strict_types=1);

/**
 * Mirror of ConsentGate_Frontend::gate_mapkit_inline_scripts() transform.
 *
 * @param string $html     HTML.
 * @param string $category Consent category.
 */
function cg_prove_gate_mapkit_inline( string $html, string $category = 'marketing' ): string {
	if ( '' === $category ) {
		return $html;
	}
	if ( ! str_contains( strtolower( $html ), 'mapkit' ) ) {
		return $html;
	}

	$updated = preg_replace_callback(
		'/<script\b([^>]*)>(.*?)<\/script>/is',
		static function ( array $matches ) use ( $category ): string {
			$attrs = $matches[1];
			$body  = $matches[2];

			if ( preg_match( '/\bsrc\s*=/i', $attrs ) ) {
				return $matches[0];
			}
			if ( preg_match( '/\bid\s*=\s*([\'"])consentgate/i', $attrs ) ) {
				return $matches[0];
			}
			if ( preg_match( '/\bdata-cg-blocked\s*=/i', $attrs ) || preg_match( '/\bdata-consent-gate\s*=/i', $attrs ) ) {
				return $matches[0];
			}
			if ( preg_match( '/\btype\s*=\s*([\'"])[^\'"]*json/i', $attrs ) ) {
				return $matches[0];
			}
			if ( preg_match( '/window\.__CONSENTGATE__|__CONSENTGATE_BLOCKER__/i', $body ) ) {
				return $matches[0];
			}
			if ( ! preg_match( '/\bmapkit\s*\.\s*(?:init|Map)\b/i', $body ) ) {
				return $matches[0];
			}

			$attrs = preg_replace( '/\stype\s*=\s*([\'"])[^\'"]*\1/i', '', $attrs );
			if ( ! is_string( $attrs ) ) {
				$attrs = $matches[1];
			}

			return '<script type="text/plain" data-consent-gate="' . htmlspecialchars( $category, ENT_QUOTES ) . '" data-cg-blocked="' . htmlspecialchars( $category, ENT_QUOTES ) . '" data-cg-kind="apple_maps"' . $attrs . '>' . $body . '</script>';
		},
		$html
	);

	return is_string( $updated ) ? $updated : $html;
}

function cg_assert( bool $cond, string $msg ): void {
	if ( ! $cond ) {
		fwrite( STDERR, "FAIL: {$msg}\n" );
		exit( 1 );
	}
}

$input = <<<'HTML'
<html><body>
<script id="consentgate-boot">ok()</script>
<script id="consentgate-config">window.__CONSENTGATE__={"config":{"widgetRegistry":[{"inline":"\\bmapkit\\s*\\.\\s*(?:init|Map)\\b","mapTarget":"mapkit.Map"}]}};</script>
<script id="consentgate-blocker">function x(){ if (/apple-mapkit.com/.test(u)) return "apple_maps"; if (window.Calendly && Calendly.initInlineWidgets) Calendly.initInlineWidgets(); }</script>
<script src="https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js"></script>
<script type="application/json">{"mapkit":true}</script>
<div id="map" style="height:400px"></div>
<script>
mapkit.init({ authorizationCallback: function(done){ done("t"); } });
var map = new mapkit.Map("map");
</script>
<script>console.log("unrelated")</script>
</body></html>
HTML;

$out = cg_prove_gate_mapkit_inline( $input, 'marketing' );

cg_assert( str_contains( $out, 'id="consentgate-boot"' ), 'boot script must stay untouched' );
cg_assert( preg_match( '/id="consentgate-blocker"[^>]*>function x/i', $out ) === 1, 'blocker must stay untouched' );
cg_assert( preg_match( '/id="consentgate-config"[^>]*>window\.__CONSENTGATE__/i', $out ) === 1, 'config must stay untouched' );
cg_assert( str_contains( $out, 'src="https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js"' ), 'library src script must stay for src-gating' );
cg_assert( str_contains( $out, 'type="application/json"' ), 'json script must stay untouched' );
cg_assert( str_contains( $out, 'console.log("unrelated")' ), 'unrelated inline must stay untouched' );
cg_assert( preg_match( '/data-cg-kind="apple_maps"[^>]*>\s*mapkit\.init/i', $out ) === 1, 'mapkit inline must be deferred' );
cg_assert( preg_match( '/<script type="text\/plain" data-consent-gate="marketing" data-cg-blocked="marketing" data-cg-kind="apple_maps">/i', $out ) === 1, 'deferred attrs missing' );

$skipped = cg_prove_gate_mapkit_inline( $input, '' );
cg_assert( $skipped === $input, 'empty category must no-op' );

echo "PASS\n";
