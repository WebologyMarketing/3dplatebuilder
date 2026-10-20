<?php
/**
 * Sync from the 3dPlateBuilder portal (app.3dplatebuilder.com).
 *
 * The plate maker sets their builder prices, options, own add-ons and products in their account
 * there and clicks Publish. This shop then pulls the published copy with its licence key and
 * applies it: prices (pbb_pricing_v2), what's on sale (pbb_offer), add-on names
 * (pbb_custom_accessories), supplier and brand (pbb_shop), and WooCommerce products.
 *
 * It pulls when the portal nudges it (POST /wp-json/pbb/v1/sync, signed with the licence key),
 * every hour as a fallback, and from "Sync now" under WooCommerce → Plate Builder.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

const PBB_SYNC_HOOK = 'pbb_portal_sync';

/** The portal's address. */
function pbb_portal_url() {
	return untrailingslashit( (string) apply_filters( 'pbb_portal_url', 'https://app.3dplatebuilder.com' ) );
}

/** The shop's licence key (WooCommerce → Plate Builder). */
function pbb_licence_key() {
	return strtoupper( trim( (string) get_option( 'pbb_licence_key', '' ) ) );
}

/** Last sync: version, time, error. */
function pbb_sync_state() {
	return wp_parse_args( (array) get_option( 'pbb_sync_state', array() ), array( 'version' => 0, 'synced_at' => 0, 'checked_at' => 0, 'error' => '' ) );
}

/** True once the portal manages this shop's prices and products. */
function pbb_portal_managed() {
	return '' !== pbb_licence_key() && pbb_sync_state()['version'] > 0;
}

/* Hourly check, plus a one-off run when the portal nudges us. */
add_action(
	'init',
	function () {
		if ( pbb_licence_key() && ! wp_next_scheduled( PBB_SYNC_HOOK ) ) {
			wp_schedule_event( time() + 300, 'hourly', PBB_SYNC_HOOK );
		}
	}
);
add_action(
	PBB_SYNC_HOOK,
	function ( $force = false ) {
		pbb_portal_sync( (bool) $force );
	}
);
register_deactivation_hook(
	PBB_PATH . '3d-plate-builder.php',
	function () {
		wp_clear_scheduled_hook( PBB_SYNC_HOOK );
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'pbb/v1',
			'/sync',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'pbb_sync_request_is_signed',
				'callback'            => function () {
					// Run straight after this request, so the portal isn't kept waiting while images download.
					if ( ! wp_next_scheduled( PBB_SYNC_HOOK, array( true ) ) ) {
						wp_schedule_single_event( time() - 1, PBB_SYNC_HOOK, array( true ) );
					}
					spawn_cron();
					return new WP_REST_Response( array( 'ok' => true ), 202 );
				},
			)
		);
	}
);

/** Only the portal can trigger a sync: an HMAC of the time and version, keyed with the licence key. */
function pbb_sync_request_is_signed( WP_REST_Request $request ) {
	$key     = pbb_licence_key();
	$time    = (string) $request->get_header( 'x_pbb_time' );
	$version = (string) $request->get_header( 'x_pbb_version' );
	$sig     = (string) $request->get_header( 'x_pbb_signature' );
	if ( '' === $key || '' === $sig || ! ctype_digit( $time ) || abs( time() - (int) $time ) > 300 ) {
		return false;
	}
	return hash_equals( hash_hmac( 'sha256', $time . '.' . $version, $key ), $sig );
}

/**
 * Fetch the published settings and apply them if they're newer than what we have.
 *
 * @param bool $force Apply even if the version hasn't changed.
 * @return true|WP_Error
 */
