<?php
/**
 * The process page: every stage from field to factory, in the order the client set.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

annamleaf_hero(
	array(
		'eyebrow'   => __( 'From field to factory', 'annamleaf' ),
		'title'     => __( 'Seven stages, one company accountable', 'annamleaf' ),
		'text'      => __( 'This is what separates us from a leaf trader: every stage below happens inside our own system, with nothing brokered.', 'annamleaf' ),
		'motif'     => 'curing',
		'shot_note' => __( 'Barn exteriors, or golden leaf hanging inside', 'annamleaf' ),
		'compact'   => true,
	)
);
?>

<div class="wrap">
	<div class="steps">
		<?php
		$annamleaf_index = 0;

		while ( have_posts() ) :
			the_post();
			$annamleaf_index++;
			?>
			<article class="stepblk">
				<div class="stepblk-fig">
					<?php
					annamleaf_plate(
						array(
							'post_id'    => get_the_ID(),
							'motif'      => annamleaf_motif_for_index( $annamleaf_index ),
							'shot_note'  => annamleaf_get_meta( get_the_ID(), 'shot_note' ),
							'shot_index' => sprintf( 'PHOTO %02d', $annamleaf_index + 1 ),
						)
					);
					?>
				</div>
				<div>
					<span class="no">
						<?php
						printf(
							/* translators: %s: stage number, e.g. 01. */
							esc_html__( 'STAGE %s', 'annamleaf' ),
							esc_html( annamleaf_get_meta( get_the_ID(), 'stage_no', sprintf( '%02d', $annamleaf_index ) ) )
						);
						?>
					</span>
					<h3><?php the_title(); ?></h3>
					<div class="entry-content"><?php the_content(); ?></div>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</div>

<?php
annamleaf_pagination();

annamleaf_band(
	__( 'Want to see it for yourself?', 'annamleaf' ),
	__( 'We host buyer visits to the growing regions and the factory during the season.', 'annamleaf' ),
	__( 'Arrange a visit', 'annamleaf' ),
	annamleaf_contact_url()
);

get_footer();
