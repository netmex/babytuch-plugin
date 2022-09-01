<?php
/**
 * @package BabytuchPlugin
 */
/*
Plugin Name: Babytuch Plugin
Plugin URI: http://github.com/davebasler
Description: Backend Logic for the babytuch.ch webshop.
Version: GITHUB_VERSION
Author: Dave Basler
Author URI: http://github.com/davebasler
License: GPLv2 or later
Text Domain: babytuch_plugin
*/


use Inc\Models\BT_OrderProcess;

// If this file is called directly, abort!!!
defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );


define( 'BABYTUCH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BABYTUCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// will force TCPDF to throw exceptions
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);


// Require once the Composer Autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
* Load WooCommerce Customizations
*/
require_once dirname( __FILE__ ) . '/woocommerce/functions.php';

/**
 * The code that runs during plugin activation
 */
function activate_babytuch_plugin() {
	Inc\Base\Activate::activate();

}
register_activation_hook( __FILE__, 'activate_babytuch_plugin' );

/**
 * The code that runs during plugin deactivation
 */
function deactivate_babytuch_plugin() {
	Inc\Base\Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_babytuch_plugin' );

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( 'Inc\\Init' ) ) {
	Inc\Init::register_services();
}        

function save_post_callback( $post_id ) {
	if('shop_order' == get_post_type($post_id)){
		global $wpdb;
		$order = wc_get_order($post_id);
		//DECREASE AMOUNTS OF SUPPLEMENTS BY 1 and 3
		if('wc-processing'== get_post_status( $post_id )){
            $res = $wpdb->get_results("
            SELECT * FROM babytuch_inventory 
            WHERE item_name = 'packagings' OR item_name = 'labels' 
			OR item_name = 'supplements' OR item_name = 'packagings_large'
			OR item_name = 'referral_labels'"
            );
            $res_json = json_decode(json_encode($res), true);
            $amount_normal = $res_json[0]["amount"];
            $amount_normal_large = $res_json[3]["amount"];
            $amount_labels = $res_json[1]["amount"];
			$amount_supplements = $res_json[2]["amount"];
			$amount_referral_labels = $res_json[4]["amount"];

            $amount_normal_new = (int)$amount_normal - 1;
            $amount_normal_large_new = (int)$amount_normal_large - 1;
            $amount_labels_new = (int)$amount_labels - 1;
			$amount_supplements_new = (int)$amount_supplements - 1;
			$amount_referral_labels_new = (int)$amount_referral_labels - 1;
			
			// Database actions code
			$small_package_limiter = get_option('small_package_limiter');
			$num_items = $order->get_item_count();
			if($num_items < (int)$small_package_limiter ){
				//$value = $wpdb->query("UPDATE wp_options SET option_value = option_value-1 WHERE option_name='packaging_amount'");
                $value = $wpdb->query( 
                    $wpdb->prepare( "
                        UPDATE babytuch_inventory SET amount = %s
                        WHERE item_name = 'packagings'", 
                        $amount_normal_new
                    ) 
                ); 
            }else{
				//$value = $wpdb->query("UPDATE wp_options SET option_value = option_value-1 WHERE option_name='packaging_big_amount'");
                $value = $wpdb->query( 
                    $wpdb->prepare( "
                        UPDATE babytuch_inventory SET amount = %s
                        WHERE item_name = 'packagings_large'", 
                        $amount_normal_large_new
                    ) 
                );   
            }
            $value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET amount = %s
                    WHERE item_name = 'labels'", 
                    $amount_labels_new
                ) 
            );   
            $value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET amount = %s
                    WHERE item_name = 'supplements'", 
                    $amount_supplements_new
                ) 
			);        
			$value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET amount = %s
                    WHERE item_name = 'referral_labels'", 
                    $amount_referral_labels_new
                ) 
            );            
			//$value = $wpdb->query("UPDATE wp_options SET option_value = option_value-3 WHERE option_name='label_amount'");
			//$value = $wpdb->query("UPDATE wp_options SET option_value = option_value-1 WHERE option_name='supplement_amount'");

            //wp-inventory
            /*
			$value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
			WHERE option_name='packaging_amount') WHERE item_name='packagings'"); 
			$value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
			WHERE option_name='label_amount') WHERE item_name='labels'");
			$value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
			WHERE option_name='supplement_amount') WHERE item_name='supplements'");
			$value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
            WHERE option_name='packaging_big_amount') WHERE item_name='packagings_large'");
            */
		}
		
		$order_data = $order->get_data();
		$order_status = $order_data["status"];
		//$current_wc_status = get_post_status( $post_id );
		$value = $wpdb->query( 
			$wpdb->prepare("
				UPDATE babytuch_order_process SET order_status = %s
				WHERE order_id = %s", 
				$order_status, $post_id 
			) 
		);

		//RESUPPLY CHECK
		//IF LESS THAN $new_order_limit PACKAGINGS, SEND E-MAIL
		/*$package_amount = get_option('packaging_amount');
		$new_order_limit = get_option('packaging_limit');
		$new_order_amount = get_option('packaging_new_order_amount');
		$new_order_sent = get_option('packaging_new_order_sent');
		$last_order_date = get_option('packaging_last_order_date');
		$last_check_date = get_option('packaging_last_check_date');
		$interval = get_option('packaging_interval');
		$old_amount_1 = get_option('packaging_amount_1');
		$old_amount_2 = get_option('packaging_amount_2');
		$package_amount_int = (int)$package_amount;
		$new_order_limit_int = (int)$new_order_limit;
		$new_order_sent_int = (int)$new_order_sent;

		if($package_amount_int <= $new_order_limit_int and $new_order_sent_int != 1){
			$subject = 'Nachbestellung für Verpackungen.';
			$message = "Guten Tag, Hiermit bestellen wir ". $new_order_amount ." neue Verpackungen.";
			wp_mail( 'davebasler@hotmail.com', $subject, $message );

			$value = $wpdb->query("UPDATE babytuch_inventory SET new_order_sent = true WHERE id=1");
			update_option('packaging_new_order_sent', true);
		}*/
	}
	
}
add_action( 'save_post', 'save_post_callback', 10 );