function pbb_portal_sync( $force = false ) {
	$key   = pbb_licence_key();
	$state = pbb_sync_state();
	if ( '' === $key ) {
		return new WP_Error( 'pbb_no_key', __( 'Add your licence key first.', '3d-plate-builder' ) );
	}

	$response = wp_remote_get(
		pbb_portal_url() . '/api/v1/site',
		array(
			'timeout' => 20,
			'headers' => array(
				'X-Licence-Key' => $key,
				'Accept'        => 'application/json',
			),
		)
	);
	$state['checked_at'] = time();
	$code                = (int) wp_remote_retrieve_response_code( $response );
	$data                = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) || 200 !== $code || ! is_array( $data ) ) {
		$state['error'] = is_wp_error( $response ) ? $response->get_error_message()
			: ( is_array( $data ) && ! empty( $data['error'] ) ? (string) $data['error'] : sprintf( /* translators: %d: HTTP status */ __( 'The portal replied with status %d.', '3d-plate-builder' ), $code ) );
		update_option( 'pbb_sync_state', $state, false );
		return new WP_Error( 'pbb_sync', $state['error'] );
	}

	$version = isset( $data['version'] ) ? (int) $data['version'] : 0;
	if ( ! $version || ( ! $force && $version <= (int) $state['version'] ) ) {
		$state['error'] = '';
		update_option( 'pbb_sync_state', $state, false );
		return true;
	}

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- not allowed on some hosts.
	}

	pbb_apply_builder( isset( $data['builder'] ) && is_array( $data['builder'] ) ? $data['builder'] : array() );
	pbb_apply_shop( isset( $data['shop'] ) && is_array( $data['shop'] ) ? $data['shop'] : array() );
	$errors = pbb_apply_products(
		isset( $data['products'] ) && is_array( $data['products'] ) ? $data['products'] : array(),
		isset( $data['labels']['styles'] ) && is_array( $data['labels']['styles'] ) ? $data['labels']['styles'] : array()
	);

	$state = array_merge(
		$state,
		array(
			'version'   => $version,
			'synced_at' => time(),
			'error'     => $errors ? implode( ' ', array_slice( $errors, 0, 3 ) ) : '',
		)
	);
	update_option( 'pbb_sync_state', $state, false );

	// Tell the portal we have it, so the plate maker sees "Your website has it".
	wp_remote_post(
		pbb_portal_url() . '/api/v1/site/applied',
		array(
			'timeout'  => 10,
			'blocking' => false,
			'headers'  => array(
				'X-Licence-Key' => $key,
				'Accept'        => 'application/json',
			),
			'body'     => array( 'version' => $version ),
		)
	);

	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}
	do_action( 'pbb_portal_synced', $version, $data );
	return true;
}

/** Prices, what's on sale and the shop's own add-ons. */
function pbb_apply_builder( array $b ) {
	$c = pbb_catalog();

	if ( isset( $b['pricing'] ) && is_array( $b['pricing'] ) ) {
		$pricing = array();
		foreach ( $b['pricing'] as $group => $values ) {
			if ( ! is_array( $values ) ) {
				continue;
			}
			foreach ( $values as $id => $price ) {
				if ( is_numeric( $price ) ) {
					$pricing[ sanitize_key( $group ) ][ sanitize_key( $id ) ] = max( 0, round( (float) $price, 2 ) );
				}
			}
		}
		update_option( 'pbb_pricing_v2', $pricing, false );
	}

	// Own add-ons: keep every name we've seen, so past orders still read right.
	$labels = pbb_custom_accessory_labels();
	$active = array();
	foreach ( isset( $b['accessories'] ) && is_array( $b['accessories'] ) ? $b['accessories'] : array() as $id => $name ) {
		$id = sanitize_key( $id );
		if ( 0 === strpos( $id, 'c_' ) ) {
			$labels[ $id ] = sanitize_text_field( $name );
		}
		$active[] = $id;
	}
	update_option( 'pbb_custom_accessories', $labels, true );

	$offer = array( 'accessories' => $active );
	foreach ( array( 'styles', 'sizes', 'badges', 'borders' ) as $group ) {
		$ids = isset( $b['offer'][ $group ] ) && is_array( $b['offer'][ $group ] ) ? $b['offer'][ $group ] : array_keys( $c[ $group ] );
		$offer[ $group ] = array_values( array_intersect( array_map( 'sanitize_key', $ids ), array_keys( $c[ $group ] ) ) );
	}
	update_option( 'pbb_offer', $offer, true );
}

/** Supplier name and postcode for the plate footer, brand colours and the logo. Empty values leave the current ones. */
function pbb_apply_shop( array $s ) {
	$shop = get_option( 'pbb_shop', array() );
	$shop = is_array( $shop ) ? $shop : array();

	foreach ( array( 'supplier', 'postcode' ) as $key ) {
		if ( ! empty( $s[ $key ] ) ) {
			$shop[ $key ] = sanitize_text_field( $s[ $key ] );
		}
	}
	foreach ( array( 'accent', 'accent_ink', 'dark' ) as $key ) {
		$hex = isset( $s[ $key ] ) ? sanitize_hex_color( $s[ $key ] ) : null;
		if ( $hex ) {
			$shop[ $key ] = $hex;
		}
	}
	if ( ! empty( $s['logo']['url'] ) && ! empty( $s['logo']['key'] ) ) {
		$url = pbb_save_portal_logo( $s['logo'] );
		if ( $url ) {
			$shop['logo'] = $url;
		}
	}
	update_option( 'pbb_shop', $shop, true );
}

