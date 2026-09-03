<?php
/**
 * Annam Leaf theme bootstrap.
 *
 * The theme is presentation only. Every piece of content it renders comes from the
 * Annam Leaf Core plugin, and every call into that plugin is guarded, so switching the
 * plugin off degrades the site instead of breaking it.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

define( 'ANNAMLEAF_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/plates.php';
require_once get_template_directory() . '/inc/rfq.php';
require_once get_template_directory() . '/inc/seo.php';

/**
 * Theme supports, menus and image sizes.
 */
function annamleaf_setup(): void {
	load_theme_textdomain( 'annamleaf', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );

	register_nav_menus( array(
		'primary' => __( 'Primary menu', 'annamleaf' ),
		'footer'  => __( 'Footer menu', 'annamleaf' ),
	) );

	// The frames photographs drop into. 3:2 everywhere except the hero.
	add_image_size( 'annamleaf-plate', 1600, 1067, true );
	add_image_size( 'annamleaf-hero', 2400, 1100, true );

	add_editor_style( 'assets/editor.css' );
}
add_action( 'after_setup_theme', 'annamleaf_setup' );

/**
 * Front end assets.
 */
function annamleaf_assets(): void {
	wp_enqueue_style( 'annamleaf-fonts', annamleaf_fonts_url(), array(), null );
	wp_enqueue_style( 'annamleaf', get_stylesheet_uri(), array( 'annamleaf-fonts' ), ANNAMLEAF_VERSION );

	wp_enqueue_script( 'annamleaf', get_template_directory_uri() . '/assets/site.js', array(), ANNAMLEAF_VERSION, true );
	wp_localize_script( 'annamleaf', 'annamleafData', array(
		'ageGate' => annamleaf_age_gate_enabled(),
	) );
}
add_action( 'wp_enqueue_scripts', 'annamleaf_assets' );

/**
 * Editor assets, so the block editor shows the site's typography and colours.
 */
function annamleaf_editor_assets(): void {
	wp_enqueue_style( 'annamleaf-fonts', annamleaf_fonts_url(), array(), null );
}
add_action( 'enqueue_block_editor_assets', 'annamleaf_editor_assets' );

/**
 * The webfont stylesheet.
 *
 * Hosted by Google here. To self-host for GDPR — worth doing before the site takes
 * European enquiries — drop the woff2 files into assets/fonts/, add @font-face rules to
 * style.css and return an empty string from the annamleaf_fonts_url filter.
 *
 * @return string
 */
function annamleaf_fonts_url(): string {
	$url = 'https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Be+Vietnam+Pro:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap';

	/**
	 * Filter the webfont stylesheet URL.
	 *
	 * @param string $url Stylesheet URL.
	 */
	return (string) apply_filters( 'annamleaf_fonts_url', $url );
}

/**
 * Whether the 18+ gate is switched on in the company profile.
 *
 * @return bool
 */
function annamleaf_age_gate_enabled(): bool {
	return function_exists( 'annamleaf_option' ) && '1' === annamleaf_option( 'age_gate' );
}

/**
 * Body classes that templates and CSS key off.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function annamleaf_body_classes( array $classes ): array {
	if ( annamleaf_age_gate_enabled() ) {
		$classes[] = 'has-age-gate';
	}

	if ( function_exists( 'annamleaf_placeholders_enabled' ) && annamleaf_placeholders_enabled() ) {
		$classes[] = 'shows-placeholders';
	}

	return $classes;
}
add_filter( 'body_class', 'annamleaf_body_classes' );

/**
 * The pattern category the theme's ready-made sections appear under.
 */
function annamleaf_register_pattern_category(): void {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}

	register_block_pattern_category(
		'annamleaf',
		array(
			'label'       => __( 'Annam Leaf sections', 'annamleaf' ),
			'description' => __( 'Ready-made sections for the About, Quality and Our Leaf pages.', 'annamleaf' ),
		)
	);
}
add_action( 'init', 'annamleaf_register_pattern_category' );

/**
 * A brochure site has no discussion.
 */
function annamleaf_close_comments(): void {
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'page', 'comments' );
}
add_action( 'init', 'annamleaf_close_comments' );
add_filter( 'comments_open', '__return_false', 20 );

/**
 * Excerpt length that fits the cards.
 *
 * @return int
 */
function annamleaf_excerpt_length(): int {
	return 28;
}
add_filter( 'excerpt_length', 'annamleaf_excerpt_length' );

/**
 * Excerpt ellipsis.
 *
 * @return string
 */
function annamleaf_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'annamleaf_excerpt_more' );

/**
 * The theme renders placeholders without the plugin, but the site has no content that way.
 * Say so where an editor will see it, rather than leaving them hunting for a missing menu.
 */
function annamleaf_plugin_notice(): void {
	if ( function_exists( 'annamleaf_option' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong><?php esc_html_e( 'Annam Leaf: the content plugin is not active.', 'annamleaf' ); ?></strong>
			<?php esc_html_e( 'The theme needs the Annam Leaf Core plugin for its pages, process stages, leaf types and the Company profile screen. Activate it under Plugins.', 'annamleaf' ); ?>
			<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Go to Plugins', 'annamleaf' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'annamleaf_plugin_notice' );
