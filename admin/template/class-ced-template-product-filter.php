<?php
namespace Cedcommerce\Template;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Ced_Template_Product_Filter.
 *
 * @since 1.0.0
 */
class Ced_Template_Product_Filter {

	/**
	 * Function- filter_by_category.
	 * Used to Apply Filter on Product Page
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_filters_on_products( $_products, $shop_name ) {

		if ( ( ! empty( $_POST['status_sorting'] ) && isset( $_POST['status_sorting'] ) ) || ( ! empty( $_POST['pro_cat_sorting'] ) && isset( $_POST['pro_cat_sorting'] ) ) || ( ! empty( $_POST['pro_type_sorting'] ) && isset( $_POST['pro_type_sorting'] ) ) || ( ! empty( $_POST['stock_status'] ) && isset( $_POST['stock_status'] ) ) ) {

			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
				$status_sorting   = isset( $_POST['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_POST['status_sorting'] ) ) : '';
				$pro_cat_sorting  = isset( $_POST['pro_cat_sorting'] ) ? sanitize_text_field( wp_unslash( $_POST['pro_cat_sorting'] ) ) : '';
				$pro_type_sorting = isset( $_POST['pro_type_sorting'] ) ? sanitize_text_field( wp_unslash( $_POST['pro_type_sorting'] ) ) : '';
				$stock_status     = isset( $_POST['stock_status'] ) ? sanitize_text_field( wp_unslash( $_POST['stock_status'] ) ) : '';
				$current_url      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				$shop_name        = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
				wp_redirect( $current_url . '&status_sorting=' . $status_sorting . '&pro_cat_sorting=' . $pro_cat_sorting . '&pro_type_sorting=' . $pro_type_sorting . '&stock_status=' . $stock_status . '&shop_name=' . $shop_name );
		} else {
				$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
			$url           = admin_url( 'admin.php?page=ced_etsy&section=products&shop_name=' . $shop_name );
			wp_redirect( $url );
		}

	}//end ced_etsy_filters_on_products()


	public function productSearch_box( $_products, $valueTobeSearched ) {
		if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {
			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
			$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$shop_name   = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
			$searchdata  = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
			$searchdata  = str_replace( ' ', ',', $searchdata );
			wp_redirect( $current_url . '&s=' . $searchdata/*.'&shop_name='.$_GET['shop_name']*/ );
		} else {
			$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
			$url       = admin_url( 'admin.php?page=ced_etsy&section=products&shop_name=' . $shop_name );
			wp_redirect( $url );
		}
	}
}//end class



