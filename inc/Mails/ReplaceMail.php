<?php

namespace Inc\Mails;

use Inc\Api\Helpers;
use WC_Order;

class ReplaceMail {

	private static string $subject = "Deine Rücksendung ist angekommen. Der Ersatz ist unterwegs zu dir.";

	private WC_Order $order;
	private array $return_products;
	private WC_Order $replacement_order;

	/**
	 * ReplaceMail constructor.
	 *
	 * @param WC_Order $order
	 * @param array $return_products
	 * @param WC_Order $replacement_order
	 */
	public function __construct( WC_Order $order, array $return_products, WC_Order $replacement_order ) {
		$this->order             = $order;
		$this->return_products   = $return_products;
		$this->replacement_order = $replacement_order;
	}


	public function send() {
		global $woocommerce;
		$mailer = $woocommerce->mailer();
		$message = $this->get_email_body();
		$mailer->send($this->order->get_billing_email(), self::$subject, $message);
	}

	private function get_email_body() {

		$address = Helpers::getShippingAddressFromOrder($this->order);
		$user_fname = $address->getFirstname();
		$shipping_method = $this->order->get_shipping_method();

		$replace_sent = date('d.m.Y', time());

		$user_message = "Hallo $user_fname,<br><br> Wir haben am $replace_sent folgende umzutauschenden Produkte in Empfang genommen:
                        <br><br>";
		$user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
                        <thead>
                            <tr>
                                <th>Produkte</th>
                                <th>Anzahl</th>
                            </tr>
                        </thead>
                        <tbody>";

		foreach($this->return_products as $return_product) {
			$name = $return_product->get_name();
			$attributes = $return_product->get_attributes();
			$size = $attributes["groesse"];
			$user_message .= "<tr><td>$name, Grösse $size</td><td>1</td></tr>";
		}

		$user_message .= "</tbody></table>";
		$user_message .= "<br><br>";

		$user_message .= "Wir werden nun das Paket mit dem Ersatz per $shipping_method wieder an diese Adresse versenden: <br><br>";
		$user_message .= $address->getFullName()."<br>".$address->getStreet()."<br>".$address->getZipAndCity()."<br><br>";
		$user_message .= "Im Paket warten folgende Produkte auf dein Auspacken:<br><br>";
		$user_message .= "<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
                        <thead>
                            <tr>
                                <th>Produkte</th>
                                <th>Anzahl</th>
                            </tr>
                        </thead>
                        <tbody>";

		$replacement_items = $this->replacement_order->get_items();

		foreach($replacement_items as $replacement_item){
			$data = $replacement_item->get_data();
			$variation_id = $data["variation_id"];
			$variation_obj = wc_get_product($variation_id);
			$attributes = $variation_obj->get_attributes();
			$size = $attributes["groesse"];
			$name = $replacement_item['name'];
			$user_message .= "<tr><td>$name, Grösse $size</td><td>1</td></tr>";
		}

		$user_message .= "</tbody></table>";
		$user_message .= "<br><br>";
		$user_message .= "Wir freuen uns über deine Meinung, z.B. auf Google oder bei Facebook.<br><br>";
		$faq_link = get_home_url().'/faq';
		$user_message .= "Solltest du weitere Unterstützung benötigen, findest du die meisten Antworten hier $faq_link <br><br>";
		$user_message .= "Liebe Grüsse <br> Neva von babytuch.ch<br>";

		ob_start();
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => self::$subject ) );
		echo str_replace( '{{name}}', $user_fname, $user_message );
		wc_get_template( 'emails/email-footer.php' );
		return ob_get_clean();

	}

}