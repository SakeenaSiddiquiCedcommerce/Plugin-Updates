<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


function ced_etsy_tool_tip( $tip = '' ) {
	print_r( "</br><span class='cedcommerce-tip'>$tip</span>" );
}

/**
 * Callback function for display html.
 *
 * @since 1.0.0
 */
function ced_etsy_render_html( $meta_keys_to_be_displayed = array(), $added_meta_keys = array() ) {
	$html  = '';
	$html .= '<table class="wp-list-table widefat fixed striped ced_etsy_config_table">';

	if ( isset( $meta_keys_to_be_displayed ) && is_array( $meta_keys_to_be_displayed ) && ! empty( $meta_keys_to_be_displayed ) ) {
		$total_items  = count( $meta_keys_to_be_displayed );
		$pages        = ceil( $total_items / 10 );
		$current_page = 1;
		$counter      = 0;
		$break_point  = 1;

		foreach ( $meta_keys_to_be_displayed as $meta_key => $meta_data ) {
			$display = 'display : none';
			if ( 0 == $counter ) {
				if ( 1 == $break_point ) {
					$display = 'display : contents';
				}
				$html .= '<tbody style="' . esc_attr( $display ) . '" class="ced_etsy_metakey_list_' . $break_point . '  			ced_etsy_metakey_body">';
				$html .= '<tr><td colspan="3"><label>CHECK THE METAKEYS OR ATTRIBUTES</label></td>';
				$html .= '<td class="ced_etsy_pagination"><span>' . $total_items . ' items</span>';
				$html .= '<button class="button ced_etsy_navigation" data-page="1" ' . ( ( 1 == $break_point ) ? 'disabled' : '' ) . ' ><b><<</b></button>';
				$html .= '<button class="button ced_etsy_navigation" data-page="' . esc_attr( $break_point - 1 ) . '" ' . ( ( 1 == $break_point ) ? 'disabled' : '' ) . ' ><b><</b></button><span>' . $break_point . ' of ' . $pages;
				$html .= '</span><button class="button ced_etsy_navigation" data-page="' . esc_attr( $break_point + 1 ) . '" ' . ( ( $pages == $break_point ) ? 'disabled' : '' ) . ' ><b>></b></button>';
				$html .= '<button class="button ced_etsy_navigation" data-page="' . esc_attr( $pages ) . '" ' . ( ( $pages == $break_point ) ? 'disabled' : '' ) . ' ><b>>></b></button>';
				$html .= '</td>';
				$html .= '</tr>';
				$html .= '<tr><td><label>Select</label></td><td><label>Metakey / Attributes</label></td><td colspan="2"><label>Value</label></td>';

			}
			$checked    = ( in_array( $meta_key, $added_meta_keys ) ) ? 'checked=checked' : '';
			$html      .= '<tr>';
			$html      .= "<td><input type='checkbox' class='ced_etsy_meta_key' value='" . esc_attr( $meta_key ) . "' " . $checked . '></input></td>';
			$html      .= '<td>' . esc_attr( $meta_key ) . '</td>';
			$meta_value = ! empty( $meta_data[0] ) ? $meta_data[0] : '';
			$html      .= '<td colspan="2">' . esc_attr( $meta_value ) . '</td>';
			$html      .= '</tr>';
			++$counter;
			if ( 10 == $counter || $break_point == $pages ) {
				$counter = 0;
				++$break_point;
				$html .= '</tbody>';
			}
		}
	} else {
		$html .= '<tr><td colspan="4" class="etsy-error">No data found. Please search the metakeys.</td></tr>';
	}
	$html .= '</table>';
	return $html;
}

/**
 * Callback function for display html.
 *
 * @since 1.0.0
 */
function get_etsy_instuctions_html( $label = 'Instructions' ) {
	?>
	<div class="ced_etsy_parent_element">
		<h2>
			<label><?php echo esc_html_e( $label, 'etsy-woocommerce-integration' ); ?></label>
			<span class="dashicons dashicons-arrow-down-alt2 ced_etsy_instruction_icon"></span>
		</h2>
	</div>
	<?php
}

/**
 * *********************************************
 * Get Product id by listing id and Shop Name
 * *********************************************
 *
 * @since 1.0.0
 */
