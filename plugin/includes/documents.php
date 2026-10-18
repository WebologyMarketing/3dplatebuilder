<?php
/**
 * DVLA entitlement checks.
 *
 * As a Registered Number Plate Supplier, the shop must see proof of ID and proof of
 * entitlement to the registration before road-legal plates are dispatched or handed over.
 * Show plates don't need them.
 *
 * Flow:
 *  1. At checkout (classic or block), if the basket holds a road-legal builder plate, the
 *     customer either uploads both documents or chooses to send them later (email,
 *     WhatsApp, post, or bring them when collecting).
 *  2. Uploads go to a private folder (never the media library) and are tracked in the
 *     WooCommerce session until the order is placed, then linked to the order.
 *  3. Paid orders that need documents go to "Awaiting documents" instead of Processing,
 *     and stay there until staff mark the documents verified (see documents-admin.php).
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

const PBB_DOC_MAX_BYTES = 10485760; // 10 MB
const PBB_DOC_STATUS    = 'awaiting-docs';

/* -------------------------------------------------------------------------- */
/* Document types (the shop lists them on its /valid-documents/ page)        */
/* -------------------------------------------------------------------------- */

function pbb_document_types() {
	return apply_filters(
		'pbb_document_types',
		array(
			'id'          => array(
				'label'   => __( 'Proof of ID', '3d-plate-builder' ),
				'options' => array(
					'driving_licence' => __( 'Driving licence (photocard or paper counterpart)', '3d-plate-builder' ),
					'utility_bill'    => __( 'Utility, Council Tax or rates bill (last 6 months)', '3d-plate-builder' ),
					'bank_statement'  => __( 'Bank or building society statement (last 6 months)', '3d-plate-builder' ),
					'national_id'     => __( 'National Identity Card', '3d-plate-builder' ),
					'passport'        => __( 'Passport (name only)', '3d-plate-builder' ),
					'bank_card'       => __( 'Bank or building society debit/credit card (name only)', '3d-plate-builder' ),
					'police_warrant'  => __( 'Police warrant card (name only)', '3d-plate-builder' ),
					'armed_forces'    => __( 'Armed forces identity card (name only)', '3d-plate-builder' ),
				),
			),
			'entitlement' => array(
				'label'   => __( 'Proof of entitlement to the registration', '3d-plate-builder' ),
				'options' => array(
					'v5c'          => __( 'Vehicle registration certificate (V5C or V5CNI)', '3d-plate-builder' ),
					'v5c2'         => __( 'New keeper supplement (V5C/2 or V5C/2NI)', '3d-plate-builder' ),
					'v750'         => __( 'Certificate of entitlement (V750 or V750NI)', '3d-plate-builder' ),
					'v778'         => __( 'Retention document (V778)', '3d-plate-builder' ),
					'v11'          => __( 'Vehicle tax or SORN renewal reminder (V11 or V11NI)', '3d-plate-builder' ),
					'v379'         => __( 'Temporary registration certificate (V379 or V379NI)', '3d-plate-builder' ),
					'v948'         => __( 'Number plate authorisation certificate (V948), DVLA/DVSA stamped', '3d-plate-builder' ),
					'ev948'        => __( 'Electronic number plate authorisation certificate (eV948)', '3d-plate-builder' ),
					'fleet_letter' => __( 'Fleet, lease or hire company authorisation letter', '3d-plate-builder' ),
					'v5c_demand'   => __( 'V5C on demand: PDF from the view vehicle record service', '3d-plate-builder' ),
				),
			),
		)
	);
}

/** File types customers can upload: photos (incl. iPhone HEIC) and PDFs. */
function pbb_document_mimes() {
	return array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
		'heic'     => 'image/heic',
		'pdf'      => 'application/pdf',
	);
}

/** Where customers send documents if they don't upload. Set on WooCommerce → Plate Builder. */
function pbb_document_contact() {
	$saved = get_option( 'pbb_documents_contact', array() );
	return array(
		'email'    => ! empty( $saved['email'] ) ? $saved['email'] : get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) ),
		'whatsapp' => ! empty( $saved['whatsapp'] ) ? $saved['whatsapp'] : '',
		'address'  => isset( $saved['address'] ) ? $saved['address'] : '',
	);
}

