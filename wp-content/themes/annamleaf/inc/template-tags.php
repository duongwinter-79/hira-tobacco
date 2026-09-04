<?php
/**
 * Template tags.
 *
 * Everything the templates print goes through here. The plugin accessors are wrapped so
 * the theme still renders — with placeholders — if the Annam Leaf Core plugin is inactive.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * A company profile value.
 *
 * @param string $key     Field key.
 * @param string $default Fallback.
 * @return string
 */
function annamleaf_get( string $key, string $default = '' ): string {
	return function_exists( 'annamleaf_option' ) ? annamleaf_option( $key, $default ) : $default;
}

/**
 * A custom field on a post.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Field key.
 * @param string $default Fallback.
 * @return string
 */
function annamleaf_get_meta( int $post_id, string $key, string $default = '' ): string {
	return function_exists( 'annamleaf_meta' ) ? annamleaf_meta( $post_id, $key, $default ) : $default;
}

/**
 * A placeholder marker, or nothing when markers are switched off.
 *
 * @param string $label Missing content label.
 * @return string
 */
function annamleaf_ph( string $label ): string {
	return function_exists( 'annamleaf_placeholder' ) ? annamleaf_placeholder( $label ) : '';
}

/**
 * A company profile value, or its placeholder marker.
 *
 * @param string $key         Field key.
 * @param string $placeholder Missing content label.
 * @return string
 */
function annamleaf_get_field( string $key, string $placeholder = '' ): string {
	$value = annamleaf_get( $key );

	if ( '' !== $value ) {
		return nl2br( esc_html( $value ) );
	}

	return annamleaf_ph( $placeholder );
}

/**
 * The trading name, or its placeholder.
 *
 * @return string
 */
function annamleaf_company_name(): string {
	return annamleaf_get_field( 'company_name', __( 'COMPANY NAME', 'annamleaf' ) );
}

/**
 * Print the hero block.
 *
 * @param array{
 *     eyebrow?: string,
 *     title?: string,
 *     text?: string,
 *     cta_label?: string,
 *     cta_url?: string,
 *     secondary_label?: string,
 *     secondary_url?: string,
 *     post_id?: int,
 *     motif?: string,
 *     photo?: string,
 *     shot_note?: string,
 *     shot_index?: string,
 *     compact?: bool
 * } $args Hero arguments.
 */
