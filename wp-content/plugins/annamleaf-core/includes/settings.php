<?php
/**
 * Company profile: the details that appear on more than one page, edited in one place.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

const ANNAMLEAF_OPTION = 'annamleaf_options';

/**
 * Declaration of every company profile field, grouped as it appears on the screen.
 *
 * @return array<string, array{title: string, fields: array}>
 */
function annamleaf_option_fields(): array {
	$fields = array(
		'company' => array(
			'title'  => __( 'Company', 'annamleaf-core' ),
			'fields' => array(
				'company_name'     => array( 'label' => __( 'Trading name', 'annamleaf-core' ), 'type' => 'text' ),
				'legal_name'       => array( 'label' => __( 'Legal name', 'annamleaf-core' ), 'type' => 'text' ),
				'reg_no'           => array( 'label' => __( 'Business registration no.', 'annamleaf-core' ), 'type' => 'text' ),
				'office_address'   => array( 'label' => __( 'Office address', 'annamleaf-core' ), 'type' => 'textarea' ),
				'factory_address'  => array( 'label' => __( 'Factory address', 'annamleaf-core' ), 'type' => 'textarea' ),
				'show_placeholders' => array(
					'label' => __( 'Mark empty fields', 'annamleaf-core' ),
					'type'  => 'checkbox',
					'hint'  => __( 'While the site is being built, every field still missing content is highlighted on the page. Switch this off before launch.', 'annamleaf-core' ),
				),
			),
		),
		'contact' => array(
			'title'  => __( 'Contact', 'annamleaf-core' ),
			'fields' => array(
				'email'    => array( 'label' => __( 'Sales email', 'annamleaf-core' ), 'type' => 'email' ),
				'phone'    => array( 'label' => __( 'Phone', 'annamleaf-core' ), 'type' => 'text' ),
				'whatsapp' => array( 'label' => __( 'WhatsApp / Zalo', 'annamleaf-core' ), 'type' => 'text' ),
				'rfq_to'   => array(
					'label' => __( 'Send enquiries to', 'annamleaf-core' ),
					'type'  => 'email',
					'hint'  => __( 'Where the quote request form delivers. Falls back to the sales email.', 'annamleaf-core' ),
				),
			),
		),
		'figures' => array(
			'title'  => __( 'Capacity figures', 'annamleaf-core' ),
			'fields' => array(
				'stat_1_figure' => array( 'label' => __( 'Figure 1', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_1_label'  => array( 'label' => __( 'Label 1', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_2_figure' => array( 'label' => __( 'Figure 2', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_2_label'  => array( 'label' => __( 'Label 2', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_3_figure' => array( 'label' => __( 'Figure 3', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_3_label'  => array( 'label' => __( 'Label 3', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_4_figure' => array( 'label' => __( 'Figure 4', 'annamleaf-core' ), 'type' => 'text' ),
				'stat_4_label'  => array( 'label' => __( 'Label 4', 'annamleaf-core' ), 'type' => 'text' ),
			),
		),
		'trade'   => array(
			'title'  => __( 'Trade notice', 'annamleaf-core' ),
			'fields' => array(
				'trade_notice' => array(
					'label' => __( 'Footer notice', 'annamleaf-core' ),
					'type'  => 'textarea',
					'hint'  => __( 'Shown in the footer on every page. Keep the site addressed to industrial buyers, not consumers.', 'annamleaf-core' ),
				),
				'age_gate'     => array(
					'label' => __( 'Show the 18+ age gate', 'annamleaf-core' ),
					'type'  => 'checkbox',
					'hint'  => __( 'Asks visitors to confirm they are 18 or older and in the trade before the site is shown.', 'annamleaf-core' ),
				),
			),
		),
	);

	/**
	 * Filter the company profile fields.
	 *
	 * @param array $fields Field groups.
	 */
	return apply_filters( 'annamleaf_option_fields', $fields );
}

/**
 * Flatten the declaration to a key => field map.
 *
 * @return array<string, array>
 */
function annamleaf_option_field_map(): array {
	$map = array();

	foreach ( annamleaf_option_fields() as $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			$map[ $key ] = $field;
		}
	}

	return $map;
}

/**
 * Register the option and its screen.
 */
function annamleaf_register_settings(): void {
	register_setting(
		'annamleaf_settings',
		ANNAMLEAF_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'annamleaf_sanitize_options',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'annamleaf_register_settings' );

/**
 * Sanitise the whole option array against the declaration.
 *
 * @param mixed $input Submitted values.
 * @return array
 */
function annamleaf_sanitize_options( $input ): array {
	$clean = array();
	$map   = annamleaf_option_field_map();
	$input = is_array( $input ) ? $input : array();

	foreach ( $map as $key => $field ) {
		$type  = $field['type'] ?? 'text';
		$value = $input[ $key ] ?? '';

		$clean[ $key ] = match ( $type ) {
			'checkbox' => empty( $value ) ? '' : '1',
			'textarea' => sanitize_textarea_field( (string) $value ),
			'email'    => sanitize_email( (string) $value ),
			default    => sanitize_text_field( (string) $value ),
		};
	}

	return $clean;
}

/**
 * Add the company profile screen.
 */
function annamleaf_settings_menu(): void {
	add_menu_page(
		__( 'Company profile', 'annamleaf-core' ),
		__( 'Company profile', 'annamleaf-core' ),
		'manage_options',
		'annamleaf-settings',
		'annamleaf_render_settings_page',
		'dashicons-building',
		21
	);
}
add_action( 'admin_menu', 'annamleaf_settings_menu' );

/**
 * Render the company profile screen.
 */
function annamleaf_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$values = get_option( ANNAMLEAF_OPTION, array() );
	$values = is_array( $values ) ? $values : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Company profile', 'annamleaf-core' ); ?></h1>

		<?php if ( isset( $_GET['annamleaf_rebuilt'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Default content rebuilt: pages, process stages, leaf types, regions and the menu are back to the delivered version.', 'annamleaf-core' ); ?></p>
			</div>
		<?php endif; ?>
		<p class="description" style="max-width:60em;">
			<?php esc_html_e( 'These details appear across the site — in the header, the footer, the contact page and the capacity strip on the home page. Change them once here.', 'annamleaf-core' ); ?>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'annamleaf_settings' ); ?>

			<?php foreach ( annamleaf_option_fields() as $group_key => $group ) : ?>
				<h2><?php echo esc_html( $group['title'] ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( $group['fields'] as $key => $field ) : ?>
						<?php
						$id    = 'annamleaf-opt-' . $key;
						$name  = ANNAMLEAF_OPTION . '[' . $key . ']';
						$value = (string) ( $values[ $key ] ?? '' );
						$type  = $field['type'] ?? 'text';
						?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $type ) : ?>
									<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
								<?php elseif ( 'checkbox' === $type ) : ?>
									<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( '1', $value ); ?>>
								<?php else : ?>
									<input type="<?php echo 'email' === $type ? 'email' : 'text'; ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
								<?php endif; ?>

								<?php if ( ! empty( $field['hint'] ) ) : ?>
									<p class="description"><?php echo esc_html( $field['hint'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Default content', 'annamleaf-core' ); ?></h2>
		<p class="description" style="max-width:60em;">
			<?php esc_html_e( 'Restores the delivered site: the six pages with their full text, the seven process stages, the leaf types, the regions and the menu. Use it if a page was emptied by accident, or after the site files are updated.', 'annamleaf-core' ); ?>
		</p>
		<p class="description" style="max-width:60em;">
			<strong><?php esc_html_e( 'This overwrites the text of those six pages.', 'annamleaf-core' ); ?></strong>
			<?php esc_html_e( 'Photographs, the company profile above and anything you added yourself are left alone.', 'annamleaf-core' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="annamleaf_rebuild">
			<?php wp_nonce_field( 'annamleaf_rebuild' ); ?>
			<button type="submit" class="button button-secondary"
				onclick="return confirm('<?php echo esc_js( __( 'Rebuild the default pages? Their current text will be replaced.', 'annamleaf-core' ) ); ?>');">
				<?php esc_html_e( 'Rebuild default content', 'annamleaf-core' ); ?>
			</button>
		</form>
	</div>
	<?php
}
