<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$header    = new \Cedcommerce\Template\View\Ced_View_Header();
$profileID = isset( $_GET['profileID'] ) ? sanitize_text_field( wp_unslash( $_GET['profileID'] ) ) : '';
global $wpdb;
$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
$tableName = $wpdb->prefix . 'ced_etsy_profiles';

if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_etsy_profile_save_button'] ) ) {

	if ( ! isset( $_POST['profile_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_settings_submit'] ) ), 'ced_etsy_profile_save_button' ) ) {
		return;
	}
	$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	$profileName     = $sanitized_array['ced_etsy_profile_name'];
	$marketplaceName = isset( $sanitized_array['marketplaceName'] ) ? $sanitized_array['marketplaceName'] : 'all';
	foreach ( $sanitized_array['ced_etsy_required_common'] as $key ) {
		$arrayToSave = array();
		isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $sanitized_array[ $key ][0] : $arrayToSave['default'] = '';
		if ( '_umb_' . $marketplaceName . '_subcategory' == $key ) {
			isset( $sanitized_array[ $key ] ) ? $arrayToSave['default'] = $sanitized_array[ $key ] : $arrayToSave['default'] = '';
		}
		if ( '_umb_etsy_category' == $key && empty( $profileID ) ) {
			$profileCategoryNames = array();
			for ( $i = 1; $i < 8; $i++ ) {
				$profileCategoryNames[] = isset( $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] ) ? $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] : '';
			}
			$CategoryNames = array();
			foreach ( $profileCategoryNames as $key1 => $value1 ) {
				$CategoryNames[] = explode( ',', $value1[0] );
			}
			foreach ( $CategoryNames as $key2 => $value2 ) {
				if ( ! empty( $CategoryNames[ $key2 ][0] ) ) {
					$profile_category_id = $CategoryNames[ $key2 ][0];
				}
			}
			$category_id = $profile_category_id;
			isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $category_id : $arrayToSave['default'] = '';

		}
		isset( $sanitized_array[ $key . '_attibuteMeta' ] ) ? $arrayToSave['metakey'] = $sanitized_array[ $key . '_attibuteMeta' ] : $arrayToSave['metakey'] = 'null';
		$updateinfo[ $key ] = $arrayToSave;
	}

	$updateinfo['selected_product_id']   = isset( $sanitized_array['selected_product_id'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['selected_product_id'] ) ) : '';
	$updateinfo['selected_product_name'] = isset( $sanitized_array['ced_sears_pro_search_box'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['ced_sears_pro_search_box'] ) ) : '';
	$updateinfo                          = json_encode( $updateinfo );
	if ( empty( $profileID ) ) {
		$profileCategoryNames = array();
		for ( $i = 1; $i < 8; $i++ ) {
			$profileCategoryNames[] = isset( $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] ) ? $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] : '';
		}
		$CategoryNames = array();
		foreach ( $profileCategoryNames as $key => $value ) {
			$CategoryNames[] = explode( ',', $value[0] );
			if ( ! empty( $CategoryNames[ $key ][1] ) ) {
				$CategoryName .= $CategoryNames[ $key ][1] . '-->';
			}
		}
		$catl        = strlen( $CategoryName );
		$profileName = substr( $CategoryName, 0, -3 );
		foreach ( $CategoryNames as $key1 => $value1 ) {
			if ( ! empty( $CategoryNames[ $key1 ][0] ) ) {
				$profile_category_id = $CategoryNames[ $key1 ][0];
			}
		}
		$profile_category_id = $profile_category_id;


		$profileDetails = array(
			'profile_name'   => $profileName,
			'profile_status' => 'active',
			'profile_data'   => $updateinfo,
			'shop_name'      => $shop_name,
		);

		global $wpdb;
		$profileTableName = $wpdb->prefix . 'ced_etsy_profiles';
		$wpdb->insert( $profileTableName, $profileDetails );
		$profileId = $wpdb->insert_id;

		$profile_edit_url = admin_url( 'admin.php?page=ced_etsy&profileID=' . $profileId . '&section=profiles&panel=edit&shop_name=' . $shop_name );
		header( 'location:' . $profile_edit_url . '' );
	} elseif ( $profileID ) {
		$wpdb->update(
			$tableName,
			array(
				'profile_name'   => $profileName,
				'profile_status' => 'Active',
				'profile_data'   => $updateinfo,
			),
			array( 'id' => $profileID )
		);
	}
	$sanitized_array['ced_etsy_settings_category']['required'] = 'on';
		update_option( 'ced_etsy_settings_category', $sanitized_array['ced_etsy_settings_category'] );
}
$etsyFirstLevelCategories = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-1.json' );
$etsyFirstLevelCategories = json_decode( $etsyFirstLevelCategories, true );

$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%s ", $profileID ), 'ARRAY_A' );

