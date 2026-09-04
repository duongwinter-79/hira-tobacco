<?php
/**
 * Image frames.
 *
 * Every photograph on the site sits in a "plate", and the frame fills in this order:
 * the post's featured image, then a default photograph bundled with the theme in
 * assets/photos/, then a duotone illustration captioned with the shot that belongs there.
 * So a fresh install looks finished, an unfinished page still reads as designed, and the
 * client's own photography wins the moment it is uploaded.
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
			'photo'      => '',
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

		// A temporary photograph carries its credit on the picture, as its licence requires.
		$credit = function_exists( 'annamleaf_photo_credit' )
			? annamleaf_photo_credit( (int) get_post_thumbnail_id( $post_id ) )
			: '';

		if ( '' !== $credit ) {
			printf(
				'<figcaption class="shotchip shotchip--credit"><span class="n">%s</span><span>%s</span></figcaption>',
				esc_html__( 'TEMPORARY', 'annamleaf' ),
				esc_html( $credit )
			);
		}

		echo '</figure>';

		return;
	}

	// A default photograph shipped with the theme, until the client uploads their own.
	$bundled = '' !== $args['photo'] ? annamleaf_bundled_photo( (string) $args['photo'] ) : null;

	if ( $bundled ) {
		printf( '<figure class="%s">', esc_attr( $classes ) );
		printf(
			'<img src="%s" alt="%s" loading="lazy" decoding="async">',
			esc_url( $bundled['url'] ),
			esc_attr( $args['shot_note'] )
		);

		if ( '' !== $bundled['credit'] ) {
			printf(
				'<figcaption class="shotchip shotchip--credit"><span class="n">%s</span><span>%s</span></figcaption>',
				esc_html__( 'TEMPORARY', 'annamleaf' ),
				esc_html( $bundled['credit'] )
			);
		}

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

/**
 * A default photograph shipped with the theme, if one exists for this frame.
 *
 * Files come from tools/fetch-photos.mjs and live in assets/photos/, with their credits
 * in credits.json beside them.
 *
 * @param string $slot Frame name, e.g. "home" or "stage-4".
 * @return array{url: string, credit: string}|null
 */
function annamleaf_bundled_photo( string $slot ): ?array {
	static $credits = null;

	$slot = sanitize_file_name( $slot );
	$file = get_template_directory() . '/assets/photos/' . $slot . '.jpg';

	if ( ! is_readable( $file ) ) {
		return null;
	}

	if ( null === $credits ) {
		$credits      = array();
		$credits_file = get_template_directory() . '/assets/photos/credits.json';

		if ( is_readable( $credits_file ) ) {
			$decoded = json_decode( (string) file_get_contents( $credits_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme asset.
			$credits = is_array( $decoded ) ? $decoded : array();
		}
	}

	return array(
		'url'    => get_template_directory_uri() . '/assets/photos/' . $slot . '.jpg',
		'credit' => (string) ( $credits[ $slot ]['credit'] ?? '' ),
	);
}
