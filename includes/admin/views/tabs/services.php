<?php
/**
 * Services / scan tab.
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

$services = ConsentGate_Services::all();
$cats     = array(
	'necessary'   => __( 'Necessary', 'wiedenroth-technologies-consentgate' ),
	'statistics'  => __( 'Statistics', 'wiedenroth-technologies-consentgate' ),
	'marketing'   => __( 'Marketing', 'wiedenroth-technologies-consentgate' ),
	'preferences' => __( 'Preferences', 'wiedenroth-technologies-consentgate' ),
);
?>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php
	$ui->heading(
		__( 'Scan the site', 'wiedenroth-technologies-consentgate' ),
		__( 'Opens every public page, plus saved content. You see which page is being checked and what was found there.', 'wiedenroth-technologies-consentgate' )
	);
	?>
	<p class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
		<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'primary' ) ); ?>" data-cg-scan>
			<?php esc_html_e( 'Scan site', 'wiedenroth-technologies-consentgate' ); ?>
		</button>
	</p>
	<p class="<?php echo esc_attr( $ui->cls( 'field__help' ) ); ?>" data-cg-scan-status>
		<?php
		$scan_at = ConsentGate_Options::last_scan_at();
		if ( '' !== $scan_at ) {
			$scan_ts = strtotime( $scan_at );
			printf(
				/* translators: %s: datetime */
				esc_html__( 'Last scan: %s', 'wiedenroth-technologies-consentgate' ),
				esc_html( $scan_ts ? (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $scan_ts ) : $scan_at )
			);
		} else {
			esc_html_e( 'No scan yet.', 'wiedenroth-technologies-consentgate' );
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

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Detected services', 'wiedenroth-technologies-consentgate' ), __( 'Set the category and loading policy. Locked entries (WordPress, WooCommerce) stay necessary.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<?php $ui->help( __( '“Load immediately” skips consent. Use it only for services that are truly necessary.', 'wiedenroth-technologies-consentgate' ) ); ?>

	<?php require CONSENTGATE_PLUGIN_DIR . 'includes/admin/views/partials/services-table.php'; ?>
</div>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php $ui->heading( __( 'Add a service manually', 'wiedenroth-technologies-consentgate' ), __( 'For scripts the scan does not recognize.', 'wiedenroth-technologies-consentgate' ) ); ?>
	<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
		<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="cg-manual-name"><?php esc_html_e( 'Name', 'wiedenroth-technologies-consentgate' ); ?></label>
		<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
			<input type="text" class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-manual-name" />
		</div>
	</div>
	<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
		<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="cg-manual-host"><?php esc_html_e( 'Host or URL', 'wiedenroth-technologies-consentgate' ); ?></label>
		<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
			<input type="text" class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-manual-host" placeholder="example.com" />
		</div>
	</div>
	<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
		<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="cg-manual-cat"><?php esc_html_e( 'Category', 'wiedenroth-technologies-consentgate' ); ?></label>
		<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
			<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" id="cg-manual-cat">
				<?php foreach ( $cats as $value => $label ) : ?>
					<?php if ( 'necessary' === $value ) { continue; } ?>
					<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<p class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
		<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'secondary' ) ); ?>" data-cg-add-service>
			<?php esc_html_e( 'Add', 'wiedenroth-technologies-consentgate' ); ?>
		</button>
	</p>
</div>

<?php
$cta_href = add_query_arg(
	'url',
	home_url( '/' ),
	'https://wiedenroth.tech/tools/dsgvo-checker'
);
?>
<aside class="<?php echo esc_attr( $ui->cls( 'cta' ) ); ?>">
	<div class="<?php echo esc_attr( $ui->cls( 'cta__row' ) ); ?>">
		<div class="<?php echo esc_attr( $ui->cls( 'cta__copy' ) ); ?>">
			<p class="<?php echo esc_attr( $ui->cls( 'cta__badge' ) ); ?>">
				<span class="<?php echo esc_attr( $ui->cls( 'cta__badge-icon' ) ); ?>" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 5 3.4 8.4 7 9.5 3.6-1.1 7-4.5 7-9.5V6z"/><path d="M9.5 12.5 11 14l3.5-3.5"/></svg>
				</span>
				<?php esc_html_e( 'Wiedenroth Technologies privacy scan', 'wiedenroth-technologies-consentgate' ); ?>
			</p>
			<h2 class="<?php echo esc_attr( $ui->cls( 'cta__title' ) ); ?>"><?php esc_html_e( 'Scan this site from the outside', 'wiedenroth-technologies-consentgate' ); ?></h2>
			<p class="<?php echo esc_attr( $ui->cls( 'cta__lead' ) ); ?>"><?php esc_html_e( 'The scan above finds trackers and scripts on this server. The privacy checker inspects the site from the outside: cookies, consent, trackers, and visible privacy gaps.', 'wiedenroth-technologies-consentgate' ); ?></p>
		</div>
		<div class="<?php echo esc_attr( $ui->cls( 'cta__action' ) ); ?>">
			<a class="<?php echo esc_attr( $ui->cls( 'cta__go' ) ); ?>" href="<?php echo esc_url( $cta_href ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Check page', 'wiedenroth-technologies-consentgate' ); ?> →
			</a>
		</div>
	</div>
	<div class="<?php echo esc_attr( $ui->cls( 'cta__foot' ) ); ?>">
		<p class="<?php echo esc_attr( $ui->cls( 'cta__hint' ) ); ?>"><?php esc_html_e( 'The scan reads only publicly visible information. It is not legal advice and does not replace a privacy policy. The result is a technical first look.', 'wiedenroth-technologies-consentgate' ); ?></p>
		<p class="<?php echo esc_attr( $ui->cls( 'cta__note' ) ); ?>"><?php esc_html_e( 'Public pages only. Non-invasive, about 20–40 seconds.', 'wiedenroth-technologies-consentgate' ); ?></p>
	</div>
</aside>
