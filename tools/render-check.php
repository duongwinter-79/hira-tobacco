<?php
/**
 * Development aid: render the theme templates without a WordPress install.
 *
 * Stubs the WordPress functions the theme calls, feeds it a fake set of process stages
 * and leaf types, and writes the resulting HTML to tools/output/. It catches the mistakes
 * that would otherwise only show up on a live install — a mistyped function name, a bad
 * argument, a template that fatals — and lets the markup be reviewed in a browser.
 *
 * Not part of the shipped theme. Run it from the repository root:
 *
 *     php tools/render-check.php
 *
 * @package AnnamLeaf
 */

define( 'ABSPATH', __DIR__ . '/../' );

$GLOBALS['annamleaf_posts']   = array();
$GLOBALS['annamleaf_cursor']  = -1;
$GLOBALS['annamleaf_current'] = null;
$GLOBALS['annamleaf_meta']    = array();

// ---------------------------------------------------------------- fake records

/**
 * Build one fake post.
 *
 * @param int    $id      ID.
 * @param string $type    Post type.
 * @param string $title   Title.
 * @param string $content Body.
 * @param array  $meta    Meta, keyed without the prefix.
 * @return stdClass
 */
function annamleaf_fake( int $id, string $type, string $title, string $content = '', array $meta = array() ): stdClass {
	$post               = new stdClass();
	$post->ID           = $id;
	$post->post_type    = $type;
	$post->post_title   = $title;
	$post->post_content = $content;
	$post->post_excerpt = '';

	foreach ( $meta as $key => $value ) {
		$GLOBALS['annamleaf_meta'][ $id ][ '_annamleaf_' . $key ] = $value;
	}

	return $post;
}

$stages = array();

foreach ( array( 'Seed & nursery', 'Fields & farmer training', 'Harvest', 'Curing', 'Buying & grading', 'Threshing, redrying & baling', 'Testing, storage & export' ) as $i => $title ) {
	$stages[] = annamleaf_fake(
		100 + $i,
		'annam_stage',
		$title,
		'<p>Placeholder body copy for the stage, long enough to show how the column reads against the illustration beside it.</p>',
		array(
			'stage_no'  => sprintf( '%02d', $i + 1 ),
			'shot_note' => 'Photo brief for ' . strtolower( $title ),
		)
	);
}

$leaves = array();

foreach ( array( 'Flue-cured Virginia' => 'Virginia sấy lò', 'Burley' => 'Burley sấy gió', 'Oriental' => 'Oriental phơi nắng', 'Dark air-cured' => 'Lá sẫm sấy gió' ) as $i => $title ) {
	$index    = count( $leaves );
	$leaves[] = annamleaf_fake(
		200 + $index,
		'annam_leaf',
		$i,
		'<p>Short description of the leaf type.</p>',
		array( 'vi_name' => $title, 'curing' => 'Flue-cured' )
	);
}

$regions = array(
	annamleaf_fake( 300, 'annam_region', 'Growing region 1', '', array( 'leaf_types' => 'Leaf type to confirm', 'harvest' => 'Harvest months to confirm' ) ),
	annamleaf_fake( 301, 'annam_region', 'Growing region 2', '', array( 'leaf_types' => 'Leaf type to confirm', 'harvest' => 'Harvest months to confirm' ) ),
);

$GLOBALS['annamleaf_fixtures'] = array(
	'annam_stage'  => $stages,
	'annam_leaf'   => $leaves,
	'annam_region' => $regions,
);

// ---------------------------------------------------------------- WordPress stubs

