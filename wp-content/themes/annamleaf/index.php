<?php
/**
 * Fallback template: the news listing, and anything without a more specific template.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

annamleaf_hero(
	array(
		'title'   => is_home() ? __( 'News', 'annamleaf' ) : wp_strip_all_tags( get_the_archive_title() ),
		'motif'   => 'field',
		'compact' => true,
	)
);
?>

<section class="sec">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="grid3">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article class="tile">
						<?php annamleaf_plate( array( 'post_id' => get_the_ID(), 'motif' => 'field' ) ); ?>
						<p class="step-no"><?php echo esc_html( get_the_date() ); ?></p>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</article>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p class="lede"><?php esc_html_e( 'Nothing published here yet.', 'annamleaf' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
annamleaf_pagination();
get_footer();
