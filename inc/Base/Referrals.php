<?php

namespace inc\Base;

use DateInterval;
use DateTime;
use Inc\Models\BT_OrderProcess;
use WP_Post;

/**
 * Handles the customization of the referral functionality
 */

class Referrals
{

    static string $referred_by_field_key = "field_64411f50197fb";
    static string $order_field_key = "field_64412001197fc";
    static string $premium_field_key = "field_644120a15f9ad";
    static string $status_field_key = "field_644120e2d7b4b";
    static string $raf_id_meta_field_key = "_raf_id";
    static int $premium_per_product = 5;

    public function register(){

        /**
         * User Registration
         * - Create referral id for user (user_meta 'gens_referral_id')
         *
         * User visits site with RAF
         * - Read 'raf' query string
         * - Check if 'raf' is a valid referral id
         * - If valid, create a cookie with the referral id
         *
         * User places an order
         * - Read 'raf' out of cookie
         * - Save it in order meta
         *
         * Order is completed
         * - Create referral for user
         */

        // create referral code for user if user is created manually
        add_action( 'user_register', [$this,'create_referral_id_for_user'], 10, 1 );

        // Save RAF ID in Order Meta after order is placed
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_raf_id_from_cookie'],2,1);

        // ensures guest user account is created for each order
        add_action( 'woocommerce_new_order', [$this,'create_guest_user_account'], 20, 1 );
        // old 'woocommerce_order_status_processing'

        // ensures that each user has a referral code by generating
        // a new one if it doesn't exist after creating an order
        add_action( 'woocommerce_order_status_processing', [$this,'check_and_generate_referral_code'], 11, 1 );

        // when a woocommerce order is processed, create a referral
        add_action('woocommerce_checkout_order_processed', [$this, 'create_referral'], 10, 1);
        // when a new refund order is created, create a referral
        add_action('woocommerce_order_status_awaiting-return', [$this, 'create_referral'], 10, 1);


        // cronjob to update status of referrals
        add_action('babytuch_referral_cronjob', [$this, 'update_referral_status']);


        // add referral notice to checkout page below submit button
        add_action('woocommerce_proceed_to_checkout', [$this, 'referral_checkout_notice'], 10, 0);

        // when a woocommerce order is refunded, update the referral status
        //add_action('woocommerce_order_status_refunded', [$this,'update_referral_status_on_return'], 10, 1);
        //add_action('woocommerce_order_status_partially-refunded', [$this,'update_referral_status_on_return'], 10, 1);
        //add_action('woocommerce_order_status_replaced', [$this,'update_referral_status_on_return'], 10, 1);
        add_action('woocommerce_order_status_returning', [$this,'update_referral_status_on_return'], 10, 1);

        //add_action('babytuch_return_end', [$this,'update_referral_status_on_return'],10,1);

        // For debugging purposes: run it on init, disable on production
        //add_action('init', [$this, 'update_referral_status']);

        add_action('init', [$this, 'schedule_referral_cronjob']);

        // TODO: add action to update status when woocommerce status changed to completed
        // TODO: add action to update status when woocommerce status changed to refunded

        // ensures that no guest order can be created with existing user
        add_action('woocommerce_after_checkout_validation', [$this, 'check_existing_user'], 10, 2);

        // create the post type for the referrals
        add_action('init', [$this,'create_referral_post_type'],0);

        // set the referral cookie
        add_action('init',[$this, 'set_referral_cookie'],0);

        // register the custom statuses for the referrals
        // add_action('init', [$this, 'add_referral_statuses'], 0);

        // In the post edit screen: adding the custom post status in the dropdown
        //add_action('admin_footer-post.php', [$this, 'append_post_status_list'], 0);

        // registers acf fields
        add_action('plugin_loaded',[$this,'register_referral_acf_fields'],0);

        // modify / add referral columns
        add_filter('manage_referral_posts_columns', [$this, 'referral_filter_posts_columns']);
        add_filter('manage_referral_posts_custom_column', [$this, 'referral_column'],10,2);

        // add bulk actions
        add_filter('bulk_actions-edit-referral', function($bulk_actions) {
            $bulk_actions['change-to-payment-pending'] = __("Status zu 'Auszahlung pendent' ändern", 'babytuch');
            $bulk_actions['change-to-paid'] = __("Status zu 'Ausgezahlt' ändern", 'babytuch');
            $bulk_actions['change-to-invalid'] = __("Status zu 'Ungültig' ändern", 'babytuch');
            return $bulk_actions;
        });

        add_filter('handle_bulk_actions-edit-referral', [$this, 'handle_bulk_actions_edit_referral'], 10, 3);
        add_filter('removable_query_args', [$this, 'remove_query_args'], 10, 1);

        add_action('admin_notices', [$this, 'referral_admin_notices']);

        //Show referral link on My Account Page
        add_action('woocommerce_before_my_account', [$this, 'account_page_show_link']);

        //Show  referral coupons
        //add_action('woocommerce_before_my_account',[$this,  'account_page_show_coupons']);

        add_action( 'woocommerce_account_referrals_endpoint', [$this,  'account_page_show_coupons'] );

        // display information on woocommerce order page in admin
        add_action( 'woocommerce_admin_order_data_after_order_details', [$this, 'show_admin_raf_notes']);

    }

