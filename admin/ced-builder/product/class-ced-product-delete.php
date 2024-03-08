<?php
/**
 * Product Delete class to delete the product.
 *
 * @since 1.0.0
 * @package Cedcommmerce\ProductDelete\Class
 */
namespace Cedcommerce\Product;

use Cedcommerce\EtsyManager\Ced_Etsy_Request as Etsy_Request;

/**
 * Class ProductDelete
 *
 * @package Cedcommerce\Product.
 */
class Ced_Product_Delete extends Etsy_Request {

	/**
	 * Listing ID variable
	 *
	 * @var int
	 */
	public $listing_id;

	/**
	 * Etsy shop name.
	 *
	 * @var string
	 */
	public $shop_name;


	/**
	 * Etsy Deleted response.
	 *
	 * @var string
	 */
	public $response;

	/**
	 * Ced_Product_Delete constructor.
	 */
	public function __construct( $shop_name = '', $product_id = '' ) {
		$this->shop_name  = isset( $shop_name ) ? $shop_name : '';
		$this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $this->shop_name, true );
	}

	/**
	 * Get value of an property which isn't exist in this class.
	 *
	 * @since    1.0.0
	 */
	public function __get( $property_name ) {
		if ( 'result' === $property_name ) {
			return $this->response;
		}
	}

	/**
	 * Set the value of a property which is not exist in Class.
	 *
	 * @since    1.0.0
	 */
	public function __set( $name, $value ) {
		if ( 'e_shop' === $name || 's_n' === $name || 'shop' === $name ) {
			$this->shop_name = $value;
		}
	}
	/**
	 * Delete Listing from Etsy.
	 *
	 * @return array
	 */
	public function ced_etsy_delete_product( $product_ids = array(), $shop_name = '', $log = true ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}
		$notification = array();
		foreach ( $product_ids as $product_id ) {
			$product    = wc_get_product( $product_id );
			$listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			if ( $listing_id ) {
				/** Refresh token
				 *
				 * @since 2.0.0
				 */
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$action   = "application/listings/{$listing_id}";
				$response = parent::delete( $action, $shop_name );
				if ( ! isset( $response['error'] ) ) {
					delete_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name );
					delete_post_meta( $product_id, '_ced_etsy_url_' . $shop_name );
					delete_post_meta( $product_id, '_ced_etsy_product_files_uploaded' . $listing_id );
					delete_post_meta( $product_id, 'ced_etsy_previous_thumb_ids' . $listing_id );
					$notification['status']  = 200;
					$notification['message'] = 'Product removed successfully';
					$response['results']     = $notification;
				} elseif ( isset( $response['error'] ) ) {
					$notification['status']  = 400;
					$notification['message'] = $response['error'];
				} else {
					$notification['status']  = 400;
					$notification['message'] = json_encode( $response );
				}
			}

			global $activity;
						$activity->action        = 'Remove';
						$activity->type          = 'product';
						$activity->input_payload = $listing_id;
						$activity->response      = $response;
						$activity->post_id       = $product_id;
						$activity->shop_name     = $shop_name;
						$activity->post_title    = $product->get_title();
			if ( $log ) {
				$activity->execute();
			}
		}
		return $notification;
	}
}