//Products
function mp_sync_on_product_save( $product_id ) {
    $product = wc_get_product( $product_id );
	global $wpdb;
            
    $table_name = 'babytuch_inventory';

    if($product->is_type( 'variable' )){
        $children   = $product->get_children();
        foreach($children as $child){
            $child_product = wc_get_product($child);
            $product_slug = $child_product->get_slug();
            $product_id = $child_product->get_id();
            $product_amount = $child_product->get_stock_quantity();
            $created = $child_product->get_date_created();
            /*$attr = $child_product->get_attributes();
            $size = $attr["groesse"];
            $product_slug = $product_slug.'-'.$size;*/

            if(new_product($product_slug)){
                $wpdb->insert(
                    $table_name,
                    array(
                        'time' => $created->date("Y-m-d H:i:s"),
                        'item_name' => $product_slug,
                        'amount' => $product_amount,
                        'new_order_amount' => 500,
                        'new_order_limit' => 50,
                        'new_order_sending' => false,
                        'receiving_code' => generate_code($product_id),
                    )
                );
            }
            else{
                $value = $wpdb->query(
                    $wpdb->prepare( "
                        UPDATE babytuch_inventory SET amount = %s
                        WHERE item_name = %s",
                        $product_amount ,$product_slug
                    )
                );
            }
        }
    }

    //DELETE OLD ROWS
    $products = wc_get_products( array('numberposts' => -1) );
    $all_current_slugs = array();
    foreach($products as $product){
        if($product->is_type( 'variable' )){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    array_push($all_current_slugs, $child_name);

                }
            }
        }
    }
    foreach($products as $product){
        if($product->is_type( 'variable' )){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    if(!(in_array($child_name,$all_current_slugs,true))){
                        $value = $wpdb->query(
                            $wpdb->prepare( "
                            DELETE FROM babytuch_inventory
                                WHERE item_name = %s",
                                $product_slug
                            )
                        );
                    }
                }
            }
        }
    }
    /*else{
        $created = $product->get_date_created();
        $product_slug = $product->get_slug();
        $product_id = $product->get_id();
        $product_amount = $product->get_stock_quantity();
        if(new_product($product_slug)){
            $wpdb->insert(
                $table_name,
                array(
                    'time' => $created,
                    'item_name' => $product_slug,
                    'amount' => $product_amount,
                    'new_order_amount' => 500,
                    'new_order_limit' => 50,
                    'new_order_sending' => false,
                    'receiving_code' => generate_code($product_id),
                )
            );
        }
        else{
            $value = $wpdb->query(
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET amount = %s
                    WHERE item_name = %s",
                    $product_amount ,$product_slug
                )
            );
        }
    }*/
		
		

    //IF LESS THAN $new_order_limit PRODUCT, SEND E-MAIL
	/*$package_amount = $wpdb->get_var(
		$wpdb->prepare( "
			SELECT amount FROM babytuch_inventory
			WHERE item_name= %s",
			$product_slug
		)
	);
	$new_order_limit = $wpdb->get_var(
		$wpdb->prepare( "
			SELECT new_order_limit FROM babytuch_inventory
			WHERE item_name= %s",
			$product_slug
		)
	);
	$new_order_amount = $wpdb->get_var(
		$wpdb->prepare( "
			SELECT new_order_amount FROM babytuch_inventory
			WHERE item_name= %s",
			$product_slug
		)
	);
	$new_order_sent = $wpdb->get_var(
		$wpdb->prepare( "
			SELECT new_order_sent FROM babytuch_inventory
			WHERE item_name= %s",
			$product_slug
		)
	);
    $package_amount_int = (int)$package_amount;
    $new_order_limit_int = (int)$new_order_limit;*/
    //$new_order_sent_int = (int)$new_order_sent;

    /**if($package_amount_int <= $new_order_limit_int and $new_order_sent_int != 1){
        $subject = "Nachbestellung für Produkt ".$product_slug.".";
        $message = "Guten Tag, Hiermit bestellen wir ". $new_order_amount ." neue Produkte ("
            . $product_slug .").";
        wp_mail( 'davebasler@hotmail.com', $subject, $message );

        $value = $wpdb->query(
            $wpdb->prepare( "
                UPDATE babytuch_inventory SET new_order_sent = true
                WHERE item_name = %s",
                $product_slug
            )
        );
    }
    **/
}

function new_product($name){
	global $wpdb;
	$is_new=false;
	$products = $wpdb->get_results( 
		$wpdb->prepare( "
			SELECT * FROM babytuch_inventory
			WHERE item_name = %s", 
			$name
		) 
	);
	$products_json = json_decode(json_encode($products), true);
    if($products_json == null){
		$is_new = true;
	}
	return $is_new;
}

add_action( 'woocommerce_update_product', 'mp_sync_on_product_save', 10, 1 );



function generate_code($order_id){
	$chars = "ABCDEFGHIJKLMNOPQRSTVUVWXYZ0123456789"; 
    srand((double)microtime()*1000000); 
    $i = 0; 
    $pass = '' ;
    while ($i <= 7) { 
        $num = rand() % 33; 
        $tmp = substr($chars, $num, 1); 
        $pass = $pass . $tmp; 
        $i++; 
	} 
	$pass = $pass . $order_id;
	return $pass;
}


//RÜCKSENDE STATUS FUNKTION
//add_action('woocommerce_order_status_changed', 'return_mail_test', 10, 4);
function return_mail_test( $order_id, $from_status, $to_status, $order ) {

   if( $order->has_status( 'returning' )) {

        // Getting all WC_emails objects
		$email_notifications = WC()->mailer()->get_emails();
		
        // Customizing Heading and subject In the WC_email processing Order object
        $email_notifications['WCReturnMail']->heading = __('Sie senden Ihre Bestellung zurück an uns.','woocommerce');
		$email_notifications['WCReturnMail']->subject = 'Rücksendung.';

        // Sending the customized email
        //$email_notifications['WCReturnMail']->trigger( $order_id );
    }

}

//INVOICE PDF CUSTOMIZATION
/* add_action( 'wpo_wcpdf_after_item_meta', 'wpo_wcpdf_show_product_attributes', 10, 3 );
function wpo_wcpdf_show_product_attributes ( $template_type, $item, $order ) {
    if(empty($item['product'])) return;
    $document = wcpdf_get_document( $template_type, $order );
    printf('<div class="product-attribute">Attribute name: %s</div>', $document->get_product_attribute('Attribute name', $item['product']));
}
*/


//KUNDENMENU CUSTOMIZING

/**add_action( 'woocommerce_before_checkout_form', function() {
		echo '<p>Don\'t forget to include your unit number in the address!</p>';
});**/
	



/**
 * Endpoint HTML content.
 */
function my_custom_contact_content() {
	$client = wp_get_current_user();
	if ( $client->exists() ) {
		$client_id = $client->ID;
		$first_name = $client->first_name;
		$last_name = $client->last_name;
		$client_email = $client->user_email;
	 }    
	
	echo ''?>
	<html>
	<body>
	<h4>Kontakt</h4>
	<form method="post" action="">
	<label>Ihre Nachricht </label>
	<textarea name="message" rows="7" cols="70"></textarea>
	<br><br><input type="submit" value="Nachricht senden" name="send">
	</form> 
	</body>
	</html>
	<?php
	if(isset($_POST['send'])){
		$message = $_POST['message'];
		$msg = "Neue Nachricht von Kunde #$client_id $first_name $last_name (E-Mail: $client_email): ";
		$msg .= '<br><br>';
		$msg .= $message;
		$babytuch_admin_email = get_option('babytuch_admin_email');
		$headers = array('Content-Type: text/html; charset=UTF-8');
		$value = wp_mail($babytuch_admin_email, 'Kundensupport', $msg, $headers);
		if($value){
			echo'Nachricht erfolgreich gesendet';
		}else{
			echo'Nachricht fehlgeschlagen. Bitte nochmals probieren.';
		}
		
	}
}

$endpoint = 'contact_form';
add_action( 'woocommerce_account_' . $endpoint .  '_endpoint', 'my_custom_contact_content' );




/*
 * Change endpoint title.
 *
 * @param string $title
 * @return string
 */
function my_custom_referrals_title( $title ) {
	global $wp_query;

	$is_endpoint = isset( $wp_query->query_vars['referrals'] );

	if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
		// New page title.
		$title = __( 'Vermittlungen', 'woocommerce' );

		remove_filter( 'the_title', 'my_custom_referrals_title' );
	}

	return $title;
}
add_filter( 'the_title', 'my_custom_referrals_title' );

/*
 * Change endpoint title.
 *
 * @param string $title
 * @return string
 */
