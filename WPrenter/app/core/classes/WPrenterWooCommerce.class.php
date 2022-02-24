<?php

class WPrenterWooCommerce
{
	//private $quantity_variables;
	public function __construct()
	{
		add_action( 'quick_edit_custom_box', array(&$this, 'action_quick_edit_custom_box'), 10, 2 );
		add_action( 'admin_head', array(&$this, 'action_admin_head') );
		add_action( 'manage_product_posts_custom_column', array(&$this, 'action_manage_product_posts_custom_column'), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'action_save_post'), 10, 2 );
		add_action( 'init', array( &$this, 'action_init' ) );
		add_action( 'woocommerce_after_checkout_billing_form', array( &$this, 'action_woocommerce_after_checkout_billing_form') );
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'action_woocommerce_checkout_update_order_meta') );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( &$this, 'action_woocommerce_admin_order_data_after_billing_address') );
		add_action( 'wp_ajax_product_order_list', array(&$this, 'action_wp_ajax_nopriv_product_order_list' ));
		add_action( 'wp_ajax_nopriv_product_order_list', array(&$this, 'action_wp_ajax_nopriv_product_order_list' ));
		add_action( 'woocommerce_before_add_to_cart_quantity', array(&$this, 'action_woocommerce_before_add_to_cart_quantity') );
		add_action( 'woocommerce_after_add_to_cart_quantity', array(&$this, 'action_woocommerce_after_add_to_cart_quantity') );
		add_action( 'wp_footer', array(&$this, 'action_wp_footer' ) );

		add_filter( 'manage_edit-product_columns', array(&$this, 'filter_manage_edit_product_columns'), 20 );
		add_filter( 'woocommerce_sale_flash', array(&$this, 'filter_woocommerce_sale_flash'), 10, 3 );
		add_filter( 'woocommerce_email_order_meta_keys', array(&$this, 'filter_woocommerce_email_order_meta_keys' ) );
		add_filter( 'the_posts', array($this, 'filter_the_posts' ) );
		add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );
		add_filter( 'woocommerce_catalog_orderby', array(&$this, 'filter_woocommerce_catalog_orderby') );
		add_filter( 'woocommerce_get_catalog_ordering_args', array(&$this, 'filter_woocommerce_get_catalog_ordering_args') );
		add_filter( 'woocommerce_default_catalog_orderby_options', array(&$this, 'filter_woocommerce_catalog_orderby') );
		//add_filter( 'woocommerce_quantity_input_args', array(&$this, 'filter_woocommerce_quantity_input_args'), 10, 2 ); // Simple products
		add_filter( 'woocommerce_available_variation', array(&$this, 'filter_woocommerce_available_variation')  ); // Variations
	}


	public function action_quick_edit_custom_box( $column_name, $post_type )
	{
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col column-<?php echo $column_name; ?>">
				<label class="inline-edit-group">
					<?php
					switch ( $column_name ) {
						case 'store_price':
							?><span class="title">Store price</span><input type="number" name="store_price" value="<?php echo get_post_meta( get_the_ID(), 'store_price', true ); ?>" /><?php
							break;
						case 'trade_price':
							?><span class="title">Trade price</span><input type="number" name="trade_price" value="<?php echo get_post_meta( get_the_ID(), 'trade_price', true ); ?>" /><br><?php
							break;
					}
					?>
				</label>
			</div>
		</fieldset>
		<?php
	}

	public function action_manage_product_posts_custom_column(  $column_name, $postid )
	{
		if( $column_name  == 'store_price' ) {
			echo get_post_meta( $postid, 'store_price', true );
		}
		if( $column_name  == 'trade_price' ) {
			echo get_post_meta( $postid, 'trade_price', true );
		}
	}

	public function filter_manage_edit_product_columns( $columns_array )
	{
		return array_slice( $columns_array, 0, 6, true )
			+ array( 'store_price' => 'Store price' )
			+ array( 'trade_price' => 'Trade price' )
			+ array_slice( $columns_array, 6, NULL, true );
	}

	public function action_admin_head()
	{
		echo '<style>table.wp-list-table .column-trade_price, table.wp-list-table .column-store_price{width: 10ch;}</style>';
	}


	public function filter_woocommerce_sale_flash( $output_html, $post, $product )
	{
		if( $product->is_type( 'simple' ) )
		{
			$regular_price = method_exists( $product, 'get_regular_price' ) ? $product->get_regular_price() : $product->regular_price;
			$sale_price = method_exists( $product, 'get_sale_price' ) ? $product->get_sale_price() : $product->sale_price;
			$saved_price = wc_price( $regular_price - $sale_price );
			$percentage = round( ( $regular_price - $sale_price ) / $regular_price * 100 ).'%';
			$percentage_txt = '<span class="sale-badge" style="color:white;background-color:red;padding:5px;">-' . $percentage.'</span>';
			$output_html = '<span class="onsale">' . esc_html__( 'Sale', 'woocommerce' ) . '</span> ' . /*$saved_price.*/ $percentage_txt. '';
		}
		return $output_html;
	}

	public function action_wp_footer()
	{
		// To run this on the single product page
		if ( ! is_product() ) return;
		?>
		<style>
			.single-product div.product form.cart .quantity { float: none; margin: 0; display: inline-block; }
			.plus, .minus { padding:12px; }
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function($)
			{
				// if quantity input is hidden, hide plus minus
				if( document.getElementsByClassName("quantity hidden").length )
				{
					document.getElementsByClassName("plus")["0"].style.display = "none";
					document.getElementsByClassName("minus")["0"].style.display = "none";
				}
				else
				{
					$('form.cart').on( 'click', 'button.plus, button.minus', function()
					{
						// Get current quantity values
						var qty = $( this ).closest( 'form.cart' ).find( '.qty' );
						var val   = parseFloat(qty.val());
						var max = parseFloat(qty.attr( 'max' ));
						var min = parseFloat(qty.attr( 'min' ));
						var step = parseFloat(qty.attr( 'step' ));
						// Change the value if plus or minus
						if ( $( this ).is( '.plus' ) ) {
							if ( max && ( max <= val ) ) {
								qty.val( max );
							}
							else {
								qty.val( val + step );
							}
						}
						else {
							if ( min && ( min >= val ) ) {
								qty.val( min );
							}
							else if ( val > 1 ) {
								qty.val( val - step );
							}
						}
					});
				}
			});
		</script>
		<?php
	}


	public function action_woocommerce_before_add_to_cart_quantity() {
		echo '<button type="button" class="minus" >-</button>';
	}

	public function action_woocommerce_after_add_to_cart_quantity() {
		echo '<button type="button" class="plus" >+</button>';
	}

	public function action_add_meta_boxes( $post_type )
	{
		$post_types = array('product');     //limit meta box to certain post types
		global $post;
		//$product = wc_get_product( $post->ID );
		if ( in_array( $post_type, $post_types )
			//&& (get_the_terms( $post->ID,'product_type')[0]->slug == 'simple' )
		) {
			//add_meta_box( 'wprenter_woocommerce_quantity' , 'Min/max/step (WPR)', array( $this, 'wprenter_woocommerce_quantity' ),  $post_type, 'side', 'low' );
		}
	}

	public function wprenter_woocommerce_quantity( $post )
	{
		foreach( $this->quantity_variables as $quantity_variable_name=>$quantity_variable_label )
		{
			echo '
			<label for="'.$quantity_variable_label.'">'.$quantity_variable_name.':</label>
			<input type="number" class="short" name="'.$quantity_variable_label.'" id="'.$quantity_variable_label.'" min="0" value="'. esc_attr(get_post_meta($post->ID, $quantity_variable_label, true)).'" placeholder="">
			';
		}
	}

	public function filter_woocommerce_quantity_input_args( $args, $product )
	{
		$this->quantity_variables = ['Quantity min'=>'quantitymin', 'Quantity max'=>'quantitymax', 'Quantity step'=>'quantitystep'];
		$quantitymin = get_post_meta( get_the_ID($product), 'quantitymin', true);
		$quantitymax = get_post_meta( get_the_ID($product), 'quantitymax', true);
		$quantitystep = get_post_meta( get_the_ID($product), 'quantitystep', true);
		$args['min_value'] 	= ($quantitymin!=='') ? $quantitymin : 1;
		$args['max_value'] 	= ($quantitymax!=='') ? $quantitymax : 1000;
		$args['step'] 	= ($quantitystep!=='') ? $quantitystep : 1;
		return $args;
	}

	public function action_save_post( $post_id, $post )
	{
		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;

		if ( isset( $_REQUEST['trade_price'] ) ) {
			update_post_meta( $post_id, 'trade_price', $_REQUEST['trade_price'] );
		}
		if ( isset( $_REQUEST['store_price'] ) ) {
			update_post_meta( $post_id, 'store_price', $_REQUEST['store_price'] );
		}
		/*
		foreach( $this->quantity_variables as $quantity_variable_name=>$quantity_variable )
		{
			$new_meta_value = ( isset( $_POST[$quantity_variable] ) ? $_POST[$quantity_variable] : 1 );
			$meta_value = get_post_meta( $post_id, $quantity_variable, true );
			if ( $new_meta_value && '' == $meta_value )
				add_post_meta( $post_id, $quantity_variable, $new_meta_value, true );
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, $quantity_variable, $new_meta_value );
			elseif ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, $quantity_variable, $meta_value );
		}
		*/
	}


	public function filter_woocommerce_available_variation( $args )
	{
		$args['max_qty'] = 80; 		// Maximum value (variations)
		$args['min_qty'] = 1;   	// Minimum value (variations)
		return $args;
	}

	public function action_wp_ajax_nopriv_product_order_list()
	{
		// egyszerűsített rendelés értesítő
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
		wp_mail( 'gyorsrendeles@bieco.shop', 'Egyszerűsített rendelés', json_encode($_POST, JSON_UNESCAPED_UNICODE), $headers );
		header('Location:/');
		exit;
	}

	public function action_init()
	{
		add_shortcode( 'product_order_list', array( &$this, 'shortcode_product_order_list' ) );
	}

	public function action_woocommerce_after_checkout_billing_form( $checkout )
	{
		// VAT Number in WooCommerce Checkout
		woocommerce_form_field( 'vat_number', array(
			'type'          => 'text',
			'class'         => array( 'vat-number-field form-row-wide') ,
			'label'         => __( 'Adószám / VAT number' ),
			'placeholder'   => __( 'Adószám / VAT number' ),
		), $checkout->get_value( 'vat_number' ));
	}

	public function action_woocommerce_checkout_update_order_meta( $order_id )
	{
		// Save VAT Number in the order meta
		if ( ! empty( $_POST['vat_number'] ) )
		{
			update_post_meta( $order_id, '_vat_number', sanitize_text_field( $_POST['vat_number'] ) );
		}
	}

	public function action_woocommerce_admin_order_data_after_billing_address( $order )
	{
		// Display VAT Number in order edit screen
		echo '<p><strong>' . __( 'VAT Number', 'woocommerce' ) . ':</strong> '. get_post_meta( $order->get_order_number(), '_vat_number', true ) . '</p>';
	}

	public function filter_woocommerce_email_order_meta_keys( $keys )
	{
		// VAT Number in emails
		$keys['VAT Number'] = '_vat_number';
		return $keys;
	}



	public function shortcode_product_order_list( $atts, $content = "" )
	{
		$products = new WP_Query( array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => '1000',
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => '_stock_status',
					'value' => 'instock',
					'compare' => '=',
				)
			)
		) );
		$output = '<form method="post" action="/wp-admin/admin-ajax.php">
					<input type="hidden" name="action" value="product_order_list">
					<table>
						<tr><td>Név *:</td><td><input name="name" type="text" required></td></tr>
						<tr><td>Elérhetőség:</td><td><input name="contact" type="text"></td></tr>
						<tr><td>Áruátvétel tervezett időpontja: *</td><td><input name="atvetelidopont" type="text" required></td></tr>
						<tr><td>Egyéb megjegyzés:</td><td><input name="text" type="text"></td></tr>
					</table>
					<table><thead><tr><!--th style="width:30px;">Kép</th--><th>Terméknév</th><th>Ár</th><th>Rendelt mennyiség</th></tr></thead><tbody>';
		while ( $products->have_posts() ) : $products->the_post();
			global $product;
			//woocommerce_get_product_thumbnail([30,30]),
			$output .= sprintf( '<tr><td><a target="_blank" href="%s">%s</a></td><td>%s</td><td>%s</td></tr>',
				get_permalink(),
				get_the_title(),
				$product->get_price_html(get_the_ID()),
				'<input type="number" min="0" max="100" value="0" name="products['.get_the_ID().']">'
			);
		endwhile;
		$output .= '</tbody></table><div class="wp-block-buttons aligncenter"><div class="wp-block-button"><button type="submit">✔ Megrendelés</button></div>