function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $t ) { return esc_html( $t ); }
function esc_url( $t ) { return esc_html( $t ); }
function esc_url_raw( $t ) { return (string) $t; }
function esc_textarea( $t ) { return esc_html( $t ); }
function __( $t, $d = '' ) { return $t; }
function _e( $t, $d = '' ) { echo $t; }
function esc_html__( $t, $d = '' ) { return esc_html( $t ); }
function esc_attr__( $t, $d = '' ) { return esc_attr( $t ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function wp_kses_post( $t ) { return (string) $t; }
function wp_kses( $t, $allowed ) { return (string) $t; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return sanitize_text_field( $v ); }
function sanitize_email( $v ) { return (string) $v; }
function sanitize_title( $v ) { return strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $v ) ); }
function is_email( $v ) { return (bool) filter_var( $v, FILTER_VALIDATE_EMAIL ); }
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function wp_trim_words( $t, $n = 55, $more = '…' ) {
	$words = preg_split( '/\s+/', trim( strip_tags( (string) $t ) ) );
	return count( $words ) <= $n ? implode( ' ', $words ) : implode( ' ', array_slice( $words, 0, $n ) ) . $more;
}
function apply_filters( $tag, $value ) { return $value; }
function add_action( ...$a ) {}
function add_filter( ...$a ) {}
function do_action( ...$a ) {}
function add_theme_support( ...$a ) {}
function register_nav_menus( ...$a ) {}
function add_image_size( ...$a ) {}
function add_editor_style( ...$a ) {}
function load_theme_textdomain( ...$a ) {}
function load_plugin_textdomain( ...$a ) {}
function register_activation_hook( ...$a ) {}
function register_deactivation_hook( ...$a ) {}
function register_post_type( ...$a ) {}
function register_post_meta( ...$a ) {}
function register_setting( ...$a ) {}
function register_block_pattern_category( ...$a ) {}
function add_menu_page( ...$a ) {}
function add_meta_box( ...$a ) {}
function remove_post_type_support( ...$a ) {}
function wp_enqueue_style( ...$a ) {}
function wp_enqueue_script( ...$a ) {}
function wp_localize_script( ...$a ) {}
function wp_head() {
	// The harness has no enqueue pipeline; link the real stylesheet so the output is reviewable.
	echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Be+Vietnam+Pro:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">' . "\n";
	echo '<link rel="stylesheet" href="../../wp-content/themes/annamleaf/style.css">' . "\n";
}
function wp_footer() {}
function wp_body_open() {}
function language_attributes() { echo 'lang="en"'; }
function body_class() { echo 'class="home shows-placeholders"'; }
function bloginfo( $what ) { echo get_bloginfo( $what ); }
function get_bloginfo( $what ) { return 'charset' === $what ? 'UTF-8' : 'Leaf tobacco · Vietnam'; }
function home_url( $path = '/' ) { return 'https://annamleaf.test' . $path; }
function admin_url( $path = '' ) { return home_url( '/wp-admin/' . $path ); }
function get_option( $key, $default = false ) {
	return 'page_on_front' === $key ? 1 : $default;
}
function update_option( ...$a ) { return true; }
function get_theme_mod( $k, $d = false ) { return $d; }
function set_theme_mod( ...$a ) {}
function current_user_can( ...$a ) { return false; }
function wp_nonce_field( ...$a ) { echo '<input type="hidden" name="_wpnonce" value="stub">'; }
function has_custom_logo() { return false; }
function the_custom_logo() {}
function has_post_thumbnail( $id = 0 ) { return false; }
function get_the_post_thumbnail( ...$a ) { return ''; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['annamleaf_meta'][ $id ][ $key ] ?? ''; }
function get_post_field( $field, $id ) {
	if ( 'post_content' === $field && 1 === (int) $id ) {
		return '<p>Most leaf suppliers buy whatever the market offers. We start earlier: our own nurseries, our own seed selection, and field technicians who work alongside every contracted household through the season.</p>';
	}

	return '';
}
function get_posts( $args = array() ) {
	$type = $args['post_type'] ?? 'post';
	return array_slice( $GLOBALS['annamleaf_fixtures'][ $type ] ?? array(), 0, (int) ( $args['numberposts'] ?? 10 ) );
}
function get_pages( $args = array() ) { return array(); }
function get_page_by_path( $path ) { return null; }
function get_permalink( $post = null ) { return home_url( '/contact/' ); }
function get_post_type_archive_link( $type ) { return home_url( '/' . ( 'annam_stage' === $type ? 'process' : 'our-leaf' ) . '/' ); }
function get_post_type() { return $GLOBALS['annamleaf_current']->post_type ?? 'page'; }
function get_post_type_object( $type ) {
	$object                        = new stdClass();
	$object->labels                = new stdClass();
	$object->labels->singular_name = 'Process stage';
	return $object;
}
function get_the_title( $post = null ) {
	if ( $post instanceof stdClass ) { return $post->post_title; }
	return $GLOBALS['annamleaf_current']->post_title ?? 'Page title';
}
function get_the_ID() { return $GLOBALS['annamleaf_current']->ID ?? 1; }
function the_title() { echo esc_html( get_the_title() ); }
function the_content() { echo $GLOBALS['annamleaf_current']->post_content ?? ''; }
function the_permalink() { echo esc_url( get_permalink() ); }
function get_the_date() { return '3 September 2026'; }
function get_the_excerpt() { return 'Excerpt'; }
function get_the_archive_title() { return 'Archive'; }
function is_home() { return false; }
function paginate_links( $args = array() ) { return array(); }
function has_nav_menu( $location ) { return false; }
function wp_nav_menu( $args = array() ) {
	if ( isset( $args['fallback_cb'] ) && is_callable( $args['fallback_cb'] ) ) {
		call_user_func( $args['fallback_cb'] );
	}
}
function get_template_directory() { return dirname( __DIR__ ) . '/wp-content/themes/annamleaf'; }
function get_template_directory_uri() { return '/wp-content/themes/annamleaf'; }
function get_stylesheet_uri() { return '/wp-content/themes/annamleaf/style.css'; }
function get_template_part( $slug, $name = null, $args = array() ) {
	$file = get_template_directory() . '/' . $slug . ( $name ? '-' . $name : '' ) . '.php';

	if ( is_readable( $file ) ) {
		include $file;
	}
}
function have_posts() {
	return $GLOBALS['annamleaf_cursor'] + 1 < count( $GLOBALS['annamleaf_posts'] );
}
function the_post() {
	$GLOBALS['annamleaf_cursor']++;
	$GLOBALS['annamleaf_current'] = $GLOBALS['annamleaf_posts'][ $GLOBALS['annamleaf_cursor'] ];
}
function get_header() { include get_template_directory() . '/header.php'; }
function get_footer() { include get_template_directory() . '/footer.php'; }

