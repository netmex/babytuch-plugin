<?php


namespace Inc\Base;


use Inc\Api\Address;
use Inc\Api\BT_PDF;
use Inc\Api\Helpers;
use Inc\Models\BT_OrderProcess;
use WC_Order;

class Returns extends BaseController {

	public static string $return_order_url_key = '_babytuch_return_order_url';
	public static string $return_order_path_key = '_babytuch_return_order_path';

	public function register() {
		add_action('babytuch_return_start', [$this, 'create_return_information'], 10, 2);

		//add_action( 'woocommerce_order_status_refunded', [$this, 'initiate_refund'], 10, 1);
		add_action( 'woocommerce_order_partially_refunded', [$this, 'initiate_partial_refund'], 10, 1);
		add_action( 'woocommerce_order_fully_refunded', [$this, 'initiate_full_refund'], 10, 1);

		add_filter('woocommerce_can_restock_refunded_items', [$this, 'can_restock_refunded_items'], 10, 3);

		// Adding Meta container admin shop_order pages
		add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );

	}

	public function add_meta_boxes() {
		add_meta_box( 'babytuch_return', __('Rückversand','babytuch'), [$this, 'add_return_meta_box_fields'], 'shop_order', 'side', 'core' );
	}

	public function add_return_meta_box_fields($post) {
		$order = wc_get_order($post->ID);

		$user = $order->get_user();
		$iban = $user ? get_user_meta($user->ID, 'iban_num') : false;

		$order_process = BT_OrderProcess::load_by_order_id($order->get_id());

        if(!$order_process) {
            echo "<div></div>";
            return;
        }

        $status = $order->get_status();

		$return_trackingnumber = get_post_meta( $post->ID, Shipping::$return_trackingnumber_key, true ) ?: '';
		$return_tracking_url = get_post_meta( $post->ID, Shipping::$return_trackingurl_key, true ) ?: '';
		$return_label_url = get_post_meta( $post->ID, Shipping::$return_label_url_key, true ) ?: '';

		$return_order_url = get_post_meta($post->ID, Returns::$return_order_url_key, true) ?: '';

		$return_information_sent = get_post_meta( $post->ID, '_babytuch_return_information_sent', true ) ?: false;


        if($order_process->isReplacementOrder()) {
            echo '<div>Bei dieser Bestellung handelt es sich um eine Ersatzbestellung.</div>';
            echo '<div><strong>Ersatzbestellung von:</strong> <a href="'.get_edit_post_link($order_process->getReplacedOrderId()).'" target="_blank">'.($order_process->getReplacedOrderId()).'</a></div>';
            echo '<hr>';
        }

        if($order_process->isReturnActivated()) {
            echo "<div>";
            echo    "<strong>Retournierte Produkte:</strong>";
            echo    "<ul>";

            $products = $order_process->getReturnProducts();
            foreach($products as $product) {
                $name = $product->get_name();
                $size = $product->get_attributes();
                $size_str = $size["groesse"];
                echo "<li><strong>".$name."</strong> - Grösse: ".$size_str." - Preis: ".$product->get_price()."</li>";
            }
            echo    "</ul>";
            echo "</div>";
            echo '<div><strong>Begründung:</strong> '.$order_process->getReturnReason().'</div>';

            echo '<hr>';

            echo '<div>
				<div>
                	<strong>Rückversandetikette:</strong> <a target="_blank" href="'.$return_label_url.'">PDF Anzeigen</a>
            	</div>
            	<div>
                	<strong>Rückversand-Trackingnummer:</strong> <a target="_blank" href="'.$return_tracking_url.'">'.$return_trackingnumber.'</a>
            	</div>
          	</div>
        	';

            echo '<hr>';

            if($order_process->isReplaceActivated()) {
                echo '<div><strong>Ersatz gewünscht:</strong> Ja - '.($order_process->isFullyReplacing() ? "Vollständig" : "Teilweise").'</div>';
                echo '<div><strong>Ersatzbestellung:</strong> <a href="'.get_edit_post_link($order_process->getReplacementOrderId()).'" target="_blank">'.($order_process->getReplacementOrderId()).'</a></div>';
            } else {
                echo '<div><strong>Rückerstattung gewünscht:</strong> Ja - '.($order_process->isFullyRefunding() ? "Vollständig" : "Teilweise").'</div>';
                echo '<div><strong>Betrag für Rückerstattung:</strong> CHF '.($order_process->getTotalPrice()).'</div>';
                echo '<div><strong>IBAN bekannt:</strong> '.($iban ? "Ja" : "Nein").'</div>';
                echo '<div><strong>Rückerstattet:</strong> '.($order_process->isRefunded() ? "Ja" : "Nein").'</div>';
            }
            echo "<hr>";

            //echo '<div><strong>Rückversandavisierung E-Mail verschickt:</strong> '.($return_information_sent ? "Ja" : "Nein" ).'</div>';
            echo '<div><strong>Rückversandauftrag:</strong> <a target="_blank" href="'.$return_order_url.'">PDF Anzeigen</a></div>';
            echo '<div><strong>Rücksendeavisierung bestätigt:</strong> '.($order_process->isReturnReceivedAdminActivated() ? "Ja" : "Nein").'</div>';
            echo '<div><strong>Paket bei Logistik eingetroffen:</strong> '.($order_process->isReturnControlStarted() ? "Ja" : "Nein").'</div>';
            echo '<div><strong>Inhalt von Logistik kontrolliert:</strong> '.($order_process->isReturnReceivedActivated() ? "Ja" : "Nein").'</div>';
            echo "<hr>";
        }

		if(!$order_process->isReturnActivated()) {
			echo "<div>
                Diese Bestellung ist nicht für den Rückversand aktiviert.
            </div>";
		}


	}


	public function create_return_information(int $order_id, array $return_product_ids) {
		$order = new WC_Order($order_id);

		$order_process = BT_OrderProcess::load_by_order_id($order_id); // TODO handle error

		$paths = $this->create_return_order($return_product_ids, $order, $order_process); // TODO handle errors here

		// store labels in meta
		$order->update_meta_data(static::$return_order_url_key, $paths['url']);
		$order->update_meta_data(static::$return_order_path_key, $paths['path']);

		$attachments = [
			$paths['path']
		];
		$this->send_return_order($order, $attachments);

		$order->save();

	}


	public function create_replacement_order(WC_Order $original_order, $product_ids) {
		// TODO: not finished
		$new_order = wc_create_order();
		foreach($product_ids as $product_id){
			$product = wc_get_product($product_id);
			$new_order->add_product( $product, 1);
		}
	}

	public function send_return_order(WC_Order $order, array $attachments) {

		$order_id = $order->get_id();

		$subject = 'Rücksendeavisierung';
		$message = "Die Bestellung $order_id wird zurückgesandt.";
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$mail_logistics = get_option('mail_logistics');
		$copy_to_admin = get_option('copy_to_admin');
		if($copy_to_admin){
			$babytuch_admin_email = get_option('babytuch_admin_email');
			$headers[] = "Cc: $babytuch_admin_email";
		}
		wp_mail( $mail_logistics, $subject, $message, $headers, $attachments);

		$order->update_meta_data('_babytuch_return_information_sent', 1);
		$order->save();
	}

	public function create_return_order(array $return_product_ids, WC_Order $order, BT_OrderProcess $order_process): array {
		$order_id = $order_process->getOrderId();

		$products = [];
		foreach($return_product_ids as $id) {
			$products[] = wc_get_product($id);
		}

		$filename = "$order_id-return-order.pdf";
		$path = self::getLogisticInformationPath()."/$filename";
		$url = self::getLogisticInformationURL()."/$filename";

		ob_start();

		$pdf = BT_PDF::createPDF('Rücksendung Auftrag', 'Babytuch Schweiz');
		$pdf->SetFooterMargin(0);
		$pdf->SetAutoPageBreak(false);

		$pdf->SetXY(15, 15);
		$html = '<h1 style="font-size:32px;">Rücksendung</h1><h2>Babytuch</h2>';

		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 2, 0, true, '', false);

		BT_PDF::printOrderInformation($pdf, $order);


		$pdf->Image($this->plugin_path.'/assets/images/returns.png',105-40, 80, 80);

		$pdf->SetXY(15, 130);
		$html = "<h1>Inhaltskontrolle</h1><br>";
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		BT_PDF::printProductInformationGrid($pdf, $products);

		/*
		 * QR Code: Rückversand Auftrag entgegengenommen
		 */

		$return_order_received_url = $order_process->getReturnOrderReceivedURL();
		$return_order_received_qr = $order_process->generateReturnOrderReceivedQRCode();
		$return_order_received_code = $order_process->getReturnOrderReceivedCode();

		$pdf->Image($return_order_received_qr,151,15, 30,30, 'PNG', $return_order_received_url);
		$pdf->Text(153, 45, 'Auftrag entgegengenommen');
		$pdf->Text(153, 50, "Code: $return_order_received_code");


		/*
		 * QR Code: Rückversand Auftrag abgeschlossen
		 */

		$return_completed_url = $order_process->getReturnCompletedURL();
		$return_return_completed_qr = $order_process->generateReturnCompletedQRCode();
		$return_completed_code = $order_process->getReturnCompletedCode();

		$pdf->Image($return_return_completed_qr,151,240, 30,30, 'PNG', $return_completed_url);

		$pdf->Text(153, 270, 'Auftrag abgeschlossen');
		$pdf->Text(153, 275 , "Code: $return_completed_code");


		ob_end_clean();

		$pdf->Output($path, 'F');

		return [
			'path' => $path,
			'url' => $url
		];

	}

	/**
	 * @param int $order_id
	 * Handles the refund after a order has been marked as refunded within WooCommerce
	 */
	public function initiate_full_refund(int $order_id) {
		$order_process = BT_OrderProcess::load_by_order_id($order_id);
		$order_process->setRefunded(true);
		$order_process->save();
		$order = $order_process->getOrder();
		if($order->get_status() !== 'refunded') {
			$order->update_status('refunded');
		}
	}

	public function initiate_partial_refund(int $order_id) {
		$order_process = BT_OrderProcess::load_by_order_id($order_id);
		$order_process->setRefunded(true);
		$order_process->save();
		$order = $order_process->getOrder();
		if($order->get_status() !== 'refunded' || $order->get_status() !== 'partially-refunded') {
			$order->update_status('partially-refunded');
		}
	}

	public function can_restock_refunded_items( bool $true, WC_Order $order, $refunded_line_items ): bool {
		$order_process = BT_OrderProcess::load_by_order_id($order->get_id());
		// prevents restock if it already happened in logistics process
		if($order_process->isReturnReceivedActivated()) {
			return false;
		} else {
			return $true;
		}
	}


}