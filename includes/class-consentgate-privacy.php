<?php
/**
 * WordPress privacy policy text, exporter and eraser.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Privacy
 */
class ConsentGate_Privacy {

	/**
	 * Register hooks.
	 *
	 * @param ConsentGate_Loader $loader Loader.
	 */
	public function register( ConsentGate_Loader $loader ): void {
		$loader->add_action( 'admin_init', $this, 'register_policy_content' );
		$loader->add_filter( 'wp_privacy_personal_data_exporters', $this, 'register_exporter' );
		$loader->add_filter( 'wp_privacy_personal_data_erasers', $this, 'register_eraser' );
	}

	/**
	 * Suggested privacy-policy paragraph.
	 */
	public function register_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'This website uses Wiedenroth Technologies ConsentGate to ask for consent to cookies and similar technologies. The technically necessary record of your choice is stored in the browser (localStorage). Non-essential services load only after explicit consent.', 'wiedenroth-technologies-consentgate' ) . '</p>';
		$content .= '<p>' . esc_html__( 'For accountability, Wiedenroth Technologies ConsentGate stores a server-side log: a pseudonymous visitor ID from the browser, the decision (accept all / reject all / selection), the chosen categories, the banner version, the language, and optionally a country from trusted geo headers. IP addresses are not stored. The site owner sets the retention period in the Wiedenroth Technologies ConsentGate settings.', 'wiedenroth-technologies-consentgate' ) . '</p>';
		$content .= '<p>' . esc_html__( 'The logs are not tied to a WordPress user account or an email address. An export or erasure through the WordPress privacy tools therefore usually returns no Wiedenroth Technologies ConsentGate records for an email.', 'wiedenroth-technologies-consentgate' ) . '</p>';

		wp_add_privacy_policy_content( 'Wiedenroth Technologies ConsentGate', wp_kses_post( $content ) );
	}

	/**
	 * @param array<string, mixed> $exporters Exporters.
	 * @return array<string, mixed>
	 */
	public function register_exporter( array $exporters ): array {
		$exporters['consentgate'] = array(
			'exporter_friendly_name' => __( 'Wiedenroth Technologies ConsentGate', 'wiedenroth-technologies-consentgate' ),
			'callback'                  => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * @param array<string, mixed> $erasers Erasers.
	 * @return array<string, mixed>
	 */
	public function register_eraser( array $erasers ): array {
		$erasers['consentgate'] = array(
			'eraser_friendly_name' => __( 'Wiedenroth Technologies ConsentGate', 'wiedenroth-technologies-consentgate' ),
			'callback'               => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Logs are not keyed by e-mail.
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array{data: array<int, mixed>, done: bool}
	 */
	public function export( string $email, int $page = 1 ): array {
		unset( $email, $page );

		return array(
			'data' => array(
				array(
					'group_id'    => 'consentgate',
					'group_label' => __( 'Wiedenroth Technologies ConsentGate', 'wiedenroth-technologies-consentgate' ),
					'item_id'     => 'consentgate-notice',
					'data'        => array(
						array(
							'name'  => __( 'Note', 'wiedenroth-technologies-consentgate' ),
							'value' => __( 'Consent logs are not tied to this email address. There is no exportable Wiedenroth Technologies ConsentGate data for this account.', 'wiedenroth-technologies-consentgate' ),
						),
					),
				),
			),
			'done' => true,
		);
	}

	/**
	 * Nothing to delete by e-mail.
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
	 */
	public function erase( string $email, int $page = 1 ): array {
		unset( $email, $page );

		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(
				__( 'Wiedenroth Technologies ConsentGate does not store consents under this email. No logs were deleted.', 'wiedenroth-technologies-consentgate' ),
			),
			'done'           => true,
		);
	}
}
