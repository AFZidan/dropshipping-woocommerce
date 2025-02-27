<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$knawat_options                  = knawat_dropshipwc_get_options();
$mp_consumer_key                 = isset( $knawat_options['mp_consumer_key'] ) ? esc_attr( $knawat_options['mp_consumer_key'] ) : '';
$mp_consumer_secret              = isset( $knawat_options['mp_consumer_secret'] ) ? esc_attr( $knawat_options['mp_consumer_secret'] ) : '';
$token_status                    = isset( $knawat_options['token_status'] ) ? esc_attr( $knawat_options['token_status'] ) : 'invalid';
$product_batch                   = isset( $knawat_options['product_batch'] ) ? esc_attr( $knawat_options['product_batch'] ) : 25;
$categorize_products             = isset( $knawat_options['categorize_products'] ) ? esc_attr( $knawat_options['categorize_products'] ) : 'no';
$remove_outofstock     			 = isset( $knawat_options['remove_outofstock'] ) ? esc_attr( $knawat_options['remove_outofstock'] ) : 'yes';
$dokan_seller                    = isset( $knawat_options['dokan_seller'] ) ? esc_attr( $knawat_options['dokan_seller'] ) : - 1;
$sync_url                        = wp_nonce_url( admin_url( 'admin-post.php?action=resyncs_knawat_products' ), 'knawat_product_sync_action', 'product_sync' );
$knawat_options['last_imported'] = get_option( 'knawat_last_imported', false );
$last_update                     = isset( $knawat_options['last_imported'] ) ? intval( esc_attr( $knawat_options['last_imported'] ) ) * 1000 : 0;
$reset_time                      = 1483300000000;

if ( $token_status === 'valid' ) :
	$products_synced = knawat_dropshipwc_get_products_count( $last_update );
	$products_count  = knawat_dropshipwc_get_products_count( $reset_time );
	$stock_count     = knawat_dropshipwc_get_products_count( $reset_time, false );
endif;
?>