function annamleaf_hero( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'         => '',
			'title'           => '',
			'text'            => '',
			'cta_label'       => '',
			'cta_url'         => '',
			'secondary_label' => '',
			'secondary_url'   => '',
			'post_id'         => 0,
			'motif'           => 'field',
			'photo'           => '',
			'shot_note'       => '',
			'shot_index'      => '',
			'compact'         => false,
		)
	);

	if ( '' === $args['title'] ) {
		return;
	}
	?>
	<div class="hero<?php echo $args['compact'] ? ' hero--compact' : ''; ?>">
		<?php
		annamleaf_plate(
			array(
				'post_id'    => (int) $args['post_id'],
				'motif'      => $args['motif'],
				'photo'      => $args['photo'],
				'shot_note'  => $args['shot_note'],
				'shot_index' => $args['shot_index'],
				'size'       => 'annamleaf-hero',
			)
		);
		?>
		<div class="hero-veil"></div>
		<div class="wrap">
			<?php if ( '' !== $args['eyebrow'] ) : ?>
				<p class="eyebrow on-field"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>

			<h1><?php echo wp_kses_post( $args['title'] ); ?></h1>

			<?php if ( '' !== $args['text'] ) : ?>
				<p><?php echo wp_kses_post( $args['text'] ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $args['cta_label'] || '' !== $args['secondary_label'] ) : ?>
				<div class="cta-row">
					<?php if ( '' !== $args['cta_label'] ) : ?>
						<a class="btn" href="<?php echo esc_url( $args['cta_url'] ); ?>"><?php echo esc_html( $args['cta_label'] ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== $args['secondary_label'] ) : ?>
						<a class="btn btn--ghost" href="<?php echo esc_url( $args['secondary_url'] ); ?>"><?php echo esc_html( $args['secondary_label'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Print the hero for the page being viewed, from its own Page hero fields.
 *
 * @param int $post_id Page ID.
 */
function annamleaf_page_hero( int $post_id, string $motif = 'plant' ): void {
	$title = annamleaf_get_meta( $post_id, 'hero_title', get_the_title( $post_id ) );

	annamleaf_hero(
		array(
			'eyebrow'   => annamleaf_get_meta( $post_id, 'hero_eyebrow' ),
			'title'     => $title,
			'text'      => annamleaf_get_meta( $post_id, 'hero_text' ),
			'cta_label' => annamleaf_get_meta( $post_id, 'hero_cta_label' ),
			'cta_url'   => annamleaf_get_meta( $post_id, 'hero_cta_url' ),
			'post_id'   => $post_id,
			'motif'     => $motif,
			'shot_note' => annamleaf_get_meta( $post_id, 'hero_shot_note' ),
			'compact'   => true,
		)
	);
}

/**
 * Print the capacity figures strip.
 */
function annamleaf_stats_strip(): void {
	$stats = function_exists( 'annamleaf_stats' ) ? annamleaf_stats() : array();

	if ( empty( $stats ) ) {
		return;
	}
	?>
	<div class="stats">
		<div class="wrap">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="stat">
					<p class="fig"><?php echo wp_kses_post( $stat['figure'] ); ?></p>
					<p class="lbl"><?php echo wp_kses_post( $stat['label'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Print a call to action band.
 *
 * @param string $title     Heading.
 * @param string $text      Supporting line.
 * @param string $cta_label Button label.
 * @param string $cta_url   Button link.
 */
function annamleaf_band( string $title, string $text, string $cta_label, string $cta_url ): void {
	?>
	<div class="band">
		<div class="wrap">
			<div>
				<h2><?php echo esc_html( $title ); ?></h2>
				<?php if ( '' !== $text ) : ?>
					<p><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $cta_label ) : ?>
				<a class="btn" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Print the language switcher.
 *
 * Translation itself is Polylang's job: it pairs an English page with its Vietnamese
 * translation. Without Polylang there is nothing to switch between, so nothing prints.
 */
function annamleaf_language_switcher(): void {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return;
	}

	$languages = pll_the_languages(
		array(
			'display_names_as' => 'slug',
			'hide_if_empty'    => 0,
			'raw'              => 1,
		)
	);

	if ( ! is_array( $languages ) || count( $languages ) < 2 ) {
		return;
	}

	echo '<div class="langs">';

	foreach ( $languages as $language ) {
		printf(
			'<a href="%1$s" hreflang="%2$s" aria-current="%3$s">%4$s</a>',
			esc_url( $language['url'] ),
			esc_attr( $language['locale'] ),
			! empty( $language['current_lang'] ) ? 'true' : 'false',
			esc_html( strtoupper( $language['slug'] ) )
		);
	}

	echo '</div>';
}

/**
 * Print archive pagination.
 */
function annamleaf_pagination(): void {
	$links = paginate_links( array( 'type' => 'array', 'prev_text' => '←', 'next_text' => '→' ) );

	if ( empty( $links ) ) {
		return;
	}

	echo '<nav class="pager wrap" aria-label="' . esc_attr__( 'Pagination', 'annamleaf' ) . '">';

	foreach ( $links as $link ) {
		echo wp_kses_post( $link );
	}

	echo '</nav>';
}

/**
 * Print the 18+ trade gate, when it is switched on in the company profile.
 */
function annamleaf_age_gate(): void {
	if ( ! annamleaf_age_gate_enabled() ) {
		return;
	}
	?>
	<div class="gate" id="gate" hidden>
		<div class="box" role="dialog" aria-modal="true" aria-labelledby="gate-title">
			<?php annamleaf_leaf_mark(); ?>
			<h2 id="gate-title"><?php esc_html_e( 'A site for the tobacco trade', 'annamleaf' ); ?></h2>
			<p><?php esc_html_e( 'We supply leaf tobacco to industrial buyers. Please confirm that you are 18 or older and visiting as a trade professional.', 'annamleaf' ); ?></p>
			<div class="row">
				<button class="btn" type="button" id="gate-yes"><?php esc_html_e( 'I am 18+ and in the trade', 'annamleaf' ); ?></button>
				<a class="btn btn--ghost" href="https://www.google.com/"><?php esc_html_e( 'Leave', 'annamleaf' ); ?></a>
			</div>
		</div>
	</div>
	<?php
}

/**
 * The menu shown until an editor builds one under Appearance → Menus.
 *
 * Lists the home page and any top level pages, so a fresh install is navigable on the
 * first load.
 */
function annamleaf_default_menu(): void {
	$items = array(
		array( 'url' => home_url( '/' ), 'label' => __( 'Home', 'annamleaf' ) ),
	);

	foreach ( get_pages( array( 'parent' => 0, 'number' => 8, 'sort_column' => 'menu_order,post_title' ) ) as $page ) {
		if ( (int) get_option( 'page_on_front' ) === $page->ID ) {
			continue;
		}

		$items[] = array( 'url' => get_permalink( $page ), 'label' => get_the_title( $page ) );
	}

	echo '<ul class="menu">';

	foreach ( $items as $item ) {
		printf(
			'<li class="menu-item"><a href="%1$s">%2$s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
}

/**
 * The URL of the page using a given page template.
 *
 * Looking the page up by its template means renaming or moving the page does not break
 * every call to action on the site.
 *
 * @param string $template      Template file name without the extension.
 * @param string $fallback_slug Page slug to try when no page uses the template.
 * @return string
 */
function annamleaf_page_url( string $template, string $fallback_slug ): string {
	$pages = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_key'    => '_wp_page_template',
			'meta_value'  => 'page-templates/' . $template . '.php',
			'fields'      => 'ids',
		)
	);

	if ( ! empty( $pages ) ) {
		return (string) get_permalink( (int) $pages[0] );
	}

	$fallback = get_page_by_path( $fallback_slug );

	return $fallback ? (string) get_permalink( $fallback ) : home_url( '/' );
}

/**
 * The URL of the contact page.
 *
 * @return string
 */
function annamleaf_contact_url(): string {
	return annamleaf_page_url( 'contact', 'contact' );
}

/**
 * The URL of the process page.
 *
 * @return string
 */
function annamleaf_process_url(): string {
	return annamleaf_page_url( 'process', 'process' );
}

/**
 * The URL of the leaf portfolio page.
 *
 * @return string
 */
function annamleaf_leaf_url(): string {
	return annamleaf_page_url( 'leaf', 'our-leaf' );
}

/**
 * Print a section heading pair: the small label and the heading beside it.
 *
 * @param string $eyebrow Small label.
 * @param string $heading Heading.
 */
function annamleaf_section_head( string $eyebrow, string $heading ): void {
	if ( '' === $eyebrow && '' === $heading ) {
		return;
	}

	echo '<div class="sechead">';

	if ( '' !== $eyebrow ) {
		printf( '<p class="eyebrow">%s</p>', esc_html( $eyebrow ) );
	}

	if ( '' !== $heading ) {
		printf( '<h2>%s</h2>', esc_html( $heading ) );
	}

	echo '</div>';
}
