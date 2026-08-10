<?php
/**
 * Submissions inbox view.
 *
 * @package KambojFormBuilder
 *
 * @var int                   $kmfb_form_id Form filter.
 * @var array<int, object>    $kmfb_submissions Submissions.
 * @var array<int, array>     $kmfb_forms All forms.
 * @var object|null           $kmfb_selected Selected submission.
 */

defined( 'ABSPATH' ) || exit;

$kmfb_export_url = wp_nonce_url(
	admin_url( 'admin-post.php?action=kmfb_export_csv&form_id=' . $kmfb_form_id ),
	'kmfb_export_csv'
);
?>
<div class="wrap kmfb-admin">
	<h1><?php esc_html_e( 'Submissions Inbox', 'kamboj-form-builder' ); ?></h1>

	<div class="kmfb-submissions-toolbar">
		<form method="get">
			<input type="hidden" name="page" value="kmfb-submissions" />
			<select name="form_id" onchange="this.form.submit()">
				<option value="0"><?php esc_html_e( 'All forms', 'kamboj-form-builder' ); ?></option>
				<?php foreach ( $kmfb_forms as $kmfb_form ) : ?>
					<option value="<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>" <?php selected( $kmfb_form_id, (int) $kmfb_form['id'] ); ?>>
						<?php echo esc_html( $kmfb_form['title'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
		<a class="button" href="<?php echo esc_url( $kmfb_export_url ); ?>"><?php esc_html_e( 'Export CSV', 'kamboj-form-builder' ); ?></a>
	</div>

	<div class="kmfb-submissions-layout">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'kamboj-form-builder' ); ?></th>
					<th><?php esc_html_e( 'Form', 'kamboj-form-builder' ); ?></th>
					<th><?php esc_html_e( 'Date', 'kamboj-form-builder' ); ?></th>
					<th><?php esc_html_e( 'Status', 'kamboj-form-builder' ); ?></th>
					<th><?php esc_html_e( 'Preview', 'kamboj-form-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $kmfb_submissions ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No submissions yet.', 'kamboj-form-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $kmfb_submissions as $kmfb_row ) : ?>
						<?php
						$kmfb_data    = kmfb_plugin()->submissions->decode_data( $kmfb_row );
						$kmfb_preview = '';
						foreach ( array_slice( $kmfb_data, 0, 2 ) as $kmfb_key => $kmfb_value ) {
							$kmfb_preview .= ucwords( str_replace( '_', ' ', $kmfb_key ) ) . ': ' . ( is_array( $kmfb_value ) ? implode( ', ', $kmfb_value ) : $kmfb_value ) . ' · ';
						}
						$kmfb_form_title = '';
						foreach ( $kmfb_forms as $kmfb_form ) {
							if ( (int) $kmfb_form['id'] === (int) $kmfb_row->form_id ) {
								$kmfb_form_title = $kmfb_form['title'];
								break;
							}
						}
						?>
						<tr>
							<td><?php echo esc_html( (string) $kmfb_row->id ); ?></td>
							<td><?php echo esc_html( $kmfb_form_title ); ?></td>
							<td><?php echo esc_html( $kmfb_row->created_at ); ?></td>
							<td><?php echo esc_html( $kmfb_row->status ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmfb-submissions&submission_id=' . $kmfb_row->id . '&form_id=' . $kmfb_form_id ) ); ?>">
									<?php echo esc_html( trim( $kmfb_preview, ' ·' ) ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $kmfb_selected ) : ?>
			<div class="kmfb-submission-detail">
				<h2><?php esc_html_e( 'Submission Details', 'kamboj-form-builder' ); ?> #<?php echo esc_html( (string) $kmfb_selected->id ); ?></h2>
				<p><strong><?php esc_html_e( 'IP:', 'kamboj-form-builder' ); ?></strong> <?php echo esc_html( $kmfb_selected->ip ); ?></p>
				<p><strong><?php esc_html_e( 'Date:', 'kamboj-form-builder' ); ?></strong> <?php echo esc_html( $kmfb_selected->created_at ); ?></p>
				<table class="widefat">
					<tbody>
						<?php foreach ( kmfb_plugin()->submissions->decode_data( $kmfb_selected ) as $kmfb_key => $kmfb_value ) : ?>
							<tr>
								<th><?php echo esc_html( ucwords( str_replace( '_', ' ', $kmfb_key ) ) ); ?></th>
								<td><?php echo esc_html( is_array( $kmfb_value ) ? implode( ', ', $kmfb_value ) : (string) $kmfb_value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
