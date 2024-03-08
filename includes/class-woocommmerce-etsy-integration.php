<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/includes
 */
class Woocommmerce_Etsy_Integration {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      Woocommmerce_Etsy_Integration_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WOOCOMMMERCE_ETSY_INTEGRATION_VERSION' ) ) {
			$this->version = WOOCOMMMERCE_ETSY_INTEGRATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommmerce-etsy-integration';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woocommmerce_Etsy_Integration_Loader. Orchestrates the hooks of the plugin.
	 * - Woocommmerce_Etsy_Integration_I18n. Defines internationalization functionality.
	 * - Woocommmerce_Etsy_Integration_Admin. Defines all hooks for the admin area.
	 * - Woocommmerce_Etsy_Integration_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommmerce-etsy-integration-loader.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ced-etsy-autoloader.php';
		new CedEtsyAutoloader();

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommmerce-etsy-integration-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommmerce-etsy-integration-admin.php';
		$this->loader = new Woocommmerce_Etsy_Integration_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woocommmerce_Etsy_Integration_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new Woocommmerce_Etsy_Integration_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woocommmerce_Etsy_Integration_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		/**
		 **************************
		 * ADD MENUS AND SUBMENUS
		 **************************
		 */
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'ced_etsy_add_menus', 23 );
		$this->loader->add_filter( 'ced_add_marketplace_menus_array', $plugin_admin, 'ced_etsy_add_marketplace_menus_to_array', 13 );
		$this->loader->add_filter( 'ced_marketplaces_logged_array', $plugin_admin, 'ced_etsy_marketplace_to_be_logged' );
		$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'my_etsy_cron_schedules' );

		$ced_ajaxs = array(
			'ced_etsy_authorize_account',
			'ced_etsy_fetch_next_level_category',
			'ced_etsy_map_categories_to_store',
			'ced_etsy_process_bulk_action',
			'ced_etsy_import_products_bulk_action',
			'ced_etsy_profiles_on_pop_up',
			'save_etsy_profile_through_popup',
			'ced_etsy_get_orders',
			'ced_etsy_search_product_name',
			'ced_etsy_get_product_metakeys',
			'ced_etsy_process_metakeys',
			'ced_etsy_fetch_next_level_category_add_profile',
			'ced_etsy_submit_shipment',
			'ced_esty_delete_mapped_profiles',
			'ced_etsy_map_shipping_profiles_woo_cat',
			'ced_etsy_delete_shipping_profile',
			'ced_etsy_category_refresh',
		);
		foreach ( $ced_ajaxs as $action_name ) {
			$this->loader->add_filter( 'wp_ajax_' . $action_name, $plugin_admin, $action_name );
		}

		/**
		 *********************************************
		 * ADD CUSTOM FIELDS ON THE PRODUC EDIT PAGE
		 *********************************************
		 */
		// AT THE VARIATION LEVEL CUSTOMIZATION
		$this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'ced_etsy_render_product_fields', 10, 3 );
		// RENDER CUSTOM FIELD ON THE SIMPLE PRODUCT LEVEL
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'ced_etsy_product_data_tabs' );
		// RENDER PRODUCT  CUSTOM FIELDS ON THE VARIATION LEVEL
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'ced_etsy_save_product_fields_variation', 10, 2 );
		// ON SAVE VARIATION BUTTON IT WILL RUN
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'ced_etsy_save_product_fields_variation', 12, 2 );
		// SHOW THE SAVE BUTTON OF THE VARIATION
		$this->loader->add_filter( 'woocommerce_product_data_panels', $plugin_admin, 'ced_etsy_product_data_panels' );
		// ON SAVE OF THE TOTAL PRODUCT
		$this->loader->add_action( 'save_post', $plugin_admin, 'ced_etsy_save_meta_data' );

		$shops = get_option( 'ced_etsy_details', array() );
		if ( ! empty( $shops ) ) {
			foreach ( $shops as $key => $value ) {
				if ( isset( $value['details']['ced_shop_account_status'] ) && 'Active' == $value['details']['ced_shop_account_status'] ) {
					$key = trim( $key );
					$this->loader->add_action( 'ced_etsy_inventory_scheduler_job_' . $key, $plugin_admin, 'ced_etsy_inventory_schedule_manager' );
					$this->loader->add_action( 'ced_etsy_auto_import_schedule_job_' . $key, $plugin_admin, 'ced_etsy_auto_import_schedule_manager' );
					$this->loader->add_action( 'ced_etsy_order_scheduler_job_' . $key, $plugin_admin, 'ced_etsy_order_schedule_manager' );
					$this->loader->add_action( 'ced_etsy_sync_existing_products_job_' . $key, $plugin_admin, 'ced_etsy_sync_existing_products' );
					$this->loader->add_action( 'ced_etsy_auto_upload_products_' . $key, $plugin_admin, 'ced_etsy_auto_upload_products' );
				}
			}
		}

		$this->loader->add_action( 'ced_etsy_order_scheduler_job_', $plugin_admin, 'ced_etsy_order_schedule_manager' );
		$this->loader->add_action( 'ced_etsy_inventory_scheduler_job_', $plugin_admin, 'ced_etsy_inventory_schedule_manager' );
		$this->loader->add_action( 'ced_etsy_sync_existing_products_job_', $plugin_admin, 'ced_etsy_sync_existing_products' );
		$order_status = array(
			'new_order',
			'customer_processing_order',
			'cancelled_order',
			'customer_completed_order',
			'customer_on_hold_order',
			'customer_refunded_order',
			'customer_failed_order',
		);
		foreach ( $order_status as $key => $status ) {
			$this->loader->add_filter( 'woocommerce_email_enabled_' . esc_attr( $status ), $plugin_admin, 'ced_etsy_email_restriction', 10, 2 );
		}
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ced_etsy_add_order_metabox' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woocommmerce_Etsy_Integration_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
