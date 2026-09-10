<?php
/**
 * One leaf type card.
 *
 * @param array $args {
 *     @type WP_Post $post  The leaf type.
 *     @type int     $index Position in the list, 1-based. Picks the bundled photo slot.
 * }
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

$annamleaf_item = $args['post'] ?? null;

if ( ! $annamleaf_item instanceof WP_Post ) {
	return;
}

$annamleaf_id       = $annamleaf_item->ID;
$annamleaf_index    = max( 1, (int) ( $args['index'] ?? 1 ) );
$annamleaf_vi_name  = annamleaf_get_meta( $annamleaf_id, 'vi_name' );
?>
<article class="leafcard">
	<?php
	annamleaf_plate(
		array(
			'post_id'   => $annamleaf_id,
			// 'plant' is the factory motif, not a growing plant — a leaf type wants leaves.
			'motif'     => 0 === $annamleaf_index % 2 ? 'harvest' : 'grading',
			'photo'     => 'leaf-' . $annamleaf_index,
			'shot_note' => annamleaf_get_meta(
				$annamleaf_id,
				'shot_note',
				sprintf(
					/* translators: %s: leaf type name. */
					__( '%s laid flat on a plain background, daylight, no flash', 'annamleaf' ),
					get_the_title( $annamleaf_item )
				)
			),
		)
	);
	?>
	<div class="top">
		<?php annamleaf_leaf_mark( 'glyph' ); ?>
		<div>
			<h3><?php echo esc_html( get_the_title( $annamleaf_item ) ); ?></h3>
			<?php if ( '' !== $annamleaf_vi_name && annamleaf_is_vietnamese() ) : ?>
				<p class="vi-name"><?php echo esc_html( $annamleaf_vi_name ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $annamleaf_item->post_excerpt ?: $annamleaf_item->post_content ), 20, '…' ) ); ?></p>

</article>