function etsy_get_product_id_by_shopname_and_listing_id( $shop_name = '', $listing = '' ) {

	if ( empty( $shop_name ) || empty( $listing ) ) {
		return;
	}
	$if_exists  = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'product',
			'post_status' => array_keys( get_post_statuses() ),
			'meta_query'  => array(
				array(
					'key'     => '_ced_etsy_listing_id_' . $shop_name,
					'value'   => $listing,
					'compare' => '=',
				),
			),
			'fields'      => 'ids',
		)
	);
	$product_id = isset( $if_exists[0] ) ? $if_exists[0] : '';
	return $product_id;
}

function ced_etsy_cedcommerce_logo() {
	return '<img src="' . esc_url( CED_ETSY_URL . 'admin/assets/images/ced-logo.png' ) . '">';
}

function etsy_request() {
	$request = new \Cedcommerce\EtsyManager\Ced_Etsy_Request();
	return $request;
}

function etsy_shop_id( $shop_name = '' ) {
	$saved_etsy_details = get_option( 'ced_etsy_details', array() );
	$shopDetails        = $saved_etsy_details[ $shop_name ];
	$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
	return $shop_id;
}


function deactivate_ced_etsy_woo_missing() {
	deactivate_plugins( CED_ETSY_PLUGIN_BASENAME );
	add_action( 'admin_notices', 'ced_etsy_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

/**
 * Callback function for sending notice if woocommerce is not activated.
 *
 * @since 1.0.0
 */
function ced_etsy_woo_missing_notice() {
	// translators: %s: search term !!
	echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html( __( 'Etsy Integration For Woocommerce requires WooCommerce to be installed and active. You can download %s from here.', 'etsy-woocommerce-integration' ) ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}

function ced_etsy_check_woocommerce_active() {
	/** Alter active plugin list
				 *
				 * @since 2.0.0
				 */
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	}
	return false;
}

function ced_etsy_format_response( $message = '', $shop_name = '' ) {
	$formatted_responses = array( 'invalid_token' => "Token expired . This may be because of recent change in login details for 'etsy.com' or some other reason . In order to update the token please <a href='" . esc_url( ced_etsy_get_auth_url( $shop_name ) ) . "' class='expired_access_token' > <b><i> Re-authorize </i></b> </a> ." );
	$message             = isset( $formatted_responses[ $message ] ) ? $formatted_responses[ $message ] : $message;
	return $message;
}

function ced_etsy_get_auth_url( $shop_name ) {

	$scopes = array(
		'address_r',
		'address_w',
		'billing_r',
		'cart_r',
		'cart_w',
		'email_r',
		'favorites_r',
		'favorites_w',
		'feedback_r',
		'listings_d',
		'listings_r',
		'listings_w',
		'profile_r',
		'profile_w',
		'recommend_r',
		'recommend_w',
		'shops_r',
		'shops_w',
		'transactions_r',
		'transactions_w',
	);

	$scopes         = urlencode( implode( ' ', $scopes ) );
	$redirect_uri   = 'https://woodemo.cedcommerce.com/woocommerce/authorize/etsy/authorize.php';
	$client_id      = 'ghvcvauxf2taqidkdx2sw4g4';
	$verifier       = base64_encode( admin_url( 'admin.php?page=ced_etsy&shop_name=' . $shop_name ) );
	$code_challenge = strtr(
		trim(
			base64_encode( pack( 'H*', hash( 'sha256', $verifier ) ) ),
			'='
		),
		'+/',
		'-_'
	);

	return "https://www.etsy.com/oauth/connect?response_type=code&redirect_uri=$redirect_uri&scope=$scopes&client_id=$client_id&state=$verifier&code_challenge=$code_challenge&code_challenge_method=S256";
}


function get_etsy_shop_id( $shop_name = '' ) {
	$saved_etsy_details = get_option( 'ced_etsy_details', array() );
	$shopDetails        = $saved_etsy_details[ $shop_name ];
	$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
	return $shop_id;
}

function ced_filter_input() {
	return filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
}

function get_product_id_by_params( $meta_key = '', $meta_value = '' ) {
	if ( ! empty( $meta_value ) ) {
		$posts = get_posts(
			array(

				'numberposts' => -1,
				'post_type'   => array( 'product', 'product_variation' ),
				'meta_query'  => array(
					array(
						'key'     => $meta_key,
						'value'   => trim( $meta_value ),
						'compare' => '=',
					),
				),
				'fields'      => 'ids',

			)
		);
		if ( ! empty( $posts ) ) {
			return $posts[0];
		}
		return false;
	}
	return false;
}


function get_etsy_shop_name() {
	$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : get_option( 'ced_etsy_shop_name' );
	return $shop_name;
}

