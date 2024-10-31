<?php namespace rontar\dynamic_retargeting;

final class Class_Admin {
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

	private $plugin;

	private function initialise() {
		$this->setup_globals();
		$this->load_dependencies();
		$this->define_hooks();
	}

	private function setup_globals() {
		$this->plugin = Class_Plugin::instance();
	}

	private function load_dependencies() {
		require_once('Class_Rontar_Integration.php');
	}

	private function define_hooks() {
		add_action( 'init', array( $this, 'add_feed' ) );
		add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_add_settings_link' ), 10, 2);
	}
	
	public function plugin_add_settings_link( $links, $plugin_file ) {
		if ( 'rontar-dynamic-retargeting-for-woocommerce/main.php' == $plugin_file ) {
			$settings_link = '<a href="admin.php?page=wc-settings&tab=integration">' . __( 'Settings' ) . '</a>';
			array_push( $links, $settings_link );
		}
		return $links;
	}	
	
	public function add_integration( $integrations ) {
		$integrations[] = __NAMESPACE__ . '\Rontar_Integration';

		return $integrations;
	}

	public function add_feed() {
		add_feed( 'rontarfeed.xml', array( $this, 'render_feed' ) );

		foreach ( array_keys( get_option( 'rewrite_rules', array() ) ) as $rule ) {
			if ( stristr( $rule, 'rontarfeed.xml' ) ) {
				flush_rewrite_rules();
				break;
			}
		}
	}

	public function render_feed() {
		echo '<shop>';
			$this->render_currencies();
			$this->render_categories();
			$this->render_offers();
		echo '</shop>';
	}

	private function render_currencies() {
		echo '<currencies>';
		echo '<currency id="' . get_woocommerce_currency() . '" rate="1"/>';
		echo '</currencies>';
	}

	private function render_categories() {
		$category_query = array(
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'taxonomy'   => 'product_cat',
		);

		echo '<categories>';
			foreach ( get_categories( $category_query ) as $category ) {
				printf(
					'<category id="%d" %s>%s</category>',
					$category->cat_ID,
					$category->parent == 0 ? '' : ( 'parentId="' . $category->parent . '"' ),
					wp_strip_all_tags( $category->name )
				);
			}
		echo '</categories>';
	}

	private function render_offers() {
		global $wpdb;

		$limit   = 1000;
		$offset  = 0;

		echo '<offers>';

			$product_query = "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' LIMIT {$limit} OFFSET %d";
			do {
				$products = $wpdb->get_results( $wpdb->prepare( $product_query, $offset ) );
				foreach ( $products as $product ) {
					$product_data = $wpdb->get_results(
						"SELECT meta_key AS 'key', meta_value AS 'value' FROM {$wpdb->postmeta} WHERE post_id={$product->ID} AND meta_key IN ('_thumbnail_id','_regular_price','_sale_price','_sale_price_dates_from','_sale_price_dates_to')",
						OBJECT_K
					);

					$regular_price     = floatval( $product_data['_regular_price']->value );
					$sale_price        = floatval( $product_data['_sale_price']->value );
					$date_on_sale_from = intval( $product_data['_sale_price_dates_from']->value );
					$date_on_sale_to   = intval( $product_data['_sale_price_dates_to']->value );
					if ( ! empty( $sale_price ) && $regular_price > $sale_price ) {
						$on_sale = true;

						if ( ! empty( $date_on_sale_from ) && $date_on_sale_from > current_time( 'timestamp', true ) ) {
							$on_sale = false;
						}

						if ( ! empty( $date_on_sale_to ) && $date_on_sale_to < current_time( 'timestamp', true ) ) {
							$on_sale = false;
						}
					} else {
						$on_sale = false;
					}
					$price = $on_sale ? $sale_price : $regular_price;

					$picture = empty( $product_data['_thumbnail_id']->value ) ? '' : wp_get_attachment_image_url( $product_data['_thumbnail_id']->value, 'full' );


					$category_id = null;
					$categories  = get_the_terms( $product->ID, 'product_cat' );
					if ( $categories ) {
						foreach ( $categories as $category ) {
							$category_id = $category->term_id;
							$child_category = get_term_children( $category->term_id, 'product_cat' );
							if ( empty( $child_category ) ) {
								break;
							}
						}
					}


					printf(
						'<offer id="%d"><url>%s</url><price>%s</price><categoryId>%d</categoryId><picture>%s</picture><name>%s</name><description>%s</description></offer>',
						$product->ID,
						get_permalink( $product->ID ),
						$price,
						$category_id,
						esc_url( $picture ),
						'<![CDATA[' . $product->post_title . ']]>',
						'<![CDATA[' . html_entity_decode( $product->post_content, ENT_COMPAT, "UTF-8" ) . ']]>'
					);
				}

				$offset += $limit;
			} while ( count( $products ) == $limit );

		echo '</offers>';
	}
}
