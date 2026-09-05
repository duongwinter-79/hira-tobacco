<?php
/**
 * The home page.
 *
 * Section order is fixed by design; every word, figure and photograph in it is editable.
 * Hero and intro come from the front page's own fields, the figures from the company
 * profile, and the three content strips from the process stages, products and regions.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

$annamleaf_front_id = (int) get_option( 'page_on_front' );
$annamleaf_stages   = function_exists( 'annamleaf_get_items' ) ? annamleaf_get_items( 'annam_stage', 4 ) : array();
$annamleaf_leaves   = function_exists( 'annamleaf_get_items' ) ? annamleaf_get_items( 'annam_leaf', 4 ) : array();
$annamleaf_regions  = function_exists( 'annamleaf_get_items' ) ? annamleaf_get_items( 'annam_region', 4 ) : array();

annamleaf_hero(
	array(
		'eyebrow'         => annamleaf_get_meta( $annamleaf_front_id, 'hero_eyebrow', __( 'Leaf tobacco · Vietnam', 'annamleaf' ) ),
		'title'           => annamleaf_get_meta( $annamleaf_front_id, 'hero_title', __( 'From our fields to your factory', 'annamleaf' ) ),
		'text'            => annamleaf_get_meta( $annamleaf_front_id, 'hero_text' )
			? annamleaf_get_meta( $annamleaf_front_id, 'hero_text' )
			: sprintf(
				/* translators: 1: company name, 2: growing region. */
				__( '%1$s grows, cures, grades and processes its own leaf in %2$s, Vietnam — one company from the seedbed to the sealed container.', 'annamleaf' ),
				annamleaf_company_name(),
				annamleaf_get_field( 'region', __( 'REGION', 'annamleaf' ) )
			),
		'cta_label'       => annamleaf_get_meta( $annamleaf_front_id, 'hero_cta_label', __( 'Request a quote', 'annamleaf' ) ),
		'cta_url'         => annamleaf_get_meta( $annamleaf_front_id, 'hero_cta_url', annamleaf_contact_url() ),
		'secondary_label' => __( 'See how we work', 'annamleaf' ),
		'secondary_url'   => annamleaf_process_url(),
		'post_id'         => $annamleaf_front_id,
		'motif'           => 'field',
		'photo'           => 'home',
		'shot_note'       => annamleaf_get_meta( $annamleaf_front_id, 'hero_shot_note', __( 'Wide field panorama at first light', 'annamleaf' ) ),
		'shot_index'      => 'PHOTO 01',
	)
);

annamleaf_stats_strip();
?>

<?php if ( $annamleaf_front_id && '' !== trim( (string) get_post_field( 'post_content', $annamleaf_front_id ) ) ) : ?>
	<section class="sec">
		<div class="wrap split split--text">
			<div>
				<?php
				annamleaf_section_head(
					annamleaf_get_meta( $annamleaf_front_id, 'section_eyebrow', __( 'Who we are', 'annamleaf' ) ),
					annamleaf_get_meta( $annamleaf_front_id, 'section_heading', __( 'One company across the whole chain', 'annamleaf' ) )
				);
				?>
			</div>
			<div class="entry-content">
				<?php echo apply_filters( 'the_content', get_post_field( 'post_content', $annamleaf_front_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content filter. ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty( $annamleaf_stages ) ) : ?>
	<section class="sec sec--card">
		<div class="wrap">
			<?php
			annamleaf_section_head(
				__( 'How we work', 'annamleaf' ),
				__( 'From seed to container, under one roof', 'annamleaf' )
			);
			?>
			<div class="grid4">
				<?php foreach ( $annamleaf_stages as $annamleaf_index => $annamleaf_stage ) : ?>
					<article class="tile">
						<?php
						annamleaf_plate(
							array(
								'post_id'   => $annamleaf_stage->ID,
								'motif'     => annamleaf_motif_for_index( $annamleaf_index + 1 ),
								'photo'     => 'stage-' . ( $annamleaf_index + 1 ),
								'shot_note' => annamleaf_get_meta( $annamleaf_stage->ID, 'shot_note' ),
							)
						);
						?>
						<p class="step-no"><?php echo esc_html( annamleaf_get_meta( $annamleaf_stage->ID, 'stage_no', sprintf( '%02d', $annamleaf_index + 1 ) ) ); ?></p>
						<h3><?php echo esc_html( get_the_title( $annamleaf_stage ) ); ?></h3>
						<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $annamleaf_stage->post_content ), 22, '…' ) ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
			<p style="margin-top:28px;">
				<a href="<?php echo esc_url( annamleaf_process_url() ); ?>">
					<?php esc_html_e( 'See every stage →', 'annamleaf' ); ?>
				</a>
			</p>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty( $annamleaf_leaves ) ) : ?>
	<section class="sec">
		<div class="wrap">
			<?php
			annamleaf_section_head(
				__( 'Our leaf', 'annamleaf' ),
				__( 'What we grow and sell', 'annamleaf' )
			);
			?>
			<div class="grid4">
				<?php
				foreach ( $annamleaf_leaves as $annamleaf_leaf_index => $annamleaf_leaf ) {
					get_template_part(
						'template-parts/card',
						'leaf',
						array(
							'post'  => $annamleaf_leaf,
							'index' => $annamleaf_leaf_index + 1,
						)
					);
				}
				?>
			</div>
			<p style="margin-top:28px;">
				<a href="<?php echo esc_url( annamleaf_leaf_url() ); ?>">
					<?php esc_html_e( 'Specifications and crop calendar →', 'annamleaf' ); ?>
				</a>
			</p>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty( $annamleaf_regions ) ) : ?>
	<section class="sec sec--card">
		<div class="wrap split">
			<div>
				<?php
				annamleaf_section_head(
					__( 'Growing regions', 'annamleaf' ),
					__( 'Where the leaf comes from', 'annamleaf' )
				);
				?>
				<ul class="flist" style="margin-top:26px;">
					<?php foreach ( $annamleaf_regions as $annamleaf_region ) : ?>
						<li>
							<span class="k"><?php echo esc_html( get_the_title( $annamleaf_region ) ); ?></span>
							<span class="v">
								<?php
								$annamleaf_parts = array_filter(
									array(
										annamleaf_get_meta( $annamleaf_region->ID, 'area_ha' ),
										annamleaf_get_meta( $annamleaf_region->ID, 'leaf_types' ),
										annamleaf_get_meta( $annamleaf_region->ID, 'harvest' ),
									)
								);
								echo esc_html( implode( ' · ', $annamleaf_parts ) );
								?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
			annamleaf_plate(
				array(
					'post_id'   => $annamleaf_regions[0]->ID,
					'motif'     => 'field',
					'photo'     => 'region',
					'shot_note' => annamleaf_get_meta(
						$annamleaf_regions[0]->ID,
						'shot_note',
						__( 'The growing area seen wide, or a drone shot of one valley', 'annamleaf' )
					),
				)
			);
			?>
		</div>
	</section>
<?php endif; ?>

<?php
annamleaf_band(
	__( 'Tell us what your blend needs.', 'annamleaf' ),
	__( 'Send the type, grade and volume — we reply with samples and an offer within five working days.', 'annamleaf' ),
	__( 'Request a quote', 'annamleaf' ),
	annamleaf_contact_url()
);

get_footer();
