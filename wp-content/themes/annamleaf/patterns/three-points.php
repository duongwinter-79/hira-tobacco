<?php
/**
 * Title: Three points
 * Slug: annamleaf/three-points
 * Categories: annamleaf
 * Description: Three short columns — used for "our model" on About and the sustainability commitments on Quality.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:columns {"className":"annamleaf-three"} -->
<div class="wp-block-columns annamleaf-three">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php esc_html_e( 'We grow it', 'annamleaf' ); ?></h3><!-- /wp:heading -->
		<!-- wp:paragraph --><p><?php esc_html_e( 'Own nurseries, contracted households, and inputs supplied and controlled by us. Field technicians are on the ground all season.', 'annamleaf' ); ?></p><!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php esc_html_e( 'We cure and grade it', 'annamleaf' ); ?></h3><!-- /wp:heading -->
		<!-- wp:paragraph --><p><?php esc_html_e( 'Curing barns under technical supervision, graded at our buying stations against your samples and recorded by lot.', 'annamleaf' ); ?></p><!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php esc_html_e( 'We process and ship it', 'annamleaf' ); ?></h3><!-- /wp:heading -->
		<!-- wp:paragraph --><p><?php esc_html_e( 'Threshing, redrying, baling, lab testing and export documentation handled in house.', 'annamleaf' ); ?></p><!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
