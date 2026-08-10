<?php
/**
 * Forms list admin view.
 *
 * @package KambojFormBuilder
 *
 * @var array<int, array<string, mixed>> $kmfb_forms Forms.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap kmfb-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Kamboj Form Builder', 'kamboj-form-builder' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmfb-form-editor' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'kamboj-form-builder' ); ?></a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmfb-form-editor&template=newsletter' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Newsletter Form', 'kamboj-form-builder' ); ?></a>
	<hr class="wp-header-end" />

	<p><?php esc_html_e( 'Build forms visually, store submissions, and connect webhooks — no shortcode markup required.', 'kamboj-form-builder' ); ?></p>

	<table class="wp-list-table widefat striped kmfb-forms-table">
		<colgroup>
			<col class="kmfb-col-form" />
			<col class="kmfb-col-shortcode" />
			<col class="kmfb-col-email" />
			<col class="kmfb-col-count" />
			<col class="kmfb-col-actions" />
		</colgroup>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Form', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Email', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Submissions', 'kamboj-form-builder' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'kamboj-form-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $kmfb_forms ) ) : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No forms yet. Create your first form.', 'kamboj-form-builder' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $kmfb_forms as $kmfb_form ) : ?>
					<?php
					$kmfb_mailer   = kmfb_plugin()->mailer;
					$kmfb_notify   = $kmfb_mailer->normalize_notification( $kmfb_form['notification'] ?? array() );
					$kmfb_mail_on  = $kmfb_mailer->is_notification_enabled( $kmfb_notify );
					$kmfb_mail_to  = ! empty( $kmfb_notify['to'] ) ? $kmfb_notify['to'] : get_option( 'admin_email' );
					$kmfb_edit_url = admin_url( 'admin.php?page=kmfb-form-editor&form_id=' . (int) $kmfb_form['id'] );
					?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( $kmfb_edit_url ); ?>" class="row-title">
									<?php echo esc_html( $kmfb_form['title'] ); ?>
								</a>
							</strong>
						</td>
						<td><code>[kmfb_form id="<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>"]</code></td>
						<td>
							<?php if ( $kmfb_mail_on ) : ?>
								<span class="kmfb-mail-status kmfb-mail-on"><?php esc_html_e( 'ON', 'kamboj-form-builder' ); ?></span>
								<br><small><?php echo esc_html( $kmfb_mail_to ); ?></small>
							<?php else : ?>
								<span class="kmfb-mail-status kmfb-mail-off"><?php esc_html_e( 'OFF', 'kamboj-form-builder' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) kmfb_plugin()->submissions->count( (int) $kmfb_form['id'] ) ); ?></td>
						<td class="kmfb-row-actions">
							<a href="<?php echo esc_url( $kmfb_edit_url ); ?>"><?php esc_html_e( 'Edit', 'kamboj-form-builder' ); ?></a>
							<a href="#" data-kmfb-duplicate data-form-id="<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>"><?php esc_html_e( 'Duplicate', 'kamboj-form-builder' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmfb-submissions&form_id=' . $kmfb_form['id'] ) ); ?>"><?php esc_html_e( 'Submissions', 'kamboj-form-builder' ); ?></a>
							<a href="#" class="kmfb-delete-link" data-kmfb-delete data-form-id="<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>"><?php esc_html_e( 'Delete', 'kamboj-form-builder' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
