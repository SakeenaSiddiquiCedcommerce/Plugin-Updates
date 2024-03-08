<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
$header = new \Cedcommerce\Template\View\Ced_View_Header();

class EtsyListImportedProducts extends WP_List_Table {

	public $show_reset;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Product import', 'woocommerce-etsy-integration' ), // singular name of the listed records
				'plural'   => __( 'Products import', 'woocommerce-etsy-integration' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {

		global $wpdb;
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$per_page  = 10;
		$columns   = $this->get_columns();
		$hidden    = array();
		$sortable  = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();

		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		if ( ! $this->current_action() ) {
			$this->items = self::get_product_details( $per_page, $offset, $shop_name );
			$count       = self::get_count( $per_page, $current_page, $shop_name );

			$this->set_pagination_args(
				array(
					'total_items' => $count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $count / $per_page ),
				)
			);
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}


	public function get_product_details( $per_page = '', $offset = 1, $shop_name = '' ) {

		$data_on_global_settings = get_option( 'ced_etsy_global_settings', '' );
		$language                = isset( $data_on_global_settings[ $shop_name ]['etsy_language'] ) ? $data_on_global_settings[ $shop_name ]['etsy_language'] : '';

			// Check clicked button of filter
		if ( isset( $_POST['filter_button'] ) ) {
			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
			$status_sorting = isset( $_POST['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_POST['status_sorting'] ) ) : '';
			$current_url    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
			wp_redirect( $current_url . '&status_sorting=' . $status_sorting . '&shop_name=' . $shop_name );
		}

		if ( ! empty( $_GET['status_sorting'] ) ) {
			$status_sorting = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : '';
			$args           = $status_sorting;
		} else {
			$args = 'active';
		}

		$product_to_show = array();
		$shop_name       = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$params = array(
			'state'  => $args,
			'offset' => $offset,
			'limit'  => $per_page,
		);

		$shop_id = get_etsy_shop_id( $shop_name );
		$action  = "application/shops/{$shop_id}/listings";
		/** Refresh token
				 *
				 * @since 2.0.0
				 */
		do_action( 'ced_etsy_refresh_token', $shop_name );
		$response = etsy_request()->get( $action, $shop_name, $params );
		if ( empty( $response['count'] ) ) {
			update_option( 'ced_etsy_total_import_product_' . $shop_name, 0 );
			return array();
		}

		// Update total Avaiable Items
		update_option( 'ced_etsy_total_import_product_' . $shop_name, $response['count'] );
		if ( isset( $response['results'][0] ) ) {
			foreach ( $response['results'] as $key => $value ) {
				$products_to_list['name']       = $value['title'];
				$products_to_list['price']      = (float) $value['price']['amount'] / $value['price']['divisor'];
				$products_to_list['stock']      = $value['quantity'];
				$products_to_list['status']     = $value['state'];
				$products_to_list['url']        = $value['url'];
				$products_to_list['listing_id'] = $value['listing_id'];
				$products_to_list['shop_name']  = $shop_name;
				$listing_id                     = $value['listing_id'];
				$action_images                  = "application/listings/{$listing_id}/images";
				$image_details                  = etsy_request()->get( $action_images, $shop_name );
					$products_to_list['image']  = isset( $image_details['results'][0]['url_170x135'] ) ? $image_details['results'][0]['url_170x135'] : '';
				$product_to_show[]              = $products_to_list;
				$if_product_exists              = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $value['listing_id'] );
				if ( ! empty( $if_product_exists ) ) {
					$count[] = isset( $if_product_exists ) ? $if_product_exists : '';
						// Cout imported Items
					update_option( 'ced_etsy_total_created_product_' . $shop_name, $count );
				}
			}
			return $product_to_show;
		}
	}


	public function no_items() {
		esc_html_e( 'No Products To Show.', 'woocommerce-etsy-integration' );
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count( $per_page = '', $page_number = '', $shop_name = '' ) {

		$total_items = get_option( 'ced_etsy_total_import_product_' . $shop_name, array() );
		if ( ! empty( $total_items ) ) {
			return $total_items;
		} else {
			return 0;
		}

	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */

	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	public function column_cb( $item ) {
		$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $item['shop_name'], $item['listing_id'] );
		if ( ! empty( $if_product_exists ) ) {
			update_option( 'ced_product_is_availabe_in_woo_' . $item['listing_id'], $item['listing_id'] );
			$image_path = CED_ETSY_URL . 'admin/assets/images/check.png';
			return sprintf( '<img class="check_image" src="' . $image_path . '" alt="Done">' );
		} else {
			return sprintf(
				'<input type="checkbox" name="etsy_import_products_id[]" class="etsy_import_products_id" value="%s" />',
				$item['listing_id']
			);
		}
	}
	public function column_image( $item = '' ) {

		$product_id = etsy_get_product_id_by_shopname_and_listing_id( $item['shop_name'], $item['listing_id'] );
		if ( ! empty( $product_id ) ) {
			$product   = wc_get_product( $product_id );
			$image_id  = $product->get_image_id();
			$image_url = wp_get_attachment_image_url( $image_id );
			echo '<a><img src="' . esc_url( $image_url ) . '" height="50" width="50" ></a>';

		} elseif ( isset( $item['image'] ) && ! empty( $item['image'] ) ) {
			echo '<a><img src="' . esc_url( $item['image'] ) . '" height="50" width="50" ></a>';
		} else {
			$image_path = CED_ETSY_URL . 'admin/assets/images/etsy.png';
			return sprintf( '<img height="35" width="60" src="' . $image_path . '" alt="Done">' );
		}

	}

	public function column_name( $item ) {

		$product_id    = etsy_get_product_id_by_shopname_and_listing_id( $item['shop_name'], $item['listing_id'] );
		$product_id    = isset( $product_id ) ? $product_id : '';
		$editUrl       = get_edit_post_link( $product_id, '' );
		$actions['id'] = 'ID:' . $item['listing_id'];
		if ( ! empty( $product_id ) ) {
			$editUrl = $editUrl;
		} else {
			$editUrl = $item['url'];
		}

		$actions['import'] = '<a href="' . $editUrl . '" class="import_single_product" data-listing-id="' . $item['listing_id'] . '"> Import</a>';
		echo '<b><a class="ced_etsy_prod_name" href="' . esc_attr( $editUrl ) . '" >' . esc_attr( $item['name'] ) . '</a></b>';
		return $this->row_actions( $actions, true );

	}

	public function column_stock( $item ) {
		return $item['stock'];
	}

	public function column_price( $item ) {
		$price = isset( $item['price'] ) ? $item['price'] : '';
		echo esc_attr( $price );
	}
	public function column_status( $item ) {
		$status = $item['status'];
		if ( ! empty( $status ) ) {
			echo esc_attr( $item['status'] );
		}
	}

	public function column_view_url( $item ) {
		$etsy_icon = CED_ETSY_URL . 'admin/assets/images/etsy.png';
			echo '<a href="' . esc_url( $item['url'] ) . '" target="_blank"><img src="' . esc_url( $etsy_icon ) . '" height="35" width="60"></a>';
	}


	public function column_update_inventory_etsy_to_woo( $item ) {
		$product_id = etsy_get_product_id_by_shopname_and_listing_id( $item['shop_name'], $item['listing_id'] );
		if ( ! empty( $product_id ) ) {
			$update = '<a class="button-primary update_inventory_etsy_to_wooc" data-listing-id ="' . $item['listing_id'] . '" href="javascript:void(0)">' . __( 'Update', 'woocommerce-etsy-integration' ) . '</a>';
			return $update;
		} else {
			return;
		}
	}

	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( 'Image', 'woocommerce-etsy-integration' ),
			'name'     => __( 'Name', 'woocommerce-etsy-integration' ),
			'price'    => __( 'Price', 'woocommerce-etsy-integration' ),
			'stock'    => __( 'Stock', 'woocommerce-etsy-integration' ),
			'status'   => __( 'Status', 'woocommerce-etsy-integration' ),
			'view_url' => __( ' View Link', 'woocommerce-etsy-integration' ),
		);
		return $columns;
	}

	protected function bulk_actions( $which = '' ) {
		if ( 'top' == $which ) :
			if ( is_null( $this->_actions ) ) {
				$this->_actions = $this->get_bulk_actions();
				/**
				 * Filters the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @since 3.5.0
				 *
				 * @param array $actions An array of the available bulk actions.
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			echo '<label for="bulk-import-action-selector' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html( __( 'Select bulk action' ) ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-import-action-selectorf">';
			echo '<option value="-1">' . esc_html( __( 'Bulk Actions' ) ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_esty_import_product_bulk_operation' ) );
			echo "\n";
		endif;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'import_product' => 'Import Product',
		);
		return $actions;
	}
	public function renderHTML() {
		?>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
			<div class="ced_etsy_child_element parent_default default_modal">
				<?php
				$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';

				$instructions = array(
					'Etsy products will be displayed here.By default active products are displayed.',
					'You can fetch the etsy product manually by selecting it using the checkbox on the left side in the product list table and using import operation from the <a>Bulk Actions</a> dropdown and the <a>Apply</a> or also you can enable the auto product import feature in Crons <a href="' . esc_url( admin_url( 'admin.php?page=ced_etsy&section=settings&shop_name=' . esc_attr( $activeShop ) ) ) . '">here</a>.',
					'You can filter out the etsy products on the basis of the status using <a>Import by status</a> dropdown.',
				);

				echo '<ul class="ced_etsy_instruction_list" type="disc">';
				foreach ( $instructions as $instruction ) {
					print_r( "<li>$instruction</li>" );
				}
				echo '</ul>';

				?>
			</div>
		</div>
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php
					$shop_name                = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
					$status_actions           = array(
						'draft'    => __( 'Draft', 'woocommerce-etsy-integration' ),
						'active'   => __( 'Active', 'woocommerce-etsy-integration' ),
						'inacitve' => __( 'Inactive', 'woocommerce-etsy-integration' ),
						'expired'  => __( 'Expired', 'woocommerce-etsy-integration' ),
					);
					$previous_selected_status = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : 'active';

					echo '<div class="ced_etsy_wrap">';
					echo '<form method="post" action="">';
					wp_nonce_field( 'manage_products', 'manage_product_filters' );

					$total_created_product    = get_option( 'ced_etsy_total_created_product_' . $shop_name );
					$total_created_product    = isset( $total_created_product ) ? $total_created_product : array();
					$total_etsy_total_product = get_option( 'ced_etsy_total_import_product_' . $shop_name );
					echo '<div class="ced_etsy_top_wrapper">';
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_html( __( 'Import By Status', 'woocommerce-etsy-integration' ) ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';
					submit_button( __( ' Filter', 'ced-etsy' ), 'action', 'filter_button', false, array() );
					echo '</div>';
					echo '</form>';
					echo '</div>';
					?>
					<form method="post">
						<?php
						$this->display();
						?>
					</form>
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="ced_etsy_preview_product_popup_main_wrapper"></div>
		<?php
	}
}

$ced_etsy_import_products_obj = new EtsyListImportedProducts();
$ced_etsy_import_products_obj->prepare_items();
