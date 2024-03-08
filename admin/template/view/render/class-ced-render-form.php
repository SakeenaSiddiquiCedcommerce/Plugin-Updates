<?php
namespace Cedcommerce\Template\View\Render;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The forms for specific admin functionality.
 *
 * @since      2.1.1
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/admin
 */
class Ced_Render_Form {
	/**
	 * *******************
	 * Function construct.
	 * *******************
	 */
	public function __construct() {

	}
	/**
	 * ***********
	 * Form Nonce.
	 * ***********
	 *
	 * @since 2.1.1
	 *
	 * @param string $my_action Noce Action.
	 * @param string $nonce_field Nonce field  value.
	 *
	 * @return Uploaded Ids
	 */
	public function ced_nonce( $my_action, $nonce_field ) {
		return wp_nonce_field( $my_action, $nonce_field );
	}
	/**
	 * **************
	 * Form open tag
	 * **************
	 *
	 * @since 2.1.1
	 *
	 * @param string $method Form method.
	 * @param string $action Form Action.
	 *
	 * @return string
	 */
	public function form_open( $method = '', $action = '' ) {
		return '<form  method="' . $method . '" action "' . $action . '">';
	}
	/**
	 * ************
	 * Form lables.
	 * ************
	 *
	 * @since 2.1.1
	 *
	 * @param Sting $name Form label name.
	 *
	 * @return String.
	 */
	public function form_label( $name = '' ) {
		return '<label for="' . $name . '">' . $name . '</label> : ';
	}
	/**
	 * ******************
	 * Render form input
	 * ******************
	 *
	 * @since 2.1.1
	 *
	 * @param string $type Input text type.
	 * @param string $class Input field class.
	 * @param string $name Input field name.
	 * @param string $placeholder Input field placeholder.
	 *
	 * @return Form input.
	 */
	public function form_input( $type = '', $class = '', $name = '', $placeholder = '' ) {
		return '<input type="' . $type . '" name="' . $name . '" class="' . $class . '" id="' . $id . '" placeholder="' . $placeholder . '"">';
	}
	/**
	 * *************
	 * For textarea
	 * *************
	 *
	 * @since 2.1.1
	 *
	 * @param string $name Form name.
	 * @param string $placeholder Form placeholder.
	 *
	 * @return Textarea String.
	 */
	public function form_textarea( $name = '', $placeholder = '' ) {
			return '<textarea name="' . $name . '" placeholder="' . $placeholder . '"></textarea>';
	}
	/**
	 * *****************************************************
	 * Function for preparing product data to be uploaded.
	 * *****************************************************
	 *
	 * @since  2.1.1
	 *
	 * @param array  $prodIDs Checked Product ids
	 * @param string $shopName Active Shop Name
	 * @param array  $prodIDs Checked Product ids
	 * @param string $shopName Active Shop Name
	 *
	 * @return Uploaded Ids
	 */
	public function button( $id = '', $class = '', $type = '', $name = '', $in_text = '' ) {
		return '<button id="' . $id . '" class="' . $class . '" type="' . $type . '" name="' . $name . '">' . $in_text . '</button>';
	}
	/**
	 * ************
	 * Close forms.
	 * ************
	 *
	 * @since 2.1.1
	 *
	 * @return string form close.
	 */
	public function form_close() {
		return '</form>';
	}
}
