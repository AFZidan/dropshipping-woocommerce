<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package     Knawat_Dropshipping_Woocommerce
 * @subpackage  Knawat_Dropshipping_Woocommerce/admin
 * @copyright   Copyright (c) 2018, Knawat
 * @since       1.0.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package     Knawat_Dropshipping_Woocommerce
 * @subpackage  Knawat_Dropshipping_Woocommerce/admin
 * @author     Dharmesh Patel <dspatel44@gmail.com>
 */
class Knawat_Dropshipping_Woocommerce_Admin {


	public $adminpage_url;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->adminpage_url = admin_url('admin.php?page=knawat_dropship' );

		add_action( 'admin_menu', array( $this, 'add_menu_pages') );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts') );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles') );
		add_action( 'after_setup_theme', array( $this, 'knawat_setup_wizard' ) );
		add_filter( 'views_edit-product', array( $this, 'knawat_dropshipwc_add_new_product_filter' ) );
		add_filter( 'views_edit-shop_order', array( $this, 'knawat_dropshipwc_add_new_order_filter' ) );
		add_action( 'load-edit.php', array( $this, 'knawat_dropshipwc_load_custom_knawat_filter' ) );
		add_action( 'load-edit.php', array( $this, 'knawat_dropshipwc_load_custom_knawat_order_filter' ) );
		add_filter( 'admin_footer_text', array( $this, 'add_dropshipping_woocommerce_credit' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'knawat_dropshipwc_add_knawat_order_status_in_backend' ), 10 );

		// Add Knawat Order Status column to order list table
		add_filter( 'manage_shop_order_posts_columns', array( $this, 'knawat_dropshipwc_shop_order_columns' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'knawat_dropshipwc_render_shop_order_columns' ) );

		// Display Knawat Cost in Variation
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'knawat_dropshipwc_add_knawat_cost_field' ), 10, 3 );

		// Add & Save Product Variation Dropshipper and Quantity
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'knawat_dropshipwc_add_dropshipper_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'knawat_dropshipwc_save_dropshipper_field' ), 10, 2 );
	}

	/**
	 * Create the Admin menu and submenu and assign their links to global varibles.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function add_menu_pages() {

		add_menu_page( __( 'Knawat Dropshipping', 'dropshipping-woocommerce' ), __( 'Dropshipping', 'dropshipping-woocommerce' ), 'manage_options', 'knawat_dropship', array( $this, 'admin_page' ), KNAWAT_DROPWC_PLUGIN_URL . 'assets/images/knawat.png', '30' );
	}

	/**
	 * Include require libraries & config for knawat setup wizard.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function knawat_setup_wizard() {
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/knawat-merlin-config.php';
	}

	/**
	 * Load Admin page.
	 *
	 * @since 1.0
	 * @return void
	 */
	function admin_page() {
		
		?>
		<div class="wrap">
		    <h1><?php esc_html_e( 'Knawat Dropshipping', 'dropshipping-woocommerce' ); ?></h1>
		    <h2><?php esc_html_e( 'Settings', 'dropshipping-woocommerce' ); ?></h2>
		    <?php
		    // Set Default Tab to Import.
		    $tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';
		    ?>
		    <div id="poststuff">
		        <div id="post-body" class="metabox-holder columns-2">

		            <div id="postbox-container-1" class="postbox-container">
		            	<?php //require_once KNAWAT_DROPWC_PLUGIN_DIR . '/templates/admin-sidebar.php'; ?>
		            </div>
		            <div id="postbox-container-2" class="postbox-container">

		                <div class="dropshipping-woocommerce-page">
		                	<?php
		                		require_once KNAWAT_DROPWC_PLUGIN_DIR . '/templates/dropshipping-woocommerce-admin-page.php';
			                ?>
		                	<div style="clear: both"></div>
		                </div>
		        	</div>
		        
		    </div>
		</div>
		<?php
	}

	/**
	 * Add Knawat Orders Filter view at filters
	 *
	 * @since 1.0
	 * @param  array $views Array of filter views
	 * @return array $views Array of filter views
	 */
	function knawat_dropshipwc_add_new_order_filter( $views ){

		global $wpdb;

		$t_order_items = $wpdb->prefix . "woocommerce_order_items";
		$t_order_itemmeta = $wpdb->prefix . "woocommerce_order_itemmeta";

		$count_query = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) as count FROM {$wpdb->posts}
			INNER JOIN {$wpdb->postmeta} as pm ON {$wpdb->posts}.ID = pm.post_id AND pm.meta_key = '_knawat_order'
			WHERE 1=1 AND {$wpdb->posts}.post_type = 'shop_order'
			AND ( {$wpdb->posts}.post_status != 'wc-cancelled' AND {$wpdb->posts}.post_status != 'trash' )
			AND pm.meta_value = 1";

		$count = $wpdb->get_var( $count_query );

		if( $count > 0 ){
			$class = '';
			if ( isset( $_GET[ 'knawat_orders' ] ) && !empty( $_GET[ 'knawat_orders' ] ) ){
				$class = 'current';
			}

			$views_html = sprintf( "<a class='%s' href='edit.php?post_type=shop_order&knawat_orders=1'>%s</a><span class='count'>(%d)</span>", $class, __('Knawat Orders', 'dropshipping-woocommerce' ), $count );
			$views['knawat'] = $views_html;
		}
		// Removed Mine filter from order listing screen.
		if( isset( $views['mine'] ) ){
			unset( $views['mine'] );
		}
		return $views;
	}

	/**
	 * Add `posts_where` filter if knawat orders need to filter
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawat_dropshipwc_load_custom_knawat_order_filter(){
	    global $typenow;
	    if( 'shop_order' != $typenow ){
	        return;
	    }

	    if ( isset( $_GET[ 'knawat_orders' ] ) && !empty( $_GET[ 'knawat_orders' ] ) && trim( $_GET[ 'knawat_orders' ] ) == 1 ){
			add_filter( 'posts_where' , array( $this, 'knawat_dropshipwc_posts_where_knawat_orders') );
			add_filter( 'posts_join', array( $this, 'knawat_dropshipwc_posts_join_knawat_orders') );
	    }
	}

	/**
	 * Add condtion in WHERE statement for filter only knawat orders in orders list table
	 *
	 * @since  1.0
	 * @param  string $where Where condition of SQL statement for orders query
	 * @return string $where Modified Where condition of SQL statement for orders query
	 */
	function knawat_dropshipwc_posts_where_knawat_orders( $where ){
	    global $wpdb;

		if ( isset( $_GET[ 'knawat_orders' ] ) && !empty( $_GET[ 'knawat_orders' ] ) && trim( $_GET[ 'knawat_orders' ] ) == 1 ){
	        $where .= " AND ( {$wpdb->posts}.post_status != 'wc-cancelled' AND {$wpdb->posts}.post_status != 'trash' ) AND {$wpdb->postmeta}.meta_value = 1 ";
	    }
	    return $where;
	}

	/**
	 * Add JOIN statement for filter only knawat orders in orders list table
	 *
	 * @since  1.0
	 * @param  string $join join of SQL statement for orders query
	 * @return string $join Modified join of SQL statement for orders query
	 */
	function knawat_dropshipwc_posts_join_knawat_orders( $join ){
	    global $wpdb;

	    if ( isset( $_GET[ 'knawat_orders' ] ) && !empty( $_GET[ 'knawat_orders' ] ) && trim( $_GET[ 'knawat_orders' ] ) == 1 ){
			$join .= "INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_knawat_order'";
	    }
	    return $join;
	}


	/**
	 * Add Knawat Product Filter view at filters
	 *
	 * @since 1.0
	 * @param  array $views Array of filter views
	 * @return array $views Array of filter views
	 */
	function knawat_dropshipwc_add_new_product_filter( $views ){

		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT( DISTINCT p.ID) as count FROM {$wpdb->posts} as p INNER JOIN {$wpdb->postmeta} as pm ON ( p.ID = pm.post_id ) WHERE 1=1 AND ( pm.meta_key = 'dropshipping' AND pm.meta_value = 'knawat' ) AND p.post_type = 'product' AND p.post_status != 'trash'" );
		if( $count > 0 ){
			$class = '';
			if ( isset( $_GET[ 'knawat_products' ] ) && !empty( $_GET[ 'knawat_products' ] ) ){
				$class = 'current';
			}

			$views_html = sprintf( "<a class='%s' href='edit.php?post_type=product&knawat_products=1'>%s</a><span class='count'>(%d)</span>", $class, __('Knawat Products', 'dropshipping-woocommerce' ), $count );
			$views['knawat'] = $views_html;
		}
		return $views;
	}

	/** 
	 * Add `posts_where` filter if knawat products need to filter
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawat_dropshipwc_load_custom_knawat_filter(){
	    global $typenow;
	    if( 'product' != $typenow ){
	        return;
	    }
	    
	    if ( isset( $_GET[ 'knawat_products' ] ) && !empty( $_GET[ 'knawat_products' ] ) && trim( $_GET[ 'knawat_products' ] ) == 1 ){
	    	add_filter( 'posts_where' , array( $this, 'knawat_dropshipwc_posts_where_knawat_products') );
	    }
	}

	/**
	 * Add condtion in WHERE statement for filter only knawat products in products list table
	 *
	 * @since  1.0
	 * @param  string $where Where condition of SQL statement for products query
	 * @return string $where Modified Where condition of SQL statement for products query
	 */
	function knawat_dropshipwc_posts_where_knawat_products( $where ){
	    global $wpdb;       
	    if ( isset( $_GET[ 'knawat_products' ] ) && !empty( $_GET[ 'knawat_products' ] ) && trim( $_GET[ 'knawat_products' ] ) == 1 ){
	        $where .= " AND ID IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key='dropshipping' AND meta_value='knawat' )";
	    }
	    return $where;
	}


	/**
	 * Add Knawat Dropshipping Woocommerce ratting text in wp-admin footer
	 *
	 * @since 1.0
	 * @return void
	 */
	public function add_dropshipping_woocommerce_credit( $footer_text ){
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		if ( $page != '' && $page == 'knawat_dropship' ) {
			$rate_url = 'https://wordpress.org/support/plugin/dropshipping-woocommerce/reviews/?rate=5#new-post';

			$footer_text .= sprintf(
				esc_html__( ' Rate %1$s Dropshipping Woocommerce%2$s %3$s', 'dropshipping-woocommerce' ),
				'<strong>',
				'</strong>',
				'<a href="' . $rate_url . '" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}
		return $footer_text;
	}

	/**
	 * Load Admin Scripts
	 *
	 * Enqueues the required admin scripts.
	 *
	 * @since 1.1.0
	 * @param string $hook Page hook
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		$js_dir  = KNAWAT_DROPWC_PLUGIN_URL . 'assets/js/';
		wp_register_script( 'dropshipping-woocommerce', $js_dir . 'dropshipping-woocommerce-admin.js', array('jquery' ), KNAWAT_DROPWC_VERSION );
		wp_enqueue_script( 'dropshipping-woocommerce' );
		
	}
	
	/**
	 * Load Admin Styles.
	 *
	 * Enqueues the required admin styles.
	 *
	 * @since 1.1.0
	 * @param string $hook Page hook
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		$css_dir  = KNAWAT_DROPWC_PLUGIN_URL . 'assets/css/';
		wp_enqueue_style('dropshipping-woocommerce', $css_dir . 'dropshipping-woocommerce-admin.css', false, "" );
	}

	/**
	 * Display Knawat Order Status in Order Meta Box
	 *
	 * @since 1.1.0
	 * @param object $order Order
	 * @return void
	 */
	public function knawat_dropshipwc_add_knawat_order_status_in_backend( $order ){
		global $knawat_dropshipwc;
		$order_id = $order->get_id();
		if( !$knawat_dropshipwc->common->is_knawat_order( $order_id ) ){
			return;
		}
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$knawat_order_status = get_post_meta( $order_id, '_knawat_order_status', true );
		} else {
			$knawat_order_status = $order->get_meta( '_knawat_order_status', true );
		}
		if( $knawat_order_status != '' ){
			?>
			<p class="form-field" style="color: #000">
				<strong><?php _e( 'Knawat Order Status:', 'dropshipping-woocommerce' ); ?></strong><br/>
				<span><?php echo ucfirst( $knawat_order_status ); ?></span>
			</p>
			<?php
		}		
	}

	/**
	 * Define Knawat Order Status column in admin orders list.
	 *
	 * @since 1.1.0
	 * @param 	array $columns Existing columns
	 * @return 	array modilfied columns
	 */
	public function knawat_dropshipwc_shop_order_columns( $columns ){
		$columns['knawat_status'] = __( 'Knawat Status', 'dropshipping-woocommerce' );
		return $columns;
	}


	/**
	 * Render Knawat Order Status in custom column
	 *
	 * @since 1.1.0
	 * @param 	string $column Current column
	 */
	public function knawat_dropshipwc_render_shop_order_columns( $column ){
		global $post;
		if ( 'knawat_status' === $column ) {
			$order_id = $post->ID;
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$knawat_order_status = get_post_meta( $order_id, '_knawat_order_status', true );
			} else {
				$order = new WC_Order( $order_id );
				$knawat_order_status = $order->get_meta( '_knawat_order_status', true );
			}

			if( $knawat_order_status != '' ){
				if ( version_compare( WC_VERSION, '3.3', '>=' ) ) {
					?>
					<mark class="order-status"><span><?php echo ucfirst( $knawat_order_status ); ?></span></mark>
					<?php
				}else{
					?>
					<span class="knawat-order-status"><?php echo ucfirst( $knawat_order_status ); ?></span>
					<?php
				}
			}else{
				echo '–';
			}
		}
	}


	/**
	 * Render Read-Only Knawat Cost in Variation Prices block
	 *
	 * @since 1.2.0
	 *
	 * @param int     $loop
	 * @param array   $variation_data
	 * @param WP_Post $variation
	 */
	public function knawat_dropshipwc_add_knawat_cost_field( $loop, $variation_data, $variation ){
		$knawat_cost = get_post_meta( $variation->ID, '_knawat_cost', true );
		if( !empty( $knawat_cost ) ){
			$label = sprintf(
				/* translators: %s: currency symbol */
				__( 'Knawat Cost (%s)', 'dropshipping-woocommerce' ),
				get_woocommerce_currency_symbol()
			);
			?>
			<p class="form-field knawat_dropshipwc_knawat_cost form-row form-row-first">
				<label for="knawat_cost<?php echo $loop; ?>"><?php echo $label; ?></label>
				<input class="short knawat_cost" id="knawat_cost<?php echo $loop; ?>" value="<?php echo $knawat_cost; ?>" placeholder="<?php echo $label; ?>" type="text" disabled="disabled">
			</p>
			<?php
		}
	}


	/**
	 * Render Dropshipper and Qty field in Variation Attribute block
	 *
	 * @since 1.2.0
	 *
	 * @param int     $loop
	 * @param array   $variation_data
	 * @param WP_Post $variation
	 */
	public function knawat_dropshipwc_add_dropshipper_field( $loop, $variation_data, $variation ){
		global $knawat_dropshipwc;
		$dropshippers_temp = $knawat_dropshipwc->get_dropshippers();
		if ( empty( $dropshippers_temp ) ) {
			return;
		}
		$dropshippers = array();
		foreach ( $dropshippers_temp as $dropship ) {
			$dropshippers[$dropship["id"]] = $dropship["name"];
		}

		$dropshipper = 'default';
		$localds_stock = 0;

		if( isset( $variation_data['_knawat_dropshipper'][0] ) ){
			$dropshipper = $variation_data['_knawat_dropshipper'][0];
			if( empty( $dropshipper ) ){
				$dropshipper = 'default';
			}
		}
		if( isset( $variation_data['_localds_stock'][0] ) ){
			$localds_stock = $variation_data['_localds_stock'][0];
			if( empty( $localds_stock ) ){
				$localds_stock = 0;
			}
		}
		?>

		<div id="knawat_dropshipwc_dropshipper_<?php echo $variation->ID; ?>" class="knawat_dropshipwc_dropshipper_wrap">
			<?php
			woocommerce_wp_select( array(
				'id'            => "knawat_dropshipper{$variation->ID}",
				'name'          => "knawat_dropshipper[{$variation->ID}]",
				'class'			=> 'knawat_dropshipper_select',
				'value'         => $dropshipper,
				'label'         => __('Dropshipper', 'dropshipping-woocommerce' ),
				'options'       => $dropshippers,
				'desc_tip'      => true,
				'description'   => __( 'Select Dropshipper for this Product variantion.', 'dropshipping-woocommerce' ),
				'wrapper_class' => 'knawat_dropshipwc_dropshipper form-row form-row-first',
			) );

			woocommerce_wp_text_input( array(
				'id'                => "localds_stock{$variation->ID}",
				'name'              => "localds_stock[{$variation->ID}]",
				'value'             => $localds_stock,
				'label'             => __( 'Stock quantity (Dropshipper)', 'dropshipping-woocommerce' ),
				'desc_tip'          => true,
				'description'       => __( "Enter a quantity of product variant for selected dropshipper.", 'dropshipping-woocommerce' ),
				'type'              => 'number',
				'wrapper_class'     => 'knawat_dropshipwc_localds_stock form-row form-row-last',
			) );
			?>
		</div>
		<?php
	}

	/**
	 * Save Dropshipper and dropshipper Qty for Product variation
	 *
	 * @return void
	 */
	public function knawat_dropshipwc_save_dropshipper_field( $variation_id, $i ){

		if( isset( $_POST['knawat_dropshipper'][$variation_id] ) ){
			$dropshipper = isset( $_POST['knawat_dropshipper'][$variation_id] ) ? sanitize_text_field( $_POST['knawat_dropshipper'][$variation_id] ) : 'default';
			update_post_meta( $variation_id, '_knawat_dropshipper', $dropshipper );
		}

		if( isset( $_POST['localds_stock'][$variation_id] ) ){
			$localds_stock = isset( $_POST['localds_stock'][$variation_id] ) ? absint( $_POST['localds_stock'][$variation_id] ) : 0;
			update_post_meta( $variation_id, '_localds_stock', $localds_stock );
		}
	}
}
