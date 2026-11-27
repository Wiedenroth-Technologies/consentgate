<?php
/**
 * Settings tab.
 *
 * @package ConsentGate
 *
 * @var ConsentGate_Admin_Ui         $ui
 * @var array<string,mixed> $options
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template included from ConsentGate_Admin::render_page().

$policy  = ConsentGate_Policy_Scan::run();
$snippet = ConsentGate_Policy_Scan::snippet( $policy['missing'] ?? array() );
?>
<form method="post" action="options.php">
	<?php settings_fields( 'consentgate_settings_group' ); ?>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php $ui->heading( __( 'General', 'wiedenroth-technologies-consentgate' ), __( 'Turn the banner on or off, and set how long consents are kept.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php
		ConsentGate_Admin::field_toggle(
			$ui,
			'enabled',
			$options['enabled'] ?? true,
			__( 'Cookie banner', 'wiedenroth-technologies-consentgate' ),
			__( 'Banner and script blocking are on', 'wiedenroth-technologies-consentgate' )
		);
		?>
		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'banner_language' ) ); ?>"><?php esc_html_e( 'Banner language', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'banner_language' ) ); ?>" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'banner_language' ) ); ?>">
					<option value="auto" <?php selected( $options['banner_language'] ?? 'auto', 'auto' ); ?>><?php esc_html_e( 'Site language', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="de" <?php selected( $options['banner_language'] ?? 'auto', 'de' ); ?>><?php esc_html_e( 'German', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="en" <?php selected( $options['banner_language'] ?? 'auto', 'en' ); ?>><?php esc_html_e( 'English', 'wiedenroth-technologies-consentgate' ); ?></option>
				</select>
				<?php $ui->help( __( 'German and English are both included. Site language follows WordPress. Pick German or English to keep the banner in that language.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>
		<?php
		ConsentGate_Admin::field_number(
			$ui,
			'retention_days',
			$options['retention_days'] ?? 365,
			__( 'Retention (days)', 'wiedenroth-technologies-consentgate' ),
			__( '30 to 2555 days. Older logs are deleted daily. Default: 365.', 'wiedenroth-technologies-consentgate' ),
			30,
			2555
		);
		?>
		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'region_mode' ) ); ?>"><?php esc_html_e( 'When should the banner appear?', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'region_mode' ) ); ?>" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'region_mode' ) ); ?>">
					<option value="always" <?php selected( $options['region_mode'], 'always' ); ?>><?php esc_html_e( 'Always', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="eu" <?php selected( $options['region_mode'], 'eu' ); ?>><?php esc_html_e( 'EU/EEA/UK/CH only (geo headers)', 'wiedenroth-technologies-consentgate' ); ?></option>
				</select>
				<?php $ui->help( __( '“EU only” needs trusted geo headers (Cloudflare or CloudFront). Without the option below, or without headers, the banner is shown to be safe.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>
		<?php
		ConsentGate_Admin::field_toggle(
			$ui,
			'trust_geo_headers',
			$options['trust_geo_headers'] ?? false,
			__( 'Geo headers', 'wiedenroth-technologies-consentgate' ),
			__( 'Trust Cloudflare/CloudFront country headers', 'wiedenroth-technologies-consentgate' ),
			__( 'Enable this only if the site sits behind Cloudflare or CloudFront. Unknown or unverified headers are ignored.', 'wiedenroth-technologies-consentgate' )
		);
		ConsentGate_Admin::field_toggle(
			$ui,
			'url_passthrough',
			$options['url_passthrough'] ?? true,
			__( 'URL-Passthrough', 'wiedenroth-technologies-consentgate' ),
			__( 'Pass Google click IDs in the URL (Consent Mode)', 'wiedenroth-technologies-consentgate' )
		);
		?>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php
		$ui->heading(
			__( 'External embeds', 'wiedenroth-technologies-consentgate' ),
			__( 'Unknown third-party iframes are blocked until marketing consent. Known services keep their own placeholders.', 'wiedenroth-technologies-consentgate' )
		);
		ConsentGate_Admin::field_toggle(
			$ui,
			'block_external_embeds',
			$options['block_external_embeds'] ?? true,
			__( 'Block unknown iframes', 'wiedenroth-technologies-consentgate' ),
			__( 'Block external iframes that are not in the service catalog', 'wiedenroth-technologies-consentgate' ),
			__( 'Same-origin iframes and embeds of this site are never blocked. Turn this off only if you accept third-party iframes without consent.', 'wiedenroth-technologies-consentgate' )
		);
		$allowlist = $options['external_embed_allowlist'] ?? array();
		if ( ! is_array( $allowlist ) ) {
			$allowlist = array();
		}
		?>
		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'external_embed_allowlist' ) ); ?>"><?php esc_html_e( 'Allowlisted hosts', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<textarea
					class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>"
					id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'external_embed_allowlist' ) ); ?>"
					name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'external_embed_allowlist' ) ); ?>"
					rows="4"
					placeholder="cdn.example.com&#10;player.example.org"
				><?php echo esc_textarea( implode( "\n", $allowlist ) ); ?></textarea>
				<?php $ui->help( __( 'One hostname per line (without https://). These external iframes load without consent.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php
		$ui->heading(
			__( 'Weekly check', 'wiedenroth-technologies-consentgate' ),
			__( 'Once a week Wiedenroth Technologies ConsentGate looks for new or missing services and emails you. It does not change categories, blocking, or the banner.', 'wiedenroth-technologies-consentgate' )
		);
		ConsentGate_Admin::field_toggle(
			$ui,
			'scan_weekly',
			$options['scan_weekly'] ?? true,
			__( 'Weekly scan', 'wiedenroth-technologies-consentgate' ),
			__( 'Check the public pages once a week', 'wiedenroth-technologies-consentgate' )
		);
		?>
		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'scan_notify_email' ) ); ?>"><?php esc_html_e( 'Notification email', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<?php ConsentGate_Admin::text( $ui, 'scan_notify_email', $options['scan_notify_email'] ?? '', 'email' ); ?>
				<?php
				$ui->help(
					sprintf(
						/* translators: %s: site admin email */
						__( 'Leave empty to use the site admin address (%s). A quiet week sends no email.', 'wiedenroth-technologies-consentgate' ),
						(string) get_option( 'admin_email' )
					)
				);
				$last_weekly = (string) ( $options['last_weekly_scan_at'] ?? '' );
				if ( '' !== $last_weekly ) {
					$ui->help(
						sprintf(
							/* translators: %s: localized date and time */
							__( 'Last check: %s', 'wiedenroth-technologies-consentgate' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_weekly ) )
						)
					);
				}
				?>
			</div>
		</div>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php $ui->heading( __( 'Privacy policy', 'wiedenroth-technologies-consentgate' ), __( 'Wiedenroth Technologies ConsentGate checks whether detected services are mentioned on the privacy page.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php if ( empty( $policy['url'] ) ) : ?>
			<?php $ui->notice( 'warning', __( 'No privacy policy found. Choose a page under Banner, or set a privacy page in WordPress.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php elseif ( ! empty( $policy['missing'] ) ) : ?>
			<?php $ui->notice( 'warning', __( 'Some services are missing from the policy. Copy the snippet below.', 'wiedenroth-technologies-consentgate' ) ); ?>
			<ul>
				<?php foreach ( $policy['missing'] as $row ) : ?>
					<li><?php echo esc_html( (string) $row['name'] ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( '' !== $snippet ) : ?>
				<pre class="<?php echo esc_attr( $ui->cls( 'snippet' ) ); ?>" data-wt-copy><?php echo esc_html( $snippet ); ?></pre>
			<?php endif; ?>
		<?php else : ?>
			<?php $ui->notice( 'success', __( 'Detected services are mentioned in the privacy policy.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php endif; ?>
	</div>

	<?php ConsentGate_Admin::save_actions( $ui ); ?>
</form>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php
	$ui->heading(
		__( 'Setup', 'wiedenroth-technologies-consentgate' ),
		__( 'Run the site scan and the “Support us” step again.', 'wiedenroth-technologies-consentgate' )
	);
	?>
	<div class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
		<?php ConsentGate_Admin::restart_setup_form( $ui ); ?>
	</div>
	<?php $ui->help( __( 'Scan results and banner texts are kept. You then choose again whether the ConsentGate note appears in the banner.', 'wiedenroth-technologies-consentgate' ) ); ?>
</div>
