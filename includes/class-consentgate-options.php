<?php
/**
 * Central options helper – Settings API backed.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Options
 */
class ConsentGate_Options {

	/**
	 * Cached settings.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $cache = null;

	/**
	 * Default banner texts (DE).
	 *
	 * @return array<string, string>
	 */
	public static function default_texts_de(): array {
		return array(
			'title'                  => 'Wir respektieren Ihre Privatsphäre',
			'description'            => 'Wir verwenden Cookies und ähnliche Technologien, um die Website bereitzustellen (notwendig) sowie – nur mit Ihrer ausdrücklichen Einwilligung – für Statistik und Marketing (z. B. Google Ads, Google Analytics). Nicht notwendige Cookies werden erst nach Ihrer Zustimmung geladen. Sie können alle ablehnen. Ihre Auswahl können Sie jederzeit über das Cookie-Symbol ändern.',
			'acceptAll'              => 'Alle akzeptieren',
			'rejectAll'              => 'Alle ablehnen',
			'customize'              => 'Einstellungen',
			'save'                   => 'Auswahl speichern',
			'close'                  => 'Schließen',
			'privacyLink'            => 'Datenschutzerklärung',
			'necessaryTitle'         => 'Notwendig',
			'necessaryDescription'   => 'Erforderlich für Grundfunktionen, Sicherheit und das Speichern Ihrer Einwilligung. Diese Cookies können nicht deaktiviert werden.',
			'statisticsTitle'        => 'Statistik',
			'statisticsDescription'  => 'Hilft uns zu verstehen, wie die Website genutzt wird (z. B. Google Analytics). Wird erst nach Einwilligung geladen.',
			'marketingTitle'         => 'Marketing',
			'marketingDescription'   => 'Wird für Werbung und Conversion-Messung verwendet (z. B. Google Ads, Meta Pixel). Wird erst nach Einwilligung geladen.',
			'preferencesTitle'       => 'Präferenzen',
			'preferencesDescription' => 'Speichert Ihre Einstellungen, zum Beispiel Sprache oder Region. Wird erst nach Einwilligung geladen.',
			'reopenLabel'            => 'Cookie-Einstellungen',
		);
	}

