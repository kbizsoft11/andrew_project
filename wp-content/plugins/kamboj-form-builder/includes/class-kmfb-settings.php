<?php
/**
 * Global plugin settings.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide Kamboj Form Builder settings.
 */
class KMFB_Settings {

	const OPTION_KEY = 'kmfb_settings';

	/**
	 * Default validation messages.
	 *
	 * @return array<string, string>
	 */
	public function default_messages() {
		return array(
			'validation_summary' => __( 'Please correct the errors below.', 'kamboj-form-builder' ),
			'required'           => __( 'This field is required.', 'kamboj-form-builder' ),
			'invalid_email'      => __( 'Please enter a valid email address.', 'kamboj-form-builder' ),
			'invalid_phone'      => __( 'Please enter a valid phone number.', 'kamboj-form-builder' ),
			'consent_required'   => __( 'You must agree before submitting.', 'kamboj-form-builder' ),
			'file_required'      => __( 'Please choose a file to upload.', 'kamboj-form-builder' ),
			'file_failed'        => __( 'File upload failed. Please try again.', 'kamboj-form-builder' ),
			'file_size'          => __( 'File is too large.', 'kamboj-form-builder' ),
			'file_type'          => __( 'File type is not allowed.', 'kamboj-form-builder' ),
			'generic_error'      => __( 'Something went wrong. Please try again.', 'kamboj-form-builder' ),
			'recaptcha_required' => __( 'Please complete the reCAPTCHA challenge.', 'kamboj-form-builder' ),
			'recaptcha_failed'   => __( 'reCAPTCHA verification failed. Please try again.', 'kamboj-form-builder' ),
		);
	}

	/**
	 * Defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults() {
		return array(
			'rate_limit_count'   => 5,
			'rate_limit_window'  => 10,
			'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png,webp',
			'max_file_size_mb'   => 5,
			'from_name'          => get_bloginfo( 'name' ),
			'from_email'         => get_option( 'admin_email' ),
			'use_custom_from'    => false,
			'delete_data_on_uninstall' => false,
			'recaptcha_version'        => '',
			'recaptcha_site_key'       => '',
			'recaptcha_secret_key'     => '',
			'recaptcha_v3_score'       => 0.5,
			'messages'           => $this->default_messages(),
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = wp_parse_args( $stored, $this->defaults() );
		$settings['messages'] = wp_parse_args(
			is_array( $settings['messages'] ?? null ) ? $settings['messages'] : array(),
			$this->default_messages()
		);

		return $settings;
	}

	/**
	 * Get a validation message by key.
	 *
	 * @param string $key Message key.
	 * @return string
	 */
	public function message( $key ) {
		$settings = $this->get();
		$messages = $settings['messages'];

		if ( ! empty( $messages[ $key ] ) ) {
			return (string) $messages[ $key ];
		}

		$defaults = $this->default_messages();
		return $defaults[ $key ] ?? '';
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'kmfb_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$messages = array();
		if ( ! empty( $input['messages'] ) && is_array( $input['messages'] ) ) {
			foreach ( $this->default_messages() as $key => $default ) {
				$messages[ $key ] = sanitize_text_field( $input['messages'][ $key ] ?? $default );
			}
		} else {
			$messages = $this->default_messages();
		}

		return array(
			'rate_limit_count'   => max( 1, (int) ( $input['rate_limit_count'] ?? 5 ) ),
			'rate_limit_window'  => max( 1, (int) ( $input['rate_limit_window'] ?? 10 ) ),
			'allowed_file_types' => sanitize_text_field( $input['allowed_file_types'] ?? 'pdf,jpg,png' ),
			'max_file_size_mb'   => max( 1, (int) ( $input['max_file_size_mb'] ?? 5 ) ),
			'from_name'          => sanitize_text_field( $input['from_name'] ?? '' ),
			'from_email'         => sanitize_email( $input['from_email'] ?? '' ),
			'use_custom_from'    => ! empty( $input['use_custom_from'] ),
			'delete_data_on_uninstall' => ! empty( $input['delete_data_on_uninstall'] ),
			'recaptcha_version'    => in_array( $input['recaptcha_version'] ?? '', array( '', 'v2', 'v3' ), true )
				? (string) ( $input['recaptcha_version'] ?? '' )
				: '',
			'recaptcha_site_key'   => sanitize_text_field( $input['recaptcha_site_key'] ?? '' ),
			'recaptcha_secret_key' => $this->sanitize_secret_key( $input['recaptcha_secret_key'] ?? '', $stored['recaptcha_secret_key'] ?? '' ),
			'recaptcha_v3_score'   => min( 0.9, max( 0.1, (float) ( $input['recaptcha_v3_score'] ?? 0.5 ) ) ),
			'messages'           => $messages,
		);
	}

	/**
	 * Allowed file extensions.
	 *
	 * @return string[]
	 */
	public function allowed_extensions() {
		$settings = $this->get();
		$parts    = array_map( 'trim', explode( ',', strtolower( $settings['allowed_file_types'] ) ) );
		return array_values( array_filter( $parts ) );
	}

	/**
	 * Preserve secret key when the password field is left blank on save.
	 *
	 * @param mixed  $submitted Submitted value.
	 * @param string $existing  Stored value.
	 * @return string
	 */
	private function sanitize_secret_key( $submitted, $existing ) {
		$submitted = sanitize_text_field( (string) $submitted );
		if ( '' === $submitted && '' !== (string) $existing ) {
			return sanitize_text_field( (string) $existing );
		}
		return $submitted;
	}
}
