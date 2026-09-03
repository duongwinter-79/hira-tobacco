<?php
/**
 * Nothing at this address.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

annamleaf_hero(
	array(
		'eyebrow'   => __( 'Page not found', 'annamleaf' ),
		'title'     => __( 'That page is not here', 'annamleaf' ),
		'text'      => __( 'The link may be out of date. The pages below cover what we grow, how we process it and how to reach the sales desk.', 'annamleaf' ),
		'cta_label' => __( 'Go to the home page', 'annamleaf' ),
		'cta_url'   => home_url( '/' ),
		'motif'     => 'field',
		'compact'   => true,
	)
);

get_footer();
