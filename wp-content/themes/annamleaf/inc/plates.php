<?php
/**
 * Image frames.
 *
 * Every photograph on the site sits in a "plate". Until the client uploads the real
 * photograph, the plate draws a duotone illustration and captions it with the shot that
 * belongs there — so an unfinished page still reads as designed, and the gap is legible.
 * Set a featured image and the photograph takes over the same frame.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * The motif each stage of the process falls back to, in running order.
 *
 * @param int $index 1-based position.
 * @return string
 */
function annamleaf_motif_for_index( int $index ): string {
	$motifs = array( 'nursery', 'field', 'harvest', 'curing', 'grading', 'plant', 'ship' );
	$key    = ( $index - 1 ) % count( $motifs );

	return $motifs[ max( 0, $key ) ];
}

/**
 * Print one image frame.
 *
 * @param array{
 *     post_id?: int,
 *     motif?: string,
 *     shot_note?: string,
 *     shot_index?: string,
 *     class?: string,
 *     size?: string
 * } $args Frame arguments.
 */
function annamleaf_plate( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'post_id'    => 0,
			'motif'      => 'field',
			'shot_note'  => '',
			'shot_index' => '',
			'class'      => '',
			'size'       => 'annamleaf-plate',
		)
	);

	$post_id = (int) $args['post_id'];
	$motif   = sanitize_key( $args['motif'] );
	$classes = trim( 'plate p-' . $motif . ' ' . $args['class'] );

	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		printf( '<figure class="%s">', esc_attr( $classes ) );
		echo get_the_post_thumbnail(
			$post_id,
			$args['size'],
			array(
				'loading' => 'lazy',
				'alt'     => esc_attr( get_the_title( $post_id ) ),
			)
		);
		echo '</figure>';

		return;
	}

	printf( '<figure class="%s plate--empty">', esc_attr( $classes ) );
	printf(
		'<svg class="motif" viewBox="0 0 400 240" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false"><use href="#m-%s"></use></svg>',
		esc_attr( $motif )
	);

	$note = trim( (string) $args['shot_note'] );

	if ( '' !== $note ) {
		echo '<figcaption class="shotchip">';

		if ( '' !== $args['shot_index'] ) {
			printf( '<span class="n">%s</span>', esc_html( $args['shot_index'] ) );
		}

		printf( '<span>%s</span>', esc_html( $note ) );
		echo '</figcaption>';
	}

	echo '</figure>';
}

/**
 * Print the SVG sprite the plates reference. Called once, at the top of the body.
 */
function annamleaf_svg_sprite(): void {
	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;
	$file    = get_template_directory() . '/assets/sprite.svg';

	if ( ! is_readable( $file ) ) {
		return;
	}

	$sprite = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme asset.

	if ( false === $sprite ) {
		return;
	}

	echo wp_kses(
		$sprite,
		array(
			'svg'     => array( 'width' => true, 'height' => true, 'style' => true, 'aria-hidden' => true, 'focusable' => true, 'viewbox' => true, 'xmlns' => true ),
			'symbol'  => array( 'id' => true, 'viewbox' => true ),
			'g'       => array( 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-dasharray' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'opacity' => true, 'fill-opacity' => true, 'transform' => true ),
			'path'    => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-dasharray' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'opacity' => true, 'fill-opacity' => true, 'stroke-opacity' => true, 'fill-rule' => true, 'transform' => true ),
			'rect'    => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true, 'opacity' => true, 'transform' => true ),
			'circle'  => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'opacity' => true, 'transform' => true ),
			'ellipse' => array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'opacity' => true, 'transform' => true ),
		)
	);
}

/**
 * Print the leaf mark used in the wordmark and the leaf cards.
 *
 * @param string $class CSS class for the svg element.
 */
function annamleaf_leaf_mark( string $class = 'mark' ): void {
	printf(
		'<svg class="%s" viewBox="0 0 40 52" aria-hidden="true" focusable="false"><use href="#m-leaf"></use></svg>',
		esc_attr( $class )
	);
}
