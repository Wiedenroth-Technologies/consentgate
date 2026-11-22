<?php
/**
 * REST endpoint for consent logs.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_REST
 */
class ConsentGate_REST {

	/**
	 * Max POSTs per hashed IP in the rate-limit window.
	 */
	private const RATE_MAX = 20;

	/**
	 * Rate-limit window in seconds.
	 */
	private const RATE_WINDOW = 600;

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			'consentgate/v1',
			'/consent',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'log_consent' ),
				'permission_callback' => array( $this, 'can_log' ),
			)
		);
	}

	/**
	 * Require the storefront REST nonce and apply a per-IP hash rate limit.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function can_log( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'consentgate_forbidden',
				__( 'Invalid request.', 'wiedenroth-technologies-consentgate' ),
				array( 'status' => 403 )
			);
		}

		if ( $this->rate_limited() ) {
			return new WP_Error(
				'consentgate_rate_limited',
				__( 'Too many requests. Please try again later.', 'wiedenroth-technologies-consentgate' ),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Store a consent decision.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function log_consent( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_params();
		}

		$ok = ConsentGate_Logs::insert( is_array( $params ) ? $params : array() );

		return new WP_REST_Response(
			array(
				'ok' => $ok,
			),
			$ok ? 200 : 400
		);
	}

	/**
	 * Whether this hashed client exceeded the log rate.
	 */
	private function rate_limited(): bool {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'consentgate_rl_' . md5( $ip . wp_salt( 'nonce' ) );
		$n   = (int) get_transient( $key );
		if ( $n >= self::RATE_MAX ) {
			return true;
		}
		set_transient( $key, $n + 1, self::RATE_WINDOW );

		return false;
	}
}
