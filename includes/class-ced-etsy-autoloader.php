<?php
/**
 * Cedcommerce Autoloader.
 *
 * @package WooCommerce-etsy-inegration\includes
 * @version 2.0.8
 */

defined( 'ABSPATH' ) || exit;

class CedEtsyAutoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_once = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}
		spl_autoload_register( array( $this, 'autoload' ) );
		$this->include_once = CED_ETSY_DIRPATH . 'admin/';

	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class name.
	 * @return string
	 */
	private function ced_etsy_get_file_name_from_class( $class ) {
		if ( strpos( $class, '\\' ) !== false ) {
			$class_name = explode( '\\', $class );
			// Explode namespace and class into array by slash.
			$ced_name = end( $class_name );
			// Create file name with help of class name.
			$s = 'class-' . strtolower( str_replace( '\\', '', $ced_name ) ) . '.php';
			// convert everything in lower case.
			$s = str_replace( '_', '-', $s );
		} else {
			$s = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		}
		return $s;
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path File path.
	 * @return bool Successful or not.
	 */
	private function ced_load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}


	/**
	 * Auto-loading cedcommerce classes for reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, 'Cedcommerce' ) ) {
			return;
		}
		$file           = $this->ced_etsy_get_file_name_from_class( $class );
		$path           = '';
		$paths          = $this->ced_autoload_paths();
			$class_name = explode( '\\', @$class );
		foreach ( $paths as $path_class => $dir_path ) {
			if ( 0 === strpos( strtolower( end( $class_name ) ), @$path_class ) ) {
				$path = $this->include_once . $dir_path;

			}
		}
		if ( empty( $path ) || ! $this->ced_load_file( $path . $file ) ) {
			$this->ced_load_file( $this->include_once . $file );
		}

	}
	/**
	 * Setting up classes with respective path.
	 *
	 * @return array file and class.
	 */
	private function ced_autoload_paths() {
		return array(
			'ced_pro'                             => 'ced-builder/product/',
			'ced_cat'                             => 'ced-builder/product/',
			'ced_ord'                             => 'ced-builder/order/',
			'ced_etsy_m'                          => 'lib/',
			'ced_etsy_req'                        => 'lib/',
			'ced_temp'                            => 'template/',
			'ced_view'                            => 'template/view/',
			'ced_rend'                            => 'template/view/render/',
			'woocommmerce_etsy_integration_admin' => 'ced-builder/product/',
		);
	}


}