	/**
	 * Default banner texts (EN).
	 *
	 * @return array<string, string>
	 */
	public static function default_texts_en(): array {
		return array(
			'title'                  => 'We respect your privacy',
			'description'            => 'We use cookies and similar technologies to run this website (necessary) and — only with your explicit consent — for statistics and marketing (e.g. Google Ads, Google Analytics). Non-essential cookies load only after you opt in. You can reject all of them. You can change your choice anytime via the cookie icon.',
			'acceptAll'              => 'Accept all',
			'rejectAll'              => 'Reject all',
			'customize'              => 'Customize',
			'save'                   => 'Save selection',
			'close'                  => 'Close',
			'privacyLink'            => 'Privacy policy',
			'necessaryTitle'         => 'Necessary',
			'necessaryDescription'   => 'Required for basic website functions, security, and storing your consent. These cookies cannot be disabled.',
			'statisticsTitle'        => 'Statistics',
			'statisticsDescription'  => 'Helps us understand how the website is used (e.g. Google Analytics). Loaded only after consent.',
			'marketingTitle'         => 'Marketing',
			'marketingDescription'   => 'Used for advertising and conversion measurement (e.g. Google Ads, Meta Pixel). Loaded only after consent.',
			'preferencesTitle'       => 'Preferences',
			'preferencesDescription' => 'Stores your settings such as language or region. Loaded only after consent.',
			'reopenLabel'            => 'Cookie settings',
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$privacy = self::privacy_policy_url();

		return array(
			'enabled'                     => true,
			'retention_days'              => 365,
			'region_mode'                 => 'always',
			'url_passthrough'             => true,
			'google_advanced_consent_mode'=> false,
			'analytics_follows_marketing' => false,
			'purpose_statistics'          => 'with_consent',
			'purpose_marketing'           => 'with_consent',
			'purpose_preferences'         => 'with_consent',
			'banner_version'              => 1,
			'position'                    => 'bottom',
			'banner_size'                 => 'normal',
			'primary_color'               => '#27335a',
			'background_color'            => '#ffffff',
			'text_color'                  => '#1a1a1a',
			'accept_bg'                   => '#27335a',
			'accept_text'                 => '#ffffff',
			'reject_bg'                   => '#ffffff',
			'reject_text'                 => '#27335a',
			'logo_url'                    => '',
			'logo_id'                     => 0,
			'logo_height'                 => 28,
			'logo_align'                  => 'left',
			'privacy_url'                 => $privacy,
			'privacy_page_id'             => 0,
			'reopen_button'               => true,
			'show_branding'               => false,
			'block_external_embeds'       => true,
			'external_embed_allowlist'    => array(),
			'trust_geo_headers'          => false,
			'banner_language'             => 'auto',
			'locale'                      => 'de',
			'statistics_enabled'          => true,
			'marketing_enabled'           => true,
			'preferences_enabled'         => false,
			'texts_de'                    => self::default_texts_de(),
			'texts_en'                    => self::default_texts_en(),
			'last_scan_at'                => '',
			'scan_weekly'                 => true,
			'scan_notify_email'           => '',
			'google_ids_ads'              => array(),
			'google_ids_analytics'        => array(),
			'google_ids_tags'             => array(),
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored = get_option( CONSENTGATE_OPTION_KEY, array() );
		$merged = wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );

		foreach ( array( 'texts_de', 'texts_en' ) as $key ) {
			$merged[ $key ] = wp_parse_args(
				is_array( $merged[ $key ] ?? null ) ? $merged[ $key ] : array(),
				'texts_de' === $key ? self::default_texts_de() : self::default_texts_en()
			);
		}

		foreach ( array( 'google_ids_ads', 'google_ids_analytics', 'google_ids_tags' ) as $key ) {
			$merged[ $key ] = is_array( $merged[ $key ] ?? null ) ? array_values( $merged[ $key ] ) : array();
		}

		// plugins_loaded runs before WP_Rewrite exists. Caching that pass would
		// keep an empty privacy URL for the rest of the request.
		global $wp_rewrite;
		if ( $wp_rewrite instanceof WP_Rewrite ) {
			self::$cache = $merged;
		}

		return $merged;
	}

	/**
	 * WordPress privacy-policy URL, or empty while rewrite rules are not ready.
	 *
	 * get_privacy_policy_url() calls get_permalink(), which needs $wp_rewrite.
	 * That global is created after plugins_loaded, and the weekly schedule
	 * reads settings on that hook.
	 */
	private static function privacy_policy_url(): string {
		global $wp_rewrite;

		if ( ! function_exists( 'get_privacy_policy_url' ) || ! $wp_rewrite instanceof WP_Rewrite ) {
			return '';
		}

		return (string) get_privacy_policy_url();
	}

	/**
	 * Get a single option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all = self::all();

		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}

		return $default;
	}

	/**
	 * Update settings (partial merge).
	 *
	 * @param array<string, mixed> $settings New values.
	 */
	public static function update( array $settings ): void {
		$merged      = wp_parse_args( $settings, self::all() );
		self::$cache = $merged;
		update_option( CONSENTGATE_OPTION_KEY, $merged );
	}

	/**
	 * Whether the first-run setup was finished (scan + branding choice).
	 */
	public static function setup_completed(): bool {
		return '1' === (string) get_option( 'consentgate_setup_completed', '0' );
	}

	/**
	 * Mark first-run setup as done.
	 */
	public static function mark_setup_completed(): void {
		update_option( 'consentgate_setup_completed', '1', false );
	}

	/**
	 * Run the first-run setup again.
	 */
	public static function reset_setup(): void {
		update_option( 'consentgate_setup_completed', '0', false );
	}

	/**
	 * Existing installs that already scanned skip the wizard.
	 */
	public static function maybe_complete_legacy_setup(): void {
		if ( false !== get_option( 'consentgate_setup_completed', false ) ) {
			return;
		}

		$done = '' !== self::last_scan_at() ? '1' : '0';
		add_option( 'consentgate_setup_completed', $done, '', false );
	}

	/**
	 * Persist scan timestamp outside the Settings-API payload so it cannot be dropped.
	 */
	public static function mark_scanned(): void {
		$now = gmdate( 'c' );
		update_option( 'consentgate_last_scan_at', $now, false );
		$all = self::all();
		$all['last_scan_at'] = $now;
		self::$cache         = $all;
		update_option( CONSENTGATE_OPTION_KEY, $all );
	}

	/**
	 * ISO timestamp of the last completed scan, or empty.
	 */
	public static function last_scan_at(): string {
		$own = get_option( 'consentgate_last_scan_at', '' );
		if ( is_string( $own ) && '' !== $own ) {
			return $own;
		}

		$fallback = self::get( 'last_scan_at', '' );

		return is_string( $fallback ) ? $fallback : '';
	}

	/**
	 * Increment banner version so existing visitors are asked again.
	 */
	public static function bump_banner_version(): void {
		$all = self::all();
		$all['banner_version'] = (int) ( $all['banner_version'] ?? 1 ) + 1;
		self::$cache = $all;
		update_option( CONSENTGATE_OPTION_KEY, $all );
	}

	/**
	 * Clear cache.
	 */
	public static function flush_cache(): void {
		self::$cache = null;
	}

	/**
	 * Purpose policies for storefront config.
	 *
	 * @return array<string, string>
	 */
	public static function purpose_policies(): array {
		$all = self::all();

		return array(
			'statistics'  => self::sanitize_policy( (string) $all['purpose_statistics'] ),
			'marketing'   => self::sanitize_policy( (string) $all['purpose_marketing'] ),
			'preferences' => self::sanitize_policy( (string) $all['purpose_preferences'] ),
		);
	}

	/**
	 * Banner logo URL from the media library, with legacy URL fallback.
	 */
	public static function resolved_logo_url(): string {
		$id = (int) self::get( 'logo_id', 0 );
		if ( $id > 0 ) {
			$url = wp_get_attachment_image_url( $id, 'full' );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return (string) self::get( 'logo_url', '' );
	}

	/**
	 * Banner width preset.
	 */
	public static function banner_size(): string {
		$size = sanitize_key( (string) self::get( 'banner_size', 'normal' ) );

		return in_array( $size, array( 'compact', 'normal', 'wide' ), true ) ? $size : 'normal';
	}

	/**
	 * Height of the optional banner logo, in pixels.
	 */
	public static function logo_height(): int {
		$height = (int) self::get( 'logo_height', 28 );

		return max( 16, min( 200, $height > 0 ? $height : 28 ) );
	}

	/**
	 * Horizontal placement of the optional banner logo.
	 */
	public static function logo_align(): string {
		$align = (string) self::get( 'logo_align', 'left' );

		return in_array( $align, array( 'left', 'center' ), true ) ? $align : 'left';
	}

	/**
	 * Inline brand mark. IDs are prefixed so several copies on one page keep their gradients.
	 *
	 * @param string $id Prefix, letters and digits only.
	 */
	public static function brand_icon_svg( string $id ): string {
		static $raw = null;
		if ( null === $raw ) {
			$path = CONSENTGATE_PLUGIN_DIR . 'assets/images/icon.svg';
			$file = is_readable( $path ) ? file_get_contents( $path ) : '';
			$raw  = is_string( $file ) ? $file : '';
		}
		if ( '' === $raw ) {
			return '';
		}

		$id  = preg_replace( '/[^A-Za-z0-9_-]/', '', $id );
		$id  = is_string( $id ) && '' !== $id ? $id : 'cg';
		$svg = str_replace(
			array( 'id="bgGrad"', 'url(#bgGrad)', 'id="navyGrad"', 'url(#navyGrad)', 'id="orangeGrad"', 'url(#orangeGrad)', 'width="512"', 'height="512"' ),
			array( 'id="' . $id . 'Bg"', 'url(#' . $id . 'Bg)', 'id="' . $id . 'Navy"', 'url(#' . $id . 'Navy)', 'id="' . $id . 'Orange"', 'url(#' . $id . 'Orange)', 'width="16"', 'height="16"' ),
			$raw
		);
		$svg = preg_replace( '/<svg\b/', '<svg class="cg-brand-mark" focusable="false" aria-hidden="true"', $svg, 1 );

		return is_string( $svg ) ? $svg : '';
	}

	/**
	 * Brand mark as a data URI so the banner does not depend on an SVG response type.
	 */
	public static function brand_icon_uri(): string {
		static $uri = null;
		if ( null !== $uri ) {
			return $uri;
		}

		$path = CONSENTGATE_PLUGIN_DIR . 'assets/images/icon.svg';
		$svg  = is_readable( $path ) ? file_get_contents( $path ) : '';
		$uri  = is_string( $svg ) && '' !== $svg
			? 'data:image/svg+xml;base64,' . base64_encode( $svg )
			: '';

		return $uri;
	}

	/**
	 * Privacy policy URL from the selected page, with legacy URL fallback.
	 */
	public static function resolved_privacy_url(): string {
		global $wp_rewrite;

		$id = (int) self::get( 'privacy_page_id', 0 );
		if ( $id > 0 && $wp_rewrite instanceof WP_Rewrite ) {
			$permalink = get_permalink( $id );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		$url = (string) self::get( 'privacy_url', '' );
		if ( '' !== $url ) {
			return $url;
		}

		return self::privacy_policy_url();
	}

	/**
	 * Attachment ID for the banner logo picker.
	 */
	public static function selected_logo_id(): int {
		$id = (int) self::get( 'logo_id', 0 );
		if ( $id > 0 && wp_attachment_is_image( $id ) ) {
			return $id;
		}

		$url = (string) self::get( 'logo_url', '' );
		if ( '' === $url ) {
			return 0;
		}

		$from_url = attachment_url_to_postid( $url );

		return ( $from_url > 0 && wp_attachment_is_image( $from_url ) ) ? $from_url : 0;
	}

	/**
	 * Page ID for the privacy policy dropdown.
	 */
	public static function selected_privacy_page_id(): int {
		$id = (int) self::get( 'privacy_page_id', 0 );
		if ( $id > 0 && 'page' === get_post_type( $id ) ) {
			return $id;
		}

		$url = (string) self::get( 'privacy_url', '' );
		if ( '' !== $url ) {
			$from_url = url_to_postid( $url );
			if ( $from_url > 0 && 'page' === get_post_type( $from_url ) ) {
				return $from_url;
			}
		}

		$wp_privacy = (int) get_option( 'wp_page_for_privacy_policy' );

		return ( $wp_privacy > 0 && 'page' === get_post_type( $wp_privacy ) ) ? $wp_privacy : 0;
	}

	/**
	 * Banner language mode: auto, de, or en.
	 */
	public static function banner_language(): string {
		$mode = (string) self::get( 'banner_language', 'auto' );

		return in_array( $mode, array( 'auto', 'de', 'en' ), true ) ? $mode : 'auto';
	}

	/**
	 * de or en from a locale tag, or empty.
	 *
	 * @param string $tag Locale or lang attribute.
	 */
	public static function locale_from_tag( string $tag ): string {
		$tag = strtolower( str_replace( '_', '-', $tag ) );
		if ( str_starts_with( $tag, 'en' ) ) {
			return 'en';
		}
		if ( str_starts_with( $tag, 'de' ) ) {
			return 'de';
		}

		return '';
	}

	/**
	 * Visitor-facing banner language.
	 */
	public static function frontend_locale(): string {
		$mode = self::banner_language();
		if ( 'de' === $mode || 'en' === $mode ) {
			return $mode;
		}

		$from_site = self::locale_from_tag( determine_locale() );

		return '' !== $from_site ? $from_site : 'en';
	}

	/**
	 * Fixed UI strings for the banner and embed placeholders.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function ui_strings(): array {
		return array(
			'de' => array(
				'emptyCategory'   => 'Keine Dienste in dieser Kategorie.',
				'mapsTitle'       => 'Google Maps ist blockiert',
				'mapsBody'        => 'Marketing-Cookies erlauben, um die Karte zu sehen.',
				'appleTitle'      => 'Apple Maps ist blockiert',
				'linexoTitle'     => 'Linexo ist blockiert',
				'embedBody'       => 'Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.',
				'youtubeTitle'    => 'YouTube ist blockiert',
				'youtubeBody'     => 'Marketing-Cookies erlauben, um das Video zu sehen.',
				'vimeoTitle'      => 'Vimeo ist blockiert',
				'vimeoBody'       => 'Marketing-Cookies erlauben, um das Video zu sehen.',
				'instagramTitle'  => 'Instagram ist blockiert',
				'twitterTitle'    => 'X (Twitter) ist blockiert',
				'facebookTitle'   => 'Facebook ist blockiert',
				'tiktokTitle'     => 'TikTok ist blockiert',
				'pinterestTitle'  => 'Pinterest ist blockiert',
				'linkedinTitle'   => 'LinkedIn ist blockiert',
				'calendlyTitle'   => 'Calendly ist blockiert',
				'spotifyTitle'    => 'Spotify ist blockiert',
				'soundcloudTitle' => 'SoundCloud ist blockiert',
				'externalTitle'   => 'Externer Inhalt ist blockiert',
				'externalBody'    => 'Erlauben Sie Marketing-Cookies, um Inhalte von %s zu sehen.',
				'blockedService'  => '%s ist blockiert',
				'openSettings'    => 'Cookie-Einstellungen öffnen',
			),
			'en' => array(
				'emptyCategory'   => 'No services in this category.',
				'mapsTitle'       => 'Google Maps is blocked',
				'mapsBody'        => 'Allow marketing cookies to show the map.',
				'appleTitle'      => 'Apple Maps is blocked',
				'linexoTitle'     => 'Linexo is blocked',
				'embedBody'       => 'Allow the matching category to show this content.',
				'youtubeTitle'    => 'YouTube is blocked',
				'youtubeBody'     => 'Allow marketing cookies to play this video.',
				'vimeoTitle'      => 'Vimeo is blocked',
				'vimeoBody'       => 'Allow marketing cookies to play this video.',
				'instagramTitle'  => 'Instagram is blocked',
				'twitterTitle'    => 'X (Twitter) is blocked',
				'facebookTitle'   => 'Facebook is blocked',
				'tiktokTitle'     => 'TikTok is blocked',
				'pinterestTitle'  => 'Pinterest is blocked',
				'linkedinTitle'   => 'LinkedIn is blocked',
				'calendlyTitle'   => 'Calendly is blocked',
				'spotifyTitle'    => 'Spotify is blocked',
				'soundcloudTitle' => 'SoundCloud is blocked',
				'externalTitle'   => 'External content is blocked',
				'externalBody'    => 'Allow marketing cookies to show content from %s.',
				'blockedService'  => '%s is blocked',
				'openSettings'    => 'Open cookie settings',
			),
		);
	}

	/**
	 * Storefront config consumed by consent-gate.js.
	 *
	 * @return array<string, mixed>
	 */
	public static function storefront_config(): array {
		$all      = self::all();
		$locale   = self::frontend_locale();
		$services = ConsentGate_Services::for_banner();
		$logo     = self::resolved_logo_url();

		return array(
			'version'                    => (int) $all['banner_version'],
			'languageMode'               => self::banner_language(),
			'locale'                     => $locale,
			'position'                   => (string) $all['position'],
			'bannerSize'                 => self::banner_size(),
			'brandIcon'                  => self::brand_icon_uri(),
			'colors'                     => array(
				'primary'    => (string) $all['primary_color'],
				'background' => (string) $all['background_color'],
				'text'       => (string) $all['text_color'],
				'acceptBg'   => (string) $all['accept_bg'],
				'acceptText' => (string) $all['accept_text'],
				'rejectBg'   => (string) $all['reject_bg'],
				'rejectText' => (string) $all['reject_text'],
			),
			'logoUrl'                    => '' !== $logo ? $logo : null,
			'logoHeight'                 => self::logo_height(),
			'logoAlign'                  => self::logo_align(),
			'brandIconSvg'               => self::brand_icon_svg( 'cgLive' ),
			'privacyUrl'                 => self::resolved_privacy_url(),
			'showReopen'                 => (bool) $all['reopen_button'],
			'showBranding'               => (bool) $all['show_branding'],
			'texts'                      => 'en' === $locale ? $all['texts_en'] : $all['texts_de'],
			'textsByLocale'              => array(
				'de' => $all['texts_de'],
				'en' => $all['texts_en'],
			),
			'ui'                         => self::ui_strings(),
			'categoriesEnabled'          => array(
				'necessary'   => true,
				'statistics'  => (bool) $all['statistics_enabled'],
				'marketing'   => (bool) $all['marketing_enabled'],
				'preferences' => (bool) $all['preferences_enabled'],
			),
			'regionMode'                 => (string) $all['region_mode'],
			'urlPassthrough'             => (bool) $all['url_passthrough'],
			'purposePolicies'            => self::purpose_policies(),
			'analyticsFollowsMarketing'  => (bool) $all['analytics_follows_marketing'],
			'googleAdvancedConsentMode'  => (bool) $all['google_advanced_consent_mode'],
			'brandUrl'                   => CONSENTGATE_PRODUCT_URL,
			'googleIds'                  => array(
				'ads'       => $all['google_ids_ads'],
				'analytics' => $all['google_ids_analytics'],
				'tags'      => $all['google_ids_tags'],
			),
			'blockRules'                 => ConsentGate_Blocker::rules(),
			'embedGates'                 => ConsentGate_Blocker::embed_gates(),
			'widgetRegistry'             => ConsentGate_Widgets::registry(),
			'blockExternalEmbeds'        => (bool) $all['block_external_embeds'],
			'externalEmbedAllowlist'     => array_values( array_filter( (array) $all['external_embed_allowlist'] ) ),
			'services'                   => $services,
		);
	}

	/**
	 * Sanitize a load policy.
	 *
	 * @param string $policy Policy.
	 */
	public static function sanitize_policy( string $policy ): string {
		return 'without_consent' === $policy ? 'without_consent' : 'with_consent';
	}

	/**
	 * Sanitize a consent category.
	 *
	 * @param string $category Category.
	 */
	public static function sanitize_category( string $category ): string {
		$allowed = array( 'necessary', 'statistics', 'marketing', 'preferences' );

		return in_array( $category, $allowed, true ) ? $category : 'marketing';
	}

	/**
	 * Sanitize full settings array from Settings API.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( mixed $input ): array {
		$defaults = self::defaults();
		$current  = self::all();
		$input    = is_array( $input ) ? $input : array();
		$output   = $current;

		$bool_keys = array(
			'enabled',
			'url_passthrough',
			'google_advanced_consent_mode',
			'analytics_follows_marketing',
			'statistics_enabled',
			'marketing_enabled',
			'preferences_enabled',
			'reopen_button',
			'show_branding',
			'block_external_embeds',
			'trust_geo_headers',
			'scan_weekly',
		);

		foreach ( $bool_keys as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$output[ $key ] = (bool) (int) $input[ $key ];
			}
		}

		if ( isset( $input['retention_days'] ) ) {
			$output['retention_days'] = max( 30, min( 2555, (int) $input['retention_days'] ) );
		}

		if ( isset( $input['region_mode'] ) ) {
			$output['region_mode'] = in_array( $input['region_mode'], array( 'always', 'eu' ), true )
				? $input['region_mode']
				: 'always';
		}

		if ( isset( $input['position'] ) ) {
			$pos = sanitize_key( (string) $input['position'] );
			$output['position'] = in_array( $pos, array( 'bottom', 'corner', 'corner-left', 'modal' ), true )
				? $pos
				: 'bottom';
		}

		if ( isset( $input['banner_size'] ) ) {
			$size = sanitize_key( (string) $input['banner_size'] );
			$output['banner_size'] = in_array( $size, array( 'compact', 'normal', 'wide' ), true )
				? $size
				: 'normal';
		}

		if ( isset( $input['banner_language'] ) ) {
			$language = sanitize_key( (string) $input['banner_language'] );
			$output['banner_language'] = in_array( $language, array( 'auto', 'de', 'en' ), true ) ? $language : 'auto';
		}

		if ( isset( $input['scan_notify_email'] ) ) {
			$email = sanitize_email( (string) $input['scan_notify_email'] );
			$output['scan_notify_email'] = is_email( $email ) ? $email : '';
		}

		if ( isset( $input['external_embed_allowlist'] ) ) {
			$raw = $input['external_embed_allowlist'];
			if ( is_array( $raw ) ) {
				$lines = $raw;
			} else {
				$lines = preg_split( '/[\s,]+/', (string) $raw ) ?: array();
			}
			$hosts = array();
			foreach ( $lines as $line ) {
				$host = self::sanitize_allowlist_host( (string) $line );
				if ( '' !== $host ) {
					$hosts[] = $host;
				}
			}
			$output['external_embed_allowlist'] = array_values( array_unique( $hosts ) );
		}

		if ( isset( $input['locale'] ) ) {
			$output['locale'] = 'en' === $input['locale'] ? 'en' : 'de';
		}

		foreach ( array( 'purpose_statistics', 'purpose_marketing', 'purpose_preferences' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = self::sanitize_policy( (string) $input[ $key ] );
			}
		}

		$color_keys = array(
			'primary_color',
			'background_color',
			'text_color',
			'accept_bg',
			'accept_text',
			'reject_bg',
			'reject_text',
		);

		foreach ( $color_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = self::sanitize_hex( (string) $input[ $key ], (string) $defaults[ $key ] );
			}
		}

		if ( isset( $input['logo_height'] ) ) {
			$output['logo_height'] = max( 16, min( 200, (int) $input['logo_height'] ) );
		}

		if ( isset( $input['logo_align'] ) ) {
			$align                = sanitize_key( (string) $input['logo_align'] );
			$output['logo_align'] = in_array( $align, array( 'left', 'center' ), true ) ? $align : 'left';
		}

		if ( isset( $input['logo_id'] ) ) {
			$logo_id           = self::sanitize_attachment_id( (int) $input['logo_id'] );
			$output['logo_id'] = $logo_id;
			if ( $logo_id > 0 ) {
				$logo_url           = wp_get_attachment_image_url( $logo_id, 'full' );
				$output['logo_url'] = is_string( $logo_url ) ? esc_url_raw( $logo_url ) : '';
			} elseif ( ! empty( $input['logo_cleared'] ) ) {
				$output['logo_url'] = '';
			}
		} elseif ( isset( $input['logo_url'] ) ) {
			$output['logo_url'] = esc_url_raw( (string) $input['logo_url'] );
		}

		if ( isset( $input['privacy_page_id'] ) ) {
			$page_id                   = self::sanitize_page_id( (int) $input['privacy_page_id'] );
			$output['privacy_page_id'] = $page_id;
			if ( $page_id > 0 ) {
				$permalink             = get_permalink( $page_id );
				$output['privacy_url'] = is_string( $permalink ) ? esc_url_raw( $permalink ) : '';
			} elseif ( (int) ( $current['privacy_page_id'] ?? 0 ) > 0 ) {
				$output['privacy_url'] = esc_url_raw( self::privacy_policy_url() );
			}
		} elseif ( isset( $input['privacy_url'] ) ) {
			$output['privacy_url'] = esc_url_raw( (string) $input['privacy_url'] );
		}

		foreach ( array( 'texts_de', 'texts_en' ) as $pack ) {
			if ( isset( $input[ $pack ] ) && is_array( $input[ $pack ] ) ) {
				$base = 'texts_de' === $pack ? self::default_texts_de() : self::default_texts_en();
				$clean = array();
				foreach ( $base as $text_key => $fallback ) {
					$clean[ $text_key ] = isset( $input[ $pack ][ $text_key ] )
						? sanitize_textarea_field( (string) $input[ $pack ][ $text_key ] )
						: $fallback;
				}
				$output[ $pack ] = $clean;
			}
		}

		foreach ( array( 'google_ids_ads', 'google_ids_analytics', 'google_ids_tags' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ) {
				$output[ $key ] = array_values(
					array_filter(
						array_map( 'sanitize_text_field', $input[ $key ] ),
						static fn( $id ) => '' !== $id
					)
				);
			}
		}

		if ( isset( $input['last_scan_at'] ) && is_string( $input['last_scan_at'] ) ) {
			$output['last_scan_at'] = sanitize_text_field( $input['last_scan_at'] );
		}

		if ( isset( $input['banner_version'] ) ) {
			$output['banner_version'] = max( 1, (int) $input['banner_version'] );
		}

		$banner_keys = array(
			'position',
			'primary_color',
			'background_color',
			'text_color',
			'accept_bg',
			'accept_text',
			'reject_bg',
			'reject_text',
			'logo_url',
			'logo_id',
			'privacy_url',
			'privacy_page_id',
			'reopen_button',
			'statistics_enabled',
			'marketing_enabled',
			'preferences_enabled',
			'texts_de',
			'texts_en',
			'banner_language',
			'locale',
		);

		foreach ( $banner_keys as $key ) {
			if ( array_key_exists( $key, $input ) && $output[ $key ] !== $current[ $key ] ) {
				$output['banner_version'] = (int) $current['banner_version'] + 1;
				break;
			}
		}

		self::$cache = null;
		unset( $output['logo_cleared'] );

		return $output;
	}

	/**
	 * Sanitize one allowlisted embed host (hostname or *.example.com).
	 *
	 * @param string $value Raw host or URL.
	 */
	public static function sanitize_allowlist_host( string $value ): string {
		$value = trim( strtolower( $value ) );
		if ( '' === $value ) {
			return '';
		}
		if ( str_contains( $value, '://' ) ) {
			$host  = wp_parse_url( $value, PHP_URL_HOST );
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
	 * Whether a hostname is covered by an allowlist entry (exact, parent, or *.base).
	 *
	 * @param string $host  Normalized hostname without leading www.
	 * @param string $entry Sanitized allowlist entry.
	 */
	public static function host_matches_allowlist_entry( string $host, string $entry ): bool {
		$host  = strtolower( preg_replace( '/^www\./', '', $host ) ?? $host );
		$entry = self::sanitize_allowlist_host( $entry );
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
	 * Sanitize a hex color.
	 *
	 * @param string $value   Raw value.
	 * @param string $fallback Fallback.
	 */
	private static function sanitize_hex( string $value, string $fallback ): string {
		$value = sanitize_hex_color( $value );

		return is_string( $value ) && '' !== $value ? $value : $fallback;
	}

	/**
	 * Image attachment ID or 0.
	 *
	 * @param int $id Raw ID.
	 */
	private static function sanitize_attachment_id( int $id ): int {
		return ( $id > 0 && wp_attachment_is_image( $id ) ) ? $id : 0;
	}

	/**
	 * Published or private page ID or 0.
	 *
	 * @param int $id Raw ID.
	 */
	private static function sanitize_page_id( int $id ): int {
		if ( $id <= 0 ) {
			return 0;
		}

		$post = get_post( $id );
		if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
			return 0;
		}

		return in_array( $post->post_status, array( 'publish', 'private' ), true ) ? $id : 0;
	}
}
