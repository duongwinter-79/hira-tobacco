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
 * The one host imported pictures may come from.
 */
const ANNAMLEAF_PHOTO_HOST = 'upload.wikimedia.org';

/**
 * Ask Wikimedia Commons for freely licensed photographs.
 *
 * Results are cached for an hour: the preview screen re-runs every search on each load.
 *
 * @param string $query Search text.
 * @param int    $limit How many candidates to return.
 * @return array<int, array{url: string, thumb: string, title: string, credit: string}>
 */
function annamleaf_search_commons_candidates( string $query, int $limit = 3 ): array {
	$cache_key = 'annamleaf_photos_' . md5( $query . '|' . $limit );
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$found = annamleaf_query_commons( $query, $limit );
	set_transient( $cache_key, $found, HOUR_IN_SECONDS );

	return $found;
}

/**
 * The first usable candidate for a search, or null.
 *
 * @param string $query Search text.
 * @return array{url: string, thumb: string, title: string, credit: string}|null
 */
function annamleaf_search_commons( string $query ): ?array {
	$found = annamleaf_search_commons_candidates( $query, 3 );

	return $found[0] ?? null;
}

/**
 * Run one Commons search.
 *
 * @param string $query Search text.
 * @param int    $limit How many candidates to return.
 * @return array<int, array{url: string, thumb: string, title: string, credit: string}>
 */
function annamleaf_query_commons( string $query, int $limit ): array {
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
		return array();
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || empty( $data['query']['pages'] ) ) {
		return array();
	}

	$found = array();

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

		$found[] = array(
			'url'    => (string) $info['thumburl'],
			'thumb'  => (string) preg_replace( '#/(\d+)px-#', '/480px-', (string) $info['thumburl'] ),
			'title'  => (string) ( $page['title'] ?? $query ),
			'credit' => trim( ( '' !== $artist ? $artist . ' · ' : '' ) . $licence . ' · Wikimedia Commons' ),
		);

		if ( count( $found ) >= $limit ) {
			break;
		}
	}

	return $found;
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

	if ( ANNAMLEAF_PHOTO_HOST !== wp_parse_url( $photo['url'], PHP_URL_HOST ) ) {
		return 0;
	}

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

/**
 * A readable name for a slot, taken from the post it fills.
 *
 * @param array{slot: string, index: int} $slot Slot definition.
 * @return string
 */
function annamleaf_slot_label( array $slot ): string {
	if ( 'page' === $slot['slot'] ) {
		return __( 'Home page cover', 'annamleaf-core' );
	}

	$post_id = annamleaf_slot_post( $slot );

	return $post_id
		? sprintf(
			/* translators: 1: stage number, 2: stage title. */
			__( 'Stage %1$02d — %2$s', 'annamleaf-core' ),
			$slot['index'],
			get_the_title( $post_id )
		)
		: sprintf( __( 'Stage %02d', 'annamleaf-core' ), $slot['index'] );
}

/**
 * Candidates for one slot, drawn from its search terms in order.
 *
 * @param array{queries: array<int, string>} $slot Slot definition.
 * @param int                                $want How many to show.
 * @return array<int, array{url: string, thumb: string, title: string, credit: string}>
 */
function annamleaf_slot_candidates( array $slot, int $want = 3 ): array {
	$candidates = array();

	foreach ( $slot['queries'] as $query ) {
		foreach ( annamleaf_search_commons_candidates( $query, $want ) as $photo ) {
			$candidates[ $photo['url'] ] = $photo;

			if ( count( $candidates ) >= $want ) {
				return array_values( $candidates );
			}
		}
	}

	return array_values( $candidates );
}

/**
 * Add the preview screen under the Company profile menu.
 */
function annamleaf_photos_menu(): void {
	add_submenu_page(
		'annamleaf-settings',
		__( 'Photo preview', 'annamleaf-core' ),
		__( 'Photo preview', 'annamleaf-core' ),
		'upload_files',
		'annamleaf-photos',
		'annamleaf_render_photos_page'
	);
}
add_action( 'admin_menu', 'annamleaf_photos_menu' );

/**
 * Show what the importer would fetch, and let the editor pick a different picture.
 */
