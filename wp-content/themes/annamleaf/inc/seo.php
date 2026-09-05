<?php
/**
 * The metadata a B2B buyer's first Google search depends on.
 *
 * Enough to launch without an SEO plugin: a description, Open Graph tags for when the
 * page is pasted into an email or LinkedIn, and an Organization record so the company
 * name, address and contact details are machine-readable. If Yoast, Rank Math or
 * SEOPress is installed later, this stands down rather than emitting everything twice.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a dedicated SEO plugin is handling this already.
 *
 * @return bool
 */
function annamleaf_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| class_exists( 'All_in_One_SEO_Pack' );
}

/**
 * The description for the page being viewed.
 *
 * @return string
 */
function annamleaf_description(): string {
	$description = '';

	if ( is_front_page() ) {
		$front_id    = (int) get_option( 'page_on_front' );
		$description = $front_id ? annamleaf_get_meta( $front_id, 'hero_text' ) : '';
	} elseif ( is_singular() ) {
		$post_id     = (int) get_the_ID();
		$description = annamleaf_get_meta( $post_id, 'hero_text' );

		if ( '' === $description ) {
			$description = has_excerpt( $post_id )
				? (string) get_the_excerpt( $post_id )
				: wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 32, '…' );
		}
	} elseif ( is_post_type_archive( 'annam_stage' ) ) {
		$description = __( 'How our leaf is grown, cured, graded and processed — every stage inside our own system.', 'annamleaf' );
	} elseif ( is_post_type_archive( 'annam_leaf' ) ) {
		$description = __( 'Sợi thuốc lá, cọng thuốc lá and lá thuốc đã tách cọng: specifications, moisture and packing for industrial buyers.', 'annamleaf' );
	}

	if ( '' === $description ) {
		$description = (string) get_bloginfo( 'description' );
	}

	// Strip the placeholder markers: they belong on the page, not in a search result.
	$description = trim( preg_replace( '/\[[^\]]*\]/', '', wp_strip_all_tags( $description ) ) );

	return trim( preg_replace( '/\s+/', ' ', $description ) );
}

/**
 * The image representing the page when it is shared.
 *
 * @return string
 */
function annamleaf_share_image(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( get_the_ID(), 'annamleaf-hero' );

		if ( $image ) {
			return (string) $image;
		}
	}

	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $logo_id ) {
		$logo = wp_get_attachment_image_url( $logo_id, 'full' );

		if ( $logo ) {
			return (string) $logo;
		}
	}

	return '';
}

/**
 * Print the description, Open Graph and Twitter tags.
 */
function annamleaf_meta_tags(): void {
	if ( annamleaf_seo_plugin_active() ) {
		return;
	}

	$description = annamleaf_description();
	$title       = wp_get_document_title();
	$image       = annamleaf_share_image();
	$url         = is_front_page() ? home_url( '/' ) : ( is_singular() ? (string) get_permalink() : home_url( add_query_arg( array() ) ) );

	// A static front page is singular too, but it is the website, not an article.
	$is_article = is_singular() && ! is_front_page();

	printf( "\n<meta name=\"description\" content=\"%s\">\n", esc_attr( $description ) );
	printf( "<meta property=\"og:type\" content=\"%s\">\n", $is_article ? 'article' : 'website' );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $title ) );
	printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $description ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( "<meta property=\"og:locale\" content=\"%s\">\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );

	if ( '' !== $image ) {
		printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $image ) );
		echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
	} else {
		echo "<meta name=\"twitter:card\" content=\"summary\">\n";
	}
}
add_action( 'wp_head', 'annamleaf_meta_tags', 5 );

/**
 * Print the Organization record, on the front page only.
 */
function annamleaf_schema(): void {
	if ( ! is_front_page() || annamleaf_seo_plugin_active() ) {
		return;
	}

	$name = annamleaf_get( 'legal_name', annamleaf_get( 'company_name', (string) get_bloginfo( 'name' ) ) );

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Organization',
		'name'        => $name,
		'url'         => home_url( '/' ),
		'description' => annamleaf_description(),
	);

	$image = annamleaf_share_image();

	if ( '' !== $image ) {
		$schema['logo'] = $image;
	}

	$email = annamleaf_get( 'email' );

	if ( '' !== $email ) {
		$schema['email'] = $email;
	}

	$phone = annamleaf_get( 'phone' );

	if ( '' !== $phone ) {
		$schema['telephone'] = $phone;
	}

	$address = annamleaf_get( 'office_address' );

	if ( '' !== $address ) {
		$schema['address'] = array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => trim( preg_replace( '/\s+/', ' ', $address ) ),
			'addressCountry' => 'VN',
		);
	}

	// Placeholder markers are page furniture, never structured data.
	$schema = array_filter(
		$schema,
		static function ( $value ) {
			return ! is_string( $value ) || ( '' !== $value && ! str_contains( $value, '[' ) );
		}
	);

	printf(
		"\n<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'annamleaf_schema', 6 );
