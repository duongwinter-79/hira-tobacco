<?php
/**
 * Copy the featured images from this install into the theme, so they ship as the defaults.
 *
 * The pictures a client uploads live in wp-content/uploads/, which is not in the repository.
 * This walks the seeded records, finds the image attached to each one, and writes it into
 * wp-content/themes/annamleaf/assets/photos/ under the slot name the templates look for.
 * That folder IS in the repository, so committing it makes those pictures the defaults for
 * every future install — no media library, no database rows.
 *
 * Run it through WP-CLI in the container, which has the repository mounted:
 *
 *     docker compose run --rm cli wp eval-file /repo/tools/export-photos.php
 *     docker compose run --rm cli wp eval-file /repo/tools/export-photos.php --dry-run
 *
 * Then look at what landed in assets/photos/ and commit it.
 *
 * @package AnnamLeafTools
 */

defined( 'ABSPATH' ) || exit;

$annamleaf_dry_run = in_array( '--dry-run', (array) ( $args ?? array() ), true )
	|| in_array( '--dry-run', (array) ( $_SERVER['argv'] ?? array() ), true );

$annamleaf_dir = get_theme_root() . '/annamleaf/assets/photos';

if ( ! is_dir( $annamleaf_dir ) && ! wp_mkdir_p( $annamleaf_dir ) ) {
	WP_CLI::error( 'Cannot write to ' . $annamleaf_dir );
}

/**
 * Which record fills which slot: the front page, then each list in menu order.
 *
 * @return array<string, int> Slot name to post ID.
 */
$annamleaf_slots = static function (): array {
	$slots = array();
	$front = (int) get_option( 'page_on_front' );

	if ( $front ) {
		$slots['home'] = $front;
	}

	$lists = array(
		'annam_stage'  => 'stage-',
		'annam_leaf'   => 'leaf-',
		'annam_region' => 'region',
	);

	foreach ( $lists as $post_type => $prefix ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => 20,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $index => $post ) {
			// There is one region frame, so only the first region has a slot.
			if ( 'region' === $prefix ) {
				if ( 0 === $index ) {
					$slots['region'] = (int) $post->ID;
				}
				continue;
			}

			$slots[ $prefix . ( $index + 1 ) ] = (int) $post->ID;
		}
	}

	return $slots;
};

$annamleaf_saved   = 0;
$annamleaf_missing = array();

foreach ( $annamleaf_slots() as $annamleaf_slot => $annamleaf_post_id ) {
	$annamleaf_attachment = (int) get_post_thumbnail_id( $annamleaf_post_id );

	if ( ! $annamleaf_attachment ) {
		$annamleaf_missing[] = $annamleaf_slot . ' (' . get_the_title( $annamleaf_post_id ) . ')';
		continue;
	}

	$annamleaf_source = get_attached_file( $annamleaf_attachment );

	if ( ! $annamleaf_source || ! is_readable( $annamleaf_source ) ) {
		WP_CLI::warning( $annamleaf_slot . ': attachment file missing on disk' );
		continue;
	}

	$annamleaf_extension = strtolower( pathinfo( $annamleaf_source, PATHINFO_EXTENSION ) );

	if ( ! in_array( $annamleaf_extension, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
		WP_CLI::warning( $annamleaf_slot . ': ' . $annamleaf_extension . ' is not a format the theme reads' );
		continue;
	}

	$annamleaf_target = $annamleaf_dir . '/' . $annamleaf_slot . '.' . $annamleaf_extension;
	$annamleaf_size   = size_format( (int) filesize( $annamleaf_source ) );

	if ( $annamleaf_dry_run ) {
		WP_CLI::log( sprintf( '%-10s would write %s (%s)', $annamleaf_slot, basename( $annamleaf_target ), $annamleaf_size ) );
		continue;
	}

	// An earlier export may have used a different format for this slot.
	foreach ( array( 'jpg', 'jpeg', 'png', 'webp' ) as $annamleaf_other ) {
		$annamleaf_stale = $annamleaf_dir . '/' . $annamleaf_slot . '.' . $annamleaf_other;

		if ( $annamleaf_stale !== $annamleaf_target && file_exists( $annamleaf_stale ) ) {
			unlink( $annamleaf_stale ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme asset.
		}
	}

	if ( ! copy( $annamleaf_source, $annamleaf_target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme asset.
		WP_CLI::warning( $annamleaf_slot . ': copy failed' );
		continue;
	}

	WP_CLI::log( sprintf( '%-10s %s (%s)', $annamleaf_slot, basename( $annamleaf_target ), $annamleaf_size ) );
	$annamleaf_saved++;
}

if ( $annamleaf_missing ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Chưa có featured image: ' . implode( ', ', $annamleaf_missing ) );
}

WP_CLI::log( '' );
WP_CLI::success(
	$annamleaf_dry_run
		? 'Dry run — chưa ghi file nào.'
		: $annamleaf_saved . ' ảnh đã ghi vào wp-content/themes/annamleaf/assets/photos/. Xem lại rồi commit.'
);
