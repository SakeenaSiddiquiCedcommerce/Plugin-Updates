<?php
namespace Cedcommerce\Template\View;

use WP_List_Table;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! session_id() ) {
	session_start();
}
class Ced_View_Etsy_Accounts extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'etsy Account', 'woocommerce-etsy-integration' ), // singular name of the listed records
				'plural'   => __( 'etsy Accounts', 'woocommerce-etsy-integration' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {
		global $wpdb;
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

		$this->items = self::get_accounts( $per_page, $current_page );

		$count = self::get_count();

		// Set the pagination

		if ( ! $this->current_action() ) {

			$this->set_pagination_args(
				array(
					'total_items' => $count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $count / $per_page ),
				)
			);

			$accounts = array();
			$accounts = self::get_accounts( $per_page, $current_page );
			if ( ! empty( $accounts ) ) {
				$this->items = $accounts;
			}
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}



	public function get_accounts( $per_page = 10, $page_number = 1 ) {

		$result = get_option( 'ced_etsy_details', array() );
		return array_filter( $result );
	}

	/**
	 * Function to count number of responses in result
	 */
	public function get_count() {

		$result = get_option( 'ced_etsy_details', array() );
		return count( array_filter( $result ) );

	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		esc_html_e( 'No Account Linked.', 'woocommerce-etsy-integration' );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		echo ' <input type="checkbox" value="' . esc_attr( $item['details']['ced_etsy_shop_name'] ) . '" name="etsy_account_name[]" > ';
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_name( $item ) {
		echo '<b>' . esc_attr( $item['details']['ced_etsy_shop_name'] ) . '</b>';
	}

	public function column_username( $item ) {
		echo esc_attr( $item['details']['user_name'] );
	}

	public function column_userid( $item ) {
		echo esc_attr( $item['details']['user_id'] );
	}

	public function column_configure( $item ) {
		$is_reauth_needed = get_option( 'ced_etsy_reauthorize_account', '' );
		if ( 'yes' == $is_reauth_needed ) {
			$configure = '<a href="' . esc_url( ced_etsy_get_auth_url( $item['details']['ced_etsy_shop_name'] ) ) . '" class="button button-primary expired_access_token" >Re-authorize</a>';
		} else {
			$configure = '<a class="button-primary" href="' . admin_url( 'admin.php?page=ced_etsy&section=settings&shop_name=' . $item['details']['ced_etsy_shop_name'] . '' ) . '">' . __( 'Configure', 'woocommerce-etsy-integration' ) . '</a>';
		}
		return $configure;

	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'name'      => __( 'Shop Name', 'woocommerce-etsy-integration' ),
			'userid'    => __( 'Shop User ID', 'woocommerce-etsy-integration' ),
			'username'  => __( 'Shop Username', 'woocommerce-etsy-integration' ),
			'configure' => __( 'Configure', 'woocommerce-etsy-integration' ),
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

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => 'Delete',
		);
		return $actions;
	}

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {
		?>
		<div class="ced_etsy_wrap ced_etsy_wrap_extn">
			<?php
			$notice = get_transient( 'ced_etsy_add_account_notice' );
			if ( $notice ) {
				if ( 'yes' == $notice ) {
					$class   = 'notice-success';
					$message = 'Account added successfully.';
				} else {
					$class   = 'notice-error';
					$message = 'Account not added . Etsy shop name invalid.';
					update_option( 'ced_etsy_access_token', '' );
					unset( $_SESSION['OAUTH_ACCESS_TOKEN'] );
				}
				echo "<div class='notice " . esc_attr( $class ) . "'><p>" . esc_attr( $message ) . '</p></div>';
			}
			?>
			<div class="ced_etsy_setting_header cedcommerce-top-border">
				<?php esc_attr( ced_etsy_cedcommerce_logo() ); ?>
				<label class="manage_labels"><b><?php esc_html_e( 'ETSY ACCOUNT', 'woocommerce-etsy-integration' ); ?></b></label>
				<?php
				$count = self::get_count();
				if ( $count < 1 ) {
					$message = 'To start syncing your products and orders, begin by connecting your Etsy account.';
					echo '<a href="javascript:void(0)" class="ced_etsy_add_account_button ced_etsy_add_button button-primary">Connect account</a>';
				} else {
					$message = 'Start syncing your products , orders and boost sales . Click configure button to explore further .';
				}
				?>
			</div>
			<div class="ced_etsy_body">
				<?php
					echo '<h4>' . esc_attr( $message ) . '</h4>';
				?>
						
				<div id="post-body" class="metabox-holder columns-2">
					<div id="">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								wp_nonce_field( 'etsy_accounts', 'etsy_accounts_actions' );
								$this->display();
								?>
							</form>
						</div>
					</div>
					<div class="clear"></div>
				</div>
				<br class="clear">
			</div>
		</div>
		<div class="ced_etsy_add_account_popup_main_wrapper">
			<div class="ced_etsy_add_account_popup_content">
				<div class="ced_etsy_add_account_popup_header">
					<h5><?php esc_html_e( 'Authorize your Etsy Account', 'woocommerce-etsy-integration' ); ?></h5>
					<span class="ced_etsy_add_account_popup_close">X</span>
				</div>
				<div class="ced_etsy_add_account_popup_body">
					<h6>Steps to authorize your account:</h6>
					<ul>
						<li>Enter your Etsy shop name and click Authorize.</li>
						<li>On the Etsy authorization page you have to log in into Etsy seller panel.</li>
						<li>Grant Access to enable access to API.</li>
					</ul>
					<form action="" method="post">
						<?php
						wp_nonce_field( 'etsy_accounts', 'etsy_accounts_actions' );
						?>
						<div class="ced_etsy_popup_wrap">
							<div class="ced_etsy_popup_container">
								<div class="ced_etsy_popup_label"><label>Shop name</label></div>
								<div class="ced_etsy_popup_input"><input id="ced_etsy_shop_name" type="text" name="ced_etsy_shop_name" required=""></div>
							</div>
						</div>
						<div class="ced_etsy_add_account_button_wrapper">
							<input type="submit" value="Authorize" id="ced_etsy_authorise_account_button" name="ced_etsy_authorise_account_button" class="ced_etsy_add_button button-primary">
						</div>
					</form>
				</div>
			</div>
		</div>


		<?php
	}

	public function current_action() {

		if ( isset( $_GET['section'] ) ) {
			$action = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action2'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$action = isset( $_POST['action2'] ) ? sanitize_text_field( wp_unslash( $_POST['action2'] ) ) : '';
			return $action;
		}
	}

	public function process_bulk_action() {

		if ( ! session_id() ) {
			session_start();
		}

		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) || isset( $_GET['action2'] ) && 'bulk-delete' === $_GET['action2'] ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$accountNames    = isset( $sanitized_array['etsy_account_name'] ) ? $sanitized_array['etsy_account_name'] : array();
			foreach ( $accountNames as $key => $value ) {
				$shops  = get_option( 'ced_etsy_details', '' );
				$tokens = get_option( 'ced_etsy_access_token', array() );
				unset( $shops[ $value ] );
				update_option( 'ced_etsy_details', $shops );
				update_option( 'ced_etsy_access_token', '' );
				unset( $_SESSION['OAUTH_ACCESS_TOKEN'] );

			}
			$redirectURL = get_admin_url() . 'admin.php?page=ced_etsy';
			wp_redirect( $redirectURL );
		} elseif ( 'bulk-enable' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-enable' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$accountNames    = isset( $sanitized_array['etsy_account_name'] ) ? $sanitized_array['etsy_account_name'] : array();
			foreach ( $accountNames as $key => $value ) {
				$shops                             = get_option( 'ced_etsy_details', '' );
				$shops[ $value ]['account_status'] = 'Active';
				update_option( 'ced_etsy_details', $shops );
			}
			$redirectURL = get_admin_url() . 'admin.php?page=ced_etsy';
			wp_redirect( $redirectURL );
		} elseif ( 'bulk-disable' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-disable' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$accountNames    = isset( $sanitized_array['etsy_account_name'] ) ? $sanitized_array['etsy_account_name'] : array();
			foreach ( $accountNames as $key => $value ) {
				$shops                             = get_option( 'ced_etsy_details', '' );
				$shops[ $value ]['account_status'] = 'InActive';
				update_option( 'ced_etsy_details', $shops );
			}
			$redirectURL = get_admin_url() . 'admin.php?page=ced_etsy';
			wp_redirect( $redirectURL );

		} elseif ( isset( $_GET['section'] ) ) {

			$section = sanitize_text_field( wp_unslash( $_GET['section'] ) );

			switch ( $section ) {
				case 'settings':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-settings.php';
					break;
				case 'category':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-category.php';
					break;
				case 'profiles':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-profiles.php';
					break;
				case 'products':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-products.php';
					break;
				case 'orders':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-orders.php';
					break;
				case 'product-importer':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-product-importer.php';
					break;
				case 'timeline':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-timeline.php';
					break;
				case 'add-shipping-profile':
					$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-add-shipping-profile.php';
					break;
			}
			if ( isset( $file ) && file_exists( $file ) ) {
				echo "<div class='ced_etsy_body'>";
				require_once $file;
				echo '</div>';
			}
		}
	}
}

if ( isset( $_POST['ced_etsy_authorise_account_button'] ) && 'Authorize' == $_POST['ced_etsy_authorise_account_button'] ) {

	if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
		return;
	}

	$shop_name = isset( $_POST['ced_etsy_shop_name'] ) ? sanitize_text_field( $_POST['ced_etsy_shop_name'] ) : '';

	update_option( 'ced_etsy_shop_name', $shop_name );

	$auth_url = ced_etsy_get_auth_url( $shop_name );
	wp_redirect( $auth_url );
	exit;
}