function annamleaf_render_photos_page(): void {
	if ( ! current_user_can( 'upload_files' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Photo preview', 'annamleaf-core' ); ?></h1>

		<?php if ( isset( $_GET['annamleaf_picked'] ) ) : ?>
			<div class="notice notice-<?php echo '1' === $_GET['annamleaf_picked'] ? 'success' : 'error'; ?> is-dismissible">
				<p>
					<?php
					echo '1' === $_GET['annamleaf_picked']
						? esc_html__( 'Picture imported and set on that frame.', 'annamleaf-core' )
						: esc_html__( 'Could not import that picture. Check that the server can reach commons.wikimedia.org.', 'annamleaf-core' );
					?>
				</p>
			</div>
		<?php endif; ?>
		<p class="description" style="max-width:62em;">
			<?php esc_html_e( 'These are the freely licensed pictures Wikimedia Commons offers for each empty frame. Pick one per frame, or use "Import temporary photographs" on the Company profile screen to take the first of each automatically. Everything here is a stand-in until the real shoot arrives.', 'annamleaf-core' ); ?>
		</p>

		<?php foreach ( annamleaf_demo_photo_slots() as $slot ) : ?>
			<?php
			$post_id    = annamleaf_slot_post( $slot );
			$candidates = annamleaf_slot_candidates( $slot );
			?>
			<h2 style="margin-top:28px;"><?php echo esc_html( annamleaf_slot_label( $slot ) ); ?></h2>

			<?php if ( ! $post_id ) : ?>
				<p><em><?php esc_html_e( 'No post for this frame yet — rebuild the default content first.', 'annamleaf-core' ); ?></em></p>
				<?php continue; ?>
			<?php endif; ?>

			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<p>
					<strong><?php esc_html_e( 'In use now:', 'annamleaf-core' ); ?></strong>
					<?php echo get_the_post_thumbnail( $post_id, array( 160, 110 ), array( 'style' => 'vertical-align:middle;margin-left:8px;' ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( empty( $candidates ) ) : ?>
				<p><em><?php esc_html_e( 'Nothing found, or Commons could not be reached from this server.', 'annamleaf-core' ); ?></em></p>
			<?php else : ?>
				<div style="display:flex;gap:16px;flex-wrap:wrap;">
					<?php foreach ( $candidates as $photo ) : ?>
						<div style="width:250px;border:1px solid #dcdcde;background:#fff;padding:10px;">
							<img src="<?php echo esc_url( $photo['thumb'] ); ?>" alt="" style="width:100%;height:150px;object-fit:cover;display:block;">
							<p style="font-size:11px;color:#646970;margin:8px 0;word-break:break-word;">
								<?php echo esc_html( $photo['credit'] ); ?>
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="annamleaf_pick_photo">
								<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
								<input type="hidden" name="url" value="<?php echo esc_attr( $photo['url'] ); ?>">
								<input type="hidden" name="title" value="<?php echo esc_attr( $photo['title'] ); ?>">
								<input type="hidden" name="credit" value="<?php echo esc_attr( $photo['credit'] ); ?>">
								<?php wp_nonce_field( 'annamleaf_demo_photos' ); ?>
								<button type="submit" class="button button-small"><?php esc_html_e( 'Use this one', 'annamleaf-core' ); ?></button>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Import the picture the editor chose on the preview screen.
 */
function annamleaf_handle_pick_photo(): void {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'annamleaf-core' ) );
	}

	check_admin_referer( 'annamleaf_demo_photos' );

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$photo   = array(
		'url'    => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
		'title'  => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
		'credit' => isset( $_POST['credit'] ) ? sanitize_text_field( wp_unslash( $_POST['credit'] ) ) : '',
	);

	$attachment_id = ( $post_id && $photo['url'] ) ? annamleaf_sideload_photo( $photo, $post_id ) : 0;

	if ( $attachment_id ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'annamleaf_picked' => $attachment_id ? '1' : '0',
			),
			admin_url( 'admin.php?page=annamleaf-photos' )
		)
	);
	exit;
}
add_action( 'admin_post_annamleaf_pick_photo', 'annamleaf_handle_pick_photo' );
