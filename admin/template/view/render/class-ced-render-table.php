<?php
namespace Cedcommerce\Template\View\Render;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The tables for Admin functionality.
 *
 * @since      2.1.1
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/admin
 */

class Ced_Render_Table {
	public function __construct() {

	}
	/**
	 * ************
	 * Open table.
	 * ************
	 *
	 * @since 2.1.1
	 * @param string $class Table #class.
	 *
	 * @return string.
	 */
	public function table_open( $class ) {
		return '<table class="' . $class . '">';
	}
	/**
	 * ************
	 * Open table.
	 * ************
	 *
	 * @since 2.1.1
	 * @param string $name Table #name.
	 *
	 * @return string.
	 */
	public function table_label( $name ) {
		return '<label for="' . $name . '">' . $name . '</label> : ';
	}
	/**
	 * *****************
	 * Open input fiels.
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $type Table input type #name.
	 * @param string $class Table input #class.
	 * @param string $name Table input #name.
	 * @param string $placeholder Table input #palceholder.
	 *
	 * @return string.
	 */
	public function table_input( $type, $class, $name, $placeholder ) {
		return '<input type="' . $type . '" name="' . $name . '" class="' . $class . '" id="' . $id . '" placeholder="' . $placeholder . '"">';
	}
	/**
	 * *****************
	 * Open input fiels.
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $name Table textarea #name.
	 * @param string $placeholder Table textarea #placeholder.
	 *
	 * @return string.
	 */
	public function table_textarea( $name, $placeholder ) {
			return '<textarea name="' . $name . '" placeholder="' . $placeholder . '"></textarea>';
	}
	/**
	 * *****************************************
	 * Table data fields values putting inside.
	 * *****************************************
	 *
	 * @since 2.1.1
	 * @param string $in_params Table data in parameter.
	 *
	 * @return string.
	 */
	public function td( $in_params = '' ) {
		return '<td>' . $in_params . '</td>';
	}
	/**
	 * *****************
	 * Table heading th
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $in_params inside table heading contents.
	 *
	 * @return string.
	 */
	public function th( $in_params = '' ) {
		return '<th>' . $in_params . '</th>';
	}

	/**
	 * *****************
	 * Table heading th
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $in_params inside table heading contents.
	 *
	 * @return string.
	 */
	public function tr( $in_params = '' ) {
		return '<tr>' . $in_params . '</tr>';
	}

	/**
	 * *****************
	 * Table Body <body>
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $in_params inside table body contents.
	 *
	 * @return string.
	 */
	public function table_body( $in_params = '' ) {
		return '<tbody>' . $in_params . '</tbody>';
	}

	/**
	 * *************
	 * Table Button
	 * *************
	 *
	 * @since 2.1.1
	 *
	 * @param string $type Table Button type.
	 * @param string $text Button Text inside.
	 *
	 * @return string Input fields.
	 */
	public function table_button( $type, $text ) {
		return '<input type="' . $type . '" value="' . $text . '">';
	}


	/**
	 * *************
	 * Table Close
	 * *************
	 *
	 * @since 2.1.1
	 *
	 * @return string Table close fields.
	 */
	public function table_close() {
		return '</table>';
	}

	/**
	 * ************
	 * Table label
	 * ************
	 *
	 * @since 2.1.1
	 *
	 * @param string $class Label class.
	 * @param string $in Lable inside content.
	 * @param string $desc Label description.
	 *
	 * @return string table label.
	 */
	public function label( $class = '', $in = '', $desc = '' ) {
		return '<label class="' . $class . '">' . $in . '</label></br><span class="cedcommerce-tip">' . ( $desc ) . '</span>';
	}

	/**
	 * **************************
	 * Table Select and Options
	 * **************************
	 *
	 * @since 2.1.1
	 *
	 * @param string $name select name.
	 * @param array  $option_array Select option arrays.
	 * @param string $selected Selected flag to make it selected.
	 * @param string $class Select class.
	 * @param string $id Select tag id.
	 *
	 * @return string of select.
	 */
	public function select( $name = '', $option_array = '', $prev_val = '', $class = '', $id = '' ) {
		$to_return = '';

		$to_return .= '<select name="' . $name . '">';
		$to_return .= '<option value="">---Select---</option>';
		foreach ( $option_array as $opt_key => $opt_value ) {
			$selected = '';
			if ( $opt_key == $prev_val ) {
				$selected = 'selected';
			}
			$to_return .= '<option value="' . esc_attr( $opt_key ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $opt_value ) . '</option>';
		}
		$to_return .= '</select>';
		return $to_return;
	}

	/**
	 * **************************
	 * Table Select and Options
	 * **************************
	 *
	 * @since 2.1.1
	 *
	 * @param string $name checkbox name.
	 * @param array  $is_checked Flag whether it checked or not.
	 * @param string $class checkbox class.
	 *
	 * @return string of checkbox.
	 */
	public function check_box( $name = '', $is_checked = '', $class = '' ) {
		return '<label class="' . $class . '"><input type="checkbox" name="' . $name . '"  ' . $is_checked . '><span class="slider round"></span></label>';
	}
}
