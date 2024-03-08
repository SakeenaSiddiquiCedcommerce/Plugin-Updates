<?php
namespace Cedcommerce\EtsyManager;

/**
 * Main class for handling reqests.
 *
 * @since      1.0.0
 *
 * @package    Woocommerce Etsy Integration
 * @subpackage Woocommerce Etsy Integration/marketplaces/etsy
 */
use Cedcommerce\Product\Ced_Product_Upload as ProductUpload;
if ( ! class_exists( 'Ced_Etsy_Manager' ) ) {
	/**
	 * Single product related functionality.
	 *
	 * Manage all single product related functionality required for listing product on marketplaces.
	 *
	 * @since      1.0.0
	 * @package    Woocommerce Etsy Integration
	 * @subpackage Woocommerce Etsy Integration/marketplaces/etsy
	 */
	class Ced_Etsy_Manager {

		/**
		 * The Instace of CED_ETSY_etsy_Manager.
		 *
		 * @since    1.0.0
		 * @var      $_instance   The Instance of CED_ETSY_etsy_Manager class.
		 */
		private static $_instance;
		private static $authorization_obj;
		private static $client_obj;
		public $etsy_product_upload;
		/**
		 * CED_ETSY_etsy_Manager Instance.
		 *
		 * Ensures only one instance of CED_ETSY_etsy_Manager is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return CED_ETSY_etsy_Manager instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public $marketplaceID   = 'etsy';
		public $marketplaceName = 'Etsy';


		/**
		 * Constructor.
		 *
		 * Registering actions and hooks for etsy.
		 *
		 * @since 1.0.0
		 */

		public function __construct() {

			$this->etsy_product_upload = ProductUpload::get_instance();

			add_action( 'woocommerce_thankyou', array( $this, 'ced_etsy_update_inventory_on_order_creation' ), 10, 1 );
			add_action( 'admin_init', array( $this, 'ced_etsy_schedules' ) );
			add_filter( 'woocommerce_duplicate_product_exclude_meta', array( $this, 'woocommerce_duplicate_product_exclude_meta' ) );
			add_action( 'updated_post_meta', array( $this, 'ced_relatime_sync_inventory_to_etsy' ), 12, 4 );
			add_filter( 'woocommerce_order_number', array( $this, 'ced_modify_woo_order_number' ), 20, 2 );
			add_action( 'ced_etsy_auto_submit_shipment', array( $this, 'ced_etsy_auto_submit_shipment' ) );
			add_action( 'admin_init', array( $this, 'ced_etsy_get_token_and_shop_data' ), 999, 3 );
			add_action( 'ced_etsy_refresh_token', array( $this, 'ced_etsy_refresh_token_action' ) );

		}

		/**
		 * Refresh Etsy token
		 *
		 * @param string $shop_name
		 * @return void
		 */
		public function ced_etsy_refresh_token_action( $shop_name = '' ) {

			if ( ! $shop_name || get_transient( 'ced_etsy_token_' . $shop_name ) ) {
				return;
			}
			$user_details = get_option( 'ced_etsy_details', array() );
			if ( ! isset( $user_details[ $shop_name ]['details']['token']['refresh_token'] ) ) {
				$legacy_token = isset( $user_details[ $shop_name ]['access_token']['oauth_token'] ) ? $user_details[ $shop_name ]['access_token']['oauth_token'] : '';
				$query_args   = array(
					'grant_type'   => 'token_exchange',
					'client_id'    => etsy_request()->client_id,
					'legacy_token' => $legacy_token,
				);
			} else {
				$refresh_token = isset( $user_details[ $shop_name ]['details']['token']['refresh_token'] ) ? $user_details[ $shop_name ]['details']['token']['refresh_token'] : '';
				$query_args    = array(
					'grant_type'    => 'refresh_token',
					'client_id'     => etsy_request()->client_id,
					'refresh_token' => $refresh_token,
				);

			}

			$parameters = $query_args;
			$action     = 'public/oauth/token';
			$response   = etsy_request()->post( $action, $parameters, $shop_name, $query_args );
			if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
				delete_option( 'ced_etsy_reauthorize_account' );
				$user_details[ $shop_name ]['details']['token'] = $response;
				update_option( 'ced_etsy_details', $user_details );
				set_transient( 'ced_etsy_token_' . $shop_name, $response, (int) $response['expires_in'] );
			}

		}

		/**
		 * Get Etsy token and shop data
		 *
		 * @param string $shop_name
		 * @return void
		 */
		public function ced_etsy_get_token_and_shop_data() {

			if ( isset( $_GET['state'] ) && ! empty( $_GET['code'] ) ) {
				delete_option( 'ced_etsy_reauthorize_account' );
				$code       = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
				$verifier   = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';
				$action     = 'public/oauth/token';
				$query_args = array(
					'grant_type'    => 'authorization_code',
					'client_id'     => 'ghvcvauxf2taqidkdx2sw4g4',
					'redirect_uri'  => 'https://woodemo.cedcommerce.com/woocommerce/authorize/etsy/authorize.php',
					'code'          => $code,
					'code_verifier' => $verifier,
				);
				$parameters = $query_args;
				$shop_name  = get_option( 'ced_etsy_shop_name', '' );
				$response   = etsy_request()->post( $action, $parameters, $shop_name, $query_args );
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					$action = 'application/shops';

					$query_args = array(
						'shop_name' => $shop_name,
					);
					$shop       = etsy_request()->get( $action, '', $query_args );
					if ( isset( $shop['results'][0] ) ) {

						set_transient( 'ced_etsy_token_' . $shop_name, $response, (int) $response['expires_in'] );
						$user_details               = get_option( 'ced_etsy_details', array() );
						$user_id                    = isset( $shop['results'][0]['user_id'] ) ? $shop['results'][0]['user_id'] : '';
						$user_name                  = isset( $shop['results'][0]['login_name'] ) ? $shop['results'][0]['login_name'] : '';
						$shop_id                    = isset( $shop['results'][0]['shop_id'] ) ? $shop['results'][0]['shop_id'] : '';
						$info                       = array(
							'details' => array(
								'ced_etsy_shop_name'      => $shop_name,
								'user_id'                 => $user_id,
								'user_name'               => $user_name,
								'shop_id'                 => $shop_id,
								'ced_etsy_keystring'      => etsy_request()->client_id,
								'ced_etsy_shared_string'  => etsy_request()->client_secret,
								'ced_shop_account_status' => 'Active',
								'token'                   => $response,
								'shop_info'               => $shop['results'][0],
							),
						);
						$user_details[ $shop_name ] = $info;

						if ( count( $user_details ) < 2 ) {
							update_option( 'ced_etsy_details', $user_details );
						}
					}
				}

				wp_redirect( admin_url( 'admin.php?page=ced_etsy&shop_name=' . $shop_name ) );
				exit;
			}

		}



		/**
		 * Sumbit Etsy order shipping details.
		 *
		 * @return void
		 */
		public function ced_etsy_auto_submit_shipment() {
			$etsy_orders = get_posts(
				array(
					'numberposts' => -1,
					'meta_key'    => '_etsy_umb_order_status',
					'meta_value'  => 'Fetched',
					'post_type'   => wc_get_order_types(),
					'post_status' => array_keys( wc_get_order_statuses() ),
					'orderby'     => 'date',
					'order'       => 'DESC',
					'fields'      => 'ids',
				)
			);
			if ( ! empty( $etsy_orders ) && is_array( $etsy_orders ) ) {
				foreach ( $etsy_orders as $woo_order_id ) {
					/**
					 * Etsy ship order function.
					 */
					$this->ced_etsy_auto_ship_order( $woo_order_id );
				}
			}
		}

		/**
		 * Sumbit Etsy order shipping details by Order ID.
		 *
		 * @return void
		 */
		public function ced_etsy_auto_ship_order( $woo_order_id = 0 ) {

			$_etsy_umb_order_status = get_post_meta( $woo_order_id, '_etsy_umb_order_status', true );
			if ( empty( $_etsy_umb_order_status ) || 'Fetched' != $_etsy_umb_order_status ) {
				return;
			}

			$tracking_no      = '';
			$tracking_code    = '';
			$tracking_details = get_post_meta( $woo_order_id, '_wc_shipment_tracking_items', true );

			if ( ! empty( $tracking_details ) ) {
				$tracking_code = isset( $tracking_details[0]['custom_tracking_provider'] ) ? $tracking_details[0]['custom_tracking_provider'] : '';
				if ( empty( $tracking_code ) ) {
					$tracking_code = isset( $tracking_details[0]['tracking_provider'] ) ? $tracking_details[0]['tracking_provider'] : '';
				}
				$tracking_no = isset( $tracking_details[0]['tracking_number'] ) ? $tracking_details[0]['tracking_number'] : '';

				if ( ! empty( $tracking_no ) && ! empty( $tracking_code ) ) {

					$shopId             = get_post_meta( $woo_order_id, 'ced_etsy_order_shop_id', true );
					$_ced_etsy_order_id = get_post_meta( $woo_order_id, '_ced_etsy_order_id', true );
					$saved_etsy_details = get_option( 'ced_etsy_details', array() );
					$shopDetails        = $saved_etsy_details[ $shopId ];
					$shop_id            = $shopDetails['details']['shop_id'];
					$params             = array(
						'tracking_code' => $tracking_no,
						'carrier_name'  => $tracking_code,
					);
					/** Refresh token
				 *
				 * @since 2.0.0
				 */
					do_action( 'ced_etsy_refresh_token', $shopId );
					$action = 'application/shops/' . $shop_id . '/receipts/' . $_ced_etsy_order_id . '/tracking';
					$result = etsy_request()->post( $action, $params, $shopId );
					if ( isset( $result['receipt_id'] ) || isset( $result['Shipping_notification_email_has_already_been_sent_for_this_receipt_'] ) ) {
						update_post_meta( $woo_order_id, '_etsy_umb_order_status', 'Shipped' );
					}
				}
			}

		}

		/**
		 * Ced Etsy modifiy woocommerce order number.
		 *
		 * @param [int] $order_id
		 * @param [int] $order
		 * @return int
		 */
		public function ced_modify_woo_order_number( $order_id, $order ) {
			$_ced_etsy_order_id      = get_post_meta( $order->get_id(), '_ced_etsy_order_id', true );
			$ced_etsy_order_shop_id  = get_post_meta( $order->get_id(), 'ced_etsy_order_shop_id', true );
			$data_on_global_settings = get_option( 'ced_etsy_global_settings', array() );
			$use_etsy_order_no       = isset( $data_on_global_settings[ $ced_etsy_order_shop_id ]['use_etsy_order_no'] ) ? $data_on_global_settings[ $ced_etsy_order_shop_id ]['use_etsy_order_no'] : '';

			if ( ! empty( $_ced_etsy_order_id ) && 'on' == $use_etsy_order_no ) {
				return $_ced_etsy_order_id;
			}
			return $order_id;

		}

		/**
		 * Exclude duplicated products from order.
		 *
		 * @param array $metakeys
		 * @return string
		 */
		public function woocommerce_duplicate_product_exclude_meta( $metakeys = array() ) {
			$shop_name  = get_option( 'ced_etsy_shop_name', '' );
			$metakeys[] = '_ced_etsy_listing_id_' . $shop_name;
			return $metakeys;
		}

		/**
		 * Schedule sycn existing, Auto upload product and Auto Submit order Shipping details.
		 *
		 * @return void
		 */
		public function ced_etsy_schedules() {
			if ( isset( $_GET['shop_name'] ) && ! empty( $_GET['shop_name'] ) ) {
				$shop_name = sanitize_text_field( $_GET['shop_name'] );
				if ( ! wp_get_schedule( 'ced_etsy_sync_existing_products_job_' . $shop_name ) ) {
					wp_schedule_event( time(), 'ced_etsy_6min', 'ced_etsy_sync_existing_products_job_' . $shop_name );
				}

				$data_on_global_settings      = get_option( 'ced_etsy_global_settings', array() );
				$update_tracking              = isset( $data_on_global_settings[ $shop_name ]['update_tracking'] ) ? $data_on_global_settings[ $shop_name ]['update_tracking'] : '';
				$ced_etsy_auto_upload_product = isset( $data_on_global_settings[ $shop_name ]['ced_etsy_auto_upload_product'] ) ? $data_on_global_settings[ $shop_name ]['ced_etsy_auto_upload_product'] : '';
				if ( ! wp_get_schedule( 'ced_etsy_auto_upload_products_' . $shop_name ) && 'on' == $ced_etsy_auto_upload_product ) {
					wp_schedule_event( time(), 'ced_etsy_30min', 'ced_etsy_auto_upload_products_' . $shop_name );
				} elseif ( 'on' != $ced_etsy_auto_upload_product ) {
					wp_clear_scheduled_hook( 'ced_etsy_auto_upload_products_' . $shop_name );
				}

				if ( ! wp_get_schedule( 'ced_etsy_auto_submit_shipment' ) && 'on' == $update_tracking ) {
					wp_schedule_event( time(), 'ced_etsy_30min', 'ced_etsy_auto_submit_shipment' );
				} elseif ( 'on' != $update_tracking ) {
					wp_clear_scheduled_hook( 'ced_etsy_auto_submit_shipment' );
				}
			}
		}

		/**
		 * ******************************************************
		 * Real time Sync product form Wooocommerce to Etsy shop.
		 * ******************************************************
		 *
		 * @param $meta_id    Udpated product meta meta_id of the product.
		 * @param $product_id Updated meta value of the product id.
		 * @param $meta_key   Update products meta key.
		 * @param $mta_value  Udpated changed meta value of the post.
		 */
		public function ced_relatime_sync_inventory_to_etsy( $meta_id, $product_id, $meta_key, $meta_value ) {

			// If tha is changed by _stock only.
			if ( '_stock' == $meta_key || '_price' == $meta_key ) {
				// Active shop name
				$shop_name = get_option( 'ced_etsy_shop_name', '' );

				$_product = wc_get_product( $product_id );
				if ( ! wp_get_schedule( 'ced_etsy_inventory_scheduler_job_' . $shop_name ) || ! is_object( $_product ) ) {
					return;
				}
				// All products by product id
				// check if it has variations.
				if ( $_product->get_type() == 'variation' ) {
					$product_id = $_product->get_parent_id();
				}
				/**
				 * *******************************************
				 *   CALLING FUNCTION TO UDPATE THE INVENTORY
				 * *******************************************
				 */
				$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_etsy_update_inventory( $product_id, $shop_name, true );
			}
		}

		/**
		 * ******************************************************
		 *  Update product inventory on Etsy shop after Woo order
		 * ******************************************************
		 *
		 * @param [int] $order_id
		 * @return void
		 */
		public function ced_etsy_update_inventory_on_order_creation( $order_id ) {
			if ( empty( $order_id ) ) {
				return;
			}

			$shop_name = get_option( 'ced_etsy_shop_name', '' );

			if ( ! wp_get_schedule( 'ced_etsy_inventory_scheduler_job_' . $shop_name ) ) {
					return;
			}

			$product_ids   = array();
			$inventory_log = array();
			$order_obj     = wc_get_order( $order_id );
			$order_items   = $order_obj->get_items();
			if ( is_array( $order_items ) && ! empty( $order_items ) ) {
				foreach ( $order_items as $key => $value ) {
					$product_id    = $value->get_data()['product_id'];
					$product_ids[] = $product_id;
				}
			}
			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				foreach ( $product_ids as $product_id ) {
					$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_etsy_update_inventory( $product_id, $shop_name, true );
				}
			}
		}

		/**
		 * Ced Etsy Fetch Categories
		 *
		 * @since    1.0.0
		 */
		public function ced_etsy_get_categories() {
			$fetchedCategories = $etsyCategoryInstance->get_etsy_categories();
			$categories        = $this->ced_etsy_store_categories( $fetchedCategories );

		}

		/**
		 * Etsy Create Auto Profiles
		 *
		 * @since    1.0.0
		 */
		public function ced_etsy_create_auto_profiles( $etsyMappedCategories = array(), $etsy_mapped_categories_name = array(), $etsyStoreId = '' ) {
			global $wpdb;

			$wooced_etsy_store_categories = get_terms( 'product_cat' );
			$alreadyMappedCategories      = get_option( 'ced_woo_etsy_mapped_categories_' . $etsyStoreId, array() );
			$alreadyMappedCategoriesName  = get_option( 'ced_woo_etsy_mapped_categories_name_' . $etsyStoreId, array() );

			if ( ! empty( $etsyMappedCategories ) ) {
				foreach ( $etsyMappedCategories as $key => $value ) {
					$profileAlreadyCreated = get_term_meta( $key, 'ced_etsy_profile_created_' . $etsyStoreId, true );
					$createdProfileId      = get_term_meta( $key, 'ced_etsy_profile_id_' . $etsyStoreId, true );
					if ( ! empty( $profileAlreadyCreated ) && 'yes' == $createdProfileId ) {
						$need = $this->if_new_profile_need( $key, $value, $etsyStoreId );
						if ( ! $need ) {
							continue;
						} else {
							$this->reset_mapped_category_data( $key, $value, $etsyStoreId );
						}
					}

					$wooCategories      = array();
					$categoryAttributes = array();

					$profile_name = isset( $etsy_mapped_categories_name[ $value ] ) ? $etsy_mapped_categories_name[ $value ] : 'Profile for etsy - Category Id : ' . $value;

					$profile_id = $wpdb->get_results( $wpdb->prepare( "SELECT `id` FROM {$wpdb->prefix}ced_etsy_profiles WHERE `profile_name` = %s AND `shop_name` = %s ", $profile_name, $etsyStoreId ), 'ARRAY_A' );

					if ( ! isset( $profile_id[0]['id'] ) && empty( $profile_id[0]['id'] ) ) {
						$is_active       = 1;
						$marketplaceName = 'etsy';

						foreach ( $etsyMappedCategories as $key1 => $value1 ) {
							if ( $value1 == $value ) {
								$wooCategories[] = $key1;
							}
						}

						$profileData    = array();
						$profileData    = $this->prepareProfileData( $etsyStoreId, $value, $wooCategories );
						$profileDetails = array(
							'profile_name'   => $profile_name,
							'profile_status' => 'active',
							'shop_name'      => $etsyStoreId,
							'profile_data'   => json_encode( $profileData ),
							'woo_categories' => json_encode( $wooCategories ),
						);
						$profileId      = $this->insertetsyProfile( $profileDetails );
					} else {
						$wooCategories      = array();
						$profileId          = $profile_id[0]['id'];
						$profile_categories = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id` = %d ", $profileId ), 'ARRAY_A' );
						$wooCategories      = json_decode( $profile_categories[0]['woo_categories'], true );
						$wooCategories[]    = $key;
						$table_name         = $wpdb->prefix . 'ced_etsy_profiles';
						$wpdb->update(
							$table_name,
							array(
								'woo_categories' => json_encode( array_unique( $wooCategories ) ),
							),
							array( 'id' => $profileId )
						);
					}
					foreach ( $wooCategories as $key12 => $value12 ) {
						update_term_meta( $value12, 'ced_etsy_profile_created_' . $etsyStoreId, 'yes' );
						update_term_meta( $value12, 'ced_etsy_profile_id_' . $etsyStoreId, $profileId );
						update_term_meta( $value12, 'ced_etsy_mapped_category_' . $etsyStoreId, $value );
					}
				}
			}
		}

		/**
		 * Etsy Insert Profiles In database
		 *
		 * @since    1.0.0
		 */
		public function insertetsyProfile( $profileDetails ) {

			global $wpdb;
			$profileTableName = $wpdb->prefix . 'ced_etsy_profiles';

			$wpdb->insert( $profileTableName, $profileDetails );

			$profileId = $wpdb->insert_id;
			return $profileId;
		}

		/**
		 * Etsy Check if Profile Need to be Created
		 *
		 * @since    1.0.0
		 */
		public function if_new_profile_need( $wooCategoryId = '', $etsyCategoryId = '', $etsyStoreId = '' ) {

			$oldetsyCategoryMapped = get_term_meta( $wooCategoryId, 'ced_etsy_mapped_category_' . $etsyStoreId, true );
			if ( $oldetsyCategoryMapped == $etsyCategoryId ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Etsy Update Mapped Category data
		 *
		 * @since    1.0.0
		 */
		public function reset_mapped_category_data( $wooCategoryId = '', $etsyCategoryId = '', $etsyStoreId = '' ) {

			update_term_meta( $wooCategoryId, 'ced_etsy_mapped_category_' . $etsyStoreId, $etsyCategoryId );

			delete_term_meta( $wooCategoryId, 'ced_etsy_profile_created_' . $etsyStoreId );

			$createdProfileId = get_term_meta( $wooCategoryId, 'ced_etsy_profile_id_' . $etsyStoreId, true );

			delete_term_meta( $wooCategoryId, 'ced_etsy_profile_id_' . $etsyStoreId );

			$this->removeCategoryMappingFromProfile( $createdProfileId, $wooCategoryId );
		}

		/**
		 * Etsy Remove previous mapped profile
		 *
		 * @since    1.0.0
		 */
		public function removeCategoryMappingFromProfile( $createdProfileId = '', $wooCategoryId = '' ) {

			global $wpdb;
			$profileTableName = $wpdb->prefix . 'ced_etsy_profiles';
			$profile_data     = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%s ", $createdProfileId ), 'ARRAY_A' );

			if ( is_array( $profile_data ) ) {

				$profile_data  = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
				$wooCategories = isset( $profile_data['woo_categories'] ) ? json_decode( $profile_data['woo_categories'], true ) : array();
				if ( is_array( $wooCategories ) && ! empty( $wooCategories ) ) {
					$categories = array();
					foreach ( $wooCategories as $key => $value ) {
						if ( $value != $wooCategoryId ) {
							$categories[] = $value;
						}
					}
					$categories = json_encode( $categories );
					$wpdb->update( $profileTableName, array( 'woo_categories' => $categories ), array( 'id' => $createdProfileId ) );
				}
			}
		}

		/**
		 * Etsy Prepare Profile data
		 *
		 * @since    1.0.0
		 */
		public function prepareProfileData( $etsyStoreId, $etsyCategoryId, $wooCategories = '' ) {

			$globalSettings     = get_option( 'ced_etsy_global_settings', array() );
			$shipping_templates = get_option( 'ced_etsy_details', array() );

			$etsyShopGlobalSettings     = isset( $globalSettings[ $etsyStoreId ] ) ? $globalSettings[ $etsyStoreId ] : array();
			$profileData                = array();
			$selected_shipping_template = isset( $shipping_templates[ $etsyStoreId ]['shippingTemplateId'] ) ? $shipping_templates[ $etsyStoreId ]['shippingTemplateId'] : null;

			$profileData['_umb_etsy_category']['default']         = $etsyCategoryId;
			$profileData['_umb_etsy_category']['metakey']         = null;
			$profileData['_ced_etsy_shipping_profile']['default'] = $selected_shipping_template;
			$profileData['_ced_etsy_shipping_profile']['metakey'] = null;

			foreach ( $etsyShopGlobalSettings['product_data'] as $key => $value ) {
				$profileData[ $key ]['default'] = isset( $value['default'] ) ? $value['default'] : '';
				$profileData[ $key ]['metakey'] = isset( $value['metakey'] ) ? $value['metakey'] : '';

			}

			return $profileData;
		}
	}
}
