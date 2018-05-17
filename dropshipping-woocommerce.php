<?php
/**
 * Plugin Name:       Knawat WooCommerce DropShipping
 * Plugin URI:        https://wordpress.org/plugins/dropshipping-woocommerce/
 * Description:       Knawat WooCommerce DropShipping
 * Version:           1.2.0
 * Author:            Knawat Team
 * Author URI:        https://github.com/Knawat
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dropshipping-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.5
 *
 * @package     Knawat_Dropshipping_Woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'Knawat_Dropshipping_Woocommerce' ) ):

/**
* Main Knawat Dropshipping Woocommerce class
*/
class Knawat_Dropshipping_Woocommerce{
	
	/** Singleton *************************************************************/
	/**
	 * Knawat_Dropshipping_Woocommerce The one true Knawat_Dropshipping_Woocommerce.
	 */
	private static $instance;

    /**
     * Main Knawat Dropshipping Woocommerce Instance.
     * 
     * Insure that only one instance of Knawat_Dropshipping_Woocommerce exists in memory at any one time.
     * Also prevents needing to define globals all over the place.
     *
     * @since 1.0.0
     * @static object $instance
     * @uses Knawat_Dropshipping_Woocommerce::setup_constants() Setup the constants needed.
     * @uses Knawat_Dropshipping_Woocommerce::includes() Include the required files.
     * @uses Knawat_Dropshipping_Woocommerce::laod_textdomain() load the language files.
     * @see run_knawat_dropshipwc_woocommerce()
     * @return object| Knawat Dropshipping Woocommerce the one true Knawat Dropshipping Woocommerce.
     */
	public static function instance() {
		if( ! isset( self::$instance ) && ! (self::$instance instanceof Knawat_Dropshipping_Woocommerce ) ) {
			self::$instance = new Knawat_Dropshipping_Woocommerce;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			add_action( 'init', array( self::$instance, 'init_includes' ) );
			add_action( 'wp_enqueue_scripts', array( self::$instance, 'knawat_dropshipwc_enqueue_style' ) );
			add_action( 'wp_enqueue_scripts', array( self::$instance, 'knawat_dropshipwc_enqueue_script' ) );

			self::$instance->includes();
			self::$instance->common = new Knawat_Dropshipping_Woocommerce_Common();
			self::$instance->admin = new Knawat_Dropshipping_Woocommerce_Admin();
			if( self::$instance->is_woocommerce_activated() ){
				self::$instance->orders = new Knawat_Dropshipping_Woocommerce_Orders();
			}
			/**
			* The code that runs during plugin activation.
			*/
			if( class_exists( 'Knawat_Merlin' ) ){
				register_activation_hook(  __FILE__,  array( 'Knawat_Merlin', 'plugin_activated' ) );
			}
		}
		return self::$instance;	
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent Knawat_Dropshipping_Woocommerce from being loaded more than once.
	 *
	 * @since 1.0.0
	 * @see Knawat_Dropshipping_Woocommerce::instance()
	 * @see run_knawat_dropshipwc_woocommerce()
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent Knawat_Dropshipping_Woocommerce from being cloned.
	 *
	 * @since 1.0.0
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dropshipping-woocommerce' ), '1.2.0' ); }

	/**
	 * A dummy magic method to prevent Knawat_Dropshipping_Woocommerce from being unserialized.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dropshipping-woocommerce' ), '1.2.0' ); }


	/**
	 * Setup plugins constants.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_constants() {

		// Plugin version.
		if( ! defined( 'KNAWAT_DROPWC_VERSION' ) ){
			define( 'KNAWAT_DROPWC_VERSION', '1.2.0' );
		}

		// Plugin folder Path.
		if( ! defined( 'KNAWAT_DROPWC_PLUGIN_DIR' ) ){
			define( 'KNAWAT_DROPWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL.
		if( ! defined( 'KNAWAT_DROPWC_PLUGIN_URL' ) ){
			define( 'KNAWAT_DROPWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin root file.
		if( ! defined( 'KNAWAT_DROPWC_PLUGIN_FILE' ) ){
			define( 'KNAWAT_DROPWC_PLUGIN_FILE', __FILE__ );
		}

		// Plugin root file.
		if( ! defined( 'KNAWAT_DROPWC_API_NAMESPACE' ) ){
			define( 'KNAWAT_DROPWC_API_NAMESPACE', 'knawat/v1' );
		}

		// Options
		if( ! defined( 'KNAWAT_DROPWC_OPTIONS' ) ){
			define( 'KNAWAT_DROPWC_OPTIONS', 'knawat_dropshipwc_options' );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {

		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-common.php';
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-admin.php';
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-webhook.php';
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-pdf-invoice.php';
		if( $this->is_woocommerce_activated() ){
			require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-shipment-tracking.php';
			require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-orders.php';
			require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/class-dropshipping-woocommerce-admin-dashboard.php';
		}
		/**
		 * Recommended and required plugins.
		 */
		if ( !class_exists( 'TGM_Plugin_Activation' ) ) {
			require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/lib/tgmpa/class-tgm-plugin-activation.php';
		}
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/knawat-required-plugins.php';
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/lib/knawat-merlin/knawat-merlin.php';
	}

	/**
	 * Include required files on init.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function init_includes() {
		// API
		require_once KNAWAT_DROPWC_PLUGIN_DIR . 'includes/api/class-dropshipping-woocommerce-handshake.php';
	}

	/**
	 * Loads the plugin language files.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(){

		load_plugin_textdomain(
			'dropshipping-woocommerce',
			false,
			basename( dirname( __FILE__ ) ) . '/languages'
		);
	
	}
	
	/**
	 * Check if woocommerce is activated
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function is_woocommerce_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();

		if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * enqueue style front-end
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function knawat_dropshipwc_enqueue_style() {
		// enqueue style here.
		$css_dir = KNAWAT_DROPWC_PLUGIN_URL . 'assets/css/';
	 	wp_enqueue_style('dropshipping-woocommerce-front', $css_dir . 'dropshipping-woocommerce.css', false, "" );		
	}

	/**
	 * Get Defined DropShippers.
	 *
	 * @access public
	 * @since 1.2.0
	 * @return array
	 */
	public function get_dropshippers() {
		$dropshippers = array(
			'default' => array(
				'id' 			=> 'default',
				'name' 			=> __( 'Knawat DropShipping', 'dropshipping-woocommerce' ),
				'countries' 	=> 0
			),
			'knawat_saudi' => array(
				'id' 			=> 'knawat_saudi',
				'name' 			=> __( 'Knawat DropShipping (Saudi Arabia)', 'dropshipping-woocommerce' ),
				'countries' 	=> array( 'SA' )
			)
		);

		return $dropshippers;
	}

	/**
	 * enqueue script front-end
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function knawat_dropshipwc_enqueue_script() {
		// enqueue script here.
	}

}

endif; // End If class exists check.

/**
 * The main function for that returns Knawat_Dropshipping_Woocommerce
 *
 * The main function responsible for returning the one true Knawat_Dropshipping_Woocommerce
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $knawat_dropshipwc = run_knawat_dropshipwc_woocommerce(); ?>
 *
 * @since 1.0.0
 * @return object|Knawat_Dropshipping_Woocommerce The one true Knawat_Dropshipping_Woocommerce Instance.
 */
function run_knawat_dropshipwc_woocommerce() {
	return Knawat_Dropshipping_Woocommerce::instance();
}

// Get Knawat_Dropshipping_Woocommerce Running.
$GLOBALS['knawat_dropshipwc'] = run_knawat_dropshipwc_woocommerce();
