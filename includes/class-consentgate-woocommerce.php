<?php
/**
 * WooCommerce compatibility: never block essential shop scripts/cookies.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_WooCommerce
 */
class ConsentGate_WooCommerce {

	/**
	 * Script handles that must never be rewritten.
	 *
	 * @var array<int, string>
	 */
	private const ESSENTIAL_HANDLES = array(
		'woocommerce',
		'wc-add-to-cart',
		'wc-cart-fragments',
		'wc-checkout',
		'wc-single-product',
		'wc-credit-card-form',
		'wc-address-i18n',
		'wc-country-select',
		'wc-password-strength-meter',
		'selectWoo',
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'underscore',
		'wp-util',
	);

	/**
	 * Register hooks.
	 *
	 * @param ConsentGate_Loader $loader Loader.
	 */
	public function register( ConsentGate_Loader $loader ): void {
		if ( ! self::is_active() ) {
			return;
		}

		$loader->add_filter( 'consentgate_essential_script_handles', $this, 'essential_handles' );
	}

	/**
	 * WooCommerce active?
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce', false ) || in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Merge essential handles.
	 *
	 * @param array<int, string> $handles Handles.
	 * @return array<int, string>
	 */
	public function essential_handles( array $handles ): array {
		return array_values( array_unique( array_merge( $handles, self::ESSENTIAL_HANDLES ) ) );
	}

	/**
	 * Default essential handles (always includes WP core jquery).
	 *
	 * @return array<int, string>
	 */
	public static function default_handles(): array {
		$handles = array( 'jquery', 'jquery-core', 'jquery-migrate', 'wp-embed' );
		if ( self::is_active() ) {
			$handles = array_merge( $handles, self::ESSENTIAL_HANDLES );
		}

		return array_values( array_unique( $handles ) );
	}
}
