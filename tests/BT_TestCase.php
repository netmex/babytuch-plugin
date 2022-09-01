<?php

class BT_TestCase extends WP_UnitTestCase {

	private static bool $ready = false;
	protected static WC_Product $product;
	protected static WC_Product_Variation $variation;

	protected array $customerAddress = array(
		'first_name' => '111Joe',
		'last_name'  => 'Conlin',
		'email'      => 'joe@testing.com',
		'phone'      => '760-555-1212',
		'address_1'  => 'Musterstrasse 123',
		'city'       => 'Zurich',
		'postcode'   => '8048',
		'country'    => 'CH'
	);


    /**
     * Creates a new order
     * @return WC_Order
     */
    protected function initOrder(): WC_Order {

        $order = wc_create_order();
        $order->add_product( self::$variation, 2);
        $order->set_address( $this->customerAddress, 'billing' );
        $order->calculate_totals();

        return $order;
    }

	/**
	 * @before
	 */
	protected function firstSetUp() {
		if (static::$ready) return;

		// set woocommerce options
		update_option('woocommerce_store_address', 'nevaland gmbh');
		update_option('woocommerce_store_address_2', 'Spycherweg 3');
		update_option('woocommerce_store_city', 'Spreitenbach');
		update_option('woocommerce_default_country', 'CH:AG');
		update_option('woocommerce_store_postcode', '8957');


		// set babytuch options
		update_option('name_logistics', 'babytuch.ch Logistik');
		update_option('name2_logistics', 'Noveos Pack+');
		update_option('adress_logistics', 'Turicaphonstrasse 29');
		update_option('city_logistics', 'Riedikon');
		update_option('plz_logistics', '8616');
		update_option('mail_logistics', 'michael.zioerjen@hey.com');

		// set post api options
		update_option('post_api_client_id', '8a8c1ab6f6ecfa8d6e6a1205fc13e023');
		update_option('post_api_client_secret', '6bc31d6e6bc3f6e1c16264a8ed625823');
		update_option('post_api_test_client_id', '078ca809b7ff5bf8fa3df6c1dbe37842');
		update_option('post_api_test_client_secret', '72cc289202df3617a9a6c77a428b38a5');
		update_option('post_api_franking_license', '60138277');
		update_option('post_api_testing', 1);


		// set SQRIP options
		$sqrip_options = array (
			'enabled' => 'yes',
			'title' => 'Rechnung/Vorauskasse',
			'description' => 'Bezahle mit der QR-Rechnung. Sobald die Zahlung erfolgt ist, werden wir deine Bestellung umgehend verarbeiten.',
			'pm_due_date' => '1',
			'pm_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjo5OSwiZmlyc3RfbmFtZSI6Ik5ldmEiLCJsYXN0X25hbWUiOiJNXHUwMGZjbGxlci1GZWxkbWFubiIsImVtYWlsIjoiYWRtaW5AbmV2YWxhbmQuY2giLCJ0aW1lIjoiMjAyMS0wNi0yOFQxNToyMTo1NC4yNTM5NThaIn0=.M2y2oXbZFuFn6uoShvOgN3sxTNJ/q7UEISEuks003UU=',
			'pm_iban' => 'CH45 0076 1646 1682 4200 2',
			'file_type' => 'pdf',
			'product' => 'Full A4',
			'integration_email' => 'attachment',
			'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjo5OSwiZmlyc3RfbmFtZSI6Ik5ldmEiLCJsYXN0X25hbWUiOiJNXHUwMGZjbGxlci1GZWxkbWFubiIsImVtYWlsIjoiYWRtaW5AbmV2YWxhbmQuY2giLCJ0aW1lIjoiMjAyMS0wNi0yOFQxNToyMTo1NC4yNTM5NThaIn0=.M2y2oXbZFuFn6uoShvOgN3sxTNJ/q7UEISEuks003UU=',
			'integration_order' => 'qrcode',
			'due_date' => '30',
			'iban' => 'CH45 0076 1646 1682 4200 2'
		);
		update_option('woocommerce_sqrip_settings', $sqrip_options);


		// create product
		$product = new WC_Product_Variable();
		$product->set_description('T-shirt variable description');
		$product->set_name('T-shirt variable');
		//$product->set_sku('test-shirt');
		$product->set_price(1);
		$product->set_regular_price(1);
		$product->set_stock_status();
		$product_id = $product->save();

		// create attribute
		$attribute = new WC_Product_Attribute();
		$attribute->set_id(0);
		$attribute->set_name('groesse');
		$attribute->set_options([1,2,3,4,5,6,7,8,9,10,11,12]);
		$attribute->set_visible(true);
		$attribute->set_variation(true);

		$product->set_attributes([$attribute]);
		$product->save();

		// create variation with size 1
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product_id );
		$variation->set_attributes(['groesse' => 1]);
		$variation->set_manage_stock(true);
		$variation->set_stock_quantity(5);
		$variation->set_status('publish');
		//$variation->set_sku($product->get_sku());
		$variation->set_price($product->get_price());
		$variation->set_regular_price($product->get_price());
		$variation->set_stock_status();

		$variation_id = $variation->save();
		$product = wc_get_product($product_id);
		$product->save();


		// upload image for product
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/assets/product-dummy.jpg', $product->get_id() );
		$product->update_meta_data('_product_image_gallery', $attachment_id);
		$product->save();

		static::$product = $product;
		static::$variation = $variation;

        static::$ready = true;
    }

    public function setUp() {
        parent::setUp();
        $this->reset_mailer();
        add_action("doing_it_wrong_run", [$this, "_wp_doing_it_wrong"]);
   }

    public function tearDown() {
        parent::tearDown();
        $this->reset_mailer();
        remove_action("doing_it_wrong_run", [$this, "_wp_doing_it_wrong"]);
    }

    /**
     * Provides more context to the broad "you're doing it wrong wordpress methods"
     * @param $function
     * @param $message
     * @param $version
     * @return void
     */
    public function _wp_doing_it_wrong($function, $message = "", $version = "") {
        $msg = "Youre Doing It Wrong at: $function ($version): $message" . "\n";

        foreach (debug_backtrace() as $traceIndex => $traceContent) {
            if(!$this->FilterStackTrace($traceIndex )) { continue;  }
            $msg .= sprintf("#%s '%s' at '%s'", $traceIndex , @$traceContent["function"], @$traceContent["file"]) . "\n";
        }

        $this->fail($msg);
    }

    protected function FilterStackTrace($index) {
        return true;
    }

    /**
     * Reset mailer
     *
     * @return bool
     */
    protected function reset_mailer(){
        return reset_phpmailer_instance();
    }

    /**
     * Get mock mailer
     *
     * Wraps tests_retrieve_phpmailer_instance()
     *
     * @return MockPHPMailer
     */
    protected function get_mock_mailer(){
        return tests_retrieve_phpmailer_instance();
    }


}