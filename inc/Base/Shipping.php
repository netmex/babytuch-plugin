<?php


namespace Inc\Base;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Inc\Api\BT_PDF;
use Inc\Api\Helpers;
use Inc\Api\QRCodeGenerator;
use Inc\Models\BT_OrderProcess;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use WC_Order;

class Shipping extends BaseController {


	public static string $logistic_information_url_key = '_babytuch_logistic_information_url';

	public static string $shipping_label_url_key = '_babytuch_shipping_label_url';
	public static string $shipping_label_path_key = '_babytuch_shipping_label_path';

	public static string $return_label_url_key = '_babytuch_return_label_url';
	public static string $return_label_path_key = '_babytuch_return_label_path';

	public static string $logistic_labels_url_key = '_babytuch_logistic_label_url';
	public static string $logistic_labels_path_key = '_babytuch_logistic_label_path';

	public static string $referral_cards_url_key = '_babytuch_referral_cards_url';
	public static string $referral_cards_path_key = '_babytuch_referral_cards_path';

	public static string $logistic_order_url_key = '_babytuch_logistic_order_url';
	public static string $logistic_order_path_key = '_babytuch_logistic_order_path';

	public static string $trackingnumber_key = '_babytuch_trackingnumber';
	public static string $trackingurl_key = '_babytuch_trackingurl';

	public static string $return_trackingnumber_key = '_babytuch_return_trackingnumber';
	public static string $return_trackingurl_key = '_babytuch_return_trackingurl';

	private QRCodeGenerator $qr_code_generator;

