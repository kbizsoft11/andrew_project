<?php
/**
 * Section title.
 *
 * This template can be overridden by copying it to your theme/testimonial-free/templates/section-title.php
 *
 * @package    Testimonial_Free
 * @subpackage Testimonial_Free/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<h2 class="sp-testimonial-free-section-title"><?php echo wp_kses_post( $main_section_title ); ?></h2>
