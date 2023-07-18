<?php

namespace Inc\Controllers;

use Exception;
use Inc\Api\Helpers;
use inc\Base\Referrals;
use Inc\Mails\ReplaceMail;
use Inc\Models\BT_OrderProcess;
use WC_Order;
use WC_Product;

class LogisticsController {

	private BT_OrderProcess $order_process;

	/**
	 * LogisticsController constructor
	 *
	 * @param BT_OrderProcess $order_process
	 */
	public function __construct( BT_OrderProcess $order_process) {
		$this->order_process = $order_process;
	}


	/**
	 * @throws Exception
	 */
	public static function create_from_order_id(int $order_id): LogisticsController {
		$order_process = BT_OrderProcess::load_by_order_id($order_id);
		if($order_process) {
			return new self($order_process);
		} else {
			throw new Exception("Es wurde kein Bestellprozess für die Bestellung mit der ID $order_id gefunden.");
		}
	}

	/**
	 * @throws Exception
	 */
	public static function create_from_return_code(string $return_code): self {
		$order_process = BT_OrderProcess::load_by_return_code($return_code);
		if($order_process) {
			return new static($order_process);
		} else {
			throw new Exception("Es wurde kein Retourenprozess für den Code $return_code gefunden.");
		}
	}

	/**
	 * @throws Exception
	 */
	public static function create_from_processing_code(string $processing_code): self {
		$order_process = BT_OrderProcess::load_by_processing_code($processing_code);
		if($order_process) {
			return new static($order_process);
		} else {
			throw new Exception("Es wurde kein Versandprozess für den Code $processing_code gefunden.");
		}
	}

	/**
	 * @throws Exception
	 */
	public static function create_from_sent_code(string $sent_code): self {
		$order_process = BT_OrderProcess::load_by_sent_code($sent_code);
		if($order_process) {
			return new static($order_process);
		} else {
			throw new Exception("Es wurde kein Versandprozess für den Code $sent_code gefunden.");
		}
	}


	/**
	 * @throws Exception
	 */
	public static function create_from_return_received_code(string $return_code): self {
		$order_process = BT_OrderProcess::load_by_return_received_code($return_code);
		if($order_process) {
			return new static($order_process);
		} else {
			throw new Exception("Es wurde kein Retourenprozess für den Code $return_code gefunden.");
		}
	}

	/**
	 * @throws Exception
	 */
	public static function create_from_return_received_admin_code(string $return_code): self {
		$order_process = BT_OrderProcess::load_by_return_received_admin_code($return_code);
		if($order_process) {
			return new static($order_process);
		} else {
			throw new Exception("Es wurde kein Retourenprozess für den Code $return_code gefunden.");
		}
	}


	/**
	 * Acknowledges the order and marks it as being at the start of the packaging process
	 * @throws Exception
	 */
	public function start_processing_order() {
		$order = $this->order_process->getOrder();

		if($this->order_process->isProcessingActivated()) {
			throw new Exception("Der Verpackungsprozess für diese Bestellung wurde bereits gestartet. <br> Scannen Sie den QR-Code unten auf dem Versandauftrag, um den Verpackungsprozess abzuschliessen.");
		}
		if($order->get_status() != 'processing') {
			throw new Exception("Diese Bestellung ist nicht bereit für den Verpackungsprozess.");
		}

		$this->order_process->setProcessingActivated(true);
		$this->order_process->save();
		$order->update_status('packing', 'Logistik hat den Verpackungsprozess gestartet.');

	}

	/**
	 * Marks the order as finished and ready to be sent
	 * @throws Exception
	 */
	public function finish_processing_order() {
		$order = $this->order_process->getOrder();
		if(!$this->order_process->isProcessingActivated()) {
			throw new Exception("Der Verpackungsprozess für diese Bestellung wurde noch nicht gestartet. <br> Scannen Sie den QR-Code oben auf dem Versandauftrag, um den Verpackungsprozess zu starten.");
		}
		if($this->order_process->isSentActivated()) {
			throw new Exception("Der Verpackungsprozess für diese Bestellung wurde bereits abgeschlossen.");
		}
		if($order->get_status() != 'packing') {
			throw new Exception("Diese Bestellung befindet sich nicht im Verpackungsprozess.");
		}

		$this->order_process->setSentActivated(true);
		$date = date('Y-m-d h:i:s',time());
		$this->order_process->setDateDelivered($date);
		$this->order_process->save();

		$order->update_status('completed','Logistik hat den Verpackungsprozess abgeschlossen.');
	}

