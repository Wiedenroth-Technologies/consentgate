<?php
/**
 * Frontend: early head boot, blocker, banner assets, script gating.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Frontend
 */
class ConsentGate_Frontend {

	/**
	 * Register hooks.
	 *
	 * @param ConsentGate_Loader $loader Loader.
	 */
	public function register( ConsentGate_Loader $loader ): void {
		$loader->add_action( 'init', $this, 'maybe_mark_scan_request', 0 );
		$loader->add_action( 'wp_head', $this, 'print_boot', 0 );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue', 100 );
		$loader->add_action( 'template_redirect', $this, 'start_buffer', 0 );
		$loader->add_filter( 'script_loader_tag', $this, 'gate_script_tag', 20, 3 );
		$loader->add_filter( 'the_content', $this, 'gate_embeds', 20 );
		$loader->add_filter( 'widget_text', $this, 'gate_embeds', 20 );
		$loader->add_filter( 'widget_block_content', $this, 'gate_embeds', 20 );
		$loader->add_filter( 'render_block', $this, 'gate_embeds', 20 );
	}

	/**
	 * Detect authenticated scan requests early so cache plugins see DONOTCACHEPAGE.
	 */
	public function maybe_mark_scan_request(): void {
		self::is_scan_request();
	}

	/**
	 * Print Consent Mode + config + blocker before other head tags.
	 */
	public function print_boot(): void {
		if ( is_admin() || ! ConsentGate_Options::get( 'enabled', true ) || self::is_scan_request() ) {
			return;
		}

		$config = ConsentGate_Options::storefront_config();
		$boot   = array(
			'shop'         => wp_parse_url( home_url(), PHP_URL_HOST ),
			'shopLocale'   => determine_locale(),
			'logUrl'       => rest_url( 'consentgate/v1/consent' ),
			'assetBase'    => CONSENTGATE_PLUGIN_URL . 'assets/logos/',
			'config'       => $config,
			'visitorInEu'  => ConsentGate_Geo::visitor_in_eu(),
			'ipCountry'    => ConsentGate_Geo::visitor_country(),
			'restNonce'    => wp_create_nonce( 'wp_rest' ),
			'logos'        => $this->logo_map(),
		);

		echo '<script id="consentgate-boot">' . ConsentGate_Consent_Mode::boot_script() . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$json = wp_json_encode( $boot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		echo '<script id="consentgate-config">window.__CONSENTGATE__=' . ( $json ? $json : '{}' ) . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$blocker = CONSENTGATE_PLUGIN_DIR . 'assets/js/consent-gate-blocker.js';
		if ( is_readable( $blocker ) ) {
			$js = file_get_contents( $blocker );
			if ( is_string( $js ) && '' !== $js ) {
				echo '<script id="consentgate-blocker">' . $js . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Enqueue banner CSS/JS.
	 */
	public function enqueue(): void {
		if ( is_admin() || ! ConsentGate_Options::get( 'enabled', true ) || self::is_scan_request() ) {
			return;
		}

		wp_enqueue_style(
			'consentgate-banner',
			CONSENTGATE_PLUGIN_URL . 'assets/css/consent-gate.css',
			array(),
			self::asset_version( 'assets/css/consent-gate.css' )
		);

		wp_enqueue_script(
			'consentgate-banner',
			CONSENTGATE_PLUGIN_URL . 'assets/js/consent-gate.js',
			array(),
			self::asset_version( 'assets/js/consent-gate.js' ),
			true
		);
	}

	/**
	 * Catch iframes that page builders print outside the_content.
	 */
	public function start_buffer(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || self::is_scan_request() ) {
			return;
		}
		if ( ! ConsentGate_Options::get( 'enabled', true ) ) {
			return;
		}
		if ( is_feed() || is_robots() || is_trackback() || is_embed() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		ob_start( array( $this, 'gate_output' ) );
	}

	/**
	 * Rewrite matching script tags to type=text/plain until consent.
	 *
	 * @param mixed $tag    HTML tag.
	 * @param mixed $handle Handle.
	 * @param mixed $src    Src.
	 */
	public function gate_script_tag( $tag, $handle, $src ): string {
		$tag    = is_string( $tag ) ? $tag : '';
		$handle = is_string( $handle ) ? $handle : '';
		$src    = is_string( $src ) ? $src : '';
		if ( is_admin() || ! ConsentGate_Options::get( 'enabled', true ) || self::is_scan_request() ) {
			return $tag;
		}

		if ( str_starts_with( $handle, 'consentgate' ) ) {
			return $tag;
		}

		$essential = apply_filters( 'consentgate_essential_script_handles', ConsentGate_WooCommerce::default_handles() );
		if ( in_array( $handle, $essential, true ) ) {
			return $tag;
		}

		$category = ConsentGate_Blocker::category_for_src( $src );
		if ( '' === $category ) {
			return $tag;
		}

		$tag = preg_replace( '/\stype=(["\'])[^"\']*\1/', '', $tag ) ?? $tag;
		$tag = str_replace( '<script ', '<script type="text/plain" data-consent-gate="' . esc_attr( $category ) . '" ', $tag );

		return $tag;
	}

	/**
	 * Gate embeds, widget scripts and stylesheets in the rendered page.
	 *
	 * @param mixed $html Full HTML.
	 */
	public function gate_output( $html ): string {
		if ( ! is_string( $html ) ) {
			return '';
		}
		if ( is_admin() || ! ConsentGate_Options::get( 'enabled', true ) || self::is_scan_request() ) {
			return $html;
		}

		$html = $this->gate_embeds( $html );

		return $this->gate_raw_assets( $html );
	}

	/**
	 * Stop widget scripts and styles until the chosen category is allowed.
	 *
	 * @param string $html HTML.
	 */
	private function gate_raw_assets( string $html ): string {
		if ( '' === $html || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag() ) {
			$tag = strtoupper( (string) $processor->get_tag() );
			if ( 'SCRIPT' !== $tag && 'LINK' !== $tag ) {
				continue;
			}
			$id  = (string) $processor->get_attribute( 'id' );
			if ( str_starts_with( $id, 'consentgate' ) ) {
				continue;
			}

			if ( 'SCRIPT' === $tag ) {
				$type = strtolower( (string) $processor->get_attribute( 'type' ) );
				if ( str_contains( $type, 'json' ) ) {
					continue;
				}
				$src = $processor->get_attribute( 'src' );
				if ( ! is_string( $src ) || '' === $src || self::is_plugin_asset( $src ) ) {
					continue;
				}
				$category = $this->asset_category( $src );
				if ( '' === $category ) {
					continue;
				}
				$processor->set_attribute( 'type', 'text/plain' );
				$processor->set_attribute( 'data-consent-gate', $category );
				$processor->set_attribute( 'data-cg-blocked', $category );
				$processor->set_attribute( 'data-cg-src', $src );
				$processor->remove_attribute( 'src' );
				continue;
			}

			$rel = strtolower( (string) $processor->get_attribute( 'rel' ) );
			if ( ! str_contains( $rel, 'stylesheet' ) && ! str_contains( $rel, 'preload' ) ) {
				continue;
			}
			$href = $processor->get_attribute( 'href' );
			if ( ! is_string( $href ) || '' === $href || self::is_plugin_asset( $href ) ) {
				continue;
			}
			$category = $this->asset_category( $href );
			if ( '' === $category ) {
				continue;
			}
			$processor->set_attribute( 'data-cg-blocked', $category );
			$processor->set_attribute( 'data-cg-href', $href );
			$processor->remove_attribute( 'href' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Category for a raw script or stylesheet URL.
	 *
	 * @param string $src URL.
	 */
	private function asset_category( string $src ): string {
		$entry = ConsentGate_Catalog::entry_for_src( $src );
		$embed = array( 'google_maps', 'apple_maps', 'linexo', 'youtube', 'vimeo' );
		if ( is_array( $entry ) && in_array( (string) $entry['key'], $embed, true ) ) {
			return ConsentGate_Blocker::category_for_embed( $src );
		}

		return ConsentGate_Blocker::category_for_src( $src );
	}

	/**
	 * Strip tracker iframe src so the browser never loads Maps/YouTube/Vimeo before consent.
	 *
	 * @param mixed $html HTML.
	 */
	public function gate_embeds( $html ): string {
		if ( ! is_string( $html ) ) {
			return '';
		}
		if ( is_admin() || ! ConsentGate_Options::get( 'enabled', true ) || self::is_scan_request() ) {
			return $html;
		}

		if ( '' === $html || ! class_exists( 'WP_HTML_Tag_Processor' ) || ( ! str_contains( $html, '<iframe' ) && ! str_contains( $html, '<IFRAME' ) ) ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag( 'IFRAME' ) ) {
			if ( $processor->get_attribute( 'data-cg-blocked' ) ) {
				continue;
			}

			$src = $processor->get_attribute( 'src' );
			if ( ! is_string( $src ) || '' === $src ) {
				$src = $processor->get_attribute( 'data-src' );
			}
			if ( ! is_string( $src ) || '' === $src ) {
				$src = $processor->get_attribute( 'data-lazy-src' );
			}
			if ( ! is_string( $src ) || '' === $src ) {
				continue;
			}

			$category = ConsentGate_Blocker::category_for_embed( $src );
			if ( '' === $category ) {
				continue;
			}

			$entry = ConsentGate_Catalog::entry_for_src( $src );
			$processor->set_attribute( 'data-cg-blocked', $category );
			$processor->set_attribute( 'data-cg-src', $src );
			if ( is_array( $entry ) ) {
				$processor->set_attribute( 'data-cg-kind', (string) $entry['key'] );
			}
			$processor->remove_attribute( 'src' );
			$processor->remove_attribute( 'data-src' );
			$processor->remove_attribute( 'data-lazy-src' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Logo URLs keyed by service id.
	 *
	 * @return array<string, string>
	 */
	private function logo_map(): array {
		$map = array(
			'_placeholder' => ConsentGate_Catalog::logo_url( '_placeholder' ),
		);
		foreach ( ConsentGate_Catalog::entries() as $entry ) {
			$map[ $entry['key'] ] = ConsentGate_Catalog::logo_url( (string) $entry['key'] );
		}

		return $map;
	}

	/**
	 * Plugin assets stay loaded. Only URLs under this plugin's own base URL count.
	 *
	 * @param string $url Asset URL.
	 */
	private static function is_plugin_asset( string $url ): bool {
		if ( '' === $url ) {
			return false;
		}

		$base = CONSENTGATE_PLUGIN_URL;
		if ( str_starts_with( $url, $base ) ) {
			return true;
		}

		$base_path = wp_parse_url( $base, PHP_URL_PATH );
		$url_path  = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $base_path ) || '' === $base_path || ! is_string( $url_path ) ) {
			return false;
		}

		return str_starts_with( $url_path, $base_path );
	}

	/**
	 * Short-lived HMAC proof that this request is the plugin's own scanner.
	 *
	 * Token format: `{unix_ts}.{hmac_sha256}` over `{ts}|{site_host}` with wp_salt( 'auth' ).
	 */
	public static function create_scan_token(): string {
		$ts      = (string) time();
		$host    = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$payload = $ts . '|' . strtolower( $host );

		return $ts . '.' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	}

	/**
	 * Whether a scan token is valid (HMAC + freshness).
	 *
	 * @param string $token Token from header or query.
	 */
	public static function verify_scan_token( string $token ): bool {
		if ( ! preg_match( '/^(\d+)\.([a-f0-9]{64})$/', $token, $m ) ) {
			return false;
		}

		$ts = (int) $m[1];
		if ( $ts < 1 || abs( time() - $ts ) > 10 * MINUTE_IN_SECONDS ) {
			return false;
		}

		$host     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$payload  = $m[1] . '|' . strtolower( $host );
		$expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		return hash_equals( $expected, $m[2] );
	}

	/**
	 * Prevent page caches from storing an unblocked scan response.
	 */
	private static function mark_scan_uncacheable(): void {
		if ( ! headers_sent() ) {
			nocache_headers();
		}
		/*
		 * Cache plugins (WP Super Cache, W3TC, LiteSpeed, etc.) look for these
		 * exact constant names. Prefixing them would break the bypass.
		 */
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
	}

	/**
	 * True only when the request carries a valid short-lived scan HMAC.
	 * A forged User-Agent alone is not enough.
	 */
	public static function is_scan_request(): bool {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}

		$token = '';
		if ( isset( $_SERVER['HTTP_X_CONSENTGATE_SCAN'] ) ) {
			$token = sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_X_CONSENTGATE_SCAN'] ) );
		} elseif ( isset( $_GET['consentgate_scan'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- HMAC scan proof, not a form nonce.
			$token = sanitize_text_field( wp_unslash( (string) $_GET['consentgate_scan'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- HMAC scan proof, not a form nonce.
		}

		if ( '' === $token || ! self::verify_scan_token( $token ) ) {
			$cached = false;
			return false;
		}

		self::mark_scan_uncacheable();
		$cached = true;

		return true;
	}

	/**
	 * Cache-bust changed assets without a plugin version bump.
	 *
	 * @param string $relative Path inside the plugin.
	 */
	private static function asset_version( string $relative ): string {
		$path  = CONSENTGATE_PLUGIN_DIR . ltrim( $relative, '/' );
		$mtime = is_readable( $path ) ? filemtime( $path ) : false;

		return false !== $mtime ? (string) $mtime : CONSENTGATE_VERSION;
	}
}