function my_custom_deletion_title( $title ) {
	global $wp_query;

	$is_endpoint = isset( $wp_query->query_vars['deletion'] );

	if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
		// New page title.
		$title = __( 'Konto Löschen', 'woocommerce' );

		remove_filter( 'the_title', 'my_custom_deletion_title' );
	}

	return $title;
}

add_filter( 'the_title', 'my_custom_deletion_title' );
/*
 * Change endpoint title.
 *
 * @param string $title
 * @return string
 */
function my_custom_contact_title( $title ) {
	global $wp_query;

	$is_endpoint = isset( $wp_query->query_vars['contact_form'] );

	if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
		// New page title.
		$title = __( 'Kontakt', 'woocommerce' );

		remove_filter( 'the_title', 'my_custom_contact_title' );
	}

	return $title;
}

add_filter( 'the_title', 'my_custom_contact_title' );


/*
 * Change endpoint title.
 *
 * @param string $title
 * @return string
 */
function my_custom_endpoint_title( $title ) {
	global $wp_query;

	$is_endpoint = isset( $wp_query->query_vars['my-iban'] );

	if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
		// New page title.
		$title = __( 'IBAN-Nr.', 'woocommerce' );

		remove_filter( 'the_title', 'my_custom_endpoint_title' );
	}

	return $title;
}

add_filter( 'the_title', 'my_custom_endpoint_title' );




/*add_action('wpo_wcpdf_after_customer_notes','add_mwst_num');
function add_mwst_num(){
	$mwst = get_option('company_mwst_number');

	echo"CHE-$mwst MWST";
}*/

/*add_filter( 'woocommerce_email_attachments', 'sqrip_attachment', 10, 3);
function sqrip_attachment ( $attachments , $email_id, $order ) {

	// Avoiding errors and problems
    if ( ! is_a( $order, 'WC_Order' ) || ! isset( $email_id ) ) {
        return $attachments;
    }
    
  
	//$file_path = ABSPATH.'/wp-content/uploads/2020/08/270820201598523581.pdf';
  
	if ( $email_id == 'customer_on_hold_order' ){
        //$attachments[] = $file_path;
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $file_path = get_post_meta($order_id, 'pm_pdf_file', true);
        $temp = substr($file_path, -30);
        $file_path = ABSPATH.'wp-content/uploads/'.$temp;
        $attachments[] = $file_path;
		return $attachments;
	}  else {
		return $attachments;
	}
}*/


/**
 * CUSTOM E-MAILS FÜR KUNDEN
 *  Add a custom email to the list of emails WooCommerce should load
 *
 * @since 0.1
 * @param array $email_classes available email classes
 * @return array filtered available email classes
 */
function add_return_order_woocommerce_email( $email_classes ) {

	// include our custom email class
	require_once( 'inc/Mails/WCReturnMail.php' );

	// add the email class to the list of email classes that WooCommerce loads
	$email_classes['WCReturnMail'] = new WCReturnMail();

	return $email_classes;

}
//add_filter( 'woocommerce_email_classes', 'add_return_order_woocommerce_email' );


//PASSWORD RESET EMAIL
function wpse_password_reset( $user) {
	global $woocommerce, $wpdb;
	$mailer = $woocommerce->mailer();

	$order_email = $user->user_email;
	$user_fname = $user->first_name;
	
	$user_message = "Hallo $user_fname,<br><br>  Du hast erfolgreich dein Passwort geändert. 
	Wenn du keine Änderung vorgenommen hast, kontaktiere uns bitte (admin@babytuch.ch).<br><br>";
	$user_message .="Liebe Grüsse <br>
	Neva von babytuch.ch
	<br>";
	$subject = "[Babytuch] Passwort geändert";

	
	ob_start();
	wc_get_template( 'emails/email-header.php', array( 'email_heading' => '[Babytuch] Passwort geändert' ) );
	echo str_replace( '{{name}}', $user_fname, $user_message );
	wc_get_template( 'emails/email-footer.php' );
	$message = ob_get_clean();
	// Debug wp_die($user_email);
	$mailer->send( $order_email, $subject, $message);
} 
add_action( 'woocommerce_customer_reset_password', 'wpse_password_reset', 10, 1 ); 


//ON HOLD EMAIL
//add_action( 'woocommerce_thankyou', 'send_on_hold' );
function send_on_hold($order_id){
	global $woocommerce, $wpdb;
	$mailer = $woocommerce->mailer();
  
	$order = wc_get_order($order_id);
	$user_fname = $order->get_billing_first_name('view');
	$first_name = $order->get_shipping_first_name('view');
	  if($first_name==''){
		  $first_name = $order->get_billing_first_name('view');
	  }
	  $last_name = $order->get_shipping_last_name('view');
	  if($last_name==''){
		  $last_name = $order->get_billing_last_name('view');
	  }
	  $street = $order->get_shipping_address_1('view');
	  if($street==''){
		  $street = $order->get_billing_address_1('view');
	  }
	  $city = $order->get_shipping_city('view');
	  if($city==''){
		  $city = $order->get_billing_city('view');
	  }
	  $zip = $order->get_shipping_postcode('view');
	  if($zip==''){
		  $zip = $order->get_billing_postcode('view');
	}
	
  $order_date = $order->get_date_created();
  $order_date_form = $order_date->date('d.m.Y');
  $user_email = $order->get_billing_email();
  $items = $order->get_items();
	
	$user_message = "Hallo $user_fname,<br><br> Danke für deine Bestellung vom $order_date_form auf babytuch. Sie hat bei uns die Bestellnummer #$order_id erhalten.
	<br><br>";
	$user_message .="Folgende Produkte warten bei uns auf den Versand:<br><br>";
	$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
	<thead>
		<tr>
			<th style='text-align:left;'>Produkte</th>
			<th style='text-align:right;'>Anzahl</th>
			<th style='text-align:right;'>Preis (CHF)</th>
		</tr>
	</thead>
	<tbody>";
	$items = $order->get_items();
	$total_price=0;
	foreach( $items as $product ) {
	  $amount = $product->get_quantity();
	  
		  $product_id = $product['product_id']; 
		  $product_obj = wc_get_product($product_id);
		  $product_id = $product_obj->get_id();
  
		  $data = $product->get_data();
		  $variation_id = $data["variation_id"];
		  $variation_obj = wc_get_product($variation_id);
		  $size = $variation_obj->get_attributes();
		  $size2 = $size["groesse"];
		  $name = $product['name']; 
		  $name2 = substr($name, -3,-2);
		  $name3 = substr($name, -4,-3);
		  if($name2=='-'){
			  $real_name=substr($name, 0,-4);
		  }elseif($name3=='-'){
			$real_name=substr($name, 0,-5);
		  }else{
			  $real_name=$name;
		  }
		  $price = $variation_obj->get_price();
		  $price2 = number_format((float)$price*$amount, 2, '.', '');
		  $user_message .= "<tr>
		  <td>$real_name, Grösse $size2</td>
		  <td style='text-align:right;'>$amount</td>
		  <td style='text-align:right;'>$price2</td>
		  </tr>";
		  $total_price= $total_price+(int)$price2;
	}
	$total_price2 = number_format((float)$total_price, 2, '.', '');
	$user_message .= "<tr>
	<td><b>Warenwert</b></td>
	<td></td>
	<td style='text-align:right;'><b>$total_price2</b></td>
	</tr>";
	$user_message .="</tbody>
  </table>
	<br><br>";
	$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
	<thead>
		<tr>
			<th></th>
			<th></th>
		</tr>
	</thead>
	<tbody>";
	$shipping_method = $order->get_shipping_method();
	$shipping_cost = $order->get_shipping_total();
	$inter_cost = $total_price+(int)$shipping_cost;
	$mwst = get_option('company_mwst_number');
	global $wpdb;
	$table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
	$res = $wpdb->get_results( 
	"SELECT * FROM $table_name2
		WHERE meta_key  = 'rate_percent'"
		
	);
	$res_json = json_decode(json_encode($res), true);
	$tax = (float)end($res_json)["meta_value"];
	$mwst_cost = round($inter_cost*(0.01*$tax),2);
	$endcost = $order->get_total();
  
	  $user_message .= "<tr>
	  <td>Lieferung ($shipping_method)</td>
	  <td>$shipping_cost</td>
	  </tr>";
	  $user_message .= "<tr>
	  <td>Bezahlung vor Lieferung mittels QR-Rechnung</td>
	  <td>0.00</td>
	  </tr>";
	  $user_message .= "<tr>
	  <td>Zwischensumme</td>
	  <td>$inter_cost</td>
	  </tr>";
	  $user_message .= "<tr>
	  <td>CHF-$mwst MWST ($tax%)</td>
	  <td>$mwst_cost</td>
	  </tr>";
	  $user_message .= "<tr>
	  <td>Gesamt</td>
	  <td>$endcost</td>
	  </tr>";
	$user_message .="</tbody>
  </table>
	<br><br>";
	$user_message .="<div style='color:#bf4040'>Verwende für die Bezahlung bitte den beiliegenden Einzahlungsschein. Sobald die Zahlung bei uns eingetroffen ist, werden wir deine Lieferung bereitstellen und an diese Adresse versenden.
	</div><br><br>";
	$user_message .="$first_name $last_name <br> $street <br> $zip $city
	<br><br>";
	$faq_link = get_home_url().'/faq';
	$user_message .="Solltest du Unterstützung benötigen, findest du die meisten Antworten hier $faq_link.
	<br><br>";
	$user_message .="Liebe Grüsse <br>
	Neva von babytuch.ch
	<br>";
	  $subject = "Wir haben deine Babytuch-Bestellung erhalten.";
  
	  global $wpdb;
	  $table_name =  $wpdb->prefix . 'wcpdf_invoice_number';
	  $wcpdf_details = $wpdb->get_results( 
		  $wpdb->prepare( "
			  SELECT * FROM $table_name 
			  WHERE order_id  = %s", 
			  $order_id 
		  ) 
	  );
	  $wcpdf_details_json = json_decode(json_encode($wcpdf_details), true);
	  $wcpdf_id = $wcpdf_details_json[0]["id"]; // TODO: undefined offset zero here
	  $attach = array(WP_CONTENT_DIR . "/uploads/wpo_wcpdf/attachments/Rechnung-$wcpdf_id.pdf");
	  
	  $file_path = get_post_meta($order_id, 'pm_pdf_file', true);
	  $temp = substr($file_path, -30);
	  $file_path = ABSPATH.'wp-content/uploads/'.$temp;
	  array_push($attach, $file_path);
  
	ob_start();
	wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Wir haben deine Babytuch-Bestellung erhalten.' ) );
	echo str_replace( '{{name}}', $user_fname, $user_message );
	wc_get_template( 'emails/email-footer.php' );
	$message = ob_get_clean();
	// Debug wp_die($user_email);
	$mailer->send( $user_email, $subject, $message, '', $attach);
}