	/**
	 * Action where user can select whether to replace or refund
	 * @throws Exception
	 *
	 */
	public function replace_or_refund() {
		if($this->order_process->isReturnActivated()) {
			throw new Exception("Deine Bestellung ist bereits zur Rücksendung aktiviert worden.");
		}
		if(!$this->order_process->isWithinReturnDeadline()) {
			$deadline = $this->order_process->getReturnDaysLimit();
			throw new Exception("Die Frist von $deadline Tagen ist abgelaufen. Diese Bestellung kann nicht mehr rückgesandt werden.");
		}
	}

    /**
     * Starts the replacement process after the user filled out the respective form
     * @param string $return_reason The reason why the products are replaced
     * @param array $replaced_product_ids the ids of the products that get replaced
     * @param array $replacement_product_ids the ids of the products that will replace the returned products
     * @param array $replacement_product_sizes the siszes of the products that will replace the returned products
     * @return void
     * @throws \ReflectionException
     * @throws Exception
     */
    public function start_replacement(string $return_reason, array $replaced_product_ids, array $replacement_product_ids, array $replacement_product_sizes) {

        if($this->order_process->isReturnActivated()) {
            throw new Exception("Deine Bestellung ist bereits zur Rücksendung aktiviert worden.");
        }

        if(!$this->order_process->isWithinReturnDeadline()) {
            $deadline = $this->order_process->getReturnDaysLimit();
            throw new Exception("Die Frist von $deadline Tagen ist abgelaufen. Diese Bestellung kann nicht mehr rückgesandt werden.");
        }

        if(empty($replaced_product_ids)) {
            throw new Exception("Bitte wählen Sie mindestens 1 Produkt zum Umtauschen.");
        }



        $return_products = array_map("wc_get_product", $replaced_product_ids);
        $replacement_products = [];

        foreach($replaced_product_ids as $key => $replaced_product_id) {
            $replacement_id = $replacement_product_ids[$key];
            $replacement_size = $replacement_product_sizes[$key];

            // returns the variable product (but not the correct size variant yet)
            $replacement_product = wc_get_product( $replacement_id );

            $replacement_variant = $this->find_product_variant_with_size($replacement_product, $replacement_size);
            if(!$replacement_variant) {
                throw new Exception("Es wurde kein Ersatzprodukt mit der Grösse $replacement_size gefunden.");
            }
            if($replacement_variant->get_id() === intval($replaced_product_id)) {
                throw new Exception("Ein Produkt kann nicht durch das das gleiche Produkt mit der gleichen Grösse ersetzt werden.");
            }
            $replacement_products[] = $replacement_variant;

        }

        $this->out_of_stock_check($replacement_products);

        $this->add_return_reason($return_reason);

        $order = $this->order_process->getOrder();
        $replacement_order = $this->create_replacement_order($replacement_products, $order);

        // update order process of old order to reflect changes
        $this->order_process->setReplaceActivated(true);
        $this->order_process->setReplacementOrderId($replacement_order->get_id());
        $this->order_process->setReturnActivated(true);
        $this->order_process->setReturnReason($return_reason);
        //$this->order_process->setTotalPrice($return_price_total);  // TODO: check what this price update exactly does why do we set it to this? then this stores the total value of the returned products
        $this->order_process->setReturnProducts(implode(",", $replaced_product_ids));
        $this->order_process->save();

        $order->update_status('returning', "Rücksendung wurde angemeldet");

        do_action('babytuch_return_start', $this->order_process->getOrderId(), $return_products);

    }

    /**
     * @throws \ReflectionException
     * @throws \WC_Data_Exception
     */
    public function create_replacement_order(array $replacement_products, WC_Order $replaced_order): WC_Order {
        $replacement_order = wc_create_order();
        foreach($replacement_products as $replacement_product) {
            $replacement_order->add_product( $replacement_product);
        }

        // set referral code on new order
        // TODO: write test that tests this
        Referrals::copy_raf_id($replaced_order, $replacement_order);

        $replacement_order->set_address($replaced_order->get_address('billing'), 'billing');
        $replacement_order->set_address($replaced_order->get_address('shipping'), 'shipping');
        $replacement_order->set_customer_id($replaced_order->get_customer_id());
        $replacement_order->calculate_totals();
        $replacement_order->set_total(0); // customer does not need to pay for replacement order

        $replacement_order->update_status("awaiting-return", 'Ersatzbestellung wartet auf Rücksendung der Originalbestellung');



        // create order process for replacement order
        $order_process = BT_OrderProcess::load_by_order_id($replacement_order->get_id());
        $order_process->setIsReplacementOrder(true);
        $order_process->setReplacedOrderId($replaced_order->get_id());
        $order_process->save();
        // TODO: what do we do with 'cost_of_sending'?

        return $replacement_order;

    }

