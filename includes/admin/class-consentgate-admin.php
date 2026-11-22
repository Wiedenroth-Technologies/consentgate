<?php
/**
 * Admin menu, Settings API, scan/export AJAX.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Admin
 */
class ConsentGate_Admin {

	/**
	 * Shared UI helper.
	 *
	 * @var ConsentGate_Admin_Ui
	 */
	private ConsentGate_Admin_Ui $ui;

	/**
	 * @param ConsentGate_Admin_Ui $ui UI helper.
	 */
	public function __construct( ConsentGate_Admin_Ui $ui ) {
		$this->ui = $ui;
	}

	/**
	 * Add top-level menu.
	 */
	public function add_menu(): void {
		ConsentGate_Admin_Ui::ensure_menu_separator();

		add_menu_page(
			'Wiedenroth Technologies ConsentGate',
			'ConsentGate',
			'manage_options',
			CONSENTGATE_SLUG,
			array( $this, 'render_page' ),
			$this->menu_icon(),
			ConsentGate_Admin_Ui::MENU_POSITION
		);
	}

	/**
	 * Base64 menu icon.
	 */
	private function menu_icon(): string {
		$path = CONSENTGATE_PLUGIN_DIR . 'assets/images/icon-menu.svg';
		if ( ! is_readable( $path ) ) {
			return 'dashicons-lock';
		}

		$svg = file_get_contents( $path );
		if ( ! is_string( $svg ) || '' === $svg ) {
			return 'dashicons-lock';
		}

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Register Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'consentgate_settings_group',
			CONSENTGATE_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'ConsentGate_Options', 'sanitize' ),
				'default'           => ConsentGate_Options::defaults(),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param mixed $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ): void {
		if ( ! is_string( $hook ) || ! str_contains( $hook, CONSENTGATE_SLUG ) ) {
			return;
		}

		$this->ui->enqueue();
		$this->ui->enqueue_extra_css( CONSENTGATE_PLUGIN_DIR . 'assets/css/consentgate-admin.css' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) wp_unslash( $_GET['tab'] ) ) : 'overview';
		if ( 'banner' === $tab ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'consentgate-banner',
			CONSENTGATE_PLUGIN_URL . 'assets/css/consent-gate.css',
			array(),
			$this->asset_version( 'assets/css/consent-gate.css' )
		);

		wp_enqueue_script(
			'consentgate-admin',
			CONSENTGATE_PLUGIN_URL . 'assets/js/admin.js',
			array( $this->ui->handle() ),
			$this->asset_version( 'assets/js/admin.js' ),
			true
		);

		wp_localize_script(
			'consentgate-admin',
			'ConsentGateAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'consentgate_admin' ),
				'i18n'    => array(
					'scanning'   => __( 'Checking your pages…', 'wiedenroth-technologies-consentgate' ),
					'scanned'    => __( 'Scan finished.', 'wiedenroth-technologies-consentgate' ),
					'error'      => __( 'Scan failed. Please try again.', 'wiedenroth-technologies-consentgate' ),
					'scanIntro'  => __( 'We open each public page and note external services that actually load there.', 'wiedenroth-technologies-consentgate' ),
					'scanNow'    => __( 'Now looking at', 'wiedenroth-technologies-consentgate' ),
					/* translators: 1: current page number, 2: total pages. */
					'scanPage'   => __( 'Page %1$d of %2$d', 'wiedenroth-technologies-consentgate' ),
					'scanClear'  => __( 'Nothing external on this page', 'wiedenroth-technologies-consentgate' ),
					'scanFound'  => __( 'Found', 'wiedenroth-technologies-consentgate' ),
					'scanSaved'  => __( 'Saved content', 'wiedenroth-technologies-consentgate' ),
					'saving'    => __( 'Saving…', 'wiedenroth-technologies-consentgate' ),
					'saved'     => __( 'Service saved.', 'wiedenroth-technologies-consentgate' ),
					'added'     => __( 'Service added.', 'wiedenroth-technologies-consentgate' ),
					'logoTitle'  => __( 'Choose banner logo', 'wiedenroth-technologies-consentgate' ),
					'logoUse'    => __( 'Use logo', 'wiedenroth-technologies-consentgate' ),
					'logoChange' => __( 'Change logo', 'wiedenroth-technologies-consentgate' ),
					'logoChoose' => __( 'Choose from Media Library', 'wiedenroth-technologies-consentgate' ),
					'foundOne'   => __( 'Found on 1 page.', 'wiedenroth-technologies-consentgate' ),
					/* translators: %d: number of pages. */
					'foundMany'  => __( 'Found on %d pages.', 'wiedenroth-technologies-consentgate' ),
					'foundNone'  => __( 'No public page recorded yet. Run a scan to refresh this.', 'wiedenroth-technologies-consentgate' ),
				),
			)
		);
	}

	/**
	 * Plugin action links.
	 *
	 * @param array<int, string> $links Links.
	 * @return array<int, string>
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'admin.php?page=' . CONSENTGATE_SLUG );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wiedenroth-technologies-consentgate' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Wiedenroth-Footer.
	 */
	public function render_wp_footer_brand(): void {
		if ( ! $this->ui->is_own_screen() ) {
			return;
		}

		$product_url = CONSENTGATE_PRODUCT_URL;
		$impressum   = 'https://wiedenroth.tech/impressum';
		$datenschutz = 'https://wiedenroth.tech/software/consentgate/datenschutz';
		$brand_url   = 'https://wiedenroth.tech';
		$brand_img   = '';

		$avif = CONSENTGATE_PLUGIN_DIR . 'assets/images/wiedenroth-technologies.avif';
		$png  = CONSENTGATE_PLUGIN_DIR . 'assets/images/wiedenroth-technologies.png';

		if ( is_readable( $avif ) ) {
			$brand_img = CONSENTGATE_PLUGIN_URL . 'assets/images/wiedenroth-technologies.avif';
		} elseif ( is_readable( $png ) ) {
			$brand_img = CONSENTGATE_PLUGIN_URL . 'assets/images/wiedenroth-technologies.png';
		}

		$this->ui->footer(
			array(
				__( 'Legal notice', 'wiedenroth-technologies-consentgate' )   => $impressum,
				__( 'Privacy', 'wiedenroth-technologies-consentgate' ) => $datenschutz,
				'Wiedenroth Technologies ConsentGate'                   => $product_url,
			),
			$brand_url,
			$brand_img
		);
	}

	/**
	 * Render settings page with tabs.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		ConsentGate_Options::flush_cache();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['cg_dismiss_scan'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), 'consentgate_dismiss_scan' ) ) {
			delete_option( 'consentgate_scan_notice' );
			wp_safe_redirect( remove_query_arg( array( 'cg_dismiss_scan', '_wpnonce' ) ) );
			exit;
		}

		$setup_done = ConsentGate_Options::setup_completed();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) wp_unslash( $_GET['tab'] ) ) : 'overview';

		$tabs = array(
			'overview' => __( 'Overview', 'wiedenroth-technologies-consentgate' ),
			'services' => __( 'Services', 'wiedenroth-technologies-consentgate' ),
			'banner'   => __( 'Banner', 'wiedenroth-technologies-consentgate' ),
			'logs'     => __( 'Consents', 'wiedenroth-technologies-consentgate' ),
			'settings' => __( 'Settings', 'wiedenroth-technologies-consentgate' ),
			'help'     => __( 'Help', 'wiedenroth-technologies-consentgate' ),
		);

		if ( ! $setup_done ) {
			$tab  = 'setup';
			$tabs = array(
				'setup' => __( 'Setup', 'wiedenroth-technologies-consentgate' ),
			);
		} elseif ( 'setup' === $tab ) {
			$tabs = array( 'setup' => __( 'Setup', 'wiedenroth-technologies-consentgate' ) ) + $tabs;
		} elseif ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'overview';
		}

		$ui      = $this->ui;
		$options = ConsentGate_Options::all();

		include CONSENTGATE_PLUGIN_DIR . 'includes/admin/views/page.php';
	}

	/**
	 * After activation, open the first-run setup.
	 */
	public function maybe_redirect_setup(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! get_transient( 'consentgate_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'consentgate_activation_redirect' );

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( ConsentGate_Options::setup_completed() ) {
			return;
		}

		wp_safe_redirect( self::setup_url( 'scan' ) );
		exit;
	}

	/**
	 * Setup screen URL.
	 *
	 * @param string $step scan|support.
	 */
	public static function setup_url( string $step = 'scan' ): string {
		$step = in_array( $step, array( 'scan', 'support' ), true ) ? $step : 'scan';

		return admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=setup&step=' . $step );
	}

	/**
	 * Nonce URL to restart onboarding.
	 */
	public static function restart_setup_url(): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=consentgate_restart_setup' ),
			'consentgate_restart_setup'
		);
	}

	/**
	 * POST form to restart onboarding.
	 */
	public static function restart_setup_form( ConsentGate_Admin_Ui $ui, string $variant = 'secondary' ): void {
		printf(
			'<form method="post" action="%s">',
			esc_url( admin_url( 'admin-post.php' ) )
		);
		echo '<input type="hidden" name="action" value="consentgate_restart_setup" />';
		wp_nonce_field( 'consentgate_restart_setup' );
		$ui->button( __( 'Restart setup', 'wiedenroth-technologies-consentgate' ), $variant, 'submit' );
		echo '</form>';
	}

	/**
	 * Restart first-run setup.
	 */
	public function handle_restart_setup(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) );
		}

		check_admin_referer( 'consentgate_restart_setup' );
		ConsentGate_Options::reset_setup();
		wp_safe_redirect( self::setup_url( 'scan' ) );
		exit;
	}

	/**
	 * AJAX: finish setup and store branding opt-in.
	 */
	public function ajax_complete_setup(): void {
		check_ajax_referer( 'consentgate_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) ), 403 );
		}

		$show = ! empty( $_POST['show_branding'] );
		ConsentGate_Options::update(
			array(
				'show_branding' => $show,
			)
		);
		ConsentGate_Options::mark_setup_completed();

		$next = $show || '' !== ConsentGate_Options::last_scan_at()
			? admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=banner' )
			: admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=overview' );

		wp_send_json_success(
			array(
				'redirect' => $next,
			)
		);
	}

	/**
	 * AJAX: run site scan.
	 */
	public function ajax_scan(): void {
		check_ajax_referer( 'consentgate_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) ), 403 );
		}

		$phase = isset( $_POST['phase'] ) ? sanitize_key( (string) wp_unslash( $_POST['phase'] ) ) : '';

		try {
			if ( 'start' === $phase ) {
				wp_send_json_success( ConsentGate_Scan::begin() );
			}
			if ( 'step' === $phase ) {
				wp_send_json_success( ConsentGate_Scan::advance() );
			}
			$result = ConsentGate_Scan::run();
		} catch ( \Throwable $e ) {
			ConsentGate_Options::mark_scanned();
			wp_send_json_error(
				array(
					'message' => __( 'Scan incomplete. Please try again.', 'wiedenroth-technologies-consentgate' ),
				),
				500
			);
		}
		wp_send_json_success(
			array(
				'count'    => $result['count'],
				'message'  => sprintf(
					/* translators: %d: number of services */
					_n( '%d service detected.', '%d services detected.', (int) $result['count'], 'wiedenroth-technologies-consentgate' ),
					(int) $result['count']
				),
			)
		);
	}

	/**
	 * AJAX: save one service assignment.
	 */
	public function ajax_save_service(): void {
		check_ajax_referer( 'consentgate_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) ), 403 );
		}

		$key      = sanitize_key( (string) wp_unslash( $_POST['service_key'] ?? '' ) );
		$category = sanitize_key( (string) wp_unslash( $_POST['category'] ?? 'marketing' ) );
		$policy   = sanitize_key( (string) wp_unslash( $_POST['policy'] ?? 'with_consent' ) );
		$included = ! empty( $_POST['included'] );

		$ok = ConsentGate_Services::save_assignment( $key, $category, $policy, $included );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Service not found.', 'wiedenroth-technologies-consentgate' ) ), 404 );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: add a manual service.
	 */
	public function ajax_add_service(): void {
		check_ajax_referer( 'consentgate_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) ), 403 );
		}

		$name     = sanitize_text_field( (string) wp_unslash( $_POST['name'] ?? '' ) );
		$host     = sanitize_text_field( (string) wp_unslash( $_POST['host'] ?? '' ) );
		$category = sanitize_key( (string) wp_unslash( $_POST['category'] ?? 'marketing' ) );

		$ok = ConsentGate_Services::add_manual( $name, $host, $category );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a name.', 'wiedenroth-technologies-consentgate' ) ), 400 );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Google Consent Mode v2 (Advanced).
	 */
	public function ajax_save_google_consent(): void {
		check_ajax_referer( 'consentgate_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) ), 403 );
		}

		$enabled = ! empty( $_POST['enabled'] );
		ConsentGate_Options::update(
			array(
				'google_advanced_consent_mode' => $enabled,
			)
		);

		wp_send_json_success( array( 'enabled' => $enabled ) );
	}

	/**
	 * CSV export.
	 */
	public function handle_export_logs(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'wiedenroth-technologies-consentgate' ) );
		}

		check_admin_referer( 'consentgate_export_logs' );
		ConsentGate_Logs::export_csv();
	}

	/**
	 * Settings API input name.
	 *
	 * @param string $name Option key.
	 */
	public static function option_name( string $name ): string {
		return CONSENTGATE_OPTION_KEY . '[' . $name . ']';
	}

	/**
	 * Stable HTML id.
	 *
	 * @param string $name Option key.
	 */
	public static function field_id( string $name ): string {
		return 'consentgate-' . str_replace( array( '_', '[', ']' ), array( '-', '-', '' ), sanitize_key( str_replace( array( '[', ']' ), array( '-', '' ), $name ) ) );
	}

	/**
	 * Toggle with hidden 0.
	 *
	 * @param ConsentGate_Admin_Ui $ui      UI helper.
	 * @param string      $name    Option key.
	 * @param mixed       $checked Current value.
	 * @param string      $label   Visible label.
	 */
	public static function toggle( ConsentGate_Admin_Ui $ui, string $name, mixed $checked, string $label ): void {
		printf(
			'<input type="hidden" name="%s" value="0" />',
			esc_attr( self::option_name( $name ) )
		);
		$ui->toggle( self::option_name( $name ), (bool) $checked, $label, self::field_id( $name ) );
	}

	/**
	 * Field row with toggle.
	 *
	 * @param ConsentGate_Admin_Ui $ui           UI helper.
	 * @param string      $name         Option key.
	 * @param mixed       $checked      Current value.
	 * @param string      $label        Field label.
	 * @param string      $toggle_label Toggle text.
	 * @param string      $help         Optional help.
	 */
	public static function field_toggle( ConsentGate_Admin_Ui $ui, string $name, mixed $checked, string $label, string $toggle_label, string $help = '' ): void {
		echo '<div class="' . esc_attr( $ui->cls( 'field' ) ) . '">';
		echo '<div class="' . esc_attr( $ui->cls( 'field__label' ) ) . '">' . esc_html( $label ) . '</div>';
		echo '<div class="' . esc_attr( $ui->cls( 'field__control' ) ) . '">';
		self::toggle( $ui, $name, $checked, $toggle_label );
		if ( '' !== $help ) {
			$ui->help( $help );
		}
		echo '</div></div>';
	}

	/**
	 * Number input.
	 *
	 * @param ConsentGate_Admin_Ui $ui    UI helper.
	 * @param string      $name  Option key.
	 * @param mixed       $value Value.
	 * @param int         $min   Min.
	 * @param int         $max   Max.
	 */
	public static function number( ConsentGate_Admin_Ui $ui, string $name, mixed $value, int $min = 0, int $max = 999999 ): void {
		printf(
			'<input type="number" class="%1$s" id="%2$s" name="%3$s" value="%4$s" min="%5$s" max="%6$s" />',
			esc_attr( $ui->cls( 'input', 'narrow' ) ),
			esc_attr( self::field_id( $name ) ),
			esc_attr( self::option_name( $name ) ),
			esc_attr( (string) (int) $value ),
			esc_attr( (string) $min ),
			esc_attr( (string) $max )
		);
	}

	/**
	 * Text input.
	 *
	 * @param ConsentGate_Admin_Ui $ui    UI helper.
	 * @param string      $name  Option key.
	 * @param mixed       $value Value.
	 * @param string      $type  Input type.
	 */
	public static function text( ConsentGate_Admin_Ui $ui, string $name, mixed $value, string $type = 'text' ): void {
		printf(
			'<input type="%1$s" class="%2$s" id="%3$s" name="%4$s" value="%5$s" />',
			esc_attr( $type ),
			esc_attr( $ui->cls( 'input' ) ),
			esc_attr( self::field_id( $name ) ),
			esc_attr( self::option_name( $name ) ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Color input.
	 *
	 * @param ConsentGate_Admin_Ui $ui    UI helper.
	 * @param string      $name  Option key.
	 * @param mixed       $value Value.
	 */
	public static function color( ConsentGate_Admin_Ui $ui, string $name, mixed $value ): void {
		printf(
			'<input type="color" class="%1$s" id="%2$s" name="%3$s" value="%4$s" data-cg-color="%5$s" />',
			esc_attr( $ui->cls( 'input', 'narrow' ) ),
			esc_attr( self::field_id( $name ) ),
			esc_attr( self::option_name( $name ) ),
			esc_attr( (string) $value ),
			esc_attr( $name )
		);
	}

	/**
	 * Page dropdown for Settings API options.
	 *
	 * @param ConsentGate_Admin_Ui $ui       UI helper.
	 * @param string      $name     Option key.
	 * @param int         $selected Selected page ID.
	 */
	public static function page_dropdown( ConsentGate_Admin_Ui $ui, string $name, int $selected ): void {
		$html = wp_dropdown_pages(
			array(
				'name'              => esc_attr( self::option_name( $name ) ),
				'id'                => esc_attr( self::field_id( $name ) ),
				'class'             => esc_attr( $ui->cls( 'select' ) ),
				'echo'              => 0,
				'show_option_none'  => esc_html__( '— Select a page —', 'wiedenroth-technologies-consentgate' ),
				'option_none_value' => '0',
				'selected'          => (int) $selected,
				'post_status'       => array( 'publish', 'private' ),
			)
		);

		if ( ! is_string( $html ) || '' === trim( $html ) ) {
			printf(
				'<input type="hidden" name="%s" value="0" />',
				esc_attr( self::option_name( $name ) )
			);
			$ui->help( __( 'No pages yet. Create a privacy policy page first.', 'wiedenroth-technologies-consentgate' ) );
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Field row with number input.
	 *
	 * @param ConsentGate_Admin_Ui $ui    UI helper.
	 * @param string      $name  Option key.
	 * @param mixed       $value Value.
	 * @param string      $label Label.
	 * @param string      $help  Help text.
	 * @param int         $min   Min.
	 * @param int         $max   Max.
	 */
	public static function field_number( ConsentGate_Admin_Ui $ui, string $name, mixed $value, string $label, string $help, int $min, int $max ): void {
		echo '<div class="' . esc_attr( $ui->cls( 'field' ) ) . '">';
		echo '<label class="' . esc_attr( $ui->cls( 'field__label' ) ) . '" for="' . esc_attr( self::field_id( $name ) ) . '">' . esc_html( $label ) . '</label>';
		echo '<div class="' . esc_attr( $ui->cls( 'field__control' ) ) . '">';
		self::number( $ui, $name, $value, $min, $max );
		if ( '' !== $help ) {
			$ui->help( $help );
		}
		echo '</div></div>';
	}

	/**
	 * Submit row.
	 *
	 * @param ConsentGate_Admin_Ui $ui    UI helper.
	 * @param string      $label Button label.
	 */
	public static function save_actions( ConsentGate_Admin_Ui $ui, string $label = '' ): void {
		if ( '' === $label ) {
			$label = __( 'Save settings', 'wiedenroth-technologies-consentgate' );
		}

		echo '<p class="' . esc_attr( $ui->cls( 'actions' ) ) . '">';
		$ui->button( $label, 'primary', 'submit' );
		echo '</p>';
	}

	/**
	 * Status badge helper.
	 *
	 * @param ConsentGate_Admin_Ui $ui     UI helper.
	 * @param string      $status active|warning|inactive.
	 */
	public static function status_badge( ConsentGate_Admin_Ui $ui, string $status ): void {
		$map = array(
			'active'   => array( 'ok', __( 'Active', 'wiedenroth-technologies-consentgate' ) ),
			'warning'  => array( 'warn', __( 'Check', 'wiedenroth-technologies-consentgate' ) ),
			'inactive' => array( 'off', __( 'Off', 'wiedenroth-technologies-consentgate' ) ),
		);

		$pair = $map[ $status ] ?? array( 'off', $status );
		$ui->badge( $pair[0], $pair[1] );
	}

	/**
	 * Dashboard statistic with icon.
	 *
	 * @param ConsentGate_Admin_Ui $ui    UI helper.
	 * @param string      $value Display value.
	 * @param string      $label Label.
	 * @param string      $icon  clipboard|check|x|puzzle.
	 * @param string      $tone  Optional: ok|off|accent.
	 */
	public static function stat( ConsentGate_Admin_Ui $ui, string $value, string $label, string $icon, string $tone = '' ): void {
		echo '<div class="' . esc_attr( $ui->cls( 'stat', $tone ) ) . '">';
		echo '<span class="' . esc_attr( $ui->cls( 'stat__icon' ) ) . '" aria-hidden="true">' . self::stat_svg( $icon ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from this class.
		echo '<div class="' . esc_attr( $ui->cls( 'stat__body' ) ) . '">';
		echo '<p class="' . esc_attr( $ui->cls( 'stat__label' ) ) . '">' . esc_html( $label ) . '</p>';
		echo '<p class="' . esc_attr( $ui->cls( 'stat__value' ) ) . '">' . esc_html( $value ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Stroke icon for a dashboard stat.
	 *
	 * @param string $icon Icon key.
	 */
	private static function stat_svg( string $icon ): string {
		$paths = array(
			'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h4"/>',
			'check'     => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
			'x'         => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
			'puzzle'    => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
		);

		$inner = $paths[ $icon ] ?? $paths['clipboard'];

		return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
	}

	/**
	 * Cache-bust changed assets without a plugin version bump.
	 *
	 * @param string $relative Path inside the plugin.
	 */
	private function asset_version( string $relative ): string {
		$path  = CONSENTGATE_PLUGIN_DIR . ltrim( $relative, '/' );
		$mtime = is_readable( $path ) ? filemtime( $path ) : false;

		return false !== $mtime ? (string) $mtime : CONSENTGATE_VERSION;
	}
}