//add_action( 'woocommerce_order_status_processing', 'send_processing' );
function send_processing($order_id){
	global $woocommerce, $wpdb;
	$details = $wpdb->get_results( 
		$wpdb->prepare( "
			SELECT * FROM babytuch_order_process
			WHERE order_id  = %s", 
			$order_id 
		) 
	);
	$details_json = json_decode(json_encode($details), true);
	$is_replacement_order = $details_json[0]["is_replacement_order"];
	if($is_replacement_order == 0){
		$mailer = $woocommerce->mailer();
	
		$order = wc_get_order($order_id);
		$user_fname = $order->get_billing_first_name('view');
		$first_name = $order->get_shipping_first_name('view');
		if($first_name==''){
			$first_name = $order->get_billing_first_name('view');
		}
		$last_name = $order->get_shipping_last_name('view');
		if($last_name==''){
			$last_name = $order->get_billing_last_name('view');
		}
		$street = $order->get_shipping_address_1('view');
		if($street==''){
			$street = $order->get_billing_address_1('view');
		}
		$city = $order->get_shipping_city('view');
		if($city==''){
			$city = $order->get_billing_city('view');
		}
		$zip = $order->get_shipping_postcode('view');
		if($zip==''){
			$zip = $order->get_billing_postcode('view');
		}
		
	$order_date = $order->get_date_created();
	$order_date_form = $order_date->date('d.m.Y');
	$user_email = $order->get_billing_email();
	$items = $order->get_items();
	
	$shipping_method = $order->get_shipping_method();
		global $wpdb;
		$table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$res = $wpdb->get_results( 
		"SELECT * FROM $table_name2
			WHERE meta_key  = 'rate_percent'"
			
		);
		$res_json = json_decode(json_encode($res), true);
		$tax = (float)end($res_json)["meta_value"];
		$endcost = $order->get_total();
	
		
		$user_message = "Hallo $user_fname,<br><br> Danke für deine Bestellung #$order_id vom $order_date_form 
		auf babytuch. Wir haben heute deine Zahlung von CHF $endcost erhalten und werden nun 
		dein Paket per $shipping_method an diese Adresse versenden:
		<br><br>";
		$user_message .="$first_name $last_name <br> $street <br> $zip $city
		<br><br>";
		$user_message .="Im Paket warten folgende Produkte auf dein Auspacken:<br><br>";
		$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
		<thead>
			<tr>
				<th style='text-align:left;'>Produkte</th>
				<th style='text-align:right;'>Anzahl</th>
			</tr>
		</thead>
		<tbody>";
		$items = $order->get_items();
		$total_price=0;
		foreach( $items as $product ) {
		$amount = $product->get_quantity();
		
			$product_id = $product['product_id']; 
			$product_obj = wc_get_product($product_id);
			$product_id = $product_obj->get_id();
	
			$data = $product->get_data();
			$variation_id = $data["variation_id"];
			$variation_obj = wc_get_product($variation_id);
			$size = $variation_obj->get_attributes();
			$size2 = $size["groesse"];
			$name = $product['name']; 
		    $name2 = substr($name, -3,-2);
		    $name3 = substr($name, -4,-3);
		    if($name2=='-'){
		  	  	$real_name=substr($name, 0,-4);
		    }elseif($name3=='-'){
		  		$real_name=substr($name, 0,-5);
		    }else{
		  	  	$real_name=$name;
		    }
			$price = $variation_obj->get_price();
	
			$user_message .= "<tr>
			<td>$real_name, Grösse $size2</td>
			<td style='text-align:right;'>$amount</td>
			</tr>";
			$total_price= $total_price+(int)$price;
		}
		$user_message .= "<tr>
		<td>Pflegehinweis</td>
		<td style='text-align:right;'>1</td>
		</tr>";
		$user_message .= "<tr>
		<td>Informationen zum Vermittlungsprogramm</td>
		<td style='text-align:right;'>1</td>
		</tr>";
		$user_message .= "<tr>
		<td>Umtausch- und Rücksende-Etikette</td>
		<td style='text-align:right;'>1</td>
		</tr>";      
		$user_message .="</tbody>
		</table>
		<br><br>";
		$user_message .="Beachte, dass du bei unserem <b>Vermittlungsprogramm</b> mitmachen kannst. 
		Für jedes dank dir verkaufte Babytuch erstatten wir dir CHF 5 zurück.
		<br><br>";
		$user_message .="Wir freuen uns über deine Meinung, z.B. auf Google oder bei Facebook.
		<br><br>";
		$user_message .="Wenn ein Babytuch <b>umtauschen</b> möchtest, weil die die Farbe nicht gefällt oder 
		die Grösse nicht ganz passt, so kannst du dies innerhalb von 30 Tagen über dein Benutzerkonto 
		vollziehen. Nutze dazu die im Paket beigelegte Rücksendeetikette.
		<br><br>";
		$user_message .="Solltest du mit unseren Produkten <b>nicht zufrieden</b> sein, kannst du die Tücher
		innerhalb von 30 Tagen zurücksenden. Wir erstatten dir anschliessend den Warenwert inkl. MWST
		zurück. Nutze dazu die im Paket beigelegte Rücksendeetikette.
		<br><br>";
		$faq_link = get_home_url().'/faq';
		$user_message .="Solltest du weitere Unterstützung benötigen,
		findest du die meisten Antworten hier $faq_link
		<br><br>";
		$user_message .="Liebe Grüsse <br>
		Neva von babytuch.ch
		<br>";
		$subject = "Wir haben deine Zahlung erhalten.";
	
		
		ob_start();
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Wir haben deine Zahlung erhalten.' ) );
		echo str_replace( '{{name}}', $user_fname, $user_message );
		wc_get_template( 'emails/email-footer.php' );
		$message = ob_get_clean();
		// Debug wp_die($user_email);
		$mailer->send( $user_email, $subject, $message);
	}
}