    public function referral_checkout_notice() {

        if ( isset($_COOKIE["gens_raf"]) ) {
            $rafID = sanitize_text_field($_COOKIE["gens_raf"]);

            if($rafID) {
                // get user by raf id
                $user_id = $this->get_user_id_by_raf_id($rafID);
                if($user_id && $user_id != get_current_user_id()) {
                    $user = get_user_by('id', $user_id);
                    echo sprintf(__('<p class="f5">Bestellung vermittelt von: <br /><strong>%s</strong></p>', 'babytuch'), $user->first_name.' '.$user->last_name);
                }
            }
        }

    }

    public function referral_admin_notices() {
        // TODO: error handling
        if (!empty($_REQUEST['changed-to-paid'])) {
            $num_changed = (int) $_REQUEST['changed-to-paid'];
            printf('<div id="message" class="updated notice-success notice is-dismissable"><p>' . __("%d Vermittlungen als 'Ausgezahlt' markiert.", 'babytuch') . '</p></div>', $num_changed);
        } else if(!empty($_REQUEST['changed-to-invalid'])) {
            $num_changed = (int) $_REQUEST['changed-to-invalid'];
            printf('<div id="message" class="updated notice-success notice is-dismissable"><p>' . __("%d Vermittlungen als 'Ungültig' markiert.", 'babytuch') . '</p></div>', $num_changed);
        } else if(!empty($_REQUEST['changed-to-payment-pending'])) {
            $num_changed = (int) $_REQUEST['changed-to-payment-pending'];
            printf('<div id="message" class="updated notice-success notice is-dismissable"><p>' . __("%d Vermittlungen als 'Auszahlung pendent' markiert.", 'babytuch') . '</p></div>', $num_changed);
        }
    }

    /**
     * Makes sure that the query args for the notices are removed and the admin notices disappear after a page refresh
     */
    public function remove_query_args($removable_query_args) {
        $removable_query_args[] = 'changed-to-paid';
        $removable_query_args[] = 'changed-to-invalid';
        $removable_query_args[] = 'changed-to-payment-pending';
        return $removable_query_args;
    }

    public function handle_bulk_actions_edit_referral($redirect_url, $action, $post_ids) {
        if ($action == 'change-to-paid') {
            foreach ($post_ids as $post_id) {
                // TODO only allow it if previous status was 'pending_payment'
                //update_field(self::$status_field_key, 'paid', $post_id);
                $this->change_referral_status($post_id, 'paid');
            }
            $redirect_url = add_query_arg('changed-to-paid', count($post_ids), $redirect_url);
        } else if ($action == 'change-to-invalid') {
            foreach ($post_ids as $post_id) {
                // TODO only allow it if previous status was 'pending'
                //update_field(self::$status_field_key, 'invalid', $post_id);
                $this->change_referral_status($post_id, 'invalid');
            }
            $redirect_url = add_query_arg('changed-to-invalid', count($post_ids), $redirect_url);
        } else if ($action == 'change-to-payment-pending') {
            foreach ($post_ids as $post_id) {
                // TODO only allow it if previous status was 'pending'
                //update_field(self::$status_field_key, 'pending_payment', $post_id);
                $this->change_referral_status($post_id, 'pending_payment');
            }
            $redirect_url = add_query_arg('changed-to-payment-pending', count($post_ids), $redirect_url);
        }
        return $redirect_url;
    }

