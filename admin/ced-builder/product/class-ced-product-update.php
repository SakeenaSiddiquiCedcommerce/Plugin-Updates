<?php
/**
 * Product Delete class to delete the product.
 *
 * @since 1.0.0
 * @package Cedcommmerce\ProductDelete\Class
 */

namespace Cedcommerce\Product;

/**
 * Class ProductDelete
 *
 * @package Cedcommerce\Product.
 */
class Ced_Product_Update {

	/**
	 * Listing ID variable
	 *
	 * @var int
	 */
	public $listing_id;

	/**
	 * Listing ID variable
	 *
	 * @var int
	 */
	public $product_id;

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
	 * Set the value of a property which is not exist in Class.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $product_id = '', $shop_name = '' ) {
		$this->shop_name  = $shop_name;
		$this->product_id = ! is_array( $product_id ) ? array( $product_id ) : $product_id;
		if ( ! empty( $this->shop_name ) && $this->product_id ) {
			$this->listing_id = get_post_meta( $this->product_id, '_ced_etsy_listing_id_' . $this->shop_name, true );
		}
	}

	 /**
	  * ***********************
	  * UPDATE PRODUCT TO ETSY
	  * ***********************
	  *
	  * @since 1.0.0
	  *
	  * @param array  $product_ids Product lsting  ids.
	  * @param string $shop_name Active shopName.
	  *
	  * @return $response ,
	  */
	public function ced_etsy_update_product( $product_ids = array(), $shop_name = '' ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}
		$notification = array();
		$shop_name    = empty( $shop_name ) ? $this->shop_name : $shop_name;
		$product_ids  = empty( $product_ids ) ? $this->product_id : $product_ids;
		foreach ( $product_ids as $product_id ) {
			if ( empty( $this->listing_id ) ) {
				$this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			}
			$payload    = new \Cedcommerce\Product\Ced_Product_Payload( $product_id, $shop_name );
			$arguements = $payload->get_formatted_data( $product_id, $shop_name );
			if ( isset( $arguements['has_error'] ) ) {
						$notification['status']  = 400;
						$notification['message'] = $arguements['error'];
			} else {
				$arguements['state'] = $payload->get_state();
				$shop_id             = get_etsy_shop_id( $shop_name );
				$action              = "application/shops/{$shop_id}/listings/{$this->listing_id}";
				/** Refresh token
				 *
				 * @since 2.0.0
				 */
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$response = etsy_request()->put( $action, $arguements, $shop_name );
				if ( isset( $response['listing_id'] ) ) {
					update_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $response ) );
					$notification['status']  = 200;
					$notification['message'] = 'Product updated successfully';
				} elseif ( isset( $response['error'] ) ) {
					$notification['status']  = 400;
					$notification['message'] = $response['error'];
				} else {
					$notification['status']  = 400;
					$notification['message'] = json_encode( $response );
				}
				global $activity;
					$activity->action        = 'Update';
					$activity->type          = 'product';
					$activity->input_payload = $arguements;
					$activity->response      = $response;
					$activity->post_id       = $product_id;
					$activity->shop_name     = $shop_name;
					$activity->post_title    = $arguements['title'];

						$activity->execute();
			}
		}
		return $notification;
	}

	public function ced_etsy_update_inventory( $product_ids = array(), $shop_name = '', $is_sync = false ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}
		$notification = array();
		$shop_name    = empty( $shop_name ) ? $this->shop_name : $shop_name;
		$product_ids  = empty( $product_ids ) ? $this->product_id : $product_ids;
		foreach ( $product_ids as $product_id ) {
			$_product = wc_get_product( $product_id );
			if ( empty( $this->listing_id ) ) {
				$this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			}
			$payload = new \Cedcommerce\Product\Ced_Product_Payload( $product_id, $shop_name );
			if ( 'variable' == $_product->get_type() ) {
				$offerings_payload = $payload->ced_variation_details( $product_id, $shop_name );
				$input_payload     = $offerings_payload;
				$response          = $this->update_variation_sku_to_etsy( $product_id, $this->listing_id, $shop_name, $offerings_payload, false );
			} else {
				$payload->get_formatted_data( $product_id, $shop_name );
				$sku      = get_post_meta( $product_id, '_sku', true );
				$response = etsy_request()->get( "application/listings/{$this->listing_id}/inventory", $shop_name );
				if ( isset( $response['products'][0] ) ) {
					if ( (int) $payload->get_quantity() <= 0 ) {
						$response = $this->ced_etsy_deactivate_product( $product_id, $shop_name );
						update_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $response ) );
						$input_payload = array( $this->listing_id );
					} else {
						$product_payload = $response;
						$product_payload['products'][0]['offerings'][0]['quantity'] = (int) $payload->get_quantity();
						$product_payload['products'][0]['offerings'][0]['price']    = (float) $payload->get_price();
						$product_payload['products'][0]['sku']                      = (string) $sku;
						unset( $product_payload['products'][0]['is_deleted'] );
						unset( $product_payload['products'][0]['product_id'] );
						unset( $product_payload['products'][0]['offerings'][0]['is_deleted'] );
						unset( $product_payload['products'][0]['offerings'][0]['offering_id'] );
						/** Refresh token
				 *
				 * @since 2.0.0
				 */
						do_action( 'ced_etsy_refresh_token', $shop_name );
						$input_payload = $product_payload;
						$response      = etsy_request()->put( "application/listings/{$this->listing_id}/inventory", $product_payload, $shop_name );
					}
				}
			}

			global $activity;
						$activity->action        = 'Update';
						$activity->type          = 'product_inventory';
						$activity->input_payload = $input_payload;
						$activity->response      = $response;
						$activity->post_id       = $product_id;
						$activity->shop_name     = $shop_name;
						$activity->post_title    = $_product->get_title();
						$activity->is_auto       = $is_sync;
						$activity->execute();

			if ( isset( $response['products'][0] ) ) {
				$notification['status']  = 200;
				$notification['message'] = 'Product inventory updated successfully';
			} elseif ( isset( $response['listing_id'] ) ) {
				$notification['status']  = 200;
				$notification['message'] = 'Product deactivated on etsy';
			} elseif ( isset( $response['error'] ) ) {
				$notification['status']  = 400;
				$notification['message'] = $response['error'];
			} else {
				$notification['status']  = 400;
				$notification['message'] = json_encode( $response );
			}
		}
		return $notification;
	}


	public function ced_etsy_activate_product( $product_ids = array(), $shop_name = '' ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}
		$shop_name   = empty( $shop_name ) ? $this->shop_name : $shop_name;
		$product_ids = empty( $product_ids ) ? $this->product_id : $product_ids;
		foreach ( $product_ids as $product_id ) {
			if ( empty( $this->listing_id ) ) {
				$this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			}
			$payload             = new \Cedcommerce\Product\Ced_Product_Payload( $product_id, $shop_name );
			$arguements['state'] = $payload->get_state();
			$shop_id             = get_etsy_shop_id( $shop_name );
			$action              = "application/shops/{$shop_id}/listings/{$this->listing_id}";
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$this->response = etsy_request()->put( $action, $arguements, $shop_name );
			return $this->response;
		}
	}

	public function ced_etsy_deactivate_product( $product_ids = array(), $shop_name = '' ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}
		$shop_name   = empty( $shop_name ) ? $this->shop_name : $shop_name;
		$product_ids = empty( $product_ids ) ? $this->product_id : $product_ids;
		foreach ( $product_ids as $product_id ) {
			if ( empty( $this->listing_id ) ) {
				$this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			}
			$payload             = new \Cedcommerce\Product\Ced_Product_Payload( $product_id, $shop_name );
			$arguements['state'] = 'inactive';
			$shop_id             = get_etsy_shop_id( $shop_name );
			$action              = "application/shops/{$shop_id}/listings/{$this->listing_id}";
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$this->response = etsy_request()->put( $action, $arguements, $shop_name );
			return $this->response;
		}
	}

	 /**
	  * ***********************************
	  * UPDATE LISTING OFFERINGS TO ETSY
	  * ***********************************
	  *
	  * @since 1.0.0
	  *
	  * @param array  $product_ids Product lsting  ids.
	  * @param string $shop_name Active shopName.
	  *
	  * @return $response ,
	  */
	private function update_variation_sku_to_etsy( $product_id = '', $listing_id = '', $shop_name = '', $offerings_payload = '', $is_sync = false ) {
		/** Refresh token
				 *
				 * @since 2.0.0
				 */
		do_action( 'ced_etsy_refresh_token', $shop_name );
		$response = etsy_request()->put( "application/listings/{$listing_id}/inventory", $offerings_payload, $shop_name );
		return $response;
	}


	/**
	 * ***************************
	 * UPDATE IMAGES ON ETSY SHOP
	 * ***************************
	 *
	 * @since 1.0.0
	 *
	 * @param array $product_ids All  product ID.
	 * @param array $shop_name Active shop name of Etsy.
	 *
	 * @return array.
	 */
	public function ced_update_images_on_etsy( $product_ids = array(), $shop_name = '' ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}
		$shop_id      = get_etsy_shop_id( $shop_name );
		$notification = array();
		if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
			foreach ( $product_ids as $pr_id ) {
				$listing_id = get_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, true );
				update_post_meta( $pr_id, 'ced_etsy_previous_thumb_ids' . $listing_id, '' );
				$etsy_images = etsy_request()->get( "application/listings/{$listing_id}/images", $shop_name );
				$etsy_images = isset( $etsy_images['results'] ) ? $etsy_images['results'] : array();
				foreach ( $etsy_images as $key => $image_info ) {
					$main_image_id = isset( $image_info['listing_image_id'] ) ? $image_info['listing_image_id'] : '';
					// Get all the listing Images form Etsy

					/** Refresh token
				 *
				 * @since 2.0.0
				 */
					do_action( 'ced_etsy_refresh_token', $shop_name );
					$action   = "application/shops/{$shop_id}/listings/{$listing_id}/images/{$main_image_id}";
					$response = etsy_request()->delete( $action, $shop_name );
				}
				// Upload Images back to Etsy.
				$upload = new \Cedcommerce\Product\Ced_Product_Upload();
				$upload->ced_etsy_prep_and_upload_img( $pr_id, $shop_name, $listing_id );
				$notification['status']  = 200;
				$notification['message'] = 'Image updated successfully';
			}
		}
		return $notification;
	}

}
