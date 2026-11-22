<?php
/**
 * Dashboard tab.
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

$stats   = ConsentGate_Logs::stats( 30 );
$policy  = ConsentGate_Policy_Scan::run();
$enabled = ! empty( $options['enabled'] );
$scan_at = ConsentGate_Options::last_scan_at();
$count   = ConsentGate_Services::count_included();
$woo     = ConsentGate_WooCommerce::is_active();

$banner_status = $enabled ? 'active' : 'inactive';
$scan_status   = '' === $scan_at ? 'warning' : 'active';
$policy_status = empty( $policy['missing'] ) && ! empty( $policy['found'] ) ? 'active' : 'warning';
?>

<section class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php
	$ui->heading(
		__( 'Status', 'wiedenroth-technologies-consentgate' ),
		__( 'Green = fine. Yellow = please check. Red = banner is off.', 'wiedenroth-technologies-consentgate' ),
		__( 'Scan the site under “Services” first, then style the banner.', 'wiedenroth-technologies-consentgate' )
	);
	?>

	<div class="<?php echo esc_attr( $ui->cls( 'stats' ) ); ?>">
		<?php
		ConsentGate_Admin::stat( $ui, (string) $stats['total'], __( 'Consents (30 days)', 'wiedenroth-technologies-consentgate' ), 'clipboard' );
		ConsentGate_Admin::stat( $ui, (string) $stats['accept_all'], __( 'Accepted all', 'wiedenroth-technologies-consentgate' ), 'check', 'ok' );
		ConsentGate_Admin::stat( $ui, (string) $stats['reject_all'], __( 'Rejected all', 'wiedenroth-technologies-consentgate' ), 'x', 'off' );
		ConsentGate_Admin::stat( $ui, (string) $count, __( 'Services in the banner', 'wiedenroth-technologies-consentgate' ), 'puzzle', 'accent' );
		?>
	</div>
</section>

<section class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Checks', 'wiedenroth-technologies-consentgate' ), __( 'A quick look at whether the banner, the scan, and the privacy policy match.', 'wiedenroth-technologies-consentgate' ) ); ?>

	<div class="<?php echo esc_attr( $ui->cls( 'tiles' ) ); ?>">
		<div class="<?php echo esc_attr( $ui->cls( 'tile' ) ); ?>">
			<div class="<?php echo esc_attr( $ui->cls( 'tile__head' ) ); ?>">
				<strong><?php esc_html_e( 'Cookie banner', 'wiedenroth-technologies-consentgate' ); ?></strong>
				<?php ConsentGate_Admin::status_badge( $ui, $banner_status ); ?>
			</div>
			<p><?php echo $enabled ? esc_html__( 'The banner is on. Non-essential scripts wait for consent.', 'wiedenroth-technologies-consentgate' ) : esc_html__( 'The banner is off.', 'wiedenroth-technologies-consentgate' ); ?></p>
		</div>
		<div class="<?php echo esc_attr( $ui->cls( 'tile' ) ); ?>">
			<div class="<?php echo esc_attr( $ui->cls( 'tile__head' ) ); ?>">
				<strong><?php esc_html_e( 'Site scan', 'wiedenroth-technologies-consentgate' ); ?></strong>
				<?php ConsentGate_Admin::status_badge( $ui, $scan_status ); ?>
			</div>
			<p>
				<?php
				if ( '' === $scan_at ) {
					esc_html_e( 'No scan yet. Scan the site under “Services”.', 'wiedenroth-technologies-consentgate' );
				} else {
					$scan_ts = strtotime( $scan_at );
					printf(
						/* translators: %s: datetime */
						esc_html__( 'Last scan: %s', 'wiedenroth-technologies-consentgate' ),
						esc_html( $scan_ts ? (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $scan_ts ) : $scan_at )
					);
				}
				?>
			</p>
		</div>
		<div class="<?php echo esc_attr( $ui->cls( 'tile' ) ); ?>">
			<div class="<?php echo esc_attr( $ui->cls( 'tile__head' ) ); ?>">
				<strong><?php esc_html_e( 'Privacy policy', 'wiedenroth-technologies-consentgate' ); ?></strong>
				<?php ConsentGate_Admin::status_badge( $ui, $policy_status ); ?>
			</div>
			<p>
				<?php
				if ( empty( $policy['url'] ) ) {
					esc_html_e( 'No privacy policy set. Choose a page under Banner, or set a privacy page in WordPress.', 'wiedenroth-technologies-consentgate' );
				} elseif ( ! empty( $policy['missing'] ) ) {
					printf(
						/* translators: %d: missing services */
						esc_html( _n( '%d detected service is missing from the policy.', '%d detected services are missing from the policy.', count( $policy['missing'] ), 'wiedenroth-technologies-consentgate' ) ),
						count( $policy['missing'] )
					);
				} else {
					esc_html_e( 'Detected services are mentioned in the privacy policy.', 'wiedenroth-technologies-consentgate' );
				}
				?>
			</p>
		</div>
		<div class="<?php echo esc_attr( $ui->cls( 'tile' ) ); ?>">
			<div class="<?php echo esc_attr( $ui->cls( 'tile__head' ) ); ?>">
				<strong><?php esc_html_e( 'WooCommerce', 'wiedenroth-technologies-consentgate' ); ?></strong>
				<?php ConsentGate_Admin::status_badge( $ui, $woo ? 'active' : 'inactive' ); ?>
			</div>
			<p><?php echo $woo ? esc_html__( 'Shop detected. Cart and checkout stay necessary.', 'wiedenroth-technologies-consentgate' ) : esc_html__( 'WooCommerce is not active. Wiedenroth Technologies ConsentGate works as a site CMP.', 'wiedenroth-technologies-consentgate' ); ?></p>
		</div>
	</div>
</section>