</div></form>';
		wp_reset_query();
		return $output;
	}

	// Shop random order. View settings drop down order by Woocommerce > Settings > Products > Display
	public function filter_woocommerce_get_catalog_ordering_args( $args )
	{
		$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
		switch( $orderby_value )
		{
			case 'title':
			{
				$args['orderby'] = 'title';
				$args['order'] = 'asc';
				break;
			}
			case 'stock':
			{
				$args['meta_key'] = '_stock_status';
				$args['orderby'] = array( 'meta_value' => 'ASC' );
				break;
			}
			case 'random':
			{
				$args['orderby'] = 'rand';
				$args['order'] = '';
				$args['meta_key'] = '';
				break;
			}
		}
		return $args;
	}
	public function filter_woocommerce_catalog_orderby( $sortby ) {
		$sortby['title'] = 'Rendezés ABC sorrendben';
		$sortby['stock'] = 'Rendezés készlet szerint';
		$sortby['random'] = 'Random rendezés';
		return $sortby;
	}



	// search filter  - to add functionality ( search by SKU) source: https://github.com/wp-plugins/woocommerce-search-by-sku
	public function filter_the_posts($posts, $query = false)
	{
		if (is_search())
		{
			$ignoreIds = array(0);
			foreach($posts as $post)
				$ignoreIds[] = $post->ID;
			if(is_wp_error($ignoreIds))
			{
				$error_string = $ignoreIds->get_error_message();
			}
			/* get_search_query does sanitization */
			$matchedSku = $this->get_parent_post_by_sku(get_search_query(), $ignoreIds);
			if(is_wp_error($matchedSku))
			{
				$error_string2 = $matchedSku->get_error_message();
			}
			if ($matchedSku)
			{
				//unset($posts);
				foreach($matchedSku as $product_id)
				{
					$posts[] = get_post($product_id->post_id);
					if(is_wp_error($posts))
					{
						$error_string3 = $posts->get_error_message();
					}
				}
			}
			return $posts;
		}
		return $posts;
	}
	public function get_parent_post_by_sku($sku, $ignoreIds)
	{
		global $wpdb, $wp_query;
		/* Should the query do some extra joins for WPML Enabled sites... */
		$wmplEnabled = false;
		if(defined('WPML_TM_VERSION') && defined('WPML_ST_VERSION') && class_exists("woocommerce_wpml")){
			$wmplEnabled = true;
			/* What language should we search for... */
			$languageCode = ICL_LANGUAGE_CODE;
		}
		$results = array();
		/* Search for the sku of a variation and return the parent sku */
		$ignoreIdsForMySql = implode(",", $ignoreIds);
		if(is_wp_error($ignoreIdsForMySql))
		{
			$error_string4 = $ignoreIdsForMySql->get_error_message();
		}
		$variationsSql = "SELECT p.post_parent as post_id FROM $wpdb->posts as p join $wpdb->postmeta pm on p.ID = pm.post_id and pm.meta_key='_sku' and pm.meta_value LIKE '%$sku%'";
		/* IF WPML Plugin is enabled join and get correct language product. */
		if($wmplEnabled){
			$variationsSql .= "join ".$wpdb->prefix."icl_translations t on t.element_id = p.post_parent and t.element_type = 'post_product' and t.language_code = '$languageCode'";
		}
		$variationsSql .= "where 1 AND p.post_parent <> 0 and p.ID not in ($ignoreIdsForMySql) and p.post_status = 'publish' group by p.post_parent";
		if(is_wp_error($variationsSql))
		{
			$error_string12 = $variationsSql->get_error_message();
		}
		$variations = $wpdb->get_results($variationsSql);
		if($wpdb->last_error !== '')
		{
			$error_name1 =	$wpdb->last_error;
			$error_query1 =	$wpdb->last_query;
		}
		if(is_wp_error($variations))
		{
			$error_string5 = $variations->get_error_message();
		}
		foreach($variations as $post){$ignoreIds[] = $post->post_id;}
		if(is_wp_error($ignoreIds))
		{
			$error_string6 = $ignoreIds->get_error_message();
		}
		$ignoreIdsForMySql = implode(",", $ignoreIds);
		if(is_wp_error($ignoreIdsForMySql))
		{
			$error_string7 = $ignoreIdsForMySql->get_error_message();
		}
		$regularProductsSql = "SELECT p.ID as post_id FROM $wpdb->posts as p join $wpdb->postmeta pm on p.ID = pm.post_id and  pm.meta_key='_sku'	AND pm.meta_value LIKE '%$sku%'";
		/* IF WPML Plugin is enabled join and get correct language product.  */
		if($wmplEnabled){
			$regularProductsSql .= "join ".$wpdb->prefix."icl_translations t on t.element_id = p.ID and t.element_type = 'post_product' and t.language_code = '$languageCode'";
		}
		$regularProductsSql .=  "where 1 and (p.post_parent = 0 or p.post_parent is null) and p.ID not in ($ignoreIdsForMySql) and p.post_status = 'publish' group by p.ID";
		if(is_wp_error($regularProductsSql))
		{
			$error_string8 = $regularProductsSql->get_error_message();
		}
		$regular_products = $wpdb->get_results($regularProductsSql);
		if($wpdb->last_error !== '')
		{
			$error_name =	$wpdb->last_error;
			$error_query =	$wpdb->last_query;
		}
		if(is_wp_error($regular_products))
		{
			$error_string9 = $regular_products->get_error_message();
		}
		$results = array_merge($variations, $regular_products);
		if(is_wp_error($results))
		{
			$error_string10 = $results->get_error_message();
		}
		$wp_query->found_posts += sizeof($results);
		return $results;
	}


}
