<div class="ced_etsy_heading">
	<div class="ced_etsy_render_meta_keys_wrapper ced_etsy_global_wrap">
		<div class="ced_etsy_parent_element">
			<h2>
				<label class="basic_heading ced_etsy_render_meta_keys_toggle"><?php esc_html_e( 'Search Product custom fields and attributes', 'etsy-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_etsy_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_etsy_child_element">
			<table class="wp-list-table widefat fixed ced_etsy_config_table">
				<tr>
					<td><label>Search for the product by its title</label></td>
					<td colspan="2"><input type="text" name="" id="ced_etsy_search_product_name">
						<ul class="ced-etsy-search-product-list">
						</ul>
					</td>
				</tr>
			</table>
			<div class="ced_etsy_render_meta_keys_content">
				<?php
				$meta_keys_to_be_displayed = get_option( 'ced_etsy_metakeys_to_be_displayed', array() );
				$added_meta_keys           = get_option( 'ced_etsy_selected_metakeys', array() );
				$metakey_html              = ced_etsy_render_html( $meta_keys_to_be_displayed, $added_meta_keys );
				print_r( $metakey_html );
				?>
			</div>
		</div>
	</div>
</div>
