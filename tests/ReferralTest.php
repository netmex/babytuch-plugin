<?php

use Inc\Api\Address;
use Inc\Api\Helpers;
use Inc\Base\Shipping;
use Spatie\PdfToText\Pdf;


/**
 * Test case that tests generation of referral code and according PDF file
 */
class ReferralTest extends BT_TestCase {

    public function test_plugin_activated() {
        $plugins = get_option('active_plugins');
        $this->assertContains('refer-a-friend-babytuch_customized/gens-raf.php', $plugins);
    }


    /**
     * A single example test.
     */
    public function test_referral_code_is_generated() {

        $order = wc_create_order();
        $order->add_product( self::$variation, 2);
        $order->set_address( $this->customerAddress, 'billing' );
        $order->calculate_totals();
        $order->update_status("processing", '', TRUE);

        // reload order to see changes in effect
        $order = new WC_Order($order->get_id());

        // check that user was created for order
        $user = get_user_by('email', $this->customerAddress['email']);
        $this->assertIsObject($user);

        // check that 'gens_referral_id' was created for user that created order
        $referral_code = get_user_meta($user->ID, 'gens_referral_id', true);
        $this->assertIsString($referral_code);

        // check referral cards generation
        $this->assertNotEmpty($order->get_meta(Shipping::$referral_cards_path_key));
        $this->assertNotEmpty($order->get_meta(Shipping::$referral_cards_url_key));
        $this->assertFileExists($order->get_meta(Shipping::$referral_cards_path_key));

        // check that PDF does contain ref code
        $pdf_content = file_get_contents($order->get_meta(Shipping::$referral_cards_path_key), true);
        $matches = array();
        preg_match_all("/(raf=$referral_code)/U", $pdf_content, $matches);
        $this->assertNotEmpty($matches);

    }

    public function test_user_account_is_created_and_order_assigned() {
        $order = wc_create_order();
        $order->add_product( self::$variation, 2);
        $order->set_address( $this->customerAddress, 'billing' );
        $order->calculate_totals();

        $customer_id = $order->get_customer_id(); // should be none
        $this->assertEquals(0, $customer_id);

        $order->update_status("processing", '', TRUE);

        // reload order to see changes in effect
        $order = new WC_Order($order->get_id());

        $customer_id = $order->get_customer_id(); // should be something
        $this->assertNotEquals(0, $customer_id);

    }


    public function test_referral_code_is_generated_for_guest_with_existing_user() {

        // user checks-out as guest but has existing user account with same e-mail

        // create new user with email as username & newly created pw
        $user_id = wp_create_user( $this->customerAddress['email'], 1234, $this->customerAddress['email'] );

        // WC guest customer identification
        update_user_meta( $user_id, 'guest', 'yes' );

        //user's billing data
        update_user_meta( $user_id, 'billing_address_1', $this->customerAddress['address_1'] );
        update_user_meta( $user_id, 'billing_city', $this->customerAddress['city'] );
        update_user_meta( $user_id, 'billing_country', $this->customerAddress['country'] );
        update_user_meta( $user_id, 'billing_email', $this->customerAddress['email'] );
        update_user_meta( $user_id, 'billing_first_name', $this->customerAddress['first_name'] );
        update_user_meta( $user_id, 'billing_last_name', $this->customerAddress['last_name']);
        update_user_meta( $user_id, 'billing_phone', $this->customerAddress['phone'] );
        update_user_meta( $user_id, 'billing_postcode', $this->customerAddress['postcode'] );

        $order = wc_create_order();
        $order->add_product( self::$variation, 2);
        $order->set_address( $this->customerAddress, 'billing' );
        $order->calculate_totals();
        $order->update_status("processing", '', TRUE);

        // reload order to see changes in effect
        $order = new WC_Order($order->get_id());

        // check that user was created for order
        $user = get_user_by('email', $this->customerAddress['email']);
        $this->assertIsObject($user);

        // check that 'gens_referral_id' was created for user that created order
        $referral_code = get_user_meta($user->ID, 'gens_referral_id', true);
        $this->assertIsString($referral_code);

        // check that PDF does contain ref code
        $pdf_content = file_get_contents($order->get_meta(Shipping::$referral_cards_path_key), true);
        $matches = array();
        preg_match_all("/(raf=$referral_code)/U", $pdf_content, $matches);
        $this->assertNotEmpty($matches);

    }

    // TODO: write test that checks if user is created for 'guest' orders

    public function test_referral_cards_attached_to_email() {
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

        $referral_cards_path = $order->get_meta(Shipping::$referral_cards_path_key);
        $filename = basename($referral_cards_path);


        $this->assertContains("Content-Type: application/pdf; name=$filename", $body);
        $this->assertContains("Content-Disposition: attachment; filename=$filename", $body);
    }



    public function test_new_order_with_referral_code_cookie() {

        // create new user with email as username & newly created pw
        $user_id = wp_create_user( $this->customerAddress['email'], 1234, $this->customerAddress['email'] );

        $raf_id = get_user_meta($user_id, 'gens_referral_id', true);

        // set cookie with raf_id
        $_COOKIE["gens_raf"] = $raf_id;

        $checkout = new WC_Checkout();
        $order_id = $checkout->create_order([]);
        $order = wc_get_order($order_id);
        $order->add_product( self::$variation, 2);
        $order->set_address( $this->customerAddress, 'billing' );
        $order->calculate_totals();
        $order->save();

        //do_action( 'woocommerce_checkout_update_order_meta', $order->get_id(), []);

        $order->update_status("processing", '', TRUE);

        global $wp_actions;

        // reload order to see changes in effect
        $order = new WC_Order($order->get_id());

        // referral code used should be stored in `_raf_id` meta field of order
        $referral_id = $order->get_meta('_raf_id');

        $this->assertEquals($raf_id, $referral_id);

    }

}
