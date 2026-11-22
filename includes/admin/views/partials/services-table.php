<?php
/**
 * Detected-services table, shared by Services and Setup.
 *
 * @package ConsentGate
 *
 * @var ConsentGate_Admin_Ui $ui
 * @var array<int, array<string, mixed>> $services
 * @var array<string, mixed> $options
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template included from an admin view.

$google_advanced = (bool) ( $options['google_advanced_consent_mode'] ?? false );
$has_google_ads  = false;
$cats            = array(
	'necessary'   => __( 'Necessary', 'wiedenroth-technologies-consentgate' ),
	'statistics'  => __( 'Statistics', 'wiedenroth-technologies-consentgate' ),
	'marketing'   => __( 'Marketing', 'wiedenroth-technologies-consentgate' ),
	'preferences' => __( 'Preferences', 'wiedenroth-technologies-consentgate' ),
);

foreach ( $services as $service_row ) {
	if ( 'google_ads' === ( $service_row['service_key'] ?? '' ) ) {
		$has_google_ads = true;
		break;
	}
}
?>

<?php if ( ! $services ) : ?>
	<div class="<?php echo esc_attr( $ui->cls( 'empty' ) ); ?>">
		<p><?php esc_html_e( 'No services yet. Scan the site.', 'wiedenroth-technologies-consentgate' ); ?></p>
	</div>
<?php else : ?>
	<div class="<?php echo esc_attr( $ui->cls( 'table-wrap' ) ); ?>">
		<table class="<?php echo esc_attr( $ui->cls( 'table' ) ); ?>">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Service', 'wiedenroth-technologies-consentgate' ); ?></th>
					<th><?php esc_html_e( 'Source', 'wiedenroth-technologies-consentgate' ); ?></th>
					<th><?php esc_html_e( 'Category', 'wiedenroth-technologies-consentgate' ); ?></th>
					<th><?php esc_html_e( 'Load', 'wiedenroth-technologies-consentgate' ); ?></th>
					<th><?php esc_html_e( 'In the banner', 'wiedenroth-technologies-consentgate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $services as $row ) : ?>
					<?php
					$locked   = ! empty( $row['locked'] );
					$is_ads   = 'google_ads' === ( $row['service_key'] ?? '' );
					$found_on = ConsentGate_Services::locations( $row );
					$found_count = (int) $found_on['count'];
					$found_pages = $found_count > 3 ? array() : $found_on['pages'];
					$found_label = ConsentGate_Catalog::label( (string) $row['service_key'], (string) $row['name'], ConsentGate_Options::locale_from_tag( determine_locale() ) ?: 'en' );
					?>
					<tr data-cg-service="<?php echo esc_attr( (string) $row['service_key'] ); ?>">
						<td>
							<div class="<?php echo esc_attr( $ui->cls( 'service-name' ) ); ?>">
								<strong><?php echo esc_html( $found_label ); ?></strong>
								<button
									type="button"
									class="<?php echo esc_attr( $ui->cls( 'icon-btn' ) ); ?>"
									data-cg-found
									data-cg-found-name="<?php echo esc_attr( $found_label ); ?>"
									data-cg-found-count="<?php echo esc_attr( (string) $found_count ); ?>"
									data-cg-found-pages="<?php echo esc_attr( (string) wp_json_encode( $found_pages ) ); ?>"
									aria-label="<?php esc_attr_e( 'Where this was found', 'wiedenroth-technologies-consentgate' ); ?>"
								>
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
								</button>
								<?php if ( $is_ads ) : ?>
									<button
										type="button"
										class="<?php echo esc_attr( $ui->cls( 'icon-btn' ) ); ?>"
										data-cg-google-settings
										aria-label="<?php esc_attr_e( 'Google Consent Mode', 'wiedenroth-technologies-consentgate' ); ?>"
									>
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
									</button>
									<span data-cg-cm-badge <?php echo $google_advanced ? '' : 'hidden'; ?>>
										<?php $ui->badge( 'ok', 'CM v2' ); ?>
									</span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $row['evidence'] ) ) : ?>
								<div class="<?php echo esc_attr( $ui->cls( 'tech' ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $row['evidence'], 12 ) ); ?></div>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) $row['source'] ); ?></td>
						<td>
							<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" data-cg-field="category" <?php disabled( $locked ); ?>>
								<?php foreach ( $cats as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $row['category'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" data-cg-field="policy" <?php disabled( $locked ); ?>>
								<option value="with_consent" <?php selected( $row['policy'], 'with_consent' ); ?>><?php esc_html_e( 'Only after consent', 'wiedenroth-technologies-consentgate' ); ?></option>
								<option value="without_consent" <?php selected( $row['policy'], 'without_consent' ); ?>><?php esc_html_e( 'Load immediately', 'wiedenroth-technologies-consentgate' ); ?></option>
							</select>
						</td>
						<td>
							<input type="checkbox" data-cg-field="included" <?php checked( ! empty( $row['included'] ) ); ?> <?php disabled( $locked ); ?> />
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

<?php if ( $has_google_ads ) : ?>
	<dialog class="<?php echo esc_attr( $ui->cls( 'dialog' ) ); ?>" id="cg-google-consent" data-cg-google-modal>
		<div class="<?php echo esc_attr( $ui->cls( 'dialog__panel' ) ); ?>" data-cg-google-panel>
			<div class="<?php echo esc_attr( $ui->cls( 'dialog__head' ) ); ?>">
				<div>
					<h2 class="<?php echo esc_attr( $ui->cls( 'dialog__title' ) ); ?>"><?php esc_html_e( 'Google Consent Mode v2', 'wiedenroth-technologies-consentgate' ); ?></h2>
				</div>
				<button type="button" class="<?php echo esc_attr( $ui->cls( 'dialog__close' ) ); ?>" data-cg-google-close aria-label="<?php esc_attr_e( 'Close', 'wiedenroth-technologies-consentgate' ); ?>">&times;</button>
			</div>
			<div class="<?php echo esc_attr( $ui->cls( 'dialog__body' ) ); ?>">
				<p class="<?php echo esc_attr( $ui->cls( 'dialog__text' ) ); ?>">
					<?php esc_html_e( 'Default: Google loads only after consent. Advanced loads the tag earlier without ads cookies — Google receives only cookieless pings. After “Accept”, real conversions are sent to Ads. Legally disputed in Germany.', 'wiedenroth-technologies-consentgate' ); ?>
				</p>
				<?php
				$ui->toggle(
					'consentgate_google_advanced_draft',
					$google_advanced,
					__( 'Enable advanced mode', 'wiedenroth-technologies-consentgate' ),
					'cg-google-advanced'
				);
				$ui->help( __( 'Optional — only if you explicitly want this.', 'wiedenroth-technologies-consentgate' ) );
				?>
			</div>
			<div class="<?php echo esc_attr( $ui->cls( 'dialog__footer' ) ); ?>">
				<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'primary' ) ); ?>" data-cg-google-done>
					<?php esc_html_e( 'Done', 'wiedenroth-technologies-consentgate' ); ?>
				</button>
			</div>
		</div>
	</dialog>
<?php endif; ?>

<?php if ( $services ) : ?>
	<dialog class="<?php echo esc_attr( $ui->cls( 'dialog' ) ); ?>" id="cg-found-on" data-cg-found-dialog>
		<div class="<?php echo esc_attr( $ui->cls( 'dialog__panel' ) ); ?>">
			<div class="<?php echo esc_attr( $ui->cls( 'dialog__head' ) ); ?>">
				<div>
					<h2 class="<?php echo esc_attr( $ui->cls( 'dialog__title' ) ); ?>" data-cg-found-title></h2>
				</div>
				<button type="button" class="<?php echo esc_attr( $ui->cls( 'dialog__close' ) ); ?>" data-cg-found-close aria-label="<?php esc_attr_e( 'Close', 'wiedenroth-technologies-consentgate' ); ?>">&times;</button>
			</div>
			<div class="<?php echo esc_attr( $ui->cls( 'dialog__body' ) ); ?>">
				<p class="<?php echo esc_attr( $ui->cls( 'dialog__text' ) ); ?>" data-cg-found-summary></p>
				<ul class="consentgate-found-list" data-cg-found-list hidden></ul>
			</div>
		</div>
	</dialog>
<?php endif; ?>
