<?php
/**
 * First-run content.
 *
 * Activating the plugin builds the whole site: seven process stages, four leaf types,
 * the Cao Bằng growing region and six finished pages, with the front page set and the
 * menu built. The
 * pages arrive written, not as empty shells asking the client to assemble sections — the
 * work left is editing wording and swapping in real figures and photographs.
 *
 * Runs once on activation. "Rebuild default content" on the Company profile screen runs
 * it again, restoring the pages to these defaults.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

const ANNAMLEAF_SEED_FLAG = 'annamleaf_seeded';

/**
 * Wrap copy in a paragraph block.
 *
 * @param string ...$paragraphs Paragraph texts, may contain inline HTML.
 * @return string
 */
function annamleaf_block_p( string ...$paragraphs ): string {
	$out = '';

	foreach ( $paragraphs as $text ) {
		$out .= '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->' . "\n\n";
	}

	return $out;
}

/**
 * A heading block.
 *
 * @param string $text  Heading text.
 * @param int    $level Heading level.
 * @return string
 */
function annamleaf_block_h( string $text, int $level = 2 ): string {
	$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';

	return '<!-- wp:heading' . $attrs . ' --><h' . $level . ' class="wp-block-heading">' . $text . '</h' . $level . '><!-- /wp:heading -->' . "\n\n";
}

/**
 * A list block.
 *
 * @param array<int, string> $items List items, may contain inline HTML.
 * @return string
 */
function annamleaf_block_list( array $items ): string {
	$out = '<!-- wp:list --><ul class="wp-block-list">';

	foreach ( $items as $item ) {
		$out .= '<!-- wp:list-item --><li>' . $item . '</li><!-- /wp:list-item -->';
	}

	return $out . '</ul><!-- /wp:list -->' . "\n\n";
}

/**
 * A three column block.
 *
 * @param array<int, array{0: string, 1: string}> $columns Heading and body for each column.
 * @return string
 */
function annamleaf_block_columns( array $columns ): string {
	$out = '<!-- wp:columns --><div class="wp-block-columns">';

	foreach ( $columns as $column ) {
		$out .= '<!-- wp:column --><div class="wp-block-column">'
			. annamleaf_block_h( $column[0], 3 )
			. annamleaf_block_p( $column[1] )
			. '</div><!-- /wp:column -->';
	}

	return $out . '</div><!-- /wp:columns -->' . "\n\n";
}

/**
 * A table block.
 *
 * @param array<int, string>        $head Header cells.
 * @param array<int, array<string>> $rows Body rows.
 * @return string
 */
function annamleaf_block_table( array $head, array $rows ): string {
	$out = '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr>';

	foreach ( $head as $cell ) {
		$out .= '<th>' . $cell . '</th>';
	}

	$out .= '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$out .= '<tr>';

		foreach ( $row as $cell ) {
			$out .= '<td>' . $cell . '</td>';
		}

		$out .= '</tr>';
	}

	return $out . '</tbody></table></figure><!-- /wp:table -->' . "\n\n";
}

/**
 * The seven stages, in running order.
 *
 * @return array<int, array{title: string, content: string, shot: string}>
 */
