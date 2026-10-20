<?php
/**
 * Builds a new shop's pages from the theme's patterns: Appearance → Shop set-up, or
 * `wp 3dpb setup --plan=starter|pro|complete`.
 *
 * Starter:  Home (the one-page shop), Help and contact, Valid documents, Terms, Privacy.
 * Pro and Complete: also a Pro home page, Design your plates, a page for each of the 7 styles,
 *           and a header menu for them.
 * Re-running is safe: existing pages are left alone unless --force is given.
 *
 * @package 3dpb-shop
 */

defined( 'ABSPATH' ) || exit;

class PBB_Shop_Setup {

	/** What happened, line by line. */
	public $log = array();

	const STYLES = array(
		'printed'      => 'Printed',
		'gel-3d'       => '3D Gel',
		'acrylic-4d-3' => '4D 3mm',
		'acrylic-4d-6' => '4D 6mm',
		'gel-4d-3'     => '4D Gel 3mm',
		'gel-4d-6'     => '4D Gel 6mm',
		'retro'        => 'Retro',
	);

	/**
	 * Set up a new shop's pages, menus and front page.
	 *
	 * @param string $plan  starter, pro or complete.
	 * @param bool   $force Replace the content of pages that already exist.
	 * @return true|WP_Error
	 */
	public function run( $plan, $force = false ) {
		if ( ! in_array( $plan, array( 'starter', 'pro', 'complete' ), true ) ) {
			return new WP_Error( 'pbb_plan', 'Choose Starter, Pro or Complete.' );
		}
		$pro = 'starter' !== $plan;

		$home_markup = $this->pattern( $pro ? '3dpb-shop/pro-home' : '3dpb-shop/starter-home' );
		if ( is_wp_error( $home_markup ) ) {
			return $home_markup;
		}
		$home = $this->page( 'home', 'Home', $home_markup, 'page-landing', $force );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );

		$help = $this->pattern( '3dpb-shop/help-page' );
		$this->page( 'contact-us', 'Help and contact', $help, '', $force );
		$this->page( 'valid-documents', 'Documents we need', $this->section( $help, 'Documents you need', 'Delivery' ), '', $force );
		$this->page( 'terms', 'Terms and conditions', "<!-- wp:paragraph -->\n<p>Your terms of sale go here.</p>\n<!-- /wp:paragraph -->", '', false );
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( ! $privacy || 'publish' !== get_post_status( $privacy ) ) {
			$privacy = $this->page( 'privacy', 'Privacy', "<!-- wp:paragraph -->\n<p>Your privacy notice goes here.</p>\n<!-- /wp:paragraph -->", '', false );
			update_option( 'wp_page_for_privacy_policy', $privacy );
		}

		// The pages embed the builder themselves, so the plugin doesn't add it to the front page too.
		if ( function_exists( 'pbb_shop' ) ) {
			$shop                       = (array) get_option( 'pbb_shop', array() );
			$shop['feature_front_page'] = false;
			update_option( 'pbb_shop', $shop );
		}

		if ( $pro ) {
			$this->page( 'design-your-plates', 'Design your plates', "<!-- wp:shortcode -->\n[plate_builder]\n<!-- /wp:shortcode -->", 'page-landing', $force );
			update_option( 'pbb_builder_page_id', (int) get_page_by_path( 'design-your-plates' )->ID, false );
			$style_page = $this->pattern( '3dpb-shop/style-page' );
			foreach ( self::STYLES as $id => $name ) {
				$content = str_replace( array( 'gel-4d-3', '4D Gel 3mm' ), array( $id, $name ), $style_page );
				// Leave this style out of its own "Other styles" list.
				$content = preg_replace( '#<!-- wp:button \{"className":"is-style-outline pbb-style-' . preg_quote( $id, '#' ) . '"\} -->.*?<!-- /wp:button -->\s*#s', '', $content );
				$this->page( sanitize_title( $name ) . '-number-plates', $name . ' number plates', $content, 'page-landing', $force );
			}
			$this->header_menu();
		} else {
			$this->reset_header();
		}