if ( ! empty( $profile_data ) ) {
	$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
	$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
	$profile_category_id   = isset( $profile_category_data['_umb_etsy_category']['default'] ) ? (int) $profile_category_data['_umb_etsy_category']['default'] : '';

	$profile_data = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
	/** Refresh token
				 *
				 * @since 2.0.0
				 */
	do_action( 'ced_etsy_refresh_token', $shop_name );
	$_action                   = "application/seller-taxonomy/nodes/{$profile_category_id}/properties";
	$getTaxonomyNodeProperties = etsy_request()->get( $_action, $shop_name );
	$getTaxonomyNodeProperties = $getTaxonomyNodeProperties['results'];
	$product_instance_field    = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
	$taxonomyList              = $product_instance_field->get_taxonomy_node_properties( $getTaxonomyNodeProperties );
}
$attributes    = wc_get_attribute_taxonomies();
$attrOptions   = array();
$addedMetaKeys = get_option( 'ced_etsy_selected_metakeys', array() );
$addedMetaKeys = array_merge( $addedMetaKeys, array( '_woocommerce_title', '_woocommerce_short_description', '_woocommerce_description' ) );

if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
	foreach ( $addedMetaKeys as $metaKey ) {
		$attrOptions[ $metaKey ] = $metaKey;
	}
}
if ( ! empty( $attributes ) ) {
	foreach ( $attributes as $attributesObject ) {
		$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
	}
}


/* select dropdown setup */
ob_start();
$fieldID  = '{{*fieldID}}';
$selectId = $fieldID . '_attibuteMeta';
echo '<select id="' . esc_attr( $selectId ) . '" name="' . esc_attr( $selectId ) . '">';
echo '<option value="null"> -- select -- </option>';
if ( is_array( $attrOptions ) ) {
	foreach ( $attrOptions as $attrKey => $attrName ) :
		echo '<option value="' . esc_attr( $attrKey ) . '">' . esc_attr( $attrName ) . '</option>';
	endforeach;
}
echo '</select>';
$selectDropdownHTML     = ob_get_clean();
$product_instance_field = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
$settings               = $product_instance_field->get_custom_products_fields();
?>

<?php
$ced_h                   = new Cedhandler();
		$ced_h->dir_name = '/admin/template/view/';
		$ced_h->ced_require( 'ced-etsy-metakeys-template' );
