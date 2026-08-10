<?php
/**
 * Google reCAPTCHA v2 / v3 integration.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads reCAPTCHA on forms and verifies tokens server-side.
 */
class KMFB_Recaptcha {

	/**
	 * @var KMFB_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param KMFB_Settings $settings Settings service.
	 */
	public function __construct( KMFB_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Global reCAPTCHA settings slice.
	 *
	 * @return array<string, mixed>
	 */
	public function get_config() {
		$global = $this->settings->get();

		return array(
			'version'    => $this->normalize_version( $global['recaptcha_version'] ?? '' ),
			'site_key'   => sanitize_text_field( $global['recaptcha_site_key'] ?? '' ),
			'secret_key' => sanitize_text_field( $global['recaptcha_secret_key'] ?? '' ),
			'v3_score'   => $this->normalize_v3_score( $global['recaptcha_v3_score'] ?? 0.5 ),
		);
	}

	/**
	 * Whether site + secret keys and version are configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		$config = $this->get_config();

		return '' !== $config['version']
			&& '' !== $config['site_key']
			&& '' !== $config['secret_key'];
	}

	/**
	 * Whether reCAPTCHA should render for a form.
	 *
	 * @param array<string, mixed> $form Form package.
	 * @return bool
	 */
	public function is_active_for_form( $form ) {
		if ( ! $this->is_configured() ) {
			return false;
		}

		return ! empty( $form['settings']['enable_recaptcha'] );
	}

	/**
	 * Enqueue Google reCAPTCHA script for a form.
	 *
	 * @param array<string, mixed> $form Form package.
	 */
	public function enqueue_for_form( $form ) {
		if ( ! $this->is_active_for_form( $form ) ) {
			return;
		}

		$config   = $this->get_config();
		$site_key = $config['site_key'];

		if ( 'v3' === $config['version'] ) {
			wp_enqueue_script(
				'google-recaptcha-v3',
				'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ),
				array(),
				KMFB_VERSION,
				true
			);
			return;
		}

		wp_enqueue_script(
			'google-recaptcha-v2',
			'https://www.google.com/recaptcha/api.js',
			array(),
			KMFB_VERSION,
			true
		);
	}

	/**
	 * Verify a token with Google.
	 *
	 * @param string $token     Client token.
	 * @param string $remote_ip Visitor IP.
	 * @return true|WP_Error
	 */
	public function verify( $token, $remote_ip = '' ) {
		if ( ! $this->is_configured() ) {
			return true;
		}

		$token = sanitize_text_field( (string) $token );
		if ( '' === $token ) {
			return new WP_Error(
				'kmfb_recaptcha',
				$this->settings->message( 'recaptcha_required' )
			);
		}

		$config = $this->get_config();
		$body   = array(
			'secret'   => $config['secret_key'],
			'response' => $token,
		);

		if ( $remote_ip ) {
			$body['remoteip'] = $remote_ip;
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 15,
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'kmfb_recaptcha',
				$this->settings->message( 'recaptcha_failed' )
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			return new WP_Error(
				'kmfb_recaptcha',
				$this->settings->message( 'recaptcha_failed' )
			);
		}

		if ( 'v3' === $config['version'] ) {
			$score = isset( $data['score'] ) ? (float) $data['score'] : 0.0;
			if ( $score < $config['v3_score'] ) {
				return new WP_Error(
					'kmfb_recaptcha',
					$this->settings->message( 'recaptcha_failed' )
				);
			}
		}

		return true;
	}

	/**
	 * Normalize version string.
	 *
	 * @param mixed $version Raw version.
	 * @return string
	 */
	private function normalize_version( $version ) {
		$version = strtolower( trim( (string) $version ) );
		return in_array( $version, array( 'v2', 'v3' ), true ) ? $version : '';
	}

	/**
	 * Clamp v3 score threshold.
	 *
	 * @param mixed $score Raw score.
	 * @return float
	 */
	private function normalize_v3_score( $score ) {
		$score = (float) $score;
		if ( $score < 0.1 ) {
			return 0.1;
		}
		if ( $score > 0.9 ) {
			return 0.9;
		}
		return $score;
	}
}
