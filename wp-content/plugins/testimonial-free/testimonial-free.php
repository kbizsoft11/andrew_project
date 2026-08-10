<?php
/**
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://shapedplugin.com
 * @since             1.0
 * @package           Testimonial
 *
 * Plugin Name:     Real Testimonials
 * Plugin URI:      https://realtestimonials.io/?ref=1
 * Description:     Real Testimonials is a responsive and customizable testimonial plugin for WordPress. Easily collect customer reviews and video testimonials with review forms, display them in beautiful layouts, and publishing—all in just a few minutes.
 * Version:         3.1.16
 * Author:          ShapedPlugin LLC
 * Author URI:      https://shapedplugin.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:     testimonial-free
 * Domain Path:     /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

define( 'SP_TFREE_NAME', 'Real Testimonials' );
define( 'SP_TFREE_VERSION', '3.1.16' );
define( 'SP_TFREE_PATH', plugin_dir_path( __FILE__ ) . 'src/' );
define( 'SP_TFREE_URL', plugin_dir_url( __FILE__ ) . 'src/' );
define( 'SP_TFREE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Pro version check.
 *
 * @return boolean
 */
function is_testimonial_pro_active() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	if (
		is_plugin_active( 'testimonial-pro/testimonial-pro.php' ) ||
		is_plugin_active_for_network( 'testimonial-pro/testimonial-pro.php' )
	) {
		return true;
	}

	return false;
}

/**
 * Returns the main instance.
 *
 * @since 2.0 SP_Testimonial_FREE
 * @return void
 */
function sp_testimonial_free() {
	if ( ! defined( 'SHAPEDPLIUGIN_OFFER_BANNER_LOADED' ) ) {
		define( 'SHAPEDPLIUGIN_OFFER_BANNER_LOADED', true );

		/**
		 * The file is responsible for generating admin offer banner.
		 */
		ShapedPlugin\TestimonialFree\Admin\Views\Notices\ShapedPlugin_Offer_Banner::instance();
	}

	new ShapedPlugin\TestimonialFree\Includes\TestimonialFree();
}

// End of the class.
if ( ! function_exists( 'sp_testimonial' ) ) {
	/**
	 * Shortcode converter function
	 *
	 * @param  int $post_id shortcode id.
	 * @return void
	 */
	function sp_testimonial( $post_id ) {
		echo do_shortcode( '[sp_testimonial id="' . esc_attr( $post_id ) . '"]' );
	}
}

if ( ! is_testimonial_pro_active() ) {
	sp_testimonial_free();
	new ShapedPlugin\TestimonialFree\Admin\Views\Notices\Testimonial_Review();
	new ShapedPlugin\TestimonialFree\Admin\Views\Framework\Classes\SPFTESTIMONIAL();
}