		foreach ( array( 'cart' => 'Your basket', 'checkout' => 'Checkout' ) as $wc_page => $wc_title ) {
			$wc_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( $wc_page ) : 0;
			if ( $wc_id > 0 ) {
				wp_update_post( array( 'ID' => $wc_id, 'post_title' => $wc_title ) );
			}
		}

		update_option( 'woocommerce_currency', 'GBP' );
		if ( 0 !== strpos( (string) get_option( 'woocommerce_default_country' ), 'GB' ) ) {
			update_option( 'woocommerce_default_country', 'GB' );
		}
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		update_option( 'woocommerce_ship_to_countries', '' );
		flush_rewrite_rules();

		$this->log[] = ucfirst( $plan ) . ' shop set up. Edit the wording in Pages, and the header and footer in Appearance → Editor.';
		return true;
	}

	/** Create a page (or update it with --force). Returns its id. */
	private function page( $slug, $title, $content, $template, $force ) {
		$existing = get_page_by_path( $slug );
		if ( $existing && ! $force ) {
			$this->log[] = "Kept: {$title}";
			return (int) $existing->ID;
		}
		$id = wp_insert_post(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			$this->log[] = $title . ': ' . $id->get_error_message();
			return 0;
		}
		update_post_meta( $id, '_wp_page_template', $template ? $template : 'default' );
		$this->log[] = ( $existing ? 'Updated: ' : 'Created: ' ) . $title;
		return (int) $id;
	}

	/** A registered pattern's markup, with any patterns it includes written out in full. */
	private function pattern( $slug, $depth = 0 ) {
		$p = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
		if ( ! $p ) {
			return new WP_Error( 'pbb_pattern', "Pattern {$slug} not found. Is the 3dPlateBuilder Shop theme active?" );
		}
		return preg_replace_callback(
			'#<!-- wp:pattern \{"slug":"([^"]+)"\} /-->#',
			function ( $m ) use ( $depth ) {
				$inner = $depth < 3 ? $this->pattern( $m[1], $depth + 1 ) : '';
				return is_string( $inner ) ? $inner : '';
			},
			$p['content']
		);
	}

	/** The markup from one heading up to the next one. */
	private function section( $markup, $from, $to ) {
		$start = strpos( $markup, $from );
		$end   = strpos( $markup, $to, (int) $start );
		if ( false === $start || false === $end ) {
			return $markup;
		}
		$start = strrpos( substr( $markup, 0, $start ), '<!-- wp:heading' );
		$end   = strrpos( substr( $markup, 0, $end ), '<!-- wp:heading' );
		return trim( substr( $markup, $start, $end - $start ) );
	}

	/** Starter: back to the theme's own header (anchors on the one page). */
	private function reset_header() {
		foreach ( $this->header_overrides() as $post ) {
			wp_delete_post( $post->ID, true );
			$this->log[] = 'Header menu set for Starter.';
		}
	}

	/** Saved copies of the header that replace the theme's file. */
	private function header_overrides() {
		return get_posts(
			array(
				'post_type'      => 'wp_template_part',
				'name'           => 'header',
				'posts_per_page' => 5,
				'post_status'    => 'any',
				'tax_query'      => array( array( 'taxonomy' => 'wp_theme', 'field' => 'name', 'terms' => get_stylesheet() ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);
	}

	/** Pro header: Number plates (the styles), Sizes and badges in the builder, Help, Contact. */
	private function header_menu() {
		$file = get_stylesheet_directory() . '/parts/header.html';
		$html = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$sub  = '';
		foreach ( self::STYLES as $name ) {
			$sub .= '<!-- wp:navigation-link {"label":"' . $name . '","url":"/' . sanitize_title( $name ) . '-number-plates/","kind":"custom"} /-->' . "\n";
		}
		$links = '<!-- wp:navigation-submenu {"label":"Number plates","url":"/#styles","kind":"custom"} -->' . "\n" . $sub . '<!-- /wp:navigation-submenu -->' . "\n"
			. '<!-- wp:navigation-link {"label":"Design your plates","url":"/design-your-plates/","kind":"custom"} /-->' . "\n"
			. '<!-- wp:navigation-link {"label":"Help","url":"/contact-us/","kind":"custom"} /-->' . "\n";
		$html = preg_replace( '#(<!-- wp:navigation \{"overlayMenu":"mobile".*?-->\n).*?(<!-- /wp:navigation -->)#s', '$1' . $links . '$2', $html, 1 );

		$theme    = get_stylesheet();
		$existing = $this->header_overrides();
		$id = wp_insert_post(
			array(
				'ID'           => $existing ? $existing[0]->ID : 0,
				'post_type'    => 'wp_template_part',
				'post_status'  => 'publish',
				'post_name'    => 'header',
				'post_title'   => 'Header',
				'post_content' => $html,
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			wp_set_object_terms( $id, $theme, 'wp_theme' );
			wp_set_object_terms( $id, 'header', 'wp_template_part_area' );
			$this->log[] = 'Header menu set for Pro.';
		}
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'3dpb setup',
		/**
		 * Set up a new shop's pages, menus and front page.
		 *
		 * ## OPTIONS
		 *
		 * --plan=<plan>
		 * : starter, pro or complete.
		 *
		 * [--force]
		 * : Replace the content of pages that already exist.
		 */
		function ( $args, $assoc ) {
			$setup  = new PBB_Shop_Setup();
			$result = $setup->run( $assoc['plan'] ?? '', ! empty( $assoc['force'] ) );
			foreach ( $setup->log as $line ) {
				WP_CLI::log( $line );
			}
			is_wp_error( $result ) ? WP_CLI::error( $result->get_error_message() ) : WP_CLI::success( 'Done.' );
		}
	);
}

/* Appearance → Shop set-up: the same, with a button. */
add_action(
	'admin_menu',
	function () {
		add_theme_page( 'Shop set-up', 'Shop set-up', 'manage_options', 'pbb-shop-setup', 'pbb_shop_setup_screen' );
	}
);

function pbb_shop_setup_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$log = array();
	if ( isset( $_POST['pbb_plan'] ) && check_admin_referer( 'pbb_shop_setup' ) ) {
		$setup  = new PBB_Shop_Setup();
		$result = $setup->run( sanitize_key( wp_unslash( $_POST['pbb_plan'] ) ), ! empty( $_POST['pbb_force'] ) );
		$log    = is_wp_error( $result ) ? array( $result->get_error_message() ) : $setup->log;
	}
	?>
	<div class="wrap">
		<h1>Shop set-up</h1>
		<p>Builds this shop's pages from the theme: the home page for the plan, help and contact, documents, terms and privacy. Pro and Complete also get the Design your plates page, a page for each of the seven styles and the header menu.</p>
		<p>Pages that already exist are kept, unless you tick "Replace existing pages".</p>
		<?php if ( $log ) : ?>
			<div class="notice notice-success"><p><?php echo implode( '<br>', array_map( 'esc_html', $log ) ); ?></p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'pbb_shop_setup' ); ?>
			<p>
				<label><input type="radio" name="pbb_plan" value="starter" checked> Starter (one page)</label><br>
				<label><input type="radio" name="pbb_plan" value="pro"> Pro</label><br>
				<label><input type="radio" name="pbb_plan" value="complete"> Complete</label>
			</p>
			<p><label><input type="checkbox" name="pbb_force" value="1"> Replace existing pages (their wording is lost)</label></p>
			<?php submit_button( 'Set up the shop' ); ?>
		</form>
	</div>
	<?php
}
