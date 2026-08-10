<?php
/**
 * Frontend form wrapper.
 *
 * @package KambojFormBuilder
 *
 * @var array<string, mixed> $kmfb_form Form package.
 */

defined( 'ABSPATH' ) || exit;

$kmfb_renderer         = kmfb_plugin()->renderer;
$kmfb_settings         = $kmfb_form['settings'];
$kmfb_recaptcha        = kmfb_plugin()->recaptcha;
$kmfb_recaptcha_active = $kmfb_recaptcha->is_active_for_form( $kmfb_form );
$kmfb_recaptcha_config = $kmfb_recaptcha->get_config();
$kmfb_layout           = ! empty( $kmfb_settings['form_layout'] ) && 'inline' === $kmfb_settings['form_layout'] ? 'inline' : 'stacked';
$kmfb_form_classes     = 'kmfb-form' . ( 'inline' === $kmfb_layout ? ' kmfb-layout-inline' : '' );
?>
<div class="kmfb-form-wrap" id="kmfb-form-<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>">
	<form
		class="<?php echo esc_attr( $kmfb_form_classes ); ?>"
		method="post"
		enctype="multipart/form-data"
		novalidate
		<?php if ( $kmfb_recaptcha_active ) : ?>
			data-recaptcha-version="<?php echo esc_attr( $kmfb_recaptcha_config['version'] ); ?>"
			data-recaptcha-site-key="<?php echo esc_attr( $kmfb_recaptcha_config['site_key'] ); ?>"
		<?php endif; ?>
	>
		<input type="hidden" name="action" value="kmfb_submit_form" />
		<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>" />
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'kmfb_submit' ) ); ?>" />
		<input type="hidden" name="kmfb_started" value="<?php echo esc_attr( (string) ( time() * 1000 ) ); ?>" />

		<?php if ( ! empty( $kmfb_settings['enable_honeypot'] ) ) : ?>
			<div class="kmfb-hp" aria-hidden="true">
				<label for="kmfb-hp-<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>">Leave empty</label>
				<input type="text" id="kmfb-hp-<?php echo esc_attr( (string) $kmfb_form['id'] ); ?>" name="kmfb_hp" tabindex="-1" autocomplete="off" />
			</div>
		<?php endif; ?>

		<?php if ( 'inline' === $kmfb_layout ) : ?>
		<div class="kmfb-inline-body">
		<?php endif; ?>

		<div class="kmfb-fields">
			<?php foreach ( $kmfb_form['fields'] as $kmfb_field ) : ?>
				<?php echo $kmfb_renderer->render_field( $kmfb_field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $kmfb_recaptcha_active && 'v2' === $kmfb_recaptcha_config['version'] ) : ?>
			<div class="kmfb-recaptcha">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $kmfb_recaptcha_config['site_key'] ); ?>"></div>
			</div>
		<?php endif; ?>

		<div class="kmfb-actions">
			<button type="submit" class="kmfb-submit">
				<?php echo esc_html( $kmfb_settings['submit_label'] ); ?>
			</button>
		</div>

		<?php if ( 'inline' === $kmfb_layout ) : ?>
		</div>
		<?php endif; ?>

		<div class="kmfb-form-message" role="status" aria-live="polite" hidden></div>
	</form>
</div>
