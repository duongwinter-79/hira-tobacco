<?php
/**
 * Content types: the parts of the site the client edits as records rather than prose.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the three content types the site is built from.
 */
function annamleaf_register_post_types(): void {

	register_post_type(
		'annam_stage',
		array(
			'labels'        => array(
				'name'               => __( 'Process stages', 'annamleaf-core' ),
				'singular_name'      => __( 'Process stage', 'annamleaf-core' ),
				'add_new_item'       => __( 'Add process stage', 'annamleaf-core' ),
				'edit_item'          => __( 'Edit process stage', 'annamleaf-core' ),
				'menu_name'          => __( 'Process', 'annamleaf-core' ),
				'not_found'          => __( 'No stages yet.', 'annamleaf-core' ),
				'item_published'     => __( 'Stage published.', 'annamleaf-core' ),
				'item_updated'       => __( 'Stage updated.', 'annamleaf-core' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'process-stage' ),
			'menu_icon'     => 'dashicons-image-filter',
			'menu_position' => 22,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'annam_leaf',
		array(
			'labels'        => array(
				'name'           => __( 'Leaf types', 'annamleaf-core' ),
				'singular_name'  => __( 'Leaf type', 'annamleaf-core' ),
				'add_new_item'   => __( 'Add leaf type', 'annamleaf-core' ),
				'edit_item'      => __( 'Edit leaf type', 'annamleaf-core' ),
				'menu_name'      => __( 'Our Leaf', 'annamleaf-core' ),
				'not_found'      => __( 'No products yet.', 'annamleaf-core' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'leaf-type' ),
			'menu_icon'     => 'dashicons-palmtree',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'annam_region',
		array(
			'labels'        => array(
				'name'           => __( 'Growing regions', 'annamleaf-core' ),
				'singular_name'  => __( 'Growing region', 'annamleaf-core' ),
				'add_new_item'   => __( 'Add growing region', 'annamleaf-core' ),
				'edit_item'      => __( 'Edit growing region', 'annamleaf-core' ),
				'menu_name'      => __( 'Regions', 'annamleaf-core' ),
				'not_found'      => __( 'No regions yet.', 'annamleaf-core' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'menu_icon'     => 'dashicons-location-alt',
			'menu_position' => 24,
			'supports'      => array( 'title', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);
}
add_action( 'init', 'annamleaf_register_post_types' );

/**
 * Use the "Order" column to sort the list tables too, so the admin list matches the site.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function annamleaf_admin_columns( array $columns ): array {
	$order = array( 'annamleaf_order' => __( 'Order', 'annamleaf-core' ) );

	return array_slice( $columns, 0, 2, true ) + $order + array_slice( $columns, 2, null, true );
}
add_filter( 'manage_annam_stage_posts_columns', 'annamleaf_admin_columns' );
add_filter( 'manage_annam_leaf_posts_columns', 'annamleaf_admin_columns' );
add_filter( 'manage_annam_region_posts_columns', 'annamleaf_admin_columns' );

/**
 * Add the photo column, so the list shows at a glance which records still need one.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function annamleaf_photo_column( array $columns ): array {
	return array_slice( $columns, 0, 2, true )
		+ array( 'annamleaf_photo' => __( 'Photo', 'annamleaf-core' ) )
		+ array_slice( $columns, 2, null, true );
}
add_filter( 'manage_annam_stage_posts_columns', 'annamleaf_photo_column' );
add_filter( 'manage_annam_leaf_posts_columns', 'annamleaf_photo_column' );

/**
 * Print the order and photo values in the admin list table.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post being listed.
 */
function annamleaf_admin_column_value( string $column, int $post_id ): void {
	if ( 'annamleaf_order' === $column ) {
		echo (int) get_post_field( 'menu_order', $post_id );

		return;
	}

	if ( 'annamleaf_photo' !== $column ) {
		return;
	}

	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail( $post_id, array( 60, 40 ), array( 'style' => 'display:block;object-fit:cover;' ) );

		return;
	}

	$brief = annamleaf_meta( $post_id, 'shot_note' );

	printf(
		'<span style="color:#8a6210;">%s</span>%s',
		esc_html__( 'Not uploaded', 'annamleaf-core' ),
		'' === $brief ? '' : '<br><span class="description">' . esc_html( $brief ) . '</span>'
	);
}
add_action( 'manage_annam_stage_posts_custom_column', 'annamleaf_admin_column_value', 10, 2 );
add_action( 'manage_annam_leaf_posts_custom_column', 'annamleaf_admin_column_value', 10, 2 );
add_action( 'manage_annam_region_posts_custom_column', 'annamleaf_admin_column_value', 10, 2 );