//add_action( 'woocommerce_order_status_completed', 'send_completed' );
function send_completed($order_id){
	global $woocommerce, $wpdb;
	$mailer = $woocommerce->mailer();
  
	$order = wc_get_order($order_id);
	$user_fname = $order->get_billing_first_name('view');
	$first_name = $order->get_shipping_first_name('view');
	if($first_name==''){
	  $first_name = $order->get_billing_first_name('view');
	}
	$last_name = $order->get_shipping_last_name('view');
	if($last_name==''){
	  $last_name = $order->get_billing_last_name('view');
	}
	$street = $order->get_shipping_address_1('view');
	if($street==''){
	  $street = $order->get_billing_address_1('view');
	}
	$city = $order->get_shipping_city('view');
	if($city==''){
	  $city = $order->get_billing_city('view');
	}
	$zip = $order->get_shipping_postcode('view');
	if($zip==''){
	  $zip = $order->get_billing_postcode('view');
	}
	
  $order_date = $order->get_date_created();
  $order_date_form = $order_date->date('d.m.Y');
  $user_email = $order->get_billing_email();
  $items = $order->get_items();
  
  $shipping_method = $order->get_shipping_method();
	global $wpdb;
	$table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
	$res = $wpdb->get_results( 
	"SELECT * FROM $table_name2
		WHERE meta_key  = 'rate_percent'"
		
	);
	$res_json = json_decode(json_encode($res), true);
	$tax = (float)end($res_json)["meta_value"];
	$endcost = $order->get_total();
  
	
	$user_message = "Hallo $user_fname,<br><br> Danke für deine Bestellung #$order_id vom $order_date_form 
	auf babytuch. Wir haben heute 
	dein Paket per $shipping_method an diese Adresse versendet:
	<br><br>";
	$user_message .="$first_name $last_name <br> $street <br> $zip $city
	<br><br>";
	$user_message .="Im Paket warten folgende Produkte auf dein Auspacken:<br><br>";
	$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
	<thead>
		<tr>
			<th style='text-align:left;'>Produkte</th>
			<th style='text-align:right;'>Anzahl</th>
		</tr>
	</thead>
	<tbody>";
	$items = $order->get_items();
	$total_price=0;
	foreach( $items as $product ) {
	  $amount = $product->get_quantity();
	  
		  $product_id = $product['product_id']; 
		  $product_obj = wc_get_product($product_id);
		  $product_id = $product_obj->get_id();
  
		  $data = $product->get_data();
		  $variation_id = $data["variation_id"];
		  $variation_obj = wc_get_product($variation_id);
		  $size = $variation_obj->get_attributes();
		  $size2 = $size["groesse"];
		  $name = $product['name']; 
		  $name2 = substr($name, -3,-2);
		    $name3 = substr($name, -4,-3);
		    if($name2=='-'){
		  	  	$real_name=substr($name, 0,-4);
		    }elseif($name3=='-'){
		  		$real_name=substr($name, 0,-5);
		    }else{
		  	  	$real_name=$name;
		    }
		  $price = $variation_obj->get_price();
  
		  $user_message .= "<tr>
		  <td>$real_name, Grösse $size2</td>
		  <td style='text-align:right;'>$amount</td>
		  </tr>";
		  $total_price= $total_price+(int)$price;
	}
	$user_message .= "<tr>
	  <td>Pflegehinweis</td>
	  <td style='text-align:right;'>1</td>
	  </tr>";
	$user_message .= "<tr>
	  <td>Informationen zum Vermittlungsprogramm</td>
	  <td style='text-align:right;'>1</td>
	  </tr>";
	$user_message .= "<tr>
	  <td>Umtausch- und Rücksende-Etikette</td>
	  <td style='text-align:right;'>1</td>
	  </tr>";      
	$user_message .="</tbody>
  </table>
	<br><br>";
	$user_message .="Beachte, dass du bei unserem <b>Vermittlungsprogramm</b> mitmachen kannst. 
	Für jedes dank dir verkaufte Babytuch erstatten wir dir CHF 5 zurück.
	<br><br>";
	$user_message .="Wir freuen uns über deine Meinung, z.B. auf Google oder bei Facebook.
	<br><br>";
	$user_message .="Wenn ein Babytuch <b>umtauschen</b> möchtest, weil die die Farbe nicht gefällt oder 
	die Grösse nicht ganz passt, so kannst du dies innerhalb von 30 Tagen über dein Benutzerkonto 
	vollziehen. Nutze dazu die im Paket beigelegte Rücksendeetikette.
	<br><br>";
	$user_message .="Solltest du mit unseren Produkten <b>nicht zufrieden</b> sein, kannst du die Tücher
	 innerhalb von 30 Tagen zurücksenden. Wir erstatten dir anschliessend den Warenwert inkl. MWST
	  zurück. Nutze dazu die im Paket beigelegte Rücksendeetikette.
	<br><br>";
	$faq_link = get_home_url().'/faq';
	$user_message .="Solltest du weitere Unterstützung benötigen,
	 findest du die meisten Antworten hier $faq_link
   <br><br>";
   $user_message .="Wir wünschen dir viel Freude beim Tragen!
  <br><br>";
	$user_message .="Liebe Grüsse <br>
	Neva von babytuch.ch
	<br>";
	  $subject = "Deine Babytücher sind nun unterwegs zu dir.";
  
	  
	ob_start();
	wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Deine Babytücher sind nun unterwegs zu dir.' ) );
	echo str_replace( '{{name}}', $user_fname, $user_message );
	wc_get_template( 'emails/email-footer.php' );
	$message = ob_get_clean();
	// Debug wp_die($user_email);
	$mailer->send( $user_email, $subject, $message);
}


