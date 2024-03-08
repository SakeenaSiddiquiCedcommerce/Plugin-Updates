<?php
use Cedcommerce\EtsyManager\Ced_Etsy_Manager as EtsyManager;
use Cedcommerce\Product\Ced_Product_Category as EtsyCategory;
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/admin
 */
class Woocommmerce_Etsy_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->etsy_manager = EtsyManager::get_instance();
		require_once CED_ETSY_DIRPATH . 'admin/ced-builder/order/class-ced-order-get.php';
		$this->ced_etsy_order   = new Ced_Order_Get();
		$this->ced_etsy_product = $this->etsy_manager->{'etsy_product_upload'};
		require_once CED_ETSY_DIRPATH . 'admin/ced-builder/product/class-ced-product-import.php';
		$this->import_product = Ced_Product_Import::get_instance();
		$this->etsy_cat_obj   = EtsyCategory::get_instance();
		$this->plugin_name    = $plugin_name;
		require_once CED_ETSY_DIRPATH . 'admin/lib/class-ced-etsy-activities.php';
		$activity            = new Etsy_Activities();
		$GLOBALS['activity'] = $activity;

		add_action( 'manage_edit-shop_order_columns', array( $this, 'ced_etsy_add_table_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'ced_etsy_manage_table_columns' ), 10, 2 );
		add_action( 'wp_ajax_ced_etsy_update_inventory', array( $this, 'ced_etsy_inventory_schedule_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_update_inventory', array( $this, 'ced_etsy_inventory_schedule_manager' ) );

		add_action( 'wp_ajax_ced_etsy_order_schedule_manager', array( $this, 'ced_etsy_order_schedule_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_order_schedule_manager', array( $this, 'ced_etsy_order_schedule_manager' ) );

		add_action( 'wp_ajax_ced_etsy_sync_existing_products', array( $this, 'ced_etsy_sync_existing_products' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_sync_existing_products', array( $this, 'ced_etsy_sync_existing_products' ) );

		add_action( 'wp_ajax_ced_etsy_auto_upload_products', array( $this, 'ced_etsy_auto_upload_products' ) );
		add_action( 'wp_ajax_nopriv_cced_etsy_auto_upload_products', array( $this, 'ced_etsy_auto_upload_products' ) );

		add_action( 'wp_ajax_ced_etsy_load_more_logs', array( $this, 'ced_etsy_load_more_logs' ) );
	}

	public function ced_etsy_load_more_logs() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$parent          = isset( $sanitized_array['parent'] ) ? $sanitized_array['parent'] : '';
			$offset          = isset( $sanitized_array['offset'] ) ? (int) $sanitized_array['offset'] : '';
			$total           = isset( $sanitized_array['total'] ) ? (int) $sanitized_array['total'] : '';

			$log_info = get_option( $parent, '' );
			if ( empty( $log_info ) ) {
				$log_info = array();
			} else {
				$log_info = json_decode( $log_info, true );
			}
			$log_info   = array_slice( $log_info, (int) $offset, 50 );
			$is_disable = 'no';
			$html       = '';
			if ( ! empty( $log_info ) ) {
				$offset += count( $log_info );
				foreach ( $log_info as $key => $info ) {

					$html .= "<tr class='ced_etsy_log_rows'>";
					$html .= "<td><span class='log_item_label log_details'><a>" . ( $info['post_title'] ) . "</a></span><span class='log_message' style='display:none;'><h3>Input payload for " . ( $info['post_title'] ) . '</h3><button id="ced_close_log_message">Close</button><pre>' . ( ! empty( $info['input_payload'] ) ? json_encode( $info['input_payload'], JSON_PRETTY_PRINT ) : '' ) . '</pre></span></td>';
					$html .= "<td><span class=''>" . $info['action'] . '</span></td>';
					$html .= "<td><span class=''>" . $info['time'] . '</span></td>';
					$html .= "<td><span class=''>" . ( $info['is_auto'] ? 'Automatic' : 'Manual' ) . '</span></td>';
					$html .= '<td>';
					if ( isset( $info['response']['response']['results'] ) || isset( $info['response']['results'] ) || isset( $info['response']['listing_id'] ) || isset( $info['response']['response']['products'] ) || isset( $info['response']['products'] ) || isset( $info['response']['listing_id'] ) ) {
						$html .= "<span class='etsy_log_success log_details'>Success</span>";
					} else {
						$html .= "<span class='etsy_log_fail log_details'>Failed</span>";
					}
					$html .= "<span class='log_message' style='display:none;'><h3>Response payload for " . ( $info['post_title'] ) . '</h3><button id="ced_close_log_message">Close</button><pre>' . ( ! empty( $info['response'] ) ? json_encode( $info['response'], JSON_PRETTY_PRINT ) : '' ) . '</pre></span>';
					$html .= '</td>';
					$html .= '</tr>';
				}
			}
			if ( $offset >= $total ) {
				$is_disable = 'yes';
			}
			echo json_encode(
				array(
					'html'       => $html,
					'offset'     => $offset,
					'is_disable' => $is_disable,
				)
			);
			wp_die();
		}
	}

	public function ced_etsy_add_table_columns( $columns ) {
		$modified_columns = array();
		foreach ( $columns as $key => $value ) {
			$modified_columns[ $key ] = $value;
			if ( 'order_number' == $key ) {
				$modified_columns['order_from'] = '<span title="Order source">Order source</span>';
			}
		}
		return $modified_columns;
	}


	public function ced_etsy_manage_table_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'order_from':
				$_ced_etsy_order_id = get_post_meta( $post_id, '_ced_etsy_order_id', true );
				if ( ! empty( $_ced_etsy_order_id ) ) {
					$etsy_icon = CED_ETSY_URL . 'admin/assets/images/etsy.png';
					echo '<p><img src="' . esc_url( $etsy_icon ) . '" height="35" width="60"></p>';
				}
		}
	}



	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommmerce_Etsy_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommmerce_Etsy_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && ( 'ced_etsy' == $_GET['page'] || 'cedcommerce-integrations' == $_GET['page'] ) ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '/assets/css/woocommmerce-etsy-integration-admin.css', array(), $this->version, 'all' );

		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		global $pagenow;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommmerce_Etsy_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommmerce_Etsy_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/woocommmerce-etsy-integration-admin.js', array( 'jquery' ), $this->version, false );
		$ajax_nonce     = wp_create_nonce( 'ced-etsy-ajax-seurity-string' );
		$localize_array = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => $ajax_nonce,
			'shop_name'  => $shop_name,
		);
		wp_localize_script( $this->plugin_name, 'ced_etsy_admin_obj', $localize_array );

	}

	/**
	 * Add admin menus and submenus
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_add_menus() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['cedcommerce-integrations'] ) ) {
			add_menu_page( __( 'Marketplaces', 'woocommerce-etsy-integration' ), __( 'Marketplaces', 'woocommerce-etsy-integration' ), 'manage_woocommerce', 'cedcommerce-integrations', array( $this, 'ced_marketplace_listing_page' ), 'dashicons-store', 12 );

			/** Alter Marketplace submenus
							 *
							 * @since 1.0.0
							 */
			$menus = apply_filters( 'ced_add_marketplace_menus_array', array() );

			if ( is_array( $menus ) && ! empty( $menus ) ) {
				foreach ( $menus as $key => $value ) {
					add_submenu_page( 'cedcommerce-integrations', $value['name'], $value['name'], 'manage_woocommerce', $value['menu_link'], array( $value['instance'], $value['function'] ) );
				}
			}
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_search_product_name.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_search_product_name() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$keyword      = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
			$product_list = '';
			if ( ! empty( $keyword ) ) {
				$arguements = array(
					'numberposts' => -1,
					'post_type'   => array( 'product', 'product_variation' ),
					's'           => $keyword,
				);
				$post_data  = get_posts( $arguements );
				if ( ! empty( $post_data ) ) {
					foreach ( $post_data as $key => $data ) {
						$product_list .= '<li class="ced_etsy_searched_product" data-post-id="' . esc_attr( $data->ID ) . '">' . esc_html( __( $data->post_title, 'etsy-woocommerce-integration' ) ) . '</li>';
					}
				} else {
					$product_list .= '<li>No products found.</li>';
				}
			} else {
				$product_list .= '<li>No products found.</li>';
			}
			echo json_encode( array( 'html' => $product_list ) );
			wp_die();
		}
	}


		/**
		 * Woocommerce_Etsy_Integration_Admin ced_etsy_get_product_metakeys.
		 *
		 * @since 1.0.0
		 */
	public function ced_etsy_get_product_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
			include_once CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-etsy-metakeys-list.php';
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_process_metakeys.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_process_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$metakey   = isset( $_POST['metakey'] ) ? sanitize_text_field( wp_unslash( $_POST['metakey'] ) ) : '';
			$operation = isset( $_POST['operation'] ) ? sanitize_text_field( wp_unslash( $_POST['operation'] ) ) : '';
			if ( ! empty( $metakey ) ) {
				$added_meta_keys = get_option( 'ced_etsy_selected_metakeys', array() );
				if ( 'store' == $operation ) {
					$added_meta_keys[ $metakey ] = $metakey;
				} elseif ( 'remove' == $operation ) {
					unset( $added_meta_keys[ $metakey ] );
				}
				update_option( 'ced_etsy_selected_metakeys', $added_meta_keys );
				echo json_encode( array( 'status' => 200 ) );
				die();
			} else {
				echo json_encode( array( 'status' => 400 ) );
				die();
			}
		}
	}

	/**
	 * Active Marketplace List
	 *
	 * @since    1.0.0
	 */

	public function ced_marketplace_listing_page() {
		/** Alter Marketplace admin menu
				 *
				 * @since 1.0.0
				 */
		$activeMarketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
		if ( is_array( $activeMarketplaces ) && ! empty( $activeMarketplaces ) ) {
			require CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-marketplaces.php';
		}
	}

	public function ced_etsy_add_marketplace_menus_to_array( $menus = array() ) {
		$menus[] = array(
			'name'            => 'Etsy',
			'slug'            => 'woocommerce-etsy-integration',
			'menu_link'       => 'ced_etsy',
			'instance'        => $this,
			'function'        => 'ced_etsy_accounts_page',
			'card_image_link' => CED_ETSY_URL . 'admin/assets/images/etsy.png',
		);
		return $menus;
	}

	/**
	 * Ced Etsy Accounts Page
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_accounts_page() {
		$account = new Cedcommerce\Template\View\Ced_View_Etsy_Accounts();
		$account->prepare_items();
	}


	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_add_order_metabox.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_add_order_metabox() {
		global $post;
		$product    = wc_get_product( $post->ID );
		$order_from = get_post_meta( $post->ID, '_umb_etsy_marketplace', true );
		if ( 'etsy' == strtolower( $order_from ) ) {
			add_meta_box(
				'ced_etsy_manage_orders_metabox',
				__( 'Manage Marketplace Orders', 'woocommerce-etsy-integration' ) . wc_help_tip( __( 'Please send shipping confirmation.', 'woocommerce-etsy-integration' ) ),
				array( $this, 'ced_etsy_render_orders_metabox' ),
				'shop_order',
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_submit_shipment.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_submit_shipment() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$ced_etsy_tracking_code = isset( $_POST['ced_etsy_tracking_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_tracking_code'] ) ) : '';
			$ced_etsy_carrier_name  = isset( $_POST['ced_etsy_carrier_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_carrier_name'] ) ) : '';
			$order_id               = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';

			$shop_name = get_option( 'ced_etsy_shop_name', '' );
			if ( empty( $shop_name ) ) {
				$shop_name = get_post_meta( $order_id, 'ced_etsy_order_shop_id', true );
			}
			$_ced_etsy_order_id = get_post_meta( $order_id, '_ced_etsy_order_id', true );
			$saved_etsy_details = get_option( 'ced_etsy_details', array() );
			$shopDetails        = $saved_etsy_details[ $shop_name ];
			$shop_id            = $shopDetails['details']['shop_id'];
			$parameters         = array(
				'tracking_code' => $ced_etsy_tracking_code,
				'carrier_name'  => $ced_etsy_carrier_name,
			);
			/** Refresh token
									 *
									 * @since 2.0.0
									 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$action   = 'application/shops/' . $shop_id . '/receipts/' . $_ced_etsy_order_id . '/tracking';
			$response = etsy_request()->post( $action, $parameters, $shop_name );
			if ( isset( $response['receipt_id'] ) || isset( $response['Shipping_notification_email_has_already_been_sent_for_this_receipt_'] ) ) {
				update_post_meta( $order_id, '_etsy_umb_order_status', 'Shipped' );
				$_order = wc_get_order( $order_id );
				$_order->update_status( 'wc-completed' );
				echo json_encode(
					array(
						'status'  => 200,
						'message' => 'Shipment submitted successfully.',
					)
				);
				wp_die();
			} elseif ( is_array( $response ) ) {
				foreach ( $response as $error => $value ) {
					$message = isset( $error ) ? ucwords( str_replace( '_', ' ', $error ) ) : '';
					echo json_encode(
						array(
							'status'  => 400,
							'message' => $message,
						)
					);
					wp_die();
				}
			} else {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => 'Shipment not submitted.',
					)
				);
				wp_die();
			}
		}
	}


	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_render_orders_metabox.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_render_orders_metabox() {
		global $post;
		$order_id = isset( $post->ID ) ? intval( $post->ID ) : '';
		if ( ! is_null( $order_id ) ) {
			$order         = wc_get_order( $order_id );
			$template_path = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-order-template.php';
			if ( file_exists( $template_path ) ) {
				include_once $template_path;
			}
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_email_restriction.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_email_restriction( $enable = '', $order = array() ) {
		if ( ! is_object( $order ) ) {
			return $enable;
		}
		$order_id   = $order->get_id();
		$order_from = get_post_meta( $order_id, '_umb_etsy_marketplace', true );
		if ( 'etsy' == strtolower( $order_from ) ) {
			$enable = false;
		}
		return $enable;
	}

	/**
	 * Marketplace
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_marketplace_to_be_logged( $marketplaces = array() ) {

		$marketplaces[] = array(
			'name'             => 'Etsy',
			'marketplace_slug' => 'etsy',
		);
		return $marketplaces;
	}

	/**
	 * Etsy Cron Schedules
	 *
	 * @since    1.0.0
	 */
	public function my_etsy_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ced_etsy_6min'] ) ) {
			$schedules['ced_etsy_6min'] = array(
				'interval' => 6 * 60,
				'display'  => __( 'Once every 6 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_10min'] ) ) {
			$schedules['ced_etsy_10min'] = array(
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_15min'] ) ) {
			$schedules['ced_etsy_15min'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Once every 15 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_30min'] ) ) {
			$schedules['ced_etsy_30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Once every 30 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_20min'] ) ) {
			$schedules['ced_etsy_20min'] = array(
				'interval' => 20 * 60,
				'display'  => __( 'Once every 20 minutes' ),
			);
		}
		return $schedules;
	}


	/**
	 * Etsy Fetch Next Level Category
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_fetch_next_level_category() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$store_category_id      = isset( $_POST['store_id'] ) ? sanitize_text_field( wp_unslash( $_POST['store_id'] ) ) : '';
			$etsy_category_name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$etsy_category_id       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$level                  = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
			$next_level             = intval( $level ) + 1;
			$etsyCategoryList       = file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-' . $next_level . '.json' );
			$etsyCategoryList       = json_decode( $etsyCategoryList, true );
			$select_html            = '';
			$nextLevelCategoryArray = array();
			if ( ! empty( $etsyCategoryList ) ) {
				foreach ( $etsyCategoryList as $key => $value ) {
					if ( isset( $value['parent_id'] ) && $value['parent_id'] == $etsy_category_id ) {
						$nextLevelCategoryArray[] = $value;
					}
				}
			}
			if ( is_array( $nextLevelCategoryArray ) && ! empty( $nextLevelCategoryArray ) ) {

				$select_html .= '<td data-catlevel="' . $next_level . '"><select class="ced_etsy_level' . $next_level . '_category ced_etsy_select_category select_boxes_cat_map" name="ced_etsy_level' . $next_level . '_category[]" data-level=' . $next_level . ' data-storeCategoryID="' . $store_category_id . '">';
				$select_html .= '<option value=""> --' . __( 'Select', 'woocommerce-etsy-integration' ) . '-- </option>';
				foreach ( $nextLevelCategoryArray as $key => $value ) {
					if ( ! empty( $value['name'] ) ) {
						$select_html .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
					}
				}
				$select_html .= '</select></td>';
				echo json_encode( $select_html );
				die;
			}
		}
	}

	/*
	*
	*Function for Fetching child categories for custom profile
	*
	*
	*/

	public function ced_etsy_fetch_next_level_category_add_profile() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$tableName              = $wpdb->prefix . 'ced_etsy_accounts';
			$etsy_store_id          = isset( $_POST['etsy_store_id'] ) ? sanitize_text_field( wp_unslash( $_POST['etsy_store_id'] ) ) : '';
			$etsy_category_name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$etsy_category_id       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$level                  = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
			$next_level             = intval( $level ) + 1;
			$etsyCategoryList       = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-' . $next_level . '.json' );
			$etsyCategoryList       = json_decode( $etsyCategoryList, true );
			$select_html            = '';
			$nextLevelCategoryArray = array();
			if ( ! empty( $etsyCategoryList ) ) {
				foreach ( $etsyCategoryList as $key => $value ) {
					if ( isset( $value['parent_id'] ) && $value['parent_id'] == $etsy_category_id ) {
						$nextLevelCategoryArray[] = $value;
					}
				}
			}
			if ( is_array( $nextLevelCategoryArray ) && ! empty( $nextLevelCategoryArray ) ) {

				$select_html .= '<td data-catlevel="' . $next_level . '"><select class="ced_etsy_level' . $next_level . '_category ced_etsy_select_category_on_add_profile  select_boxes_cat_map" name="ced_etsy_level' . $next_level . '_category[]" data-level=' . $next_level . ' data-etsyStoreId="' . $etsy_store_id . '">';
				$select_html .= '<option value=""> --' . __( 'Select', 'woocommerce-etsy-integration' ) . '-- </option>';
				foreach ( $nextLevelCategoryArray as $key => $value ) {
					if ( ! empty( $value['name'] ) ) {
						$select_html .= '<option value="' . $value['id'] . ',' . $value['name'] . '">' . $value['name'] . '</option>';
					}
				}
				$select_html .= '</select></td>';
				echo json_encode( $select_html );
				die;
			}
		}
	}


	/**
	 * Etsy Mapping Categories to WooStore
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_map_categories_to_store() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array             = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$etsy_category_array         = isset( $sanitized_array['etsy_category_array'] ) ? $sanitized_array['etsy_category_array'] : '';
			$store_category_array        = isset( $sanitized_array['store_category_array'] ) ? $sanitized_array['store_category_array'] : '';
			$etsy_category_name          = isset( $sanitized_array['etsy_category_name'] ) ? $sanitized_array['etsy_category_name'] : '';
			$etsy_store_id               = isset( $_POST['storeName'] ) ? sanitize_text_field( wp_unslash( $_POST['storeName'] ) ) : '';
			$etsy_saved_category         = get_option( 'ced_etsy_saved_category', array() );
			$alreadyMappedCategories     = array();
			$alreadyMappedCategoriesName = array();
			$etsyMappedCategories        = array_combine( $store_category_array, $etsy_category_array );
			$etsyMappedCategories        = array_filter( $etsyMappedCategories );
			$alreadyMappedCategories     = get_option( 'ced_woo_etsy_mapped_categories_' . $etsy_store_id, array() );
			if ( is_array( $etsyMappedCategories ) && ! empty( $etsyMappedCategories ) ) {
				foreach ( $etsyMappedCategories as $key => $value ) {
					$alreadyMappedCategories[ $etsy_store_id ][ $key ] = $value;
				}
			}
			update_option( 'ced_woo_etsy_mapped_categories_' . $etsy_store_id, $alreadyMappedCategories );
			$etsyMappedCategoriesName    = array_combine( $etsy_category_array, $etsy_category_name );
			$etsyMappedCategoriesName    = array_filter( $etsyMappedCategoriesName );
			$alreadyMappedCategoriesName = get_option( 'ced_woo_etsy_mapped_categories_name_' . $etsy_store_id, array() );
			if ( is_array( $etsyMappedCategoriesName ) && ! empty( $etsyMappedCategoriesName ) ) {
				foreach ( $etsyMappedCategoriesName as $key => $value ) {
					$alreadyMappedCategoriesName[ $etsy_store_id ][ $key ] = $value;
				}
			}
			update_option( 'ced_woo_etsy_mapped_categories_name_' . $etsy_store_id, $alreadyMappedCategoriesName );
			$this->etsy_manager->ced_etsy_create_auto_profiles( $etsyMappedCategories, $etsyMappedCategoriesName, $etsy_store_id );
			wp_die();
		}
	}

	/**
	 * Etsy Inventory Scheduler
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_inventory_schedule_manager() {

		$hook    = current_action();
		$shop_id = str_replace( 'ced_etsy_inventory_scheduler_job_', '', $hook );
		$shop_id = trim( $shop_id );

		$shop_id = get_option( 'ced_etsy_shop_name', '' );

		$products_to_sync = get_option( 'ced_etsy_chunk_products_' . $shop_id, array() );
		if ( empty( $products_to_sync ) ) {
			$store_products   = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'meta_query'  => array(
						array(
							'key'     => '_ced_etsy_listing_id_' . $shop_id,
							'compare' => 'EXISTS',
						),
					),
				)
			);
			$store_products   = wp_list_pluck( $store_products, 'ID' );
			$products_to_sync = array_chunk( $store_products, 10 );

		}
		if ( is_array( $products_to_sync[0] ) && ! empty( $products_to_sync[0] ) ) {
			foreach ( $products_to_sync[0] as $product_id ) {
				$response = ( new \Cedcommerce\Product\Ced_Product_Update( $shop_id, $product_id ) )->ced_etsy_update_inventory( $product_id, $shop_id, true );
			}
			unset( $products_to_sync[0] );
			$products_to_sync = array_values( $products_to_sync );
			update_option( 'ced_etsy_chunk_products_' . $shop_id, $products_to_sync );
		}
	}


	public function ced_etsy_auto_upload_products() {
		$shop_name     = str_replace( 'ced_etsy_auto_upload_products_', '', current_action() );
		$shop_name     = trim( $shop_name );
		$product_chunk = get_option( 'ced_etsy_product_upload_chunk_' . $shop_name, array() );

		$shop_name = get_option( 'ced_etsy_shop_name', '' );

		if ( empty( $product_chunk ) ) {
			$store_products = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'fields'      => 'ids',
					'meta_query'  => array(
						array(
							'key'     => '_ced_etsy_listing_id_' . $shop_name,
							'compare' => 'NOT EXISTS',
						),

					),
				)
			);
			$product_chunk = array_chunk( $store_products, 20 );
		}
		if ( isset( $product_chunk[0] ) && is_array( $product_chunk[0] ) && ! empty( $product_chunk[0] ) ) {
			foreach ( $product_chunk[0] as $product_id ) {
				$response = ( new \Cedcommerce\Product\Ced_Product_Upload( $product_id, $shop_name ) )->ced_etsy_upload_product( $product_id, $shop_name, true );
			}
			unset( $product_chunk[0] );
			$product_chunk = array_values( $product_chunk );
			update_option( 'ced_etsy_product_upload_chunk_' . $shop_name, $product_chunk );
		}
	}


	/**
	 * Etsy Sync existing products scheduler
	 *
	 * @since    1.0.5
	 */
	public function ced_etsy_sync_existing_products() {

		$hook      = current_action();
		$shop_name = str_replace( 'ced_etsy_sync_existing_products_job_', '', $hook );
		$shop_name = trim( $shop_name );

		$shop_name = get_option( 'ced_etsy_shop_name', '' );

		$saved_etsy_details = get_option( 'ced_etsy_details', true );
		$shopDetails        = $saved_etsy_details[ $shop_name ];
		$shop_id            = $shopDetails['details']['shop_id'];
		$offset             = get_option( 'ced_etsy_get_offset_' . $shop_name, '' );
		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$query_args = array(
			'offset' => $offset,
			'limit'  => 25,
			'state'  => 'active',
		);

		/** Refresh token
						 *
						 * @since 2.0.0
						 */
		do_action( 'ced_etsy_refresh_token', $shop_name );
		$action   = "application/shops/{$shop_id}/listings";
		$response = etsy_request()->get( $action, $shop_name, $query_args );
		if ( isset( $response['results'][0] ) ) {
			foreach ( $response['results'] as $key => $value ) {
				$skus = isset( $value['skus'] ) ? $value['skus'] : '';
				if ( ! empty( $skus ) ) {
					foreach ( $skus as $sku ) {
						$product_id = wc_get_product_id_by_sku( $sku );
						if ( $product_id ) {
							$_product = wc_get_product( $product_id );
							if ( 'variation' == $_product->get_type() ) {
								$product_id = $_product->get_parent_id();
							}
							update_post_meta( $product_id, '_ced_etsy_state_' . $shop_name, $value['state'] );
							update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $value['url'] );
							update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $value['listing_id'] );
							update_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $value ) );
							break;
						}
					}
				}
			}
			$next_offset = $offset + 25;
			update_option( 'ced_etsy_get_offset_' . $shop_name, $next_offset );
		} else {
			update_option( 'ced_etsy_get_offset_' . $shop_name, 0 );
		}
	}

	/**
	 * ****************************************************
	 *  AUTO IMPORT PRODUCT BY SCHEDULER GLOBAL SETTINGS
	 * ****************************************************
	 *
	 * @since    2.0.0
	 */

	public function ced_etsy_auto_import_schedule_manager() {
		$hook      = current_action();
		$shop_name = str_replace( 'ced_etsy_auto_import_schedule_job_', '', $hook );
		if ( empty( $shop_name ) ) {
			$shop_name = get_option( 'ced_etsy_shop_name', '' );
		}
		$shop_name_t        = 'ced_etsy_import_by_status_' . $shop_name;
		$saved_status       = get_option( $shop_name_t, '' );
		$saved_etsy_details = get_option( 'ced_etsy_details', array() );
		$shopDetails        = $saved_etsy_details[ $shop_name ];
		$shop_id            = $shopDetails['details']['shop_id'];
		$offset             = get_option( 'ced_etsy_get_import_offset', '' );

		if ( ! empty( $saved_status ) ) {
			$status = $saved_status;
		} else {
			$status = 'active';
		}

		$status = 'active';

		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$params = array(
			'state'  => $status,
			'offset' => $offset,
			'limit'  => $per_page,
		);

		if ( empty( $shop_name ) ) {
			return;
		}
		/** Refresh token
						 *
						 * @since 2.0.0
						 */
		do_action( 'ced_etsy_refresh_token', $shop_name );
		$shop_id  = get_etsy_shop_id( $shop_name );
		$response = etsy_request()->get( "application/shops/{$shop_id}/listings", $shop_name, $params );
		if ( isset( $response['results'][0] ) ) {
			foreach ( $response['results'] as $key => $value ) {
				$this->import_product->ced_etsy_import_products( $value['listing_id'], $shop_name );
			}
			if ( isset( $response['pagination']['next_offset'] ) && ! empty( $response['pagination']['next_offset'] ) ) {
				$next_offset = $response['pagination']['next_offset'];
			} else {
				$next_offset = 0;
			}
			update_option( 'ced_etsy_get_import_offset', $next_offset );
		} else {
			update_option( 'ced_etsy_get_import_offset', 0 );
		}
	}

	/**
	 * Etsy Order Scheduler
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_order_schedule_manager() {
		$hook       = current_action();
		$shop_id    = str_replace( 'ced_etsy_order_scheduler_job_', '', $hook );
		$shop_id    = trim( $shop_id );
		$shop_id    = get_option( 'ced_etsy_shop_name', '' );
		$get_orders = $this->ced_etsy_order->get_orders( $shop_id );
		if ( ! empty( $get_orders ) ) {
			$createOrder = $this->ced_etsy_order->createLocalOrder( $get_orders, $shop_id );
		}
	}

	/**
	 * Etsy Fetch Orders
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_get_orders() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_id    = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			$get_orders = $this->ced_etsy_order->get_orders( $shop_id );
			if ( ! empty( $get_orders ) ) {
				$createOrder = $etsyOrdersInstance->createLocalOrder( $get_orders, $shop_id );
			}
		}
	}

	/**
	 * Etsy Profiles List on popup
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_profiles_on_pop_up() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$store_id = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			$prodId   = isset( $_POST['prodId'] ) ? sanitize_text_field( wp_unslash( $_POST['prodId'] ) ) : '';
			global $wpdb;
			$profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `shop_name` = %s", $store_id ), 'ARRAY_A' );
			?>
			<div class="ced_etsy_profile_popup_content">
				<div id="profile_pop_up_head_main">
					<h2><?php esc_html_e( 'CHOOSE PROFILE FOR THIS PRODUCT', 'woocommerce-etsy-integration' ); ?></h2>
					<div class="ced_etsy_profile_popup_close">X</div>
				</div>
				<div id="profile_pop_up_head"><h3><?php esc_html_e( 'Available Profiles', 'woocommerce-etsy-integration' ); ?></h3></div>
				<div class="ced_etsy_profile_dropdown">
					<select name="ced_etsy_profile_selected_on_popup" class="ced_etsy_profile_selected_on_popup">
						<option class="profile_options" value=""><?php esc_html_e( '---Select Profile---', 'woocommerce-etsy-integration' ); ?></option>
						<?php
						foreach ( $profiles as $key => $value ) {
							echo '<option  class="profile_options" value="' . esc_html( $value['id'] ) . '">' . esc_html( $value['profile_name'] ) . '</option>';
						}
						?>
					</select>
				</div>	
				<div id="save_profile_through_popup_container">
					<button data-prodId="<?php echo esc_html( $prodId ); ?>" class="ced_etsy_custom_button" id="save_etsy_profile_through_popup"  data-shopid="<?php echo esc_html( $store_id ); ?>"><?php esc_html_e( 'Assign Profile', 'woocommerce-etsy-integration' ); ?></button>
				</div>
			</div>
			<?php
			wp_die();
		}
	}

	/**
	 * Etsy Refreshing Categories
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_category_refresh() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_name = isset( $_POST['shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['shop_name'] ) ) : '';

			$fetchedCategories = $this->etsy_cat_obj->get_etsy_categories( $shop_name );
			if ( isset( $fetchedCategories['results'] ) && ! empty( $fetchedCategories['results'] ) ) {
				$categories = $this->etsy_cat_obj->ced_etsy_store_categories( $fetchedCategories );
				echo json_encode( array( 'status' => 200 ) );
				wp_die();
			} else {
				echo json_encode( array( 'status' => 400 ) );
				wp_die();
			}
		}
	}

	/**
	 * Etsy Save profile On Product level
	 *
	 * @since    1.0.0
	 */
	public function save_etsy_profile_through_popup() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shopid     = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			$prodId     = isset( $_POST['prodId'] ) ? sanitize_text_field( wp_unslash( $_POST['prodId'] ) ) : '';
			$profile_id = isset( $_POST['profile_id'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_id'] ) ) : '';
			if ( '' == $profile_id ) {
				echo 'null';
				wp_die();
			}

			update_post_meta( $prodId, 'ced_etsy_profile_assigned' . $shopid, $profile_id );
		}
	}

	/**
	 * Etsy Bulk Operations
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_process_bulk_action() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_name           = isset( $_POST['shopname'] ) ? sanitize_text_field( wp_unslash( $_POST['shopname'] ) ) : '';
			$operation           = isset( $_POST['operation_to_be_performed'] ) ? sanitize_text_field( wp_unslash( $_POST['operation_to_be_performed'] ) ) : '';
			$product_id          = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$title               = '<b><i>' . get_the_title( $product_id ) . '</i></b>';
			$already_uploaded    = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			$response['status']  = 400;
			$response['message'] = 'you need to upload this product first';
			switch ( $operation ) {
				case 'upload_product':
					if ( ! $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Upload( $product_id, $shop_name ) )->ced_etsy_upload_product( $product_id, $shop_name );
					} else {
						$response['status']  = 400;
						$response['message'] = 'Product already uploaded';
					}
					break;
				case 'update_product':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_etsy_update_product( $product_id, $shop_name );
					}
					break;
				case 'remove_product':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Delete( $shop_name, $product_id ) )->ced_etsy_delete_product( $product_id, $shop_name );
					}
					break;
				case 'update_inventory':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_etsy_update_inventory( $product_id, $shop_name );
					}
					break;
				case 'update_image':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_update_images_on_etsy( $product_id, $shop_name );
					}
					break;
				case 'unlink_product':
					if ( $already_uploaded ) {
						delete_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name );
						delete_post_meta( $product_id, '_ced_etsy_url_' . $shop_name );
						delete_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name );
						delete_post_meta( $product_id, '_ced_etsy_state_' . $shop_name );
						$response['status']  = 200;
						$response['message'] = 'Unlinked successfully';
					}
					break;
				default:
					$response['status']  = 400;
					$response['message'] = 'Invalid operation';
					break;
			}

			echo json_encode(
				array(
					'status'  => $response['status'],
					'message' => $title . ' : ' . ced_etsy_format_response( $response['message'], $shop_name ),
				)
			);
			wp_die();
		}
	}


	private function ced_notice_response( $status = '', $message = '', $product_id = '' ) {
		return json_encode(
			array(
				'status'  => $status,
				'message' => __( $message, 'woocommerce-etsy-integration' ),
				'prodid'  => $product_id,
			)
		);
	}

	/**
	 * Etsy Import Products Bulk Operations.
	 *
	 * @since    1.1.2
	 */
	public function ced_etsy_import_products_bulk_action() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$operation       = isset( $sanitized_array['operation_to_be_performed'] ) ? $sanitized_array['operation_to_be_performed'] : '';
			$listing_ids     = isset( $sanitized_array['listing_id'] ) ? $sanitized_array['listing_id'] : '';
			$shop_name       = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';

			foreach ( $listing_ids as $key => $listing_id ) {
				$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
				if ( ! empty( $if_product_exists ) ) {
					echo json_encode(
						array(
							'status'  => 200,
							'message' => __(
								'Product exists in store !'
							),
						)
					);
				} else {
					$response = $this->import_product->ced_etsy_import_products( $listing_id, $shop_name );
					echo json_encode(
						array(
							'status'  => 200,
							'message' => __(
								'Product Imported Successfully !'
							),
						)
					);
				}
				break;
			}
			wp_die();
		}
	}


	/**
	 * ******************************************************************
	 * Function to Delete for mapped profiles in the profile-view page
	 * ******************************************************************
	 *
	 *  @since version 1.0.8.
	 */
	public function ced_esty_delete_mapped_profiles() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$profile_id = isset( $_POST['profile_id'] ) ? sanitize_text_field( $_POST['profile_id'] ) : '';
			$shop_name  = isset( $_POST['shop_name'] ) ? sanitize_text_field( $_POST['shop_name'] ) : '';
			$tableName  = $wpdb->prefix . 'ced_etsy_profiles';
			$result     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}ced_etsy_profiles WHERE `shop_name`= %d ", $shop_name ), 'ARRAY_A' );
			foreach ( $result as $key => $value ) {
				if ( $value['id'] === $profile_id ) {
					$wpdb->query(
						$wpdb->prepare(
							" DELETE FROM {$wpdb->prefix}ced_etsy_profiles WHERE 
							`id` = %s AND shop_name = %s",
							$value['id'],
							$shop_name
						)
					);
					echo json_encode(
						array(
							'status'  => 200,
							'message' => __(
								'Profile Deleted Successfully !',
								'woocommerce-etsy-integration'
							),
						)
					);
				}
			}
			die;
		}
	}

	/**
	 * ***********************************************************
	 * CED etsy prdouct field table on the simple product level .
	 * ***********************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_product_data_tabs( $tabs ) {
		$tabs['etsy_inventory'] = array(
			'label'  => __( 'Etsy', 'woocommerce-etsy-integration' ),
			'target' => 'etsy_inventory_options',
			'class'  => array( 'show_if_simple', 'show_if_variable' ),
		);
		return $tabs;
	}


	/**
	 * ******************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_product_data_panels.
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_product_data_panels() {

		global $post;

		?>
		<div id='etsy_inventory_options' class='panel woocommerce_options_panel'><div class='options_group'>
			<form>
				<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
			</form>
			<?php
			echo "<div class='ced_etsy_simple_product_level_wrap'>";
			echo "<div class=''>";
			echo "<h2 class='etsy-cool'>Etsy Product Data";
			echo '</h2>';
			echo '</div>';
			echo "<div class='ced_etsy_simple_product_content' style='max-height: 350px;min-height: 350px;
			overflow: scroll;'>";
			$this->ced_esty_render_fields( $post->ID, true );
			echo '</div>';
			echo '</div>';
			?>
		</div></div>
		<?php

	}
	/**
	 * ******************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_product_data_panels.
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */

	public function ced_etsy_render_product_fields( $loop, $variation_data, $variation ) {
		if ( ! empty( $variation_data ) ) {
			?>
			<div id='etsy_inventory_options_variable' class='panel woocommerce_options_panel'><div class='options_group'>
				<form>
					<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
				</form>
				<?php
				echo "<div class='ced_etsy_variation_product_level_wrap'>";
				echo "<div class='ced_etsy_parent_element'>";
				echo "<h2 class='etsy-cool'> Etsy Product Data";
				echo "<span class='dashicons dashicons-arrow-down-alt2 ced_etsy_instruction_icon'></span>";
				echo '</h2>';
				echo '</div>';
				echo "<div class='ced_etsy_variation_product_content ced_etsy_child_element'>";
				$this->ced_esty_render_fields( $variation->ID, false );
				echo '</div>';
				echo '</div>';
				?>
			</div></div>
			<?php
		}
	}

	/**
	 * ********************************************************
	 * CREATE FIELDS AT EACH VARIATIONS LEVEL FOR ENTER PRICE
	 * ********************************************************
	 *
	 * @since 2.0.0
	 */

	public function ced_esty_render_fields( $product_id = '', $simple_product = '' ) {

		$productFieldInstance = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
		$settings             = $productFieldInstance->get_custom_products_fields( get_etsy_shop_name() );

		$variation_fields = array(
			'_ced_etsy_price',
			'_ced_etsy_markup_type',
			'_ced_etsy_markup_value',
			'_ced_etsy_stock',
		);

		if ( ! empty( $settings ) ) {
			foreach ( $settings as $section => $product_fields ) {
				foreach ( $product_fields as $key => $value ) {

					$label    = isset( $value['fields']['label'] ) ? $value['fields']['label'] : '';
					$field_id = isset( $value['fields']['id'] ) ? $value['fields']['id'] : '';

					if ( ! in_array( $field_id, $variation_fields ) && ! $simple_product ) {
						continue;
					}

					$id             = 'ced_etsy_data[' . $product_id . '][' . $field_id . ']';
					$selected_value = get_post_meta( $product_id, $field_id, true );

					if ( '_select' == $value['type'] ) {
						$option_array     = array();
						$option_array[''] = '--select--';
						foreach ( $value['fields']['options'] as $option_key => $option ) {
							$option_array[ $option_key ] = $option;
						}
						woocommerce_wp_select(
							array(
								'id'          => $id,
								'label'       => $value['fields']['label'],
								'options'     => $option_array,
								'value'       => $selected_value,
								'desc_tip'    => 'true',
								'description' => $value['fields']['description'],
								'class'       => 'ced_etsy_product_select',
							)
						);
					} elseif ( '_text_input' == $value['type'] ) {
						woocommerce_wp_text_input(
							array(
								'id'          => $id,
								'label'       => $value['fields']['label'],
								'desc_tip'    => 'true',
								'description' => $value['fields']['description'],
								'type'        => 'text',
								'value'       => $selected_value,
							)
						);
					}
				}
			}
		}
	}


	/**
	 * *****************************************************************
	 * Woocommerce_etsy_Integration_Admin ced_etsy_save_product_fields.
	 * *****************************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_save_product_fields_variation( $post_id = '', $i = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ced_product_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_product_settings_submit'] ) ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $_POST['ced_etsy_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_etsy_data'] as $id => $value ) {
					foreach ( $value as $meta_key => $meta_val ) {
						update_post_meta( $id, $meta_key, $meta_val );
					}
				}
			}
		}
	}


	/**
	 * **************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_save_meta_data
	 * **************************************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_save_meta_data( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ced_product_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_product_settings_submit'] ) ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $_POST['ced_etsy_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_etsy_data'] as $id => $value ) {
					foreach ( $value as $meta_key => $meta_val ) {
						update_post_meta( $id, $meta_key, $meta_val );
					}
				}
			}
		}
	}



	public function ced_etsy_delete_shipping_profile() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$e_shiping_id    = isset( $sanitized_array['e_profile_id'] ) ? $sanitized_array['e_profile_id'] : array();
			$shop_name       = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';
			if ( '' != $shop_name && ! empty( $e_shiping_id ) ) {
				$shop_id = get_etsy_shop_id( $shop_name );
				$action  = 'application/shops/' . $shop_id . '/shipping-profiles/' . $e_shiping_id;
				/** Refresh token
				 *
				 * @since 2.0.0
				 */
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$is_deleted = etsy_request()->delete( $action, $shop_name, array(), 'DELETE' );
				echo json_encode(
					array(
						'status'  => 200,
						'message' => __(
							'Profile is Deleted!',
							'woocommerce-etsy-integration'
						),
					)
				);
				wp_die();
			}
		}
	}
}
