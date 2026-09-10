<?php
/**
 * Template Name: Leaf portfolio
 * Template Post Type: page
 *
 * The leaf page: hero, the products as cards and as a specification table, then
 * whatever else the client writes on the page — delivery forms, crop calendar.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

$annamleaf_items = function_exists( 'annamleaf_get_items' ) ? annamleaf_get_items( 'annam_leaf', 30 ) : array();

while ( have_posts() ) :
	the_post();
	annamleaf_page_hero( get_the_ID(), 'harvest' );
endwhile;
?>

<?php if ( ! empty( $annamleaf_items ) ) : ?>
	<section class="sec">
		<div class="wrap">
			<div class="grid4">
				<?php
				foreach ( $annamleaf_items as $annamleaf_item_index => $annamleaf_item ) {
					get_template_part(
						'template-parts/card',
						'leaf',
						array(
							'post'  => $annamleaf_item,
							'index' => $annamleaf_item_index + 1,
						)
					);
				}
				?>
			</div>
		</div>
	</section>

<?php endif; ?>

<?php if ( '' !== trim( wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ) ) ) : ?>
	<section class="sec">
		<div class="wrap">
			<div class="entry-content"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content filter. ?></div>
		</div>
	</section>
<?php endif; ?>

<?php
annamleaf_band(
	__( 'Need a grade that is not listed?', 'annamleaf' ),
	__( 'Tell us the specification you buy against and we will tell you what this crop can match.', 'annamleaf' ),
	__( 'Request a quote', 'annamleaf' ),
	annamleaf_contact_url()
);

get_footer();
