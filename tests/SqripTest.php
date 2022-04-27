<?php



/**
 * Class SqripTest
 *
 * @package Babytuch_Plugin
 */
class SqripTest extends BT_TestCase {


	/**
	 * A single example test.
	 */
	public function test_sqrip_pdf_is_attached_to_email() {

		$order = wc_create_order();
		$order->add_product( self::$variation, 2);
		$order->set_address( $this->customerAddress, 'billing' );
		$order->calculate_totals();

		// Process Payment
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$order->set_payment_method($available_gateways['sqrip']);

		// TODO: check whether this triggers the sqrip payment gateway
		$order->update_status("on-hold", '', TRUE);

		// TODO: search for correct PDF file
		$email = tests_retrieve_phpmailer_instance()->get_sent();
		$body = $email->body;

		$this->assertContains('Content-Type: application/pdf;', $body);
		$this->assertContains('Content-Disposition: attachment', $body);

		$order->update_status("processing", '', TRUE);

	}

}