/* -------------------------------------------------------------------------- */
/* Private storage                                                            */
/* -------------------------------------------------------------------------- */

/**
 * Folder for uploaded documents. Prefers a folder outside the web root:
 * on Plesk every site has a `private` folder next to `httpdocs`, which the web server
 * never serves. Falls back to wp-content/uploads/pbb-documents, locked down below.
 * Override with the `pbb_documents_dir` filter.
 */
function pbb_documents_dir() {
	$plesk_private = dirname( untrailingslashit( ABSPATH ) ) . '/private';
	$default       = ( is_dir( $plesk_private ) && wp_is_writable( $plesk_private ) )
		? $plesk_private . '/pbb-documents'
		: wp_upload_dir( null, false )['basedir'] . '/pbb-documents';

	$dir = untrailingslashit( apply_filters( 'pbb_documents_dir', $default ) );

	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	// Belt and braces for the fallback inside the web root (Apache honours .htaccess;
	// on nginx the random 40-character file names keep files unguessable).
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	return $dir;
}

/** Absolute path of a stored document, refusing anything that escapes the folder. */
function pbb_document_path( $relative ) {
	$relative = ltrim( str_replace( array( '..', '\\' ), '', (string) $relative ), '/' );
	return $relative ? pbb_documents_dir() . '/' . $relative : '';
}

/* -------------------------------------------------------------------------- */
/* Session state (shared by classic and block checkout)                        */
/* -------------------------------------------------------------------------- */

/** True if the basket holds at least one road-legal builder plate. */
function pbb_cart_needs_documents() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( ! empty( $item['pbb'] ) && ! empty( $item['pbb']['road_legal'] ) ) {
			return true;
		}
	}
	return false;
}

function pbb_documents_session() {
	$state = WC()->session ? WC()->session->get( 'pbb_documents' ) : null;

	// Once the order these documents went with has gone through, start fresh: an
	// entitlement document belongs to one registration and must never carry over.
	// (Checking here rather than only on the thank-you page, which page-builder
	// checkouts such as ShopBuilder may never render.) Pending/failed orders keep
	// the uploads so a retried card payment doesn't need them again.
	if ( is_array( $state ) && ! empty( $state['order_id'] ) ) {
		$order = wc_get_order( $state['order_id'] );
		if ( ! $order || ! $order->has_status( array( 'pending', 'failed', 'checkout-draft' ) ) ) {
			WC()->session->set( 'pbb_documents', null );
			$state = null;
		}
	}

	return wp_parse_args(
		is_array( $state ) ? $state : array(),
		array(
			'method' => '', // 'upload' | 'later'
			'files'  => array(), // kind => { file, name, mime, type, uploaded }
		)
	);
}

function pbb_documents_save_session( array $state ) {
	if ( ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true ); // guests need a session to keep uploads
	}
	WC()->session->set( 'pbb_documents', $state );
}

/** Public state for the checkout UI (no file paths). */
function pbb_documents_public_state() {
	$state = pbb_documents_session();
	$files = array();
	foreach ( $state['files'] as $kind => $f ) {
		$files[ $kind ] = array(
			'name' => $f['name'],
			'type' => $f['type'],
		);
	}
	return array(
		'needed'   => pbb_cart_needs_documents(),
		'method'   => $state['method'],
		'files'    => (object) $files,
		'types'    => pbb_document_types(),
		'contact'  => pbb_document_contact(),
		'maxBytes' => PBB_DOC_MAX_BYTES,
		'accept'   => '.jpg,.jpeg,.png,.webp,.heic,.pdf,image/*,application/pdf',
	);
}

/* -------------------------------------------------------------------------- */
/* Checkout endpoints: ?wc-ajax=pbb_documents_{state,method,upload,remove}     */
/* -------------------------------------------------------------------------- */

