<?php
/**
 * WP Consent API bridge.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_WP_Consent_API
 */
class ConsentGate_WP_Consent_API {

	/**
	 * Register hooks.
	 *
	 * @param ConsentGate_Loader $loader Loader.
	 */
	public function register( ConsentGate_Loader $loader ): void {
		$loader->add_filter( 'wp_consent_api_registered_' . CONSENTGATE_PLUGIN_BASENAME, $this, 'register_cmp' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'declare_consent_type', 0 );
	}

	/**
	 * Register as CMP when WP Consent API is present.
	 */
	public function register_cmp(): bool {
		return true;
	}

	/**
	 * Tell WP Consent API this site uses opt-in.
	 */
	public function declare_consent_type(): void {
		if ( ! function_exists( 'wp_set_consent' ) ) {
			return;
		}

		if ( function_exists( 'wp_set_consent_type' ) ) {
			wp_set_consent_type( 'optin' );
		}
	}
}
