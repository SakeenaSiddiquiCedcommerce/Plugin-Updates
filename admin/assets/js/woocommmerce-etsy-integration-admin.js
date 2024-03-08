(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 var ajaxUrl      = ced_etsy_admin_obj.ajax_url;
	 var ajaxNonce    = ced_etsy_admin_obj.ajax_nonce;
	 var shop_name    = ced_etsy_admin_obj.shop_name;
	 var selected_btn = 'unchecked';
	 var parsed_response;
	 var parent = false;

	$( document ).on(
		'change',
		'#ced_etsy_auto_upload_categories' ,
		function() {
			var categories = $( this ).val();
			var shop_name  = $( this ).data( 'shop-name' );
			var operation  = 'remove';
			if ( $( this ).is( ':checked' ) ) {
				operation = 'save';
			}
			$( '.ced_etsy_loader' ).show();
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_etsy_auto_upload_categories',
						categories : categories,
						operation:operation,
						shop_name:shop_name,
					},
					type : 'POST',
					success: function( response ) {
						$( '.ced_etsy_loader' ).hide();
					}
				}
			);

		}
	);

	$( document ).on(
		'click',
		'.ced_etsy_parent_element',
		function(){
			if ($( this ).find( '.ced_etsy_instruction_icon' ).hasClass( "dashicons-arrow-down-alt2" )) {
				$( this ).find( '.ced_etsy_instruction_icon' ).removeClass( "dashicons-arrow-down-alt2" );
				$( this ).find( '.ced_etsy_instruction_icon' ).addClass( "dashicons-arrow-up-alt2" );
			} else if ($( this ).find( '.ced_etsy_instruction_icon' ).hasClass( "dashicons-arrow-up-alt2" )) {
				$( this ).find( '.ced_etsy_instruction_icon' ).addClass( "dashicons-arrow-down-alt2" );
				$( this ).find( '.ced_etsy_instruction_icon' ).removeClass( "dashicons-arrow-up-alt2" );
			}
			$( this ).next( '.ced_etsy_child_element' ).slideToggle();
		}
	);

	$( document ).on(
		'click',
		'.log_details',
		function(e){
			e.preventDefault();
			$( '.log_message' ).hide();
			$( document ).find( '.ced_etsy_add_account_popup_main_wrapper' ).addClass( 'show' );
			$( this ).next().addClass( 'show' );
			$( this ).next().slideToggle();
		}
	);

		$( document ).on(
			'click',
			'#ced_close_log_message',
			function (e) {
				e.preventDefault();
				$( '.log_message' ).hide();
			}
		);

		$( document ).on(
			'click',
			'.ced_etsy_load_more',
			function () {
				$( '.ced_etsy_loader' ).show();
				var parent  = $( document ).find( this ).attr( 'data-parent' );
				var offset  = $( document ).find( this ).attr( 'data-offset' );
				var total   = $( this ).data( 'total' );
				var element = this;
				console.log( this );
				$.ajax(
					{
						url: ajaxUrl,
						data: {
							ajax_nonce: ajaxNonce,
							parent: parent,
							offset: offset,
							total: total,
							action: 'ced_etsy_load_more_logs',
						},
						type: 'POST',
						success: function (response) {

							parsed_response = jQuery.parseJSON( response );
							if ( parsed_response.html !== "" ) {
								$( element ).attr( 'data-offset', parseInt( parsed_response.offset ) );
								setTimeout(
									function () {
										$( '.ced_etsy_loader' ).hide();
										$( '.' + parent ).find( '.ced_etsy_log_rows' ).last().after( parsed_response.html );

									},
									1000
								);

								if (parsed_response.is_disable == "yes" ) {
									$( element ).hide();
								}

							}
						}
					}
				);
			}
		);

	$( document ).on(
		'click',
		'.ced_etsy_setting_sections',
		function(){
			var target = $( this ).attr( 'data-target' );
			$( "#" + target ).slideToggle();
		}
	);

	$( document ).on(
		'hover',
		".ced_etsy_thumbnail li img",
		function(){
			$( '#preview-image img' ).attr( 'src',$( this ).attr( 'src' ).replace( ' ', '' ) );
			var cedimgSwap = [];
			var imgUrl     = "";
			$( ".ced_etsy_thumbnail li img" ).each(
				function(){
					imgUrl = this.src.replace( ' ', '' );
					cedimgSwap.push( imgUrl );
				}
			);
		}
	);

	$( document ).on(
		'keyup' ,
		'#ced_etsy_search_product_name' ,
		function() {
			var keyword = $( this ).val();
			if ( keyword.length < 3 ) {
				var html = '';
				html    += '<li>Please enter 3 or more characters.</li>';
				$( document ).find( '.ced-etsy-search-product-list' ).html( html );
				$( document ).find( '.ced-etsy-search-product-list' ).show();
				return;
			}
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						keyword : keyword,
						action : 'ced_etsy_search_product_name',
					},
					type:'POST',
					success : function( response ) {
						parsed_response = jQuery.parseJSON( response );
						$( document ).find( '.ced-etsy-search-product-list' ).html( parsed_response.html );
						$( document ).find( '.ced-etsy-search-product-list' ).show();
					}
				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'.ced_etsy_searched_product' ,
		function() {
			$( '.ced_etsy_loader' ).show();
			var post_id = $( this ).data( 'post-id' );
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						post_id : post_id,
						action : 'ced_etsy_get_product_metakeys',
					},
					type:'POST',
					success : function( response ) {
						$( '.ced_etsy_loader' ).hide();
						parsed_response = jQuery.parseJSON( response );
						$( document ).find( '.ced-etsy-search-product-list' ).hide();
						$( ".ced_etsy_render_meta_keys_content" ).html( parsed_response.html );
						$( ".ced_etsy_render_meta_keys_content" ).show();
					}
				}
			);
		}
	);

	$( document ).on(
		'change',
		'.ced_etsy_meta_key',
		function(){
			$( '.ced_etsy_loader' ).show();
			var metakey = $( this ).val();
			var operation;
			if ( $( this ).is( ':checked' ) ) {
				operation = 'store';
			} else {
				operation = 'remove';
			}

			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_etsy_process_metakeys',
						metakey : metakey ,
						operation : operation,
					},
					type : 'POST',
					success: function(response)
				{
						$( '.ced_etsy_loader' ).hide();
					}
				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'.ced_etsy_navigation' ,
		function() {
			$( '.ced_etsy_loader' ).show();
			var page_no = $( this ).data( 'page' );
			$( '.ced_etsy_metakey_body' ).hide();
			window.setTimeout( function() {$( '.ced_etsy_loader' ).hide()},500 );
			$( document ).find( '.ced_etsy_metakey_list_' + page_no ).show();
		}
	);

	$( document ).on(
		"click",
		".ced_etsy_add_account_button",
		function(){

			$( document ).find( '.ced_etsy_add_account_popup_main_wrapper' ).addClass( 'show' );

		}
	)

	$( document ).on(
		"click",
		".ced_etsy_add_account_popup_close",
		function(){

			$( document ).find( '.ced_etsy_add_account_popup_main_wrapper' ).removeClass( 'show' );

		}
	)

	$( document ).on(
		"change",
		".ced_etsy_shipping_country_id",
		function(){
			var country = $( this ).val();
			if ( 'US' == country || 'CA' == country ) {
				$( '.ced_etsy_shipping_profile_conditional' ).show( 'slow' );
			} else {
				$( '.ced_etsy_shipping_profile_conditional' ).hide( 'hide' );
			}
		}
	)

	$( document ).on(
		'click',
		'#ced_etsy_submit_shipment',
		function(){

			var can_ajax = true;
			$( '.ced_etsy_required_data' ).each(
				function() {
					if ( $( this ).val() == '' ) {
						$( this ).css( 'border' , '1px solid red' );
						can_ajax = false;
						return false;
					} else {
						$( this ).removeAttr( 'style' );
					}
				}
			);

			if (can_ajax) {
				$( this ).addClass( 'disabled' );
				$( '.ced_spinner' ).css( 'visibility' , 'visible' );
				var ced_etsy_tracking_code = $( '#ced_etsy_tracking_code' ).val();
				var ced_etsy_carrier_name  = $( '#ced_etsy_carrier_name' ).val();
				var order_id               = $( this ).data( 'order-id' );

				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action : 'ced_etsy_submit_shipment',
							ced_etsy_tracking_code: ced_etsy_tracking_code,
							ced_etsy_carrier_name:ced_etsy_carrier_name,
							order_id:order_id,
						},
						type : 'POST',
						success: function(response)
					{
							$( "#ced_etsy_submit_shipment" ).removeClass( 'disabled' );
							$( '.ced_spinner' ).css( 'visibility' , 'hidden' );
							parsed_response = jQuery.parseJSON( response );
							var classes     = classes = 'notice notice-success';
							if (parsed_response.status == 400) {
								classes = 'notice notice-error';
							}
							var html = '<div class="' + classes + '"><p>' + parsed_response.message + '</p></div>';
							$( '.ced_etsy_error' ).html( html );
							window.setTimeout( function() {window.location.reload();},5000 );
						}
					}
				);
			}
		}
	);

		$( document ).on(
			'click',
			'.ced_etsy_add_more_shop_section',
			function(){
				var repeatable = $( this ).parents( 'tr' ).clone();
				$( repeatable ).find( ".ced_etsy_shopsection_inputs" ).val( "" );
				$( repeatable ).insertAfter( $( this ).parents( 'tr' ) );
				$( this ).parent( 'td' ).remove();
			}
		);

		$( document ).on(
			'change',
			'.ced_etsy_select_store_category_checkbox',
			function(){
				var store_category_id = $( this ).attr( 'data-categoryID' );

				if ( $( this ).is( ':checked' ) ) {
					$( '#ced_etsy_categories_' + store_category_id ).show( 'slow' );
				} else {
					$( '#ced_etsy_categories_' + store_category_id ).hide( 'slow' );
				}
			}
		);

		$( document ).on(
			'click',
			'#ced_etsy_category_refresh_button',
			function()
			{
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action     : 'ced_etsy_category_refresh',
							shop_name  : shop_name
						},
						type : 'POST',
						success: function(response)
					{
							$( '.ced_etsy_loader' ).hide();
							var response  = jQuery.parseJSON( response );
							var response1 = jQuery.trim( response.message );
							if (response1 == "Shop is Not Active") {
								var notice = "";
								notice    += "<div class='notice notice-error'><p>Currently Shop is not Active . Please activate your Shop in order to refresh categories.</p></div>";
								$( ".success-admin-notices" ).append( notice );
								return;
							} else if (response.status == 200) {
								var notice = "";
								notice    += "<div class='notice notice-success'><p>Categories Updated Successfully</p></div>";
								$( ".success-admin-notices" ).append( notice );
								window.setTimeout( function(){window.location.reload()}, 2000 );
							} else {
								var notice = "";
								notice    += "<div class='notice notice-error'><p>Unable To Fetch Categories</p></div>";
								$( ".success-admin-notices" ).append( notice );
								window.setTimeout( function(){window.location.reload()}, 2000 );
							}
						}
					}
				);
			}
		);

		$( document ).on(
			'change',
			'.ced_etsy_select_category_on_add_profile',
			function(){

				var selected_etsy_category_id   = $( this ).val();
				var selected_etsy_category_name = $( this ).find( "option:selected" ).text();
				var level                       = $( this ).attr( 'data-level' );

				if ( level != '8' ) {
					$( '.ced_etsy_loader' ).show();
					$.ajax(
						{
							url : ajaxUrl,
							data : {
								ajax_nonce : ajaxNonce,
								action : 'ced_etsy_fetch_next_level_category_add_profile',
								level : level,
								name : selected_etsy_category_name,
								id : selected_etsy_category_id,
								etsy_store_id : shop_name
							},
							type : 'POST',
							success: function(response)
						{
								response = jQuery.parseJSON( response );
								$( '.ced_etsy_loader' ).hide();
								if ( response != 'No-Sublevel' ) {
									for (var i = 1; i < 10; i++) {
										$( '#ced_etsy_categories_in_profile' ).find( '.ced_etsy_level' + (parseInt( level ) + i) + '_category' ).remove();
									}
									if (response != 0 && selected_etsy_category_id != "") {
										$( '#ced_etsy_categories_in_profile' ).append( response );
									}
								} else {
									$( '#ced_etsy_categories_in_profile' ).find( '.ced_etsy_level' + (parseInt( level ) + 1) + '_category' ).remove();
								}
							}
						}
					);
				}

			}
		);

		$( document ).on(
			'change',
			'.ced_etsy_select_category',
			function(){

				var store_category_id           = $( this ).attr( 'data-storeCategoryID' );
				var selected_etsy_category_id   = $( this ).val();
				var selected_etsy_category_name = $( this ).find( "option:selected" ).text();
				var level                       = $( this ).attr( 'data-level' );
				if ( level != '10' ) {
					$( '.ced_etsy_loader' ).show();
					$.ajax(
						{
							url : ajaxUrl,
							data : {
								ajax_nonce : ajaxNonce,
								action : 'ced_etsy_fetch_next_level_category',
								level : level,
								name : selected_etsy_category_name,
								id : selected_etsy_category_id,
								store_id : store_category_id,
							},
							type : 'POST',
							success: function(response)
						{
								response = jQuery.parseJSON( response );
								$( '.ced_etsy_loader' ).hide();
								if ( response != 'No-Sublevel' ) {
									for (var i = 1; i < 10; i++) {
										$( '#ced_etsy_categories_' + store_category_id ).find( '.ced_etsy_level' + (parseInt( level ) + i) + '_category' ).closest( "td" ).remove();
									}
									if (response != 0) {
										$( '#ced_etsy_categories_' + store_category_id ).append( response );
									}
								} else {
									$( '#ced_etsy_categories_' + store_category_id ).find( '.ced_etsy_level' + (parseInt( level ) + 1) + '_category' ).remove();
								}
							}
						}
					);
				}

			}
		);

		$( document ).on(
			'click',
			'#ced_etsy_save_category_button',
			function(){

				var  etsy_category_array  = [];
				var  store_category_array = [];
				var  etsy_category_name   = [];
				var shopname              = $( this ).attr( 'data-etsyStoreName' );
				var shop_name             = jQuery.trim( shopname );
				jQuery( '.ced_etsy_select_store_category_checkbox' ).each(
					function(key) {

						if ( jQuery( this ).is( ':checked' ) ) {
							var store_category_id = $( this ).attr( 'data-categoryid' );
							var cat_level         = $( '#ced_etsy_categories_' + store_category_id ).find( "td:last" ).attr( 'data-catlevel' );

							var selected_etsy_category_id = $( '#ced_etsy_categories_' + store_category_id ).find( '.ced_etsy_level' + cat_level + '_category' ).val();

							if ( selected_etsy_category_id == '' || selected_etsy_category_id == null ) {
								selected_etsy_category_id = $( '#ced_etsy_categories_' + store_category_id ).find( '.ced_etsy_level' + (parseInt( cat_level ) - 1) + '_category' ).val();
							}
							var category_name = '';
							$( '#ced_etsy_categories_' + store_category_id ).find( 'select' ).each(
								function(key1){
									category_name += $( this ).find( "option:selected" ).text() + ' --> ';
								}
							);
							var name_len = 0;
							if ( selected_etsy_category_id != '' && selected_etsy_category_id != null ) {
								etsy_category_array.push( selected_etsy_category_id );
								store_category_array.push( store_category_id );

								name_len      = category_name.length;
								category_name = category_name.substring( 0, name_len - 5 );
								category_name = category_name.trim();
								name_len      = category_name.length;
								if ( category_name.lastIndexOf( '--Select--' ) > 0 ) {
									category_name = category_name.trim();
									category_name = category_name.replace( '--Select--', '' );
									name_len      = category_name.length;
									category_name = category_name.substring( 0, name_len - 5 );
								}
								name_len = category_name.length;
								etsy_category_name.push( category_name );
							}
						}
					}
				);
				$( '.ced_etsy_loader' ).show();
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action : 'ced_etsy_map_categories_to_store',
							etsy_category_array : etsy_category_array,
							store_category_array : store_category_array,
							etsy_category_name : etsy_category_name,
							storeName : shop_name
						},
						type : 'POST',
						success: function(response)
					{
							$( '.ced_etsy_loader' ).hide();
							/*var html="<div class='notice notice-success'><p>Profile Created Successfully</p></div>";
							$("#profile_create_message").html(html);*/
							/* window.setTimeout(function(){*/window.location.reload();/*}, 3000)*/

						}
					}
				);

			}
		);

		/**
		 * To Perforn Bulk action of the import product section / tab
		 */
		$( document ).on(
			'click',
			'#ced_esty_import_product_bulk_operation' ,
			function(e){
				e.preventDefault();
				$( '.success-admin-notices' ).show();
				let operation = $( '.bulk-import-action-selectorf' ).val();
				if ( operation <= 0 ) {
					  var notice = '';
					  notice    += '<div class="notice notice-error">Please Select Operation To Be Performed</div>';
					  $( '.success-admin-notices' ).append( notice );
					  return;
				} else {

					let operation               = $( '.bulk-import-action-selectorf' ).val();
					let etsy_import_products_id = new Array();
					// Get all checked ids in the array from input box
					$( '.etsy_import_products_id:checked' ).each(
						function(){
							etsy_import_products_id.push( $( this ).val() );
						}
					);
					importProductsFromEtsy( etsy_import_products_id , operation );
				}
			}
		);

		$( document ).on(
			'click' ,
			'.import_single_product' ,
			function(e) {
				e.preventDefault();
				$( '.success-admin-notices' ).show();
				let listing_id         = $( this ).data( 'listing-id' );
				const listing_id_array = new Array();
				listing_id_array.push( listing_id );
				importProductsFromEtsy( listing_id_array );
			}
		);

		/**
		 * To perform Bulk action of the import product tab
		 *
		 * @param products_id  array for all check id
		 * @param operation string to be perfor by Bulk action
		 */

	function importProductsFromEtsy( etsy_import_products_id , operation ) {
		if ( etsy_import_products_id == '') {
			   let notice = '';
			   notice    += '<div class="notice notice-error">No Products Selected To Import</div>';
			   $( ".success-admin-notices" ).append( notice );
			   return;
		}

		$( '.ced_etsy_loader' ).show();
		$.ajax(
			{
				url : ajaxUrl,
				data : {
					ajax_nonce                : ajaxNonce,
					action                    : 'ced_etsy_import_products_bulk_action',
					operation_to_be_performed : operation,
					listing_id                : etsy_import_products_id,
					shop_name                 : shop_name
				},
				type: 'POST',
				success : function (response) {
						 let sliced_listing_id = etsy_import_products_id.slice( 1 );
					if ( sliced_listing_id != 0 ) {
						importProductsFromEtsy( sliced_listing_id ,operation );
					} else {
						$( '.ced_etsy_loader' ).hide();
						response = jQuery.parseJSON( response );
						if ( response.status == 200 ) {
											   let notice = '';
											   notice    += "<div class='notice notice-success'>" + response.message + "</div>";
											   $( ".success-admin-notices" ).append( notice );
											   window.setTimeout( function(){window.location.reload()}, 2500 );
						}
					}
				}
			 }
		);
	}

		$( document ).on(
			'click',
			'#ced_etsy_bulk_operation',
			function(e){
				$( '.success-admin-notices' ).show();
				e.preventDefault();
				var operation = $( this ).data( 'operation' );
				$( "#ced_etsy_bulk_operation" ).attr( 'style','' );
				$( this ).attr( 'style' , 'padding: 5px;display: inline-block;box-shadow: 5px 5px 4px darkgray;transition-delay: 200ms;transition-property: padding;' );
				if (operation <= 0 ) {
					  var notice = "";
					  notice    += "<div class='notice notice-error'>Please Select Operation To Be Performed</div>";
					  $( ".success-admin-notices" ).append( notice );
					  return;
				} else {
					var etsy_products_id = new Array();
					$( '.etsy_products_id:checked' ).each(
						function(){
							etsy_products_id.push( $( this ).val() );
						}
					);
					var total_products = etsy_products_id.length;
					performBulkAction( etsy_products_id, operation, total_products );
				}

			}
		);

	function performBulkAction( etsy_products_id,operation ,total_products)
		 {

		if (etsy_products_id == "") {
				var notice = "";
				notice    += "<div class='notice notice-error'>No Products Selected</div>";
				$( ".success-admin-notices" ).append( notice );
				return;
		}
			 $( '.ced_etsy_loader' ).show();
		$( '.ced_progress' ).show();
			 var etsy_products_id_to_perform = etsy_products_id[0];var total_processed             = 0;
					$( "#ced_progress" ).attr( "max",total_products );

			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_etsy_process_bulk_action',
						operation_to_be_performed : operation,
						id : etsy_products_id_to_perform,
						shopname:shop_name
					},
					type : 'POST',
					success: function(response)
				{

						var remainig_products_id = etsy_products_id.splice( 1 );
						total_processed          = total_products - remainig_products_id.length;

						var response  = jQuery.parseJSON( response );
						var response1 = jQuery.trim( response.message );
						if (response1 == "Shop is Not Active") {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>Currently Shop is not Active . Please activate your Shop in order to perform operations.</p></div>";
							$( ".success-admin-notices" ).append( notice );
							$( '.ced_etsy_loader' ).hide();
							$( '.ced_progress' ).hide();
							return;
						} else if (response.status == 200) {
							$( "#ced_progress" ).attr( "value", total_processed );
							var id               = response.prodid;
							var Response_message = jQuery.trim( response.message );
							var notice           = "";
							notice              += "<div class='notice notice-success'>" + response.message + "</div>";
							$( ".success-admin-notices" ).append( notice );
							if (Response_message == 'Product ' + id + ' Deleted Successfully') {
								$( "#" + id + "" ).html( '<b class="not_completed">Not on Etsy</b>' );
								$( "." + id + "" ).remove();
							} else {
								$( "#" + id + "" ).html( '<span class="success_upload_on_etsy">Present on Etsy</span>' );
							}

							if (remainig_products_id == "") {
								$( '.ced_etsy_loader' ).hide();
								$( '.ced_progress' ).hide();
								$( "#ced_progress" ).attr( "max", '' );
								$( "#ced_progress" ).attr( "value", 0 );
								return;
							} else {
								performBulkAction( remainig_products_id, operation, total_products );
							}

						} else if (response.status == 400) {
							$( "#ced_progress" ).attr( "value", total_processed );
							var notice = "";
							notice    += "<div class='notice notice-error'>" + response.message + "</div>";
							$( ".success-admin-notices" ).append( notice );
							if (remainig_products_id == "") {
								$( '.ced_etsy_loader' ).hide();
								$( '.ced_progress' ).hide();
								$( "#ced_progress" ).attr( "max", '' );
								$( "#ced_progress" ).attr( "value", 0 );
								  return;
							} else {
								performBulkAction( remainig_products_id, operation, total_products );
							}

						}
					}
				}
			);
	}

		$( document ).on(
			'click',
			'.ced_etsy_profiles_on_pop_up',
			function(){

				var product_id = $( this ).attr( "data-product_id" );
				var shopid     = $( this ).attr( "data-shopid" );
				$.ajax(
					{

						url:ajaxUrl,
						data:{
							ajax_nonce:ajaxNonce,
							prodId:product_id,
							shopid:shopid,
							action:'ced_etsy_profiles_on_pop_up'
						},
						type:'POST',
						success:function(response)
					{
									 $( ".ced_etsy_preview_product_popup_main_wrapper" ).html( response );
									 $( document ).find( '.ced_etsy_preview_product_popup_main_wrapper' ).addClass( 'show' );
						}

					}
				);
			}
		);

		$( document ).on(
			'click',
			'#save_etsy_profile_through_popup',
			function(){
				$( '.ced_etsy_loader' ).show();
				var product_id = $( this ).attr( "data-prodId" );
				var shopid     = $( this ).attr( "data-shopid" );
				var profile_id = $( ".ced_etsy_profile_selected_on_popup" ).val();
				$.ajax(
					{

						url:ajaxUrl,
						data:{
							ajax_nonce:ajaxNonce,
							prodId:product_id,
							shopid:shopid,
							profile_id:profile_id,
							action:'save_etsy_profile_through_popup'
						},
						type:'POST',
						success:function(response)
					{
									 response = jQuery.trim( response );
							if (response == "null") {
								$( '.ced_etsy_loader' ).hide();
								$( document ).find( '.ced_etsy_preview_product_popup_main_wrapper' ).removeClass( 'show' );
								var notice = "";
								notice    += "<div class='notice notice-error'><p>No Profile Selected.</p></div>";
								$( ".success-admin-notices" ).append( notice );
								window.setTimeout( function(){window.location.reload()}, 2500 );

							} else {
								$( '.ced_etsy_loader' ).hide();
								location.reload( true );
							}
						}

					}
				);

			}
		);
		$( document ).on(
			'click',
			'#ced_etsy_fetch_orders',
			function(event)
			{
					   event.preventDefault();
					   var store_id = $( this ).attr( 'data-id' );
					   $( '.ced_etsy_loader' ).show();
					$.ajax(
						{
							url : ajaxUrl,
							data : {
								ajax_nonce : ajaxNonce,
								action : 'ced_etsy_get_orders',
								shopid:store_id
							},
							type : 'POST',
							success: function(response)
						{
									 $( '.ced_etsy_loader' ).hide();
									 var response  = jQuery.parseJSON( response );
									 var response1 = jQuery.trim( response.message );
								if (response1 == "Shop is Not Active") {
										var notice = "";
										notice    += "<div class='notice notice-error'><p>Currently Shop is not Active . Please activate your Shop in order to fetch orders.</p></div>";
										$( ".success-admin-notices" ).append( notice );
										return;
								} else {
									location.reload( true );
								}
							}
						   }
					);
			}
		);

		$( document ).on(
			'click',
			'.ced_etsy_profile_popup_close',
			function(){

				$( document ).find( '.ced_etsy_preview_product_popup_main_wrapper' ).removeClass( 'show' );

			}
		);

		$( document ).on(
			'click',
			'.ced_etsy_preview_product_popup_close',
			function(){

				$( document ).find( '.ced_etsy_preview_product_popup_main_wrapper' ).removeClass( 'show' );

			}
		);
		$( document ).on(
			'change',
			'#ced_etsy_scheduler_info',
			function(){

				if (this.checked) {
					  $( ".ced_etsy_scheduler_info" ).css( 'display','contents' );
				} else {
					$( ".ced_etsy_scheduler_info" ).css( 'display','none' );
				}
			}
		);

		/**
		 * To delete profiles from the profile view tab
		 */
		$( document ).on(
			'click' ,
			'.Delete_profiles' ,
			function(){
				let ced_profile_id = $( this ).data( 'profileid' );
				if (confirm( "Are you Sure ?" )) {
					$.ajax(
						{
							url  : ajaxUrl,
							data : {
								profile_id : ced_profile_id,
								action     : 'ced_esty_delete_mapped_profiles',
								shop_name  : shop_name,
								ajax_nonce : ajaxNonce,
							},
							type : 'POST',
							success:function( response ){

								  parsed_response = jQuery.parseJSON( response );
								  var classes     = classes = 'notice notice-success';
								if (parsed_response.status == 400 ) {
									classes = 'notice notice-error';
								}

								var html = '<div class="' + classes + '"><p>' + parsed_response.message + '</p></div>';
								$( '.ced_etsy_error' ).html( html );
								window.setTimeout( function() {window.location.reload();},1000 );

							}
						}
					);
				}
			}
		);

		$( document ).on(
			'change',
			'.ced_etsy_shipn_prof',
			function(e){
				e.preventDefault();

				let e_prof_id  = $( this ).data( 'e_profile_id' );
				let woo_cat_id = $( this ).val();
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce   : ajaxNonce,
							action       : 'ced_etsy_map_shipping_profiles_woo_cat',
							e_profile_id : e_prof_id,
							woo_cat_id   : woo_cat_id,
							shop_name    : shop_name,
						},
						type : 'POST',
						success : function(response){
							 parsed_response = jQuery.parseJSON( response );
							  var classes    = classes = 'notice notice-success';
							if ( parsed_response.status == 400 ) {
								classes = 'notice notice-error';
							}
							$( '.ced_etsy_loader' ).hide();
							var html = '<div class="' + classes + '"><p>' + parsed_response.message + '</p></div>';
							$( '.ced_etsy_error' ).html( html );
							window.setTimeout( function() {window.location.reload();},1000 );
						}
					}
				);
			}
		);

		$( document ).on(
			'click',
			'.Delete_shipping_profiles',
			function(e){
				e.preventDefault();
				let e_prof_id = $( this ).data( 'e_profile_id' );
				if (confirm( "Deleting the shipping profile will also be permanently deleted from Etsy shop as well . Do you want to proceed ?" )) {
					$.ajax(
						{
							url : ajaxUrl,
							data : {
								ajax_nonce   : ajaxNonce,
								action       : 'ced_etsy_delete_shipping_profile',
								shop_name    : shop_name,
								e_profile_id : e_prof_id,
							},
							type : 'POST',
							success : function( response ){
								 parsed_response = jQuery.parseJSON( response );
								  var classes    = classes = 'notice notice-success';
								if ( parsed_response.status == 400 ) {
									classes = 'notice notice-error';
								}
								$( '.ced_etsy_loader' ).hide();
								var html = '<div class="' + classes + '"><p>' + parsed_response.message + '</p></div>';
								$( '.ced_etsy_error' ).html( html );
								window.setTimeout( function() {window.location.reload();},1000 );
							}
						}
					);
				}

			}
		);

})( jQuery );
