<?php
/**
 * Site header.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'annamleaf' ); ?></a>

<?php annamleaf_svg_sprite(); ?>

<header class="site-head">
	<div class="wrap">
		<?php if ( has_custom_logo() ) : ?>
			<div class="brand brand--logo"><?php the_custom_logo(); ?></div>
		<?php else : ?>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php annamleaf_leaf_mark(); ?>
				<span>
					<span class="wordmark"><?php echo wp_kses_post( annamleaf_company_name() ); ?></span><br>
					<span class="tag"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></span>
				</span>
			</a>
		<?php endif; ?>

		<button class="burger" id="burger" aria-expanded="false" aria-controls="primary-nav">
			<span aria-hidden="true">&#9776;</span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'annamleaf' ); ?></span>
		</button>

		<nav class="nav" id="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'annamleaf' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => 'annamleaf_default_menu',
				)
			);
			?>
		</nav>

		<?php annamleaf_language_switcher(); ?>
	</div>
</header>

<main id="main">
