<?php
/**
 * Form builder admin view.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap kmfb-admin kmfb-editor-wrap">
	<h1>
		<?php esc_html_e( 'Form Builder', 'kamboj-form-builder' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmfb-forms' ) ); ?>" class="page-title-action"><?php esc_html_e( 'All Forms', 'kamboj-form-builder' ); ?></a>
	</h1>
	<p class="kmfb-editor-intro"><?php esc_html_e( 'Build your form visually, preview it live, and publish with one shortcode.', 'kamboj-form-builder' ); ?></p>

	<div id="kmfb-builder-app" class="kmfb-builder-app">
		<p><?php esc_html_e( 'Loading builder…', 'kamboj-form-builder' ); ?></p>
	</div>
</div>
