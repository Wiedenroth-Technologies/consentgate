<?php
/**
 * ConsentGate admin UI helper (own copy, not shared with other plugins).
 *
 * CSS classes: {slug}-wt-{part}
 * CSS source uses the placeholder __SLUG__.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Admin_Ui
 */
class ConsentGate_Admin_Ui {

	/**
	 * Plugin slug used as CSS prefix.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Absolute path to wt-admin.css.
	 *
	 * @var string
	 */
	private string $css_path;

	/**
	 * URL to wt-admin.js (optional).
	 *
	 * @var string
	 */
	private string $js_url;

	/**
	 * Asset version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Whether notice output buffering is active.
	 *
	 * @var bool
	 */
	private bool $capturing_notices = false;

	/**
	 * Captured foreign admin notices HTML.
	 *
	 * @var string
	 */
	private string $captured_notices = '';

	/**
	 * @param string $slug     Plugin slug, e.g. consentgate.
	 * @param string $css_path Absolute path to wt-admin.css.
	 * @param string $js_url   URL to wt-admin.js.
	 * @param string $version  Asset version.
	 */
	public function __construct( string $slug, string $css_path, string $js_url = '', string $version = '1.0.0' ) {
		$this->slug     = sanitize_key( $slug );
		$this->css_path = $css_path;
		$this->js_url   = $js_url;
		$this->version  = $version;
	}

	/**
	 * Plugin slug.
	 */
	public function slug(): string {
		return $this->slug;
	}

	/**
	 * Style/script handle used by enqueue().
	 */
	public function handle(): string {
		return $this->slug . '-wt-admin';
	}

	/**
	 * Top-level menu position: between Comments (25) and Appearance (60).
	 */
	public const MENU_POSITION = 58;

	/**
	 * Separator above the WT plugin group, matching the core gap before Appearance.
	 */
	public static function ensure_menu_separator(): void {
		global $menu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as $item ) {
			if ( is_array( $item ) && isset( $item[2] ) && 'separator-wt' === $item[2] ) {
				return;
			}
		}

