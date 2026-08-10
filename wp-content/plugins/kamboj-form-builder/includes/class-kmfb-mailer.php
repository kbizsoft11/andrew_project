<?php
/**
 * Email notifications and merge tags.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends form notification emails.
 */
class KMFB_Mailer {

	const LOG_OPTION = 'kmfb_mail_log';

	/**
	 * Last PHPMailer / wp_mail error for the current attempt.
	 *
	 * @var string
	 */
	private $last_error = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_mail_failed', array( $this, 'capture_mail_failed' ) );
	}

	/**
	 * Store wp_mail failure details.
	 *
	 * @param WP_Error $error Error object.
	 */
	public function capture_mail_failed( $error ) {
		if ( $error instanceof WP_Error ) {
			$this->last_error = $error->get_error_message();
		}
	}

	/**
	 * Send notification for a submission.
	 *
	 * @param array<string, mixed> $form Form config.
	 * @param array<string, mixed> $data Sanitized submission data.
	 * @return bool
	 */
	public function send_notification( $form, $data ) {
		$notification = $this->normalize_notification( $form['notification'] ?? array() );

		if ( ! $this->is_notification_enabled( $notification ) ) {
			$this->log_event(
				array(
					'type'    => 'notification',
					'form_id' => (int) ( $form['id'] ?? 0 ),
					'to'      => '',
					'status'  => 'skipped_disabled',
					'error'   => __( 'Email notifications are OFF in the form editor (Email tab). Turn them on and save the form.', 'kamboj-form-builder' ),
				)
			);
			return false;
		}

		$to = $this->resolve_recipients( $notification['to'] ?? '' );

		if ( empty( $to ) ) {
			$this->log_event(
				array(
					'type'    => 'notification',
					'form_id' => (int) ( $form['id'] ?? 0 ),
					'to'      => '',
					'status'  => 'skipped_invalid_to',
					'error'   => __( 'No valid recipient email is configured.', 'kamboj-form-builder' ),
				)
			);
			return false;
		}

		$subject = $this->replace_tags( $notification['subject'], $form, $data );
		$body    = $this->replace_tags( $notification['body'], $form, $data );

		if ( '' === trim( $subject ) ) {
			$subject = sprintf(
				/* translators: %s: form title */
				__( 'New form submission: %s', 'kamboj-form-builder' ),
				$form['title'] ?? __( 'Contact Form', 'kamboj-form-builder' )
			);
		}

		if ( '' === trim( $body ) ) {
			$body = $this->build_plain_body( $form, $data );
		}

		$reply_email = $this->resolve_reply_to( $data );

		return $this->deliver_mail(
			$to,
			$subject,
			$body,
			$reply_email,
			array(
				'type'    => 'notification',
				'form_id' => (int) ( $form['id'] ?? 0 ),
				'form'    => $form,
				'data'    => $data,
			)
		);
	}

	/**
	 * Send a confirmation email to the person who submitted the form.
	 *
	 * @param array<string, mixed> $form Form config.
	 * @param array<string, mixed> $data Sanitized submission data.
	 * @return bool
	 */
	public function send_confirmation( $form, $data ) {
		$notification = $this->normalize_notification( $form['notification'] ?? array() );

		if ( '1' !== $this->normalize_enabled( $notification['confirmation_enabled'] ?? '0' ) ) {
			$this->log_event(
				array(
					'type'    => 'confirmation',
					'form_id' => (int) ( $form['id'] ?? 0 ),
					'to'      => '',
					'status'  => 'skipped_disabled',
					'error'   => __( 'Confirmation email is OFF in the form editor (Email tab).', 'kamboj-form-builder' ),
				)
			);
			return false;
		}

		$to = $this->resolve_submitter_email( $data );
		if ( ! is_email( $to ) ) {
			$this->log_event(
				array(
					'type'    => 'confirmation',
					'form_id' => (int) ( $form['id'] ?? 0 ),
					'to'      => '',
					'status'  => 'skipped_no_submitter',
					'error'   => __( 'No valid submitter email found in the submission.', 'kamboj-form-builder' ),
				)
			);
			return false;
		}

		$subject = $this->replace_tags( $notification['confirmation_subject'], $form, $data );
		$body    = $this->replace_tags( $notification['confirmation_body'], $form, $data );

		if ( '' === trim( $subject ) ) {
			$subject = sprintf(
				/* translators: %s: site name */
				__( 'Thank you for contacting %s', 'kamboj-form-builder' ),
				get_bloginfo( 'name' )
			);
		}

		if ( '' === trim( $body ) ) {
			$body = sprintf(
				/* translators: %s: site name */
				__( "Thank you for your submission. We will get back to you soon.\n\n— %s", 'kamboj-form-builder' ),
				get_bloginfo( 'name' )
			);
		}

		return $this->deliver_mail(
			$to,
			$subject,
			$body,
			'',
			array(
				'type'    => 'confirmation',
				'form_id' => (int) ( $form['id'] ?? 0 ),
				'form'    => $form,
				'data'    => $data,
			)
		);
	}

	/**
	 * Send a test email from settings.
	 *
	 * @param string $to Recipient email.
	 * @return true|WP_Error
	 */
	public function send_test_email( $to ) {
		if ( ! is_email( $to ) ) {
			return new WP_Error( 'kmfb_invalid_email', __( 'Please enter a valid email address.', 'kamboj-form-builder' ) );
		}

		$form = array(
			'id'    => 0,
			'title' => __( 'Test Form', 'kamboj-form-builder' ),
		);
		$data = array(
			'name'    => 'Test User',
			'email'   => $to,
			'message' => __( 'This is a test email from Kamboj Form Builder.', 'kamboj-form-builder' ),
		);

		$sent = $this->deliver_mail(
			$to,
			__( 'Kamboj Form Builder test email', 'kamboj-form-builder' ),
			__( 'If you received this message, email delivery is working.', 'kamboj-form-builder' ) . "\n\n" .
			__( 'Site:', 'kamboj-form-builder' ) . ' ' . home_url( '/' ) . "\n" .
			__( 'Sent at:', 'kamboj-form-builder' ) . ' ' . wp_date( 'Y-m-d H:i:s' ),
			'',
			array(
				'type'    => 'test',
				'form_id' => 0,
				'form'    => $form,
				'data'    => $data,
			)
		);

		if ( ! $sent ) {
			$error = $this->last_error ? $this->last_error : __( 'Unknown mail error.', 'kamboj-form-builder' );
			return new WP_Error(
				'kmfb_mail_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'WordPress could not send the email: %s', 'kamboj-form-builder' ),
					$error
				)
			);
		}

		return true;
	}

	/**
	 * Get recent mail log entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_mail_log() {
		$log = get_option( self::LOG_OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Normalize notification settings from storage.
	 *
	 * @param array<string, mixed> $notification Raw notification settings.
	 * @return array<string, string>
	 */
	public function normalize_notification( $notification ) {
		if ( ! is_array( $notification ) ) {
			$notification = array();
		}

		$defaults = kmfb_plugin()->forms->default_notification();
		$merged   = wp_parse_args( $notification, $defaults );

		return array(
			'enabled'              => $this->normalize_enabled( $merged['enabled'] ?? '1' ),
			'to'                   => $this->sanitize_recipients_string( $merged['to'] ?? '' ),
			'subject'              => sanitize_text_field( $merged['subject'] ?? '' ),
			'body'                 => sanitize_textarea_field( $merged['body'] ?? '' ),
			'confirmation_enabled' => $this->normalize_enabled( $merged['confirmation_enabled'] ?? '0' ),
			'confirmation_subject' => sanitize_text_field( $merged['confirmation_subject'] ?? '' ),
			'confirmation_body'    => sanitize_textarea_field( $merged['confirmation_body'] ?? '' ),
			'file_delivery'        => in_array( $merged['file_delivery'] ?? 'url', array( 'url', 'attachment' ), true )
				? $merged['file_delivery']
				: 'url',
		);
	}

	/**
	 * Parse comma/semicolon separated recipients into a wp_mail-ready string.
	 *
	 * @param string $value Raw recipient list.
	 * @return string
	 */
	public function sanitize_recipients_string( $value ) {
		$recipients = $this->parse_recipient_list( (string) $value );
		return implode( ', ', $recipients );
	}

	/**
	 * Resolve notification recipients, falling back to admin email.
	 *
	 * @param string $value Raw recipient list.
	 * @return string
	 */
	public function resolve_recipients( $value ) {
		$recipients = $this->parse_recipient_list( (string) $value );

		if ( empty( $recipients ) ) {
			$admin = sanitize_email( get_option( 'admin_email' ) );
			if ( is_email( $admin ) ) {
				$recipients[] = $admin;
			}
		}

		return implode( ', ', $recipients );
	}

	/**
	 * Split and validate multiple email addresses.
	 *
	 * @param string $value Raw recipient list.
	 * @return string[]
	 */
	public function parse_recipient_list( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return array();
		}

		$parts = preg_split( '/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		$valid = array();

		foreach ( $parts as $part ) {
			$email = sanitize_email( trim( $part ) );
			if ( is_email( $email ) ) {
				$valid[] = $email;
			}
		}

		return array_values( array_unique( $valid ) );
	}

	/**
	 * Check whether notifications are enabled.
	 *
	 * @param array<string, mixed> $notification Notification settings.
	 * @return bool
	 */
	public function is_notification_enabled( $notification ) {
		return '1' === $this->normalize_enabled( $notification['enabled'] ?? '1' );
	}

	/**
	 * Normalize enabled flag to "1" or "0".
	 *
	 * @param mixed $value Raw enabled value.
	 * @return string
	 */
	public function normalize_enabled( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return '1';
		}

		return '0';
	}

	/**
	 * Deliver email the same way a theme functions.php call would.
	 *
	 * @param string               $to          Recipient.
	 * @param string               $subject     Subject.
	 * @param string               $body        Body.
	 * @param string               $reply_email Reply-to address.
	 * @param array<string, mixed> $context     Logging context.
	 * @return bool
	 */
	private function deliver_mail( $to, $subject, $body, $reply_email, $context ) {
		$this->last_error = '';
		$form             = $context['form'] ?? array();
		$data             = $context['data'] ?? array();
		$mode             = 'bare';
		$attachments      = $this->resolve_attachments( $form, $data );

		/**
		 * Short-circuit email delivery.
		 *
		 * @param null|bool            $pre_send Short-circuit return value.
		 * @param string               $to       Recipient.
		 * @param string               $subject  Subject.
		 * @param string               $body     Body.
		 * @param array<string, mixed> $form     Form config.
		 * @param array<string, mixed> $data     Submission data.
		 */
		$pre_send = apply_filters( 'kmfb_pre_wp_mail', null, $to, $subject, $body, $form, $data );
		if ( null !== $pre_send ) {
			$sent = (bool) $pre_send;
			$this->log_event(
				array(
					'type'    => $context['type'] ?? 'notification',
					'form_id' => (int) ( $context['form_id'] ?? 0 ),
					'to'      => $to,
					'status'  => $sent ? 'sent' : 'failed',
					'error'   => $sent ? 'filter:kmfb_pre_wp_mail' : 'filter:kmfb_pre_wp_mail_failed',
				)
			);
			return $sent;
		}

		// 1) Plain wp_mail — same as functions.php with no headers/filters.
		$sent = $this->wp_mail_send( $to, $subject, $body, '', $attachments );

		// 2) Optional Reply-To only if plain send failed.
		if ( ! $sent && $reply_email && is_email( $reply_email ) ) {
			$this->last_error = '';
			$mode             = 'reply_to';
			$sent             = $this->wp_mail_send( $to, $subject, $body, 'Reply-To: ' . $reply_email, $attachments );
		}

		// 3) Optional custom From (off by default — many hosts break on this).
		if ( ! $sent && $this->should_use_custom_from() ) {
			$this->last_error = '';
			$mode             = 'custom_from';
			$sent             = $this->attempt_custom_from_mail( $to, $subject, $body, $reply_email, $attachments );
		}

		$this->log_event(
			array(
				'type'    => $context['type'] ?? 'notification',
				'form_id' => (int) ( $context['form_id'] ?? 0 ),
				'to'      => $to,
				'status'  => $sent ? 'sent' : 'failed',
				'error'   => $sent ? $mode : ( $this->last_error ?: __( 'wp_mail returned false.', 'kamboj-form-builder' ) ),
			)
		);

		return (bool) $sent;
	}

	/**
	 * Whether custom From settings should be applied.
	 *
	 * @return bool
	 */
	private function should_use_custom_from() {
		$settings = kmfb_plugin()->settings->get();
		return ! empty( $settings['use_custom_from'] );
	}

	/**
	 * Send using plugin From name/email settings.
	 *
	 * @param string $to          Recipient.
	 * @param string $subject     Subject.
	 * @param string $body        Body.
	 * @param string $reply_email Reply-to address.
	 * @return bool
	 */
	private function attempt_custom_from_mail( $to, $subject, $body, $reply_email, $attachments = array() ) {
		$global = kmfb_plugin()->settings->get();
		$from   = $this->resolve_from_address( $global );

		if ( ! is_email( $from['email'] ) ) {
			return false;
		}

		$from_email_filter = static function () use ( $from ) {
			return $from['email'];
		};
		$from_name_filter = static function () use ( $from ) {
			return $from['name'];
		};

		add_filter( 'wp_mail_from', $from_email_filter );
		add_filter( 'wp_mail_from_name', $from_name_filter );

		$headers = array();
		if ( $reply_email && is_email( $reply_email ) ) {
			$headers[] = 'Reply-To: ' . $reply_email;
		}

		$sent = empty( $headers )
			? $this->wp_mail_send( $to, $subject, $body, '', $attachments )
			: $this->wp_mail_send( $to, $subject, $body, $headers, $attachments );

		remove_filter( 'wp_mail_from', $from_email_filter );
		remove_filter( 'wp_mail_from_name', $from_name_filter );

		return (bool) $sent;
	}

	/**
	 * Build a plain-text body from submission data.
	 *
	 * @param array<string, mixed> $form Form config.
	 * @param array<string, mixed> $data Submission data.
	 * @return string
	 */
	private function build_plain_body( $form, $data ) {
		$notification   = $this->normalize_notification( $form['notification'] ?? array() );
		$attach_mode    = 'attachment' === ( $notification['file_delivery'] ?? 'url' );
		$file_field_map = array();

		foreach ( $form['fields'] ?? array() as $field ) {
			if ( 'file' === ( $field['type'] ?? '' ) && ! empty( $field['name'] ) ) {
				$file_field_map[ $field['name'] ] = true;
			}
		}

		$lines = array(
			__( 'You received a new submission.', 'kamboj-form-builder' ),
			'',
		);

		foreach ( $data as $key => $value ) {
			if ( 0 === strpos( (string) $key, '_' ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			}
			$value   = $this->format_field_value_for_email( (string) $key, (string) $value, $attach_mode, $file_field_map );
			$lines[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . $value;
		}

		$lines[] = '';
		$lines[] = '--';
		$lines[] = sprintf(
			/* translators: %s: site name */
			__( 'Sent from %s', 'kamboj-form-builder' ),
			get_bloginfo( 'name' )
		);

		return implode( "\n", $lines );
	}

	/**
	 * Persist a mail log entry.
	 *
	 * @param array<string, mixed> $event Event data.
	 */
	private function log_event( $event ) {
		$entry = wp_parse_args(
			$event,
			array(
				'time'    => current_time( 'mysql' ),
				'type'    => 'notification',
				'form_id' => 0,
				'to'      => '',
				'status'  => 'failed',
				'error'   => '',
			)
		);

		$log = $this->get_mail_log();
		array_unshift( $log, $entry );
		$log = array_slice( $log, 0, 15 );

		update_option( self::LOG_OPTION, $log, false );
	}

	/**
	 * Resolve a safe From address for outgoing mail.
	 *
	 * @param array<string, mixed> $global Global settings.
	 * @return array{name: string, email: string}
	 */
	private function resolve_from_address( $global ) {
		$admin_email = sanitize_email( get_option( 'admin_email' ) );
		$from_email  = is_email( $global['from_email'] ?? '' ) ? sanitize_email( $global['from_email'] ) : $admin_email;

		if ( ! is_email( $from_email ) ) {
			$from_email = $admin_email;
		}

		$from_name = ! empty( $global['from_name'] ) ? $global['from_name'] : get_bloginfo( 'name' );

		return array(
			'name'  => $this->sanitize_header_text( $from_name ),
			'email' => $from_email,
		);
	}

	/**
	 * Resolve reply-to address from submission data.
	 *
	 * @param array<string, mixed> $data Submission data.
	 * @return string
	 */
	private function resolve_reply_to( $data ) {
		return $this->resolve_submitter_email( $data );
	}

	/**
	 * Resolve submitter email from submission data.
	 *
	 * @param array<string, mixed> $data Submission data.
	 * @return string
	 */
	private function resolve_submitter_email( $data ) {
		if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
			return sanitize_email( $data['email'] );
		}

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) && false !== stripos( (string) $key, 'email' ) && is_email( $value ) ) {
				return sanitize_email( $value );
			}
		}

		return '';
	}

	/**
	 * Strip unsafe characters from mail header text.
	 *
	 * @param string $value Header value.
	 * @return string
	 */
	private function sanitize_header_text( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		$value = str_replace( array( "\r", "\n" ), '', $value );
		return trim( $value );
	}

	/**
	 * Replace merge tags in template strings.
	 *
	 * @param string               $template Template.
	 * @param array<string, mixed> $form     Form.
	 * @param array<string, mixed> $data     Data.
	 * @return string
	 */
	public function replace_tags( $template, $form, $data ) {
		$notification  = $this->normalize_notification( $form['notification'] ?? array() );
		$attach_mode   = 'attachment' === ( $notification['file_delivery'] ?? 'url' );
		$file_field_map = array();

		foreach ( $form['fields'] ?? array() as $field ) {
			if ( 'file' === ( $field['type'] ?? '' ) && ! empty( $field['name'] ) ) {
				$file_field_map[ $field['name'] ] = true;
			}
		}

		$all_fields = '';
		foreach ( $data as $key => $value ) {
			if ( 0 === strpos( (string) $key, '_' ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			}
			$value = $this->format_field_value_for_email( (string) $key, (string) $value, $attach_mode, $file_field_map );
			$all_fields .= ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . $value . "\n";
		}

		$replacements = array(
			'{{form_title}}' => $form['title'] ?? '',
			'{{site_name}}'  => get_bloginfo( 'name' ),
			'{{site_url}}'   => home_url( '/' ),
			'{{all_fields}}' => trim( $all_fields ),
			'{{date}}'       => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
		);

		foreach ( $data as $key => $value ) {
			if ( 0 === strpos( (string) $key, '_' ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			}
			$replacements[ '{{' . $key . '}}' ] = $this->format_field_value_for_email(
				(string) $key,
				(string) $value,
				$attach_mode,
				$file_field_map
			);
		}

		return strtr( (string) $template, $replacements );
	}

	/**
	 * Resolve uploaded file paths for email attachments.
	 *
	 * @param array<string, mixed> $form Form config.
	 * @param array<string, mixed> $data Submission data.
	 * @return string[]
	 */
	private function resolve_attachments( $form, $data ) {
		$notification = $this->normalize_notification( $form['notification'] ?? array() );
		if ( 'attachment' !== ( $notification['file_delivery'] ?? 'url' ) ) {
			return array();
		}

		$paths = $data['_kmfb_file_paths'] ?? array();
		if ( ! is_array( $paths ) ) {
			return array();
		}

		$attachments = array();
		foreach ( $paths as $path ) {
			if ( is_string( $path ) && is_readable( $path ) ) {
				$attachments[] = $path;
			}
		}

		return $attachments;
	}

	/**
	 * Send mail with optional attachments.
	 *
	 * @param string               $to          Recipient.
	 * @param string               $subject     Subject.
	 * @param string               $body        Body.
	 * @param string|string[]      $headers     Headers.
	 * @param string[]             $attachments File paths.
	 * @return bool
	 */
	private function wp_mail_send( $to, $subject, $body, $headers = '', $attachments = array() ) {
		if ( empty( $attachments ) ) {
			return wp_mail( $to, $subject, $body, $headers );
		}

		return wp_mail( $to, $subject, $body, $headers, $attachments );
	}

	/**
	 * Format a field value for email templates.
	 *
	 * @param string               $key            Field name.
	 * @param string               $value          Field value.
	 * @param bool                 $attach_mode    Whether files are attached.
	 * @param array<string, bool>  $file_field_map File field names.
	 * @return string
	 */
	private function format_field_value_for_email( $key, $value, $attach_mode, $file_field_map ) {
		if ( $attach_mode && ! empty( $file_field_map[ $key ] ) && '' !== $value ) {
			$filename = basename( (string) wp_parse_url( $value, PHP_URL_PATH ) );
			return sprintf(
				/* translators: %s: uploaded file name */
				__( 'Attached: %s', 'kamboj-form-builder' ),
				$filename ?: $value
			);
		}

		return $value;
	}
}