//RETURNING MAILS
add_action( 'woocommerce_order_status_returning', 'send_test' );
function send_test($order_id){
    global $woocommerce, $wpdb;
    $mailer = $woocommerce->mailer();

    $order = wc_get_order($order_id);
    $user_name = $order->get_billing_last_name();
    $user_fname = $order->get_billing_first_name();
    $user_email = $order->get_billing_email();
    $full_name = $user_fname.' '.$user_name;

    $order_details = $wpdb->get_results( 
        $wpdb->prepare( "
            SELECT * FROM babytuch_order_process
            WHERE order_id  = %s", 
            $order_id 
        ) 
    );
    $order_details_json = json_decode(json_encode($order_details), true);
    $check = $order_details_json[0]["replace_activated"];
    if((int)$check == 0){
		global $woocommerce, $wpdb;
		$mailer = $woocommerce->mailer();
	  
		$order = wc_get_order($order_id);
		$user_fname = $order->get_billing_first_name('view');
	   
	  
	  $order_date_form = date('d.m.Y', time());
	  $user_email = $order->get_billing_email();
	  $items = $order->get_items();
	  
	  $shipping_method = $order->get_shipping_method();
		global $wpdb;
		$table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$res = $wpdb->get_results( 
		"SELECT * FROM $table_name2
			WHERE meta_key  = 'rate_percent'"
			
		);
		$res_json = json_decode(json_encode($res), true);
		$tax = (float)end($res_json)["meta_value"];
		$endcost = $order->get_total();
	  
		$name_logistics = get_option('name_logistics');
		$name2_logistics = get_option('name2_logistics');
		$adress_logistics = get_option('adress_logistics');
		$plz_logistics = get_option('plz_logistics');
		$city_logistics = get_option('city_logistics');
	  
		$table_name = $wpdb->prefix . 'wc_order_product_lookup';
		$user_details = $wpdb->get_results( 
			$wpdb->prepare( "
				SELECT customer_id FROM $table_name
				WHERE order_id  = %s", 
				$order_id 
			) 
		);
		$user_details_json = json_decode(json_encode($user_details), true);
		$customer_id = $user_details_json[0]["customer_id"];
		$table_name2 = $wpdb->prefix . 'wc_customer_lookup';
		$user_details2 = $wpdb->get_results( 
			$wpdb->prepare( "
				SELECT user_id FROM $table_name2
				WHERE customer_id  = %s", 
				$customer_id 
			) 
		);
		$user_details2_json = json_decode(json_encode($user_details2), true);
		$client_id = $user_details2_json[0]["user_id"];
		if(empty($client_id)){
			$order = wc_get_order($order_id);
			$email = $order->get_billing_email();
			$user_details2 = $wpdb->get_results( 
			$wpdb->prepare( "
				SELECT user_id FROM $table_name2
				WHERE email  = %s", 
				$email 
			) 
			);
			$user_details2_json = json_decode(json_encode($user_details2), true);
			$client_id = $user_details2_json[0]["user_id"];
			if(empty($client_id) and count($user_details2_json)>1){
				$client_id = $user_details2_json[1]["user_id"];
			}
		}
	  $table_name2 = $wpdb->prefix . 'usermeta';
	  $ref_id = $wpdb->get_results( 
		 $wpdb->prepare( "
			 SELECT meta_value FROM $table_name2
			 WHERE user_id = %s AND meta_key='iban_num'", 
			 $client_id
		 ) 
	  );
	  $ref_id_json = json_decode(json_encode($ref_id), true);
	  if($ref_id_json){
		$iban = $ref_id_json[0]["meta_value"]; 
	  } else{
		 $iban = 'n.a.';
	  }
	  
	  
		$user_message = "Hallo $user_fname,<br><br> Wir haben am $order_date_form von 
		deiner Rücksendung Kenntnis genommen. Es tut uns leid, wenn dir die Produkte aus der
		 Bestellung #$order_id nicht gefallen.
		<br><br>";
		$user_message .="Du hast uns angekündigt, folgende <b>Produkte</b> zurück zu senden:
		<br><br>";
		$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
		<thead>
			<tr>
				<th>Produkte</th>
				<th>Anzahl</th>
				<th>Preis (CHF)</th>
			</tr>
		</thead>
		<tbody>";
		global $wpdb;
		$order_details = $wpdb->get_results( 
			$wpdb->prepare( "
				SELECT * FROM babytuch_order_process
				WHERE order_id = %s", 
				$order_id 
			) 
		);
		$order_details_json = json_decode(json_encode($order_details), true);
		
		$return_products = $order_details_json[0]["return_products"];
		$split_arr = explode(",", $return_products);
	   $total=0;
		foreach($split_arr as $id){
			if(!empty($id)){
				$product_to_refill = wc_get_product((int)$id);
				$price = $product_to_refill->get_price();
				$name = $product_to_refill->get_name();
				$size = $product_to_refill->get_attributes();
				$size2 = $size["groesse"];
				$user_message .= "<tr>
				<td>$name, Grösse $size2</td>
				<td>1</td>
				<td>$price</td>
				</tr>";
				$total = $total+(int)$price;
			}
		}
		$user_message .="</tbody>
	  </table>
		<br><br>";
		$return_reason = $order_details_json[0]["return_reason"];
		$user_message .="Als Grund hast du uns genannt: <b>$return_reason</b>.
		Danke für dieses Feedback. Dank solcher Rückmeldungen können wir unser Angebot verbessern.
		<br><br>";
		$user_message .="<b>Rücksendung</b><br>Die Rücksendeetikette lag bereits bei der Zustellung im Paket.
		 Sie ermöglicht dir die portofreie Rücksendung. Falls du die Etikette verloren hast, 
		 geht das Porto leider zu deinen Lasten – sorry.
		<br><br>";
		$user_message .="Adresse für die Rücksendung:<br> $name_logistics<br>$name2_logistics <br>
		$adress_logistics <br>$plz_logistics $city_logistics
		<br><br>";
		$user_message .="Nach Eingang des Pakets werden wir dir den Warenwert inkl. MWST auf dein
		 bei uns hinterlegtes Konto mit der IBAN $iban zurückerstatten.
		<br><br>";
		$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
		<thead>
			<tr>
				<th></th>
				<th></th>
			</tr>
		</thead>
		<tbody>";
		$mwst = get_option('company_mwst_number');
		$mwst_cost = round($total*(0.01*$tax),2);
		$end_refund = $total;
		$user_message .= "<tr>
				<td>Warenwert</td>
				<td>$total</td>
				</tr>";
		$user_message .= "<tr>
				<td>Abzüge</td>
				<td>0.00</td>
				</tr>";
		$user_message .= "<tr>
				<td>Zwischensumme</td>
				<td>$total</td>
				</tr>";
		$is_replacement_order = $order_details_json[0]["is_replacement_order"];
		if($is_replacement_order==1){
			$cost_of_sending = $order_details_json[0]["cost_of_sending"];
			$user_message .= "<tr>
				<td>Versandkosten (inkl. MwST)</td>
				<td>-$cost_of_sending</td>
				</tr>";
			$end_refund = $end_refund-(float)$cost_of_sending;
		}
		$user_message .= "<tr>
				<td><b>Unsere Rückerstattung an dich</b></td>
				<td>$end_refund</td>
				</tr>";
	  
		$user_message .="</tbody>
	  </table>
		<br><br>";
		$user_message .="Stimmt alles? Super.
		<br><br>";
		$faq_link = get_home_url().'/faq';
		$user_message .="Solltest du weitere Unterstützung benötigen,
		 findest du die meisten Antworten hier $faq_link
	   <br><br>";
		$user_message .="Liebe Grüsse <br>
		Neva von babytuch.ch
		<br>";
		  $subject = "Du hast uns eine Rücksendung angekündigt.";
	  
		  
		ob_start();
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Du hast uns eine Rücksendung angekündigt.' ) );
		echo str_replace( '{{name}}', $user_fname, $user_message );
		wc_get_template( 'emails/email-footer.php' );
		$message = ob_get_clean();
		// Debug wp_die($user_email);
		$mailer->send( $user_email, $subject, $message);
    }else{
		global $woocommerce, $wpdb;
		$mailer = $woocommerce->mailer();
	  
		$order = wc_get_order($order_id);
		$user_fname = $order->get_billing_first_name('view');
	   
	  
	  $order_date_form = date('d.m.Y', time());
	  $user_email = $order->get_billing_email();
	  $items = $order->get_items();
	  
	  $shipping_method = $order->get_shipping_method();
		global $wpdb;
		$table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$res = $wpdb->get_results( 
		"SELECT * FROM $table_name2
			WHERE meta_key  = 'rate_percent'"
			
		);
		$res_json = json_decode(json_encode($res), true);
		$tax = (float)end($res_json)["meta_value"];
		$endcost = $order->get_total();
	  
		$name_logistics = get_option('name_logistics');
		$name2_logistics = get_option('name2_logistics');
		$adress_logistics = get_option('adress_logistics');
		$plz_logistics = get_option('plz_logistics');
		$city_logistics = get_option('city_logistics');
	  
	  
		$user_message = "Hallo $user_fname,<br><br> Wir haben am $order_date_form von 
		deinem Umtausch Kenntnis genommen. Es tut uns leid, wenn die Produkte aus der
		 Bestellung #$order_id noch nicht ganz deinem Wunsch entsprechen. Gerne lösen wir das zusammen. 
		<br><br>";
		$user_message .="Du hast uns angekündigt, folgende <b>Produkte</b> umtauschen zu wollen:
		<br><br>";
		$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
		<thead>
			<tr>
				<th>Produkte</th>
				<th>wird ersetzt durch</th>
				<th>Anzahl</th>
			</tr>
		</thead>
		<tbody>";
		global $wpdb;
		$order_details = $wpdb->get_results( 
			$wpdb->prepare( "
				SELECT * FROM babytuch_order_process
				WHERE order_id = %s", 
				$order_id 
			) 
		);
		$order_details_json = json_decode(json_encode($order_details), true);
		$replacement_order_id = (int)$order_details_json[0]["replacement_order"];
		$replacement_order = wc_get_order($replacement_order_id);
		$items = $replacement_order->get_items();
	  
		$return_products = $order_details_json[0]["return_products"];
		$split_arr = explode(",", $return_products);
	  
		$i=0;
		foreach($items as $item){
		  $product_id = $item['product_id']; 
		  $product_obj = wc_get_product($product_id);
		  $product_id = $product_obj->get_id();
	  
		  $data = $item->get_data();
		  $variation_id = $data["variation_id"];
		  $variation_obj = wc_get_product($variation_id);
		  $size = $variation_obj->get_attributes();
		  $size2 = $size["groesse"];
		  $name = $item['name']; 
		  $replacement_prod_id = $split_arr[$i];
		  if(!empty($replacement_prod_id)){
			$product_to_refill = wc_get_product((int)$replacement_prod_id);
			$name2 = $product_to_refill->get_name();
			$size3 = $product_to_refill->get_attributes();
			$size4 = $size3["groesse"];
		  }
		  $user_message .= "<tr>
				<td>$name2, Grösse $size4</td>
				<td>$name, Grösse $size2</td>
				<td>1</td>
				</tr>";
		  $i++;
		}
		$user_message .="</tbody>
	  </table>
		<br><br>";
		$user_message .="<b>Rücksendung</b><br>Die Rücksendeetikette lag bereits bei der Zustellung im Paket.
		 Sie ermöglicht dir die portofreie Rücksendung. Falls du die Etikette verloren hast, 
		 geht das Porto leider zu deinen Lasten – sorry.
		<br><br>";
		$user_message .="Adresse für die Rücksendung:<br> $name_logistics<br>$name2_logistics <br>
		$adress_logistics <br>$plz_logistics $city_logistics
		<br><br>";
		$user_message .="Nach Eingang des Pakets werden wir dir das Ersatzprodukt umgehend zustellen.
		<br><br>";
		$faq_link = get_home_url().'/faq';
		$user_message .="Solltest du weitere Unterstützung benötigen,
		 findest du die meisten Antworten hier $faq_link
	   <br><br>";
		$user_message .="Liebe Grüsse <br>
		Neva von babytuch.ch
		<br>";
		  $subject = "Du hast uns einen Umtausch angekündigt.";
	  
		  
		ob_start();
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Du hast uns einen Umtausch angekündigt.' ) );
		echo str_replace( '{{name}}', $user_fname, $user_message );
		wc_get_template( 'emails/email-footer.php' );
		$message = ob_get_clean();
		// Debug wp_die($user_email);
		$mailer->send( $user_email, $subject, $message);
    }
	
    /*ob_start();
    wc_get_template( 'emails/email-header.php', array( 'email_heading' => $subject ) );
    echo str_replace( '{{name}}', $full_name, $user_message );
    wc_get_template( 'emails/email-footer.php' );
    $message = ob_get_clean();
    // Debug wp_die($user_email);
    $mailer->send( $user_email, $subject, $message);*/
}