?>
<form action="" method="post">
	<?php wp_nonce_field( 'ced_etsy_profile_save_button', 'profile_settings_submit' ); ?>

	<div class="ced_etsy_heading">
		<?php echo esc_html_e( get_etsy_instuctions_html( 'BASIC INFORMATION' ) ); ?>
		<div class="ced_etsy_child_element default_modal">
			<table class="wp-list-table fixed widefat ced_etsy_config_table">
				<tr>
					<td>
						<label><?php esc_html_e( 'Profile Name', 'woocommerce-etsy-integration' ); ?></label>
					<?php

					if ( isset( $profile_data['profile_name'] ) ) {
						?>
							<p><input type="text" name="ced_etsy_profile_name" value="<?php echo esc_attr( $profile_data['profile_name'] ); ?>"></p>
						</td>
					
						<?php
					}
					?>
					<td>
						<label><?php esc_html_e( 'Profile ID', 'woocommerce-etsy-integration' ); ?></label>
						<?php
						if ( isset( $profile_data['profile_name'] ) ) {
							?>
							<p><span><?php echo esc_attr( $profile_data['id'] ); ?></span> </p>
							<?php
						}
						?>
					</td>
					<td>
						<label><?php esc_html_e( 'Mapped WooCommerce Categories', 'woocommerce-etsy-integration' ); ?></label>
						<?php
						if ( isset( $profile_data['woo_categories'] ) ) {
							$woo_categories = json_decode( $profile_data['woo_categories'], true );
							foreach ( $woo_categories as $term_id ) {
								echo '<p>' . esc_attr( get_term( $term_id )->name ) . '</p>';
							}
							?>
						
						</td>
					</tr>
							<?php
						}
						?>
					</td>
					</tr>
				
				</table>
			</div>
		</div>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html( 'Product Export Settings' ) ); ?>
			<div class="ced_etsy_child_element default_modal">

					<?php
					$requiredInAnyCase          = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
					$global_settings_field_data = get_option( 'ced_etsy_global_settings', '' );
					$marketPlace                = 'ced_etsy_required_common';
					$productID                  = 0;
					$categoryID                 = '';
					$indexToUse                 = 0;

					if ( ! empty( $profile_data ) ) {
						$data = json_decode( $profile_data['profile_data'], true );
					}

					$ced_etsy_settings_category                     = get_option( 'ced_etsy_settings_category', array() );
							$ced_etsy_settings_category['required'] = 'on';
							echo '<thead>';
							echo '<tr><td>';
							echo '<p><b><i><u>Attributes to display</u></i> </b></p>';
							$settings_category = array_keys( $settings );
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
					foreach ( $settings as $section => $fields ) {
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

							foreach ( $fields as $value ) {

								if ( '_ced_etsy_product_list_type' == $value['id'] ) {
									continue;
								}

								$isText   = false;
								$field_id = trim( $value['fields']['id'], '_' );
								if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
									$attributeNameToRender  = ucfirst( $value['fields']['label'] );
									$attributeNameToRender .= '<span class="ced_etsy_wal_required"> [ Required ]</span>';
								} else {
									$attributeNameToRender = ucfirst( $value['fields']['label'] );
								}
								$is_required = isset( $value['fields']['is_required'] ) ? $value['fields']['is_required'] : false;
								$default     = isset( $data[ $value['fields']['id'] ]['default'] ) ? $data[ $value['fields']['id'] ]['default'] : '';
								echo '<tr class="form-field _umb_id_type_field ">';
								if ( '_select' == $value['type'] ) {
									$valueForDropdown = $value['fields']['options'];
									if ( '_umb_id_type' == $value['fields']['id'] ) {
										unset( $valueForDropdown['null'] );
									}
									$product_instance_field->renderDropdownHTML(
										$field_id,
										$attributeNameToRender,
										$valueForDropdown,
										$categoryID,
										$productID,
										$marketPlace,
										$value['fields']['description'],
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$is_required
									);


								} elseif ( '_text_input' == $value['type'] ) {
									$isText = true;
									$product_instance_field->renderInputTextHTML(
										$field_id,
										$attributeNameToRender,
										$categoryID,
										$productID,
										$marketPlace,
										$value['fields']['description'],
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$is_required
									);
								} elseif ( '_checkbox' == $value['type'] ) {
									$product_instance_field->rendercheckboxHTML(
										$field_id,
										$attributeNameToRender,
										$categoryID,
										$productID,
										$marketPlace,
										$value['fields']['description'],
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$is_required
									);
									$isText = true;
								} elseif ( '_hidden' == $value['type'] ) {

									$profile_category_id = isset( $profile_category_id ) ? $profile_category_id : '';
									$product_instance_field->renderInputTextHTMLhidden(
										$field_id,
										$attributeNameToRender,
										$categoryID,
										$productID,
										$marketPlace,
										$value['fields']['description'],
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $profile_category_id,
										),
										$is_required
									);
									$isText = false;
								}

								echo '<td>';
								if ( $isText ) {
									$previousSelectedValue = 'null';
									if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && ! empty( $data[ $value['fields']['id'] ]['metakey'] ) ) {
										$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
									}
									$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
									$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
									print_r( $updatedDropdownHTML );
								}
								echo '</td>';
								echo '</tr>';

							}
							echo '</tbody>';
								echo '</table>';
					}
					?>
			</div>
		</div>
		<div class="ced_etsy_heading ced_etsy_category_specific">
			<?php echo esc_html_e( get_etsy_instuctions_html( 'Category specific' ) ); ?>
		<div class="ced_etsy_child_element">
			<table class="wp-list-table ced_etsy_global_settings">
				<tr>
						<td><b>Etsy Attribute</b></td>
						<td><b>Default Value</b></td>
						<td><b>Pick Value From Custom field or Attribute</b></td>
					</tr>
				<?php
				if ( ! empty( $profileID ) ) {

					if ( ! empty( $taxonomyList ) ) {
						foreach ( $taxonomyList as $key => $value ) {

							$isText   = true;
							$field_id = trim( $value['fields']['id'], '_' );
							$default  = isset( $data[ $value['fields']['id'] ] ) ? $data[ $value['fields']['id'] ] : '';
							$default  = isset( $default['default'] ) ? $default['default'] : '';
							echo '<tr class="form-field _umb_brand_field ">';
							if ( '_select' == $value['type'] ) {
								$valueForDropdown     = $value['fields']['options'];
								$tempValueForDropdown = array();
								foreach ( $valueForDropdown as $key => $_value ) {
									$tempValueForDropdown[ $key ] = $_value;
								}
								$valueForDropdown = $tempValueForDropdown;

								$product_instance_field->renderDropdownHTML(
									$field_id,
									ucfirst( $value['fields']['label'] ),
									$valueForDropdown,
									$categoryID,
									$productID,
									$marketPlace,
									$value['fields']['description'],
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									)
								);
								$isText = true;
							} else {
								continue;
								$product_instance_field->renderInputTextHTML(
									$field_id,
									ucfirst( $value['fields']['label'] ),
									$categoryID,
									$productID,
									$marketPlace,
									$value['fields']['description'],
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									)
								);
							}

							echo '<td>';
							if ( $isText ) {
								$previousSelectedValue = 'null';
								if ( isset( $data[ $value['fields']['id'] ] ) && 'null' != $data[ $value['fields']['id'] ] ) {
									$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
								}
								$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
								$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
								print_r( $updatedDropdownHTML );
							}
							echo '</td>';
							echo '</tr>';
						}
					}
				}
				?>

			</table>
		</div>
		</div>
		<div class="ced-button-wrapper">
			<button  name="ced_etsy_profile_save_button" class="button-primary"><?php esc_html_e( 'Update Profile', 'woocommerce-etsy-integration' ); ?></button>			
		</div>

	</form>
