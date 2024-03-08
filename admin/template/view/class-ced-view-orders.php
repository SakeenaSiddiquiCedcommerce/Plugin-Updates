<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$header = new \Cedcommerce\Template\View\Ced_View_Header();


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Ced_Etsy_List_Orders extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Etsy Order', 'woocommerce-etsy-integration' ), // singular name of the listed records
				'plural'   => __( 'Etsy Orders', 'woocommerce-etsy-integration' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}
	/**
	 *
	 * Function for preparing data to be displayed
	 */
	public function prepare_items() {

		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::get_orders( $per_page, $current_page );
		$count       = self::get_count();
		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {

			$this->renderHTML();
		}

	}
	/*
	*
	* Text displayed when no  data is available
	*
	*/
	public function no_items() {
		esc_html_e( 'No Orders To Display.', 'woocommerce-etsy-integration' );
	}
	/**
	 *
	 * Function for id column
	 */
	public function column_id( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			echo '<b>' . esc_attr( $displayOrders['order_id'] ) . '</b>';
			break;
		}
	}
	/**
	 *
	 * Function for name column
	 */
	public function column_name( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$productId     = $displayOrders['product_id'];
			$url           = get_edit_post_link( $productId, '' );
			echo '<b><a class="ced_etsy_prod_name" href="' . esc_attr( $url ) . '" target="#">' . esc_attr( $displayOrders['name'] ) . '</a></b></br>';

		}
	}
	/**
	 *
	 * Function for order Id column
	 */
	public function column_etsy_order_id( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders   = $value->get_data();
			$orderID         = $displayOrders['order_id'];
			$details         = wc_get_order( $orderID );
			$details         = $details->get_data();
			$order_meta_data = $details['meta_data'];
			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_id = $value1->get_data();
				if ( 'merchant_order_id' == $order_id['key'] ) {
					echo '<b>' . esc_attr( $order_id['value'] ) . '</b>';

				}
			}
			break;
		}
	}
	/**
	 *
	 * Function for order status column
	 */
	public function column_order_status( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders   = $value->get_data();
			$orderID         = $displayOrders['order_id'];
			$details         = wc_get_order( $orderID );
			$details         = $details->get_data();
			$order_meta_data = $details['meta_data'];
			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_status = $value1->get_data();
				if ( '_etsy_umb_order_status' == $order_status['key'] ) {
					echo '<b>' . esc_attr( $order_status['value'] ) . '</b>';
				}
			}
			break;
		}
	}
	/**
	 *
	 * Function for Edit order column
	 */
	public function column_action( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$woo_order_url = get_edit_post_link( $displayOrders['order_id'], '' );
			echo '<a href="' . esc_url( $woo_order_url ) . '" target="_blank">Edit</a>';
			break;
		}
	}
	/**
	 *
	 * Function for customer name column
	 */
	public function column_customer_name( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$details       = wc_get_order( $orderID );
			$details       = $details->get_data();
			echo '<b>' . esc_attr( $details['billing']['first_name'] ) . '</b>';
			break;
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {
		$columns = array(
			'id'            => __( 'WooCommerce Order', 'woocommerce-etsy-integration' ),
			'name'          => __( 'Product Name', 'woocommerce-etsy-integration' ),
			'etsy_order_id' => __( 'Etsy Order ID', 'woocommerce-etsy-integration' ),
			'customer_name' => __( 'Customer Name', 'woocommerce-etsy-integration' ),
			'order_status'  => __( 'Order Status', 'woocommerce-etsy-integration' ),
			'action'        => __( 'Action', 'woocommerce-etsy-integration' ),
		);
		return $columns;
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
	public function renderHTML() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		?>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
			<div class="ced_etsy_child_element parent_default default_modal">
				<?php
				$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';

				$instructions = array(
					'Etsy orders will be displayed here.',
					'You can fetch the etsy orders manually by clicking the fetch order button or also you can enable the auto fetch order feature in Crons <a href="' . esc_url( admin_url( 'admin.php?page=ced_etsy&section=settings&shop_name=' . esc_attr( $activeShop ) ) ) . '">here</a>.',
					'Make sure you have the skus present in all your products/variations for order syncing.',
					'You can also submit the tracking details from WooCommerce to etsy . You need to go in the order edit section using <a>Edit</a> option in the order table below.Once you go in order edit section you will find the section at the bottom where you can enter tracking info and update them on etsy.',
				);

				echo '<ul class="ced_etsy_instruction_list" type="disc">';
				foreach ( $instructions as $instruction ) {
					print_r( "<li>$instruction</li>" );
				}
				echo '</ul>';

				?>
			</div>
		</div>
		<div class="ced_etsy_wrap ced_etsy_wrap_extn">
			<?php echo '<button  class="button-primary" id="ced_etsy_fetch_orders" data-id="' . esc_attr( $shop_name ) . '" >' . esc_html( __( 'Fetch Orders', 'woocommerce-etsy-integration' ) ) . '</button>'; ?>
			<div id="post-body" class="metabox-holder columns-2">
				<div id="">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
							$this->display();
							?>
						</form>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public function get_count() {
		global $wpdb;
		$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s", 'ced_etsy_order_shop_id', $shop_name ), 'ARRAY_A' );
		return count( $orders_post_id );
	}

	/*
	 *
	 *  Function to get all the orders
	 *
	 */
	public function get_orders( $per_page, $current_page ) {
		global $wpdb;
		$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$offset         = ( $current_page - 1 ) * $per_page;
		$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  order by `post_id` DESC LIMIT %d OFFSET %d", 'ced_etsy_order_shop_id', $shop_name, $per_page, $offset ), 'ARRAY_A' );

		foreach ( $orders_post_id as $key => $value ) {
			$post_id        = isset( $value['post_id'] ) ? $value['post_id'] : '';
			$post_details   = wc_get_order( $post_id );
			$order_detail[] = $post_details->get_items();
		}
		$order_detail = isset( $order_detail ) ? $order_detail : '';
		return( $order_detail );
	}
}

$ced_etsy_orders_obj = new Ced_Etsy_List_Orders();
$ced_etsy_orders_obj->prepare_items();