function pbb_documents_verify_request() {
	if ( ! check_ajax_referer( 'pbb_documents', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session has expired. Please refresh the page.', '3d-plate-builder' ) ), 403 );
	}
}

add_action(
	'wc_ajax_pbb_documents_state',
	function () {
		pbb_documents_verify_request();
		wp_send_json_success( pbb_documents_public_state() );
	}
);

add_action(
	'wc_ajax_pbb_documents_method',
	function () {
		pbb_documents_verify_request();
		$method = isset( $_POST['method'] ) ? sanitize_key( wp_unslash( $_POST['method'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$state  = pbb_documents_session();
		$state['method'] = in_array( $method, array( 'upload', 'later' ), true ) ? $method : '';
		pbb_documents_save_session( $state );
		wp_send_json_success( pbb_documents_public_state() );
	}
);

add_action( 'wc_ajax_pbb_documents_upload', 'pbb_documents_handle_upload' );

function pbb_documents_handle_upload() {
	pbb_documents_verify_request();
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
	$types = pbb_document_types();
	$kind  = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : '';
	$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$file  = isset( $_FILES['file'] ) ? $_FILES['file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked below.
	// phpcs:enable

	if ( ! pbb_cart_needs_documents() ) {
		wp_send_json_error( array( 'message' => __( 'Your basket doesn\'t need documents.', '3d-plate-builder' ) ), 400 );
	}
	if ( ! isset( $types[ $kind ] ) || ! isset( $types[ $kind ]['options'][ $type ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please choose which document this is.', '3d-plate-builder' ) ), 400 );
	}
	if ( ! $file || UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		wp_send_json_error( array( 'message' => __( 'The upload failed. Please try again.', '3d-plate-builder' ) ), 400 );
	}
	if ( $file['size'] > PBB_DOC_MAX_BYTES ) {
		wp_send_json_error( array( 'message' => __( 'That file is over 10 MB. Please upload a smaller photo or PDF.', '3d-plate-builder' ) ), 400 );
	}

	// Check the real file contents, not just the extension.
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], pbb_document_mimes() );
	if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please upload a photo (JPG, PNG, HEIC) or a PDF.', '3d-plate-builder' ) ), 400 );
	}

	$dir = pbb_documents_dir() . '/pending';
	wp_mkdir_p( $dir );
	$stored = 'pending/' . wp_generate_password( 40, false ) . '.' . $check['ext'];
	if ( ! move_uploaded_file( $file['tmp_name'], pbb_documents_dir() . '/' . $stored ) ) {
		wp_send_json_error( array( 'message' => __( 'We couldn\'t save that file. Please try again or send it later.', '3d-plate-builder' ) ), 500 );
	}

	$state = pbb_documents_session();
	if ( ! empty( $state['files'][ $kind ]['file'] ) ) {
		wp_delete_file( pbb_document_path( $state['files'][ $kind ]['file'] ) ); // replace the previous upload
	}
	$state['method']         = 'upload';
	$state['files'][ $kind ] = array(
		'file'     => $stored,
		'name'     => sanitize_file_name( $file['name'] ),
		'mime'     => $check['type'],
		'type'     => $type,
		'uploaded' => time(),
	);
	pbb_documents_save_session( $state );
	wp_send_json_success( pbb_documents_public_state() );
}

add_action(
	'wc_ajax_pbb_documents_remove',
	function () {
		pbb_documents_verify_request();
		$kind  = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$state = pbb_documents_session();
		if ( ! empty( $state['files'][ $kind ]['file'] ) ) {
			wp_delete_file( pbb_document_path( $state['files'][ $kind ]['file'] ) );
		}
		unset( $state['files'][ $kind ] );
		pbb_documents_save_session( $state );
		wp_send_json_success( pbb_documents_public_state() );
	}
);

/* -------------------------------------------------------------------------- */
/* Validation and saving to the order                                         */
/* -------------------------------------------------------------------------- */

/** @return true|WP_Error */
function pbb_documents_validate() {
	if ( ! pbb_cart_needs_documents() ) {
		return true;
	}
	$state = pbb_documents_session();
	if ( 'later' === $state['method'] ) {
		return true;
	}
	if ( 'upload' !== $state['method'] ) {
		return new WP_Error( 'pbb_documents', __( 'Road-legal plates need proof of ID and entitlement. Please upload them or choose to send them later.', '3d-plate-builder' ) );
	}
	foreach ( pbb_document_types() as $kind => $group ) {
		$f = isset( $state['files'][ $kind ] ) ? $state['files'][ $kind ] : null;
		if ( ! $f || ! file_exists( pbb_document_path( $f['file'] ) ) ) {
			/* translators: %s: document group, e.g. "Proof of ID" */
			return new WP_Error( 'pbb_documents', sprintf( __( 'Please upload your %s, or choose to send your documents later.', '3d-plate-builder' ), strtolower( $group['label'] ) ) );
		}
	}
	return true;
}

/**
 * Link the session's documents to the order.
 *
 * The session keeps them (with their new paths) until the order-received page, so a
 * failed card payment can be retried without uploading again.
 */
function pbb_documents_attach_to_order( WC_Order $order ) {
	if ( ! pbb_cart_needs_documents() ) {
		return;
	}
	$state = pbb_documents_session();
	$files = array();

	foreach ( $state['files'] as $kind => $f ) {
		$from = pbb_document_path( $f['file'] );
		if ( ! file_exists( $from ) ) {
			continue;
		}
		// Move out of "pending" so the cleanup job never deletes files that belong to an order.
		if ( 0 === strpos( $f['file'], 'pending/' ) ) {
			$to_rel = 'orders/' . wp_generate_password( 40, false ) . '.' . pathinfo( $from, PATHINFO_EXTENSION );
			wp_mkdir_p( pbb_documents_dir() . '/orders' );
			if ( @rename( $from, pbb_document_path( $to_rel ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
				$f['file']                     = $to_rel;
				$state['files'][ $kind ]['file'] = $to_rel;
			}
		}
		$files[ $kind ] = $f;
	}
	pbb_documents_save_session( $state );

	$method = 'upload' === $state['method'] && count( $files ) === count( pbb_document_types() ) ? 'upload' : 'later';
	$order->update_meta_data( '_pbb_docs_required', 'yes' );
	$order->update_meta_data( '_pbb_docs_method', $method );
	$order->update_meta_data( '_pbb_docs_files', $files );
	$order->update_meta_data( '_pbb_docs_verified', '' );
}

// Remember which order the documents went with (the order has an id by now), so
// pbb_documents_session() can clear them once that order is through.
function pbb_documents_remember_order( $order ) {
	$order = wc_get_order( $order );
	if ( ! $order || ! WC()->session || 'yes' !== $order->get_meta( '_pbb_docs_required' ) ) {
		return;
	}
	$state = WC()->session->get( 'pbb_documents' );
	if ( is_array( $state ) ) {
		$state['order_id'] = $order->get_id();
		WC()->session->set( 'pbb_documents', $state );
	}
}
add_action( 'woocommerce_checkout_order_processed', 'pbb_documents_remember_order' );
add_action( 'woocommerce_store_api_checkout_order_processed', 'pbb_documents_remember_order' );

// Order placed: forget the documents so the next order starts fresh.
add_action(
	'woocommerce_thankyou',
	function () {
		if ( WC()->session ) {
			WC()->session->set( 'pbb_documents', null );
		}
	},
	99
);

// Classic checkout.
add_action(
	'woocommerce_after_checkout_validation',
	function ( $data, $errors ) {
		$valid = pbb_documents_validate();
		if ( is_wp_error( $valid ) ) {
			$errors->add( 'pbb_documents', $valid->get_error_message() );
		}
	},
	10,
	2
);
add_action(
	'woocommerce_checkout_create_order',
	function ( $order ) {
		pbb_documents_attach_to_order( $order );
	}
);

// Block checkout (Store API): throwing stops the order with a visible error.
add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ( $order ) {
		$valid = pbb_documents_validate();
		if ( is_wp_error( $valid ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pbb_documents', esc_html( $valid->get_error_message() ), 400 );
		}
		pbb_documents_attach_to_order( $order );
	}
);

/* -------------------------------------------------------------------------- */
/* "Awaiting documents" order status                                          */
/* -------------------------------------------------------------------------- */

function pbb_order_needs_documents( $order ) {
	return $order instanceof WC_Order
		&& 'yes' === $order->get_meta( '_pbb_docs_required' )
		&& ! $order->get_meta( '_pbb_docs_verified' );
}

add_action(
	'init',
	function () {
		register_post_status(
			'wc-' . PBB_DOC_STATUS,
			array(
				'label'                     => _x( 'Awaiting documents', 'Order status', '3d-plate-builder' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Awaiting documents <span class="count">(%s)</span>', 'Awaiting documents <span class="count">(%s)</span>', '3d-plate-builder' ),
			)
		);
	}
);

add_filter(
	'wc_order_statuses',
	function ( $statuses ) {
		$out = array();
		foreach ( $statuses as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$out[ 'wc-' . PBB_DOC_STATUS ] = _x( 'Awaiting documents', 'Order status', '3d-plate-builder' );
			}
		}
		return $out;
	}
);

// Paid but held: counts as paid in reports and for the customer, and can still be edited.
add_filter(
	'woocommerce_order_is_paid_statuses',
	function ( $statuses ) {
		$statuses[] = PBB_DOC_STATUS;
		return $statuses;
	}
);
add_filter(
	'woocommerce_reports_order_statuses',
	function ( $statuses ) {
		if ( is_array( $statuses ) ) {
			$statuses[] = PBB_DOC_STATUS;
		}
		return $statuses;
	}
);

/**
 * Any order that reaches Processing (card payment, PayPal, Klarna, or staff marking a
 * bank transfer as paid) is held instead if its documents haven't been verified.
 */
add_action(
	'woocommerce_order_status_processing',
	function ( $order_id, $order ) {
		if ( pbb_order_needs_documents( $order ) ) {
			$order->update_status( PBB_DOC_STATUS, __( 'Held until proof of ID and entitlement are verified.', '3d-plate-builder' ) );
		}
	},
	20,
	2
);

/* -------------------------------------------------------------------------- */
/* Customer instructions (thank-you page and emails)                          */
/* -------------------------------------------------------------------------- */

/** The contact page, for the postal address. */
function pbb_contact_page_url() {
	$page = get_page_by_path( 'contact-us' );
	return $page ? get_permalink( $page ) : home_url( '/contact-us/' );
}

/** The shop's postal address from WooCommerce → Settings → General, on one line ('' if not set). */
function pbb_store_address() {
	$parts = array(
		get_bloginfo( 'name' ),
		get_option( 'woocommerce_store_address', '' ),
		get_option( 'woocommerce_store_address_2', '' ),
		get_option( 'woocommerce_store_city', '' ),
		get_option( 'woocommerce_store_postcode', '' ),
	);
	$parts = array_filter( array_map( 'trim', array_map( 'strval', $parts ) ) );
	return count( $parts ) > 1 ? wp_specialchars_decode( implode( ', ', $parts ), ENT_QUOTES ) : '';
}

/** The list of documents we accept. */
function pbb_valid_documents_url() {
	$page = get_page_by_path( 'valid-documents' );
	return $page ? get_permalink( $page ) : home_url( '/valid-documents/' );
}

/**
 * "Documents required before dispatch": a box in the shop's brand colours, used on the
 * thank-you page and in the order emails so they match.
 */
function pbb_documents_instructions_html( WC_Order $order, $plain = false ) {
	$contact = pbb_document_contact();
	$ref     = $order->get_order_number();
	$address = $contact['address'] ? $contact['address'] : pbb_store_address();
	$yellow  = pbb_shop_colour( 'accent' );
	$ink     = pbb_shop_colour( 'accent_ink' );
	$dark    = pbb_shop_colour( 'dark' );
	$link    = 'color:' . $yellow . ';text-decoration:underline';

	// Each way: label, plain-text value, HTML value.
	$ways   = array();
	$ways[] = array( __( 'Email', '3d-plate-builder' ), $contact['email'], '<a style="' . $link . '" href="mailto:' . esc_attr( $contact['email'] ) . '?subject=' . rawurlencode( 'Documents for order #' . $ref ) . '">' . esc_html( $contact['email'] ) . '</a>' );
	if ( $contact['whatsapp'] ) {
		$wa     = preg_replace( '/^0/', '44', preg_replace( '/\D/', '', $contact['whatsapp'] ) );
		$ways[] = array( __( 'WhatsApp', '3d-plate-builder' ), $contact['whatsapp'], '<a style="' . $link . '" href="https://wa.me/' . esc_attr( $wa ) . '">' . esc_html( $contact['whatsapp'] ) . '</a>' );
	}
	if ( '' !== $address ) {
		$ways[] = array(
			__( 'Post (Royal Mail Tracked or Signed For)', '3d-plate-builder' ),
			$address,
			esc_html( $address ) . ' (<a style="' . $link . '" href="' . esc_url( pbb_contact_page_url() ) . '">' . esc_html__( 'contact page', '3d-plate-builder' ) . '</a>)',
		);
	}
	if ( $order->has_shipping_method( 'local_pickup' ) || $order->has_shipping_method( 'pickup_location' ) ) {
		$ways[] = array( __( 'In person', '3d-plate-builder' ), __( 'bring them with you when you collect', '3d-plate-builder' ), esc_html__( 'bring them with you when you collect', '3d-plate-builder' ) );
	}

	$heading = __( 'Documents required before dispatch', '3d-plate-builder' );
	$intro   = __( 'By law, we need to see two documents before we can produce your plates: proof of ID and proof of entitlement to this registration.', '3d-plate-builder' );
	/* translators: %s: order number */
	$ask     = sprintf( __( 'Please send these to us as soon as possible, quoting order #%s, via:', '3d-plate-builder' ), $ref );
	$button  = __( 'View full list of accepted documents', '3d-plate-builder' );
	$foot    = __( 'We\'re unable to dispatch your plates until these documents have been received and verified. Any documents you post to us are sent back with your order.', '3d-plate-builder' );

	if ( $plain ) {
		$lines = array_map( fn ( $w ) => '- ' . $w[0] . ': ' . $w[1], $ways );
		return "\n" . strtoupper( $heading ) . "\n" . $intro . "\n" . $ask . "\n" . implode( "\n", $lines ) . "\n" . $button . ': ' . pbb_valid_documents_url() . "\n" . $foot . "\n";
	}
	$items = '';
	foreach ( $ways as $w ) {
		$items .= '<p style="margin:0 0 10px;color:#fff">&bull; ' . esc_html( $w[0] ) . ': ' . $w[2] . '</p>';
	}
	return '<section class="pbb-docs-notice" style="margin:1.5em 0;padding:28px 32px;background:' . $dark . ';border:3px solid ' . $yellow . ';color:#fff;line-height:1.6">'
		. '<h2 style="margin:0 0 14px;color:' . $yellow . ';font-size:22px;font-weight:800;letter-spacing:.04em;text-transform:uppercase">' . esc_html( $heading ) . '</h2>'
		. '<p style="margin:0 0 14px;color:#fff">' . esc_html( $intro ) . '</p>'
		. '<p style="margin:0 0 12px;color:#fff">' . esc_html( $ask ) . '</p>'
		. $items
		. '<p style="margin:20px 0"><a href="' . esc_url( pbb_valid_documents_url() ) . '" style="display:inline-block;padding:14px 26px;background:' . $yellow . ';color:' . $ink . ';font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:.02em">' . esc_html( $button ) . '</a></p>'
		. '<p style="margin:0;color:#cfcfcf;font-size:14px">' . esc_html( $foot ) . '</p>'
		. '</section>';
}

add_action(
	'woocommerce_thankyou',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && pbb_order_needs_documents( $order ) && 'later' === $order->get_meta( '_pbb_docs_method' ) ) {
			echo pbb_documents_instructions_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		} elseif ( $order && pbb_order_needs_documents( $order ) ) {
			echo '<p class="pbb-docs-notice">' . esc_html__( 'Thanks, we\'ve received your documents. We\'ll check them and dispatch your plates once verified.', '3d-plate-builder' ) . '</p>';
		}
	},
	5
);

add_action(
	'woocommerce_email_after_order_table',
	function ( $order, $sent_to_admin, $plain_text ) {
		if ( $sent_to_admin || ! pbb_order_needs_documents( $order ) || 'later' !== $order->get_meta( '_pbb_docs_method' ) ) {
			return;
		}
		// A shop that already adds its own documents box to order emails can turn this one off.
		if ( ! apply_filters( 'pbb_documents_email_notice', true, $order ) ) {
			return;
		}
		echo pbb_documents_instructions_html( $order, $plain_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	},
	10,
	3
);

/* -------------------------------------------------------------------------- */
/* Checkout assets and the classic checkout container                         */
/* -------------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_checkout() || is_order_received_page() || ! pbb_cart_needs_documents() ) {
			return;
		}
		wp_enqueue_style( 'pbb-documents', PBB_URL . 'assets/css/documents.css', array(), PBB_VERSION );
		wp_enqueue_script( 'pbb-documents', PBB_URL . 'assets/js/documents.js', array(), PBB_VERSION, true );
		wp_add_inline_script(
			'pbb-documents',
			'window.pbbDocuments = ' . wp_json_encode(
				array(
					'endpoint' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
					'nonce'    => wp_create_nonce( 'pbb_documents' ),
				)
			) . ';',
			'before'
		);
		if ( has_block( 'woocommerce/checkout', wc_get_page_id( 'checkout' ) ) ) {
			wp_enqueue_script( 'pbb-documents-blocks', PBB_URL . 'assets/js/documents-blocks.js', array( 'pbb-documents', 'wp-plugins', 'wp-element', 'wc-blocks-checkout' ), PBB_VERSION, true );
		}
	}
);

// Classic checkout: the panel sits under the billing/shipping details, outside the
// order review table that WooCommerce re-renders on every update.
add_action(
	'woocommerce_checkout_after_customer_details',
	function () {
		if ( pbb_cart_needs_documents() ) {
			echo '<div id="pbb-documents" data-pbb-documents hidden></div>';
		}
	}
);

/* -------------------------------------------------------------------------- */
/* Cleanup                                                                    */
/* -------------------------------------------------------------------------- */

/**
 * Daily: delete abandoned checkout uploads (pending > 2 days) and, for privacy, the
 * files of orders verified more than 30 days ago (`pbb_documents_retention_days`).
 * The record of which documents were seen, by whom and when stays on the order.
 */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'pbb_documents_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'pbb_documents_cleanup' );
		}
	}
);

add_action(
	'pbb_documents_cleanup',
	function () {
		foreach ( (array) glob( pbb_documents_dir() . '/pending/*' ) as $path ) {
			if ( is_file( $path ) && filemtime( $path ) < time() - 2 * DAY_IN_SECONDS ) {
				wp_delete_file( $path );
			}
		}

		$days = (int) apply_filters( 'pbb_documents_retention_days', 30 );
		if ( $days <= 0 ) {
			return;
		}
		$cutoff = time() - $days * DAY_IN_SECONDS;
		$orders = wc_get_orders(
			array(
				'type'       => 'shop_order', // never refunds
				'limit'      => 200,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_pbb_docs_verified_at',
						'value'   => $cutoff,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_pbb_docs_purged',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		foreach ( $orders as $order ) {
			// Some order stores ignore meta_query, so check each order here too: only orders whose
			// documents were verified before the cutoff, that still have files, and aren't done yet.
			$verified_at = (int) $order->get_meta( '_pbb_docs_verified_at' );
			$files       = array_filter( (array) $order->get_meta( '_pbb_docs_files' ) );
			if ( ! $order instanceof WC_Order || ! $verified_at || $verified_at >= $cutoff || ! $files || $order->get_meta( '_pbb_docs_purged' ) ) {
				continue;
			}
			foreach ( $files as $f ) {
				if ( ! empty( $f['file'] ) ) {
					wp_delete_file( pbb_document_path( $f['file'] ) );
				}
			}
			$order->update_meta_data( '_pbb_docs_purged', time() );
			$order->add_order_note( __( 'Document files deleted after the retention period. The verification record is kept.', '3d-plate-builder' ) );
			$order->save();
		}
	}
);
