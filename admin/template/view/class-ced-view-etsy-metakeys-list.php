<?php

$product_meta_data = array();

$product_object = wc_get_product( $product_id );
if ( is_object( $product_object ) ) {
	$product_type       = $product_object->get_type();
	$product_attributes = $product_object->get_attributes();
	if ( is_array( $product_attributes ) && ! empty( $product_attributes ) ) {
		foreach ( $product_attributes as $slug => $attribute_object ) {
			if ( strpos( $slug, 'pa_' ) === false ) {
				if ( is_object( $attribute_object ) ) {
					$attribute_data = $attribute_object->get_data();
					$product_meta_data['attributes'][ $attribute_data['name'] ][0] = isset( $attribute_data['options'][0] ) ? $attribute_data['options'][0] : '';
				}
			}
		}
	}

		$product_custom_data           = get_post_custom( $product_id );
		$product_meta_data['metakeys'] = $product_custom_data;
}

$added_meta_keys = get_option( 'ced_etsy_selected_metakeys', array() );
if ( isset( $product_meta_data['metakeys'] ) ) {
	$meta_keys_to_be_displayed = array_merge( $product_meta_data['metakeys'], $added_meta_keys );
} elseif ( isset( $product_meta_data['attributes'] ) ) {
	$meta_keys_to_be_displayed = array_merge( $product_meta_data['attributes'], $added_meta_keys );
}
update_option( 'ced_etsy_metakeys_to_be_displayed', $meta_keys_to_be_displayed );
$metakey_html = ced_etsy_render_html( $meta_keys_to_be_displayed, $added_meta_keys );
echo json_encode(
	array(
		'html' => $metakey_html,
	)
);
die();