/*
* MAXMIMAL 4 PRODUKTE PRO BESTELLUNG
* Validating the quantity on add to cart action with the quantity of the same product available in the cart. 
*/
function wc_qty_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) {


	$already_in_cart 	= wc_qty_get_cart_qty( $product_id );
	$product 			= wc_get_product( $product_id );
	$product_title 		= $product->get_title();
	$max = 4;
	
	if ( !is_null( $max ) ) {
		
		if ( ( $already_in_cart + $quantity ) > $max ) {
			// oops. too much.
			$passed = false;			

			wc_add_notice( apply_filters( 'isa_wc_max_qty_error_message_already_had', sprintf( __( '
			Sie können maximal %1$s Produkte bestellen. Sie haben im Moment %3$s Produkte
			in Ihrem Warenkorb', 'woocommerce-max-quantity' ), 
						$max,
						$product_title,
						$already_in_cart ),
					$max,
					$already_in_cart ),
			'error' );

		}
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wc_qty_add_to_cart_validation', 1, 5 );

/*
* Get the total quantity of the product available in the cart.
*/ 
function wc_qty_get_cart_qty( $product_id ) {
	global $woocommerce;
	$running_qty = 0; // iniializing quantity to 0

	// search the cart for the product in and calculate quantity.
	foreach($woocommerce->cart->get_cart() as $other_cart_item_keys => $values ) {
        // TODO: maybe check for category instead of product?
		if ( $product_id == $values['product_id'] ) {				
			$running_qty += (int) $values['quantity'];
		}
	}

	return $running_qty;
}


/**
 * LIMITER FÜR CHECKOUT FELDER
 * 
 * 
*/
function babytuch_checkout_fields ( $fields ) {
	$fields['billing']['billing_postcode']['maxlength'] = 4;
	$fields['billing']['billing_first_name']['maxlength'] = 18;
	$fields['billing']['billing_last_name']['maxlength'] = 18;
	$fields['billing']['billing_address_1']['maxlength'] = 36;
	$fields['billing']['billing_address_2']['maxlength'] = 6;
	$fields['billing']['billing_city']['maxlength'] = 36;
	$fields['billing']['billing_phone']['required'] = false;
	$fields['shipping']['shipping_last_name']['maxlength'] = 18;
	$fields['shipping']['shipping_first_name']['maxlength'] = 18;
	$fields['shipping']['shipping_postcode']['maxlength'] = 4;
	$fields['shipping']['shipping_address_1']['maxlength'] = 36;
	$fields['shipping']['shipping_city']['maxlength'] = 36;
	unset($fields['billing']['billing_address_2']);
	unset($fields['billing']['billing_phone']);
	unset($fields['billing']['billing_state']);
	unset($fields['shipping']['shipping_address_2']);
	unset($fields['billing']['billing_company']);
	unset($fields['shipping']['shipping_company']);
	unset($fields['shipping']['shipping_state']);
	$fields['billing']['billing_country']['priority'] = 80;
	$fields['shipping']['shipping_country']['priority'] = 80;
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'babytuch_checkout_fields');


function babytuch_address_fields( $address_fields) {
	$address_fields['address_1']['label'] = 'Strasse und Hausnummer';
	$address_fields['address_1']['placeholder'] = 'Musterstrasse 123';
	$address_fields['address_1']['required'] = true;
	$address_fields['address_1']['clear'] = false;

	return $address_fields;
}

add_filter( 'woocommerce_default_address_fields' , 'babytuch_address_fields', 9999 );

add_filter( 'wc_order_statuses', 'wc_renaming_order_status' );
function wc_renaming_order_status( $order_statuses ) {
    foreach ( $order_statuses as $key => $status ) {
        if ( 'wc-on-hold' === $key ) 
            $order_statuses['wc-on-hold'] = _x( 'auf Zahlung wartend', 'Order status', 'woocommerce' );
    }
    return $order_statuses;
}


add_filter( 'woocommerce_my_account_my_orders_columns', 'additional_my_account_orders_column', 10, 1 );
function additional_my_account_orders_column( $columns ) {
    $new_columns = [];

    foreach ( $columns as $key => $name ) {
        $new_columns[ $key ] = $name;

        if ( 'order-status' === $key ) {
            $new_columns['order-items'] = __( 'Rücksenden/Umtausch', 'woocommerce' );
        }
    }
    return $new_columns;
}

add_action( 'woocommerce_my_account_my_orders_column_order-items', 'additional_my_account_orders_column_content', 10, 1 );
function additional_my_account_orders_column_content( $order ) {
	$details = array();
	
	$order_id=$order->get_id();
	$order_status = $order->get_status();
	global $wpdb;
	$order_details = $wpdb->get_results( 
		$wpdb->prepare( "
			SELECT * FROM babytuch_order_process
			WHERE order_id  = %s", 
			$order_id 
		) 
	);
	$order_details_json = json_decode(json_encode($order_details), true);
	if($order_details_json and $order_status=='completed'){
		$code = $order_details_json[0]["return_code"];
		$url = get_home_url()."/ruecksenden/?code=$code";
		$details[] = "<a href=$url>Zurücksenden</a>";
		$url = get_home_url()."/umtausch/?code=$code";
		$details[] = "<a href=$url>Umtauschen</a>";
	}

    echo count( $details ) > 0 ? implode( '<br>', $details ) : '&ndash;';
}

//ZUSÄTZLICHE INFO STREICHEN
add_filter( 'woocommerce_product_tabs', 'bbloomer_remove_product_tabs', 9999 );
  
function bbloomer_remove_product_tabs( $tabs ) {
    unset( $tabs['additional_information'] ); 
    return $tabs;
}

//ARTIKELNUMMER VERSTECKEN
add_filter( 'wc_product_sku_enabled', '__return_false' );

/**
 * IBAN BEIM BESTELLEN
 * 
 * 
 * 
 */

/*add_action( 'woocommerce_after_order_notes', 'babytuch_add_custom_checkout_field' );
  
function babytuch_add_custom_checkout_field( $checkout ) { 

	
   
   woocommerce_form_field( 'iban_num', array(        
      'type' => 'text',        
      'class' => array( 'form-row-wide' ),        
      'label' => 'IBAN Nummer',        
      'placeholder' => 'CH12345679',        
      'required' => false,        
      'default' => '',        
   ), $checkout->get_value( 'iban_num' ) ); 
}


add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');

function my_custom_checkout_field_process() {
    // Check if set, if its not set add an error.
    if ( ! $_POST['iban_num'] )
        wc_add_notice( __( 'IBAN-Nr. ist ein Pflichtfeld.' ), 'error' );
}

add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );

function my_custom_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['iban_num'] ) ) {
        update_post_meta( $order_id, 'IBAN', sanitize_text_field( $_POST['iban_num'] ) );
    }
}
*/



/**
 * CRON EVENT FOR REORDERS AND BILLINGS
 * => needs a cron event to be called
 * 
 * 
 */
add_action( 'babytuch_regular_checks', 'babytuch_regular_checks' );
function babytuch_regular_checks(){
		global $wpdb;
		//REGULAR CHECK
		$products_last_check = get_option('products_last_check');
		$interval = get_option('products_interval');
		$next_check = strtotime($products_last_check. " + $interval days");
		$products_next_check = date('Y-m-d',$next_check);
		if($products_next_check<date('Y-m-d',time())){
			$res = $wpdb->get_results("
				SELECT * FROM babytuch_inventory 
				WHERE item_name != 'packagings' AND item_name != 'labels' 
				AND item_name != 'supplements' AND item_name != 'packagings_large'"
			);
			$res_json = json_decode(json_encode($res), true);
			for($i=0; $i<count($res_json); $i++){
				$name = $res_json[$i]["item_name"];
				for($j=52; $j>1; $j--){
					$k=$j-1;
					$stock_new = $res_json[$i]["stock_$k"];
					$test = $wpdb->get_results( 
						$wpdb->prepare("
							UPDATE babytuch_inventory SET stock_$j = %s
							WHERE item_name = %s", 
							$stock_new, $name
						));
					if($j==2){
						$current_stock = $res_json[$i]["amount"];
						$test = $wpdb->get_results( 
							$wpdb->prepare("
								UPDATE babytuch_inventory SET stock_1 = %s
								WHERE item_name = %s", 
								$current_stock, $name
							));
					}
				}
			};
			$supplements = $wpdb->get_results("
					SELECT * FROM babytuch_inventory 
					WHERE item_name = 'packagings' OR item_name = 'labels' 
					OR item_name = 'supplements' OR item_name = 'packagings_large'"
				);
			$supplements_json = json_decode(json_encode($supplements), true);
			foreach($supplements_json as $supplement){
				$name = $supplement["item_name"];
				for($j=52; $j>1; $j--){
					$k=$j-1;
					$stock_new = $supplement["stock_$k"];
					$test = $wpdb->get_results( 
						$wpdb->prepare("
							UPDATE babytuch_inventory SET stock_$j = %s
							WHERE item_name = %s", 
							$stock_new, $name
						));
					if($j==2){
						$current_stock = $supplement["amount"];
						$test = $wpdb->get_results( 
							$wpdb->prepare("
								UPDATE babytuch_inventory SET stock_1 = %s
								WHERE item_name = %s", 
								$current_stock, $name
							));
					}
				}
            }	
            update_option('products_last_check', date('Y-m-d',time()));
		}
}
