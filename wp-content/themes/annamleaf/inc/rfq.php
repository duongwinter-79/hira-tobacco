<?php
/**
 * The quote request form.
 *
 * Posts to admin-post.php, validates, and emails the address set on the Company profile
 * screen. No enquiry data is stored in the database.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * The fields of the enquiry form.
 *
 * @return array<string, array{label: string, type: string, required?: bool, options?: array}>
 */
function annamleaf_rfq_fields(): array {
	$leaf_types = array();

	if ( function_exists( 'annamleaf_get_items' ) ) {
		foreach ( annamleaf_get_items( 'annam_leaf', 12 ) as $leaf ) {
			$leaf_types[] = get_the_title( $leaf );
		}
	}

	if ( empty( $leaf_types ) ) {
		$leaf_types = array(
			__( 'Flue-cured Virginia', 'annamleaf' ),
			__( 'Burley', 'annamleaf' ),
			__( 'Oriental', 'annamleaf' ),
			__( 'Dark air-cured', 'annamleaf' ),
			__( 'Cut rag / scrap', 'annamleaf' ),
		);
	}

	return array(
		'name'    => array( 'label' => __( 'Your name', 'annamleaf' ), 'type' => 'text', 'required' => true, 'width' => 'full' ),
		'company' => array( 'label' => __( 'Company', 'annamleaf' ), 'type' => 'text' ),
		'email'   => array( 'label' => __( 'Work email', 'annamleaf' ), 'type' => 'email', 'required' => true ),
		'type'    => array( 'label' => __( 'Leaf type', 'annamleaf' ), 'type' => 'select', 'options' => $leaf_types ),
		'volume'  => array( 'label' => __( 'Volume (MT)', 'annamleaf' ), 'type' => 'text' ),
		'port'    => array( 'label' => __( 'Destination port', 'annamleaf' ), 'type' => 'text', 'width' => 'full' ),
		'message' => array( 'label' => __( 'Grades, specifications or anything else', 'annamleaf' ), 'type' => 'textarea', 'width' => 'full' ),
	);
}

/**
 * Print the enquiry form.
 */
function annamleaf_rfq_form(): void {
	$status = isset( $_GET['rfq'] ) ? sanitize_key( wp_unslash( $_GET['rfq'] ) ) : '';
	?>
	<form class="form" id="rfq" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="annamleaf_rfq">
		<?php wp_nonce_field( 'annamleaf_rfq', 'annamleaf_rfq_nonce' ); ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">

		<p class="honey" aria-hidden="true">
			<label for="annamleaf-website"><?php esc_html_e( 'Leave this field empty', 'annamleaf' ); ?></label>
			<input type="text" id="annamleaf-website" name="annamleaf_website" tabindex="-1" autocomplete="off">
		</p>

		<?php foreach ( annamleaf_rfq_fields() as $key => $field ) : ?>
			<?php
			$id       = 'rfq-' . $key;
			$width    = ( $field['width'] ?? '' ) === 'full' ? ' full' : '';
			$required = ! empty( $field['required'] );
			?>
			<div class="field<?php echo esc_attr( $width ); ?>">
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' *' : ''; ?>
				</label>

				<?php if ( 'textarea' === $field['type'] ) : ?>
					<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>"></textarea>
				<?php elseif ( 'select' === $field['type'] ) : ?>
					<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>">
						<?php foreach ( (array) ( $field['options'] ?? array() ) as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input
						type="<?php echo 'email' === $field['type'] ? 'email' : 'text'; ?>"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $key ); ?>"
						<?php echo $required ? 'required' : ''; ?>>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="full">
			<button class="btn" type="submit"><?php esc_html_e( 'Send request', 'annamleaf' ); ?></button>
			<p class="formnote" style="margin-top:12px;">
				<?php esc_html_e( 'We supply leaf to industrial buyers only. Your details are used solely to answer this enquiry.', 'annamleaf' ); ?>
			</p>
		</div>

		<?php if ( 'sent' === $status ) : ?>
			<p class="formmsg"><?php esc_html_e( 'Thank you — your request is with our sales desk. We reply within five working days.', 'annamleaf' ); ?></p>
		<?php elseif ( 'error' === $status ) : ?>
			<p class="formmsg formmsg--error"><?php esc_html_e( 'Please fill in your name and a valid work email, then send again.', 'annamleaf' ); ?></p>
		<?php elseif ( 'failed' === $status ) : ?>
			<p class="formmsg formmsg--error">
				<?php
				printf(
					/* translators: %s: sales email address. */
					esc_html__( 'The message could not be sent. Please email us directly at %s.', 'annamleaf' ),
					esc_html( annamleaf_get( 'email', get_option( 'admin_email' ) ) )
				);
				?>
			</p>
		<?php endif; ?>
	</form>
	<?php
}

/**
 * Handle the submitted enquiry.
 */
function annamleaf_rfq_handle(): void {
	$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );
	$nonce    = isset( $_POST['annamleaf_rfq_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['annamleaf_rfq_nonce'] ) ) : '';

	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'annamleaf_rfq' ) ) {
		annamleaf_rfq_redirect( $redirect, 'error' );
	}

	// A bot filled the hidden field: drop it silently.
	if ( ! empty( $_POST['annamleaf_website'] ) ) {
		annamleaf_rfq_redirect( $redirect, 'sent' );
	}

	$values = array();

	foreach ( annamleaf_rfq_fields() as $key => $field ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';

		$values[ $key ] = match ( $field['type'] ) {
			'textarea' => sanitize_textarea_field( (string) $raw ),
			'email'    => sanitize_email( (string) $raw ),
			default    => sanitize_text_field( (string) $raw ),
		};
	}

	if ( '' === $values['name'] || ! is_email( $values['email'] ) ) {
		annamleaf_rfq_redirect( $redirect, 'error' );
	}

	$lines = array();

	foreach ( annamleaf_rfq_fields() as $key => $field ) {
		if ( '' !== $values[ $key ] ) {
			$lines[] = $field['label'] . ': ' . $values[ $key ];
		}
	}

	$lines[] = '';
	$lines[] = __( 'Sent from', 'annamleaf' ) . ': ' . $redirect;

	$to      = function_exists( 'annamleaf_rfq_recipient' ) ? annamleaf_rfq_recipient() : (string) get_option( 'admin_email' );
	$subject = sprintf(
		/* translators: %s: company or person who sent the enquiry. */
		__( 'Quote request — %s', 'annamleaf' ),
		'' !== $values['company'] ? $values['company'] : $values['name']
	);

	$sent = wp_mail(
		$to,
		$subject,
		implode( "\n", $lines ),
		array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $values['name'] . ' <' . $values['email'] . '>',
		)
	);

	annamleaf_rfq_redirect( $redirect, $sent ? 'sent' : 'failed' );
}
add_action( 'admin_post_nopriv_annamleaf_rfq', 'annamleaf_rfq_handle' );
add_action( 'admin_post_annamleaf_rfq', 'annamleaf_rfq_handle' );

/**
 * Send the visitor back to the form with a result.
 *
 * @param string $url    Page the form was on.
 * @param string $status sent, error or failed.
 */
function annamleaf_rfq_redirect( string $url, string $status ): void {
	wp_safe_redirect( add_query_arg( 'rfq', $status, $url ) . '#rfq' );
	exit;
}
