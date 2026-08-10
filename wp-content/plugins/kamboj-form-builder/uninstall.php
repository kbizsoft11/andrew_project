<?php
/**
 * Uninstall cleanup.
 *
 * @package KambojFormBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$kmfb_settings = get_option( 'kmfb_settings', array() );
if ( ! is_array( $kmfb_settings ) || empty( $kmfb_settings['delete_data_on_uninstall'] ) ) {
	return;
}

require_once __DIR__ . '/includes/class-kmfb-installer.php';

$kmfb_form_ids = get_posts(
	array(
		'post_type'      => array( 'kmfb_form', 'mfm_form' ),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $kmfb_form_ids as $kmfb_form_id ) {
	wp_delete_post( (int) $kmfb_form_id, true );
}

KMFB_Installer::drop_tables();

delete_option( 'kmfb_settings' );
delete_option( 'kmfb_mail_log' );
delete_option( 'kmfb_db_version' );
delete_option( 'kmfb_notification_fix_v1' );
