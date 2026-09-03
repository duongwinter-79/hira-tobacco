<?php
/**
 * A single record — a process stage, a leaf type or a news post.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	annamleaf_hero(
		array(
			'eyebrow'   => get_post_type_object( get_post_type() )->labels->singular_name ?? '',
			'title'     => get_the_title(),
			'post_id'   => get_the_ID(),
			'motif'     => 'plant',
			'shot_note' => annamleaf_get_meta( get_the_ID(), 'shot_note' ),
			'compact'   => true,
		)
	);
	?>
	<section class="sec">
		<div class="wrap">
			<div class="entry-content"><?php the_content(); ?></div>

			<?php
			$annamleaf_overview = match ( get_post_type() ) {
				'annam_stage' => annamleaf_process_url(),
				'annam_leaf'  => annamleaf_leaf_url(),
				default       => '',
			};
			?>
			<?php if ( '' !== $annamleaf_overview ) : ?>
				<p style="margin-top:30px;">
					<a href="<?php echo esc_url( $annamleaf_overview ); ?>">
						<?php esc_html_e( '← Back to the overview', 'annamleaf' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;

get_footer();
