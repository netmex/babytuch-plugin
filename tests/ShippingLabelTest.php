<?php
/**
 * Class SampleTest
 *
 * @package Babytuch_Plugin
 */

use Inc\Base\Shipping;

/**
 * Sample test case.
 */
class ShippingLabelTest extends BT_TestCase {


	/**
	 * A single example test.
	 */
	public function test_logistics_information_and_tracking_numbers_are_generated() {

		// activate testing data
		update_option('post_api_testing', 1);

		$order = wc_create_order();
		$order->add_product( self::$variation, 2);
		$order->set_address( $this->customerAddress, 'billing' );
		$order->calculate_totals();
		$order->update_status("processing", '', TRUE);

		// reload order to see changes in effect
		$order = new WC_Order($order->get_id());

		// check labels generation
		$this->assertNotEmpty($order->get_meta(Shipping::$logistic_labels_path_key));
		$this->assertNotEmpty($order->get_meta( Shipping::$logistic_labels_url_key));
		$this->assertFileExists($order->get_meta(Shipping::$logistic_labels_path_key));

		// check order generation
		$this->assertNotEmpty($order->get_meta(Shipping::$logistic_order_path_key));
		$this->assertNotEmpty($order->get_meta( Shipping::$logistic_order_url_key));
		$this->assertFileExists($order->get_meta(Shipping::$logistic_order_path_key));

		// check referral cards generation
		$this->assertNotEmpty($order->get_meta(Shipping::$referral_cards_path_key));
		$this->assertNotEmpty($order->get_meta( Shipping::$referral_cards_url_key));
		$this->assertFileExists($order->get_meta(Shipping::$referral_cards_path_key));

		// check shipping label generation
		$this->assertNotEmpty($order->get_meta(Shipping::$shipping_label_path_key));
		$this->assertNotEmpty($order->get_meta(Shipping::$shipping_label_url_key));
		$this->assertFileExists($order->get_meta(Shipping::$shipping_label_path_key));

		// check tracking number generation
		$this->assertNotEmpty($order->get_meta(Shipping::$trackingnumber_key));
		$this->assertNotEmpty($order->get_meta(Shipping::$trackingurl_key));

		// check return label generation
		$this->assertNotEmpty($order->get_meta(Shipping::$return_label_path_key));
		$this->assertNotEmpty($order->get_meta(Shipping::$return_label_url_key));
		$this->assertFileExists($order->get_meta(Shipping::$return_label_path_key));

		// check tracking number generation
		$this->assertNotEmpty($order->get_meta(Shipping::$return_trackingnumber_key));
		$this->assertNotEmpty($order->get_meta(Shipping::$return_trackingurl_key));

	}

	public function test_logistics_email_is_sent() {
		$order = wc_create_order();
		$order->add_product( self::$variation, 1);
		$order->set_address( $this->customerAddress, 'billing' );
		$order->calculate_totals();
		$order->update_status("processing", '', TRUE);

        // Multiple e-mails are sent
        // 0 -> Dein Babytuch Konto wurde erstellt
        // 1 -> Logistics Information
        $email = tests_retrieve_phpmailer_instance()->get_sent(1);

		$body = $email->body;

		// reload order to see changes in effect
		$order = new WC_Order($order->get_id());

		$this->assertContains('Content-Disposition: attachment', $body);

        // check shipping label is attached
        $shipping_label_path = $order->get_meta(Shipping::$shipping_label_path_key);
        $shipping_label_filename = basename($shipping_label_path);
        $this->assertContains("Content-Type: application/pdf; name=$shipping_label_filename", $body);

        // check return label is attached
        $return_label_path = $order->get_meta(Shipping::$return_label_path_key);
        $return_label_filename = basename($return_label_path);

        $this->assertContains("Content-Type: application/pdf; name=$return_label_filename", $body);

        $logistic_info_sent = $order->get_meta('_babytuch_logistic_information_sent');

		$this->assertNotEmpty($logistic_info_sent);

	}
}