function annamleaf_seed_stages(): array {
	return array(
		array(
			'title'   => __( 'Seed & nursery', 'annamleaf-core' ),
			'content' => __( 'Varieties are chosen for the destination market, sown in our own nurseries and raised there before transplanting. Control at this stage decides how even the whole crop turns out.', 'annamleaf-core' ),
			'shot'    => __( 'Seedling trays in the greenhouse; hands holding a plant', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Fields & farmer training', 'annamleaf-core' ),
			'content' => __( 'Contracted hectares across our growing regions. Inputs come from us, field technicians visit on a set schedule, and a field diary is kept plot by plot.', 'annamleaf-core' ),
			'shot'    => __( 'A field technician standing with a farmer between the rows', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Harvest', 'annamleaf-core' ),
			'content' => __( 'Picked by stalk position and ripeness, in several passes through the season. Leaf reaches the barn the same day it is cut, which is what holds its grade.', 'annamleaf-core' ),
			'shot'    => __( 'Hands picking a leaf; a full basket of green leaf', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Curing', 'annamleaf-core' ),
			'content' => __( 'Flue-curing for Virginia, air-curing for Burley. Temperature and humidity follow a set curve — this is the stage that decides colour, aroma and sugar.', 'annamleaf-core' ),
			'shot'    => __( 'Barn exteriors; golden leaf hanging inside — the signature shot', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Buying & grading', 'annamleaf-core' ),
			'content' => __( "Weighed and graded at our buying stations against the buyer's own reference samples. Each lot carries a code back to the household, the plot and the buying week.", 'annamleaf-core' ),
			'shot'    => __( 'The grading table; a buyer matching a hand of leaf to a sample', 'annamleaf-core' ),
		),
		array(
			'title'   => __( 'Threshing, redrying & baling', 'annamleaf-core' ),
			'content' => __( 'Our line separates lamina from stem, redries to specification moisture, then presses and labels bales by lot ready for the container.', 'annamleaf-core' ),
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
 * The leaf portfolio. Moisture and packing carry the trade's usual figures as a starting
 * point; grades are company specific, so they stay empty until the client fills them in.
 *
 * @return array<int, array{title: string, vi: string, curing: string, excerpt: string, moisture: string, packing: string}>
 */
function annamleaf_seed_leaves(): array {
	$cases = __( '200 kg cases', 'annamleaf-core' );

	return array(
		array(
			'title'    => __( 'Flue-cured Virginia', 'annamleaf-core' ),
			'vi'       => 'Virginia sấy lò',
			'curing'   => __( 'Flue-cured', 'annamleaf-core' ),
			'excerpt'  => __( 'Bright leaf, high sugar, cured in temperature-controlled barns.', 'annamleaf-core' ),
			'moisture' => '12.0–13.5%',
			'packing'  => $cases,
		),
		array(
			'title'    => __( 'Burley', 'annamleaf-core' ),
			'vi'       => 'Burley sấy gió',
			'curing'   => __( 'Air-cured', 'annamleaf-core' ),
			'excerpt'  => __( 'Air-cured, low sugar, high filling power and casing absorption.', 'annamleaf-core' ),
			'moisture' => '12.0–13.5%',
			'packing'  => $cases,
		),
		array(
			'title'    => __( 'Oriental', 'annamleaf-core' ),
			'vi'       => 'Oriental phơi nắng',
			'curing'   => __( 'Sun-cured', 'annamleaf-core' ),
			'excerpt'  => __( 'Small sun-cured leaf, aromatic, used to lift a blend.', 'annamleaf-core' ),
			'moisture' => '12.0–13.5%',
			'packing'  => $cases,
		),
		array(
			'title'    => __( 'Dark air-cured', 'annamleaf-core' ),
			'vi'       => 'Lá sẫm sấy gió',
			'curing'   => __( 'Air-cured', 'annamleaf-core' ),
			'excerpt'  => __( 'Heavier, darker leaf for full-bodied blends.', 'annamleaf-core' ),
			'moisture' => '12.0–13.5%',
			'packing'  => $cases,
		),
	);
}

/**
 * Build the site.
 *
 * @param bool $force Rebuild even though the site has been seeded before.
 */
function annamleaf_seed_content( bool $force = false ): void {
	if ( ! $force && get_option( ANNAMLEAF_SEED_FLAG ) ) {
		return;
	}

	update_option( ANNAMLEAF_SEED_FLAG, '1' );

	foreach ( annamleaf_seed_stages() as $index => $stage ) {
		annamleaf_seed_post(
			'annam_stage',
			$stage['title'],
			annamleaf_block_p( $stage['content'] ),
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
			annamleaf_block_p( $leaf['excerpt'] ),
			array(
				'vi_name'  => $leaf['vi'],
				'curing'   => $leaf['curing'],
				'moisture' => $leaf['moisture'],
				'packing'  => $leaf['packing'],
			),
			$index + 1,
			$leaf['excerpt']
		);
	}

	// The one region we know. Others get added in Regions as the client confirms them.
	annamleaf_seed_post(
		'annam_region',
		'Cao Bằng',
		'',
		array(
			'leaf_types' => __( 'Leaf type to confirm', 'annamleaf-core' ),
			'harvest'    => __( 'Harvest months to confirm', 'annamleaf-core' ),
		),
		1
	);

	annamleaf_seed_pages( $force );
}

/**
 * Create one seeded record, unless a record with the same title already exists.
 *
 * @param string               $post_type Post type.
 * @param string               $title     Title.
 * @param string               $content   Block markup.
 * @param array<string,string> $meta      Custom fields, without the prefix.
 * @param int                  $order     Menu order.
 * @param string               $excerpt   Optional excerpt.
 * @return int The post ID, or 0.
 */
function annamleaf_seed_post( string $post_type, string $title, string $content, array $meta = array(), int $order = 0, string $excerpt = '' ): int {
	$existing = get_page_by_path( sanitize_title( $title ), OBJECT, $post_type );

	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => $post_type,
			'post_title'   => $title,
			'post_content' => $content,
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
 * The six pages, written out in full.
 *
 * @return array<string, array{title: string, template: string, content: string, meta: array<string,string>}>
 */
function annamleaf_seed_page_definitions(): array {
	$pages = array();

	$pages['home'] = array(
		'title'    => __( 'Home', 'annamleaf-core' ),
		'template' => '',
		'content'  => annamleaf_block_p(
			__( 'Most leaf suppliers buy whatever the market offers. We start earlier: our own nurseries, our own seed selection, and field technicians who work alongside every contracted household through the season.', 'annamleaf-core' ),
			__( 'That means every bale we ship traces back to the field it grew in, the barn it was cured in and the week it was graded — with no broker in between.', 'annamleaf-core' )
		),
		'meta'     => array(
			'section_eyebrow' => __( 'Who we are', 'annamleaf-core' ),
			'section_heading' => __( 'One company across the whole chain', 'annamleaf-core' ),
		),
	);

	$pages['about'] = array(
		'title'    => __( 'About', 'annamleaf-core' ),
		'template' => '',
		'content'  =>
			annamleaf_block_p(
				__( 'We grow, cure, grade, process and export leaf tobacco from our own fields in Vietnam. Most suppliers buy what the market offers; we start at the seedbed, which is why every bale we ship can be traced back to the field it grew in, the barn it was cured in and the week it was graded.', 'annamleaf-core' ),
				sprintf(
					/* translators: %s: growing region. */
					__( 'The company was founded in [year] and works with farming households across %s. We supply industrial buyers only — cigarette manufacturers and leaf merchants — and ship on the Incoterms they nominate.', 'annamleaf-core' ),
					annamleaf_option( 'region', __( '[growing region]', 'annamleaf-core' ) )
				)
			)
			. annamleaf_block_h( __( 'Three things we do ourselves', 'annamleaf-core' ) )
			. annamleaf_block_columns(
				array(
					array(
						__( 'We grow it', 'annamleaf-core' ),
						__( 'Own nurseries, contracted households, and inputs supplied and controlled by us. Field technicians are on the ground for the whole season, not just at buying time.', 'annamleaf-core' ),
					),
					array(
						__( 'We cure and grade it', 'annamleaf-core' ),
						__( 'Curing barns under technical supervision, then grading at our own buying stations against your reference samples, recorded lot by lot.', 'annamleaf-core' ),
					),
					array(
						__( 'We process and ship it', 'annamleaf-core' ),
						__( 'Threshing, redrying, baling, lab testing and export documentation, all handled in house rather than sub-contracted.', 'annamleaf-core' ),
					),
				)
			)
			. annamleaf_block_h( __( 'Farmers are the first link, not the last supplier', 'annamleaf-core' ) )
			. annamleaf_block_p(
				__( 'We contract before the season, supply seed and inputs, train on agronomy and curing, then buy the whole qualifying crop. Households that stay with us across seasons are what keeps quality steady from one crop to the next.', 'annamleaf-core' ),
				__( 'Labour commitments — no child labour, safe working conditions — are checked on the routine field visits, not just written into the contract.', 'annamleaf-core' )
			),
		'meta'     => array(
			'hero_eyebrow'   => __( 'About us', 'annamleaf-core' ),
			'hero_text'      => __( 'One company from the seedbed to the sealed container.', 'annamleaf-core' ),
			'hero_shot_note' => __( 'Facility exterior, or the company team', 'annamleaf-core' ),
		),
	);

	$pages['process'] = array(
		'title'    => __( 'Process', 'annamleaf-core' ),
		'template' => 'page-templates/process.php',
		'content'  => annamleaf_block_p(
			__( 'This is what separates us from a leaf trader: every stage below happens inside our own system, with nothing brokered. Each one is a place where quality is either held or lost.', 'annamleaf-core' )
		),
		'meta'     => array(
			'hero_eyebrow'   => __( 'From field to factory', 'annamleaf-core' ),
			'hero_title'     => __( 'Seven stages, one company accountable', 'annamleaf-core' ),
			'hero_shot_note' => __( 'Barn exteriors, or golden leaf hanging inside', 'annamleaf-core' ),
		),
	);

	$pages['leaf'] = array(
		'title'    => __( 'Our Leaf', 'annamleaf-core' ),
		'template' => 'page-templates/leaf.php',
		'content'  =>
			annamleaf_block_h( __( 'Shipped in the form you need', 'annamleaf-core' ) )
			. annamleaf_block_list(
				array(
					'<strong>' . __( 'Threshed lamina', 'annamleaf-core' ) . '</strong> — ' . __( 'threshed, redried and baled to export standard.', 'annamleaf-core' ),
					'<strong>' . __( 'Whole leaf', 'annamleaf-core' ) . '</strong> — ' . __( 'packed by grade for buyers who process in house.', 'annamleaf-core' ),
					'<strong>' . __( 'Stems', 'annamleaf-core' ) . '</strong> — ' . __( 'separated on the threshing line.', 'annamleaf-core' ),
					'<strong>' . __( 'Cut rag', 'annamleaf-core' ) . '</strong> — ' . __( 'cut to your specification.', 'annamleaf-core' ),
					'<strong>' . __( 'Scrap', 'annamleaf-core' ) . '</strong> — ' . __( 'recovered during processing.', 'annamleaf-core' ),
				)
			)
			. annamleaf_block_h( __( 'When the crop moves', 'annamleaf-core' ) )
			. annamleaf_block_p( __( 'Replace the months below with this season’s dates.', 'annamleaf-core' ) )
			. annamleaf_block_table(
				array( __( 'Stage', 'annamleaf-core' ), __( 'Period', 'annamleaf-core' ), __( 'Notes', 'annamleaf-core' ) ),
				array(
					array( __( 'Sowing', 'annamleaf-core' ), '[month–month]', __( 'In our own nurseries', 'annamleaf-core' ) ),
					array( __( 'Transplanting', 'annamleaf-core' ), '[month–month]', __( 'Varies by region and weather', 'annamleaf-core' ) ),
					array( __( 'Harvest and curing', 'annamleaf-core' ), '[month–month]', __( 'Several picking passes', 'annamleaf-core' ) ),
					array( __( 'Processing', 'annamleaf-core' ), '[month–month]', __( 'Threshing line running', 'annamleaf-core' ) ),
					array( __( 'Shipment', 'annamleaf-core' ), '[month–month]', __( 'Ex port of loading', 'annamleaf-core' ) ),
				)
			),
		'meta'     => array(
			'hero_eyebrow'   => __( 'Our leaf', 'annamleaf-core' ),
			'hero_title'     => __( 'Types, specifications and packing', 'annamleaf-core' ),
			'hero_text'      => __( 'Samples on request. Grades are matched against your own reference samples before shipment.', 'annamleaf-core' ),
			'hero_shot_note' => __( 'Graded leaf laid out by type', 'annamleaf-core' ),
		),
	);

	$pages['quality'] = array(
		'title'    => __( 'Quality & Sustainability', 'annamleaf-core' ),
		'template' => '',
		'content'  =>
			annamleaf_block_p(
				__( 'Traceability is not a certificate on a wall — it follows from growing, curing and processing the leaf ourselves. Every lot carries a code back to the household, the plot and the buying week, and reference samples are retained for each crop.', 'annamleaf-core' )
			)
			. annamleaf_block_h( __( 'What we work to', 'annamleaf-core' ) )
			. annamleaf_block_list(
				array(
					'<strong>GAP</strong> — ' . __( 'Good Agricultural Practices across the full contracted area.', 'annamleaf-core' ),
					'<strong>ALP</strong> — ' . __( 'Agricultural Labour Practices: no child labour, safe hours and conditions.', 'annamleaf-core' ),
					'<strong>CORESTA GRL</strong> — ' . __( 'residue testing against the guidance residue levels, every crop.', 'annamleaf-core' ),
					'<strong>ISO 9001</strong> — ' . __( 'certificate number to be confirmed.', 'annamleaf-core' ),
				)
			)
			. annamleaf_block_h( __( 'Commitments we can measure', 'annamleaf-core' ) )
			. annamleaf_block_columns(
				array(
					array(
						__( 'No child labour', 'annamleaf-core' ),
						__( 'A binding clause in every household contract, checked on the routine field visits.', 'annamleaf-core' ),
					),
					array(
						__( 'Curing fuel', 'annamleaf-core' ),
						__( 'Moving barns onto biomass fuel, and measuring what has been converted each season.', 'annamleaf-core' ),
					),
					array(
						__( 'Reforestation', 'annamleaf-core' ),
						__( 'Trees planted every year to offset the wood used in curing.', 'annamleaf-core' ),
					),
				)
			),
		'meta'     => array(
			'hero_eyebrow'   => __( 'Quality & sustainability', 'annamleaf-core' ),
			'hero_title'     => __( 'We can control it because we are present at every stage', 'annamleaf-core' ),
			'hero_shot_note' => __( 'The lab, or stacked lot-labelled bales', 'annamleaf-core' ),
		),
	);

	$pages['contact'] = array(
		'title'    => __( 'Contact', 'annamleaf-core' ),
		'template' => 'page-templates/contact.php',
		'content'  => annamleaf_block_p(
			__( 'Tell us the type, grade and volume you need. Our sales desk replies within five working days, with samples if you ask for them.', 'annamleaf-core' )
		),
		'meta'     => array(
			'section_eyebrow' => __( 'Contact', 'annamleaf-core' ),
			'section_heading' => __( 'Request samples and an offer', 'annamleaf-core' ),
		),
	);

	return $pages;
}

/**
 * Create the pages, set the front page and build the menu.
 *
 * @param bool $force Overwrite the content of pages that already exist.
 */
function annamleaf_seed_pages( bool $force = false ): void {
	$ids = array();

	foreach ( annamleaf_seed_page_definitions() as $key => $page ) {
		$ids[ $key ] = annamleaf_seed_page( $page['title'], $page['content'], $page['meta'], $page['template'], $force );
	}

	if ( ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}

	annamleaf_seed_menu(
		array(
			array( 'id' => $ids['home'] ?? 0, 'label' => __( 'Home', 'annamleaf-core' ) ),
			array( 'id' => $ids['about'] ?? 0, 'label' => __( 'About', 'annamleaf-core' ) ),
			array( 'id' => $ids['process'] ?? 0, 'label' => __( 'Process', 'annamleaf-core' ) ),
			array( 'id' => $ids['leaf'] ?? 0, 'label' => __( 'Our Leaf', 'annamleaf-core' ) ),
			array( 'id' => $ids['quality'] ?? 0, 'label' => __( 'Quality', 'annamleaf-core' ) ),
			array( 'id' => $ids['contact'] ?? 0, 'label' => __( 'Contact', 'annamleaf-core' ) ),
		)
	);

	$options = (array) get_option( ANNAMLEAF_OPTION, array() );

	update_option(
		ANNAMLEAF_OPTION,
		array_merge(
			array(
				'show_placeholders' => '1',
				'region'            => 'Cao Bằng',
				'trade_notice'      => __( 'This site is intended for industrial buyers and trade partners. It is not directed at consumers.', 'annamleaf-core' ),
			),
			$options
		)
	);
}

/**
 * Create one page, or refresh it when rebuilding.
 *
 * @param string               $title    Page title.
 * @param string               $content  Block markup.
 * @param array<string,string> $meta     Custom fields, without the prefix.
 * @param string               $template Page template file, relative to the theme.
 * @param bool                 $force    Overwrite an existing page's content.
 * @return int
 */
function annamleaf_seed_page( string $title, string $content, array $meta = array(), string $template = '', bool $force = false ): int {
	$existing = get_page_by_path( sanitize_title( $title ) );
	$post_id  = $existing instanceof WP_Post ? (int) $existing->ID : 0;

	if ( 0 === $post_id ) {
		$post_id = annamleaf_seed_post( 'page', $title, $content, $meta );
	} elseif ( $force ) {
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, ANNAMLEAF_META_PREFIX . $key, $value );
		}
	}

	if ( $post_id && '' !== $template ) {
		update_post_meta( $post_id, '_wp_page_template', $template );
	}

	return $post_id;
}

/**
 * Build the primary menu from the seeded pages.
 *
 * @param array<int, array{id: int, label: string}> $items Menu items.
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

	$existing = wp_get_nav_menu_items( $menu_id );
	$linked   = array();

	foreach ( (array) $existing as $item ) {
		if ( 'post_type' === $item->type ) {
			$linked[] = (int) $item->object_id;
		}
	}

	$position = 0;

	foreach ( $items as $item ) {
		if ( empty( $item['id'] ) ) {
			continue;
		}

		$position++;

		// Do not add a second entry for a page already in the menu.
		if ( in_array( (int) $item['id'], $linked, true ) ) {
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

/**
 * Rebuild the default content from the Company profile screen.
 */
function annamleaf_handle_rebuild(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'annamleaf-core' ) );
	}

	check_admin_referer( 'annamleaf_rebuild' );

	annamleaf_register_post_types();
	annamleaf_seed_content( true );
	flush_rewrite_rules();

	wp_safe_redirect( add_query_arg( 'annamleaf_rebuilt', '1', admin_url( 'admin.php?page=annamleaf-settings' ) ) );
	exit;
}
add_action( 'admin_post_annamleaf_rebuild', 'annamleaf_handle_rebuild' );
