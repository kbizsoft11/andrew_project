<?php
/**
 * Spam protection utilities.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Honeypot and rate limiting.
 */
class KMFB_Spam {

	/**
	 * @var KMFB_Settings
	 */
	private $settings;

	/**
	 * @var KMFB_Recaptcha
	 */
	private $recaptcha;

	/**
	 * Constructor.
	 *
	 * @param KMFB_Settings  $settings  Settings.
	 * @param KMFB_Recaptcha $recaptcha reCAPTCHA service.
	 */
	public function __construct( KMFB_Settings $settings, KMFB_Recaptcha $recaptcha ) {
		$this->settings  = $settings;
		$this->recaptcha = $recaptcha;
	}

	/**
	 * Validate anti-spam checks.
	 *
	 * @param array<string, mixed> $form    Form config.
	 * @param array<string, mixed> $payload Submitted payload.
	 * @return true|WP_Error
	 */
	public function validate( $form, $payload ) {
		$settings = $form['settings'];

		if ( ! empty( $settings['enable_honeypot'] ) ) {
			$honeypot = isset( $payload['kmfb_hp'] ) ? trim( (string) $payload['kmfb_hp'] ) : '';
			if ( '' !== $honeypot ) {
				return new WP_Error( 'kmfb_spam', __( 'Spam detected.', 'kamboj-form-builder' ) );
			}

			$started = isset( $payload['kmfb_started'] ) ? (int) $payload['kmfb_started'] : 0;
			if ( $started > 0 && ( time() * 1000 - $started ) < 2000 ) {
				return new WP_Error( 'kmfb_spam', __( 'Please wait a moment before submitting.', 'kamboj-form-builder' ) );
			}
		}

		if ( ! empty( $settings['enable_rate_limit'] ) && ! $this->check_rate_limit( (int) $form['id'] ) ) {
			return new WP_Error( 'kmfb_rate_limit', __( 'Too many submissions. Please try again later.', 'kamboj-form-builder' ) );
		}

		if ( $this->recaptcha->is_active_for_form( $form ) ) {
			// Nonce verified in handler before spam checks.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token = isset( $payload['g-recaptcha-response'] ) ? (string) $payload['g-recaptcha-response'] : '';
			$check = $this->recaptcha->verify( $token, $this->get_client_ip() );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
		}

		return true;
	}

	/**
	 * Check and increment rate limit for IP + form.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	public function check_rate_limit( $form_id ) {
		$global     = $this->settings->get();
		$ip         = $this->get_client_ip();
		$key        = 'kmfb_rate_' . md5( $ip . '|' . $form_id );
		$count      = (int) get_transient( $key );
		$max_count  = (int) $global['rate_limit_count'];
		$window     = (int) $global['rate_limit_window'] * MINUTE_IN_SECONDS;

		if ( $count >= $max_count ) {
			return false;
		}

		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Get IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}
}