    public function update_referral_status_on_return(int $order_id) {
        $order_process = BT_OrderProcess::load_by_order_id($order_id);
        $order = wc_get_order($order_id);
        $referral = self::get_referral_by_order_id($order_id);
        if(!$referral) {return;}

        $status = get_field(self::$status_field_key, $referral->ID);

        // only do it when referral status is 'pending'
        // order_status ist immer noch auf "returning"
        if($status['value'] == 'pending') {

            if($order_process->isFullyRefunding()) {
                $this->change_referral_status($referral->ID, 'invalid');
                $order->add_order_note("Vermittlung wurde als 'Ungültig' markiert, da die Bestellung vollständig rückerstattet wird.");
            } elseif($order_process->isPartiallyRefunding()) {
                // Prämie der Vermittlung anpassen (minus die Tücher, die rückerstattet werden)
                $new_items = $order_process->getOrder()->get_item_count() - $order_process->getReturnProductsCount();
                $new_premium = $this->update_premium($referral->ID, $new_items);
                $this->change_referral_status($referral->ID, 'pending_payment');
                $order->add_order_note("Betrag der Vermittlung wurde reduziert auf CHF $new_premium, da die Bestellung teilweise rückerstattet wird.");
            } elseif($order_process->isFullyReplacing()) {
                $this->change_referral_status($referral->ID, 'invalid');
                $order->add_order_note("Vermittlung wurde als 'Ungültig' markiert, da die Bestellung vollständig umgetauscht wird.");
            } elseif($order_process->isPartiallyReplacing()) {
                // Prämie der Vermittlung anpassen (minus die Tücher, die umgetauscht werden)
                $new_items = $order_process->getOrder()->get_item_count() - $order_process->getReturnProductsCount();;
                $new_premium = $this->update_premium($referral->ID, $new_items);
                $this->change_referral_status($referral->ID, 'pending_payment');
                $order->add_order_note("Betrag der Vermittlung wurde reduziert auf CHF $new_premium, da die Bestellung teilweise umgetauscht wird.");
            }
        }
    }

    public function update_premium($referral_id, $product_count) {
        $new_premium = $product_count * self::$premium_per_product;
        update_field(self::$premium_field_key, $new_premium, $referral_id);
        return $new_premium;
    }

    public static function get_referral_by_order_id($order_id) {
        // see: https://www.advancedcustomfields.com/resources/querying-relationship-fields/
        $args = array(
            'post_type'       => 'referral',
            'post_status'     => 'publish',
            'posts_per_page'  => 1,
            'meta_query' => array(
                array(
                    'key' => 'order', // name of custom field
                    'value' =>  $order_id,
                    'compare' => 'LIKE'
                )
            )
        );

        // get referral that is associated with $order_id
        $posts = get_posts( $args );
        if(count($posts) == 0) return null;
        return $posts[0];
    }

    public function change_referral_status($post_id, $new_status) {
        if(!$post_id) return;
        $old_status = get_field(self::$status_field_key, $post_id);
        if($old_status['value'] == $new_status) return;

        // TODO: add action to hook into to connect with SQRIP

        //$order = wc_get_order(get_field(self::$order_field_key, $post_id));
        //$order->add_order_note("Status der Vermittlung auf '$new_status' geändert.");
        update_field(self::$status_field_key, $new_status, $post_id);
    }