<div class="knawat_dropshipwc_settings">

	<h3><?php esc_attr_e( 'Settings', 'dropshipping-woocommerce' ); ?></h3>
	<p style="margin: 0px;"><strong><?php _e( 'Note: Only orders in Processing status will be sent to Knawat to processs.', 'dropshipping-woocommerce' ); ?></strong></p>
	<p style="margin: 0px;"><?php _e( 'You need a Knawat consumer key and consumer secret for import products into your store from knawat.com', 'dropshipping-woocommerce' ); ?></p>
	<form method="post" id="knawatds_setting_form">
		<table class="form-table">
			<tbody>
			<?php do_action( 'knawat_dropshipwc_before_settings_section' ); ?>

			<tr class="knawat_dropshipwc_row">
				<th scope="row">
					<?php _e( 'Knawat Consumer Key', 'dropshipping-woocommerce' ); ?>
				</th>
				<td>
					<input class="mp_consumer_key regular-text" name="knawat[mp_consumer_key]" type="text" value="<?php if ( $mp_consumer_key != '' ) { echo $mp_consumer_key;} ?>"/>
					<p class="description" id="mp_consumer_key-description">
						<?php
						printf(
							'%s <a href="https://user-images.githubusercontent.com/4643935/153250029-81fc8acb-8c1b-4ab4-87b5-374f8e6ee387.png" target="_blank">%s</a>',
							__( 'You can get your Knawat Consumer Key from your <strong>Dashboard > Store settings</strong>', 'dropshipping-woocommerce' ),
							__( 'click to see the image', 'dropshipping-woocommerce' )
						);
						?>
					</p>
				</td>
			</tr>

			<tr class="knawat_dropshipwc_row">
				<th scope="row">
					<?php _e( 'Knawat Consumer Secret', 'dropshipping-woocommerce' ); ?>
				</th>
				<td>
					<input class="mp_consumer_secret regular-text" name="knawat[mp_consumer_secret]" type="text" value="<?php if ( $mp_consumer_secret != '' ) { echo $mp_consumer_secret; } ?>"/>
					<p class="description" id="mp_consumer_secret-description">
						<?php
						printf(
							'%s <a href="https://user-images.githubusercontent.com/4643935/153250029-81fc8acb-8c1b-4ab4-87b5-374f8e6ee387.png" target="_blank">%s</a>',
							__( 'You can get your Knawat Consumer Secret from your <strong>Dashboard > Store settings</strong>', 'dropshipping-woocommerce' ),
							__( 'click to see the image', 'dropshipping-woocommerce' )
						);
						?>
					</p>
				</td>
			</tr>

			<tr class="knawat_dropshipwc_row">
				<th scope="row">
					<?php _e( 'Products Batch Size:', 'dropshipping-woocommerce' ); ?>
				</th>
				<td>
					<input class="product_batch regular-text" name="knawat[product_batch]" type="number" value="<?php echo $product_batch; ?>" min="5" max="100"/>
					<p class="description" id="product_batch-description">
						<?php
						_e( 'Products batch size for import products from knawat.com', 'dropshipping-woocommerce' );
						?>
					</p>
				</td>
			</tr>

			<tr class="knawat_dropshipwc_row">
				<th scope="row">
					<?php _e( 'Categorize Products', 'dropshipping-woocommerce' ); ?>
				</th>
				<td>
					<select name="knawat[categorize_products]" required="required">
						<option value="no" <?php selected( 'no', $categorize_products, true ); ?>><?php esc_html_e( 'No','dropshipping-woocommerce' ); ?></option>
						<option value="yes" <?php selected( 'yes', $categorize_products, true ); ?>><?php esc_html_e( 'Yes (Product Category)','dropshipping-woocommerce' ); ?></option>
						<option value="yes_as_tags" <?php selected( 'yes_as_tags', $categorize_products, true ); ?>><?php esc_html_e( 'Yes (Product Tag)','dropshipping-woocommerce' ); ?></option>
					</select>
					<p class="description" id="product_batch-description">
						<?php
						_e( 'Select yes to fetch product categories', 'dropshipping-woocommerce' );
						?>
					</p>
				</td>
			</tr>

			<tr class="knawat_dropshipwc_row">
                <th scope="row">
					<?php esc_html_e( 'Delete Out Of Stock Products', 'dropshipping-woocommerce' ); ?>
                </th>
                <td>
                    <select name="knawat[remove_outofstock]" required="required">
                        <option value="yes" <?php selected( 'yes', $remove_outofstock, true ) ?>><?php esc_html_e('Yes','dropshipping-woocommerce'); ?></option>
						<option value="no" <?php selected( 'no', $remove_outofstock, true ) ?>><?php esc_html_e('No','dropshipping-woocommerce'); ?></option>
                    </select>

                    <p class="description" id="remove_outofstock-description">
					<?php esc_html_e('Some products go out of stock and return back. If you checked this, products will not return back to your store.', 'dropshipping-woocommerce' ); ?>
                    </p>
                </td>
            </tr>

			<tr class="knawat_dropshipwc_row">
				<th scope="row">
					<?php _e( 'Orders Synchronization Interval:', 'dropshipping-woocommerce' ); ?>
				</th>
				<?php
				$current_interval = get_option( 'knawat_order_pull_cron_interval', 6 * 60 * 60 );
				$intervals        = array(
					172800 => __( '48 Hours', 'dropshipping-woocommerce' ),
					86400  => __( '24 Hours', 'dropshipping-woocommerce' ),
					43200  => __( '12 Hours', 'dropshipping-woocommerce' ),
					32400  => __( '9 Hours', 'dropshipping-woocommerce' ),
					21600  => __( '6 Hours', 'dropshipping-woocommerce' ),
					10800  => __( '3 Hours', 'dropshipping-woocommerce' ),
					3600   => __( '1 Hour', 'dropshipping-woocommerce' ),
				);
				?>
				<td>
					<select name="order_pull_interval" required="required">
						<?php
						foreach ( $intervals as $value => $interval ) {
							echo '<option value="' . $value . '" ' . selected( $current_interval, $value, false ) . '>' . $interval . '</option>';
						}
						?>
					</select>
					<p class="description" id="order_pull_interval-description">
						<?php
						_e( 'Select interval for synchronization orders with Knawat', 'dropshipping-woocommerce' );
						?>
					</p>
				</td>
			</tr>

			<?php if ( knawat_dropshipwc_is_dokan_active() ) { ?>
				<tr class="knawat_dropshipwc_row">
					<th scope="row">
						<?php _e( 'Dokan Seller', 'dropshipping-woocommerce' ); ?>
					</th>
					<td>
						<?php
						$seller_args = array(
							'name'             => 'knawat[dokan_seller]',
							'id'               => 'knawat_dokan_seller',
							'class'            => 'dokan_seller',
							'role'             => 'seller',
							'selected'         => $dokan_seller,
							'show_option_none' => __( 'Select Dokan Seller', 'dropshipping-woocommerce' ),
						);
						wp_dropdown_users( $seller_args );
						?>
						<p class="description" id="dokan_seller-description">
							<?php
							_e( 'Select dokan seller for import Knawat products under it.', 'dropshipping-woocommerce' );
							?>
						</p>
					</td>
				</tr>
			<?php } ?>

			<?php if ( $mp_consumer_key != '' && $mp_consumer_secret != '' ) { ?>
				<tr class="knawat_dropshipwc_row">
					<th scope="row">
						<?php _e( 'Knawat Connection status', 'dropshipping-woocommerce' ); ?>
					</th>
					<td>
						<?php
						if ( 'valid' === $token_status ) {
							?>
							<p class="connection_wrap success">
								<span class="dashicons dashicons-yes"></span> <?php _e( 'Connected', 'dropshipping-woocommerce' ); ?>
							</p>
							<?php
						} else {
							?>
							<p class="connection_wrap error">
								<span class="dashicons dashicons-dismiss"></span> <?php _e( 'Not connected', 'dropshipping-woocommerce' ); ?>
							</p>
							<p class="description">
								<?php
								_e( 'Please verify your knawat consumer keys.', 'dropshipping-woocommerce' );
								?>
							</p>
							<?php
						}
						?>
					</td>
				</tr>
			<?php } ?>

			</tbody>
		</table>
		<div class="knawatds_element submit_button">
			<input type="hidden" name="knawatds_action" value="knawatds_save_settings"/>
			<?php wp_nonce_field( 'knawatds_setting_form_nonce_action', 'knawatds_setting_form_nonce' ); ?>
			<input type="submit" class="button-primary knawatds_submit_button" style="" value="<?php esc_attr_e( 'Save Settings', 'dropshipping-woocommerce' ); ?>"/>
		</div>

		<table class="form-table">
			<tbody>
				<tr class="knawat_dropshipwc_row">
					<th scope="row">
						<?php _e( '<strong>Products Synced</strong>', 'dropshipping-woocommerce' ); ?>
					</th>
					<td>
						 <meter id="last-update-knawat" value="<?php echo $products_count - $products_synced; ?>" min="0" max="<?php echo $products_count; ?>" style="height: 35px; width: 815px;"></meter>
						
						<p> <?php _e( 'You have <strong>' . $products_count . ' product(s) </strong>, ' . ( $stock_count ) . ' of them in-stock, Already ' . ( $products_count - $products_synced ) . ' are done syncing.','dropshipping-woocommerce' ); ?>  </p>

						<p> <?php _e( 'if some of your products didn\'t get updated, you may need to <a href="' . $sync_url . '">Sync All</a>, but it\'ll take approx few hours to update all prices and stock.','dropshipping-woocommerce' ); ?> </p>
					 </td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
