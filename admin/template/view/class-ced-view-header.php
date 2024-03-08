<?php
namespace Cedcommerce\Template\View;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


/**
 * The Settings specific class..
 *
 * Ced_View_Settings class is rending fields which are required to show on the settings tab.
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/View/Settings
 */
class Ced_View_Header {
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $shop_name;
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $section;
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $not_show;
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function __construct( $shop_name = '' ) {
		$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		if ( isset( $_GET['section'] ) ) {
			$this->section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		}
		update_option( 'ced_etsy_shop_name', trim( $this->shop_name ) );
		print_r( $this->show_loading_image() );
		print_r( $this->header_wrap_view( $this->section, $this->shop_name ) );
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function show_loading_image() {
		return '<div class="ced_etsy_loader">
		<img src="' . esc_url( CED_ETSY_URL . 'admin/assets/images/loading.gif' ) . '" width="50px" height="50px" class="ced_etsy_loading_img" >
		</div>';
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function header_wrap_view( $curnt_section = '', $curnt_shopname = '' ) {
		$view  = '';
		$view .= '<div class="ced_progress">
		<h3><label for="file">Processing Request</label></h3>
		<h4>Do not press any key or refresh the page until the operation is complete</h4>
		<progress id="ced_progress" value="0" max="100"></progress>
	</div><div class="success-admin-notices is-dismissible"></div>
			<div class="navigation-wrapper">';
			// $view                   .= ced_etsy_cedcommerce_logo();
			$view                   .= '<ul class="navigation">
				';
					$header_sections = $this->header_sections();
					$this->not_show  = array( 'shipping-add', 'shipping-edit', 'profile-edit' );
		foreach ( $header_sections as $section => $name ) {
			if ( in_array( $section, $this->not_show ) ) {
				continue;
			}
				$view .= '<li>
								<a href="' . $this->section_url( $section, $this->shop_name ) . '" class="' . $this->check_active( $this->section, $section ) . '">' . ucfirst( $name ) . '</a>
							</li>';
		}
					$view .= '
				</ul>
				<div class="ced_etsy_document"><span><a href="https://woocommerce.com/document/etsy-integration-for-woocommerce/" target="_blank" class="ced_etsy_document_link" name="" value="">View documentation</a></span></div>
		</div>';
		return $view;
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function check_active( $current_section, $view_sec ) {
		if ( $current_section === $view_sec ) {
			return 'active';
		} else {
			return '';
		}
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function section_url( $section = '', $shop_name = '' ) {
		if ( empty( $section ) || empty( $shop_name ) ) {
			$section   = $this->section;
			$shop_name = $this->shop_name;
		}
		return admin_url( 'admin.php?page=ced_etsy&section=' . $section . '&shop_name=' . $shop_name );
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function header_sections() {
		return array(
			'settings'         => 'Global Settings',
			'category'         => 'Category Mapping',
			'profiles'         => 'Profiles',
			'profile-edit'     => 'Profile Edit',
			'products'         => 'Products',
			'orders'           => 'Orders',
			'product-importer' => 'Importer',
			'timeline'         => 'Timeline',
		);
	}
}

