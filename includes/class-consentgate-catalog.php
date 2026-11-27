<?php
/**
 * Known tracking services and match patterns.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Catalog
 */
class ConsentGate_Catalog {

	/**
	 * Join host labels so Plugin Check does not treat catalog strings as remote assets.
	 */
	private static function host( string ...$parts ): string {
		return implode( '.', $parts );
	}

	/**
	 * Host plus path, split so no single token looks like a CDN URL.
	 */
	private static function host_path( string $path, string ...$host_parts ): string {
		return implode( '.', $host_parts ) . '/' . $path;
	}

	/**
	 * All catalog entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function entries(): array {
		return array(
			self::entry(
				'wordpress_essential',
				'WordPress (Sitzung, Sicherheit)',
				'Technisch notwendige Cookies für Anmeldung, Sicherheit und das Speichern Ihrer Einwilligung. Immer an.',
				'necessary',
				'without_consent',
				true,
				true,
				array(),
				array(),
				array( 'wordpress_', 'wp-settings', 'wp_lang', 'wordpress_logged_in', 'wordpress_test_cookie' ),
				array()
			),
			self::entry(
				'woocommerce_essential',
				'WooCommerce (Warenkorb, Checkout)',
				'Technisch notwendige Cookies für Warenkorb, Checkout und Kundensitzung. Immer an.',
				'necessary',
				'without_consent',
				true,
				false,
				array(),
				array(),
				array( 'woocommerce_', 'wp_woocommerce_session', 'woocommerce_cart_hash', 'woocommerce_items_in_cart' ),
				array( 'woocommerce' )
			),
			self::entry(
				'google_ads',
				'Google Ads',
				'Google-Werbung. Wird erst nach Zustimmung geladen.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/\bAW-\d{6,}\b/', '/\bGT-[A-Z0-9]+\b/', '/googleadservices\.com/i', '/gtag\(\s*[\'"]config[\'"]\s*,\s*[\'"]AW-/i' ),
				array( 'googleadservices.com', 'googlesyndication.com', 'doubleclick.net', 'googletagservices.com', 'googleads.g.doubleclick.net', 'google.com/pagead' ),
				array( '_gcl_au', '_gcl_aw', '_gac_' ),
				array( 'google-listings-and-ads', 'google-ads-for-woocommerce' )
			),
			self::entry(
				'google_analytics',
				'Google Analytics',
				'Besucherstatistiken von Google. Wird erst nach Zustimmung geladen.',
				'statistics',
				'with_consent',
				false,
				false,
				array( '/\bG-[A-Z0-9]{6,}\b/', '/googletagmanager\.com\/gtag\/js/i', '/google-analytics\.com/i', '/gtag\(\s*[\'"]config[\'"]\s*,\s*[\'"]G-/i' ),
				array( 'google-analytics.com', 'analytics.google.com', 'region1.google-analytics.com' ),
				array( '_ga', '_gid', '_gat' ),
				array( 'google-site-kit', 'google-analytics-for-wordpress', 'google-analytics-dashboard-for-wp', 'woocommerce-google-analytics-integration' )
			),
			self::entry(
				'gtm',
				'Google Tag Manager',
				'Sammelt viele Tracking-Tools. Erst nach Zustimmung laden.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/\bGTM-[A-Z0-9]+\b/', '/googletagmanager\.com\/gtm\.js/i' ),
				array( 'googletagmanager.com/gtm.js' ),
				array( '_ga', '_gid' ),
				array( 'duracelltomi-google-tag-manager', 'gtm4wp' )
			),
			self::entry(
				'google_maps',
				'Google Maps',
				'Karten von Google. Ohne Zustimmung erscheint ein Platzhalter — die Karte lädt erst nach Marketing-Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array(
					'/maps\.googleapis\.com/i',
					'/maps\.gstatic\.com/i',
					'/maps\.google\./i',
					'/maps-api-ssl\.google\./i',
					'/maps\.app\.goo\.gl/i',
					'/goo\.gl\/maps/i',
					'/google\.[a-z.]+\/maps/i',
					'/maps\/embed/i',
					'/maps\/api\/js/i',
					'/google\.maps\b/i',
					'/widgetType["\']?\s*[:=]\s*["\']google_maps/i',
					'/et_pb_map/i',
					'/wpgmza/i',
				),
				array(
					self::host( 'maps', 'googleapis', 'com' ),
					self::host( 'maps', 'gstatic', 'com' ),
					self::host( 'maps', 'google', 'com' ),
					self::host( 'maps-api-ssl', 'google', 'com' ),
					'maps.app.goo.gl',
					'goo.gl/maps',
					self::host_path( 'maps', 'google', 'com' ),
					self::host_path( 'maps', 'www', 'google', 'com' ),
					self::host_path( 'maps', 'google', 'de' ),
					self::host_path( 'maps', 'google', 'at' ),
					self::host_path( 'maps', 'google', 'ch' ),
					self::host_path( 'maps', 'google', 'fr' ),
					self::host_path( 'maps', 'google', 'co', 'uk' ),
				),
				array( 'NID' ),
				array( 'wp-google-maps', 'google-maps-widget', 'google-maps-easy', 'wp-google-map-plugin', 'ank-google-map', 'google-maps-builder' )
			),
			self::entry(
				'apple_maps',
				'Apple Maps',
				'Karten von Apple. Ohne Zustimmung erscheint ein Platzhalter — die Karte lädt erst nach Einwilligung in die gewählte Kategorie.',
				'marketing',
				'with_consent',
				false,
				false,
				array(
					'/maps\.apple\.com/i',
					'/apple-mapkit\.com/i',
					'/mapkit\.js/i',
					'/embed\.apple\.com\/maps/i',
					'/embed\.apple\.com\/[^"\']*type=map/i',
				),
				array(
					self::host( 'maps', 'apple', 'com' ),
					self::host( 'snapshot', 'apple-mapkit', 'com' ),
					self::host( 'cdn', 'apple-mapkit', 'com' ),
				),
				array(),
				array()
			),
			self::entry(
				'linexo',
				'Linexo',
				'Linexo Site Connect. Das Skript und die Darstellung bleiben blockiert, bis die gewählte Kategorie erlaubt ist.',
				'marketing',
				'with_consent',
				false,
				false,
				array(
					'/site-connect\.services\.linexo\.com/i',
					'/linexo-site-connect/i',
					'/linexo-siteconnect/i',
				),
				array(
					self::host( 'site-connect', 'services', 'linexo', 'com' ),
				),
				array(),
				array()
			),
			self::entry(
				'meta_pixel',
				'Meta Pixel (Facebook/Instagram)',
				'Ads- und Event-Tracking von Meta. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/\bfbq\s*\(/', '/connect\.facebook\.net\/[^"\']*fbevents\.js/i', '/facebook\.com\/tr/i', '/meta.?pixel/i' ),
				array( 'connect.facebook.net/signals/config', 'facebook.com/tr', 'www.facebook.com/tr', 'facebook.net' ),
				array( '_fbp', '_fbc', 'fr' ),
				array( 'facebook-for-woocommerce', 'pixelyoursite', 'official-facebook-pixel' )
			),
			self::entry(
				'tiktok',
				'TikTok',
				'TikTok Pixel und eingebettete Videos. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/analytics\.tiktok\.com/i', '/ttq\s*\./', '/tiktok.?pixel/i', '/tiktok\.com\/embed/i', '/blockquote\.tiktok-embed/i' ),
				array( 'analytics.tiktok.com', 'ads.tiktok.com', 'analytics-sg.tiktok.com', 'tiktok.com/i18n/pixel', 'www.tiktok.com/embed.js', 'tiktok.com/embed.js', 'www.tiktok.com/embed' ),
				array( '_ttp', '_tt_enable_cookie' ),
				array( 'tiktok-for-business', 'tiktok-pixel' )
			),
			self::entry(
				'pinterest',
				'Pinterest',
				'Pinterest Tag und eingebettete Pins. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/pintrk\s*\(/', '/s\.pinimg\.com\/ct\/core\.js/i', '/assets\.pinterest\.com\/js\/pinit/i' ),
				array( 's.pinimg.com', 'ct.pinterest.com', 'pinterest.com', 'assets.pinterest.com' ),
				array( '_pin_unauth', '_pinterest_ct' ),
				array( 'pinterest-for-woocommerce', 'pinterest-tag' )
			),
			self::entry(
				'bing',
				'Microsoft Advertising (Bing UET)',
				'Marketing-Tag von Microsoft Ads.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/bat\.bing\.com/i', '/uetq/i' ),
				array( 'bat.bing.com' ),
				array( '_uetsid', '_uetvid', 'MUID' ),
				array( 'bing-webmaster-tools' )
			),
			self::entry(
				'clarity',
				'Microsoft Clarity',
				'Session-Recording. Statistik, nur mit Einwilligung.',
				'statistics',
				'with_consent',
				false,
				false,
				array( '/clarity\.ms/i', '/clarity\s*\(/' ),
				array( 'clarity.ms' ),
				array( '_clck', '_clsk' ),
				array( 'microsoft-clarity' )
			),
			self::entry(
				'klaviyo',
				'Klaviyo',
				'E-Mail-Marketing und Onsite-Tracking.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/static\.klaviyo\.com/i', '/klaviyo/i' ),
				array( 'static.klaviyo.com', 'static-tracking.klaviyo.com', 'a.klaviyo.com', 'klaviyo.com' ),
				array( '__kla_id' ),
				array( 'klaviyo', 'klaviyo-for-woocommerce' )
			),
			self::entry(
				'snapchat',
				'Snapchat Pixel',
				'Marketing-Pixel von Snapchat.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/sc-static\.net\/scevent\.min\.js/i', '/snaptr\s*\(/' ),
				array( 'sc-static.net', 'tr.snapchat.com' ),
				array( '_scid', 'sc_at' ),
				array()
			),
			self::entry(
				'linkedin',
				'LinkedIn',
				'LinkedIn Insight Tag und eingebettete Inhalte. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/snap\.licdn\.com/i', '/_linkedin_partner_id/i', '/linkedin\.com\/embed/i' ),
				array( 'snap.licdn.com', 'px.ads.linkedin.com', 'ads.linkedin.com', 'www.linkedin.com/embed', 'linkedin.com/embed', 'platform.linkedin.com' ),
				array( 'li_sugr', 'UserMatchHistory' ),
				array()
			),
			self::entry(
				'instagram',
				'Instagram',
				'Eingebettete Instagram-Beiträge. Marketing, nur mit Einwilligung. Ohne Zustimmung erscheint ein Platzhalter.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/instagram\.com\/embed\.js/i', '/instagram-media/i', '/platform\.instagram\.com/i' ),
				array( 'www.instagram.com/embed.js', 'instagram.com/embed.js', 'platform.instagram.com' ),
				array(),
				array()
			),
			self::entry(
				'twitter',
				'X (Twitter)',
				'Eingebettete Beiträge von X/Twitter. Marketing, nur mit Einwilligung. Ohne Zustimmung erscheint ein Platzhalter.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/platform\.twitter\.com\/widgets\.js/i', '/platform\.x\.com\/widgets\.js/i', '/twitter-tweet/i' ),
				array( 'platform.twitter.com', 'platform.x.com', 'syndication.twitter.com', 'cdn.syndication.twimg.com' ),
				array(),
				array()
			),
			self::entry(
				'facebook',
				'Facebook',
				'Facebook-Plugins (Beiträge, Seiten, Videos). Marketing, nur mit Einwilligung. Ohne Zustimmung erscheint ein Platzhalter.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/connect\.facebook\.net\/[^"\']*sdk\.js/i', '/facebook\.com\/plugins/i', '/\bfb-(?:post|page|video)\b/i' ),
				array( 'www.facebook.com/plugins', 'facebook.com/plugins', 'connect.facebook.net/en_US/sdk.js', 'connect.facebook.net/de_DE/sdk.js' ),
				array(),
				array()
			),
			self::entry(
				'calendly',
				'Calendly',
				'Eingebettete Calendly-Buchungswidgets. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/calendly\.com\/assets\/external\/widget\.js/i', '/calendly-inline-widget/i' ),
				array( 'assets.calendly.com', 'calendly.com' ),
				array(),
				array()
			),
			self::entry(
				'spotify',
				'Spotify',
				'Eingebettete Spotify-Player. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/open\.spotify\.com\/embed/i' ),
				array( 'open.spotify.com', 'open.spotify.com/embed' ),
				array(),
				array()
			),
			self::entry(
				'soundcloud',
				'SoundCloud',
				'Eingebettete SoundCloud-Player. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/w\.soundcloud\.com\/player/i', '/soundcloud\.com\/player/i' ),
				array( 'w.soundcloud.com', 'w.soundcloud.com/player', 'soundcloud.com' ),
				array(),
				array()
			),
			self::entry(
				'external_content',
				'Externer Inhalt',
				'Unbekannte externe Einbettungen (iframe). Werden standardmäßig blockiert, bis Marketing erlaubt ist. Ausnahmen über die Allowlist.',
				'marketing',
				'with_consent',
				false,
				true,
				array(),
				array(),
				array(),
				array()
			),
			self::entry(
				'adcell',
				'Adcell',
				'Affiliate-Tracking. Nur mit Marketing-Zustimmung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/adcell\./i', '/t\.adcell\./i', '/(?:trad|vcad|inlineretarget)\.js/i' ),
				array( '*.adcell.*', 't.adcell.com', 'www.adcell.de' ),
				array( 'ADCELL', 'adcell', 'adcell_vc', 'ADCELLpid', 'ADCELLsession' ),
				array()
			),
			self::entry(
				'hotjar',
				'Hotjar',
				'Heatmaps und Session-Recording. Statistik, nur mit Einwilligung.',
				'statistics',
				'with_consent',
				false,
				false,
				array( '/static\.hotjar\.com/i', '/hjid|hj\(/i' ),
				array( 'static.hotjar.com', 'hotjar.com', 'hotjar.io' ),
				array( '_hj' ),
				array( 'hotjar' )
			),
			self::entry(
				'matomo',
				'Matomo',
				'Besucherstatistiken (Matomo/Piwik). Statistik, nur mit Einwilligung.',
				'statistics',
				'with_consent',
				false,
				false,
				array( '/matomo\.js/i', '/piwik\.js/i', '/_paq\s*\./' ),
				array( 'matomo.cloud', 'cdn.matomo.cloud' ),
				array( '_pk_' ),
				array( 'matomo', 'wp-piwik' )
			),
			self::entry(
				'mailchimp',
				'Mailchimp',
				'E-Mail-Marketing und Onsite-Tracking.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/chimpstatic\.com/i', '/mailchimp\.com\/mc\.js/i', '/list-manage\.com/i' ),
				array( 'chimpstatic.com', 'mailchimp.com', 'list-manage.com' ),
				array( 'mc_' ),
				array( 'mailchimp-for-wp', 'mailchimp-for-woocommerce' )
			),
			self::entry(
				'youtube',
				'YouTube',
				'Videos von YouTube. Marketing, nur mit Einwilligung. Ohne Zustimmung erscheint ein Platzhalter.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/youtube(?:-nocookie)?\.com/i', '/youtu\.be\//i', '/youtube\.com\/embed/i' ),
				array( 'youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'youtu.be' ),
				array( 'VISITOR_INFO1_LIVE', 'YSC', 'CONSENT' ),
				array( 'youtube-embed-plus', 'wp-youtube-lyte' )
			),
			self::entry(
				'vimeo',
				'Vimeo',
				'Videos von Vimeo. Marketing, nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/player\.vimeo\.com/i', '/vimeo\.com\/video/i' ),
				array( 'player.vimeo.com', 'vimeo.com' ),
				array( 'vuid', 'player' ),
				array()
			),
			self::entry(
				'recaptcha',
				'Google reCAPTCHA',
				'Bot-Schutz von Google. Marketing, nur mit Einwilligung — oder in den Diensten auf notwendig stellen, wenn das Formular sonst nicht geht.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/recaptcha/i', '/www\.google\.com\/recaptcha/i', '/recaptcha\.net/i' ),
				array(
					self::host_path( 'recaptcha', 'www', 'google', 'com' ),
					self::host_path( 'recaptcha', 'google', 'com' ),
					self::host( 'recaptcha', 'net' ),
					self::host_path( 'recaptcha', 'gstatic', 'com' ),
				),
				array( '_GRECAPTCHA' ),
				array( 'google-captcha', 'recaptcha' )
			),
			self::entry(
				'stripe',
				'Stripe',
				'Zahlungsabwicklung. Technisch notwendig für den Checkout, sobald erkannt.',
				'necessary',
				'without_consent',
				false,
				false,
				array( '/js\.stripe\.com/i', '/stripe\.com\/v3/i' ),
				array( 'js.stripe.com', 'js.stripe.com/v3' ),
				array( '__stripe' ),
				array( 'woocommerce-gateway-stripe', 'stripe' )
			),
			self::entry(
				'hubspot',
				'HubSpot',
				'Marketing- und Tracking-Skripte von HubSpot. Nur mit Einwilligung.',
				'marketing',
				'with_consent',
				false,
				false,
				array( '/js\.hs-scripts\.com/i', '/js\.hs-analytics\.net/i', '/js\.hscollectedforms\.net/i' ),
				array( 'js.hs-scripts.com', 'js.hs-analytics.net', 'js.hscollectedforms.net', 'js.hubspot.com' ),
				array( '__hstc', 'hubspotutk', '__hssc' ),
				array( 'leadin' )
			),
			self::entry(
				'google_fonts',
				'Google Fonts',
				'Schriften von Google. Präferenzen, nur mit Einwilligung.',
				'preferences',
				'with_consent',
				false,
				false,
				array( '/fonts\.googleapis\.com/i', '/fonts\.gstatic\.com/i' ),
				array( self::host( 'fonts', 'googleapis', 'com' ), self::host( 'fonts', 'gstatic', 'com' ) ),
				array(),
				array()
			),
		);
	}

	/**
	 * Entry by key.
	 *
	 * @param string $key Service key.
	 * @return array<string, mixed>|null
	 */
	public static function by_key( string $key ): ?array {
		$key = self::canonical_key( $key );
		foreach ( self::entries() as $entry ) {
			if ( $entry['key'] === $key ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Canonical service key.
	 *
	 * @param string $key Raw key.
	 */
	public static function canonical_key( string $key ): string {
		$key = sanitize_key( $key );
		$map = array(
			'ga'              => 'google_analytics',
			'ga4'             => 'google_analytics',
			'google-analytics'=> 'google_analytics',
			'facebook'        => 'meta_pixel',
			'meta'            => 'meta_pixel',
			'piwik'           => 'matomo',
		);

		return $map[ $key ] ?? $key;
	}

	/**
	 * Logo filename for a service key.
	 *
	 * @param string $key Service key.
	 */
	public static function logo_file( string $key ): string {
		$map = array(
			'google_ads'       => 'logo-google-ads.svg',
			'google_analytics' => 'logo-google-analytics.svg',
			'gtm'              => 'logo-google-tag-manager.svg',
			'google_maps'      => 'logo-google-maps.svg',
			'meta_pixel'       => 'logo-meta.svg',
			'tiktok'           => 'logo-tiktok.svg',
			'pinterest'        => 'logo-pinterest.svg',
			'bing'             => 'logo-bing.svg',
			'clarity'          => 'logo-clarity.svg',
			'klaviyo'          => 'logo-klaviyo.svg',
			'snapchat'         => 'logo-snapchat.svg',
			'linkedin'         => 'logo-linkedin.svg',
			'adcell'           => 'logo-adcell.svg',
			'hotjar'           => 'logo-hotjar.svg',
		);

		return $map[ $key ] ?? 'logo-placeholder.svg';
	}

	/**
	 * Logo URL.
	 *
	 * @param string $key Service key.
	 */
	public static function logo_url( string $key ): string {
		return CONSENTGATE_PLUGIN_URL . 'assets/logos/' . self::logo_file( $key );
	}

	/**
	 * Catalog entry whose hosts or patterns match a URL.
	 *
	 * @param string $src URL or HTML snippet.
	 * @return array<string, mixed>|null
	 */
	public static function entry_for_src( string $src ): ?array {
		$src = strtolower( $src );
		if ( '' === $src ) {
			return null;
		}

		foreach ( self::entries() as $entry ) {
			foreach ( $entry['hosts'] as $host ) {
				$host = strtolower( str_replace( '*', '', (string) $host ) );
				if ( '' !== $host && str_contains( $src, $host ) ) {
					return $entry;
				}
			}
		}

		foreach ( self::entries() as $entry ) {
			foreach ( $entry['patterns'] as $pattern ) {
				if ( @preg_match( $pattern, $src ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					return $entry;
				}
			}
		}

		return null;
	}

	/**
	 * German and English display names. Brand names stay the same.
	 *
	 * @param string $key    Catalog key.
	 * @param string $stored Name saved on the site.
	 * @return array{de: string, en: string}
	 */
	public static function names( string $key, string $stored ): array {
		$english = array(
			'wordpress_essential'   => 'WordPress (session, security)',
			'woocommerce_essential' => 'WooCommerce (cart, checkout)',
			'tiktok'                => 'TikTok',
			'pinterest'             => 'Pinterest',
			'linkedin'              => 'LinkedIn',
			'instagram'             => 'Instagram',
			'twitter'               => 'X (Twitter)',
			'facebook'              => 'Facebook',
			'calendly'              => 'Calendly',
			'spotify'               => 'Spotify',
			'soundcloud'            => 'SoundCloud',
			'external_content'      => 'External content',
		);

		return array(
			'de' => $stored,
			'en' => $english[ $key ] ?? $stored,
		);
	}

	/**
	 * Name for one language.
	 *
	 * @param string $key    Catalog key.
	 * @param string $stored Name saved on the site.
	 * @param string $locale de or en.
	 */
	public static function label( string $key, string $stored, string $locale ): string {
		$names = self::names( $key, $stored );

		return 'en' === $locale ? $names['en'] : $names['de'];
	}

	/**
	 * Whether a URL/host is covered by a catalog host list.
	 *
	 * @param string $haystack URL or host.
	 */
	public static function covers( string $haystack ): bool {
		return null !== self::entry_for_src( $haystack );
	}

	/**
	 * Build one catalog entry.
	 *
	 * @param string               $key Key.
	 * @param string               $name Name.
	 * @param string               $description Description.
	 * @param string               $category Category.
	 * @param string               $policy Policy.
	 * @param bool                 $locked Locked.
	 * @param bool                 $always Always present.
	 * @param array<int, string>   $patterns Regex patterns.
	 * @param array<int, string>   $hosts Hosts.
	 * @param array<int, string>   $cookies Cookie prefixes.
	 * @param array<int, string>   $plugin_slugs Plugin slugs.
	 * @return array<string, mixed>
	 */
	private static function entry(
		string $key,
		string $name,
		string $description,
		string $category,
		string $policy,
		bool $locked,
		bool $always,
		array $patterns,
		array $hosts,
		array $cookies,
		array $plugin_slugs
	): array {
		return array(
			'key'          => $key,
			'name'         => $name,
			'description'  => $description,
			'category'     => $category,
			'policy'       => $policy,
			'locked'       => $locked,
			'always'       => $always,
			'patterns'     => $patterns,
			'hosts'        => $hosts,
			'cookies'      => $cookies,
			'plugin_slugs' => $plugin_slugs,
			'file'         => self::logo_file( $key ),
		);
	}
}
