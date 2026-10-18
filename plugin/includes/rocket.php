<?php
/**
 * WP Rocket: leave the builder's files alone.
 *
 * The builder is a set of JavaScript modules. Browsers only load modules from another domain when
 * that domain sends CORS headers, and RocketCDN doesn't, so rewriting them to the CDN (or delaying
 * them until the visitor interacts) leaves visitors on the loading screen. Logged-in admins never
 * see this, because WP Rocket doesn't optimise their pages.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

/** URL path of the plugin folder, e.g. /wp-content/plugins/plateboss-builder/ */
function pbb_rocket_path() {
	return (string) wp_parse_url( PBB_URL, PHP_URL_PATH );
}

// Serve the builder's files from this site, not the CDN.
add_filter(
	'rocket_cdn_reject_files',
	function ( $files ) {
		$files   = (array) $files;
		$files[] = pbb_rocket_path() . '(.*)';
		return $files;
	}
);

// Run the builder straight away, not on the visitor's first tap or scroll.
add_filter(
	'rocket_delay_js_exclusions',
	function ( $exclusions ) {
		$exclusions   = (array) $exclusions;
		$exclusions[] = 'plateboss-builder';
		$exclusions[] = 'wp-importmap';
		return $exclusions;
	}
);

// Never minify, combine or defer the modules.
foreach ( array( 'rocket_exclude_js', 'rocket_exclude_defer_js' ) as $pbb_rocket_filter ) {
	add_filter(
		$pbb_rocket_filter,
		function ( $excluded ) {
			$excluded   = (array) $excluded;
			$excluded[] = pbb_rocket_path() . '(.*)';
			return $excluded;
		}
	);
}
unset( $pbb_rocket_filter );

// After each plugin update, clear WP Rocket's cached pages once so visitors get the new markup.
add_action(
	'admin_init',
	function () {
		if ( get_option( 'pbb_rocket_cleaned' ) === PBB_VERSION || ! function_exists( 'rocket_clean_domain' ) ) {
			return;
		}
		update_option( 'pbb_rocket_cleaned', PBB_VERSION, false );
		rocket_clean_domain();
	}
);
