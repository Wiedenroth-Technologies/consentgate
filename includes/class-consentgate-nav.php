<?php
/**
 * Menu item and Navigation-block link that reopens the banner.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Nav
 */
class ConsentGate_Nav {

	/**
	 * Hash used by menu links to open the banner.
	 */
	public const HASH = '#consentgate';

	/**
	 * Register hooks.
	 *
	 * @param ConsentGate_Loader $loader Loader.
	 */
	public function register( ConsentGate_Loader $loader ): void {
		$loader->add_action( 'load-nav-menus.php', $this, 'add_meta_box' );
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_editor_assets' );
		$loader->add_filter( 'nav_menu_link_attributes', $this, 'nav_menu_link_attributes', 10, 2 );
		$loader->add_filter( 'render_block_core/navigation-link', $this, 'render_navigation_link', 10, 2 );
	}

	/**
	 * Whether a URL should open the banner.
	 *
	 * @param string $url Href.
	 */
	public static function is_open_url( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}

		if ( self::HASH === $url ) {
			return true;
		}

		$fragment = wp_parse_url( $url, PHP_URL_FRAGMENT );

		return is_string( $fragment ) && 'consentgate' === $fragment;
	}

	/**
	 * Classic Appearance → Menus metabox.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'consentgate-nav-menu',
			__( 'ConsentGate', 'wiedenroth-technologies-consentgate' ),
			array( $this, 'render_meta_box' ),
			'nav-menus',
			'side',
			'default'
		);
	}

	/**
	 * Metabox markup for the cookie-settings link.
	 */
	public function render_meta_box(): void {
		$label = __( 'Cookie settings', 'wiedenroth-technologies-consentgate' );
		?>
		<div id="posttype-consentgate" class="posttypediv">
			<div id="tabs-panel-consentgate" class="tabs-panel tabs-panel-active">
				<ul id="consentgate-checklist" class="categorychecklist form-no-clear">
					<li>
						<label class="menu-item-title">
							<input type="checkbox" class="menu-item-checkbox" name="menu-item[-1][menu-item-object-id]" value="-1" />
							<?php echo esc_html( $label ); ?>
						</label>
						<input type="hidden" class="menu-item-type" name="menu-item[-1][menu-item-type]" value="custom" />
						<input type="hidden" class="menu-item-object" name="menu-item[-1][menu-item-object]" value="custom" />
						<input type="hidden" class="menu-item-title" name="menu-item[-1][menu-item-title]" value="<?php echo esc_attr( $label ); ?>" />
						<input type="hidden" class="menu-item-url" name="menu-item[-1][menu-item-url]" value="<?php echo esc_attr( self::HASH ); ?>" />
						<input type="hidden" class="menu-item-classes" name="menu-item[-1][menu-item-classes]" value="consentgate-open" />
					</li>
				</ul>
			</div>
			<p class="button-controls wp-clearfix">
				<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'wiedenroth-technologies-consentgate' ); ?>" name="add-post-type-menu-item" id="submit-posttype-consentgate" />
					<span class="spinner"></span>
				</span>
			</p>
			<p class="howto"><?php esc_html_e( 'Reopens the cookie banner, even when the cookie icon is hidden.', 'wiedenroth-technologies-consentgate' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Variation so the link appears in the Navigation block inserter.
	 */
	public function enqueue_editor_assets(): void {
		wp_enqueue_script(
			'consentgate-editor',
			CONSENTGATE_PLUGIN_URL . 'assets/js/editor.js',
			array( 'wp-blocks' ),
			CONSENTGATE_VERSION,
			true
		);

		wp_localize_script(
			'consentgate-editor',
			'ConsentGateEditor',
			array(
				'hash'        => self::HASH,
				'label'       => __( 'Cookie settings', 'wiedenroth-technologies-consentgate' ),
				'description' => __( 'Opens the Wiedenroth Technologies ConsentGate banner again.', 'wiedenroth-technologies-consentgate' ),
			)
		);
	}

	/**
	 * Mark classic menu links that open the banner.
	 *
	 * @param mixed $atts Link attributes.
	 * @param mixed $item Menu item.
	 * @return array<string, mixed>
	 */
	public function nav_menu_link_attributes( $atts, $item ): array {
		if ( ! is_array( $atts ) || ! is_object( $item ) ) {
			return is_array( $atts ) ? $atts : array();
		}
		$href = isset( $atts['href'] ) ? (string) $atts['href'] : '';
		if ( ! self::is_open_url( $href ) ) {
			return $atts;
		}

		$class         = isset( $atts['class'] ) ? (string) $atts['class'] : '';
		$atts['class'] = trim( $class . ' consentgate-open' );

		return $atts;
	}

	/**
	 * Mark Navigation-block links that open the banner.
	 *
	 * @param mixed $block_content Rendered HTML.
	 * @param mixed $block         Block.
	 */
	public function render_navigation_link( $block_content, $block ): string {
		if ( ! is_string( $block_content ) ) {
			return '';
		}
		if ( ! is_array( $block ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}
		$url = (string) ( $block['attrs']['url'] ?? '' );
		if ( ! self::is_open_url( $url ) || '' === $block_content ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag( 'a' ) ) {
			$processor->add_class( 'consentgate-open' );
		}

		return $processor->get_updated_html();
	}
}
