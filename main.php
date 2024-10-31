<?php namespace rontar\dynamic_retargeting;

/**
 * Plugin Name:       Rontar Dynamic Retargeting for WooCommerce
 * Description:       Rontar product feed and retargeting pixel easy integration.
 * Version:           1.0.7
 * Author:            Rontar <help@rontar.com>
 * Author URI:        https://www.rontar.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rontar_dynamic_retargeting
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( __DIR__ . '/includes/W8_Loader.php' );

final class Class_Plugin {
	/** Singleton *************************************************************/
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been ran previously
		if ( null === $instance ) {
			$class_name = get_class();
			$instance = new $class_name;
			$instance->initialise();
		}

		// Always return the instance
		return $instance;
	}

	/** Magic Methods *********************************************************/
	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}

	private $version;
	private $name;
	private $path;

	/**
	 * @var \W8_Loader null
	 */
	private $loader;

	private $admin;
	private $front;

	private $options;

	public function __get( $constant_name ) {
		return isset( $this->$constant_name ) ? $this->$constant_name : null;
	}

	private function initialise() {
		$this->setup_globals();
		$this->load_dependencies();
		$this->define_hooks();
	}

	private function setup_globals() {
		$this->version = '1.0.0';
		$this->name    = 'rontar_dynamic_retargeting';
		$this->path    = __DIR__ . '/';

		$this->admin   = null;
		$this->front   = null;

		$this->loader = new \W8_Loader();
		$this->loader->register();

		$this->options = get_option( "woocommerce_{$this->name}_settings", $this->get_defaul_options() );
	}

	private function load_dependencies() {
		$this->loader->setPrefixes( array(
			__NAMESPACE__ => array(
				$this->path . 'includes',
				$this->path . 'admin',
				$this->path . 'front',
			),
		) );
	}

	private function define_hooks() {
		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ), PHP_INT_MAX );
	}

	public function activate() {
		update_option( "woocommerce_{$this->name}_settings", $this->get_defaul_options() );
		flush_rewrite_rules();
	}

	public function deactivate() {
		flush_rewrite_rules();
	}

	public function init() {
		$this->load_plugin_textdomain();

		$this->admin = Class_Admin::instance();
		$this->front = Class_Front::instance();
	}

	private function get_defaul_options() {
		return array(
			'enabled'         => 'no',
			'advertiser_id'   => '',
			'product_feed_id' => '',
			'audience_id'     => '',
		);
	}

	private function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->name,
			false,
			$this->path . 'languages/'
		);
	}
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	Class_Plugin::instance();
}
