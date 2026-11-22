<?php
/**
 * Help tab.
 *
 * @package ConsentGate
 *
 * @var ConsentGate_Admin_Ui $ui
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php
	$ui->heading(
		__( 'How Wiedenroth Technologies ConsentGate works', 'wiedenroth-technologies-consentgate' ),
		__( 'Non-essential scripts load only after explicit consent. Google Consent Mode v2 stays on.', 'wiedenroth-technologies-consentgate' )
	);
	?>
	<ol class="<?php echo esc_attr( $ui->cls( 'steps' ) ); ?>">
		<li class="<?php echo esc_attr( $ui->cls( 'step' ) ); ?>"><?php esc_html_e( 'The first time: setup with a site scan, then optional “Support us”.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li class="<?php echo esc_attr( $ui->cls( 'step' ) ); ?>"><?php esc_html_e( 'Check categories under Services.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li class="<?php echo esc_attr( $ui->cls( 'step' ) ); ?>"><?php esc_html_e( 'Style the banner and save.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li class="<?php echo esc_attr( $ui->cls( 'step' ) ); ?>"><?php esc_html_e( 'Add detected services to the privacy policy.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li class="<?php echo esc_attr( $ui->cls( 'step' ) ); ?>"><?php esc_html_e( 'Mark your own theme scripts with the gate pattern.', 'wiedenroth-technologies-consentgate' ); ?></li>
	</ol>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Categories', 'wiedenroth-technologies-consentgate' ), __( 'Necessary is always on. The other categories follow consent.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<ul>
		<li><strong><?php esc_html_e( 'Necessary', 'wiedenroth-technologies-consentgate' ); ?></strong> — <?php esc_html_e( 'Session, security, consent, WooCommerce cart.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li><strong><?php esc_html_e( 'Statistics', 'wiedenroth-technologies-consentgate' ); ?></strong> — <?php esc_html_e( 'for example Google Analytics, Matomo, Clarity.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li><strong><?php esc_html_e( 'Marketing', 'wiedenroth-technologies-consentgate' ); ?></strong> — <?php esc_html_e( 'for example Google Ads, Meta Pixel, TikTok.', 'wiedenroth-technologies-consentgate' ); ?></li>
		<li><strong><?php esc_html_e( 'Preferences', 'wiedenroth-technologies-consentgate' ); ?></strong> — <?php esc_html_e( 'optional comfort settings.', 'wiedenroth-technologies-consentgate' ); ?></li>
	</ul>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Gate theme scripts', 'wiedenroth-technologies-consentgate' ), __( 'For pixels that do not come from plugins.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<pre class="<?php echo esc_attr( $ui->cls( 'snippet' ) ); ?>" data-wt-copy>&lt;script type="text/plain" data-consent-gate="marketing" src="https://example.com/pixel.js"&gt;&lt;/script&gt;

&lt;script type="text/plain" data-consent-gate="statistics"&gt;
  // bisheriges Inline-Snippet
&lt;/script&gt;</pre>
	<?php $ui->help( __( 'Categories: necessary, statistics, marketing, preferences.', 'wiedenroth-technologies-consentgate' ) ); ?>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Reopen the banner', 'wiedenroth-technologies-consentgate' ), __( 'Cookie icon or menu link.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<p><?php esc_html_e( 'The cookie icon at the bottom of the screen can be turned off under Banner. Add “Cookie settings” to a WordPress menu instead: under Appearance → Menus (ConsentGate box) or in the Navigation block in the site editor.', 'wiedenroth-technologies-consentgate' ); ?></p>
	<p><?php esc_html_e( 'A custom link to #consentgate also opens the banner.', 'wiedenroth-technologies-consentgate' ); ?></p>
	<pre class="<?php echo esc_attr( $ui->cls( 'snippet' ) ); ?>" data-wt-copy>&lt;a href="#consentgate"&gt;Cookie-Einstellungen&lt;/a&gt;</pre>
	<p><?php esc_html_e( 'Public API: window.ConsentGate.open(), acceptAll(), rejectAll().', 'wiedenroth-technologies-consentgate' ); ?></p>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'WP Consent API', 'wiedenroth-technologies-consentgate' ), __( 'Other plugins can read the same categories.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<p><?php esc_html_e( 'Wiedenroth Technologies ConsentGate sets functional, statistics, marketing, and preferences (opt-in). If the “WP Consent API” plugin is active, Wiedenroth Technologies ConsentGate registers as a CMP.', 'wiedenroth-technologies-consentgate' ); ?></p>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Note', 'wiedenroth-technologies-consentgate' ), __( 'A technical tool, not legal advice.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<p><?php esc_html_e( 'Wiedenroth Technologies ConsentGate implements technical measures (banner, blocking, log). It is not legal advice and does not replace a reviewed privacy policy. IAB TCF is not included.', 'wiedenroth-technologies-consentgate' ); ?></p>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Setup', 'wiedenroth-technologies-consentgate' ), __( 'Run the site scan and the “Support us” step again.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<p><?php esc_html_e( 'Scan results and banner texts stay in place. You then choose again whether the ConsentGate note appears in the banner.', 'wiedenroth-technologies-consentgate' ); ?></p>
	<div class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
		<?php ConsentGate_Admin::restart_setup_form( $ui ); ?>
	</div>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'WooCommerce', 'wiedenroth-technologies-consentgate' ), __( 'Shop features stay usable.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<p><?php esc_html_e( 'Cart, checkout, and session cookies are not blocked. Trackers from Woo extensions (Meta, Google Ads, Klaviyo, and others) follow consent.', 'wiedenroth-technologies-consentgate' ); ?></p>
</div>