/**
 * The logo goes in uploads/3d-plate-builder/ rather than the media library, which doesn't take SVG.
 *
 * @return string|null URL of the saved file.
 */
function pbb_save_portal_logo( array $logo ) {
	$ext = strtolower( pathinfo( (string) $logo['key'], PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'png', 'svg' ), true ) ) {
		return null;
	}
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . '3d-plate-builder';
	$name    = 'logo-' . substr( md5( (string) $logo['key'] ), 0, 12 ) . '.' . $ext;
	$url     = trailingslashit( $uploads['baseurl'] ) . '3d-plate-builder/' . $name;
	if ( file_exists( $dir . '/' . $name ) ) {
		return $url;
	}

	$response = wp_remote_get( esc_url_raw( $logo['url'] ), array( 'timeout' => 20 ) );
	$body     = (string) wp_remote_retrieve_body( $response );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) || '' === $body || strlen( $body ) > 5 * MB_IN_BYTES ) {
		return null;
	}
	if ( 'svg' === $ext ) {
		// Strip anything that could run: scripts, event handlers, external links.
		$body = preg_replace( '#<script\b.*?</script>#is', '', $body );
		$body = preg_replace( '#\son\w+\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $body );
		$body = preg_replace( '#(href\s*=\s*["\'])\s*javascript:[^"\']*#i', '$1#', $body );
		if ( false === stripos( $body, '<svg' ) ) {
			return null;
		}
	} elseif ( 0 !== strpos( $body, "\x89PNG" ) ) {
		return null;
	}
	if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $dir . '/' . $name, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return null;
	}
	return $url;
}

/**
 * Create or update the shop's products. Ones no longer published go back to draft (never deleted:
 * orders point at them). Plate products are registered as builder presets (pbb_product_presets()).
 *
 * @return string[] Problems worth showing in the admin.
 */
