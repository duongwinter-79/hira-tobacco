<?php
/**
 * Plugin Name:       Annam Leaf Core
 * Plugin URI:        https://annamleaf.com/
 * Description:       Content structure for the Annam Leaf website: process stages, leaf types, growing regions, page heroes and the company profile settings. Lives in a plugin, not the theme, so the content survives a redesign.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Text Domain:       annamleaf-core
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

define( 'ANNAMLEAF_CORE_VERSION', '1.0.0' );
define( 'ANNAMLEAF_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once ANNAMLEAF_CORE_DIR . 'includes/post-types.php';
require_once ANNAMLEAF_CORE_DIR . 'includes/meta.php';
require_once ANNAMLEAF_CORE_DIR . 'includes/settings.php';
require_once ANNAMLEAF_CORE_DIR . 'includes/api.php';
require_once ANNAMLEAF_CORE_DIR . 'includes/seed.php';

/**
 * On activation: register the types, fill the site with the demo structure once, and
 * flush the rewrite rules so /process/ and /our-leaf/ resolve.
 */
function annamleaf_core_activate(): void {
	annamleaf_register_post_types();
	annamleaf_seed_content();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'annamleaf_core_activate' );

/**
 * Clean up the rewrite rules when the plugin is switched off.
 */
function annamleaf_core_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'annamleaf_core_deactivate' );

/**
 * Load translations for the admin labels.
 */
function annamleaf_core_load_textdomain(): void {
	load_plugin_textdomain( 'annamleaf-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'annamleaf_core_load_textdomain' );