    /**
     * @throws Exception
     */
    private function out_of_stock_check(array $replacement_products) {
        foreach($replacement_products as $replacement_product) {
            if($replacement_product->get_stock_quantity() <= 0) {
                throw new Exception("Einige Ihrer Ersatzprodukte sind nicht mehr an Lager: ".$replacement_product->get_name()." - Grösse ".$replacement_product->get_data()['attributes']['groesse']." Bitte wählen Sie eine andere Kombination.");
            }
        }
    }

    private function find_product_variant_with_size(WC_Product $product, string $size) {
        // find correct size variation for product
        $children_ids = $product->get_children();
        $children = array_map("wc_get_product", $children_ids);
        foreach($children as $child) {
            if($child->get_data()['attributes']['groesse'] == $size) {
                return $child;
            }
        }
        return null;
    }


	public function start_refund(string $reason, string $iban, array $product_ids) {
		if($this->order_process->isReturnActivated()) {
			throw new Exception("Deine Bestellung ist bereits zur Rücksendung aktiviert worden.");
		}

		if(!$this->order_process->isWithinReturnDeadline()) {
			$deadline = $this->order_process->getReturnDaysLimit();
			throw new Exception("Die Frist von $deadline Tagen ist abgelaufen. Diese Bestellung kann nicht mehr rückgesandt werden.");
		}

		if(empty($iban)) {
			throw new Exception("Bitte geben Sie Ihre IBAN-Nr. ein.");
		}

		if(empty($product_ids)) {
			throw new Exception("Bitte wählen Sie mindestens 1 Produkt zum Zurücksenden.");
		}


		$order = $this->order_process->getOrder();
		$user_id = $order->get_user_id();

		$this->add_return_reason($reason);

		$this->update_iban($iban, $user_id);

		$return_cost = $this->calculate_return_cost($product_ids);
		$return_products = implode(',',$product_ids);

		$this->order_process->setTotalPrice($return_cost);
		$this->order_process->setReturnProducts($return_products);
		$this->order_process->setReturnReason($reason);
		$this->order_process->setReturnActivated(true);
		$this->order_process->save();

		$order->update_status('returning');
		$this->update_total_returns();

		do_action('babytuch_return_start', $order->get_id(), $product_ids);

	}

