<?php
namespace Cedcommerce\Product;

use Cedcommerce\EtsyManager\Ced_Etsy_Request as Etsy_Request;
if ( ! class_exists( 'Ced_Product_Upload' ) ) {
	class Ced_Product_Upload extends Etsy_Request {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $_instance    The ID of this plugin.
		 */
		public static $_instance;
		/**
		 * Saved data at the global settings.
		 *
		 * @since    2.0.8
		 * @var      string    $global_settings    variable to hold all saved data.
		 */
		private $global_settings;
		/**
		 * The saved cedEtsy Data.
		 *
		 * @since    2.0.8
		 * @var      string    $saved_etsy_details    All saved data.
		 */
		private $saved_etsy_details;
		/**
		 * Hold the Woocommerce product.
		 *
		 * @since    2.0.8
		 * @var      string    $ced_product    Wocommerce product.
		 */
		public $ced_product;
		/**
		 * The listing ID of uploaded product.
		 *
		 * @since    1.0.0
		 * @var      string    $l_id    The listing ID of the product.
		 */
		private $l_id;
		/**
		 * Active Etsy shopName.
		 *
		 * @since    1.0.0
		 * @var      string    $shop_name  Etsy shopName.
		 */
		public $shop_name;
		/**
		 * Ced_Etsy_Config Instance.
		 *
		 * Ensures only one instance of Ced_Etsy_Config is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function __construct( $shop_name = '' ) {
			$this->shop_name = $shop_name;
		}


		/**
		 * ********************************************
		 * Function for products data to be uploaded.
		 * ********************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $prodIDs Checked Product ids
		 * @param string $shopName Active Shop Name
		 */

		public function ced_etsy_upload_product( $pro_ids = array(), $shop_name = '', $is_cron = false ) {
			$this->is_cron = $is_cron;
			if ( ! is_array( $pro_ids ) ) {
				$pro_ids = array( $pro_ids );
			}
			if ( is_array( $pro_ids ) && ! empty( $pro_ids ) ) {
				$shop_name = trim( $shop_name );
				$response  = self::prepare_items( $pro_ids, $shop_name, $is_cron );
				return $response;

			}
		}

		/**
		 * *****************************************************
		 * Function for preparing product data to be uploaded.
		 * *****************************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $prodIDs Checked Product ids
		 * @param string $shopName Active Shop Name
		 *
		 * @return Uploaded Ids
		 */
		private function prepare_items( $pro_ids = array(), $shop_name = '', $is_sync = false ) {
			if ( '' == $shop_name || empty( $shop_name ) ) {
				return;
			}
			$notification = array();
			foreach ( $pro_ids as $key => $pr_id ) {
				$already_uploaded = get_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					continue;
				}
				$this->ced_product  = wc_get_product( absint( $pr_id ) );
				$pro_type           = $this->ced_product->get_type();
				$delete_instance    = new \Cedcommerce\Product\Ced_Product_Delete();
				$payload            = new \Cedcommerce\Product\Ced_Product_Payload( $pr_id, $shop_name );
				$payload->is_upload = true;
				if ( 'variable' == $pro_type ) {
					$this->data = $payload->get_formatted_data( $pr_id, $shop_name );
					if ( isset( $this->data['has_error'] ) ) {
						$notification['status']  = 400;
						$notification['message'] = $this->data['error'];
					} else {
						self::doupload( $pr_id, $shop_name );
						$response = $this->upload_response;
						if ( isset( $response['listing_id'] ) ) {
							$this->l_id = isset( $response['listing_id'] ) ? $response['listing_id'] : '';
							update_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, $this->l_id );
							update_post_meta( $pr_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
							update_post_meta( $pr_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $response ) );
							$offerings_payload = $payload->ced_variation_details( $pr_id, $shop_name );
							$var_response      = $this->update_variation_sku_to_etsy( $pr_id, $this->l_id, $shop_name, $offerings_payload, false );
							if ( ! isset( $var_response['products'][0]['product_id'] ) ) {
								$this->data['variation'] = $offerings_payload;
								$response                = $var_response;
								$notification['status']  = 400;
								$notification['message'] = isset( $var_response['error'] ) ? $var_response['error'] : '';
								$delete_instance->ced_etsy_delete_product( array( $pr_id ), $shop_name, false );
								continue;
							} else {
								$this->ced_etsy_prep_and_upload_img( $pr_id, $shop_name, $this->l_id );
								if ( 'active' == $payload->get_state() ) {
									$activate = ( new \Cedcommerce\Product\Ced_Product_Update( $pr_id, $shop_name ) )->ced_etsy_activate_product( $pr_id, $shop_name );
								}

								$notification['status']  = 200;
								$notification['message'] = 'product uploaded successfully';
							}
						} elseif ( isset( $response['error'] ) ) {
							$notification['status']  = 400;
							$notification['message'] = $response['error'];
						} else {
							$notification['status']  = 400;
							$notification['message'] = json_encode( $response );
						}

						global $activity;
						$activity->action        = 'Upload';
						$activity->type          = 'product';
						$activity->input_payload = $this->data;
						$activity->response      = $response;
						$activity->post_id       = $pr_id;
						$activity->shop_name     = $shop_name;
						$activity->is_auto       = $is_sync;
						$activity->post_title    = $this->data['title'];
						$activity->execute();
					}
				} elseif ( 'simple' == $pro_type ) {
					$this->data = $payload->get_formatted_data( $pr_id, $shop_name );
					if ( isset( $this->data['has_error'] ) ) {
						$notification['status']  = 400;
						$notification['message'] = $this->data['error'];
					} else {
						self::doupload( $pr_id, $shop_name );
						$response = $this->upload_response;
						if ( isset( $response['listing_id'] ) ) {
							update_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, $response['listing_id'] );
							update_post_meta( $pr_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
							update_post_meta( $pr_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $response ) );
							$this->l_id = $response['listing_id'];
							$this->ced_etsy_prep_and_upload_img( $pr_id, $shop_name, $this->l_id );
							if ( 'active' == $payload->get_state() ) {
								$activate = ( new \Cedcommerce\Product\Ced_Product_Update( $pr_id, $shop_name ) )->ced_etsy_activate_product( $pr_id, $shop_name );
							}
							$activate = ( new \Cedcommerce\Product\Ced_Product_Update( $pr_id, $shop_name ) )->ced_etsy_update_inventory( $pr_id, $shop_name );
							if ( $payload->is_downloadable ) {
								$this->ced_upload_downloadable( $pr_id, $shop_name, $response['listing_id'], $payload->downloadable_data );
							}

							$notification['status']  = 200;
							$notification['message'] = 'Product uploaded successfully';
						} elseif ( isset( $response['error'] ) ) {
							$notification['status']  = 400;
							$notification['message'] = $response['error'];
						} else {
							$notification['status']  = 400;
							$notification['message'] = json_encode( $response );
						}

						global $activity;
						$activity->action        = 'Upload';
						$activity->type          = 'product';
						$activity->input_payload = $this->data;
						$activity->response      = $response;
						$activity->post_id       = $pr_id;
						$activity->shop_name     = $shop_name;
						$activity->is_auto       = $is_sync;
						$activity->post_title    = $this->data['title'];
						$activity->execute();
					}
				} else {
					$notification['status']  = 400;
					$notification['message'] = $pro_type . ' product type not supported.';
				}
			}
			return $notification;
		}

		/**
		 * ***************************
		 * Upload downloadable files
		 * ***************************
		 *
		 * @since 2.0.8
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 *
		 * @return
		 */
		private function ced_upload_downloadable( $p_id = '', $shop_name = '', $l_id = '', $downloadable_data = array() ) {
			$listing_files_uploaded = get_post_meta( $p_id, '_ced_etsy_product_files_uploaded' . $l_id, true );
			if ( empty( $listing_files_uploaded ) ) {
				$listing_files_uploaded = array();
			}
			if ( ! empty( $downloadable_data ) ) {
				$count = 0;
				foreach ( $downloadable_data as $data ) {
					if ( $count > 4 ) {
						break;
					}
					$file_data = $data->get_data();
					if ( isset( $listing_files_uploaded[ $file_data['id'] ] ) ) {
						continue;
					}
					try {
						$file_path = str_replace( wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $file_data['file'] );
						$extension = ! empty( $file_path ) ? pathinfo( $file_path )['extension'] : '';
						/** Refresh token
				 *
				 * @since 2.0.0
				 */
						do_action( 'ced_etsy_refresh_token', $shop_name );
						$shop_id  = get_etsy_shop_id( $shop_name );
						$response = parent::ced_etsy_upload_image_and_file( 'file', "application/shops/{$shop_id}/listings/{$l_id}/files", $file_path, ( $file_data['name'] . '.' . $extension ), $shop_name );
						if ( isset( $response['listing_file_id'] ) ) {
							$listing_files_uploaded[ $file_data['id'] ] = $response['listing_file_id'];
							update_post_meta( $p_id, '_ced_etsy_product_files_uploaded' . $l_id, $listing_files_uploaded );
						}
					} catch ( Exception $e ) {
						$this->error_msg['msg'] = 'Message:' . $e->getMessage();
						return $this->error_msg;
					}
				}
			}
		}



		/**
		 * *************************
		 * Update uploaded images.
		 * *************************
		 *
		 * @since 2.0.8
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 *
		 * @return
		 */
		public function ced_etsy_prep_and_upload_img( $p_id = '', $shop_name = '', $listing_id = '' ) {
			if ( empty( $p_id ) || empty( $shop_name ) ) {
				return;
			}
			$this->ced_product = isset( $this->ced_product ) ? $this->ced_product : wc_get_product( $p_id );
			$prnt_img_id       = get_post_thumbnail_id( $p_id );
			if ( WC()->version < '3.0.0' ) {
				$attachment_ids = $this->ced_product->get_gallery_attachment_ids();
			} else {
				$attachment_ids = $this->ced_product->get_gallery_image_ids();
			}
			$previous_thum_ids = get_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $listing_id, true );
			if ( empty( $previous_thum_ids ) || ! is_array( $previous_thum_ids ) ) {
				$previous_thum_ids = array();
			}
			$attachment_ids = array_slice( $attachment_ids, 0, 9 );
			if ( ! empty( $attachment_ids ) ) {
				foreach ( array_reverse( $attachment_ids ) as $attachment_id ) {
					if ( isset( $previous_thum_ids[ $attachment_id ] ) ) {
						continue;
					}

					/*
					|=======================
					| UPLOAD GALLERY IMAGES
					|=======================
					*/
					$image_result = self::do_image_upload( $listing_id, $p_id, $attachment_id, $shop_name );
					if ( isset( $image_result['listing_image_id'] ) ) {
						$previous_thum_ids[ $attachment_id ] = $image_result['listing_image_id'];
						update_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $listing_id, $previous_thum_ids );
					}
				}
			}

			/*
			|===================
			| UPLOAD MAIN IMAGE
			|===================
			*/
			if ( ! isset( $previous_thum_ids[ $prnt_img_id ] ) ) {
				$image_result = self::do_image_upload( $listing_id, $p_id, $prnt_img_id, $shop_name );
				if ( isset( $image_result['listing_image_id'] ) ) {
					$previous_thum_ids[ $prnt_img_id ] = $image_result['listing_image_id'];
					update_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $listing_id, $previous_thum_ids );
				}
			}
		}

		/**
		 * ************************************
		 * UPLOAD IMAGED ON THE ETSY SHOP ;)
		 * ************************************
		 *
		 * @since 1.0.0
		 *
		 * @param int    $l_id Product listing ids.
		 * @param int    $pr_id Product ids .
		 * @param int    $img_id Image Ids.
		 * @param string $shop_name Active Shop Name
		 *
		 * @return Nothing [Message]
		 */

		public function do_image_upload( $l_id, $pr_id, $img_id, $shop_name ) {
			$image_path = wp_get_attachment_url( $img_id );
			$image_name = basename( $image_path );
			/** Refresh token
							 *
							 * @since 2.0.0
							 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$shop_id  = get_etsy_shop_id( $shop_name );
			$response = parent::ced_etsy_upload_image_and_file( 'image', "application/shops/{$shop_id}/listings/{$l_id}/images", $image_path, $image_name, $shop_name );
			return $this->ced_etsy_parse_response( $response );

		}

		public function ced_etsy_parse_response( $json ) {
			return json_decode( $json, true );
		}

		/**
		 * *************************
		 * Prepare file to be upload.
		 * *************************
		 *
		 * @since 2.0.5
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 * @param string $listingID Listing ID from Etsy.
		 *
		 * @return
		 */

		public function prepare_files( $product_id, $shop_name, $listingID ) {
			$downloadable_data = $this->downloadable_data;
			if ( ! empty( $downloadable_data ) ) {
				$count = 0;
				foreach ( $downloadable_data as $data ) {
					if ( $count > 4 ) {
						break;
					}
					$file_data = $data->get_data();
					$this->upload_files( $product_id, $shop_name, $listingID, $file_data, $count );
				}
			}
		}


		/**
		 * *****************************************
		 * UPDATE VARIATION SKU TO ETSY SHOP
		 * *****************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $listing_id Product lsting  ids.
		 * @param array  $productId Product  ids.
		 * @param string $shopId Active shopName.
		 *
		 * @return $reponse
		 */

		private function update_variation_sku_to_etsy( $product_id = '', $listing_id = '', $shop_name = '', $offerings_payload = '', $is_sync = false ) {
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$response = parent::put( "application/listings/{$listing_id}/inventory", $offerings_payload, $shop_name );
			if ( isset( $response['products'][0]['product_id'] ) ) {
				update_post_meta( $product_id, 'ced_etsy_last_updated' . $shop_name, gmdate( 'l jS \of F Y h:i:s A' ) );
			}
			if ( ! $is_sync ) {
				return $response;
			}
		}


		/**
		 * ****************************************************
		 * UPLOADING THE VARIABLE AND SIMPLE PROUCT TO ETSY
		 * ****************************************************
		 *
		 * @since 1.0.0
		 *
		 * @param int    $product_id Product  ids.
		 * @param string $shop_name Active shop Name.
		 *
		 * @return Uploaded product Ids.
		 */

		public function doupload( $product_id, $shop_name ) {
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$shop_id  = get_etsy_shop_id( $shop_name );
			$response = parent::post( "application/shops/{$shop_id}/listings", $this->data, $shop_name );
			/**
			 * ************************************************
			 *  Update post meta after uploading the Products.
			 * ************************************************
			 *
			 * @since 2.0.8
			 */

			if ( isset( $response['listing_id'] ) ) {
				update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $response['listing_id'] );
				update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
			}

			if ( isset( $response['error'] ) ) {
				$error                 = array();
				$error['error']        = isset( $response['error'] ) ? $response['error'] : 'some error occured';
				$this->upload_response = $error;
			} else {
				$this->upload_response = $response;
			}
		}

	}
}
