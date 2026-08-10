<?php
/**
 * Client designation.
 *
 * This template can be overridden by copying it to your theme/testimonial-free/templates/testimonial/designation.php
 *
 * @package    Testimonial_Free
 * @subpackage Testimonial_Free/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?> 
<div class="sp-testimonial-client-designation">
<?php
	do_action( 'sptpro_before_testimonial_client_designation_company' );
	echo wp_kses_post( $tfree_designation );
	do_action( 'sptpro_after_testimonial_client_designation_company' );
?>
</div>