	/**
	 * Logistics starts the processing of the return order (by scanning the QR code on top of the return order)
	 * @throws Exception
	 */
	public function receive_return_order() {
		$order = $this->order_process->getOrder();
		$order_id = $order->get_id();
		$order = $this->order_process->getOrder();
		if(!$this->order_process->isReturnActivated()) {
			throw new Exception("Die Bestellung #$order_id wurde noch nicht für die Rücksendung aktiviert. Bitte aktivieren Sie zuerst die Rücksendung dieser Bestellung, 
                    um diese danach zum Erhalt aktivieren zu können.");
		}
		if($order->get_status() == "return-received" || $this->order_process->isReturnReceivedAdminActivated()) {
			throw new Exception("Diese Rücksendung (Bst. Nr: $order_id) ist bereits entgegengenommen worden.");
		}

		$this->order_process->setReturnReceivedAdminActivated(true);
		$this->order_process->save();
		$order->update_status('return-received', "Rücksendung ist bei Logistik eingetroffen.");

		// TODO: temporary store order ID and display warning if they don't match
	}


	/**
	 * Logistics starts to control the content of the package by scanning the QR on the packet
	 */
	public function start_return_control() {
		$order = $this->order_process->getOrder();
		$order_id = $order->get_id();
		if(!$this->order_process->isReturnActivated()) {
			throw new Exception("Die Bestellung #$order_id wurde noch nicht für die Rücksendung aktiviert. Bitte aktivieren Sie zuerst die Rücksendung dieser Bestellung, 
                    um diese danach zum Erhalt aktivieren zu können.");
		}
		if(!$this->order_process->isReturnReceivedAdminActivated()) {
			throw new Exception("Der Eingang dieses Rücksendungsauftrags (Bst. Nr: $order_id) wurde noch nicht bestätigt. Bitte scannen Sie zunächst den QR-Code oben rechts auf dem Rücksendungsauftrag und erst anschliessend den QR-Code auf dem Paket.");
		}
		if($this->order_process->isReturnReceivedActivated()) {
			throw new Exception("Diese Rücksendung (Bst. Nr: $order_id) wurde bereits kontrolliert und eingebucht.");
		}
		if($this->order_process->isReturnControlStarted()) {
			throw new Exception("Die Kontrolle dieser Rücksendung (Bst. Nr: $order_id) wurde bereits gestartet.");
		}

		// TODO: throw error if order ID does not match with the one that was scanned last

		$this->order_process->setReturnControlStarted(true);
        $order->add_order_note("Logistik hat Erhalt der Rücksendung bestätigt.");
		if($this->order_process->isReplacementOrder()) {
			$cost = $this->order_process->getCostOfSending();
			$new_cost = round((float)$cost*(2/3),2);
			$this->order_process->setCostOfSending($new_cost);
		}

		$this->order_process->save();
	}

	/**
	 * Logistics finishes the control of the contents of the package
	 */
	public function finish_return_control() {
		if(!$this->order_process->isReturnActivated()) {
			throw new Exception("Diese Bestellung wurde noch nicht für die Rücksendung aktiviert. Bitte aktivieren Sie zuerst die Rücksendung dieser Bestellung, 
                    um diese danach zum Erhalt aktivieren zu können.");
		}
		if(!$this->order_process->isReturnReceivedAdminActivated()) {
			throw new Exception("Der Eingang dieser Rücksendung wurde noch nicht von der Logistik bestätigt. Scannen Sie zuerst den QR Code auf der Rücksendeavisierung.");
		}
		if(!$this->order_process->isReturnControlStarted()) {
			throw new Exception("Die Kontrolle der Rücksendung wurde noch nicht gestartet. Scannen Sie den QR Code auf der Etikette des Pakets.");
		}
		if($this->order_process->isReturnReceivedActivated()) {
			throw new Exception("Diese Rücksendung wurde bereits kontrolliert.");
		}

		// TODO: throw error if order ID does not match with the one that was scanned last
	}


	public function return_control_ok() {
		$order = $this->order_process->getOrder();
		$this->order_process->restock_order();

		$iban = Helpers::getCustomerIbanFromOrder($order);

		$fn = $order->get_billing_first_name();
		$ln = $order->get_billing_last_name();

		$babytuch_admin_email = get_option('babytuch_admin_email');

		if($this->order_process->isReplaceActivated()){
			// it was a return that should be replaced
			// replacement order was already created and can now be marked for shipping
			$replacement_order = $this->order_process->getReplacementOrder();
			$replacement_order->update_status('processing');

			// TODO: put mail in another class
			$subject = "Rücksendung Erhalt aktiviert";
			$message = "Die Rücksendung der Bestellung ". $order->get_id() ." wurde erfolgreich erhalten und die Umtausch Bestellung wird nun verarbeitet.";
			wp_mail( $babytuch_admin_email, $subject, $message );
			$return_products = $this->order_process->getReturnProducts();
			$replace_mail = new ReplaceMail($order, $return_products, $replacement_order);
			$replace_mail->send();

			$order->update_status('replaced','Bestellung wurde ersetzt mit Bestellung #'.$replacement_order->get_id());


		} elseif ($this->order_process->isReplacementOrder()){
			// it is the return of a replacement order so the money needs to be reimbursed minus the sending costs
			$cost_of_sending = $this->order_process->getCostOfSending();
			$price = $this->order_process->getTotalPrice();
			$new_price = (float)$price - (float)$cost_of_sending;

			$this->order_process->setTotalPrice($new_price);

			$subject = "Rücksendung Erhalt aktiviert";
			$message = "Die Rücksendung der Umtausch - Bestellung ". $order->get_id() ." (Name: $fn $ln IBAN: $iban)
                        wurde erfolgreich erhalten und kann nun abzüglich der 
                        Versandkosten zurückerstattet werden. (Rückerstattung: ". $new_price ." Fr.)";
			wp_mail( $babytuch_admin_email, $subject, $message );

			$order->update_status('refund-required','Bestellung wartet auf Rückerstattung.');

		} else {
			// TODO: put mail in another class
			$cost = $this->order_process->getTotalPrice();
			$subject = "Rücksendung Erhalt aktiviert";
			$message = "Die Rücksendung der Bestellung ". $order->get_id() ." (Name: $fn $ln IBAN: $iban) wurde erfolgreich 
                            erhalten (Rückerstattung: ". $cost ." Fr.).";
			wp_mail( $babytuch_admin_email, $subject, $message );

			$order->update_status('refund-required','Bestellung wartet auf Rückerstattung.');

		}


		$this->order_process->setReturnReceivedActivated(true);
		$this->order_process->save();
        $order->add_order_note("Logistik hat Inhalt der Rücksendung kontrolliert.");

        do_action('babytuch_return_end', $this->order_process);

	}

	public function return_control_not_ok() {
		$order = $this->order_process->getOrder();
		$this->order_process->setNotOk(true);
		$this->order_process->save();

		// TODO: put mail in another class
		$home_url = get_home_url();
		$subject = "Rücksendung nicht OK";
		$message = "Die Rücksendung der Bestellung ". $order->get_id() ." wurde als 'nicht OK' 
                        eingestuft. Es betrifft die folgende Bestellung:
                        $home_url/wp-admin/post.php?post=".$order->get_id()."&action=edit";
		$babytuch_admin_email = get_option('babytuch_admin_email');
		wp_mail( $babytuch_admin_email, $subject, $message );

		$order->update_status('action-required','Bestellung benötigt manuellen Eingriff.');
	}


    /**
     * Adds return reason to list of available reasons or updates count of existing reasons
     * @param $new_reason
     * @return void
     */
    private function add_return_reason($new_reason) {
		$returns_reasons = get_option('returns_reasons');
		$new_returns_array = array();
		foreach($returns_reasons as $reason_pair){
			$amount = (int)$reason_pair["amount"];
			$reason = $reason_pair["reason"];
			if($reason==$new_reason){
				$amount = $amount + 1;
			}
			$arr = array(
				'reason'  => $reason,
				'amount' => $amount,
			);
			array_push($new_returns_array, $arr);
		}
		update_option('returns_reasons', $new_returns_array);
	}

	private function update_iban(string $iban, int $user_id) {
		$iban = str_replace(' ','', $iban);
		update_user_meta($user_id, 'iban_num',  $iban);
	}

	private function calculate_return_cost(array $products): int {
		$cost = 0;
		foreach($products as $product_id) {
			$product = wc_get_product($product_id);
			$price = (int)$product->get_price();
			$cost = $cost + $price;
		}
		return $cost;
	}

	private function update_total_returns(): void {
		$total_returns = get_option('total_returns');
		if($total_returns){
			update_option('total_returns', (int)get_option('total_returns')+1);
		} else {
			update_option('total_returns', 1);
		}
	}

	/**
	 * @return BT_OrderProcess
	 */
	public function getOrderProcess(): BT_OrderProcess {
		return $this->order_process;
	}


    public function renderLogisticsOrderProcess() {
        $order = $this->order_process->getOrder();
        $status = $order->get_status();

        $statuses = array(
            'pending' => "Bestellung wurde erfasst",
            'processing' => "Auftrag erstellt",
            'packing' => "Auftrag entgegengenommen",
            'completed' => "Auftrag abgeschlossen",
        );

        echo '<nav>';
        echo '<ol class="cd-multi-steps text-bottom count">';
        $index = 0;
        foreach($statuses as $stat => $text) {

            if($stat == $status) {
                echo '<li class="current"><strong>'.($index+1).'. '.$text.'</strong></li>';
            } else {
                echo '<li><em>'.($index+1).'. '.$text.'</em></li>';
            }
            if($index < count($statuses)-1) {
                echo "<span class='hidden-mobile'>→</span>";
            }
            $index++;
        }
		echo '</ol>';
	echo '</nav>';
    }

    public function renderLogisticsReturnProcess() {
        $order = $this->order_process->getOrder();
        $status = $order->get_status();

        $statuses = array(
            'returning' => "Ware wird zurückgeschickt",
            'return-received' => "Rücksendung erhalten",
            'packing' => "Auftrag entgegengenommen",
            'completed' => "Auftrag abgeschlossen",
        );

        echo '<nav>';
        echo '<ol class="cd-multi-steps text-bottom count">';
        $index = 0;
        foreach($statuses as $stat => $text) {

            if($stat == $status) {
                echo '<li class="current"><strong>'.($index+1).'. '.$text.'</strong></li>';
            } else {
                echo '<li><em>'.($index+1).'. '.$text.'</em></li>';
            }
            if($index < count($statuses)-1) {
                echo "<span class='hidden-mobile'>→</span>";
            }
            $index++;
        }
    }

}