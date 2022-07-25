<?php


use Inc\Base\Returns;
use Inc\Models\BT_OrderProcess;

final class ReturnOrderTest extends BT_TestCase {

    public function test_return_check_not_ok() {

        // activate testing data
        update_option('post_api_testing', 1);

        $order = wc_create_order();
        $order->add_product( self::$variation, 2);
        $order->set_address( $this->customerAddress, 'billing' );
        $order->calculate_totals();
        $order->update_status("returning", '', TRUE);

    }


	public function test_return_order_is_generated_correctly() {
		// activate testing data
		update_option('post_api_testing', 1);

		$order = wc_create_order();
		$order->add_product( self::$variation, 2);
		$order->set_address( $this->customerAddress, 'billing' );
		$order->calculate_totals();
		$order->update_status("returning", '', TRUE);


		do_action('babytuch_return_start', $order->get_id(), [self::$variation->get_id()]);

		// reload order to see changes in effect
		$order = new WC_Order($order->get_id());

		// check return order generation
		$this->assertNotEmpty($order->get_meta(Returns::$return_order_path_key));
		$this->assertNotEmpty($order->get_meta( Returns::$return_order_url_key));
		$this->assertFileExists($order->get_meta(Returns::$return_order_path_key));

	}

	public function test_return_order_email_is_sent() {
		// activate testing data
		update_option('post_api_testing', 1);

		$order = wc_create_order();
		$order->add_product( self::$variation, 2);
		$order->set_address( $this->customerAddress, 'billing' );
		$order->calculate_totals();
		$order->update_status("returning", '', TRUE);
		$order_id = $order->get_id();

		do_action('babytuch_return_start', $order->get_id(), [self::$variation->get_id()]);

		$email = tests_retrieve_phpmailer_instance()->get_sent();
		$body = $email->body;
		$this->assertContains(" $order_id ", $body);
		$this->assertContains('Content-Type: application/pdf;', $body);
		$this->assertContains('Content-Disposition: attachment', $body);

	}
}