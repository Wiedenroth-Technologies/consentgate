<?php
/**
 * Class map autoloader for ConsentGate.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Autoloader
 */
class ConsentGate_Autoloader {

	/**
	 * Class-to-file map.
	 *
	 * @var array<string, string>
	 */
	private static array $map = array(
		'ConsentGate'                    => 'includes/class-consentgate.php',
		'ConsentGate_Activator'          => 'includes/class-consentgate-activator.php',
		'ConsentGate_Admin_Ui'           => 'includes/class-consentgate-admin-ui.php',
		'ConsentGate_Deactivator'        => 'includes/class-consentgate-deactivator.php',
		'ConsentGate_Loader'             => 'includes/class-consentgate-loader.php',
		'ConsentGate_Options'            => 'includes/class-consentgate-options.php',
		'ConsentGate_Admin'              => 'includes/admin/class-consentgate-admin.php',
		'ConsentGate_Catalog'            => 'includes/class-consentgate-catalog.php',
		'ConsentGate_Widgets'            => 'includes/class-consentgate-widgets.php',
		'ConsentGate_Services'           => 'includes/class-consentgate-services.php',
		'ConsentGate_Scan'               => 'includes/class-consentgate-scan.php',
		'ConsentGate_Blocker'            => 'includes/class-consentgate-blocker.php',
		'ConsentGate_Frontend'           => 'includes/class-consentgate-frontend.php',
		'ConsentGate_Nav'                => 'includes/class-consentgate-nav.php',
		'ConsentGate_Logs'               => 'includes/class-consentgate-logs.php',
		'ConsentGate_REST'               => 'includes/class-consentgate-rest.php',
		'ConsentGate_Consent_Mode'       => 'includes/class-consentgate-consent-mode.php',
		'ConsentGate_WP_Consent_API'     => 'includes/class-consentgate-wp-consent-api.php',
		'ConsentGate_WooCommerce'        => 'includes/class-consentgate-woocommerce.php',
		'ConsentGate_Geo'                => 'includes/class-consentgate-geo.php',
		'ConsentGate_Policy_Scan'        => 'includes/class-consentgate-policy-scan.php',
		'ConsentGate_Privacy'            => 'includes/class-consentgate-privacy.php',
	);

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class Class name.
	 */
	public static function load( string $class ): void {
		if ( ! isset( self::$map[ $class ] ) ) {
			return;
		}

		$file = CONSENTGATE_PLUGIN_DIR . self::$map[ $class ];

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
