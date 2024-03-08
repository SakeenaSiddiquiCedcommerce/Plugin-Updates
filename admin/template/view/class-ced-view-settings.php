<?php
$header = new \Cedcommerce\Template\View\Ced_View_Header();
/**
 * Class Ced View Settings.
 *
 * @package Settings view
 * Class Ced View Settings is under the Cedcommerce\View\Settings.
 */

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
class Ced_View_Settings {
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $shop_name;
	/**
	 * Previously saved values in DB.
	 *
	 * @since    1.0.0
	 * @var      string    $pre_saved_values    The PresavedValues is pre-saved values in DB.
	 */
	private $pre_saved_values;
	/**
	 * Cron jobs option want to increase.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $schedulers = array( 'ced_etsy_auto_import_schedule_job_', 'ced_etsy_inventory_scheduler_job_', 'ced_etsy_order_scheduler_job_' );
	/**
	 * Setting tabs.
	 *
	 * @since    1.0.0
	 * @var      string    $tabs    The ID of this plugin.
	 */
	private $tabs = array(
		'product_import_settings' => 'Product Import Settings',
		'order_imoprt_settings'   => 'Order Import Settings',
		'scheduler_setting_view'  => 'Schedulers/Crons',
	);

	/**
	 * Instializing all the required variations and functions.
	 *
	 * @since    2.1.3
	 *    string    $shop_name    The Etsy shop name.
	 */
	public function __construct( $shop_name = '' ) {
		/**
		 * Show header on the top of tabs.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		?>
		<div class="ced_etsy_heading ">
			<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
			<div class="ced_etsy_child_element default_modal">
				<?php
				$shop_name    = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
				$instructions = array(

					'In this section all the configuration related to product and order sync are provided.',
					'The <a>Search product custom fields and attributes</a> section will help you to choose the required metakey or attribute on which the product information is stored.These metakeys or attributes will furthur be used in <a>Product export settings</a> for listing products on etsy from WooCommerce.',
					'For selecting the required metakey or attribute expand the <a>Search product custom fields and attributes</a> section enter the product name/keywords and list will be displayed under that . Select the metakey or attribute as per requirement and save settings.',
					'Configure the order related settings in <a>Order import settings</a>.',
					'To automate the process related to inventory , order and import product sync , enable the features as per requirement in <a>Schedulers/Crons</a>.',
				);

				echo '<ul class="ced_etsy_instruction_list" type="disc">';
				foreach ( $instructions as $instruction ) {
					print_r( "<li> $instruction</li>" );
				}
				echo '</ul>';

				?>
			</div>
		</div>
		<?php
		$this->shop_name = $shop_name;
		if ( empty( $this->shop_name ) ) {
			$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		}
		if ( $this->shop_name ) {
			$this->pre_saved_values = get_option( 'ced_etsy_global_settings', array() );
			$this->pre_saved_values = isset( $this->pre_saved_values[ $this->shop_name ] ) ? $this->pre_saved_values[ $this->shop_name ] : array();
		}
		/**
		 * Get submit form here.
		 */

		if ( isset( $_POST['global_settings'] ) ) {
			if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
				return;
			}
			/**
			 * Save Settings in DB.
			 *
			 * @since    2.1.3
			 */
			$this->ced_etsy_save_settings();
		}
	}

	/**
	 * Schedule events for automate the scheduling of import and export.
	 *
	 * @since    2.1.3
	 * @var      string    $scheduler_name    The Scheduler hook name .
	 * @var      string    $times_stamp    The given times stamp.
	 */
	public function ced_schedule_events( $scheduler_name = '', $times_stamp = '' ) {
		wp_schedule_event( time(), $times_stamp, $scheduler_name . $this->shop_name );
		update_option( $scheduler_name . $this->shop_name, $this->shop_name );
	}

	/**
	 * Clear Schedule events for automate the scheduling of import and export.
	 *
	 * @since    2.1.3
	 * @var      string    $hook_name    The Scheduler hook name.
	 */
	public function ced_clear_scheduled_hook( $hook_name = '' ) {
		wp_clear_scheduled_hook( $hook_name . $this->shop_name );
	}

	/**
	 * Save setting values in Db.
	 *
	 * @since    2.1.3
	 */
	public function ced_etsy_save_settings() {

		$sanitized_array = ced_filter_input();

		if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
			return;
		}

		$ced_etsy_global_settings = isset( $sanitized_array['ced_etsy_global_settings'] ) ? $sanitized_array['ced_etsy_global_settings'] : array();
		if ( isset( $sanitized_array['ced_etsy_global_settings'] ) ) {
			foreach ( $sanitized_array['ced_etsy_global_settings'] as $scheduler => $scheduler_value ) {
				// Un-schedule the events.
				$this->ced_clear_scheduled_hook( $scheduler );
				// scheduling evens.
				if ( in_array( $scheduler, $this->schedulers ) ) {
					if ( isset( $this->schedulers[ $scheduler ] ) && 'on' === $this->schedulers[ $scheduler ] ) {
						$this->ced_schedule_events( $scheduler, 'ced_etsy_15min' );
					}
				}
			}
		}

		wp_clear_scheduled_hook( 'ced_etsy_inventory_scheduler_job_' . $this->shop_name );
		wp_clear_scheduled_hook( 'ced_etsy_auto_import_schedule_job_' . $this->shop_name );
		wp_clear_scheduled_hook( 'ced_etsy_order_scheduler_job_' . $this->shop_name );

		$auto_import_schedule = isset( $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_import_product'] ) ? $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_import_product'] : '';
		$inventory_schedule   = isset( $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_update_inventory'] ) ? $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_update_inventory'] : '';
		$order_schedule       = isset( $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_fetch_orders'] ) ? $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_fetch_orders'] : '';

		if ( ! empty( $auto_import_schedule ) ) {
			wp_schedule_event( time(), 'ced_etsy_30min', 'ced_etsy_auto_import_schedule_job_' . $this->shop_name );
			update_option( 'ced_etsy_auto_import_schedule_job_' . $this->shop_name, $this->shop_name );
		}
		if ( ! empty( $inventory_schedule ) ) {
			wp_schedule_event( time(), 'ced_etsy_10min', 'ced_etsy_inventory_scheduler_job_' . $this->shop_name );
			update_option( 'ced_etsy_inventory_scheduler_job_' . $this->shop_name, $this->shop_name );
		}

		if ( ! empty( $order_schedule ) ) {
			wp_schedule_event( time(), 'ced_etsy_15min', 'ced_etsy_order_scheduler_job_' . $this->shop_name );
			update_option( 'ced_etsy_order_scheduler_job_' . $this->shop_name, $this->shop_name );
		}

		$marketplace_name           = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( wp_unslash( $_POST['marketplaceName'] ) ) : 'etsy';
		$offer_settings_information = array();
		$array_to_save              = array();
		if ( isset( $sanitized_array['ced_etsy_required_common'] ) ) {
			foreach ( ( $sanitized_array['ced_etsy_required_common'] ) as $key ) {
				isset( $sanitized_array[ $key ][0] ) ? $array_to_save['default'] = $sanitized_array[ $key ][0] : $array_to_save['default'] = '';

				if ( '_umb_' . $marketplace_name . '_subcategory' == $key ) {
					isset( $sanitized_array[ $key ] ) ? $array_to_save['default'] = $sanitized_array[ $key ] : $array_to_save['default'] = '';
				}

				isset( $sanitized_array[ $key . '_attribute_meta' ] ) ? $array_to_save['metakey'] = $sanitized_array[ $key . '_attribute_meta' ] : $array_to_save['metakey'] = 'null';
				$offer_settings_information['product_data'][ $key ]                               = $array_to_save;
			}
		}
		/**
		 * Getting older settings values merging with new settings values.
		 *
		 * @since    2.0.8
		 */
		$sanitized_array['ced_etsy_settings_category']['required'] = 'on';
		$settings                     = get_option( 'ced_etsy_global_settings', array() );
		$settings[ $this->shop_name ] = array_merge( $ced_etsy_global_settings, $offer_settings_information );
		update_option( 'ced_etsy_settings_category', $sanitized_array['ced_etsy_settings_category'] );
		update_option( 'ced_etsy_global_settings', $settings );
		wp_redirect( admin_url( 'admin.php?page=ced_etsy&section=settings&shop_name=' . $this->shop_name ) );
		exit;

	}

	/**
	 * Showing setting values in form.
	 *
	 * @since    2.0.8
	 */
	public function settings_view( $shop_name = '' ) {
		$ced_h           = new Cedhandler();
		$ced_h->dir_name = '/admin/template/view/';
		$ced_h->ced_require( 'ced-etsy-metakeys-template' );
		// Rending forms.
		$form = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
		print_r( $form->form_open( 'POST', '' ) );
		wp_nonce_field( 'global_settings', 'global_settings_submit' );
		$this->product_export_setting();
		foreach ( $this->tabs as $tab_key => $tab_name ) {
			$this->ced_etsy_show_setting_tabs( $tab_name, $tab_key );
		}
		print_r( '<div class="left ced-button-wrapper" >' . $form->button( 'glb_stg_btn', 'button-primary', 'submit', 'global_settings', 'Save Settings' ) . '</div>' );
		print_r( $form->form_close() );
	}
	/**
	 * Show settings tabs using array.
	 *
	 * @since    2.1.3
	 */
	private function ced_etsy_show_setting_tabs( $tab_name = '', $tab_key = '' ) {
		?>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html( $tab_name ) ); ?>
			<div class="ced_etsy_child_element">
				<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
				<?php
				$fields = $this->ced_etsy_all_settings_fields();
				$fields = isset( $fields[ $tab_key ] ) ? $fields[ $tab_key ] : array();
				print_r( $this->ced_etsy_render_table( $fields ) );
				?>
								
			</div>
		</div>
		<?php
	}
	/**
	 * Reder Table into forms.
	 *
	 * @since    2.0.8
	 */
	private function ced_etsy_render_table( $table_array = array() ) {
		$ced_h        = new Cedhandler();
		$stored_value = isset( $this->pre_saved_values[ $this->shop_name ] ) ? $this->pre_saved_values[ $this->shop_name ] : $this->pre_saved_values;
		$table        = new \Cedcommerce\Template\View\Render\Ced_Render_Table();
		print_r( $table->table_open( 'wp-list-table fixed widefat ced_etsy_schedule_wrap' ) );
		$table_array = isset( $table_array ) ? $table_array : array();
		$prep_tr     = '';
		$table_tds   = '';
		foreach ( $table_array as $table_values ) {
			$is_value   = isset( $stored_value[ $table_values['name'] ] ) ? $stored_value[ $table_values['name'] ] : '';
			$table_ids  = '';
			$is_checked = '';
			$table_tds .= '<tr>';
			if ( 'on' === $is_value ) {
				$is_checked = 'checked';
			}
			$table_ids .= $table->label( '', $table_values['label'], $table_values['tooltip'] );
			$table_tds .= $table->th( $table_ids );
			if ( 'select' === $table_values['type'] ) {
				$table_tds .= $table->td( $table->select( 'ced_etsy_global_settings[' . $table_values['name'] . ']', $table_values['options'], $is_value ) );
			}
			if ( 'check' === $table_values['type'] ) {
				$table_tds .= $table->td( $table->label( 'switch', $table->check_box( 'ced_etsy_global_settings[' . $table_values['name'] . ']', $is_checked ) ) );
			}
			$table_tds .= '</tr>';
		}
		print_r( $table->table_body( $table_tds ) );
		print_r( $table->table_close() );
	}

	/**
	 * All the Required settings tabs ans sub-tabs.
	 *
	 * @since    2.0.8
	 */
	public function ced_etsy_all_settings_fields() {
		return array(
			'product_import_settings' => array(
				array(
					'label'   => __( 'Import translated info of the listing.', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Select the target language in which you want to import the product\'s title , description and tags in WooCommerce. Default would be english.',
					'type'    => 'select',
					'name'    => 'ced_etsy_target_lang',
					'options' => array(
						'de' => 'German',
						'en' => 'English',
						'es' => 'Spanish',
						'fr' => 'French',
						'it' => 'Italian',
						'ja' => 'Japanese',
						'nl' => 'Dutch',
						'pl' => 'Polish',
						'pt' => 'Brazilian Portuguese',
					),
					'default' => 'en',
				),
				array(
					'label'   => __( 'WooCommerce default product status', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Choose the product status in which you want to import etsy products . Default is published.',
					'type'    => 'select',
					'name'    => 'import_product_status',
					'options' => get_post_statuses(),
					'default' => '15',
				),
			),
			'order_imoprt_settings'   => array(
				array(
					'label'   => __( 'WooCommerce default order status', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Choose the order status in which you want to import etsy orders . Default is processing.',
					'type'    => 'select',
					'name'    => 'default_order_status',
					'options' => wc_get_order_statuses(),
				),
				array(
					'label'   => __( 'Fetch number of orders', 'etsy-woocommerce-integration' ),
					'tooltip' => 'No. of orders to fetch from etsy. Default is 15 orders . Orders with status paid and not shipped are pulled .',
					'type'    => 'select',
					'name'    => 'order_limit',
					'options' => array(
						10 => '10',
						15 => '15',
						20 => '20',
						25 => '25',
						50 => '50',
					),
					'default' => '15',
				),
				array(
					'label'   => __( 'Use etsy order number', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Use etsy order number when creating etsy orders in WooCommerce.',
					'type'    => 'check',
					'name'    => 'use_etsy_order_no',
					'options' => '',
				),
				array(
					'label'   => __( 'Auto update tracking', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Auto update tracking information on etsy if using <a href="https://woocommerce.com/products/shipment-tracking" target="_blank">Shipment Tracking</a> plugin.',
					'type'    => 'check',
					'name'    => 'update_tracking',
					'options' => '',
				),
				array(
					'label'   => __( 'Create etsy users as customers', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Enable this if you want to import etsy users as customers in WooCommerce.',
					'type'    => 'check',
					'name'    => 'create_customer',
					'options' => '',
				),
				array(
					'label'   => __( 'Update stock without creating orders in WooCommerce', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Enable this if you want to update the stock levels without creating orders in WooCommerce .',
					'type'    => 'check',
					'name'    => 'update_stock_with_no_order',
					'options' => '',
				),

			),
			'scheduler_setting_view'  => array(
				array(
					'label'   => __( 'Fetch etsy orders', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Auto fetch etsy orders and create in WooCommerce.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_fetch_orders',
					'options' => '',
				),

				array(
					'label'   => __( 'Update inventory to etsy', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Auto update price and stock from WooCommerce to etsy.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_update_inventory',
					'options' => '',
				),
				array(
					'label'   => __( 'Upload products to etsy', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Auto upload products from WooCommerce to etsy.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_upload_product',
					'options' => '',
				),
				array(
					'label'   => __( 'Import products from etsy', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Auto import the active listings from etsy to WooCommerce.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_import_product',
					'options' => '',
				),
			),
		);
	}

	/**
	 * Product export setting view.
	 *
	 * @since    2.0.8
	 */
	public function product_export_setting() {
		?>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html( 'Product Export Settings' ) ); ?>
			<div class="ced_etsy_child_element default_modal">
				<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
				
					
						<?php
						/**
						 * -------------------------------------
						 *  INCLUDING PRODUCT FIELDS ARRAY FILE
						 * -------------------------------------
						 */
						$this->shop_name        = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
						$product_field_instance = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
						$settings               = $product_field_instance->get_custom_products_fields();
						$requiredInAnyCase      = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
						$marketPlace            = 'ced_etsy_required_common';
						$productID              = 0;
						$categoryID             = '';
						$indexToUse             = 0;
						$attributes             = wc_get_attribute_taxonomies();
						$attr_options           = array();
						$added_meta_keys        = get_option( 'ced_etsy_selected_metakeys', array() );
						$added_meta_keys        = array_merge( $added_meta_keys, array( '_woocommerce_title', '_woocommerce_short_description', '_woocommerce_description' ) );
						$select_dropdown_html   = '';

						if ( $added_meta_keys && count( $added_meta_keys ) > 0 ) {
							foreach ( $added_meta_keys as $meta_key ) {
								$attr_options[ $meta_key ] = $meta_key;
							}
						}
						if ( ! empty( $attributes ) ) {
							foreach ( $attributes as $attributes_object ) {
								$attr_options[ 'umb_pattr_' . $attributes_object->attribute_name ] = $attributes_object->attribute_label;
							}
						}

						if ( ! empty( $settings ) ) {
							$ced_etsy_settings_category             = get_option( 'ced_etsy_settings_category', array() );
							$ced_etsy_settings_category['required'] = 'on';
							echo '<thead>';
							echo '<tr><td>';
							$settings_category = array_keys( $settings );
							echo '<p><b><i><u>Attributes to display</u></i> </b></p>';
							foreach ( $settings_category as $value ) {
								$checked  = '';
								$disabled = '';
								if ( isset( $ced_etsy_settings_category[ $value ] ) && 'on' == $ced_etsy_settings_category[ $value ] ) {
									$checked = 'checked';
								}
								if ( 'required' == $value ) {
									$disabled = 'disabled';
								}

								echo "<span class='setting_label'><span>" . esc_attr( strtoupper( $value ) ) . '</span>';
								echo "<span><label class='switch'><label class=''><input type='checkbox' class='ced_etsy_setting_sections' data-target='" . esc_attr( $value ) . "' name='ced_etsy_settings_category[" . esc_attr( $value ) . "]' " . esc_attr( $checked ) . ' ' . esc_attr( $disabled ) . '><span class="slider round"></span></label></label></span></span>';
							}
							echo '</td></tr>';
							echo '</thead>';
							$product_specific_attribute_key = get_option( 'ced_etsy_product_specific_attribute_key', array() );
							foreach ( $settings as $section => $product_fields ) {
								$style = '';
								if ( ! isset( $ced_etsy_settings_category[ $section ] ) ) {
									$style = 'display:none;';
								}
								echo "<table class='wp-list-table ced_etsy_global_settings' style='" . esc_attr( $style ) . "' id='" . esc_attr( $section ) . "' class='ced_etsy_setting_body'>";
								echo "<thead><tr class='ced_etsy_settings_label " . esc_attr( $section ) . "'>";

								echo '</tr></thead>';
								echo '<tbody>';
								?>
						<tr>
							<td class="setting_label <?php echo esc_attr( $section ); ?>"><b><?php echo esc_attr( ucwords( $section ) ); ?> Attributes</b></td>
							<td><b>Default Value</b></td>
								<?php
								if ( 'required' == $section ) {
									echo '<td></td>';
								} else {
									echo '<td><b>Pick Value From Custom field or Attribute</b></td>';
								}
								?>
							
						</tr>
								<?php
								foreach ( $product_fields as $field_data ) {

									$is_text = false;
									echo '<tr>';
									// Don't show category specifiction option
									if ( '_umb_etsy_category' == $field_data['id'] ) {
										continue;
									}

									$check    = false;
									$field_id = isset( $field_data['id'] ) ? $field_data['id'] : '';
									if ( empty( $product_specific_attribute_key ) ) {
										$product_specific_attribute_key = array( $field_id );
									} else {
										foreach ( $product_specific_attribute_key as $key => $product_key ) {
											if ( $product_key == $field_id ) {
												$check = true;
												break;
											}
										}
										if ( false == $check ) {
											$product_specific_attribute_key[] = $field_id;
										}
									}

									$ced_etsy_global_data = get_option( 'ced_etsy_global_settings', array() );
									if ( ! empty( $ced_etsy_global_data ) ) {
										$data = isset( $ced_etsy_global_data[ $this->shop_name ]['product_data'] ) ? $ced_etsy_global_data[ $this->shop_name ]['product_data'] : array();
									}
									update_option( 'ced_etsy_product_specific_attribute_key', $product_specific_attribute_key );
									echo '<tr class="form-field _umb_id_type_field ">';
									$label        = isset( $field_data['fields']['label'] ) ? $field_data['fields']['label'] : '';
									$field_id     = trim( $field_id, '_' );
									$category_id  = '';
									$product_id   = '';
									$market_place = 'ced_etsy_required_common';
									$description  = isset( $field_data['fields']['description'] ) ? $field_data['fields']['description'] : '';
									$required     = isset( $field_data['fields']['is_required'] ) ? (bool) $field_data['fields']['is_required'] : '';
									$index_to_use = 0;
									$default      = isset( $data[ $field_data['fields']['id'] ]['default'] ) ? $data[ $field_data['fields']['id'] ]['default'] : $field_data['fields']['default'];
									$field_value  = array(
										'case'  => 'profile',
										'value' => $default,
									);

									if ( '_text_input' == $field_data['type'] ) {
										$is_text = true;
										$product_field_instance->renderInputTextHTML( $field_id, $label, $category_id, $product_id, $market_place, $description, $index_to_use, $field_value, $required );

									} elseif ( '_select' == $field_data['type'] ) {
										$value_for_dropdown = $field_data['fields']['options'];
										$product_field_instance->renderDropdownHTML( $field_id, $label, $value_for_dropdown, $category_id, $product_id, $market_place, $description, $index_to_use, $field_value, $required );
									}
									echo '<td>';
									if ( $is_text ) {
										$previous_selected_value = 'null';
										if ( isset( $data[ $field_data['fields']['id'] ]['metakey'] ) && 'null' != $data[ $field_data['fields']['id'] ]['metakey'] ) {
											$previous_selected_value = $data[ $field_data['fields']['id'] ]['metakey'];
										}
										$select_id = $field_data['fields']['id'] . '_attribute_meta';
										?>
							<select id="<?php echo esc_attr( $select_id ); ?>" name="<?php echo esc_attr( $select_id ); ?>">
								<option value="null" selected> -- select -- </option>
										<?php
										if ( is_array( $attr_options ) ) {
											foreach ( $attr_options as $attr_key => $attr_name ) :
												if ( trim( $previous_selected_value ) == $attr_key ) {
													$selected = 'selected';
												} else {
													$selected = '';
												}
												?>
										<option value="<?php echo esc_attr( $attr_key ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
												<?php
											endforeach;
										}
										?>
							</select>
										<?php
									}
									echo '</td>';
									echo '</tr>';

								}
								echo '</tbody>';
								echo '</table>';
							}
						}
						?>
				
		</div>
	</div>
		<?php
	}
}

$global_setting = new Ced_View_Settings();
$global_setting->settings_view();
