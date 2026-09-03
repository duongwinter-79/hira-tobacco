<?php
/**
 * First-run content.
 *
 * On activation the plugin fills the site with the approved demo structure — seven
 * process stages, four leaf types, three regions and the five pages — so the client
 * opens a complete site and edits real records instead of facing an empty admin.
 * Runs once; the flag stops a later reactivation from duplicating anything.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

const ANNAMLEAF_SEED_FLAG = 'annamleaf_seeded';

/**
 * The seven stages, in running order.
 *
 * @return array<int, array{title: string, content: string, shot: string}>
 */
function annamleaf_seed_stages(): array {
	return array(
		array(
			'title'   => __( 'Seed & nursery', 'annamleaf-core' ),
			'content' => __( 'Varieties are chosen for the destination market, sown in our own nurseries and raised before transplanting. Control here decides how even the whole crop turns out.', 'annamleaf-core' ),
			'shot'    => __( 'Seedling trays in the greenhouse; hands holding a plant', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Fields & farmer training', 'annamleaf-core' ),
			'content' => __( 'Contracted hectares across our growing regions. Inputs come from us, technicians visit on a set schedule, and a field diary is kept plot by plot.', 'annamleaf-core' ),
			'shot'    => __( 'A field technician standing with a farmer between the rows', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Harvest', 'annamleaf-core' ),
			'content' => __( 'Picked by stalk position and ripeness, in several passes through the season. Leaf reaches the barn the same day to hold its grade.', 'annamleaf-core' ),
			'shot'    => __( 'Hands picking a leaf; a full basket of green leaf', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Curing', 'annamleaf-core' ),
			'content' => __( 'Flue-curing for Virginia, air-curing for Burley. Temperature and humidity follow a set curve — the stage that decides colour, aroma and sugar.', 'annamleaf-core' ),
			'shot'    => __( 'Barn exteriors; golden leaf hanging inside — the signature shot', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Buying & grading', 'annamleaf-core' ),
			'content' => __( "Weighed and graded at our buying stations against the buyer's own samples. Each lot carries a code back to the household, the plot and the buying week.", 'annamleaf-core' ),
			'shot'    => __( 'The grading table; a buyer matching a hand of leaf to a sample', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Threshing, redrying & baling', 'annamleaf-core' ),
			'content' => __( 'Our line separates lamina from stem, redries to specification moisture, then presses bales labelled by lot.', 'annamleaf-core' ),
			'shot'    => __( 'Factory conveyor; the threshing line; a labelled finished bale', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Testing, storage & export', 'annamleaf-core' ),
			'content' => __( 'Lot samples are tested against CORESTA guidance residue levels, held in condition-controlled storage, then stuffed and shipped on your chosen Incoterms.', 'annamleaf-core' ),
			'shot'    => __( 'The lab; stacked bales in the warehouse; a container loading on site', 'annamleaf-core' ),
		),
	);
}

/**
 * The leaf portfolio.
 *
 * @return array<int, array{title: string, vi: string, curing: string, excerpt: string}>
 */
function annamleaf_seed_leaves(): array {
	return array(
		array(
			'title'   => __( 'Flue-cured Virginia', 'annamleaf-core' ),
			'vi'      => 'Virginia sấy lò',
			'curing'  => __( 'Flue-cured', 'annamleaf-core' ),
			'excerpt' => __( 'Bright leaf, high sugar, cured in temperature-controlled barns.', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Burley', 'annamleaf-core' ),
			'vi'      => 'Burley sấy gió',
			'curing'  => __( 'Air-cured', 'annamleaf-core' ),
			'excerpt' => __( 'Air-cured, low sugar, high filling power and casing absorption.', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Oriental', 'annamleaf-core' ),
			'vi'      => 'Oriental phơi nắng',
			'curing'  => __( 'Sun-cured', 'annamleaf-core' ),
			'excerpt' => __( 'Small sun-cured leaf, aromatic, used to lift a blend.', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Dark air-cured', 'annamleaf-core' ),
			'vi'      => 'Lá sẫm sấy gió',
			'curing'  => __( 'Air-cured', 'annamleaf-core' ),
			'excerpt' => __( 'Heavier, darker leaf for full-bodied blends.', 'annamleaf-core' ),
		),
	);
}

/**
 * Create the demo structure, once.
 */
function annamleaf_seed_content(): void {
	if ( get_option( ANNAMLEAF_SEED_FLAG ) ) {
		return;
	}

	update_option( ANNAMLEAF_SEED_FLAG, '1' );

	foreach ( annamleaf_seed_stages() as $index => $stage ) {
		annamleaf_seed_post(
			'annam_stage',
			$stage['title'],
			$stage['content'],
			array(
				'stage_no'  => sprintf( '%02d', $index + 1 ),
				'shot_note' => $stage['shot'],
			),
			$index + 1
		);
	}

	foreach ( annamleaf_seed_leaves() as $index => $leaf ) {
		annamleaf_seed_post(
			'annam_leaf',
			$leaf['title'],
			$leaf['excerpt'],
			array(
				'vi_name' => $leaf['vi'],
				'curing'  => $leaf['curing'],
			),
			$index + 1,
			$leaf['excerpt']
		);
	}

	for ( $i = 1; $i <= 3; $i++ ) {
		annamleaf_seed_post(
			'annam_region',
			/* translators: %d: region number. */
			sprintf( __( 'Growing region %d', 'annamleaf-core' ), $i ),
			'',
			array(
				'leaf_types' => __( 'Leaf type to confirm', 'annamleaf-core' ),
				'harvest'    => __( 'Harvest months to confirm', 'annamleaf-core' ),
			),
			$i
		);
	}

	annamleaf_seed_pages();
}

/**
 * Create one seeded record.
 *
 * @param string               $post_type Post type.
 * @param string               $title     Title.
 * @param string               $content   Body copy, wrapped as a paragraph block.
 * @param array<string,string> $meta      Custom fields, without the prefix.
 * @param int                  $order     Menu order.
 * @param string               $excerpt   Optional excerpt.
 * @return int The new post ID, or 0.
 */
function annamleaf_seed_post( string $post_type, string $title, string $content, array $meta = array(), int $order = 0, string $excerpt = '' ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'    => $post_type,
			'post_title'   => $title,
			'post_content' => '' === $content ? '' : '<!-- wp:paragraph --><p>' . esc_html( $content ) . '</p><!-- /wp:paragraph -->',
			'post_excerpt' => $excerpt,
			'post_status'  => 'publish',
			'menu_order'   => $order,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, ANNAMLEAF_META_PREFIX . $key, $value );
	}

	return (int) $post_id;
}

/**
 * Create the pages, set the front page and build the primary menu.
 */
function annamleaf_seed_pages(): void {
	$home_id = annamleaf_seed_page(
		__( 'Home', 'annamleaf-core' ),
		__( 'Most leaf suppliers buy whatever the market offers. We start earlier: our own nurseries, our own seed selection, and field technicians who work alongside every contracted household through the season.', 'annamleaf-core' ),
		array(
			'section_eyebrow' => __( 'Who we are', 'annamleaf-core' ),
			'section_heading' => __( 'One company across the whole chain', 'annamleaf-core' ),
		)
	);

	if ( $home_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}

	$about_id = annamleaf_seed_page(
		__( 'About', 'annamleaf-core' ),
		__( 'We are a vertically integrated leaf tobacco supplier: we grow, cure, process and export ourselves. Add the company story here, then drop in the "Three points" section from the block inserter.', 'annamleaf-core' ),
		array(
			'hero_eyebrow'   => __( 'About us', 'annamleaf-core' ),
			'hero_text'      => __( 'One company from the seedbed to the sealed container.', 'annamleaf-core' ),
			'hero_shot_note' => __( 'Facility exterior, or the company team', 'annamleaf-core' ),
		)
	);

	$quality_id = annamleaf_seed_page(
		__( 'Quality & Sustainability', 'annamleaf-core' ),
		__( 'Traceability is not a certificate on a wall — it follows from growing, curing and processing the leaf ourselves. Add the standards and sustainability sections from the block inserter.', 'annamleaf-core' ),
		array(
			'hero_eyebrow'   => __( 'Quality & sustainability', 'annamleaf-core' ),
			'hero_text'      => __( 'We can control it because we are present at every stage.', 'annamleaf-core' ),
			'hero_shot_note' => __( 'The lab, or stacked lot-labelled bales', 'annamleaf-core' ),
		)
	);

	$contact_id = annamleaf_seed_page(
		__( 'Contact', 'annamleaf-core' ),
		__( 'Tell us the type, grade and volume you need. Our sales desk replies within five working days, with samples if you ask for them.', 'annamleaf-core' ),
		array(
			'section_eyebrow' => __( 'Contact', 'annamleaf-core' ),
			'section_heading' => __( 'Request samples and an offer', 'annamleaf-core' ),
		)
	);

	if ( $contact_id ) {
		update_post_meta( $contact_id, '_wp_page_template', 'page-templates/contact.php' );
	}

	annamleaf_seed_menu(
		array(
			array( 'type' => 'page', 'id' => $home_id, 'label' => __( 'Home', 'annamleaf-core' ) ),
			array( 'type' => 'page', 'id' => $about_id, 'label' => __( 'About', 'annamleaf-core' ) ),
			array( 'type' => 'archive', 'id' => 'annam_stage', 'label' => __( 'Process', 'annamleaf-core' ) ),
			array( 'type' => 'archive', 'id' => 'annam_leaf', 'label' => __( 'Our Leaf', 'annamleaf-core' ) ),
			array( 'type' => 'page', 'id' => $quality_id, 'label' => __( 'Quality', 'annamleaf-core' ) ),
			array( 'type' => 'page', 'id' => $contact_id, 'label' => __( 'Contact', 'annamleaf-core' ) ),
		)
	);

	update_option(
		ANNAMLEAF_OPTION,
		array_merge(
			array(
				'show_placeholders' => '1',
				'trade_notice'      => __( 'This site is intended for industrial buyers and trade partners. It is not directed at consumers.', 'annamleaf-core' ),
			),
			(array) get_option( ANNAMLEAF_OPTION, array() )
		)
	);
}

/**
 * Create one page.
 *
 * @param string               $title   Page title.
 * @param string               $content Body copy.
 * @param array<string,string> $meta    Custom fields, without the prefix.
 * @return int
 */
function annamleaf_seed_page( string $title, string $content, array $meta = array() ): int {
	$existing = get_page_by_path( sanitize_title( $title ) );

	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	return annamleaf_seed_post( 'page', $title, $content, $meta );
}

/**
 * Build the primary menu from the seeded pages and archives.
 *
 * @param array<int, array{type: string, id: mixed, label: string}> $items Menu items.
 */
function annamleaf_seed_menu( array $items ): void {
	$name = __( 'Primary', 'annamleaf-core' );
	$menu = wp_get_nav_menu_object( $name );

	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );

		if ( is_wp_error( $menu_id ) ) {
			return;
		}
	} else {
		$menu_id = (int) $menu->term_id;
	}

	$position = 0;

	foreach ( $items as $item ) {
		if ( empty( $item['id'] ) ) {
			continue;
		}

		$position++;

		if ( 'archive' === $item['type'] ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['label'],
					'menu-item-object'    => (string) $item['id'],
					'menu-item-type'      => 'post_type_archive',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $position,
				)
			);

			continue;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $item['label'],
				'menu-item-object'    => 'page',
				'menu-item-object-id' => (int) $item['id'],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $position,
			)
		);
	}

	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );

	if ( empty( $locations['primary'] ) ) {
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
}
