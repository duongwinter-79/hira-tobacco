<?php
/**
 * The leaf portfolio: the cards, then the same records as a specification table.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

$annamleaf_items = function_exists( 'annamleaf_get_items' ) ? annamleaf_get_items( 'annam_leaf', 30 ) : array();

annamleaf_hero(
	array(
		'eyebrow'   => __( 'Our leaf', 'annamleaf' ),
		'title'     => __( 'Types, specifications and packing', 'annamleaf' ),
		'text'      => __( 'Samples on request. Grades are matched against your own reference samples before shipment.', 'annamleaf' ),
		'motif'     => 'harvest',
		'shot_note' => __( 'Graded leaf laid out by type', 'annamleaf' ),
		'compact'   => true,
	)
);
?>

<?php if ( ! empty( $annamleaf_items ) ) : ?>
	<section class="sec">
		<div class="wrap">
			<div class="grid4">
				<?php
				foreach ( $annamleaf_items as $annamleaf_item ) {
					get_template_part( 'template-parts/card', 'leaf', array( 'post' => $annamleaf_item ) );
				}
				?>
			</div>
		</div>
	</section>

	<section class="sec sec--card">
		<div class="wrap">
			<?php
			annamleaf_section_head(
				__( 'Specifications', 'annamleaf' ),
				__( 'The same leaf, as a spec sheet', 'annamleaf' )
			);
			?>
			<div class="scroller">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Type', 'annamleaf' ); ?></th>
							<th><?php esc_html_e( 'Curing', 'annamleaf' ); ?></th>
							<th><?php esc_html_e( 'Grades', 'annamleaf' ); ?></th>
							<th><?php esc_html_e( 'Moisture', 'annamleaf' ); ?></th>
							<th><?php esc_html_e( 'Packing', 'annamleaf' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $annamleaf_items as $annamleaf_item ) : ?>
							<tr>
								<td><strong><?php echo esc_html( get_the_title( $annamleaf_item ) ); ?></strong></td>
								<?php
								foreach ( array( 'curing', 'grades', 'moisture', 'packing' ) as $annamleaf_key ) :
									$annamleaf_value = annamleaf_get_meta( $annamleaf_item->ID, $annamleaf_key );
									?>
									<td class="<?php echo 'moisture' === $annamleaf_key ? 'num' : ''; ?>">
										<?php echo '' !== $annamleaf_value ? esc_html( $annamleaf_value ) : wp_kses_post( annamleaf_ph( 'TBC' ) ); ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>
<?php else : ?>
	<section class="sec">
		<div class="wrap">
			<p class="lede"><?php esc_html_e( 'No leaf types have been added yet.', 'annamleaf' ); ?></p>
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
