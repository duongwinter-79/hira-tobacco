<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * The contact page: company details from the company profile, and the quote request form.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="sec">
		<div class="wrap split split--text">
			<div>
				<p class="eyebrow"><?php echo esc_html( annamleaf_get_meta( get_the_ID(), 'section_eyebrow', __( 'Contact', 'annamleaf' ) ) ); ?></p>
				<h2><?php echo esc_html( annamleaf_get_meta( get_the_ID(), 'section_heading', get_the_title() ) ); ?></h2>

				<div class="entry-content" style="margin-top:18px;"><?php the_content(); ?></div>

				<ul class="flist" style="margin-top:30px;">
					<li>
						<span class="k"><?php esc_html_e( 'Office', 'annamleaf' ); ?></span>
						<span class="v"><?php echo wp_kses_post( annamleaf_get_field( 'office_address', __( 'OFFICE ADDRESS', 'annamleaf' ) ) ); ?></span>
					</li>
					<li>
						<span class="k"><?php esc_html_e( 'Factory', 'annamleaf' ); ?></span>
						<span class="v"><?php echo wp_kses_post( annamleaf_get_field( 'factory_address', __( 'FACTORY ADDRESS', 'annamleaf' ) ) ); ?></span>
					</li>
					<li>
						<span class="k"><?php esc_html_e( 'Email', 'annamleaf' ); ?></span>
						<span class="v">
							<?php
							$annamleaf_email = annamleaf_get( 'email' );

							if ( '' !== $annamleaf_email ) {
								printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $annamleaf_email ) );
							} else {
								echo wp_kses_post( annamleaf_ph( 'sales@annamleaf.com' ) );
							}
							?>
						</span>
					</li>
					<li>
						<span class="k"><?php esc_html_e( 'Phone', 'annamleaf' ); ?></span>
						<span class="v">
							<?php echo wp_kses_post( annamleaf_get_field( 'phone', '+84 …' ) ); ?>
							<?php
							$annamleaf_whatsapp = annamleaf_get( 'whatsapp' );

							if ( '' !== $annamleaf_whatsapp ) {
								echo ' · ' . esc_html( $annamleaf_whatsapp );
							}
							?>
						</span>
					</li>
				</ul>
			</div>

			<?php annamleaf_rfq_form(); ?>
		</div>
	</section>
	<?php
endwhile;

get_footer();
