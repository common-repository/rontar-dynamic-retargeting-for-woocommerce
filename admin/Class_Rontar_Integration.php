<?php namespace rontar\dynamic_retargeting;

final class Rontar_Integration extends \WC_Integration {
	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id           = 'rontar_dynamic_retargeting';
		$this->method_title = __( 'Rontar Dynamic Retargeting', $this->id );
		$this->method_description = __( 'Get started by using our <a href="https://account.rontar.com/Signup?utm_source=woocommerce&utm_medium=listing" target="_blank">sign up wizard</a>. The wizard will provide you with your Advertiser ID, Product feed ID, and Audience ID.', $this->id );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Actions.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'             => __( 'Enable', $this->id ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable Rontar Dynamic Retargeting', $this->id ),
				'default'           => 'no',
			),
			'advertiser_id' => array(
				'title'             => __( 'Advertiser ID', $this->id ),
				'type'              => 'text',
				'description'       => __( 'Rontar will provide you with the advertiser ID', $this->id ),
				'desc_tip'          => true,
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
					'oninvalid' => "this.setCustomValidity('Advertiser ID required')",
					'oninput'   => "setCustomValidity('')"
				),
			),
			'product_feed_id' => array(
				'title'             => __( 'Product feed ID', $this->id ),
				'type'              => 'text',
				'description'       => __( 'Rontar will provide you with the product feed ID', $this->id ),
				'desc_tip'          => true,
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
					'oninvalid' => "this.setCustomValidity('Product feed ID required')",
					'oninput'   => "setCustomValidity('')"
				),
			),
			'audience_id' => array(
				'title'             => __( 'Audience ID', $this->id ),
				'type'              => 'text',
				'description'       => __( 'Rontar will provide you with the audience ID', $this->id ),
				'desc_tip'          => true,
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
					'oninvalid' => "this.setCustomValidity('Audience ID required')",
					'oninput'   => "setCustomValidity('')"
				),
			),
			'product_feed' => array(
				'title'             => __( 'Product Feed URL', $this->id ),
				'type'              => 'text',
				'description'       => __( 'Rontar XML product feed URL', $this->id ),
				'desc_tip'          => true,
				'default'           => site_url('rontarfeed.xml'),
				'custom_attributes' => array( 'readonly' => 'readonly' ),
			),
		);
	}
}
