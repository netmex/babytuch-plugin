<?php

class ReplaceOrderTest extends BT_TestCase {
	public function testOrderReturnProcessCanBeStarted() {
		// activate testing data
		update_option('post_api_testing', 1);

		$order = wc_create_order();
		$order->add_product( self::$variation, 2);
		$order->set_address( $this->customerAddress, 'billing' );
		$order->calculate_totals();
	}

	public function testOrderReturnProcessCannotBeStartedTwice() {

	}
}