<?php
/**
 * Global settings view.
 *
 * @package KambojFormBuilder
 *
 * @var array<string, mixed>              $kmfb_settings Settings.
 * @var array<int, array<string, mixed>>  $kmfb_mail_log Mail log entries.
 */

defined( 'ABSPATH' ) || exit;

$kmfb_messages = isset( $kmfb_settings['messages'] ) && is_array( $kmfb_settings['messages'] )
	? wp_parse_args( $kmfb_settings['messages'], kmfb_plugin()->settings->default_messages() )
	: kmfb_plugin()->settings->default_messages();

$kmfb_message_labels = array(
	'validation_summary' => __( 'Validation summary (fallback message)', 'kamboj-form-builder' ),
	'required'           => __( 'Required field', 'kamboj-form-builder' ),
	'invalid_email'      => __( 'Invalid email', 'kamboj-form-builder' ),
	'invalid_phone'      => __( 'Invalid phone', 'kamboj-form-builder' ),
	'consent_required'   => __( 'Consent required', 'kamboj-form-builder' ),
	'file_required'      => __( 'File required', 'kamboj-form-builder' ),
	'file_failed'        => __( 'File upload failed', 'kamboj-form-builder' ),
	'file_size'          => __( 'File too large', 'kamboj-form-builder' ),
	'file_type'          => __( 'File type not allowed', 'kamboj-form-builder' ),
	'generic_error'      => __( 'Generic submit error', 'kamboj-form-builder' ),
	'recaptcha_required' => __( 'reCAPTCHA required', 'kamboj-form-builder' ),
	'recaptcha_failed'   => __( 'reCAPTCHA failed', 'kamboj-form-builder' ),
);
?>
<div class="wrap kmfb-admin">
	<h1><?php esc_html_e( 'Kamboj Form Builder Settings', 'kamboj-form-builder' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'kmfb_settings_group' ); ?>

		<h2><?php esc_html_e( 'Email delivery', 'kamboj-form-builder' ); ?></h2>
		<p class="description"><?php esc_html_e( 'By default the plugin sends mail exactly like wp_mail() in functions.php — no custom headers. Enable custom From only if you know your host supports it.', 'kamboj-form-builder' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Custom From address', 'kamboj-form-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[use_custom_from]" value="1" <?php checked( ! empty( $kmfb_settings['use_custom_from'] ) ); ?> />
						<?php esc_html_e( 'Use custom From name/email below (advanced — can break delivery on some hosts)', 'kamboj-form-builder' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email from name', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="text" class="regular-text" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[from_name]" value="<?php echo esc_attr( $kmfb_settings['from_name'] ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email from address', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="email" class="regular-text" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[from_email]" value="<?php echo esc_attr( $kmfb_settings['from_email'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Defaults to the WordPress admin email if left empty or invalid.', 'kamboj-form-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Test email', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="email" class="regular-text" id="kmfb-test-email-to" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
					<button type="button" class="button" id="kmfb-send-test-email"><?php esc_html_e( 'Send test email', 'kamboj-form-builder' ); ?></button>
					<span id="kmfb-test-email-status" class="kmfb-test-email-status"></span>
					<p class="description"><?php esc_html_e( 'Save settings first, then send a test. If it fails, install WP Mail SMTP or contact your host.', 'kamboj-form-builder' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Validation messages', 'kamboj-form-builder' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Customize the messages visitors see when a form fails validation. Each invalid field shows its own message below the field.', 'kamboj-form-builder' ); ?></p>

		<table class="form-table" role="presentation">
			<?php foreach ( $kmfb_message_labels as $kmfb_key => $kmfb_label ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $kmfb_label ); ?></th>
					<td>
						<input
							type="text"
							class="large-text"
							name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[messages][<?php echo esc_attr( $kmfb_key ); ?>]"
							value="<?php echo esc_attr( $kmfb_messages[ $kmfb_key ] ); ?>"
						/>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Google reCAPTCHA', 'kamboj-form-builder' ); ?></h2>
		<p class="description">
			<?php
			printf(
				/* translators: %s: Google reCAPTCHA admin URL */
				esc_html__( 'Get your Site Key and Secret Key from %s. Choose v2 for the checkbox widget or v3 for invisible score-based protection.', 'kamboj-form-builder' ),
				'<a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">Google reCAPTCHA Admin</a>'
			);
			?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'reCAPTCHA version', 'kamboj-form-builder' ); ?></th>
				<td>
					<select name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[recaptcha_version]">
						<option value="" <?php selected( $kmfb_settings['recaptcha_version'] ?? '', '' ); ?>><?php esc_html_e( 'Disabled', 'kamboj-form-builder' ); ?></option>
						<option value="v2" <?php selected( $kmfb_settings['recaptcha_version'] ?? '', 'v2' ); ?>><?php esc_html_e( 'v2 — Checkbox ("I\'m not a robot")', 'kamboj-form-builder' ); ?></option>
						<option value="v3" <?php selected( $kmfb_settings['recaptcha_version'] ?? '', 'v3' ); ?>><?php esc_html_e( 'v3 — Invisible (score-based)', 'kamboj-form-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Site key', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="text" class="large-text" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[recaptcha_site_key]" value="<?php echo esc_attr( $kmfb_settings['recaptcha_site_key'] ?? '' ); ?>" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Secret key', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="password" class="large-text" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[recaptcha_secret_key]" value="<?php echo esc_attr( $kmfb_settings['recaptcha_secret_key'] ?? '' ); ?>" autocomplete="new-password" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'v3 score threshold', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="number" min="0.1" max="0.9" step="0.1" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[recaptcha_v3_score]" value="<?php echo esc_attr( (string) ( $kmfb_settings['recaptcha_v3_score'] ?? 0.5 ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Only used for reCAPTCHA v3. Submissions below this score are blocked (0.5 recommended).', 'kamboj-form-builder' ); ?></p>
				</td>
			</tr>
		</table>
		<p class="description"><?php esc_html_e( 'After saving keys here, enable reCAPTCHA per form under Form Fields → Form behavior.', 'kamboj-form-builder' ); ?></p>

		<h2><?php esc_html_e( 'Spam protection & uploads', 'kamboj-form-builder' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Rate limit', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="number" min="1" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[rate_limit_count]" value="<?php echo esc_attr( (string) $kmfb_settings['rate_limit_count'] ); ?>" />
					<?php esc_html_e( 'submissions per', 'kamboj-form-builder' ); ?>
					<input type="number" min="1" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[rate_limit_window]" value="<?php echo esc_attr( (string) $kmfb_settings['rate_limit_window'] ); ?>" />
					<?php esc_html_e( 'minutes (per IP, per form)', 'kamboj-form-builder' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Allowed file types', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="text" class="regular-text" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[allowed_file_types]" value="<?php echo esc_attr( $kmfb_settings['allowed_file_types'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Comma-separated extensions, e.g. pdf,jpg,png', 'kamboj-form-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Max file size (MB)', 'kamboj-form-builder' ); ?></th>
				<td>
					<input type="number" min="1" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[max_file_size_mb]" value="<?php echo esc_attr( (string) $kmfb_settings['max_file_size_mb'] ); ?>" />
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Uninstall', 'kamboj-form-builder' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove plugin data', 'kamboj-form-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( KMFB_Settings::OPTION_KEY ); ?>[delete_data_on_uninstall]" value="1" <?php checked( ! empty( $kmfb_settings['delete_data_on_uninstall'] ) ); ?> />
						<?php esc_html_e( 'Delete forms, submissions, and plugin settings when the plugin is deleted', 'kamboj-form-builder' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Leave unchecked to keep your data if you reinstall later.', 'kamboj-form-builder' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<h2><?php esc_html_e( 'Recent email log', 'kamboj-form-builder' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Shows what happened after the last form submissions and test emails. Success message on the website does not guarantee email delivery.', 'kamboj-form-builder' ); ?></p>

	<table class="widefat striped kmfb-mail-log-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Time', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Type', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'To', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Status', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Details', 'kamboj-form-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $kmfb_mail_log ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No email attempts logged yet. Submit a form or send a test email.', 'kamboj-form-builder' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $kmfb_mail_log as $kmfb_entry ) : ?>
					<tr>
						<td><?php echo esc_html( $kmfb_entry['time'] ?? '' ); ?></td>
						<td><?php echo esc_html( $kmfb_entry['type'] ?? '' ); ?></td>
						<td><?php echo esc_html( $kmfb_entry['to'] ?? '' ); ?></td>
						<td><?php echo esc_html( $kmfb_entry['status'] ?? '' ); ?></td>
						<td><?php echo esc_html( $kmfb_entry['error'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
