<?php
namespace Cedcommerce\Product;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Class_Ced_Etsy_Import_Reviews' ) ) {

	class Ced_Etsy_Import_Reviews {


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
	}
}
