<?php namespace rontar\dynamic_retargeting;

final class Class_Front {
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

	private function load_dependencies() {}

	private function define_hooks() {
		add_action( 'wp_footer', array( $this, 'add_pixel' ) );
	}

	public function add_pixel() {
		if (
			empty( $this->plugin->options['enabled'] )
			||
			'yes' != $this->plugin->options['enabled']
			||
			empty( $this->plugin->options['advertiser_id'] )
			||
			empty( $this->plugin->options['product_feed_id'] )
			||
			empty( $this->plugin->options['audience_id'] )
			||
			! in_array( 'woocommerce-page', get_body_class() ) // if not the WooCommerce page
		) {
			return;
		}

		global $wp_query;
		$str_replace = array(
			'[ADVERTISER_ID]'  => esc_js( $this->plugin->options['advertiser_id'] ),
			'[PRODUCTFEED_ID]' => esc_js( $this->plugin->options['product_feed_id'] ),
			'[AUDIENCE_ID]'    => esc_js( $this->plugin->options['audience_id'] ),
		);

		ob_start();

		?>
<script>
	window.rnt=window.rnt||function(){(rnt.q=rnt.q||[]).push(arguments)};
	rnt('add_event', {advId: '[ADVERTISER_ID]'});
	//<!-- EVENTS START -->
	<?php if ( is_shop() ) : ?>
		rnt('add_event', {advId: '[ADVERTISER_ID]', pageType:'home'});
		rnt('add_audience', {audienceId: '[AUDIENCE_ID]'});
	<?php elseif ( is_product() ) : ?>
		<?php
			$str_replace['[PRODUCT_ID]'] = $wp_query->queried_object_id;
		?>
		rnt('add_audience', {audienceId: '[AUDIENCE_ID]', priceId: '[PRODUCTFEED_ID]', productId: '[PRODUCT_ID]'});
		rnt('add_product_event', {advId: '[ADVERTISER_ID]', priceId: '[PRODUCTFEED_ID]', productId: '[PRODUCT_ID]'});
	<?php elseif ( is_product_category() ) : ?>
		<?php
			$str_replace['[CATEGORY_ID]'] = $wp_query->queried_object_id;
			$str_replace['[PRODUCT_IDS]'] = empty( $wp_query->posts ) ? '' : implode( ',', wp_list_pluck( $wp_query->posts, 'ID' ) );
		?>
		rnt('add_category_event', {advId: '[ADVERTISER_ID]', priceId: '[PRODUCTFEED_ID]', categoryId: '[CATEGORY_ID]', productIds: '[PRODUCT_IDS]'});
		rnt('add_audience', {audienceId: '[AUDIENCE_ID]'});
	<?php elseif ( is_cart() ) : ?>
		<?php
			$product_ids = array();
			foreach ( WC()->cart->get_cart_contents() as $item ) {
				$product_ids[] = empty( $item['variation_id'] ) ? $item['product_id'] : $item['variation_id'];
			}
			$str_replace['[PRODUCT_IDS]'] = implode( ',', $product_ids );
		?>
		rnt('add_shopping_cart_event', {advId: '[ADVERTISER_ID]', priceId: '[PRODUCTFEED_ID]', productIds: '[PRODUCT_IDS]'});
	<?php elseif ( is_order_received_page() ) : ?>
		<?php
			$product_ids = array();
			$order = wc_get_order( $wp_query->query["order-received"] );
			/** @var \WC_Order_Item_Product $item */
			foreach ( $order->get_items() as $item ) {
				$variation_id = $item->get_variation_id();
				$product_ids[] = empty( $variation_id ) ? $item->get_product_id() : $item->get_variation_id();
			}
			$str_replace['[PRODUCT_IDS]'] = implode( ',', $product_ids );
		?>
		rnt('add_order_event', {advId: '[ADVERTISER_ID]', priceId: '[PRODUCTFEED_ID]', productIds: '[PRODUCT_IDS]'});
	<?php else : ?>
		rnt('add_audience', {audienceId: '[AUDIENCE_ID]'});
	<?php endif; ?>
	//<!-- EVENTS FINISH -->
</script>
<script async src='//uaadcodedsp.rontar.com/rontar_aud_async.js'></script>
		<?php

		$script = ob_get_clean();
		echo str_replace( array_keys( $str_replace ), array_values( $str_replace ), $script );
	}
}
