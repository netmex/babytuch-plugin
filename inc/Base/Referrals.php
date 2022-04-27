<?php

namespace inc\Base;

/**
 * Handles the customization of the referral functionality
 */
class Referrals
{
    public function register(){
        // create referral code for user if user is created manually
        add_action( 'user_register', [$this,'create_referral_id_for_user'], 10, 1 );

        // ensures guest user account is created for each order
        add_action( 'woocommerce_order_status_processing', [$this,'create_guest_user_account'], 10, 1 );

        // ensures that each user has a referral code by generating
        // a new one if it doesn't exist after creating an order
        add_action( 'woocommerce_order_status_processing', [$this,'check_and_generate_referral_code'], 11, 1 );

        // ensures that no guest order can be created with existing user
        add_action('woocommerce_after_checkout_validation', [$this, 'check_existing_user'], 10, 2);
    }

    /**
     * Checks if the user for the order already has a referral code and
     * creates one if that is not the case
     * @param $order_id
     * @return void
     */
    public function check_and_generate_referral_code($order_id) {
        if(!$order_id) return;

        $order = wc_get_order($order_id);
        $order_email = $order->get_billing_email();
        $user_id = email_exists( $order_email )? email_exists( $order_email ) : username_exists( $order_email );
        if(!$user_id) return;

        $this->create_referral_id_for_user($user_id);
    }

    /**
     * Creates a user account for the guest when an order is processed
     * @param $order_id
     * @return void
     */
    public function create_guest_user_account( $order_id ) {
        if(!$order_id) return;

        $order = wc_get_order($order_id);
        $order_email = $order->get_billing_email();

        $user_id = email_exists( $order_email )? email_exists( $order_email ) : username_exists( $order_email ) ;

        if(!$user_id) {

            // random password with 12 chars
            $random_password = wp_generate_password();

            // create new user with email as username & newly created pw
            $user_id = wp_create_user( $order_email, $random_password, $order_email );

            //WC guest customer identification
            update_user_meta( $user_id, 'guest', 'yes' );

            //user's billing data
            update_user_meta( $user_id, 'billing_address_1', $order->get_billing_address_1() );
            update_user_meta( $user_id, 'billing_address_2', $order->get_billing_address_2() );
            update_user_meta( $user_id, 'billing_city', $order->get_billing_city() );
            update_user_meta( $user_id, 'billing_company', $order->get_billing_company() );
            update_user_meta( $user_id, 'billing_country', $order->get_billing_country() );
            update_user_meta( $user_id, 'billing_email', $order->get_billing_email() );
            update_user_meta( $user_id, 'billing_first_name', $order->get_billing_first_name() );
            update_user_meta( $user_id, 'billing_last_name', $order->get_billing_last_name());
            update_user_meta( $user_id, 'billing_phone', $order->get_billing_phone() );
            update_user_meta( $user_id, 'billing_postcode', $order->get_billing_phone() );
            update_user_meta( $user_id, 'billing_state', $order->get_billing_state() );

            // user's shipping data
            update_user_meta( $user_id, 'shipping_address_1', $order->get_shipping_address_1() );
            update_user_meta( $user_id, 'shipping_address_2', $order->get_shipping_address_2() );
            update_user_meta( $user_id, 'shipping_city', $order->get_shipping_city() );
            update_user_meta( $user_id, 'shipping_company', $order->get_shipping_company() );
            update_user_meta( $user_id, 'shipping_country', $order->get_shipping_country());
            update_user_meta( $user_id, 'shipping_first_name', $order->get_shipping_first_name() );
            update_user_meta( $user_id, 'shipping_last_name', $order->get_shipping_last_name() );
            update_user_meta( $user_id, 'shipping_method', $order->get_shipping_method());
            update_user_meta( $user_id, 'shipping_postcode', $order->get_shipping_postcode());
            update_user_meta( $user_id, 'shipping_state', $order->get_shipping_state() );

            update_user_meta( $user_id, 'first_name', $order->get_billing_first_name() );
            update_user_meta( $user_id, 'last_name', $order->get_billing_last_name());


            // link past orders to this newly created customer
            wc_update_new_customer_past_orders( $user_id );

            //EMAIL TO CLIENT
            global $woocommerce, $wpdb;
            $mailer = $woocommerce->mailer();

            $user_fname = $order->get_billing_first_name();
            $url= get_home_url().'/mein-konto/';
            $faq_link = get_home_url()."/so-funktionierts/faq/";

            $user_message = "Hallo $user_fname,<br><br>  Schön, dass wir dich zu unseren Kunden und/oder Vermittler zählen dürfen. Dein Konto auf babytuch.ch wurde erstellt. Du kannst über diese Adresse darauf zugreifen: $url <br><br>";
            $user_message .="Der Benutzername lautet $order_email <br>";
            $user_message .="Dein Passwort lautet $random_password  <br><br>";
            $user_message .="Du benötigst das Konto für diese Aktionen: <br>
        - Bestellungen verfolgen <br>
        - Tragtücher umtauschen <br>
        - Rücksendungen avisieren und verfolgen <br>
        - Rückerstattungen und Vermittlungsprovisionen einsehen <br><br>
        ";
            $user_message .="Solltest du Unterstützung benötigen, findest du die meisten Antworten hier $faq_link
        <br><br>";
            $user_message .="Du kannst dein Konto jederzeit löschen. Damit erlischt aber jeglicher Anspruch auf die obenstehenden Leistungen.
        <br><br>";
            $user_message .="Liebe Grüsse <br>
        Neva von babytuch.ch
        <br>";
            $subject = "Dein babytuch.ch-Konto wurde erstellt";

            ob_start();
            wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Willkommen auf babytuch.ch' ) );
            echo str_replace( '{{name}}', $user_fname, $user_message );
            wc_get_template( 'emails/email-footer.php' );
            $message = ob_get_clean();
            $mailer->send( $order_email, $subject, $message);
        }
    }

    public function create_referral_id_for_user($user_id) {
        $referral_code = get_user_meta($user_id, 'gens_referral_id', true);
        if($referral_code) return;

        //RAF Generate Code
        $referral_code = $this->generate_referral_id();
        update_user_meta( $user_id, 'gens_referral_id', $referral_code );
    }

    public function check_existing_user($fields, $errors) {

        $email = $fields['billing_email'];

        if(email_exists( $email ) || username_exists( $email )) {
            $errors->add( 'validation', __( 'Für diese E-Mail Adresse existiert bereits ein Kundenkonto. Bitte melden Sie sich an.' ));
        }

    }

    private function generate_referral_id($randomString="ref"){
        $characters = "0123456789";
        for ($i = 0; $i < 7; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        // TODO: handle case where referral ID already exists

        return $randomString;
    }

}