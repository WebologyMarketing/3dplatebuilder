<?php
/**
 * Staff side of the DVLA entitlement checks: the "Plate documents" panel on the order
 * screen (classic and HPOS), private downloads, verification, and contact settings.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------- */
/* Order screen panel                                                         */
/* -------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes',
	function () {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'pbb-documents', __( 'Plate documents (DVLA)', 'plateboss-builder' ), 'pbb_render_documents_box', $screen, 'side', 'high' );
	}
);

function pbb_render_documents_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	if ( 'yes' !== $order->get_meta( '_pbb_docs_required' ) ) {
		echo '<p>' . esc_html__( 'No road-legal builder plates in this order, so no documents are needed.', 'plateboss-builder' ) . '</p>';
		return;
	}

	$types    = pbb_document_types();
	$files    = (array) $order->get_meta( '_pbb_docs_files' );
	$method   = $order->get_meta( '_pbb_docs_method' );
	$verified = $order->get_meta( '_pbb_docs_verified' );
	$purged   = $order->get_meta( '_pbb_docs_purged' );

	echo '<p><strong>' . esc_html__( 'Customer chose:', 'plateboss-builder' ) . '</strong> '
		. ( 'upload' === $method ? esc_html__( 'uploaded at checkout', 'plateboss-builder' ) : esc_html__( 'send later (email, WhatsApp, post or in store)', 'plateboss-builder' ) )
		. '</p>';

	echo '<ul style="margin:0 0 1em">';
	foreach ( $types as $kind => $group ) {
		echo '<li style="margin-bottom:.6em"><strong>' . esc_html( $group['label'] ) . '</strong><br>';
		if ( ! empty( $files[ $kind ] ) ) {
			$f    = $files[ $kind ];
			$type = isset( $group['options'][ $f['type'] ] ) ? $group['options'][ $f['type'] ] : $f['type'];
			echo esc_html( $type ) . '<br>';
			if ( $purged ) {
				echo '<em>' . esc_html__( 'File deleted after the retention period.', 'plateboss-builder' ) . '</em>';
			} else {
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'pbb_document_download',
							'order'  => $order->get_id(),
							'kind'   => $kind,
						),
						admin_url( 'admin-post.php' )
					),
					'pbb_document_download_' . $order->get_id() . '_' . $kind
				);
				echo '<a href="' . esc_url( $url ) . '">' . esc_html( $f['name'] ) . '</a>';
			}
		} else {
			echo '<em>' . esc_html__( 'Not uploaded yet', 'plateboss-builder' ) . '</em>';
		}
		echo '</li>';
	}
	echo '</ul>';

	if ( $verified ) {
		$v    = (array) $verified;
		$user = ! empty( $v['user'] ) ? get_userdata( $v['user'] ) : null;
		echo '<p style="padding:8px;background:#e7f6ec;border-radius:4px">✓ '
			. esc_html(
				sprintf(
					/* translators: 1: staff name, 2: date */
					__( 'Verified by %1$s on %2$s', 'plateboss-builder' ),
					$user ? $user->display_name : __( 'staff', 'plateboss-builder' ),
					wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $order->get_meta( '_pbb_docs_verified_at' ) )
				)
			) . '</p>';
		foreach ( $types as $kind => $group ) {
			if ( ! empty( $v['seen'][ $kind ] ) && isset( $group['options'][ $v['seen'][ $kind ] ] ) ) {
				echo '<p style="margin:.2em 0">' . esc_html( $group['label'] . ': ' . $group['options'][ $v['seen'][ $kind ] ] ) . '</p>';
			}
		}
		return;
	}

	// Verification form. Saved with the order's own "Update" button (WooCommerce checks its nonce).
	echo '<hr><p><strong>' . esc_html__( 'Verify documents', 'plateboss-builder' ) . '</strong><br>'
		. esc_html__( 'Record which documents you have seen, then tick to release the order to Processing.', 'plateboss-builder' ) . '</p>';
	foreach ( $types as $kind => $group ) {
		$current = ! empty( $files[ $kind ]['type'] ) ? $files[ $kind ]['type'] : '';
		echo '<p><label for="pbb-seen-' . esc_attr( $kind ) . '">' . esc_html( $group['label'] ) . '</label><br>';
		echo '<select name="pbb_docs_seen[' . esc_attr( $kind ) . ']" id="pbb-seen-' . esc_attr( $kind ) . '" style="width:100%">';
		echo '<option value="">' . esc_html__( 'Choose…', 'plateboss-builder' ) . '</option>';
		foreach ( $group['options'] as $id => $label ) {
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $current, $id, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';
	}
	echo '<p><label><input type="checkbox" name="pbb_docs_verify" value="1"> '
		. esc_html__( 'I have seen valid ID and proof of entitlement for this registration', 'plateboss-builder' ) . '</label></p>';
	echo '<p class="description">' . esc_html__( 'Then click Update. The order moves from Awaiting documents to Processing.', 'plateboss-builder' ) . '</p>';
}