	public function register() {
		add_action( 'woocommerce_order_status_processing', [$this, 'initiate_shipping'] );
		// add our own item to the order actions meta box
		add_action( 'woocommerce_order_actions', [$this, 'add_order_meta_box_actions'] );
		add_action( 'woocommerce_order_action_babytuch_regenerate_logistic_information', array( $this, 'regenerate_logistic_information' ) );
		// Adding Meta container admin shop_order pages
		add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );
		$this->qr_code_generator = new QRCodeGenerator();
	}

	public function add_meta_boxes() {
		add_meta_box( 'babytuch_shipping', __('Versandetiketten','babytuch'), [$this, 'add_shipping_meta_box_fields'], 'shop_order', 'side', 'core' );
	}

	public function add_shipping_meta_box_fields($post) {
		$order = wc_get_order($post->ID);
		$status = $order->get_status();

		if($status == "on-hold") {
			echo "<div>
                Trackingnummern und Adressetiketten werden erst generiert, wenn der Auftrag den Status 'In Bearbeitung' hat.
            </div>";
		} else {
			$shipping_label_url = get_post_meta( $post->ID, Shipping::$shipping_label_url_key, true ) ?: '';

			$trackingnumber = get_post_meta( $post->ID, Shipping::$trackingnumber_key, true ) ?: '';
			$tracking_url = get_post_meta( $post->ID, Shipping::$trackingurl_key, true ) ?: '';

			$logistic_order_url = get_post_meta( $post->ID, Shipping::$logistic_order_url_key, true ) ?: '';
			$logistic_labels_url = get_post_meta( $post->ID, Shipping::$logistic_labels_url_key, true ) ?: '';
			$referral_cards_url = get_post_meta( $post->ID, Shipping::$referral_cards_url_key, true ) ?: '';

			$logistics_information_sent = get_post_meta( $post->ID, '_babytuch_logistic_information_sent', true ) ?: false;

			echo '<div>
            <div>
                <strong>Versandetikette:</strong> <a target="_blank" href="'.$shipping_label_url.'">PDF Anzeigen</a>
            </div>
            <div>
                <strong>Trackingnummer:</strong> <a target="_blank" href="'.$tracking_url.'">'.$trackingnumber.'</a>
            </div>
            <hr>
            <div>
                <strong>Logistik Auftrag:</strong> <a target="_blank" href="'.$logistic_order_url.'">PDF Anzeigen</a>
            </div>
            <div>
                <strong>Logistik Etiketten:</strong> <a target="_blank" href="'.$logistic_labels_url.'">PDF Anzeigen</a>
            </div>
            <div>
                <strong>Vermittlungskarten:</strong> <a target="_blank" href="'.$referral_cards_url.'">PDF Anzeigen</a>
            </div>
            <div>
                <strong>Logistik Info E-Mail verschickt:</strong> '.($logistics_information_sent ? "Ja" : "Nein" ).'
            </div>
          </div>
   			';
		}
	}

	public function add_order_meta_box_actions( $actions ) {
		$actions['babytuch_regenerate_logistic_information'] = __( 'Logistik Information generieren und versenden', 'babytuch' );
		return $actions;
	}

	public function regenerate_logistic_information(WC_Order $order ) {
		$this->initiate_shipping($order->get_id());
	}

	public function initiate_shipping($order_id) {

		$order = new WC_Order($order_id);
		$recipientAddress = Helpers::getShippingAddressFromOrder($order);
		$senderAddress = Helpers::getAdminAddress();
		$warehouseAddress = Helpers::getLogisticsAddress();

		try {
			$this->generate_shipping_label( $order, $recipientAddress, $senderAddress );
			$this->generate_return_label($order, $warehouseAddress, $recipientAddress);
		} catch ( GuzzleException $e ) {
			$order->add_order_note(__('Es gab einen Fehler beim Anfordern der Adressetiketten: '.strip_tags($e->getMessage()), 'babytuch'));

			// send mail to admin
			$order_url = $order->get_edit_order_url();
			$msg = "Es gab einen Fehler beim Anfordern der Adressetiketten von der Post API. <br><br>";
			$msg .= "<strong>Fehlermeldung:</strong><br>";
			$msg .= $e->getMessage();
			$msg .= "<br><br>";
			$msg .= "Folge diesen Schritten, um das Problem zu beheben:<br>";
			$msg .= "<ol>";
			$msg .= "<li>Überprüfe und korrigiere die (Liefer-)Adresse der <a href='$order_url'>Bestellung</a></li>";
			$msg .= "<li>Löse die Aktion 'Logistik Information generieren und versenden' aus.</li>";
			$msg .= "</ol>";
			$msg .= "Falls das Problem weiterhin besteht, kontaktiere den technischen Administrator der Seite.";

			$babytuch_admin_email = get_option('babytuch_admin_email');
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail($babytuch_admin_email, 'Babytuch Fehler bei Bestellung', $msg, $headers);
			return;
		}

        try {
            $paths = $this->create_logistic_information($order);
        } catch (Exception $e) {
            $order->add_order_note(__('Es gab einen Fehler beim Erstellen der Logistiketiketten: '.$e->getMessage(), 'babytuch'));
            return;
        }

		// store labels in meta
		$order->update_meta_data(static::$logistic_labels_url_key, $paths['logistic_labels']['url']);
		$order->update_meta_data(static::$logistic_labels_path_key, $paths['logistic_labels']['path']);

		// store referral cards in meta
		$order->update_meta_data(static::$referral_cards_url_key, $paths['referral_cards']['url']);
		$order->update_meta_data(static::$referral_cards_path_key, $paths['referral_cards']['path']);

		// store order in meta
		$order->update_meta_data(static::$logistic_order_url_key, $paths['logistic_order']['url']);
		$order->update_meta_data(static::$logistic_order_path_key, $paths['logistic_order']['path']);
		$order->save(); // important to save the meta data

		$attachments = [
			$paths['logistic_labels']['path'],
			//$paths['referral_cards']['path'],
			$paths['logistic_order']['path']
		];

		$this->send_shipping_information($order, $attachments);

	}

	/**
	 * @throws GuzzleException
	 */
	private function generate_shipping_label(WC_Order $order , $recipientAddress, $senderAddress) {
		// generate address label for order
		$addressLabel = Helpers::createAddressLabel($order->get_id(), $recipientAddress, $senderAddress);

		$label = $addressLabel['labelPath'];
		$trackingNumber = $addressLabel['trackingNumber'];

		// attach label to order
		$attachment_url = Helpers::attachFileToOrder($order->get_id(), $label);
		$order->update_meta_data('_babytuch_shipping_label_url', $attachment_url);
		$order->update_meta_data(static::$shipping_label_path_key, $label);

		// attach tracking number to order
		$order->update_meta_data('_babytuch_trackingnumber', $trackingNumber);

		$trackingUrl = $this->getTrackingUrlFromNumber($trackingNumber);
		$order->update_meta_data('_babytuch_trackingurl', $trackingUrl);

		// generate order notes with tracking number and shipping label
		$order->add_order_note(__('Versandetikette und Trackingnummer wurden erfolgreich erstellt.', 'babytuch'));
	}

	/**
	 * @throws GuzzleException
	 */
	private function generate_return_label($order, $warehouseAddress, $senderAddress) {
		// generate address label for order
		$addressLabel = Helpers::createAddressLabel($order->get_id(), $warehouseAddress, $senderAddress, true);

		$label = $addressLabel['labelPath'];
		$trackingNumber = $addressLabel['trackingNumber'];

		// attach label to order
		$attachment_url = Helpers::attachFileToOrder($order->get_id(), $label);
		$order->update_meta_data('_babytuch_return_label_url', $attachment_url);
		$order->update_meta_data(static::$return_label_path_key, $label);

		// attach tracking number to order
		$order->update_meta_data('_babytuch_return_trackingnumber', $trackingNumber);

		$trackingUrl = $this->getTrackingUrlFromNumber($trackingNumber);
		$order->update_meta_data('_babytuch_return_trackingurl', $trackingUrl);

		// generate order notes with tracking number and shipping label
		$order->add_order_note(__('Rückversandetikette und Trackingnummer wurden erfolgreich erstellt.', 'babytuch'));
	}

	private function getTrackingUrlFromNumber(string $trackingNumber): string {
		return 'https://www.post.ch/swisspost-tracking?formattedParcelCodes='.$trackingNumber;
	}

	public function send_shipping_information(WC_Order $order, array $attachments) {

		$order_id = $order->get_id();
		$subject = "Bestellung #$order_id verarbeiten";

		$order_items = $order->get_items();
		$message = "Die folgenden Produkte können nun verpackt und versendet werden: <br>";

		foreach($order_items as $product_item){
			$name = $product_item['name'];
			$qty = $product_item['qty'];
			$message .="<br><br>$name<br>".$qty."x";
		}

		$recipient = Helpers::getShippingAddressFromOrder($order);

		$message .= "<br><br><br><br>Empfänger:<br>". $recipient->getFullName() . "<br>"
		            . $recipient->getStreet() . "<br>" . $recipient->getZip() . " " . $recipient->getCity() . " " . $recipient->getCountry();


		$header[] = 'Content-Type: text/html; charset=UTF-8';
		$mail_logistics = get_option('mail_logistics');
		$copy_to_admin = get_option('copy_to_admin');
		if($copy_to_admin){
			$babytuch_admin_email = get_option('babytuch_admin_email');
			$header[] = "Cc: $babytuch_admin_email";
		}
		wp_mail( $mail_logistics, $subject, $message, $header, $attachments);

		$order->update_meta_data('_babytuch_logistic_information_sent', 1);
		$order->save();
	}

    /**
     * @throws Exception
     */
    public function create_logistic_information(WC_Order $order):array {

		$shipping_label_path = $order->get_meta(static::$shipping_label_path_key);
		// TODO: stop here if label is not found

		$return_label_path = $order->get_meta(static::$return_label_path_key);
		// TODO: stop here if label is not found

		$order_id = $order->get_id();

		$user_id = $order->get_user_id();
		$raf_code = '404';

		if($user_id) {
			$raf_code = get_user_meta($user_id, 'gens_referral_id') ?: '404';
			// TODO: what should we do if user is not found??
		}

		$customer_address = Helpers::getShippingAddressFromOrder($order);

		$order_process = BT_OrderProcess::load_by_order_id($order_id); // TODO handle error


		$logistic_labels = $this->create_logistic_labels($return_label_path, $shipping_label_path, $order_process);

		$referral_cards = $this->create_referral_cards($customer_address->getFullName(), $raf_code, $order_process);

		$logistic_order = $this->create_logistic_order($order, $order_process);

		return [
			'logistic_labels' => $logistic_labels,
			'referral_cards' => $referral_cards,
			'logistic_order' => $logistic_order
		];

	}


    /**
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfReader\PdfReaderException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
     */
    public function create_logistic_labels($return_label_path, $shipping_label_path, BT_OrderProcess $order_process):array {
		$order_id = $order_process->getOrderId();
		$return_code = $order_process->getReturnConfirmCode();
		$filename = "$order_id-logistic-labels.pdf";
		$path = self::getLogisticInformationPath()."/$filename";
		$url = self::getLogisticInformationURL()."/$filename";

		ob_start();

		$labels_pdf = BT_PDF::createPDF('Etiketten', 'Babytuch Schweiz');

		/* ---------------------------------------------
		 *  Top left : Rücksendetikette Post
		 * --------------------------------------------- */

		$labels_pdf->setXY(0,0);
		$labels_pdf->StartTransform();
		$labels_pdf->Rotate(90);

		// import return label
		$labels_pdf->setSourceFile($return_label_path);
		$return_label_pdf = $labels_pdf->importPage(1);
		$labels_pdf->useImportedPage($return_label_pdf, -148 , -6, 148, 105);

		$logistic_return_confirm_url = $order_process->getReturnConfirmURL();
		$logistic_return_confirm_qr =$order_process->generateReturnConfirmQRCode();

		$labels_pdf->setXY(0,0);

		// QR Code: Rücksendung bestätigt
		$logistic_return_confirm_qr_pos = ['x' => -35, 'y' => 70];
		$labels_pdf->Image($logistic_return_confirm_qr,$logistic_return_confirm_qr_pos['x'],$logistic_return_confirm_qr_pos['y'], 30,30, 'PNG', $logistic_return_confirm_url);

		$labels_pdf->StopTransform();

		/* ---------------------------------------------
		 *  Bottom left: Versandetikette Post
		 * --------------------------------------------- */

		$labels_pdf->SetXY(0, 0);
		$labels_pdf->StartTransform();
		$labels_pdf->Rotate(90);

		// import shipping label
		$labels_pdf->setSourceFile($shipping_label_path);
		$shipping_label_pdf = $labels_pdf->importPage(1);
		$labels_pdf->useImportedPage($shipping_label_pdf, -296, -6, 148, 105);
		$labels_pdf->StopTransform();


		/* ---------------------------------------------
		 *  Top right: Return Information
		 * --------------------------------------------- */

		$returnInfo = '
		
		<h1>Umtausch und Rückgabe</h1>
		<p>Wir freuen uns sehr, dass du dich für ein Babytuch entschieden hast und wünschen dir viele schöne Tragestunden mit deinem Goldschatz.</p>
		<p>Jedes Babytuch, das unser Haus verlässt, wird nach strengen Qualitätsstandards sorgfältig von Hand geprüft und einzeln kontrolliert. Dennoch kann es vorkommen, dass du etwas zurückschicken möchtest. Vielleicht hast du eine Auswahl an Babytüchern bestellt oder es gibt andere Gründe. Für diese Fälle gehst du wie folgt vor:</p>
		<ol>
			<li>Scanne den QR-Code mit deinem Smartphone und öffne die so hinterlegte Webseite oder logge dich in dein Konto auf babytuch.ch ein, öffne den Punkt "Bestellungen" und wähle die betroffene Bestellung aus.</li>
			<li>Wähle jene Artikel aus, welche du zurückschicken möchtest und ergänze den Grund.</li>
			<li>Prüfe, ob du deine Konto-Nummer im IBAN-Format korrekt erfasst ist.</li>
			<li>Verpacke die ausgewählten Artikel – am besten in der selben Schachtel mit der wir dir die Babytücher zugestellt haben – und verschliesse die Schachtel.</li>
			<li>Löse die Rücksende-Etikette von oben ab und überklebe damit die aufgeklebte Etikette.</li>
			<li>Bringe das Paket auf die Post. Die Sendung ist bereits frankiert.</li>
			<li>Nachdem wir das Paket erhalten und den Inhalt geprüft haben, werden wir dir den Warenwert abzüglich einer Bearbeitungspauschale auf dein Konto zurückerstatten. Beachte, dass Umtausch und Rückgabe nur bei sauberen, unbenutzten und nicht gewaschenen Babytüchern möglich ist!</li>
		</ol>
		';
		$labels_pdf->setListIndentWidth(4);
		$labels_pdf->writeHTMLCell(95,148,110,5, $returnInfo);

		// QR Code
		$return_url = $order_process->getReturnConfirmURL();
		$return_qr = $order_process->generateReturnConfirmQRCode();
		$labels_pdf->Image($return_qr,110,156, 30,30, 'PNG', $return_url);
		$labels_pdf->Text(110, 186, 'Ware zurücksenden');
		$labels_pdf->Text(110, 191, "Code: $return_code");

		ob_end_clean();

		$labels_pdf->Output($path, 'F');

		return [
			'path' => $path,
			'url' => $url
		];
	}

    /**
     * @throws Exception
     */
    public function create_referral_cards($full_name, $raf_code, BT_OrderProcess $order_process):array {

		$rows = 5;
		$cols = 2;
		$referenceCardSize = ['w' => 85, 'h' => 54];
		$gap = 10;
		$margin = ['x' => 14, 'y' => 13];
		$order_id = $order_process->getOrderId();

		$filename = "$order_id-referral-cards.pdf";
		$path = self::getLogisticInformationPath()."/$filename";
		$url = self::getLogisticInformationURL()."/$filename";

		ob_start();

		$pdf = BT_PDF::createPDF('Vermittlungskarten', 'Babytuch Schweiz');

		/* ---------------------------------------------
		 *      Seite 1: Vermittlungskarten Vorderseite
		 * --------------------------------------------- */

		$pdf->SetMargins(0, 0, 0, true);
		$pdf->SetFooterMargin(0);
		$pdf->SetAutoPageBreak(false);


		/*$order_wc = wc_get_order($order_id);
		//$customer_id=$order->get_customer_id('view');
		$customer_email = $order_wc->get_billing_email('view');
		$table_name = $wpdb->prefix . 'wc_customer_lookup';
		$user_id = $wpdb->get_results(
			$wpdb->prepare( "
         SELECT * FROM $table_name
         WHERE email = %s",
				$customer_email
			)
		);

		$user_id_json = json_decode(json_encode($user_id), true);
		$id=$user_id_json[0]["user_id"];
		if($id==NULL and count($user_id_json)>1){
			$id=$user_id_json[1]["user_id"];
		}

		if($id!=NULL){
			$table_name2 = $wpdb->prefix . 'usermeta';
			$ref_id = $wpdb->get_results(
				$wpdb->prepare( "
                SELECT meta_value FROM $table_name2
                WHERE user_id = %s AND meta_key='gens_referral_id'",
					$id
				)
			);
			$ref_id_json = json_decode(json_encode($ref_id), true);
			$raf_code = $ref_id_json[0]["meta_value"];
		}else{
			$raf_code = '404';
		}*/

		for($row=0;$row < $rows; $row++) {
			for($col=0;$col < $cols; $col++) {

				$y_off = 2 + $margin['y'] + $row * $referenceCardSize['h'];
				$x_off = $margin['x'] + $col * ($gap+$referenceCardSize['w']);
				// uncomment do debug card positioning
				//$pdf->Rect($margin['x']+$col*($gap+$referenceCardSize['w']), $margin['y']+$row*$referenceCardSize['h'], $referenceCardSize['w'], $referenceCardSize['h'], 'D', array('all' => $style3));
				$pdf->SetXY($x_off, $y_off);
				$pdf->SetFont('nunitosansb', '', 20/1.5);
				$pdf->MultiCell(85, 8, 'Ihr persönlicher Einladungscode', 0, 'C', 0, 1, $x_off, '', true);
				$pdf->SetFont('nunitosans', '', 14/1.5);
				$pdf->MultiCell(85, 8, 'Sie wurden eingeladen von:', 0, 'C', 0, 1, $x_off, '', true);
				$pdf->SetFont('nunitosansbi', '', 16/1.5);
				$pdf->MultiCell(85, 8, $full_name, 0, 'C', 0, 1, $x_off, '', true);
				$pdf->SetFont('nunitosans', '', 14/1.5);
				$pdf->setCellHeightRatio(1.25);
				$pdf->MultiCell(50, 10, 'Wenn Sie diesen Code bei Ihrer Bestellung auf babytuch.ch einlösen, erhält die Person von der Sie diese Karte erhalten haben 5.- CHF zurück.', 0, 'L', 0, 2, 5+ $x_off, '', true);

				// TODO: use proper QR generator
				// TODO: handle case if user has no account
				$reference_url = $this->home_url.'?raf='.$raf_code;
				$reference_qr = $this->qr_code_generator->generate($reference_url, $order_id.'-reference.png');
				$pdf->Image($reference_qr,54 + $x_off,22 + $y_off, 30,30, 'PNG', $reference_url);

			}
		}

		/* ---------------------------------------------
		 *      Seite 2: Vermittlungskarten Rückseite
		 * --------------------------------------------- */

		$pdf->AddPage();
		$pdf->SetMargins(0, 0, 0, true);
		$pdf->SetFooterMargin(0);
		$pdf->SetAutoPageBreak(false);

		// import reference-card back
		$pdf->setSourceFile($this->plugin_path.'/assets/images/reference-card-back.pdf');
		$reference_card_back_pdf = $pdf->importPage(1);

		for($row=0;$row < $rows; $row++) {
			for( $col = 0; $col < $cols; $col ++ ) {
				$y_off = $margin['y'] + $row * $referenceCardSize['h'];
				$x_off = $margin['x'] + $col * ($gap+$referenceCardSize['w']);

				$pdf->useImportedPage($reference_card_back_pdf, $x_off, $y_off, $referenceCardSize['w'], $referenceCardSize['h']);
			}
		}

		ob_end_clean();

		$pdf->Output($path, 'F');

		return [
			'path' => $path,
			'url' => $url
		];
	}

	public function create_logistic_order(WC_Order $order, BT_OrderProcess $order_process): array {

		$order_id = $order_process->getOrderId();
		$processing_code = $order_process->getProcessingCode();
		$sent_code = $order_process->getSentCode();

		$filename = "$order_id-logistic-order.pdf";
		$path = self::getLogisticInformationPath()."/$filename";
		$url = self::getLogisticInformationURL()."/$filename";

		ob_start();

		$pdf = BT_PDF::createPDF('Vermittlungskarten', 'Babytuch Schweiz');

		$pdf->SetFooterMargin(0);
		$pdf->SetAutoPageBreak(false);
		$pdf->SetXY(15, 15);
		$html = '<h1 style="font-size:32px;">Versandauftrag</h1><h2>Babytuch</h2>';

		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 2, 0, true, '', false);

		BT_PDF::printOrderInformation($pdf, $order);


		$pdf->SetXY(15, 70);
		$html = "<h1>Benötigtes Verpackungsmaterial</h1><br>";
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);


		$pdf->Image($this->plugin_path.'/assets/images/supplements.png',15, '', 180);

		$pdf->SetXY(15, 120);

		$html = '<table>
    <tbody>
        <tr style="font-size:14px;line-height:20px">';
		$num_items = $order->get_item_count();
		$small_package_limiter = get_option('small_package_limiter');
		if((int)$num_items < (int)$small_package_limiter){
			$html .='<td style="height:80px;width:135px;">Verpackung:<br />NORMAL</td>';
		}else{
			$html .='<td style="height:80px;width:135px;">Verpackung:<br />GROSS</td>';
		}
		$html .='<td style="height:75px;width:40px;"></td>
            <td style="height:100px;width:100px;">Versandetikette oben links aufkleben, Rest einpacken</td>
            <td style="height:80px;width:80px;"></td>
            <td style="width:120px;">Vermittlungskarten</td>
            <td style="height:80px;width:60px;"></td>
            <td style="width:100px;">Faltflyer:<br />Anleitung und Tipps zu deinem Babytuch</td>
        </tr>
    </tbody>
    </table><br><br><br><br>';
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		$pdf->SetXY(15, 155);


		$products = [];
		foreach($order->get_items() as $item) {
			$amount = $item->get_quantity();
			for($i=0;$i<(int)$amount;$i++){
				$products[] = $item->get_product();
			}
		}

		BT_PDF::printProductInformationGrid($pdf, $products);


		/*
		 * QR Code: Auftrag entgegengenommen
		 */

		$logistic_order_start_url = $order_process->getLogisticStartURL();
		$order_start_qr = $order_process->generateLogisticStartQRCode();

		$pdf->Image($order_start_qr,151,15, 30,30, 'PNG', $logistic_order_start_url);
		$pdf->Text(153, 45, 'Auftrag entgegengenommen');
		$pdf->Text(153, 50, "Code: $processing_code");


		/*
		 * QR Code: Auftrag versandt
		 */

		$logistic_order_sent_url = $order_process->getLogisticSentURL();
		$order_sent_qr = $order_process->generateLogisticSentQRCode();
		$pdf->Image($order_sent_qr,151,240, 30,30, 'PNG', $logistic_order_sent_url);

		$pdf->Text(153, 270, 'Auftrag abgeschlossen');
		$pdf->Text(153, 275 , "Code: $sent_code");


		// ---------------------------------------------------------
		ob_end_clean();

		$pdf->Output($path, 'F');

		return [
			'path' => $path,
			'url' => $url
		];


	}

}