<?php
/**
 * Google Consent Mode v2 defaults (printed in wp_head).
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Consent_Mode
 */
class ConsentGate_Consent_Mode {

	/**
	 * Inline boot script: dataLayer + default denied + restore.
	 */
	public static function boot_script(): string {
		$wait = ConsentGate_Options::get( 'google_advanced_consent_mode' ) ? 2000 : 500;

		return '(function(){window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}window.gtag=window.gtag||gtag;gtag("consent","default",{ad_storage:"denied",ad_user_data:"denied",ad_personalization:"denied",analytics_storage:"denied",functionality_storage:"granted",personalization_storage:"denied",security_storage:"granted",wait_for_update:' . (int) $wait . '});gtag("set","ads_data_redaction",true);gtag("set","url_passthrough",' . ( ConsentGate_Options::get( 'url_passthrough' ) ? 'true' : 'false' ) . ');try{var raw=localStorage.getItem("consentgate.v1");if(!raw)return;var stored=JSON.parse(raw);if(!stored||!stored.categories)return;var c=stored.categories;var g=function(f){return f?"granted":"denied";};gtag("consent","update",{ad_storage:g(c.marketing),ad_user_data:g(c.marketing),ad_personalization:g(c.marketing),analytics_storage:g(c.statistics),functionality_storage:"granted",personalization_storage:g(c.preferences),security_storage:"granted"});if(stored.urlPassthrough===false)gtag("set","url_passthrough",false);window.__CONSENTGATE_RESTORED__=stored;}catch(e){}})();';
	}
}
