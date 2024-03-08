<?php
/**
 * Shipment Order Template
 *
 * @package  Woocommerce_Etsy_Integration
 * @version  1.0.0
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
global $post;

$order_id              = isset( $post->ID ) ? intval( $post->ID ) : '';
$umb_etsy_order_status = get_post_meta( $order_id, '_etsy_umb_order_status', true );

$merchant_order_id = get_post_meta( $order_id, 'merchant_order_id', true );
$purchase_order_id = get_post_meta( $order_id, 'purchaseOrderId', true );
$fulfillment_node  = get_post_meta( $order_id, 'fulfillment_node', true );
$order_detail      = get_post_meta( $order_id, 'order_detail', true );
$order_item        = get_post_meta( $order_id, 'order_items', true );

if ( isset( $order_item[0] ) ) {
	$order_items = $order_item;
} else {
	$order_items[0] = $order_item['orderLine'];
}

$number_items          = 0;
$umb_etsy_order_status = get_post_meta( $order_id, '_etsy_umb_order_status', true );
if ( empty( $umb_etsy_order_status ) || 'Fetched' == $umb_etsy_order_status ) {
	$umb_etsy_order_status = 'Created';
}

?>

<div id="umb_etsy_order_settings" class="panel woocommerce_options_panel">
	<div class="ced_etsy_loader" class="loading-style-bg" style="display: none;">
		<img src="<?php echo esc_url( CED_ETSY_URL . 'admin/assets/images/loading.gif' ); ?>">
	</div>

	<div class="options_group">
		<p class="form-field">
			<h3><center>
				<?php
				esc_html_e( 'ETSY ORDER STATUS : ', 'woocommerce-etsy-integration' );
				echo esc_attr( strtoupper( $umb_etsy_order_status ) );
				?>
			</center></h3>
		</p>
	</div>
	<div class="ced_etsy_error"></div>
	<div class="options_group umb_etsy_options"> 
		<?php
		if ( 'Created' == $umb_etsy_order_status ) {
			?>
			<div id="ced_etsy_shipment_wrap">
				<div>
					<table class="widefat fixed stripped">
						<tbody>
							<tr>
								<td>
									<span>Tracking Code</span>
								</td>
								<td>
									<input type="text" name="" id="ced_etsy_tracking_code" class="ced_etsy_required_data">
								</td>
							</tr>
							<tr>
								<td>
									<span>Carrier Name</span>
								</td>
								<td>
									<input type="text" name="" id="ced_etsy_carrier_name" class="ced_etsy_required_data">
								</td>
							</tr>
							
							<tr>
								<td>
									<input type="button" class="button button-primary" name="" id="ced_etsy_submit_shipment" value="Submit" data-order-id="<?php echo esc_attr( $order_id ); ?>">
								</td>
								<td>	
									<span class="ced_spinner spinner"></span>									
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<?php
		}
		?>
	</div>    
</div>    
