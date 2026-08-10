<?php
/**
 * Plugin Name:       Kamboj Form Builder – Drag & Drop Contact Forms
 * Plugin URI:        https://wordpress.org/plugins/kamboj-form-builder/
 * Description:       Create professional contact forms, feedback forms, and custom WordPress forms with a drag & drop builder, AJAX, reCAPTCHA, webhooks, and CSV export.
 * Version:           1.7.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mohit Kamboj
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kamboj-form-builder
 * Domain Path:       /languages
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

define( 'KMFB_VERSION', '1.7.1' );
define( 'KMFB_PLUGIN_FILE', __FILE__ );
define( 'KMFB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KMFB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-plugin.php';

/**
 * Boot plugin.
 *
 * @return KMFB_Plugin
 */
function kmfb_plugin() {
	return KMFB_Plugin::instance();
}

kmfb_plugin();

register_activation_hook( __FILE__, array( 'KMFB_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KMFB_Installer', 'deactivate' ) );