    public function schedule_referral_cronjob() {
        if ( ! wp_next_scheduled( 'babytuch_referral_cronjob' ) ) {
            wp_schedule_event( time(), 'daily', 'babytuch_referral_cronjob' );
        }
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
     * Creates a user account for the guest when an order is processed and logs user in
     * @param $order_id
     * @return void
     */
    public function create_guest_user_account( $order_id ) {
        if(!$order_id) return;
        if(is_user_logged_in()) return;

        $order = wc_get_order($order_id);
        $order_email = $order->get_billing_email();

        $user_id = email_exists( $order_email )? email_exists( $order_email ) : username_exists( $order_email ) ;

        if(!$user_id) {

            // random password with 12 chars
            $random_password = wp_generate_password();

            // create new user with email as username & newly created pw
            $user_id = wp_create_user( $order_email, $random_password, $order_email );

            // WC guest customer identification
            // update_user_meta( $user_id, 'guest', 'yes' );

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

            wp_signon( array(
                'user_login'    => $order_email,
                'user_password' => $random_password,
                'remember'      => true
            ) );

        }
    }

    public function get_referral_id_for_user($user_id) {
        return get_user_meta($user_id, 'gens_referral_id', true);
    }

    public function create_referral_id_for_user($user_id) {
        // prevent that referral id is created twice
        $referral_code = $this->get_referral_id_for_user($user_id);
        if($referral_code) return $referral_code;

        // prevent duplicate referral ids
        do{
            $referral_code = $this->generate_referral_id();
        } while ($this->exists_ref_id($referral_code));

        update_user_meta( $user_id, 'gens_referral_id', $referral_code );
        return $referral_code;
    }

    /**
     * Check if ID already exists
     *
     * @since    1.0.0
     * @return string
     */
    public function exists_ref_id($referralID) {

        $args = array('meta_key' => "gens_referral_id", 'meta_value' => $referralID );
        if (get_users($args)) {
            return true;
        } else {
            return false;
        }

    }

    public function check_existing_user($fields, $errors) {
        // does not apply to logged in users
        if(is_user_logged_in()) return;

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
        return $randomString;
    }

    public function get_user_id_by_raf_id($referral_id) {
        $gens_user_ids = get_users( array(
            "meta_key" => "gens_referral_id",
            "meta_value" => $referral_id,
            "number" => 1,
            "fields" => "ID"
        ) );
        return $gens_user_ids[0];
    }

    public function create_referral($order_id) {
        $order = wc_get_order($order_id);
        $referral_id = sanitize_text_field(get_post_meta( $order_id, self::$raf_id_meta_field_key, true));

        // bail early if there is no referral ID
        if(!$referral_id) return;


        $user_id = $this->get_user_id_by_raf_id($referral_id);

        if(!$user_id) return null;

        // check that user did not use their own referral code
        if($user_id == $order->get_user_id()) return null;

        // check that no referral already exists for this order
        if(self::get_referral_by_order_id($order_id)) return null;

        $referral_amount = $order->get_item_count() * self::$premium_per_product; // TODO: make customizable

        // create referral
        $referral_id = wp_insert_post(
            array(
                'post_type' => 'referral',
                'post_status' => 'publish',
                'post_title' => 'Referral for order ' . $order_id,
                'post_author' => $user_id,
            )
        );
        update_field(self::$order_field_key, $order_id, $referral_id);
        update_field(self::$referred_by_field_key, $user_id, $referral_id);
        update_field(self::$status_field_key, 'pending', $referral_id);
        update_field(self::$premium_field_key, $referral_amount, $referral_id);

        return $referral_id;
    }

    public function update_referral_status() {

        $args = array(
            'post_type'       => 'referral',
            'post_status'     => 'publish',
            'posts_per_page'  => -1,
            'meta_query'      => array(
                array(
                    'key'         => 'status',
                    'value'       => 'pending',
                    'compare'   => '=',
                ),
            )
        );

        // get all referrals with status 'pending'
        $posts = get_posts( $args );

        foreach($posts as $post) {
            $order = wc_get_order(get_field(self::$order_field_key, $post->ID));
            $order_status = $order->get_status();
            if($order_status == 'completed') {

                $order_process = BT_OrderProcess::load_by_order_id($order->get_id());
                if(!$order_process) {
                    error_log( 'OrderProcess for order with ID '.$order->get_id().' could not be loaded' );
                    continue;
                }

                if(!$order_process->isWithinReturnDeadline()) {
                    // create WooCommerce notice
                    $order->add_order_note("Rückgabefrist ist abgelaufen.");

                    // Mark referral as valid and pending for payment
                    $this->change_referral_status($post->ID, 'pending_payment');
                    $this->gens_send_email(get_post($post->ID));
                }

            }
        }
    }

    public function create_referral_post_type() {
        $labels = array(
            'name'                  => _x( 'Vermittlungen', 'Post Type General Name', 'babytuch-plugin' ),
            'singular_name'         => _x( 'Vermittlung', 'Post Type Singular Name', 'babytuch-plugin' ),
            'menu_name'             => __( 'Vermittlungen', 'babytuch-plugin' ),
            'name_admin_bar'        => __( 'Vermittlung', 'babytuch-plugin' ),
            'archives'              => __( 'Item Archives', 'babytuch-plugin' ),
            'attributes'            => __( 'Item Attributes', 'babytuch-plugin' ),
            'parent_item_colon'     => __( 'Parent Item:', 'babytuch-plugin' ),
            'all_items'             => __( 'All Items', 'babytuch-plugin' ),
            'add_new_item'          => __( 'Add New Item', 'babytuch-plugin' ),
            'add_new'               => __( 'Add New', 'babytuch-plugin' ),
            'new_item'              => __( 'New Item', 'babytuch-plugin' ),
            'edit_item'             => __( 'Edit Item', 'babytuch-plugin' ),
            'update_item'           => __( 'Update Item', 'babytuch-plugin' ),
            'view_item'             => __( 'View Item', 'babytuch-plugin' ),
            'view_items'            => __( 'View Items', 'babytuch-plugin' ),
            'search_items'          => __( 'Search Item', 'babytuch-plugin' ),
            'not_found'             => __( 'Not found', 'babytuch-plugin' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'babytuch-plugin' ),
            'featured_image'        => __( 'Featured Image', 'babytuch-plugin' ),
            'set_featured_image'    => __( 'Set featured image', 'babytuch-plugin' ),
            'remove_featured_image' => __( 'Remove featured image', 'babytuch-plugin' ),
            'use_featured_image'    => __( 'Use as featured image', 'babytuch-plugin' ),
            'insert_into_item'      => __( 'Insert into item', 'babytuch-plugin' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'babytuch-plugin' ),
            'items_list'            => __( 'Items list', 'babytuch-plugin' ),
            'items_list_navigation' => __( 'Items list navigation', 'babytuch-plugin' ),
            'filter_items_list'     => __( 'Filter items list', 'babytuch-plugin' ),
        );
        $args = array(
            'label'                 => __( 'Vermittlung', 'babytuch-plugin' ),
            'description'           => __( 'Vermittlungen', 'babytuch-plugin' ),
            'labels'                => $labels,
            'supports'              => false,
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-money',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'page',
            'show_in_rest'          => false,
        );
        register_post_type( 'referral', $args );
    }

    public function register_referral_acf_fields() {
        if ( function_exists('acf_add_local_field_group') ):


            acf_add_local_field_group(array(
                'key' => 'group_64411f502c827',
                'title' => 'Vermittlungen',
                'fields' => array(
                    array(
                        'key' => self::$referred_by_field_key,
                        'label' => 'Vermittler',
                        'name' => 'referred_by',
                        'aria-label' => '',
                        'type' => 'user',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'role' => '',
                        'return_format' => 'id',
                        'multiple' => 0,
                        'allow_null' => 0,
                    ),
                    array(
                        'key' => self::$order_field_key,
                        'label' => 'Bestellung',
                        'name' => 'order',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'shop_order',
                        ),
                        'post_status' => '',
                        'taxonomy' => '',
                        'return_format' => 'id',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'ui' => 1,
                    ),
                    array(
                        'key' => self::$premium_field_key,
                        'label' => 'Prämie',
                        'name' => 'premium',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'min' => '',
                        'max' => '',
                        'step' => '',
                        'placeholder' => '',
                        'prepend' => 'CHF',
                        'append' => '',
                    ),
                    array(
                        'key' => self::$status_field_key,
                        'label' => 'Status',
                        'name' => 'status',
                        'aria-label' => '',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'pending' => 'Ausstehend',
                            'pending_payment' => 'Auszahlung pendent',
                            'paid' => 'Ausgezahlt',
                            'invalid' => 'Ungültig'
                        ),
                        'default_value' => 'within_return_period',
                        'return_format' => 'array',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'referral',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
            ));

        endif;
    }

    public function set_referral_cookie() {
         $cookie_expire = time()+60*60*24*30; // 30 days
        //cookie.set("gens_raf",$_GET["raf"],{ expires: $time, path:'/' });
        if ( !isset($_SESSION['raf'] ) && isset($_GET['raf']) ) {
            $_SESSION['raf'] = $_GET['raf'];
            setcookie('gens_raf', $_SESSION['raf'], $cookie_expire, '/', '.'.$_SERVER['HTTP_HOST']);
        }

        // make sure uppercase version also works
        if ( !isset($_SESSION['raf'] ) && isset($_GET['RAF']) ) {
            $_SESSION['raf'] = strtolower($_GET['RAF']);
            setcookie('gens_raf', $_SESSION['raf'], $cookie_expire, '/', '.'.$_SERVER['HTTP_HOST']);
        }

    }


    /**
     * Save the RAF ID to the order based on the cookie
     * @param $order_id
     * @return mixed
     */
    public function save_raf_id_from_cookie( $order_id ) {
        if ( isset($_COOKIE["gens_raf"]) ) {
            $rafID = sanitize_text_field($_COOKIE["gens_raf"]);
            $this::save_raf_id($order_id, $rafID);
        }
        return $order_id;
    }

    /**
     * Save RAF ID to order meta
     * @param $order_id
     * @param $raf_id
     * @return void
     */
    public static function save_raf_id($order_id, $raf_id) {
        update_post_meta( $order_id, self::$raf_id_meta_field_key, $raf_id);
    }

    public static function copy_raf_id($old_order, $new_order) {
        $raf_id = get_post_meta( $old_order->get_id(), self::$raf_id_meta_field_key, true );
        if ( $raf_id ) {
            self::save_raf_id($new_order->get_id(), $raf_id);
        }
    }

    /**
     * Modify the columns for the referral post type
     */
    public function referral_filter_posts_columns( $columns ) {

        /*
         * Titel (neu: "Datum, Zeit")
         *   Bestellung
         *   Vermittelt an ("Kunde")
         *   Anzahl Tücher
         *   Bestellstatus
         *   Vermittler
         *   Prämie
         *   Status
         */

        $columns = array(
            'cb' => $columns['cb'],
            'title' => __( 'Titel' ),
            'order' => __( 'Bestellung', 'babytuch' ),
            'referred_to' => __( 'Kunde', 'babytuch' ),
            'item_count' => __( 'Anzahl Tücher', 'babytuch' ),
            'order_status' => __('Bestellstatus', 'babytuch'),
            'referred_by' => __( 'Vermittler', 'babytuch'  ),
            'premium' => __( 'Prämie', 'babytuch' ),
            'status' => __( 'Status', 'babytuch' ),
        );

        return $columns;
    }



    /**
     * Add data to modified columns for the referral post type
     */
    public function referral_column( $column, $post_id ) {
        $order_id = get_field( 'order', $post_id );
        $order = wc_get_order( $order_id );
        if ( 'order' === $column ) {
            echo "<a href='/wp-admin/post.php?post=$order_id&action=edit'>".get_the_title($order_id)."</a>";
        } else if ( 'order_status' === $column) {
            printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $order->get_status() ) ), esc_html( wc_get_order_status_name( $order->get_status() ) ) );
            //echo wc_get_order_status_name( $order->get_status() );
        } else if ( 'item_count' === $column) {
            $item_count = $order->get_item_count();
            echo $item_count;;
        } else if ( 'premium' === $column) {
            echo get_field('premium',$post_id)." CHF";
        } else if ('status' === $column) {
            $status = get_field( 'status', $post_id );
            $value = $status['value'];
            $label = $status['label'];
            echo $label;
        } else if ('referred_by' === $column) {
            $referred_by_id = get_field('referred_by'); // WP_User array
            $referred_by = get_user_by('id', $referred_by_id);  // WP_User object
            echo $referred_by->user_firstname." ".$referred_by->user_lastname;
        } else if ('referred_to' === $column) {
            $referred_to_id = $order->get_user_id();
            if(!$referred_to_id) {
                echo "Gast";
            } else {
                $referred_to = get_user_by('id', $order->get_user_id());  // WP_User object
                echo $referred_to->user_firstname." ".$referred_to->user_lastname;
            }

        }
    }

    /**
     * Show Unique URL - get referral id and create link
     * woocommerce_before_my_account hook
     *
     * @since    1.0.0
     */
    public function account_page_show_link() {

        $referral_id = $this->get_referral_id_for_user( get_current_user_id() );
        $refLink = esc_url(add_query_arg( 'raf', $referral_id, get_home_url() ));
        ?>
        <div id="raf-message" class="woocommerce-message raf-message"><?php _e( 'Vermittlungsprogramm','babytuch'); ?><br/>
            <p class="f5 mt1 near-black mb1">Teile diese URL mit Babytuch-Interessierten. <br/> Für jedes verkaufte Babytuch über diese Adresse wirst du mit <strong>CHF 5.-</strong> belohnt.</p>
            <a href="<?php echo $refLink; ?>" class="mb2"><?php echo $refLink; ?></a>
        </div>
        <?php
    }

    public function get_referrals_for_user($user_id) {
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'referral',
            'meta_query' => array (
                'relation'      => 'AND',
                array(
                    'key'       => 'status',
                    'value'     => array('paid', 'pending_payment'),
                    'compare'   => 'IN',
                ),
                array (
                    'key' => 'referred_by',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
        );

        $referrals = get_posts( $args );
        return $referrals;
    }

    /**
     * Account page - list referral coupons
     * woocommerce_before_my_account hook
     *
     * @since    1.0.0
     */
    public function account_page_show_coupons() {

        $this->account_page_show_link();

        $referrals = $this->get_referrals_for_user(get_current_user_id());

        if($referrals) {?>

            <h2 class="mt4"><?php echo apply_filters( 'wpgens_raf_title', __( 'Meine Vermittlungen', 'gens-raf' ) ); ?></h2>
            <table class="shop_table shop_table_responsive">
            <tr>
                <th><?php _e('Datum','babytuch'); ?></th>
                <th><?php _e('Vermittelte Person','babytuch'); ?></th>
                <th><?php _e('Anzahl vermittelte Tücher','babytuch'); ?></th>
                <th><?php _e('Belohnung','babytuch'); ?></th>
                <th><?php _e('Status','babytuch'); ?></th>
            </tr>
            <?php
            $count=0;
            $total_amount = 0;
            $total_amount_open = 0;
            $count_open = 0;
            $total_refund=0;
            $total_refund_open=0;
            foreach ( $referrals as $referral ) {

                $referral_order = wc_get_order( get_field( 'order', $referral->ID ) );
                $referral_order_username = $referral_order->get_billing_first_name()." ".$referral_order->get_billing_last_name();
                $referral_premium = get_field('premium', $referral->ID);
                $referral_item_count = $referral_order->get_item_count();

                setlocale(LC_TIME, 'de_DE');
                $referral_date = strftime('%d. %B %Y',get_post_timestamp($referral->ID));

                $referral_status = get_field( 'status', $referral->ID );
                $referral_label = $referral_status['label'];

                if($referral_status['value'] === 'paid'){
                    $total_amount = $total_amount + $referral_item_count;
                    $total_refund = $total_refund + $referral_premium;
                    $count++;
                }elseif($referral_status['value'] === 'pending' || $referral_status['value'] === 'pending_payment'){
                    $total_amount_open = $total_amount_open + $referral_item_count;
                    $total_refund_open = $total_refund_open + $referral_premium;
                    $count_open++;
                }
                echo '<tr>';
                echo '<td>'.$referral_date.'</td>';
                echo '<td>'.$referral_order_username.'</td>';
                echo '<td>'.$referral_item_count.'</td>';
                echo '<td>'.$referral_premium.' CHF</td>';
                echo '<td>'.$referral_label.'</td>';
                echo '</tr>';
            }

            if($count_open != 0) {

                echo '<tr>';
                echo '<td colspan="2"><b>Offene Belohnungen Total ('.$count_open.')</b></td>';
                echo '<td>'.$total_amount_open.'</td>';
                echo '<td>'.$total_refund_open.' CHF</td>';
                echo '<td></td>';
                echo '</tr>';
            }
            if($count != 0) {

                echo '<tr>';
                echo '<td colspan="2"><b>Abgeschlossene Belohnungen Total ('.$count.')</b></td>';
                echo '<td>'.$total_amount.'</td>';
                echo '<td>'.$total_refund.' CHF</td>';
                echo '<td></td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        $current_iban = get_user_meta(get_current_user_id(), 'iban_num');

        ?>

        <h4 class="mt4">Meine IBAN-Nr</h4>
        <p>Wird nur für Rückerstattung und Vermittlungsprogramm verwendet</p>
        <form method="post" action="">
            <label>IBAN-Nr: </label>
            <input style="width:300px;" type="text" placeholder="CH1908518016000520000" name="iban" value=<?php if(!empty($current_iban)){echo $current_iban[0];}?>>
            <br><br><input type="submit" value="Speichern" name="save">
        </form>

        <?php
        if(isset($_POST['save'])){
            echo 'IBAN-Nr. erfolgreich gespeichert.';
            update_user_meta(get_current_user_id(), 'iban_num', trim($_POST['iban']));
        }
    }

    /**
     * Send Email to user in case of successful referral
     *
     * @param WP_Post $referral
     * @since    1.0.0
     */
    public function gens_send_email(WP_Post $referral) {

        $referral_order = wc_get_order( get_field( 'order', $referral->ID ) );
        $referral_premium = get_field('premium', $referral->ID);
        $referral_item_count = $referral_order->get_item_count();

        $iban = get_user_meta($referral->post_author, 'iban_num', true);
        $referred_by = get_userdata($referral->post_author);

        $fn = $referred_by->first_name;
        $fn2 = $referral_order->get_billing_first_name();
        $ln2 = $referral_order->get_billing_last_name();

        $subject = "Babytuch Weitervermittlung";
        $user_message = "Hallo $fn <br><br>";

        if($iban){
            $user_message .= "Du hast uns <strong>$fn2 $ln2</strong> als neuen Kunden vermittelt (Danke!) und 
			dir damit <strong>CHF $referral_premium</strong> verdient – Herzliche Gratulation! <br><br>Wir werden dir in den nächsten Tagen den Betrag auf dein Bankkonto mit der IBAN $iban überweisen.<br><br>";
        } else {
            $url = get_home_url().'/mein-konto/my-iban/';
            $user_message .= "Du hast uns <strong>$fn2 $ln2</strong> als neuen Kunden vermittelt (Danke!) und dir 
			damit <strong>CHF $referral_premium</strong> verdient – Herzliche Gratulation! <br><br>Damit wir dir diesen Betrag ausbezahlen
			 können, benötigen wir noch die IBAN deines Bankkontos. Bitte erfasse diese in deinem 
			 Konto $url.<br><br>";
        }
        $user_message .= "Liebe Grüsse<br>Neva von babytuch.ch";


        global $woocommerce;
        $mailer = $woocommerce->mailer();

        ob_start();
        wc_get_template( 'emails/email-header.php', array( 'email_heading' => $subject ) );
        echo $user_message;
        wc_get_template( 'emails/email-footer.php' );
        $message = ob_get_clean();
        // Debug wp_die($user_email);
        $mailer->send( $referred_by->user_email, $subject, $message);

    }

    /**
	 * Show referral details on order screen:
	 *
	 * @since    1.0.0
	 */
	public function show_admin_raf_notes($order) {

		$order_id = ( version_compare( WC_VERSION, '2.7', '<' ) ) ? $order->id : $order->get_id();

		$referralID = get_post_meta( $order_id, '_raf_id', true );

		if (!empty($referralID)) {

			$args = array('meta_key' => "gens_referral_id", 'meta_value' => $referralID );
			$user = get_users($args);

            $referral = $this->get_referral_by_order_id($order_id);
			?>
		    <div class="form-field form-field-wide">
		        <h4><?php _e( 'Vermittlungsprogramm:', 'gens-raf' ); ?></h4>
		        <p>
		            <strong><?php _e( 'Vermittelt durch:','gens-raf' ); ?></strong> <a href="<?php echo get_edit_user_link($user[0]->id); ?>"><?php echo $user[0]->user_email; ?></a><br>
		            <?php if($referral): ?>
		                <strong><a href="<?php echo get_edit_post_link($referral); ?>">Vermittlung <?php echo $referral->ID; ?></a></strong><br>
		                <?php $status = get_field('status', $referral->ID); ?>
		                Status: <?php echo $status['label']; ?>
		                <?php else: ?>
		                Vermittlung wird erst erstellt, wenn Bestellung verarbeitet wird.
		            <?php endif; ?>
                </p>
		    </div>
            <?php
    	}
	}

}