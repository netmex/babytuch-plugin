<?php
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Base;

use WC_Order;

class Payments{
    public function register(){
	    add_action( 'wpo_wcpdf_after_order_details', [$this,'pdf_invoice_after_order_details'], 10, 2 );
	    add_filter('woocommerce_email_attachments', [$this, 'add_pdf_to_order_processing_mail'], 99, 3);
	    add_action('wpo_wcpdf_email_attachment', [$this, 'save_invoice_in_order_meta'], 10, 3);
    }

    public function save_invoice_in_order_meta( $pdf_path, $document_type, $document) {
    	$order_id = $document->order_id;
    	update_post_meta($order_id, '_babytuch_invoice_pdf_path', $pdf_path);
	}

    public function add_pdf_to_order_processing_mail($attachments, $email_id, $order) {
	    if (empty($order) || ! isset( $email_id ) || !method_exists($order,'get_payment_method') || $email_id !== 'customer_processing_order') {
		    return $attachments;
	    }

	    $payment_method = $order->get_payment_method();
	    if ($payment_method !== 'sqrip') {
		    $order_id = $order->get_id();
		    $invoice_pdf_path = get_post_meta($order_id,'_babytuch_invoice_pdf_path', true );
		    if($invoice_pdf_path) {
		    	$attachments[] = $invoice_pdf_path;
		    }
	    }
	    return $attachments;
    }

    public function pdf_invoice_after_order_details($template_type, WC_Order $order){
	    $account_details = get_option('woocommerce_bacs_accounts');
	    $account_name = $account_details[0]["account_name"];
	    $account_number = $account_details[0]["account_number"];
	    $bank_name = $account_details[0]["bank_name"];
	    $sort_code = $account_details[0]["sort_code"];
	    $iban = $account_details[0]["iban"];
	    $bic = $account_details[0]["bic"];
	    $mwst = get_option('company_mwst_number');
	    $address = get_option('woocommerce_store_address_2');
	    $plz = get_option('woocommerce_store_postcode');
	    $city = get_option('woocommerce_store_city');
	    $bank_address = get_option('babytuch_bank_address');
	    $bank_city = get_option('babytuch_bank_city');

		$paid_date = $order->get_date_paid() ? $order->get_date_paid()->date("d.m.y - H:i") : null;

	    echo '<div style="line-height:100%">';
	    echo "Diese Rechnung wird inklusive 7.7% Mehrwertsteuer ausgewiesen ($mwst MWST).<br><br>";


	    if($paid_date) { // order has not been paid yet
		    echo "Ihre Bestellung wurde bereits bezahlt am $paid_date.<br><br>";
	    } else {
		    echo "Ihre Bestellung wird erst mit Überweisung des ausstehenden Betrags gültig.<br><br>";
	    }

		echo "Freundliche Grüsse <br>Neva von babytuch.ch <br><br><br>";

	    // hide payment information if invoice was already paid
	    if(!$paid_date) {
		    echo'<h3>Zahlungsdetails</h3>';

		    echo"Bankverbindung:<br>";
		    echo"$bank_name<br>";
		    echo"$bank_address<br>";
		    echo"$bank_city<br><br>";
		    //echo"BLZ: $sort_code<br><br>";

		    echo"Kontodetails:<br>";
		    echo"IBAN: $iban<br><br>";
		    //echo"BIC: $bic<br><br>";

		    echo"Kontoinhaber:<br>";
		    echo"$account_name <br>";
		    echo"$address<br>";
		    echo"$plz $city<br>";
		    echo'</div>';
		    echo'<h2></h2>';
	    }
    }

 
            
}