		$menu[ self::MENU_POSITION - 1 ] = array( '', 'read', 'separator-wt', '', 'wp-menu-separator' );
	}

	/**
	 * Register notice capture and body class on own admin screens.
	 */
	public function register_admin_hooks(): void {
		add_action( 'admin_notices', array( $this, 'start_notice_capture' ), -1000 );
		add_action( 'all_admin_notices', array( $this, 'finish_notice_capture' ), PHP_INT_MAX );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * Whether the current request is this plugin's admin screen.
	 */
	public function is_own_screen(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';
		if ( $this->slug === $page || str_starts_with( $page, $this->slug . '-' ) ) {
			return true;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen && str_contains( (string) $screen->id, $this->slug );
	}

	/**
	 * Add a body class so foreign notices can be hidden on our screen.
	 *
	 * @param mixed $classes Body classes.
	 */
	public function admin_body_class( $classes ): string {
		$classes = is_string( $classes ) ? $classes : '';
		if ( $this->is_own_screen() ) {
			$classes .= ' ' . $this->slug . '-wt-screen';
		}
		return $classes;
	}

	/**
	 * Start buffering WordPress / third-party admin notices.
	 */
	public function start_notice_capture(): void {
		if ( $this->capturing_notices || ! $this->is_own_screen() ) {
			return;
		}

		$this->capturing_notices = true;
		ob_start();
	}

	/**
	 * Store buffered notices and keep them out of the default slot.
	 */
	public function finish_notice_capture(): void {
		if ( ! $this->capturing_notices ) {
			return;
		}

		$html                      = ob_get_clean();
		$this->capturing_notices   = false;
		$this->captured_notices    = is_string( $html ) ? trim( $html ) : '';
	}

	/**
	 * Inbox toggle + overlay for foreign admin notices.
	 */
	public function inbox(): void {
		echo '<div class="' . esc_attr( $this->cls( 'inbox' ) ) . '" data-wt-inbox>';
		echo '<div class="' . esc_attr( $this->cls( 'inbox__backdrop' ) ) . '" data-wt-inbox-backdrop hidden></div>';
		echo '<button type="button" class="' . esc_attr( $this->cls( 'inbox__toggle' ) ) . '" data-wt-inbox-toggle hidden aria-expanded="false" aria-haspopup="true">';
		echo '<span data-wt-inbox-label></span>';
		echo '</button>';
		echo '<div class="' . esc_attr( $this->cls( 'inbox__panel' ) ) . '" data-wt-inbox-panel hidden role="region" aria-label="' . esc_attr__( 'WordPress notices', 'wiedenroth-technologies-consentgate' ) . '">';
		if ( '' !== $this->captured_notices ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin notice HTML from WP / other plugins.
			echo $this->captured_notices;
		}
		echo '</div></div>';
	}

	/**
	 * Build a prefixed class name.
	 *
	 * cls( 'card' )                → consentgate-wt-card
	 * cls( 'card__title' )         → consentgate-wt-card__title
	 * cls( 'btn', 'primary' )      → consentgate-wt-btn consentgate-wt-btn--primary
	 * cls( 'notice', 'warning' )   → consentgate-wt-notice consentgate-wt-notice--warning
	 *
	 * @param string $part      Block or element, without prefix.
	 * @param string $modifiers Space-separated modifiers without leading --.
	 */
	public function cls( string $part, string $modifiers = '' ): string {
		$base = $this->slug . '-wt-' . ltrim( $part, '-' );
		$out  = $base;

		if ( '' === trim( $modifiers ) ) {
			return $out;
		}

		foreach ( preg_split( '/\s+/', trim( $modifiers ) ) ?: array() as $mod ) {
			if ( '' === $mod ) {
				continue;
			}
			$out .= ' ' . $base . '--' . $mod;
		}

		return $out;
	}

	/**
	 * Enqueue scoped admin CSS/JS on the current screen.
	 */
	public function enqueue(): void {
		$handle = $this->handle();

		wp_register_style( $handle, false, array(), $this->version );
		wp_enqueue_style( $handle );

		if ( is_readable( $this->css_path ) ) {
			$css = file_get_contents( $this->css_path );
			if ( is_string( $css ) && '' !== $css ) {
				wp_add_inline_style( $handle, str_replace( '__SLUG__', $this->slug, $css ) );
			}
		}

		if ( '' !== $this->js_url ) {
			wp_enqueue_script(
				$handle,
				$this->js_url,
				array(),
				$this->version,
				true
			);

			wp_localize_script(
				$handle,
				'consentgateAdminUi',
				array(
					'slug' => $this->slug,
					'i18n' => array(
						'copied'    => __( 'Class name copied.', 'wiedenroth-technologies-consentgate' ),
						'noticeOne' => __( '1 notice', 'wiedenroth-technologies-consentgate' ),
						/* translators: %d: number of admin notices */
						'noticeMany' => __( '%d notices', 'wiedenroth-technologies-consentgate' ),
					),
				)
			);
		}
	}

	/**
	 * Inline an extra CSS file that uses the __SLUG__ placeholder.
	 *
	 * @param string $css_path Absolute path.
	 */
	public function enqueue_extra_css( string $css_path ): void {
		if ( ! is_readable( $css_path ) ) {
			return;
		}

		$css = file_get_contents( $css_path );
		if ( ! is_string( $css ) || '' === $css ) {
			return;
		}

		wp_add_inline_style( $this->handle(), str_replace( '__SLUG__', $this->slug, $css ) );
	}

	/**
	 * Escape and print a class attribute.
	 *
	 * @param string $part      Block or element.
	 * @param string $modifiers Modifiers.
	 */
	public function class_attr( string $part, string $modifiers = '' ): void {
		echo 'class="' . esc_attr( $this->cls( $part, $modifiers ) ) . '"';
	}

	/**
	 * Card heading – title (and optional text) inside a card.
	 *
	 * @param string $title Short title.
	 * @param string $text  Optional explanation.
	 * @param string $tip   Optional tip line.
	 */
	public function heading( string $title, string $text = '', string $tip = '' ): void {
		echo '<div class="' . esc_attr( $this->cls( 'card__head' ) ) . '">';
		echo '<h2 class="' . esc_attr( $this->cls( 'card__title' ) ) . '">' . esc_html( $title ) . '</h2>';
		if ( '' !== $text ) {
			echo '<p class="' . esc_attr( $this->cls( 'card__text' ) ) . '">' . esc_html( $text ) . '</p>';
		}
		if ( '' !== $tip ) {
			echo '<p class="' . esc_attr( $this->cls( 'card__tip' ) ) . '"><span class="' . esc_attr( $this->cls( 'card__tip-label' ) ) . '">' . esc_html__( 'Tip:', 'wiedenroth-technologies-consentgate' ) . '</span> ' . esc_html( $tip ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * @deprecated Use heading() inside a card.
	 *
	 * @param string $title Short title.
	 * @param string $text  Explanation.
	 * @param string $tip   Optional tip line.
	 */
	public function intro( string $title, string $text, string $tip = '' ): void {
		$this->heading( $title, $text, $tip );
	}

	/**
	 * Inline notice.
	 *
	 * @param string $type    info|success|warning|error.
	 * @param string $title   Optional title.
	 * @param string $message Message.
	 */
	public function notice( string $type, string $message, string $title = '' ): void {
		$allowed = array( 'info', 'success', 'warning', 'error' );
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'info';
		}

		echo '<div class="' . esc_attr( $this->cls( 'notice', $type ) ) . '" role="status">';
		if ( '' !== $title ) {
			echo '<p class="' . esc_attr( $this->cls( 'notice__title' ) ) . '">' . esc_html( $title ) . '</p>';
		}
		echo '<p class="' . esc_attr( $this->cls( 'notice__text' ) ) . '">' . esc_html( $message ) . '</p>';
		echo '</div>';
	}

	/**
	 * Status pill.
	 *
	 * @param string $status ok|warn|off.
	 * @param string $label  Visible label.
	 */
	public function badge( string $status, string $label ): void {
		$allowed = array( 'ok', 'warn', 'off' );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'off';
		}

		printf(
			'<span class="%1$s"><span class="%2$s" aria-hidden="true"></span>%3$s</span>',
			esc_attr( $this->cls( 'badge', $status ) ),
			esc_attr( $this->cls( 'badge__dot' ) ),
			esc_html( $label )
		);
	}

	/**
	 * Button markup.
	 *
	 * @param string $label   Label.
	 * @param string $variant primary|secondary|ghost|danger.
	 * @param string $type    button|submit.
	 * @param string $href    Optional link URL.
	 */
	public function button( string $label, string $variant = 'primary', string $type = 'button', string $href = '' ): void {
		$allowed = array( 'primary', 'secondary', 'ghost', 'danger' );
		if ( ! in_array( $variant, $allowed, true ) ) {
			$variant = 'primary';
		}

		$class = $this->cls( 'btn', $variant );

		if ( '' !== $href ) {
			printf(
				'<a class="%1$s" href="%2$s">%3$s</a>',
				esc_attr( $class ),
				esc_url( $href ),
				esc_html( $label )
			);
			return;
		}

		printf(
			'<button type="%1$s" class="%2$s">%3$s</button>',
			esc_attr( $type ),
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	/**
	 * Toggle (styled checkbox).
	 *
	 * @param string $name    Input name.
	 * @param bool   $checked Current value.
	 * @param string $label   Visible label.
	 * @param string $id      Optional input id.
	 */
	public function toggle( string $name, bool $checked, string $label, string $id = '' ): void {
		if ( '' === $id ) {
			$id = $this->slug . '-toggle-' . sanitize_key( $name );
		}

		echo '<label class="' . esc_attr( $this->cls( 'toggle' ) ) . '" for="' . esc_attr( $id ) . '">';
		printf(
			'<input type="checkbox" class="%1$s" name="%2$s" id="%3$s" value="1" %4$s />',
			esc_attr( $this->cls( 'toggle__input' ) ),
			esc_attr( $name ),
			esc_attr( $id ),
			checked( $checked, true, false )
		);
		echo '<span class="' . esc_attr( $this->cls( 'toggle__track' ) ) . '" aria-hidden="true"><span class="' . esc_attr( $this->cls( 'toggle__thumb' ) ) . '"></span></span>';
		echo '<span class="' . esc_attr( $this->cls( 'toggle__label' ) ) . '">' . esc_html( $label ) . '</span>';
		echo '</label>';
	}

	/**
	 * Help text under a field.
	 *
	 * @param string $text Help text.
	 */
	public function help( string $text ): void {
		echo '<p class="' . esc_attr( $this->cls( 'field__help' ) ) . '">' . esc_html( $text ) . '</p>';
	}

	/**
	 * Legal + brand footer.
	 *
	 * @param array<string, string> $links      Label => URL.
	 * @param string                $brand_url  Brand homepage.
	 * @param string                $brand_img  Optional logo URL.
	 * @param string                $brand_name Brand name fallback.
	 */
	public function footer( array $links, string $brand_url, string $brand_img = '', string $brand_name = 'Wiedenroth Technologies' ): void {
		echo '<footer class="' . esc_attr( $this->cls( 'footer' ) ) . '">';
		echo '<nav class="' . esc_attr( $this->cls( 'footer__links' ) ) . '" aria-label="' . esc_attr__( 'Rechtliches', 'wiedenroth-technologies-consentgate' ) . '">';

		$first = true;
		foreach ( $links as $label => $url ) {
			if ( ! $first ) {
				echo '<span class="' . esc_attr( $this->cls( 'footer__sep' ) ) . '" aria-hidden="true">·</span>';
			}
			$first = false;
			printf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( $url ),
				esc_html( $label )
			);
		}

		echo '</nav>';

		if ( '' !== $brand_url ) {
			echo '<div class="' . esc_attr( $this->cls( 'brand' ) ) . '">';
			echo '<p class="' . esc_attr( $this->cls( 'brand__label' ) ) . '">' . esc_html__( 'A product by', 'wiedenroth-technologies-consentgate' ) . '</p>';
			echo '<a class="' . esc_attr( $this->cls( 'brand__link' ) ) . '" href="' . esc_url( $brand_url ) . '" target="_blank" rel="noopener noreferrer">';

			if ( '' !== $brand_img ) {
				printf(
					'<img src="%1$s" alt="%2$s" width="118" height="30" loading="lazy" decoding="async" />',
					esc_url( $brand_img ),
					esc_attr( $brand_name )
				);
			} else {
				echo '<span class="' . esc_attr( $this->cls( 'brand__text' ) ) . '">' . esc_html( $brand_name ) . '</span>';
			}

			echo '</a></div>';
		}

		echo '</footer>';
	}
}
