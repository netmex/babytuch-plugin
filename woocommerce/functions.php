<?php

// If this file is called directly, abort!!!
defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );


/**
 * Register new endpoint to use inside WooCommerce's My Account page.
 */
function babytuch_account_endpoints() {
	add_rewrite_endpoint( 'deletion', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'contact_form', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'referrals', EP_ROOT | EP_PAGES );
}

add_action( 'init', 'babytuch_account_endpoints' );

/**
 * Add new query var for the custom account pages
 *
 * @param array $vars
 * @return array
 */
function my_custom_query_vars( $vars ) {
	$vars[] = 'contact_form';
	$vars[] = 'deletion';
	$vars[] = 'referrals';

	return $vars;
}

add_filter( 'query_vars', 'my_custom_query_vars', 0 );


/**
 * Flush rewrite rules on plugin activation.
 */
function babytuch_flush_rewrite_rules() {
	add_rewrite_endpoint( 'deletion', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'contact_form', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'referrals', EP_ROOT | EP_PAGES );
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'babytuch_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'babytuch_flush_rewrite_rules' );



/**
 * Insert and modify items into the WooCommerce My Account menu.
 *
 * @param array $items
 * @return array
 */
function babytuch_account_menu_items( $items ) {
	
	$menu = array(
		
		'edit-account' => __( 'Konto', 'woocommerce' ),
		'edit-address' => __( 'Adresse', 'woocommerce' ),
		'orders'       => __( 'Bestellung', 'woocommerce' ),
		'referrals'       => __( 'Vermittlungen', 'woocommerce' ),
		'customer-logout' => __( 'Abmelden', 'woocommerce' ),
	);
	
	return $menu;
}

add_filter( 'woocommerce_account_menu_items', 'babytuch_account_menu_items' );

/**
 * Endpoint HTML content.
 */
function my_custom_deletion_content() {
	$client = wp_get_current_user();
	if ( $client->exists() ) {
		$client_id = $client->ID;
		$first_name = $client->first_name;
		$last_name = $client->last_name;
		$client_email = $client->user_email;
	 }    
	
	
	echo ''?>
	<br><br>
	<h4>Willst du dein Konto löschen?</h4>
	<p>Dann sende uns einen Antrag zur Löschung deines Kontos:</p>
	<form method="post" action="">
	<br><input type="submit" value="Konto löschen" name="delete">
	</form> 

	<?php
	if(isset($_POST['delete'])){
		$url = get_home_url().'/wp-admin/users.php';
		$msg = "Kunde #$client_id ($first_name $last_name) mit der E-Mail $client_email hat eine Löschanfrage gesendet. Bitte lösche sein Konto hier: $url.";
		$babytuch_admin_email = get_option('babytuch_admin_email');
		wp_mail($babytuch_admin_email, 'Löschanfrage', $msg);
		echo 'Löschantrag erfolgreich gesendet.';
	}
}

add_action( 'woocommerce_account_edit-account_endpoint', 'my_custom_deletion_content', 20 );


add_action( 'woocommerce_after_checkout_validation', 'babytuch_validate_address', 10, 2);

function babytuch_validate_address( $fields, $errors ){

    // ensure that city field fits into post API restrictions
    if(preg_match('/[\*\|\\\{\}\[\]=<>]/', $fields['billing_city'])) {
	   $errors->add( 'validation', 'Stadt darf keines der folgenden ungültigen Zeichen enthalten: *|\\{}[]<>=' );
    }

    // ensure length of address fits into post API restrictions
	if ( strlen($fields['billing_address_1']) > 25 || strlen($fields['shipping_address_1']) > 25  ){
		$errors->add( 'validation', 'Strasse und Hausnummer dürfen nicht länger als 25 Zeichen sein.' );
	}
}
