<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
class Cedhandler {

	// Directory name
	public $dir_name;
	// To get object of this class
	private static $_instance;
	/**
	 * Get instace of the class
	 *
	 * @since 1.0.0
	 */

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Requrie imoprtant template.
	 *
	 * @since 1.0.0
	 */
	public function ced_require( $file_name = '' ) {
		if ( '' != $file_name && '' != $this->dir_name ) {
			if ( file_exists( CED_ETSY_DIRPATH . $this->dir_name . $file_name . '.php' ) ) {
				require_once CED_ETSY_DIRPATH . $this->dir_name . $file_name . '.php';
			}
		}
	}
}
