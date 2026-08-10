<?php
/**
 * Validation message.
 *
 * This template can be overridden by copying it to yourtheme/testimonial-free/templates/form/validation-msg.php
 *
 * @package    Testimonial_Free
 * @subpackage Testimonial_Free/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="sp-tpro-form-validation-msg"><?php echo wp_kses_post( stripslashes( $validation_msg ) ); ?></div>
