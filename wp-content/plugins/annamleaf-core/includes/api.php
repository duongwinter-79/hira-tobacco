<?php
/**
 * Accessors the theme reads. Templates never touch get_option() or get_post_meta() directly,
 * so the storage can change without touching a template.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * One company profile value.
 *
 * @param string $key     Field key.
 * @param string $default Fallback.
 * @return string
 */
function annamleaf_option( string $key, string $default = '' ): string {
	$values = get_option( ANNAMLEAF_OPTION, array() );

	if ( ! is_array( $values ) || ! isset( $values[ $key ] ) || '' === $values[ $key ] ) {
		return $default;
	}

	return (string) $values[ $key ];
}

/**
 * One custom field on a post.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Field key without the prefix.
 * @param string $default Fallback.
 * @return string
 */
function annamleaf_meta( int $post_id, string $key, string $default = '' ): string {
	$value = get_post_meta( $post_id, ANNAMLEAF_META_PREFIX . $key, true );

	return '' === $value || null === $value ? $default : (string) $value;
}

/**
 * Whether empty fields are marked with a visible placeholder.
 *
 * On a fresh install this is on, so the site shows exactly what content is still missing.
 * Turn it off on the Company profile screen before launch.
 *
 * @return bool
 */
function annamleaf_placeholders_enabled(): bool {
	$values = get_option( ANNAMLEAF_OPTION, null );

	// Never saved: this is a fresh build, show the gaps.
	$enabled = ! is_array( $values ) ? true : ! empty( $values['show_placeholders'] );

	/**
	 * Filter whether placeholder markers are shown.
	 *
	 * @param bool $enabled Whether to mark empty fields.
	 */
	return (bool) apply_filters( 'annamleaf_placeholders_enabled', $enabled );
}

/**
 * Markup for one placeholder marker.
 *
 * @param string $label What the client still has to supply.
 * @return string
 */
function annamleaf_placeholder( string $label ): string {
	if ( ! annamleaf_placeholders_enabled() ) {
		return '';
	}

	return '<span class="ph">[' . esc_html( $label ) . ']</span>';
}

/**
 * A value if it exists, otherwise a placeholder marker. Returns escaped, printable HTML.
 *
 * @param string $value       The stored value.
 * @param string $placeholder Label for the marker when the value is empty.
 * @return string
 */
function annamleaf_field( string $value, string $placeholder = '' ): string {
	if ( '' !== $value ) {
		return nl2br( esc_html( $value ) );
	}

	return '' === $placeholder ? '' : annamleaf_placeholder( $placeholder );
}

/**
 * A company profile value, or its placeholder.
 *
 * @param string $key         Field key.
 * @param string $placeholder Label for the marker.
 * @return string
 */
function annamleaf_option_field( string $key, string $placeholder = '' ): string {
	return annamleaf_field( annamleaf_option( $key ), $placeholder );
}

/**
 * The four capacity figures for the strip under the hero.
 *
 * @return array<int, array{figure: string, label: string}>
 */
function annamleaf_stats(): array {
	$defaults = array(
		array( 'X', __( 'Contracted growing area', 'annamleaf-core' ) ),
		array( 'X', __( 'Farming households', 'annamleaf-core' ) ),
		array( 'X,000 MT', __( 'Processed leaf per year', 'annamleaf-core' ) ),
		array( 'X', __( 'Export markets', 'annamleaf-core' ) ),
	);

	$stats = array();

	foreach ( $defaults as $index => $fallback ) {
		$number = $index + 1;
		$figure = annamleaf_option( 'stat_' . $number . '_figure' );
		$label  = annamleaf_option( 'stat_' . $number . '_label' );

		$stats[] = array(
			'figure' => '' !== $figure ? esc_html( $figure ) : annamleaf_placeholder( $fallback[0] ),
			'label'  => '' !== $label ? esc_html( $label ) : esc_html( $fallback[1] ),
		);
	}

	return array_values(
		array_filter(
			$stats,
			static function ( array $stat ): bool {
				return '' !== $stat['figure'];
			}
		)
	);
}

/**
 * The address the quote request form delivers to.
 *
 * @return string
 */
function annamleaf_rfq_recipient(): string {
	$to = annamleaf_option( 'rfq_to', annamleaf_option( 'email' ) );

	return $to && is_email( $to ) ? $to : (string) get_option( 'admin_email' );
}

/**
 * Posts of one Annam Leaf content type, in the order the client arranged them.
 *
 * @param string $post_type Post type.
 * @param int    $limit     How many.
 * @return WP_Post[]
 */
function annamleaf_get_items( string $post_type, int $limit = 20 ): array {
	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'numberposts'      => $limit,
			'orderby'          => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'suppress_filters' => false,
		)
	);

	return is_array( $posts ) ? $posts : array();
}