// ---------------------------------------------------------------- run

require_once dirname( __DIR__ ) . '/wp-content/plugins/annamleaf-core/includes/meta.php';
require_once dirname( __DIR__ ) . '/wp-content/plugins/annamleaf-core/includes/settings.php';
require_once dirname( __DIR__ ) . '/wp-content/plugins/annamleaf-core/includes/api.php';
require_once get_template_directory() . '/functions.php';

$out = __DIR__ . '/output';

if ( ! is_dir( $out ) ) {
	mkdir( $out, 0755, true );
}

/**
 * Render one template with a given set of posts in the loop.
 *
 * @param string $template Template file, relative to the theme.
 * @param array  $posts    Posts for the loop.
 * @param string $name     Output file name.
 */
function annamleaf_render( string $template, array $posts, string $name ): void {
	$GLOBALS['annamleaf_posts']   = $posts;
	$GLOBALS['annamleaf_cursor']  = -1;
	$GLOBALS['annamleaf_current'] = $posts[0] ?? null;

	ob_start();
	include get_template_directory() . '/' . $template;
	$html = ob_get_clean();

	file_put_contents( __DIR__ . '/output/' . $name, $html );
	printf( "%-32s %6d bytes\n", $template, strlen( $html ) );
}

$page = annamleaf_fake( 1, 'page', 'About', '<p>Page body copy.</p>' );

annamleaf_render( 'front-page.php', array( $page ), 'home.html' );
annamleaf_render( 'archive-annam_stage.php', $GLOBALS['annamleaf_fixtures']['annam_stage'], 'process.html' );
annamleaf_render( 'archive-annam_leaf.php', $GLOBALS['annamleaf_fixtures']['annam_leaf'], 'our-leaf.html' );
annamleaf_render( 'page.php', array( $page ), 'page.html' );
annamleaf_render( 'page-templates/contact.php', array( $page ), 'contact.html' );
annamleaf_render( '404.php', array(), '404.html' );

echo "\nRendered into tools/output/ — open them in a browser to review.\n";
