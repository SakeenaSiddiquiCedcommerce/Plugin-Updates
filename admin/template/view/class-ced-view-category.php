<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$header                   = new \Cedcommerce\Template\View\Ced_View_Header();
$woo_store_categories     = get_terms( 'product_cat' );
$etsyFirstLevelCategories = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-1.json' );
$etsyFirstLevelCategories = json_decode( $etsyFirstLevelCategories, true );

?>
<div class="ced_etsy_heading">
<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
<div class="ced_etsy_child_element parent_default default_modal">
	<?php
				$activeShop   = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
				$profile_url  = admin_url( 'admin.php?page=ced_etsy&section=profiles&shop_name=' . esc_attr( $activeShop ) );
				$instructions = array(
					'In this section you will need to map the WooCommerce store categories to the etsy categories.',
					'You need to select the WooCommerce category using the checkbox on the left side and list of etsy categories will appear in dropdown.Select the etsy category in which you want to list the products of the selected WooCommerce category on etsy.',
					'Click Save mapping option at the bottom.' .
					'Once you map the categories profiles will automatically be created and you can use the <a href="' . esc_url( $profile_url ) . '" >Profiles</a> in order to override the Global Settings.',
				);

				echo '<ul class="ced_etsy_instruction_list" type="disc">';
				foreach ( $instructions as $instruction ) {
					print_r( "<li>$instruction</li>" );
				}
				echo '</ul>';

				?>
</div>
</div>
<div class="ced_etsy_category_mapping_wrapper" id="ced_etsy_category_mapping_wrapper">

	<div class="ced_etsy_store_categories_listing" id="ced_etsy_store_categories_listing">
		<table class="wp-list-table widefat fixed striped posts ced_etsy_store_categories_listing_table" id="ced_etsy_store_categories_listing_table">
			<thead>
				<th><b><?php esc_html_e( 'Select', 'woocommerce-etsy-integration' ); ?></b></th>
				<th><b><?php esc_html_e( 'Store Categories', 'woocommerce-etsy-integration' ); ?></b></th>
				<th colspan="3"><b><?php esc_html_e( 'Mapped to Etsy Category', 'woocommerce-etsy-integration' ); ?></b></th>
			</thead>
			<tbody>
				<?php

				function ced_etsy_categories_tree( $value, $cat_name ) {
					if ( 0 != $value->parent ) {
						$parent_id = $value->parent;
						$sbcatch2  = get_term( $parent_id );
						$cat_name  = $sbcatch2->name . ' --> ' . $cat_name;
						if ( 0 != $sbcatch2->parent ) {
							$cat_name = ced_etsy_categories_tree( $sbcatch2, $cat_name );
						}
					}
					return $cat_name;
				}


				foreach ( $woo_store_categories as $key => $value ) {

					$cat_name = $value->name;
					$cat_name = ced_etsy_categories_tree( $value, $cat_name );

					?>
					<tr class="ced_etsy_store_category" id="<?php echo esc_attr( 'ced_etsy_store_category_' . $value->term_id ); ?>">
						<td>
							<input type="checkbox" class="ced_etsy_select_store_category_checkbox" name="ced_etsy_select_store_category_checkbox[]" data-categoryID="<?php echo esc_attr( $value->term_id ); ?>"></input>
						</td>
						<td>
							<span class="ced_etsy_store_category_name"><?php echo esc_attr( $cat_name ); ?></span>
						</td>
						<?php
						$shop_name                   = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
						$category_mapped_to          = get_term_meta( $value->term_id, 'ced_etsy_mapped_category_' . $shop_name, true );
						$alreadyMappedCategoriesName = get_option( 'ced_woo_etsy_mapped_categories_name_' . $shop_name, array() );
						$category_mapped_name_to     = isset( $alreadyMappedCategoriesName[ $shop_name ][ $category_mapped_to ] ) ? $alreadyMappedCategoriesName[ $shop_name ][ $category_mapped_to ] : '';
						if ( ! empty( $category_mapped_to ) && null != $category_mapped_to && ! empty( $category_mapped_name_to ) && null != $category_mapped_name_to ) {
							?>
							<td colspan="4">
								<span>
									<b><?php echo esc_attr( $category_mapped_name_to ); ?></b>
								</span>
							</td>
							<?php
						} else {
							?>
							<td colspan="4">
								<span class="ced_etsy_category_not_mapped">
									<?php esc_html_e( 'Category Not Mapped', 'woocommerce-etsy-integration' ); ?>
								</span>
							</td>
							<?php
						}
						?>
					</tr>

					<tr class="ced_etsy_categories" id="<?php echo esc_attr( 'ced_etsy_categories_' . $value->term_id ); ?>">
						<td></td>
						<td data-catlevel="1">
							<select class="ced_etsy_level1_category ced_etsy_select_category select2 ced_etsy_select2 select_boxes_cat_map" name="ced_etsy_level1_category[]" data-level=1 data-storeCategoryID="<?php echo esc_attr( $value->term_id ); ?>" data-storeName="<?php echo esc_attr( $shop_name ); ?>" >
								<option value="">--<?php esc_html_e( 'Select', 'woocommerce-etsy-integration' ); ?>--</option>
								<?php
								foreach ( $etsyFirstLevelCategories as $key1 => $value1 ) {
									if ( isset( $value1['name'] ) && ! empty( $value1['name'] ) ) {
										?>
										<option value="<?php echo esc_attr( $value1['id'] ); ?>"><?php echo esc_attr( $value1['name'] ); ?></option>	
										<?php
									}
								}
								?>
							</select>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
		<div class="ced_etsy_category_mapping_header ced_etsy_hidden" id="ced_etsy_category_mapping_header">
		<a class="button-primary" href="" data-etsyStoreName="<?php echo esc_attr( $shop_name ); ?>" id="ced_etsy_cancel_category_button">
			<?php esc_html_e( 'Cancel', 'woocommerce-etsy-integration' ); ?>
		</a>
		<button class="button-primary" data-etsyStoreName="<?php echo esc_attr( $shop_name ); ?>" id="ced_etsy_save_category_button">
			<?php esc_html_e( 'Save Mapping', 'woocommerce-etsy-integration' ); ?>
		</button>
	</div>

</div>
