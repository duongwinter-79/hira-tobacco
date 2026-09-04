<?php
/**
 * Template Name: Process
 * Template Post Type: page
 *
 * The process page: the page's own hero and intro, then every stage the client has
 * arranged under Process, from field to factory.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

$annamleaf_stages = function_exists( 'annamleaf_get_items' ) ? annamleaf_get_items( 'annam_stage', 30 ) : array();

while ( have_posts() ) :
	the_post();

	annamleaf_page_hero( get_the_ID(), 'curing' );

	if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) :
		?>
		<section class="sec sec--tight">
			<div class="wrap">
				<div class="entry-content"><?php the_content(); ?></div>
			</div>
		</section>
		<?php
	endif;
endwhile;
?>

<div class="wrap">
	<div class="steps">
		<?php foreach ( $annamleaf_stages as $annamleaf_index => $annamleaf_stage ) : ?>
			<article class="stepblk">
				<div class="stepblk-fig">
					<?php
					annamleaf_plate(
						array(
							'post_id'    => $annamleaf_stage->ID,
							'motif'      => annamleaf_motif_for_index( $annamleaf_index + 1 ),
							'photo'      => 'stage-' . ( $annamleaf_index + 1 ),
							'shot_note'  => annamleaf_get_meta( $annamleaf_stage->ID, 'shot_note' ),
							'shot_index' => sprintf( 'PHOTO %02d', $annamleaf_index + 2 ),
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
							esc_html( annamleaf_get_meta( $annamleaf_stage->ID, 'stage_no', sprintf( '%02d', $annamleaf_index + 1 ) ) )
						);
						?>
					</span>
					<h3><?php echo esc_html( get_the_title( $annamleaf_stage ) ); ?></h3>
					<div class="entry-content">
						<?php echo apply_filters( 'the_content', $annamleaf_stage->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content filter. ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>

<?php
annamleaf_band(
	__( 'Want to see it for yourself?', 'annamleaf' ),
	__( 'We host buyer visits to the growing regions and the factory during the season.', 'annamleaf' ),
	__( 'Arrange a visit', 'annamleaf' ),
	annamleaf_contact_url()
);

get_footer();
