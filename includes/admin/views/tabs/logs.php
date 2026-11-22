<?php
/**
 * Consent logs tab.
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

$page   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$data   = ConsentGate_Logs::page( $page, 50, $search );
$total  = (int) $data['total'];
$pages  = max( 1, (int) ceil( $total / 50 ) );
$export = wp_nonce_url( admin_url( 'admin-post.php?action=consentgate_export_logs' ), 'consentgate_export_logs' );
$labels = array(
	'accept_all' => __( 'Accepted all', 'wiedenroth-technologies-consentgate' ),
	'reject_all' => __( 'Rejected all', 'wiedenroth-technologies-consentgate' ),
	'custom'     => __( 'Selection', 'wiedenroth-technologies-consentgate' ),
);
?>

<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
	<?php
	$ui->heading(
		__( 'Consents', 'wiedenroth-technologies-consentgate' ),
		__( 'Pseudonymous logs (visitor ID, decision, optional country). No IP addresses. Entries are not tied to a WordPress account.', 'wiedenroth-technologies-consentgate' )
	);
	?>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( CONSENTGATE_SLUG ); ?>" />
		<input type="hidden" name="tab" value="logs" />
		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="cg-log-search"><?php esc_html_e( 'Search', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<input type="search" class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-log-search" name="s" value="<?php echo esc_attr( $search ); ?>" />
			</div>
		</div>
		<p class="<?php echo esc_attr( $ui->cls( 'actions' ) ); ?>">
			<?php $ui->button( __( 'Filter', 'wiedenroth-technologies-consentgate' ), 'secondary', 'submit' ); ?>
			<?php $ui->button( __( 'Export CSV', 'wiedenroth-technologies-consentgate' ), 'ghost', 'button', $export ); ?>
		</p>
	</form>

	<?php if ( ! $data['rows'] ) : ?>
		<div class="<?php echo esc_attr( $ui->cls( 'empty' ) ); ?>">
			<p><?php esc_html_e( 'No consents stored yet.', 'wiedenroth-technologies-consentgate' ); ?></p>
		</div>
	<?php else : ?>
		<div class="<?php echo esc_attr( $ui->cls( 'table-wrap' ) ); ?>">
			<table class="<?php echo esc_attr( $ui->cls( 'table' ) ); ?>">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'wiedenroth-technologies-consentgate' ); ?></th>
						<th><?php esc_html_e( 'Decision', 'wiedenroth-technologies-consentgate' ); ?></th>
						<th><?php esc_html_e( 'Categories', 'wiedenroth-technologies-consentgate' ); ?></th>
						<th><?php esc_html_e( 'Language', 'wiedenroth-technologies-consentgate' ); ?></th>
						<th><?php esc_html_e( 'Country', 'wiedenroth-technologies-consentgate' ); ?></th>
						<th><?php esc_html_e( 'Visitor', 'wiedenroth-technologies-consentgate' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data['rows'] as $row ) : ?>
						<?php
						$cats = json_decode( (string) $row['categories'], true );
						$cats = is_array( $cats ) ? $cats : array();
						$on   = array();
						foreach ( array( 'statistics' => 'S', 'marketing' => 'M', 'preferences' => 'P' ) as $k => $short ) {
							if ( ! empty( $cats[ $k ] ) ) {
								$on[] = $short;
							}
						}
						?>
						<tr>
							<td><?php echo esc_html( (string) $row['created_at'] ); ?></td>
							<td><?php echo esc_html( $labels[ $row['decision'] ] ?? (string) $row['decision'] ); ?></td>
							<td><?php echo esc_html( $on ? implode( ', ', $on ) : '–' ); ?></td>
							<td><?php echo esc_html( (string) $row['locale'] ); ?></td>
							<td><?php echo esc_html( (string) ( $row['ip_country'] ?: '–' ) ); ?></td>
							<td><code><?php echo esc_html( (string) $row['visitor_id'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( $pages > 1 ) : ?>
			<p class="<?php echo esc_attr( $ui->cls( 'field__help' ) ); ?>">
				<?php
				printf(
					/* translators: 1: page, 2: pages, 3: total */
					esc_html__( 'Page %1$s of %2$s (%3$s entries)', 'wiedenroth-technologies-consentgate' ),
					esc_html( (string) $page ),
					esc_html( (string) $pages ),
					esc_html( (string) $total )
				);
				?>
				<?php if ( $page > 1 ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=logs&paged=' . ( $page - 1 ) . '&s=' . rawurlencode( $search ) ) ); ?>"><?php esc_html_e( 'Previous', 'wiedenroth-technologies-consentgate' ); ?></a>
				<?php endif; ?>
				<?php if ( $page < $pages ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=logs&paged=' . ( $page + 1 ) . '&s=' . rawurlencode( $search ) ) ); ?>"><?php esc_html_e( 'Next', 'wiedenroth-technologies-consentgate' ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