function pbb_apply_products( array $products, array $style_labels ) {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return array( __( 'WooCommerce isn\'t active, so products weren\'t updated.', '3d-plate-builder' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$c       = pbb_catalog();
	$map     = (array) get_option( 'pbb_portal_products', array() ); // portal id => product id
	$shop    = get_option( 'pbb_shop', array() );
	$shop    = is_array( $shop ) ? $shop : array();
	$presets = pbb_int_keys( isset( $shop['products'] ) && is_array( $shop['products'] ) ? $shop['products'] : array() );
	$errors  = array();
	$seen    = array();

	foreach ( array_values( $products ) as $i => $p ) {
		if ( empty( $p['id'] ) || empty( $p['name'] ) ) {
			continue;
		}
		$portal_id = (int) $p['id'];
		$product   = isset( $map[ $portal_id ] ) ? wc_get_product( (int) $map[ $portal_id ] ) : null;
		if ( ! $product || 'trash' === $product->get_status() ) {
			$product = new WC_Product_Simple();
		}

		$product->set_name( sanitize_text_field( $p['name'] ) );
		$product->set_description( wp_kses_post( wpautop( esc_html( isset( $p['description'] ) ? (string) $p['description'] : '' ) ) ) );
		$product->set_regular_price( wc_format_decimal( isset( $p['price'] ) ? (float) $p['price'] : 0, 2 ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_menu_order( $i );
		$product->update_meta_data( '_pbb_portal_id', $portal_id );

		$images = pbb_portal_images( isset( $p['images'] ) && is_array( $p['images'] ) ? $p['images'] : array(), $errors );
		$product->set_image_id( $images ? $images[0] : 0 );
		$product->set_gallery_image_ids( array_slice( $images, 1 ) );
		$id = $product->save();

		$map[ $portal_id ] = $id;
		$seen[]            = $id;

		$preset = isset( $p['preset'] ) && is_array( $p['preset'] ) ? $p['preset'] : null;
		if ( 'plate' === ( $p['type'] ?? '' ) && $preset && isset( $c['styles'][ $preset['style'] ?? '' ] ) ) {
			$entry = array(
				'style' => $preset['style'],
				'name'  => isset( $style_labels[ $preset['style'] ] ) ? sanitize_text_field( $style_labels[ $preset['style'] ] ) : $c['styles'][ $preset['style'] ]['label'],
			);
			if ( ! empty( $preset['size'] ) && isset( $c['sizes'][ $preset['size'] ] ) ) {
				$entry['size'] = $preset['size'];
			}
			if ( ! empty( $preset['badge'] ) && isset( $c['badges'][ $preset['badge'] ] ) ) {
				$entry['badge'] = $preset['badge'];
			}
			$presets[ $id ] = $entry;
		} else {
			unset( $presets[ $id ] );
		}
	}

	// Taken off in the portal: hide (draft) rather than delete.
	foreach ( $map as $portal_id => $id ) {
		if ( in_array( (int) $id, $seen, true ) ) {
			continue;
		}
		$product = wc_get_product( (int) $id );
		if ( $product && 'publish' === $product->get_status() ) {
			$product->set_status( 'draft' );
			$product->save();
		}
		unset( $presets[ (int) $id ] );
	}

	$shop['products'] = $presets;
	update_option( 'pbb_shop', $shop, true );
	update_option( 'pbb_portal_products', $map, false );
	return $errors;
}

/**
 * Attachment ids for a product's images, downloading only ones we haven't got yet (matched by key).
 *
 * @param array    $images [{key, url}, …]
 * @param string[] $errors Collects problems.
 * @return int[]
 */
function pbb_portal_images( array $images, array &$errors ) {
	$ids = array();
	foreach ( $images as $img ) {
		$key = isset( $img['key'] ) ? sanitize_file_name( (string) $img['key'] ) : '';
		if ( '' === $key || empty( $img['url'] ) ) {
			continue;
		}
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_pbb_portal_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);
		if ( $found ) {
			$ids[] = (int) $found[0];
			continue;
		}

		$tmp = download_url( esc_url_raw( $img['url'] ), 30 );
		if ( is_wp_error( $tmp ) ) {
			$errors[] = sprintf( /* translators: %s: error */ __( 'An image didn\'t download (%s). It will be tried again next time.', '3d-plate-builder' ), $tmp->get_error_message() );
			continue;
		}
		$id = media_handle_sideload( array( 'name' => $key, 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			$errors[] = $id->get_error_message();
			continue;
		}
		update_post_meta( $id, '_pbb_portal_key', $key );
		$ids[] = (int) $id;
	}
	return $ids;
}

/* WooCommerce → Plate Builder: licence key and Sync now. */
add_action( 'admin_post_pbb_save_licence', 'pbb_save_licence' );

function pbb_save_licence() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', '3d-plate-builder' ) );
	}
	check_admin_referer( 'pbb_save_licence' );

	$key = isset( $_POST['licence_key'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['licence_key'] ) ) ) : '';
	if ( $key !== pbb_licence_key() ) {
		update_option( 'pbb_licence_key', $key, false );
		wp_clear_scheduled_hook( PBB_SYNC_HOOK );
	}
	$result = '' === $key ? true : pbb_portal_sync( true );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'pbb-settings',
				'updated' => is_wp_error( $result ) ? 'sync-failed' : 'synced',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/** The account box at the top of WooCommerce → Plate Builder. */
function pbb_render_portal_box() {
	$state = pbb_sync_state();
	$key   = pbb_licence_key();
	?>
	<h2 class="title"><?php esc_html_e( 'Your 3dPlateBuilder account', '3d-plate-builder' ); ?></h2>
	<p>
		<?php esc_html_e( 'Your prices, options, add-ons and products are managed in your account at app.3dplatebuilder.com. Publish there, and this shop updates within a minute (or within the hour if it misses the nudge).', '3d-plate-builder' ); ?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pbb_save_licence">
		<?php wp_nonce_field( 'pbb_save_licence' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="pbb-licence"><?php esc_html_e( 'Licence key', '3d-plate-builder' ); ?></label></th>
				<td>
					<input type="text" class="regular-text code" id="pbb-licence" name="licence_key" value="<?php echo esc_attr( $key ); ?>" placeholder="XXXXX-XXXXX-XXXXX-XXXXX" autocomplete="off">
					<p class="description"><?php esc_html_e( 'From your account, under Overview.', '3d-plate-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Last update', '3d-plate-builder' ); ?></th>
				<td>
					<?php
					if ( $state['synced_at'] ) {
						printf(
							/* translators: 1: version number, 2: how long ago */
							esc_html__( 'Version %1$d, %2$s ago.', '3d-plate-builder' ),
							(int) $state['version'],
							esc_html( human_time_diff( (int) $state['synced_at'] ) )
						);
					} else {
						esc_html_e( 'Nothing published yet.', '3d-plate-builder' );
					}
					if ( $state['error'] ) {
						echo '<br><strong style="color:#b32d2e">' . esc_html( $state['error'] ) . '</strong>';
					}
					?>
				</td>
			</tr>
		</table>
		<?php submit_button( $key ? __( 'Save and sync now', '3d-plate-builder' ) : __( 'Save', '3d-plate-builder' ), 'primary', 'submit', false ); ?>
	</form>
	<hr>
	<?php
}
