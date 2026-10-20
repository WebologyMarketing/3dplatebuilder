<?php
/**
 * 3dPlateBuilder Shop: a child of Twenty Twenty-Five.
 *
 * inc/brand.php  the shop's colours, heading font and details, from the builder plugin's shop config
 * inc/setup.php  Appearance → Shop set-up (or `wp 3dpb setup --plan=…`): builds a new shop's pages and menus
 *
 * @package 3dpb-shop
 */

defined( 'ABSPATH' ) || exit;

define( 'PBB_SHOP_THEME_VERSION', '1.0.0' );

require_once __DIR__ . '/inc/brand.php';
require_once __DIR__ . '/inc/setup.php';

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( '3dpb-shop', get_stylesheet_uri(), array(), PBB_SHOP_THEME_VERSION );
	}
);

add_action(
	'after_setup_theme',
	function () {
		add_editor_style( 'style.css' );
		// Twenty Twenty-Five's own patterns and styles don't fit a plate shop; keep the editor to ours.
		remove_theme_support( 'core-block-patterns' );
	}
);

add_action(
	'init',
	function () {
		register_block_style( 'core/button', array( 'name' => 'accent', 'label' => __( 'Main colour', '3dpb-shop' ) ) );
		register_block_pattern_category( '3dpb', array( 'label' => __( 'Plate shop', '3dpb-shop' ) ) );
	}
);
