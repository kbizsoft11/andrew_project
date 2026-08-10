<?php
/**
 * Form submission handler.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validates and processes submissions.
 */
class KMFB_Handler {

	/**
	 * @var KMFB_Form_CPT
	 */
	private $forms;

	/**
	 * @var KMFB_Submissions
	 */
	private $submissions;

	/**
	 * @var KMFB_Spam
	 */
	private $spam;

	/**
	 * @var KMFB_Mailer
	 */
	private $mailer;

	/**
	 * @var KMFB_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct( KMFB_Form_CPT $forms, KMFB_Submissions $submissions, KMFB_Spam $spam, KMFB_Mailer $mailer, KMFB_Settings $settings ) {
		$this->forms       = $forms;
		$this->submissions = $submissions;
		$this->spam        = $spam;
		$this->mailer      = $mailer;
		$this->settings    = $settings;

		add_action( 'wp_ajax_kmfb_submit_form', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_kmfb_submit_form', array( $this, 'handle_ajax' ) );
	}

	/**
	 * AJAX submission endpoint.
	 */
	public function handle_ajax() {
		check_ajax_referer( 'kmfb_submit', 'nonce' );

		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;
		$form    = $this->forms->get_form( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'kamboj-form-builder' ) ), 404 );
		}

		$payload = wp_unslash( $_POST );
		$spam    = $this->spam->validate( $form, $payload );
		if ( is_wp_error( $spam ) ) {
			wp_send_json_error( array( 'message' => $spam->get_error_message() ), 400 );
		}

		$validated = $this->validate_fields( $form, $payload );
		if ( is_wp_error( $validated ) ) {
			$error_data = $validated->get_error_data( 'kmfb_validation' );
			$fields     = ( is_array( $error_data ) && isset( $error_data['fields'] ) ) ? $error_data['fields'] : array();

			wp_send_json_error(
				array(
					'message' => $validated->get_error_message(),
					'fields'  => $fields,
				),
				422
			);
		}

		$submission_id = 0;
		$storage_data  = $this->public_submission_data( $validated );
		if ( ! empty( $form['settings']['store_submissions'] ) ) {
			$submission_id = $this->submissions->insert( $form_id, $storage_data );
		}

		$mail_sent = $this->mailer->send_notification( $form, $validated );

		// Fallback: bare wp_mail exactly like functions.php if plugin mail path failed.
		if ( ! $mail_sent ) {
			$mail_sent = $this->send_fallback_mail( $form, $validated );
		}

		$this->mailer->send_confirmation( $form, $validated );

		$this->maybe_send_webhook( $form, $storage_data, $submission_id );

		/**
		 * Fires after a successful form submission.
		 *
		 * @param array<string, mixed> $validated     Sanitized data.
		 * @param array<string, mixed> $form          Form config.
		 * @param int                  $submission_id Submission ID.
		 */
		do_action( 'kmfb_form_submitted', $validated, $form, $submission_id );

		wp_send_json_success(
			array(
				'message'      => $form['settings']['success_message'],
				'redirect_url' => $form['settings']['redirect_url'],
			)
		);
	}

	/**
	 * Validate submitted fields against form definition.
	 *
	 * @param array<string, mixed> $form    Form.
	 * @param array<string, mixed> $payload Raw payload.
	 * @return array<string, mixed>|WP_Error
	 */
	private function validate_fields( $form, $payload ) {
		$clean        = array();
		$field_errors = array();

		foreach ( $form['fields'] as $field ) {
			if ( ! $this->field_is_visible( $field, $payload ) ) {
				continue;
			}

			$name  = $field['name'];
			$type  = $field['type'];
			$value = $payload[ $name ] ?? '';

			if ( 'checkbox' === $type && substr( $name, -2 ) === '[]' ) {
				$name  = substr( $name, 0, -2 );
				$value = $payload[ $name ] ?? array();
			}

			if ( is_array( $payload[ $name ] ?? null ) ) {
				$value = array_map( 'sanitize_text_field', $payload[ $name ] );
			} elseif ( 'textarea' === $type ) {
				$value = sanitize_textarea_field( (string) $value );
			} elseif ( 'email' === $type ) {
				$value = sanitize_email( (string) $value );
			} else {
				$value = sanitize_text_field( (string) $value );
			}

			if ( ! empty( $field['required'] ) && $this->is_empty_value( $value ) ) {
				$field_errors[ $name ] = $this->field_error_message( $form, $field, 'required' );
				continue;
			}

			if ( 'email' === $type && ! empty( $value ) && ! is_email( $value ) ) {
				$field_errors[ $name ] = $this->form_message( $form, 'invalid_email' );
				continue;
			}

			if ( 'tel' === $type && ! empty( $value ) && ! KMFB_Phone::is_valid( $value ) ) {
				$field_errors[ $name ] = $this->form_message( $form, 'invalid_phone' );
				continue;
			}

			if ( 'file' === $type ) {
				$file_result = $this->handle_file_upload( $form, $field, $name );
				if ( is_wp_error( $file_result ) ) {
					$field_errors[ $name ] = $file_result->get_error_message();
					continue;
				}
				if ( is_array( $file_result ) ) {
					if ( ! isset( $clean['_kmfb_file_paths'] ) ) {
						$clean['_kmfb_file_paths'] = array();
					}
					if ( ! empty( $file_result['path'] ) ) {
						$clean['_kmfb_file_paths'][ $name ] = $file_result['path'];
					}
					$value = $file_result['url'] ?? '';
				} else {
					$value = $file_result;
				}
			}

			if ( 'consent' === $type && ! empty( $field['required'] ) && empty( $value ) ) {
				$field_errors[ $name ] = $this->form_message( $form, 'consent_required' );
				continue;
			}

			$clean[ $name ] = $value;
		}

		if ( ! empty( $field_errors ) ) {
			return new WP_Error(
				'kmfb_validation',
				$this->form_message( $form, 'validation_summary' ),
				array( 'fields' => $field_errors )
			);
		}

		return $clean;
	}

	/**
	 * Resolve a field-level error message.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $key   Message key.
	 * @return string
	 */
	private function field_error_message( $form, $field, $key ) {
		if ( ! empty( $field['error_message'] ) ) {
			return sanitize_text_field( $field['error_message'] );
		}

		return $this->form_message( $form, $key );
	}

	/**
	 * Resolve a validation message for a form (form override, then global).
	 *
	 * @param array<string, mixed> $form Form config.
	 * @param string               $key  Message key.
	 * @return string
	 */
	private function form_message( $form, $key ) {
		$messages = $form['settings']['messages'] ?? array();
		if ( is_array( $messages ) && ! empty( $messages[ $key ] ) ) {
			return (string) $messages[ $key ];
		}

		return $this->settings->message( $key );
	}

	/**
	 * Check if value is empty.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_empty_value( $value ) {
		if ( is_array( $value ) ) {
			return empty( array_filter( $value, static function ( $item ) {
				return '' !== trim( (string) $item );
			} ) );
		}
		return '' === trim( (string) $value );
	}

	/**
	 * Determine if conditional field should be validated.
	 *
	 * @param array<string, mixed> $field   Field.
	 * @param array<string, mixed> $payload Payload.
	 * @return bool
	 */
	private function field_is_visible( $field, $payload ) {
		if ( empty( $field['conditions'] ) ) {
			return true;
		}

		foreach ( $field['conditions'] as $condition ) {
			$target = $condition['field'] ?? '';
			$value  = $payload[ $target ] ?? '';
			$operator = $condition['operator'] ?? 'equals';
			$expected = $condition['value'] ?? '';

			switch ( $operator ) {
				case 'not_equals':
					if ( (string) $value === (string) $expected ) {
						return false;
					}
					break;
				case 'filled':
					if ( $this->is_empty_value( $value ) ) {
						return false;
					}
					break;
				default:
					if ( (string) $value !== (string) $expected ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Handle secure file upload.
	 *
	 * @param array<string, mixed> $field Field.
	 * @param string               $name  Field name.
	 * @return array{url: string, path: string}|string|WP_Error Uploaded file info, empty string, or error.
	 */
	private function handle_file_upload( $form, $field, $name ) {
		// Nonce is verified in handle_ajax() before validation reaches file uploads.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES[ $name ] ) || empty( $_FILES[ $name ]['name'] ) ) {
			return ! empty( $field['required'] )
				? new WP_Error( 'kmfb_file', $this->form_message( $form, 'file_required' ) )
				: '';
		}

		$file = $_FILES[ $name ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing

		if ( ! empty( $file['error'] ) ) {
			return new WP_Error( 'kmfb_file', $this->form_message( $form, 'file_failed' ) );
		}

		$global      = $this->settings->get();
		$max_size    = (int) $field['max_size_mb'] * 1024 * 1024;
		$global_max  = (int) $global['max_file_size_mb'] * 1024 * 1024;
		$size_limit  = min( $max_size, $global_max );
		$allowed_ext = $this->settings->allowed_extensions();

		if ( (int) $file['size'] > $size_limit ) {
			return new WP_Error( 'kmfb_file', $this->form_message( $form, 'file_size' ) );
		}

		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_ext, true ) ) {
			return new WP_Error( 'kmfb_file', $this->form_message( $form, 'file_type' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$uploaded = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $this->get_allowed_mimes( $allowed_ext ),
			)
		);

		if ( isset( $uploaded['error'] ) ) {
			return new WP_Error( 'kmfb_file', $uploaded['error'] );
		}

		return array(
			'url'  => $uploaded['url'] ?? '',
			'path' => $uploaded['file'] ?? '',
		);
	}

	/**
	 * Build mime map from extensions.
	 *
	 * @param string[] $extensions Extensions.
	 * @return array<string, string>
	 */
	private function get_allowed_mimes( $extensions ) {
		$map = wp_get_mime_types();
		$allowed = array();

		foreach ( $extensions as $ext ) {
			foreach ( $map as $ext_group => $mime ) {
				$parts = explode( '|', $ext_group );
				if ( in_array( $ext, $parts, true ) ) {
					$allowed[ $ext ] = $mime;
				}
			}
		}

		return $allowed;
	}

	/**
	 * Remove internal submission keys before storage or webhooks.
	 *
	 * @param array<string, mixed> $data Submission data.
	 * @return array<string, mixed>
	 */
	private function public_submission_data( $data ) {
		unset( $data['_kmfb_file_paths'] );
		return $data;
	}

	/**
	 * POST submission data to webhook URL.
	 *
	 * @param array<string, mixed> $form          Form.
	 * @param array<string, mixed> $data          Data.
	 * @param int                  $submission_id Submission ID.
	 */
	private function maybe_send_webhook( $form, $data, $submission_id ) {
		$url = $form['settings']['webhook_url'] ?? '';
		if ( ! $url ) {
			return;
		}

		wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'form_id'       => $form['id'],
						'form_title'    => $form['title'],
						'submission_id' => $submission_id,
						'data'          => $data,
						'submitted_at'  => current_time( 'mysql' ),
					)
				),
			)
		);
	}

	/**
	 * Last-resort email using plain wp_mail (functions.php style).
	 *
	 * @param array<string, mixed> $form Form config.
	 * @param array<string, mixed> $data Submission data.
	 * @return bool
	 */
	private function send_fallback_mail( $form, $data ) {
		$notification = kmfb_plugin()->mailer->normalize_notification( $form['notification'] ?? array() );
		$to           = kmfb_plugin()->mailer->resolve_recipients( $notification['to'] ?? '' );

		if ( '' === $to ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: form title */
			__( 'New form submission: %s', 'kamboj-form-builder' ),
			$form['title'] ?? __( 'Contact Form', 'kamboj-form-builder' )
		);

		$lines = array();
		foreach ( $this->public_submission_data( $data ) as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			}
			$lines[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . $value;
		}

		$body        = implode( "\n", $lines );
		$attachments = array();
		$paths       = $data['_kmfb_file_paths'] ?? array();
		if ( 'attachment' === ( $notification['file_delivery'] ?? 'url' ) && is_array( $paths ) ) {
			foreach ( $paths as $path ) {
				if ( is_string( $path ) && is_readable( $path ) ) {
					$attachments[] = $path;
				}
			}
		}

		$sent = empty( $attachments ) ? wp_mail( $to, $subject, $body ) : wp_mail( $to, $subject, $body, '', $attachments );

		if ( $sent ) {
			$log = get_option( KMFB_Mailer::LOG_OPTION, array() );
			if ( ! is_array( $log ) ) {
				$log = array();
			}
			array_unshift(
				$log,
				array(
					'time'    => current_time( 'mysql' ),
					'type'    => 'notification',
					'form_id' => (int) ( $form['id'] ?? 0 ),
					'to'      => $to,
					'status'  => 'sent',
					'error'   => 'handler_fallback_bare',
				)
			);
			update_option( KMFB_Mailer::LOG_OPTION, array_slice( $log, 0, 15 ), false );
		}

		return (bool) $sent;
	}
}
