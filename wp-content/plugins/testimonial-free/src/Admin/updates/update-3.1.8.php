<?php
/**
 * Update options for the version 3.1.8
 *
 * @link       https://shapedplugin.com
 *
 * @package    testimonial_free
 * @subpackage testimonial_free/Admin/updates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


update_option( 'testimonial_version', '3.1.8' );
update_option( 'testimonial_db_version', '3.1.8' );

// Delete the transient for plugins.
if ( get_transient( 'sprtf_plugins' ) ) {
	delete_transient( 'sprtf_plugins' );
}