add_action(
	'woocommerce_process_shop_order_meta',
	function ( $order_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the order form nonce before this action.
		if ( empty( $_POST['pbb_docs_verify'] ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order || 'yes' !== $order->get_meta( '_pbb_docs_required' ) || $order->get_meta( '_pbb_docs_verified' ) ) {
			return;
		}

		$types = pbb_document_types();
		$seen  = array();
		$raw   = isset( $_POST['pbb_docs_seen'] ) && is_array( $_POST['pbb_docs_seen'] ) ? wp_unslash( $_POST['pbb_docs_seen'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- whitelisted below.
		// phpcs:enable
		foreach ( $types as $kind => $group ) {
			$value = isset( $raw[ $kind ] ) ? sanitize_key( $raw[ $kind ] ) : '';
			if ( ! isset( $group['options'][ $value ] ) ) {
				WC_Admin_Meta_Boxes::add_error( __( 'Plate documents: choose which ID and entitlement documents you saw before verifying.', 'plateboss-builder' ) );
				return;
			}
			$seen[ $kind ] = $value;
		}

		$order->update_meta_data(
			'_pbb_docs_verified',
			array(
				'user' => get_current_user_id(),
				'seen' => $seen,
			)
		);
		$order->update_meta_data( '_pbb_docs_verified_at', time() );
		$note = sprintf(
			/* translators: 1: ID document, 2: entitlement document */
			__( 'DVLA documents verified. ID: %1$s. Entitlement: %2$s.', 'plateboss-builder' ),
			$types['id']['options'][ $seen['id'] ],
			$types['entitlement']['options'][ $seen['entitlement'] ]
		);
		if ( $order->has_status( PBB_DOC_STATUS ) ) {
			$order->update_status( 'processing', $note, true );
		} else {
			$order->add_order_note( $note, false, true );
			$order->save();
		}
	},
	50 // after WooCommerce saves the order (40), so our status wins only when it's still held
);

/* -------------------------------------------------------------------------- */
/* Private downloads                                                          */
/* -------------------------------------------------------------------------- */

add_action(
	'admin_post_pbb_document_download',
	function () {
		$order_id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		$kind     = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : '';
		check_admin_referer( 'pbb_document_download_' . $order_id . '_' . $kind );
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this document.', 'plateboss-builder' ), 403 );
		}

		$order = wc_get_order( $order_id );
		$files = $order ? (array) $order->get_meta( '_pbb_docs_files' ) : array();
		$f     = isset( $files[ $kind ] ) ? $files[ $kind ] : null;
		$path  = $f ? pbb_document_path( $f['file'] ) : '';
		if ( ! $path || ! is_file( $path ) ) {
			wp_die( esc_html__( 'Document not found. It may have been deleted after the retention period.', 'plateboss-builder' ), 404 );
		}

		nocache_headers();
		header( 'Content-Type: ' . $f['mime'] );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		// Inline so staff can view photos and PDFs in the browser; attachment for anything else.
		$inline = in_array( $f['mime'], array( 'image/jpeg', 'image/png', 'image/webp', 'application/pdf' ), true );
		header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . sanitize_file_name( $order->get_order_number() . '-' . $kind . '-' . $f['name'] ) . '"' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
);

/* -------------------------------------------------------------------------- */
/* Settings: where customers send documents                                   */
/* -------------------------------------------------------------------------- */

add_action(
	'pbb_settings_after',
	function () {
		$c = pbb_document_contact();
		?>
		<hr>
		<h2><?php esc_html_e( 'Documents (DVLA checks)', 'plateboss-builder' ); ?></h2>
		<p><?php esc_html_e( 'Shown to customers who choose to send their documents after ordering, on the order confirmation page and in their order email.', 'plateboss-builder' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pbb_save_documents_contact">
			<?php wp_nonce_field( 'pbb_save_documents_contact' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pbb-doc-email"><?php esc_html_e( 'Email for documents', 'plateboss-builder' ); ?></label></th>
					<td><input type="email" id="pbb-doc-email" name="pbb_docs[email]" value="<?php echo esc_attr( $c['email'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="pbb-doc-wa"><?php esc_html_e( 'WhatsApp number (optional)', 'plateboss-builder' ); ?></label></th>
					<td><input type="text" id="pbb-doc-wa" name="pbb_docs[whatsapp]" value="<?php echo esc_attr( $c['whatsapp'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="pbb-doc-address"><?php esc_html_e( 'Postal address (optional)', 'plateboss-builder' ); ?></label></th>
					<td><textarea id="pbb-doc-address" name="pbb_docs[address]" rows="3" class="large-text"><?php echo esc_textarea( $c['address'] ); ?></textarea></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save document settings', 'plateboss-builder' ) ); ?>
		</form>
		<?php
	}
);

add_action(
	'admin_post_pbb_save_documents_contact',
	function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'plateboss-builder' ) );
		}
		check_admin_referer( 'pbb_save_documents_contact' );
		$in = isset( $_POST['pbb_docs'] ) && is_array( $_POST['pbb_docs'] ) ? wp_unslash( $_POST['pbb_docs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below.
		update_option(
			'pbb_documents_contact',
			array(
				'email'    => isset( $in['email'] ) ? sanitize_email( $in['email'] ) : '',
				'whatsapp' => isset( $in['whatsapp'] ) ? sanitize_text_field( $in['whatsapp'] ) : '',
				'address'  => isset( $in['address'] ) ? sanitize_textarea_field( $in['address'] ) : '',
			),
			false
		);
		wp_safe_redirect( add_query_arg( array( 'page' => 'pbb-settings', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
);
