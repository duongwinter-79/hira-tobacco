<?php
/**
 * Editable fields on posts and pages.
 *
 * Every field the client fills in is declared once in annamleaf_field_groups() and the
 * meta box, the sanitising and the saving are all generated from that declaration.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

const ANNAMLEAF_META_PREFIX = '_annamleaf_';

/**
 * The field declaration for every post type that has one.
 *
 * @return array<string, array{title: string, context: string, fields: array}>
 */
function annamleaf_field_groups(): array {
	$groups = array(
		'annam_stage'  => array(
			'title'   => __( 'Stage details', 'annamleaf-core' ),
			'context' => 'side',
			'fields'  => array(
				'stage_no'  => array(
					'label' => __( 'Stage number', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Shown above the title, e.g. 01.', 'annamleaf-core' ),
				),
				'shot_note' => array(
					'label' => __( 'Photo brief', 'annamleaf-core' ),
					'type'  => 'textarea',
					'hint'  => __( 'What to photograph for this stage. Shown as a caption on the placeholder illustration and hidden as soon as you set a featured image.', 'annamleaf-core' ),
				),
			),
		),
		'annam_leaf'   => array(
			'title'   => __( 'Leaf specification', 'annamleaf-core' ),
			'context' => 'normal',
			'fields'  => array(
				'vi_name'  => array(
					'label' => __( 'Vietnamese name', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Shown under the English name on the card.', 'annamleaf-core' ),
				),
				'curing'   => array(
					'label' => __( 'Curing', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Flue-cured, air-cured, sun-cured.', 'annamleaf-core' ),
				),
				'grades'   => array(
					'label' => __( 'Grades', 'annamleaf-core' ),
					'type'  => 'text',
				),
				'moisture' => array(
					'label' => __( 'Moisture', 'annamleaf-core' ),
					'type'  => 'text',
				),
				'packing'  => array(
					'label' => __( 'Packing', 'annamleaf-core' ),
					'type'  => 'text',
				),
			),
		),
		'annam_region' => array(
			'title'   => __( 'Region details', 'annamleaf-core' ),
			'context' => 'normal',
			'fields'  => array(
				'area_ha'    => array(
					'label' => __( 'Area (ha)', 'annamleaf-core' ),
					'type'  => 'text',
				),
				'leaf_types' => array(
					'label' => __( 'Leaf grown here', 'annamleaf-core' ),
					'type'  => 'text',
				),
				'harvest'    => array(
					'label' => __( 'Harvest period', 'annamleaf-core' ),
					'type'  => 'text',
				),
			),
		),
		'page'         => array(
			'title'   => __( 'Page hero', 'annamleaf-core' ),
			'context' => 'normal',
			'fields'  => array(
				'hero_eyebrow'   => array(
					'label' => __( 'Eyebrow', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Small label above the heading.', 'annamleaf-core' ),
				),
				'hero_title'     => array(
					'label' => __( 'Hero heading', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Leave empty to use the page title.', 'annamleaf-core' ),
				),
				'hero_text'      => array(
					'label' => __( 'Hero text', 'annamleaf-core' ),
					'type'  => 'textarea',
				),
				'hero_cta_label' => array(
					'label' => __( 'Button label', 'annamleaf-core' ),
					'type'  => 'text',
				),
				'hero_cta_url'   => array(
					'label' => __( 'Button link', 'annamleaf-core' ),
					'type'  => 'url',
				),
				'hero_shot_note' => array(
					'label' => __( 'Photo brief', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Caption on the placeholder illustration, until a featured image is set.', 'annamleaf-core' ),
				),
				'section_eyebrow' => array(
					'label' => __( 'Section label', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Small label beside the page text, e.g. "Who we are".', 'annamleaf-core' ),
				),
				'section_heading' => array(
					'label' => __( 'Section heading', 'annamleaf-core' ),
					'type'  => 'text',
					'hint'  => __( 'Heading beside the page text.', 'annamleaf-core' ),
				),
			),
		),
	);

	/**
	 * Filter the editable field declaration.
	 *
	 * @param array $groups Field groups keyed by post type.
	 */
	return apply_filters( 'annamleaf_field_groups', $groups );
}

/**
 * Register every declared field as post meta so REST, revisions and export see it.
 */
function annamleaf_register_meta(): void {
	foreach ( annamleaf_field_groups() as $post_type => $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			register_post_meta(
				$post_type,
				ANNAMLEAF_META_PREFIX . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => false,
					'sanitize_callback' => static function ( $value ) use ( $field ) {
						return annamleaf_sanitize_field( $value, $field['type'] ?? 'text' );
					},
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'annamleaf_register_meta' );

/**
 * Sanitise one value according to its declared type.
 *
 * @param mixed  $value Raw value.
 * @param string $type  Field type.
 * @return string
 */
function annamleaf_sanitize_field( $value, string $type ): string {
	$value = is_scalar( $value ) ? (string) $value : '';

	return match ( $type ) {
		'textarea' => sanitize_textarea_field( $value ),
		'url'      => esc_url_raw( $value ),
		default    => sanitize_text_field( $value ),
	};
}

/**
 * Add the declared meta boxes to their post types.
 */
function annamleaf_add_meta_boxes(): void {
	foreach ( annamleaf_field_groups() as $post_type => $group ) {
		add_meta_box(
			'annamleaf-fields-' . $post_type,
			$group['title'],
			'annamleaf_render_meta_box',
			$post_type,
			$group['context'],
			'default',
			array(
				'__block_editor_compatible_meta_box' => true,
				'post_type'                          => $post_type,
			)
		);
	}
}
add_action( 'add_meta_boxes', 'annamleaf_add_meta_boxes' );

/**
 * Render the fields of one group.
 *
 * @param WP_Post $post Post being edited.
 * @param array   $box  Meta box arguments.
 */
function annamleaf_render_meta_box( WP_Post $post, array $box ): void {
	$groups    = annamleaf_field_groups();
	$post_type = $box['args']['post_type'] ?? $post->post_type;

	if ( ! isset( $groups[ $post_type ] ) ) {
		return;
	}

	wp_nonce_field( 'annamleaf_save_fields', 'annamleaf_fields_nonce' );

	echo '<div class="annamleaf-fields" style="display:grid;gap:14px;">';

	foreach ( $groups[ $post_type ]['fields'] as $key => $field ) {
		$id    = 'annamleaf-' . $key;
		$name  = ANNAMLEAF_META_PREFIX . $key;
		$value = (string) get_post_meta( $post->ID, $name, true );

		echo '<p style="margin:0;">';
		printf(
			'<label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px;">%2$s</label>',
			esc_attr( $id ),
			esc_html( $field['label'] )
		);

		if ( 'textarea' === ( $field['type'] ?? 'text' ) ) {
			printf(
				'<textarea id="%1$s" name="%2$s" rows="3" class="widefat">%3$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="widefat">',
				'url' === ( $field['type'] ?? 'text' ) ? 'url' : 'text',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		if ( ! empty( $field['hint'] ) ) {
			printf( '<span class="description" style="display:block;margin-top:4px;">%s</span>', esc_html( $field['hint'] ) );
		}

		echo '</p>';
	}

	echo '</div>';
}

/**
 * Save the declared fields.
 *
 * @param int     $post_id Post being saved.
 * @param WP_Post $post    Post object.
 */
function annamleaf_save_fields( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$nonce = isset( $_POST['annamleaf_fields_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['annamleaf_fields_nonce'] ) ) : '';

	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'annamleaf_save_fields' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$groups = annamleaf_field_groups();

	if ( ! isset( $groups[ $post->post_type ] ) ) {
		return;
	}

	foreach ( $groups[ $post->post_type ]['fields'] as $key => $field ) {
		$name = ANNAMLEAF_META_PREFIX . $key;

		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}

		$value = annamleaf_sanitize_field( wp_unslash( $_POST[ $name ] ), $field['type'] ?? 'text' );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $name );
		} else {
			update_post_meta( $post_id, $name, $value );
		}
	}
}
add_action( 'save_post', 'annamleaf_save_fields', 10, 2 );
