<?php
/**
 * Plugin Name:       Wiedenroth Technologies ConsentGate
 * Plugin URI:        https://wiedenroth.tech/software/consentgate
 * Description:       Consent management for WordPress: cookie banner, script blocking, Google Consent Mode v2, service scan, and a consent log — including WooCommerce.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Wiedenroth Technologies GmbH
 * Author URI:        https://wiedenroth.tech
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wiedenroth-technologies-consentgate
 * Domain Path:       /languages
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Nicht in v1:
 * - IAB TCF
 * - Tracker-Proxy
 * - Multisite-Network-weites zentrales Management
 */

define( 'CONSENTGATE_VERSION', '1.0.1' );
define( 'CONSENTGATE_PLUGIN_FILE', __FILE__ );
define( 'CONSENTGATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONSENTGATE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CONSENTGATE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CONSENTGATE_SLUG', 'wiedenroth-technologies-consentgate' );
define( 'CONSENTGATE_OPTION_KEY', 'consentgate_settings' );
define( 'CONSENTGATE_PRODUCT_URL', 'https://wiedenroth.tech/software/consentgate' );
define( 'CONSENTGATE_DB_VERSION', '1.1.0' );

require_once CONSENTGATE_PLUGIN_DIR . 'includes/class-consentgate-autoloader.php';
ConsentGate_Autoloader::register();

/**
 * Plugin activation.
 */
function consentgate_activate(): void {
	require_once CONSENTGATE_PLUGIN_DIR . 'includes/class-consentgate-activator.php';
	ConsentGate_Activator::activate();
}
register_activation_hook( __FILE__, 'consentgate_activate' );

/**
 * Plugin deactivation.
 */
function consentgate_deactivate(): void {
	require_once CONSENTGATE_PLUGIN_DIR . 'includes/class-consentgate-deactivator.php';
	ConsentGate_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'consentgate_deactivate' );

/**
 * Bootstrap the plugin.
 */
function consentgate_run(): void {
	$plugin = ConsentGate::instance();
	$plugin->run();
}
consentgate_run();
