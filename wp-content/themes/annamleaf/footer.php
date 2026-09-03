<?php
/**
 * Site footer.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

$annamleaf_notice = annamleaf_get(
	'trade_notice',
	__( 'This site is intended for industrial buyers and trade partners. It is not directed at consumers.', 'annamleaf' )
);
?>
</main>

<footer class="site-foot">
	<div class="wrap">
		<div class="foot-top">
			<div>
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" style="margin-bottom:14px;">
					<?php annamleaf_leaf_mark(); ?>
					<span>
						<span class="wordmark"><?php echo wp_kses_post( annamleaf_company_name() ); ?></span><br>
						<span class="tag"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></span>
					</span>
				</a>
				<p style="max-width:38ch;font-size:13.5px;">
					<?php echo wp_kses_post( annamleaf_get_field( 'legal_name', __( 'COMPANY LEGAL NAME', 'annamleaf' ) ) ); ?><br>
					<?php echo wp_kses_post( annamleaf_get_field( 'office_address', __( 'REGISTERED ADDRESS', 'annamleaf' ) ) ); ?><br>
					<?php esc_html_e( 'Business reg.', 'annamleaf' ); ?>
					<?php echo wp_kses_post( annamleaf_get_field( 'reg_no', __( 'NO.', 'annamleaf' ) ) ); ?>
				</p>
			</div>

			<div>
				<h4><?php esc_html_e( 'Site', 'annamleaf' ); ?></h4>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'depth'          => 1,
						)
					);
				} else {
					annamleaf_default_menu();
				}
				?>
			</div>

			<div>
				<h4><?php esc_html_e( 'Contact', 'annamleaf' ); ?></h4>
				<ul>
					<li><?php echo wp_kses_post( annamleaf_get_field( 'email', 'sales@annamleaf.com' ) ); ?></li>
					<li><?php echo wp_kses_post( annamleaf_get_field( 'phone', '+84 …' ) ); ?></li>
					<li><?php echo wp_kses_post( annamleaf_get_field( 'office_address', __( 'OFFICE ADDRESS', 'annamleaf' ) ) ); ?></li>
				</ul>
			</div>
		</div>

		<div class="foot-bot">
			<p><?php echo esc_html( $annamleaf_notice ); ?></p>
			<span class="agebadge">18+</span>
			<p>
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<?php echo wp_kses_post( annamleaf_get_field( 'legal_name', __( 'COMPANY LEGAL NAME', 'annamleaf' ) ) ); ?>
			</p>
		</div>
	</div>
</footer>

<?php annamleaf_age_gate(); ?>
<?php wp_footer(); ?>
</body>
</html>
