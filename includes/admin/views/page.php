<?php
/**
 * Main settings page shell with tabs.
 *
 * @package ConsentGate
 *
 * @var ConsentGate_Admin_Ui          $ui
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var array<string,mixed>  $options
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template included from ConsentGate_Admin::render_page().

$product_url = CONSENTGATE_PRODUCT_URL;
?>
<div class="wrap <?php echo esc_attr( $ui->cls( 'app' ) ); ?>">
	<div class="wp-header-end"></div>
	<header class="<?php echo esc_attr( $ui->cls( 'header' ) ); ?>">
		<div class="<?php echo esc_attr( $ui->cls( 'header__titles' ) ); ?>">
			<img
				class="<?php echo esc_attr( $ui->cls( 'header__logo' ) ); ?>"
				src="<?php echo esc_url( CONSENTGATE_PLUGIN_URL . 'assets/images/icon.svg' ); ?>"
				alt=""
				width="50"
				height="50"
				decoding="async"
			/>
			<div class="<?php echo esc_attr( $ui->cls( 'header__copy' ) ); ?>">
				<h1 class="<?php echo esc_attr( $ui->cls( 'title' ) ); ?>"><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<p class="<?php echo esc_attr( $ui->cls( 'tagline' ) ); ?>">
					<?php esc_html_e( 'Consent for trackers and cookies — banner, scan, and log on one site.', 'wiedenroth-technologies-consentgate' ); ?>
				</p>
			</div>
		</div>
		<div class="<?php echo esc_attr( $ui->cls( 'header__actions' ) ); ?>">
			<?php $ui->inbox(); ?>
			<?php $ui->button( __( 'Documentation', 'wiedenroth-technologies-consentgate' ), 'secondary', 'button', $product_url ); ?>
		</div>
	</header>

	<?php
	$scan_notice = get_option( 'consentgate_scan_notice' );
	if ( is_array( $scan_notice ) && ( ! empty( $scan_notice['added'] ) || ! empty( $scan_notice['removed'] ) ) ) :
		$dismiss = wp_nonce_url(
			admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=' . $tab . '&cg_dismiss_scan=1' ),
			'consentgate_dismiss_scan'
		);
		?>
		<div class="<?php echo esc_attr( $ui->cls( 'notice', 'warning' ) ); ?>" role="status">
			<p class="<?php echo esc_attr( $ui->cls( 'notice__title' ) ); ?>"><?php esc_html_e( 'Weekly check found a change', 'wiedenroth-technologies-consentgate' ); ?></p>
			<p class="<?php echo esc_attr( $ui->cls( 'notice__text' ) ); ?>">
				<?php esc_html_e( 'Nothing was changed. Review the services when you have a moment.', 'wiedenroth-technologies-consentgate' ); ?>
				<?php if ( ! empty( $scan_notice['added'] ) && is_array( $scan_notice['added'] ) ) : ?>
					<?php
					echo ' ';
					echo esc_html(
						sprintf(
							/* translators: %s: comma-separated service names */
							__( 'New: %s.', 'wiedenroth-technologies-consentgate' ),
							implode( ', ', array_map( 'strval', $scan_notice['added'] ) )
						)
					);
					?>
				<?php endif; ?>
				<?php if ( ! empty( $scan_notice['removed'] ) && is_array( $scan_notice['removed'] ) ) : ?>
					<?php
					echo ' ';
					echo esc_html(
						sprintf(
							/* translators: %s: comma-separated service names */
							__( 'No longer found: %s.', 'wiedenroth-technologies-consentgate' ),
							implode( ', ', array_map( 'strval', $scan_notice['removed'] ) )
						)
					);
					?>
				<?php endif; ?>
				<a href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'wiedenroth-technologies-consentgate' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( count( $tabs ) > 1 ) : ?>
	<nav class="<?php echo esc_attr( $ui->cls( 'tabs' ) ); ?>" aria-label="<?php esc_attr_e( 'Wiedenroth Technologies ConsentGate sections', 'wiedenroth-technologies-consentgate' ); ?>">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a
				class="<?php echo esc_attr( $ui->cls( 'tab', $tab === $slug ? 'active' : '' ) ); ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=' . $slug ) ); ?>"
				<?php echo $tab === $slug ? 'aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php endif; ?>

	<div class="<?php echo esc_attr( $ui->cls( 'content' ) ); ?>">
		<?php
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ui->notice( 'success', __( 'Settings saved.', 'wiedenroth-technologies-consentgate' ) );
		}

		$view = CONSENTGATE_PLUGIN_DIR . 'includes/admin/views/tabs/' . $tab . '.php';
		if ( is_readable( $view ) ) {
			include $view;
		} else {
			$ui->notice( 'error', __( 'Section not found.', 'wiedenroth-technologies-consentgate' ) );
		}
		?>
	</div>
</div>
