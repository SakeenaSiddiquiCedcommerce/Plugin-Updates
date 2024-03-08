<?php
/**
 * Product Category Class.
 *
 * @version 2.1.1
 * @package category-fetch-for-woocommerce-categories.
 */

namespace Cedcommerce\Product;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Ced_Product_Category' ) ) {
	/**
	 * Get Etsy Category.
	 */
	class Ced_Product_Category {


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
		 * Etsy getting seller taxonomies
		 *
		 * @since    1.0.0
		 */
		public function get_etsy_categories( $shop_name = '' ) {
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$categories = etsy_request()->get( 'application/seller-taxonomy/nodes', $shop_name );
			return $categories;
		}

		/**
		 * Etsy Storing Categories
		 *
		 * @since    1.0.0
		 */
		public function ced_etsy_store_categories( $fetchedCategories, $ajax = '' ) {
			foreach ( $fetchedCategories['results'] as $key => $value ) {
				if ( count( $value['children_ids'] ) > 0 ) {
					$arr1[] = array(
						'id'       => $value['id'],
						'name'     => $value['name'],
						'path'     => $value['path'],
						'children' => count( $value['children_ids'] ),
					);
				} else {
					$arr1[] = array(
						'id'       => $value['id'],
						'name'     => $value['name'],
						'path'     => $value['path'],
						'children' => 0,
					);
				}
				foreach ( $value['children'] as $key1 => $value1 ) {
					if ( count( $value1['children_ids'] ) > 0 ) {
						$arr2[] = array(
							'parent_id' => $value['id'],
							'id'        => $value1['id'],
							'name'      => $value1['name'],
							'path'      => $value1['path'],
							'children'  => count( $value1['children_ids'] ),
						);
					} else {
						$arr2[] = array(
							'parent_id' => $value['id'],
							'id'        => $value1['id'],
							'name'      => $value1['name'],
							'path'      => $value1['path'],
							'children'  => 0,
						);
					}
					foreach ( $value1['children'] as $key2 => $value2 ) {
						if ( count( $value2['children_ids'] ) > 0 ) {
							$arr3[] = array(
								'parent_id' => $value1['id'],
								'id'        => $value2['id'],
								'name'      => $value2['name'],
								'path'      => $value2['path'],
								'children'  => count( $value2['children_ids'] ),
							);
						} else {
							$arr3[] = array(
								'parent_id' => $value1['id'],
								'id'        => $value2['id'],
								'name'      => $value2['name'],
								'path'      => $value2['path'],
								'children'  => 0,
							);
						}
						foreach ( $value2['children'] as $key3 => $value3 ) {
							if ( count( $value3['children_ids'] ) > 0 ) {
								$arr4[] = array(
									'parent_id' => $value2['id'],
									'id'        => $value3['id'],
									'name'      => $value3['name'],
									'path'      => $value3['path'],
									'children'  => count( $value3['children_ids'] ),
								);
							} else {
								$arr4[] = array(
									'parent_id' => $value2['id'],
									'id'        => $value3['id'],
									'name'      => $value3['name'],
									'path'      => $value3['path'],
									'children'  => 0,
								);
							}
							foreach ( $value3['children'] as $key4 => $value4 ) {
								if ( count( $value4['children_ids'] ) > 0 ) {
									$arr5[] = array(
										'parent_id' => $value3['id'],
										'id'        => $value4['id'],
										'name'      => $value4['name'],
										'path'      => $value4['path'],
										'children'  => count( $value4['children_ids'] ),
									);
								} else {
									$arr5[] = array(
										'parent_id' => $value3['id'],
										'id'        => $value4['id'],
										'name'      => $value4['name'],
										'path'      => $value4['path'],
										'children'  => 0,
									);
								}
								foreach ( $value4['children'] as $key5 => $value5 ) {
									if ( count( $value5['children_ids'] ) > 0 ) {
										$arr6[] = array(
											'parent_id' => $value4['id'],
											'id'        => $value5['id'],
											'name'      => $value5['name'],
											'path'      => $value5['path'],
											'children'  => count( $value5['children_ids'] ),
										);
									} else {
										$arr6[] = array(
											'parent_id' => $value4['id'],
											'id'        => $value5['id'],
											'name'      => $value5['name'],
											'path'      => $value5['path'],
											'children'  => 0,
										);
									}
									foreach ( $value5['children'] as $key6 => $value6 ) {
										if ( is_array( $value6['children_ids'] ) && ! empty( $value6['children_ids'] ) ) {

											$arr7[] = array(
												'parent_id' => $value5['id'],
												'id'       => $value6['id'],
												'name'     => $value6['name'],
												'path'     => $value6['path'],
												'children' => count( $value6['children_ids'] ),
											);

										} else {
											$arr7[] = array(
												'parent_id' => $value5['id'],
												'id'       => $value6['id'],
												'name'     => $value6['name'],
												'path'     => $value6['path'],
												'children' => 0,
											);
										}
									}
								}
							}
						}
					}
				}
			}

			$catFirstLevelFile = $folderName . 'category.json';
			file_put_contents( $catFirstLevelFile, json_encode( $fetchedCategories['results'] ) );

			$folderName        = CED_ETSY_DIRPATH . 'admin/etsy/lib/json/';
			$catFirstLevelFile = $folderName . 'categoryLevel-1.json';
			file_put_contents( $catFirstLevelFile, json_encode( $arr1 ) );
			$catSecondLevelFile = $folderName . 'categoryLevel-2.json';
			file_put_contents( $catSecondLevelFile, json_encode( $arr2 ) );

			$catThirdLevelFile = $folderName . 'categoryLevel-3.json';
			file_put_contents( $catThirdLevelFile, json_encode( $arr3 ) );
			$catFourthLevelFile = $folderName . 'categoryLevel-4.json';
			file_put_contents( $catFourthLevelFile, json_encode( $arr4 ) );

			$catFifthLevelFile = $folderName . 'categoryLevel-5.json';
			file_put_contents( $catFifthLevelFile, json_encode( $arr5 ) );
			$catSixthLevelFile = $folderName . 'categoryLevel-6.json';
			file_put_contents( $catSixthLevelFile, json_encode( $arr6 ) );

			$catSeventhLevelFile = $folderName . 'categoryLevel-7.json';
			file_put_contents( $catSeventhLevelFile, json_encode( $arr7 ) );

			update_option( 'ced_etsy_categories_fetched', 'Yes' );
			if ( $ajax ) {
				return 'true';
				die;
			}
		}
	}
}
