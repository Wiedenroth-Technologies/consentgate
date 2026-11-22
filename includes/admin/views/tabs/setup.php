<?php
/**
 * First-run setup: scan, then optional support badge.
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

$scan_at  = ConsentGate_Options::last_scan_at();
$scanned  = '' !== $scan_at;
$services = ConsentGate_Services::all();
$count    = count( $services );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$step = isset( $_GET['step'] ) ? sanitize_key( (string) wp_unslash( $_GET['step'] ) ) : '';
if ( ! in_array( $step, array( 'scan', 'support' ), true ) ) {
	$step = $scanned ? 'support' : 'scan';
}
if ( 'support' === $step && ! $scanned ) {
	$step = 'scan';
}

$scan_url    = ConsentGate_Admin::setup_url( 'scan' );
$support_url = ConsentGate_Admin::setup_url( 'support' );
$icon_svg    = ConsentGate_Options::brand_icon_svg( 'cgSetup' );
?>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php
	$ui->heading(
		__( 'Setup', 'wiedenroth-technologies-consentgate' ),
		__( 'Scan the site first. Then choose whether the banner should mention us.', 'wiedenroth-technologies-consentgate' )
	);
	?>

	<ol class="<?php echo esc_attr( $ui->cls( 'steps' ) ); ?>">
		<li class="<?php echo esc_attr( $ui->cls( 'step', 'scan' === $step ? 'current' : ( $scanned ? 'done' : '' ) ) ); ?>">
			<span class="<?php echo esc_attr( $ui->cls( 'step__num' ) ); ?>">1</span>
			<span class="<?php echo esc_attr( $ui->cls( 'step__title' ) ); ?>"><?php esc_html_e( 'Scan site', 'wiedenroth-technologies-consentgate' ); ?></span>
		</li>
		<li class="<?php echo esc_attr( $ui->cls( 'step', 'support' === $step ? 'current' : '' ) ); ?>">
			<span class="<?php echo esc_attr( $ui->cls( 'step__num' ) ); ?>">2</span>
			<span class="<?php echo esc_attr( $ui->cls( 'step__title' ) ); ?>"><?php esc_html_e( 'Support us', 'wiedenroth-technologies-consentgate' ); ?></span>
		</li>
		<li class="<?php echo esc_attr( $ui->cls( 'step' ) ); ?>">
			<span class="<?php echo esc_attr( $ui->cls( 'step__num' ) ); ?>">3</span>
			<span class="<?php echo esc_attr( $ui->cls( 'step__title' ) ); ?>"><?php esc_html_e( 'Style the banner', 'wiedenroth-technologies-consentgate' ); ?></span>
		</li>
	</ol>
</div>

<?php if ( 'scan' === $step ) : ?>
	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php
		$ui->heading(
			__( 'Scan the site', 'wiedenroth-technologies-consentgate' ),
			__( 'Opens every public page. You see the page that is open right now and whether a service was found.', 'wiedenroth-technologies-consentgate' )
		);
		?>
		<p class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
			<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'primary' ) ); ?>" data-cg-scan data-cg-scan-next="<?php echo esc_url( $support_url ); ?>">
				<?php echo $scanned ? esc_html__( 'Scan again', 'wiedenroth-technologies-consentgate' ) : esc_html__( 'Scan site', 'wiedenroth-technologies-consentgate' ); ?>
			</button>
			<?php if ( $scanned ) : ?>
				<a class="<?php echo esc_attr( $ui->cls( 'btn', 'secondary' ) ); ?>" href="<?php echo esc_url( $support_url ); ?>">
					<?php esc_html_e( 'Next', 'wiedenroth-technologies-consentgate' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! ConsentGate_Options::setup_completed() ) : ?>
			<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'ghost' ) ); ?>" data-cg-setup data-cg-branding="0">
				<?php esc_html_e( 'Skip setup', 'wiedenroth-technologies-consentgate' ); ?>
			</button>
			<?php endif; ?>
		</p>
		<p class="<?php echo esc_attr( $ui->cls( 'field__help' ) ); ?>" data-cg-scan-status>
			<?php
			if ( $scanned ) {
				$scan_ts = strtotime( $scan_at );
				printf(
					/* translators: 1: datetime, 2: service count */
					esc_html__( 'Last scan: %1$s · %2$d services.', 'wiedenroth-technologies-consentgate' ),
					esc_html( $scan_ts ? (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $scan_ts ) : $scan_at ),
					(int) $count
				);
			} else {
				esc_html_e( 'No scan yet. It usually takes a few seconds.', 'wiedenroth-technologies-consentgate' );
			}
			?>
		</p>
		<div class="consentgate-scan" data-cg-scan-live hidden>
			<p class="consentgate-scan__intro" data-cg-scan-intro></p>
			<p class="consentgate-scan__current" data-cg-scan-current></p>
			<p class="consentgate-scan__meta" data-cg-scan-meta></p>
			<div class="consentgate-scan__track" aria-hidden="true"><span data-cg-scan-bar></span></div>
			<ol class="consentgate-scan__list" data-cg-scan-list></ol>
		</div>
	</div>
<?php else : ?>
	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php
		$ui->heading(
			__( 'Scan complete', 'wiedenroth-technologies-consentgate' ),
			sprintf(
				/* translators: %d: service count */
				_n( '%d service is included in the banner.', '%d services are included in the banner.', $count, 'wiedenroth-technologies-consentgate' ),
				$count
			)
		);
		?>
		<?php require CONSENTGATE_PLUGIN_DIR . 'includes/admin/views/partials/services-table.php'; ?>
		<p class="<?php echo esc_attr( $ui->cls( 'field__help' ) ); ?>">
			<?php esc_html_e( 'You can adjust categories afterwards under Services.', 'wiedenroth-technologies-consentgate' ); ?>
		</p>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php
		$ui->heading(
			__( 'Support us', 'wiedenroth-technologies-consentgate' ),
			__( 'Wiedenroth Technologies ConsentGate stays free to use. A small “ConsentGate” note in the banner is optional and helps us keep developing it.', 'wiedenroth-technologies-consentgate' )
		);
		?>
		<div class="consentgate-setup-brand">
			<p class="<?php echo esc_attr( $ui->cls( 'field__help' ) ); ?>"><?php esc_html_e( 'This is how the note looks:', 'wiedenroth-technologies-consentgate' ); ?></p>
			<a class="cg-brand" href="<?php echo esc_url( CONSENTGATE_PRODUCT_URL ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin SVG, gradient ids rewritten. ?>
				<span>ConsentGate</span>
			</a>
		</div>
		<?php $ui->help( __( 'Off by default. You can change it later under Banner.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<p class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
			<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'primary' ) ); ?>" data-cg-setup data-cg-branding="1">
				<?php esc_html_e( 'Show the note', 'wiedenroth-technologies-consentgate' ); ?>
			</button>
			<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'secondary' ) ); ?>" data-cg-setup data-cg-branding="0">
				<?php esc_html_e( 'Continue without the note', 'wiedenroth-technologies-consentgate' ); ?>
			</button>
			<a class="<?php echo esc_attr( $ui->cls( 'btn', 'ghost' ) ); ?>" href="<?php echo esc_url( $scan_url ); ?>">
				<?php esc_html_e( 'Back to the scan', 'wiedenroth-technologies-consentgate' ); ?>
			</a>
		</p>
	</div>
<?php endif; ?>
