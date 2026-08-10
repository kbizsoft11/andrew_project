<?php
/**
 * Uninstall file.
 *
 * @link       http://shapedplugin.com
 * @since      1.0.0
 *
 * @package    Testimonial
 * @subpackage Testimonial/uninstall
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Load TPro file.
require_once 'testimonial-free.php';

$setting_options         = get_option( 'sp_testimonial_pro_options' );
$testimonial_data_remove = isset( $setting_options['testimonial_data_remove'] ) ? $setting_options['testimonial_data_remove'] : false;
if ( $testimonial_data_remove ) {

	// Delete member post type.
	$testimonials = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => array( 'spt_testimonial', 'spt_shortcodes', 'spt_testimonial_form' ),
			'post_status' => array( 'any', 'trash' ),
		)
	);
	foreach ( $testimonials as $testimonial ) {
		wp_delete_post( $testimonial->ID, true );
	}

	// Delete plugin options.
	$plugin_options = array(
		'sp_testimonial_pro_options',
		'testimonial_cat_children',
		'testimonial_version',
		'testimonial_first_version',
		'testimonial_activation_date',
		'testimonial_db_version',
	);

	foreach ( $plugin_options as $plugin_option ) {
		delete_option( $plugin_option );
		delete_site_option( $plugin_option ); // for multisite.
	}

	// Delete offer banner related option keys.
	delete_option( 'shapedplugin_offer_banner_dismissed_black_friday_2025' );
	delete_option( 'shapedplugin_offer_banner_dismissed_new_year_2026' );
}
