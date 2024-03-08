<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Ced_Etsy_Import_Products' ) ) {

	class Ced_Product_Import {


		public static $_instance;

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

		/**
		 * ****************************************************
		 * IMPORT PRODUCT BY BULK ACTION OR IMPORT LINK (TEXT)
		 * ****************************************************
		 *
		 * @since    2.0.0
		 */
		public function ced_etsy_import_products( $listing_id = '', $shop_name = '' ) {
			if ( empty( $listing_id ) || empty( $shop_name ) ) {
				return;
			}
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$response = etsy_request()->get( "application/listings/{$listing_id}", $shop_name );
			if ( isset( $response['listing_id'] ) ) {
				$this->ced_etsy_get_listing_infos( $response, $shop_name );
			} else {
				$error        = array();
				$error['msg'] = " Product doesn't contain information";
				return $error;
			}
		}

		/**
		 * *************************************
		 * Etsy ced_etsy_get_listing_infos.
		 * *************************************
		 *
		 * @since    2.0.0
		 */
		private function ced_etsy_get_listing_infos( $etsy_product = array(), $shop_name = '' ) {

			$listing_id = isset( $etsy_product['listing_id'] ) ? $etsy_product['listing_id'] : '';
			if ( empty( $listing_id ) ) {
				return;
			}
			$etsy_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
			$by_sku_exists       = get_product_id_by_params( '_ced_etsy_listing_id_' . $shop_name, $listing_id );
			if ( ! empty( $etsy_product_exists ) || $by_sku_exists ) {
				return;
			}
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$action        = "application/listings/{$listing_id}/";
			$inventories   = etsy_request()->get( $action . 'inventory', $shop_name );
			$images        = etsy_request()->get( $action . 'images', $shop_name );
			$image_details = isset( $images['results'] ) ? $images['results'] : array();
			$e_inventories = isset( $inventories['products'] ) ? $inventories['products'] : array();

			if ( ! empty( $e_inventories ) ) {
				foreach ( $e_inventories as $info ) {
					$sku = isset( $info['sku'] ) ? $info['sku'] : '';
					if ( ! empty( $sku ) ) {
						$product_id_by_sku = get_product_id_by_params( '_sku', $sku );
						if ( ! empty( $product_id_by_sku ) ) {
							$_product = wc_get_product( $product_id_by_sku );
							if ( 'variation' == $_product->get_type() ) {
								$product_id_by_sku = $_product->get_parent_id();
							}
							update_post_meta( $product_id_by_sku, '_ced_etsy_state_' . $shop_name, $etsy_product['state'] );
							update_post_meta( $product_id_by_sku, '_ced_etsy_url_' . $shop_name, $etsy_product['url'] );
							update_post_meta( $product_id_by_sku, '_ced_etsy_listing_id_' . $shop_name, $etsy_product['listing_id'] );
							update_post_meta( $product_id_by_sku, '_ced_etsy_listing_data_' . $shop_name, json_encode( $etsy_product ) );
							return;
						}
					}
				}
			}

			if ( isset( $inventories['products'] ) && count( $inventories['products'] ) > 1 ) {
				$this->create_variable_product( $etsy_product, $e_inventories, $image_details, $shop_name );
			} else {
				$this->create_simple_product( $etsy_product, $e_inventories, $image_details, $shop_name );
			}
		}

		/**
		 * *****************************************
		 * CREATING SIMPLE PRODUCT IN WOOCOMMERCE
		 * *****************************************
		 *
		 * @since 2.0.0
		 */
		private function create_simple_product( $product = '', $product_details = '', $image_details = '', $shop_name = '' ) {

			$saved_global_settings_data = get_option( 'ced_etsy_global_settings', '' );
			$ced_etsy_target_lang       = isset( $saved_global_settings_data[ $shop_name ]['ced_etsy_target_lang'] ) ? $saved_global_settings_data[ $shop_name ]['ced_etsy_target_lang'] : '';
			$import_product_status      = isset( $saved_global_settings_data[ $shop_name ]['import_product_status'] ) ? $saved_global_settings_data[ $shop_name ]['import_product_status'] : 'publish';

			$t_title = '';
			$t_desc  = '';
			$t_tags  = array();

			if ( ! empty( $ced_etsy_target_lang ) ) {
				$e_l_id       = $product['listing_id'];
				$shop_id      = get_etsy_shop_id( $shop_name );
				$translations = etsy_request()->get( "application/shops/{$shop_id}/listings/{$e_l_id}/translations/{$ced_etsy_target_lang}", $shop_name );

				$t_title = isset( $translations['title'] ) ? $translations['title'] : '';
				$t_desc  = isset( $translations['description'] ) ? $translations['description'] : '';
				$t_tags  = isset( $translations['tags'] ) ? $translations['tags'] : array();
			}

			$product_id = wp_insert_post(
				array(
					'post_title'   => ! empty( $t_title ) ? $t_title : $product['title'],
					'post_status'  => $import_product_status,
					'post_type'    => 'product',
					'post_content' => ! empty( $t_desc ) ? $t_desc : $product['description'],
				)
			);

			$this->insert_product_tags( $product_id, $product, $t_tags );
			$this->insert_product_category( $product_id, $product, $product_details );
			update_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $product ) );
			$_weight = isset( $product['item_weight'] ) ? $product['item_weight'] : 0;
			$_length = isset( $product['item_length'] ) ? $product['item_length'] : 0;
			$_width  = isset( $product['item_width'] ) ? $product['item_width'] : 0;
			$_height = isset( $product['item_height'] ) ? $product['item_height'] : 0;

			update_post_meta( $product_id, '_weight', $_weight );
			update_post_meta( $product_id, '_length', $_length );
			update_post_meta( $product_id, '_width', $_width );
			update_post_meta( $product_id, '_height', $_height );

			wp_set_object_terms( $product_id, 'simple', 'product_type' );
			update_post_meta( $product_id, '_visibility', 'visible' );
			update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $product['listing_id'] );
			update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $product['url'] );
			update_post_meta( $product_id, 'ced_etsy_product_data', $product );
			update_post_meta( $product_id, 'ced_etsy_product_inventory', $product_details );
			if ( isset( $product['skus'][0] ) ) {
				update_post_meta( $product_id, '_sku', $product['skus'][0] );
			}
			update_post_meta( $product_id, '_stock_status', 'instock' );

			if ( $product['quantity'] > 0 ) {
				update_post_meta( $product_id, '_stock_status', 'instock' );
				update_post_meta( $product_id, '_manage_stock', 'yes' );
				update_post_meta( $product_id, '_stock', $product['quantity'] );
			} else {
				update_post_meta( $product_id, '_stock_status', 'outofstock' );
			}
			update_post_meta( $product_id, '_regular_price', $product['price']['amount'] / $product['price']['divisor'] );
			update_post_meta( $product_id, '_price', $product['price']['amount'] / $product['price']['divisor'] );

			if ( isset( $image_details ) ) {
				$this->create_product_images( $product_id, $image_details );
			}

		}

		/**
		 * *****************************************
		 * CREATING VARIABLE PRODUCT IN WOOCOMMERCE
		 * *****************************************
		 *
		 * @since 2.0.0
		 */

		private function create_variable_product( $product = '', $product_details = '', $image_details = '', $shop_name = '' ) {
			$etsy_variation_products = $product_details;

			$saved_global_settings_data = get_option( 'ced_etsy_global_settings', '' );
			$ced_etsy_target_lang       = isset( $saved_global_settings_data[ $shop_name ]['ced_etsy_target_lang'] ) ? $saved_global_settings_data[ $shop_name ]['ced_etsy_target_lang'] : '';
			$import_product_status      = isset( $saved_global_settings_data[ $shop_name ]['import_product_status'] ) ? $saved_global_settings_data[ $shop_name ]['import_product_status'] : 'publish';

			$t_title = '';
			$t_desc  = '';
			$t_tags  = array();

			if ( ! empty( $ced_etsy_target_lang ) ) {
				$e_l_id       = $product['listing_id'];
				$shop_id      = get_etsy_shop_id( $shop_name );
				$translations = etsy_request()->get( "application/shops/{$shop_id}/listings/{$e_l_id}/translations/{$ced_etsy_target_lang}", $shop_name );

				$t_title = isset( $translations['title'] ) ? $translations['title'] : '';
				$t_desc  = isset( $translations['description'] ) ? $translations['description'] : '';
				$t_tags  = isset( $translations['tags'] ) ? $translations['tags'] : array();
			}

			$product_id = wp_insert_post(
				array(
					'post_title'   => ! empty( $t_title ) ? $t_title : $product['title'],
					'post_status'  => $import_product_status,
					'post_type'    => 'product',
					'post_content' => ! empty( $t_desc ) ? $t_desc : $product['description'],
				)
			);

			$_weight = isset( $product['item_weight'] ) ? $product['item_weight'] : 0;
			$_length = isset( $product['item_length'] ) ? $product['item_length'] : 0;
			$_width  = isset( $product['item_width'] ) ? $product['item_width'] : 0;
			$_height = isset( $product['item_height'] ) ? $product['item_height'] : 0;
			update_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $product ) );
			wp_set_object_terms( $product_id, 'variable', 'product_type' );
			update_post_meta( $product_id, '_visibility', 'visible' );
			update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $product['listing_id'] );
			update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $product['url'] );
			update_post_meta( $product_id, 'ced_etsy_product_data', $product );
			update_post_meta( $product_id, 'ced_etsy_product_inventory', $product_details );
			update_post_meta( $product_id, '_stock_status', 'instock' );
			update_post_meta( $product_id, '_weight', $_weight );
			update_post_meta( $product_id, '_length', $_length );
			update_post_meta( $product_id, '_width', $_width );
			update_post_meta( $product_id, '_height', $_height );

			foreach ( $etsy_variation_products[0]['property_values'] as $key => $value ) {
				$avaliable_variation_attributes[] = $value['property_name'];
			}
			$attr_value = array();
			foreach ( $etsy_variation_products as $key => $value ) {
				foreach ( $value['property_values'] as $key1 => $value1 ) {
					$variations[ $key ]['attributes'][ $value1['property_name'] ] = $value1['values'][0];
					$attr_value[ $value1['property_name'] ][]                     = $value1['values'][0];
				}
				$variations[ $key ]['price']    = $value['offerings'][0]['price']['amount'] / $value['offerings'][0]['price']['divisor'];
				$variations[ $key ]['quantity'] = $value['offerings'][0]['quantity'];
				$variations[ $key ]['sku']      = $value['sku'];
			}

			foreach ( $avaliable_variation_attributes as $key => $value ) {
				$data['attribute_names'][]    = $value;
				$data['attribute_position'][] = $key;
				$values                       = array();
				foreach ( $variations as $key1 => $value1 ) {
					$values[] = $value1['attributes'][ $value ];
				}
				$values                         = array_unique( $values );
				$data['attribute_values'][]     = implode( '|', $values );
				$data['attribute_visibility'][] = 1;
				$data['attribute_variation'][]  = 1;
			}
			if ( isset( $data['attribute_names'], $data['attribute_values'] ) ) {
				$attribute_names         = $data['attribute_names'];
				$attribute_values        = $data['attribute_values'];
				$attribute_visibility    = isset( $data['attribute_visibility'] ) ? $data['attribute_visibility'] : array();
				$attribute_variation     = isset( $data['attribute_variation'] ) ? $data['attribute_variation'] : array();
				$attribute_position      = $data['attribute_position'];
				$attribute_names_max_key = max( array_keys( $attribute_names ) );

				for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
					if ( empty( $attribute_names[ $i ] ) || ! isset( $attribute_values[ $i ] ) ) {
						continue;
					}
					$attribute_id   = 0;
					$attribute_name = wc_clean( $attribute_names[ $i ] );

					if ( 'pa_' === substr( $attribute_name, 0, 3 ) ) {
						$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );
					}

					$options = isset( $attribute_values[ $i ] ) ? $attribute_values[ $i ] : '';

					if ( is_array( $options ) ) {
						$options = wp_parse_id_list( $options );
					} else {
						$options = 0 < $attribute_id ? wc_sanitize_textarea( wc_sanitize_term_text_based( $options ) ) : wc_sanitize_textarea( $options );
						$options = wc_get_text_attributes( $options );
					}

					if ( empty( $options ) ) {
						continue;
					}

					$attribute = new WC_Product_Attribute();
					$attribute->set_id( $attribute_id );
					$attribute->set_name( $attribute_name );
					$attribute->set_options( $options );
					$attribute->set_position( $attribute_position[ $i ] );
					$attribute->set_visible( isset( $attribute_visibility[ $i ] ) );
					$attribute->set_variation( isset( $attribute_variation[ $i ] ) );
					$attributes[] = $attribute;
				}
			}

			$product_type = 'variable';
			$classname    = WC_Product_Factory::get_product_classname( $product_id, $product_type );
			$_product     = new $classname( $product_id );
			$_product->set_attributes( $attributes );
			$_product->save();

			$this->insert_product_category( $product_id, $product, $product_details );
			$this->insert_product_tags( $product_id, $product, $t_tags );
			$this->insert_product_variations( $product_id, $variations, $avaliable_variation_attributes );

			if ( isset( $image_details ) ) {
				$this->create_product_images( $product_id, $image_details );
			}
		}


		/**
		 * *********************************************
		 * INSERTING PRODUCT CATEGORY IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */
		private function insert_product_category( $product_id = '', $listing_details = '', $inventory_details = '' ) {
			$taxonomy_id = $listing_details['taxonomy_id'];
			$all_cats    = file_get_contents( ( CED_ETSY_DIRPATH . 'admin/lib/json/categories.json' ) );

			if ( ! empty( $all_cats ) ) {
				$all_cats = json_decode( $all_cats, 1 );

			}
			if ( isset( $all_cats[ $taxonomy_id ] ) ) {
				$categoryPath = array( $all_cats[ $taxonomy_id ] );
			} else {
				$categoryPath = array();
			}
			$term_ids  = array();
			$parent_id = '';
			foreach ( $categoryPath as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				$term = wp_insert_term(
					$value,
					'product_cat',
					array(
						'description' => $value,
						'parent'      => $parent_id,
					)
				);
				if ( isset( $term->error_data['term_exists'] ) ) {

					$term_id = $term->error_data['term_exists'];
				} elseif ( isset( $term['term_id'] ) ) {
					$term_id = $term['term_id'];
				}
				$term_ids[] = $term_id;

				$parent_id = ! empty( $term_id ) ? $term_id : '';
			}
			wp_set_object_terms( $product_id, array_unique( $term_ids ), 'product_cat' );

		}

		/**
		 * *********************************************
		 * INSERTING PRODUCT TAG IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */
		private function insert_product_tags( $product_id = '', $listing_details = '', $t_tags = array() ) {
			if ( ! empty( $t_tags ) ) {
				$tagsPath = $t_tags;
			} elseif ( isset( $listing_details['tags'][0] ) ) {
				$tagsPath = $listing_details['tags'];
			} else {
				$tagsPath = array( $listing_details['tags'] );
			}
			$term_ids = array();
			foreach ( $tagsPath as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				$term = wp_insert_term(
					$value,
					'product_tag',
					array(
						'description' => $value,
					)
				);
				if ( isset( $term->error_data['term_exists'] ) ) {
					$term_ids[] = $term->error_data['term_exists'];
				} elseif ( isset( $term['term_id'] ) ) {
					$term_ids[] = $term['term_id'];
				}
			}
			wp_set_object_terms( $product_id, array_unique( $term_ids ), 'product_tag' );
		}

		/**
		 * *********************************************
		 * INSERTING PRODUCT VARIATIONS IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */

		private function insert_product_variations( $post_id, $variations, $available_attributes ) {
			$parent_qty = 0;
			foreach ( $variations as $index => $variation ) {
				$variation_post = array(
					'post_title'  => 'Variation #' . $index . ' of ' . count( $variations ) . ' for product#' . $post_id,
					'post_name'   => 'product-' . $post_id . '-variation-' . $index,
					'post_status' => 'publish',
					'post_parent' => $post_id,
					'post_type'   => 'product_variation',
					'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index,
				);

				$variation_post_id = wp_insert_post( $variation_post );

				foreach ( $available_attributes as $key => $value ) {
					$values = array();
					foreach ( $variations as $key1 => $value1 ) {
						$values[] = $value1['attributes'][ $value ];
					}
					$values          = array_unique( $values );
					$array[ $value ] = array_values( $values );
					$newvalues       = array();
					foreach ( $values as $key => $mwvalue ) {
						$newvalues[] = $mwvalue;
					}
					wp_set_object_terms( $variation_post_id, $newvalues, $value );
				}

				foreach ( $variation['attributes'] as $attribute => $value ) {
					$attr = strtolower( $attribute );
					$attr = str_replace( ' ', '-', $attr );
					$attr = sanitize_title( $attr );

					update_post_meta( $variation_post_id, 'attribute_' . $attr, $value );

					$thedata = array(
						$attr => array(
							'name'         => $value,
							'value'        => '',
							'is_visible'   => '1',
							'is_variation' => '1',
							'is_taxonomy'  => '1',
						),
					);

					update_post_meta( $variation_post_id, '_product_attributes', $thedata );

				}
				if ( $variation['quantity'] > 0 ) {
					$parent_qty = $parent_qty + (int) $variation['quantity'];
					update_post_meta( $variation_post_id, '_stock_status', 'instock' );
					update_post_meta( $variation_post_id, '_stock', $variation['quantity'] );
					update_post_meta( $variation_post_id, '_manage_stock', 'yes' );
					update_post_meta( $post_id, '_stock_status', 'instock' );
				} else {
					update_post_meta( $variation_post_id, '_stock_status', 'outofstock' );
				}

				update_post_meta( $variation_post_id, '_price', str_replace( ',', '', $variation['price'] ) );
				update_post_meta( $variation_post_id, '_regular_price', str_replace( ',', '', $variation['price'] ) );
				update_post_meta( $variation_post_id, '_sku', str_replace( ',', '', $variation['sku'] ) );

			}

			if ( $parent_qty > 0 ) {
				update_post_meta( $post_id, '_stock', (int) $parent_qty );
			}
		}

		/**
		 * *********************************************
		 * INSERTING PRODUCT IMAGES IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */

		private function create_product_images( $product_id, $images = array() ) {
			foreach ( $images as $key1 => $value1 ) {
				$image_url  = $value1['url_fullxfull'];
				$image_name = explode( '/', $image_url );
				$image_name = $image_name[ count( $image_name ) - 1 ];

				$upload_dir       = wp_upload_dir();
				$image_url        = str_replace( 'https', 'http', $image_url );
				$image_data       = file_get_contents( $image_url );
				$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
				$filename         = basename( $unique_file_name );
				if ( wp_mkdir_p( $upload_dir['path'] ) ) {
					$file = $upload_dir['path'] . '/' . $filename;
				} else {
					$file = $upload_dir['basedir'] . '/' . $filename;
				}
				file_put_contents( $file, $image_data );
				$wp_filetype = wp_check_filetype( $filename, null );
				$attachment  = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);
				$attach_id   = wp_insert_attachment( $attachment, $file, $product_id );
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				if ( 0 == $key1 ) {
					set_post_thumbnail( $product_id, $attach_id );
				} else {
					$image_ids[] = $attach_id;
				}
			}

			// PRODUCT GALLERY IMAGES
			if ( ! empty( $image_ids ) ) {
				update_post_meta( $product_id, '_product_image_gallery', implode( ',', $image_ids ) );

			}
		}
	}
}
