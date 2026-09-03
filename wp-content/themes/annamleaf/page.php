<?php
/**
 * A standard page: hero from its own fields, then whatever the editor built.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	annamleaf_page_hero( get_the_ID() );

	$annamleaf_eyebrow = annamleaf_get_meta( get_the_ID(), 'section_eyebrow' );
	$annamleaf_heading = annamleaf_get_meta( get_the_ID(), 'section_heading' );
	$annamleaf_split   = '' !== $annamleaf_eyebrow || '' !== $annamleaf_heading;
	?>
	<section class="sec">
		<div class="wrap <?php echo $annamleaf_split ? 'split split--text' : ''; ?>">
			<?php if ( $annamleaf_split ) : ?>
				<div><?php annamleaf_section_head( $annamleaf_eyebrow, $annamleaf_heading ); ?></div>
			<?php endif; ?>

			<div class="entry-content"><?php the_content(); ?></div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
