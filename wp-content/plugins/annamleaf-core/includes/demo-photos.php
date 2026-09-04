<?php
/**
 * Temporary photography.
 *
 * The client has no photographs yet, so this fetches freely licensed ones from Wikimedia
 * Commons and sets them as featured images, to show the design with real pictures in it.
 * Every imported file is tagged, credited on the page, and removable in one click — these
 * are a stand-in for the shoot in docs/shot-list.md, never the finished site.
 *
 * Nothing is hard-coded to a file name: the importer searches Commons at run time, so it
 * keeps working as the site's photo library changes.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

const ANNAMLEAF_DEMO_META = '_annamleaf_demo_photo';
const ANNAMLEAF_CREDIT_META = '_annamleaf_credit';

/**
 * What to fetch, and where each picture belongs.
 *
 * Several search terms per slot: the first that returns a usable image wins.
 *
 * @return array<int, array{slot: string, index: int, queries: array<int, string>}>
 */
function annamleaf_demo_photo_slots(): array {
	return array(
		array(
			'slot'    => 'page',
			'index'   => 0,
			'queries' => array( 'Cao Bang Vietnam landscape', 'Cao Bang province', 'Vietnam rice terraces mountains' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 1,
			'queries' => array( 'tobacco seedlings', 'tobacco nursery', 'seedling tray greenhouse' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 2,
			'queries' => array( 'tobacco field', 'tobacco plantation', 'Nicotiana tabacum field' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 3,
			'queries' => array( 'tobacco harvest', 'tobacco harvesting', 'tobacco leaves picking' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 4,
			'queries' => array( 'tobacco curing barn', 'tobacco drying barn', 'tobacco leaves drying' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 5,
			'queries' => array( 'tobacco leaves sorting', 'tobacco grading', 'dried tobacco leaves' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 6,
			'queries' => array( 'tobacco factory', 'tobacco processing plant', 'tobacco warehouse' ),
		),
		array(
			'slot'    => 'stage',
			'index'   => 7,
			'queries' => array( 'shipping container terminal', 'container port loading', 'cargo container ship' ),
		),
	);
}

/**
 * Ask Wikimedia Commons for a freely licensed photograph.
 *
 * @param string $query Search text.
 * @return array{url: string, title: string, credit: string}|null
 */
function annamleaf_search_commons( string $query ): ?array {
	$endpoint = add_query_arg(
		array(
			'action'      => 'query',
			'format'      => 'json',
			'generator'   => 'search',
			'gsrsearch'   => $query . ' filetype:bitmap',
			'gsrnamespace' => 6,
			'gsrlimit'    => 8,
			'prop'        => 'imageinfo',
			'iiprop'      => 'url|size|extmetadata',
			'iiurlwidth'  => 2000,
		),
		'https://commons.wikimedia.org/w/api.php'
	);

	$response = wp_remote_get(
		$endpoint,
		array(
			'timeout'    => 20,
			'user-agent' => 'AnnamLeafDemoPhotos/1.0 (WordPress site setup)',
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || empty( $data['query']['pages'] ) ) {
		return null;
	}

	foreach ( $data['query']['pages'] as $page ) {
		$info = $page['imageinfo'][0] ?? null;

		if ( ! $info || empty( $info['thumburl'] ) ) {
			continue;
		}

		// Landscape and big enough to fill a hero; skip diagrams and portrait scans.
		$width  = (int) ( $info['width'] ?? 0 );
		$height = (int) ( $info['height'] ?? 0 );

		if ( $width < 1200 || $height < 1 || ( $width / $height ) < 1.2 ) {
			continue;
		}

		$meta    = $info['extmetadata'] ?? array();
		$artist  = wp_strip_all_tags( (string) ( $meta['Artist']['value'] ?? '' ) );
		$licence = wp_strip_all_tags( (string) ( $meta['LicenseShortName']['value'] ?? 'Wikimedia Commons' ) );

		return array(
			'url'    => (string) $info['thumburl'],
			'title'  => (string) ( $page['title'] ?? $query ),
			'credit' => trim( ( '' !== $artist ? $artist . ' · ' : '' ) . $licence . ' · Wikimedia Commons' ),
		);
	}

	return null;
}

/**
 * Download one picture into the media library.
 *
 * @param array{url: string, title: string, credit: string} $photo Photo to import.
 * @param int                                               $post_id Post to attach it to.
 * @return int Attachment ID, or 0.
 */
function annamleaf_sideload_photo( array $photo, int $post_id ): int {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $photo['url'], 30 );

	if ( is_wp_error( $tmp ) ) {
		return 0;
	}

	$name = sanitize_file_name( str_replace( 'File:', '', $photo['title'] ) );

	if ( ! preg_match( '/\.(jpe?g|png|webp)$/i', $name ) ) {
		$name .= '.jpg';
	}

	$attachment_id = media_handle_sideload(
		array(
			'name'     => $name,
			'tmp_name' => $tmp,
		),
		$post_id,
		null,
		array( 'post_excerpt' => $photo['credit'] )
	);

	if ( is_wp_error( $attachment_id ) ) {
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		return 0;
	}

	update_post_meta( $attachment_id, ANNAMLEAF_DEMO_META, '1' );
	update_post_meta( $attachment_id, ANNAMLEAF_CREDIT_META, $photo['credit'] );

	return (int) $attachment_id;
}

/**
 * The post each slot belongs to.
 *
 * @param array{slot: string, index: int} $slot Slot definition.
 * @return int Post ID, or 0.
 */
function annamleaf_slot_post( array $slot ): int {
	if ( 'page' === $slot['slot'] ) {
		return (int) get_option( 'page_on_front' );
	}

	$stages = annamleaf_get_items( 'annam_stage', 30 );
	$stage  = $stages[ $slot['index'] - 1 ] ?? null;

	return $stage ? (int) $stage->ID : 0;
}

/**
 * Import the temporary photographs.
 *
 * @return array{imported: int, skipped: int}
 */
function annamleaf_import_demo_photos(): array {
	$imported = 0;
	$skipped  = 0;

	foreach ( annamleaf_demo_photo_slots() as $slot ) {
		$post_id = annamleaf_slot_post( $slot );

		if ( ! $post_id || has_post_thumbnail( $post_id ) ) {
			$skipped++;
			continue;
		}

		$photo = null;

		foreach ( $slot['queries'] as $query ) {
			$photo = annamleaf_search_commons( $query );

			if ( $photo ) {
				break;
			}
		}

		if ( ! $photo ) {
			$skipped++;
			continue;
		}

		$attachment_id = annamleaf_sideload_photo( $photo, $post_id );

		if ( ! $attachment_id ) {
			$skipped++;
			continue;
		}

		set_post_thumbnail( $post_id, $attachment_id );
		$imported++;
	}

	return array(
		'imported' => $imported,
		'skipped'  => $skipped,
	);
}

/**
 * How many temporary photographs are in the library.
 *
 * @return int
 */
function annamleaf_count_demo_photos(): int {
	$ids = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 100,
			'fields'      => 'ids',
			'meta_key'    => ANNAMLEAF_DEMO_META,
			'meta_value'  => '1',
		)
	);

	return is_array( $ids ) ? count( $ids ) : 0;
}

/**
 * Delete every temporary photograph and the thumbnails pointing at them.
 *
 * @return int How many were removed.
 */
function annamleaf_remove_demo_photos(): int {
	$ids = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 100,
			'fields'      => 'ids',
			'meta_key'    => ANNAMLEAF_DEMO_META,
			'meta_value'  => '1',
		)
	);

	if ( ! is_array( $ids ) || empty( $ids ) ) {
		return 0;
	}

	$removed = 0;

	foreach ( $ids as $attachment_id ) {
		$parents = get_posts(
			array(
				'post_type'   => 'any',
				'post_status' => 'any',
				'numberposts' => 20,
				'fields'      => 'ids',
				'meta_key'    => '_thumbnail_id',
				'meta_value'  => (string) $attachment_id,
			)
		);

		foreach ( (array) $parents as $parent_id ) {
			delete_post_thumbnail( (int) $parent_id );
		}

		if ( wp_delete_attachment( (int) $attachment_id, true ) ) {
			$removed++;
		}
	}

	return $removed;
}

/**
 * Handle the import button.
 */
function annamleaf_handle_import_photos(): void {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'annamleaf-core' ) );
	}

	check_admin_referer( 'annamleaf_demo_photos' );

	$result = annamleaf_import_demo_photos();

	wp_safe_redirect(
		add_query_arg(
			array(
				'annamleaf_photos'  => 'imported',
				'annamleaf_count'   => (int) $result['imported'],
				'annamleaf_skipped' => (int) $result['skipped'],
			),
			admin_url( 'admin.php?page=annamleaf-settings' )
		)
	);
	exit;
}
add_action( 'admin_post_annamleaf_import_photos', 'annamleaf_handle_import_photos' );

/**
 * Handle the remove button.
 */
function annamleaf_handle_remove_photos(): void {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'annamleaf-core' ) );
	}

	check_admin_referer( 'annamleaf_demo_photos' );

	$removed = annamleaf_remove_demo_photos();

	wp_safe_redirect(
		add_query_arg(
			array(
				'annamleaf_photos' => 'removed',
				'annamleaf_count'  => $removed,
			),
			admin_url( 'admin.php?page=annamleaf-settings' )
		)
	);
	exit;
}
add_action( 'admin_post_annamleaf_remove_photos', 'annamleaf_handle_remove_photos' );

/**
 * The credit line stored with an imported photograph, if it is one.
 *
 * @param int $attachment_id Attachment.
 * @return string
 */
function annamleaf_photo_credit( int $attachment_id ): string {
	return (string) get_post_meta( $attachment_id, ANNAMLEAF_CREDIT_META, true );
}
