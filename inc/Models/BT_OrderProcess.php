<?php

namespace Inc\Models;

use Inc\Api\Helpers;
use Inc\Api\QRCodeGenerator;
use WC_Order;

class BT_OrderProcess extends Entity {

	private static int $default_return_days_limit = 35;

	private int $return_days_limit;
	protected string $date_order_created;
	protected int $order_id;
	protected string $order_email;
	protected string $order_status;
	protected float $total_price;

	/**
	 * @var string The code that is used to activate the processing of the order
	 */
	protected string $processing_code;

	/**
	 * @var bool Whether the packaging process for this order was started
     *           (i.e., the logistics provider scanned the QR code on the logistics order PDF)
	 */
	protected bool $processing_activated;

	protected string $sent_code;

	/**
	 * @var bool Whether the order is finished packing and marked for being sent
	 */
	protected bool $sent_activated;


	/**
	 * @var string The date at which the order was marked for sending
	 */
	protected string $date_delivered;


	/**
	 * @var string  The code for the logistic to confirm the receiving of the package (printed on the return label)
	 *              Will be used once by the customer to start the return process and once by the logistic to confirm
	 *              the receiving of the package.
	 */
	protected string  $return_code;

    /**
     * 1. $return_activated = true // return process for this order was activated
     * 2. $return_received_admin_activated = true // return order was acknowledged by logistics
     * 3. $return_control_started = true // logistics starts the controlling of the packet
     * 4. $return_received_activated = // logistics controlled the content of the package
     */


	/**
	 * @var bool    Whether the return process for this order was activated
     *              (after the return_code is scanned for the first time by the customer).
	 */
	protected bool    $return_activated;
	protected string  $return_products;

	/**
	 * @var bool    Whether the logistic has confirmed the receiving of the package
     *              (after scanning the return_code on the return label for the second time)
	 */
	protected bool    $return_control_started;

	/** @var string The code for the logistic to confirm the receiving of the package with all its content (after checking it) */
	protected string  $return_received_code;
	protected bool    $return_received_activated;

	/**
	 * @var string The code for the logistic to acknowledge the return order
	 *              (after it was triggered by the customer by scanning the return_code)
	 */
	protected string  $return_received_admin_code;

	/**
	 * @var bool    Whether the logistic acknowledged the return order
	 */
	protected bool    $return_received_admin_activated;

	protected bool    $refunded;
	protected bool    $replace_activated;
	protected int     $replacement_order;
	protected bool    $is_replacement_order;
	protected int     $is_replacement_order_of;

	protected float $cost_of_sending;
	protected bool $not_ok;
	protected string $return_reason;

	protected string $home_url;

	private QRCodeGenerator $qr_code_generator;

	private string $logistic_start_url = "/verpackungsprozess/?code=";
	private string $logistic_sent_url = "/versand/?code=";
	private string $return_confirm_url = "/umtauschen-und-zuruecksenden/?code=";
	private string $return_received_admin_url = "/retourenmanagement-admin/?code=";
	private string $return_received_url = "/retourenmanagement/?code=";
	private string $replace_url = "/umtausch/?code=";
	private string $refund_url = "/ruecksenden/?code=";


	protected static array $attributes = ['date_order_created','order_id','order_email','order_status', 'total_price', 'processing_code', 'processing_activated', 'sent_code', 'sent_activated', 'date_delivered', 'return_code', 'return_activated', 'return_products', 'return_control_started', 'return_received_code', 'return_received_activated', 'return_received_admin_code', 'return_received_admin_activated', 'refunded', 'replace_activated', 'replacement_order', 'is_replacement_order', 'is_replacement_order_of', 'cost_of_sending', 'not_ok', 'return_reason'];

	protected static string $table_name = "babytuch_order_process";

	public function __construct(\stdClass $in) {
		$reflection_object     = new \ReflectionObject($in);
		$reflection_properties = $reflection_object->getProperties();
		foreach ($reflection_properties as $reflection_property) {
			$name = $reflection_property->getName();
			if (property_exists('Inc\Models\BT_OrderProcess', $name)) {
				$this->{$name} = $in->$name;
			}
		}
		$this->qr_code_generator = new QRCodeGenerator();
		$this->home_url = get_home_url();
		$this->return_days_limit = get_option('return_days_limit') ?: self::$default_return_days_limit;
	}


	public static function create_from_order(WC_Order $order): BT_OrderProcess {

		$order_id = $order->get_id();
		$price = $order->get_total() - $order->get_shipping_total();
        $shipment_cost = $order->get_shipping_total();


		$data = array(
			'date_order_created' => current_time( 'mysql' ),
			'order_id' => $order_id,
			'order_email' => $order->get_billing_email(),
			'order_status' => $order->get_status(),
			'total_price' => $price,
			'processing_code' => Helpers::generateUniqueCode($order_id),
			'processing_activated' => false,
			'sent_code' => Helpers::generateUniqueCode($order_id),
			'sent_activated' => false,
			'return_code' => Helpers::generateUniqueCode($order_id),
			'return_activated' => false,
			'return_received_code' => Helpers::generateUniqueCode($order_id),
			'return_received_activated' => false,
			'return_received_admin_code' => Helpers::generateUniqueCode($order_id),
			'return_received_admin_activated' => false,
			'refunded' => false,
            'cost_of_sending' => $shipment_cost
		);

		$order_process = new BT_OrderProcess((object) $data);
		$order_process->save();
		return $order_process;
	}

	public static function load_by($field, $value): ?BT_OrderProcess {
		global $wpdb;
		$result = $wpdb->get_row(
			$wpdb->prepare( "
			SELECT * FROM babytuch_order_process
			WHERE %1s = %s LIMIT 1",
				$field, $value
			)
		);
		if($result) {
			return new BT_OrderProcess($result);
		} else {
			return null;
		}
	}

	public static function load_by_email_and_order_id(string $email, int $order_id): ?BT_OrderProcess {
		global $wpdb;
		$result = $wpdb->get_row(
			$wpdb->prepare( "
			SELECT * FROM babytuch_order_process
			WHERE order_email = %s AND order_id = %d",
				$email, $order_id
			)
		);
		if($result) {
			return new BT_OrderProcess($result);
		} else {
			return null;
		}
	}

	public static function load_by_processing_code($processing_code): ?BT_OrderProcess {
		return self::load_by('processing_code',$processing_code);
	}

	public static function load_by_sent_code($sent_code): ?BT_OrderProcess {
		return self::load_by('sent_code',$sent_code);
	}

	public static function load_by_return_code($return_code): ?BT_OrderProcess {
		return self::load_by('return_code',$return_code);
	}

	public static function load_by_return_received_code($return_received_code): ?BT_OrderProcess {
		return self::load_by('return_received_code',$return_received_code);
	}

	public static function load_by_return_received_admin_code($return_received_admin_code): ?BT_OrderProcess {
		return self::load_by('return_received_admin_code',$return_received_admin_code);
	}

	public static function load_by_order_id($order_id): ?BT_OrderProcess {
		return self::load_by('order_id', $order_id);
	}

	public static function load_by_id($id): ?BT_OrderProcess {
		return self::load_by('id', $id);
	}

	public function getLogisticStartURL():string {
		return $this->getFullURL($this->logistic_start_url.$this->getProcessingCode());
	}

	public function getLogisticSentURL():string {
		return $this->getFullURL($this->logistic_sent_url.$this->getSentCode());
	}

	public function getReturnConfirmURL():string {
		return $this->getFullURL($this->return_confirm_url.$this->getReturnConfirmCode());
	}

	public function getReturnOrderReceivedURL():string {
		return $this->getFullURL($this->return_received_admin_url.$this->getReturnOrderReceivedCode());
	}

	public function getReturnCompletedURL(): string  {
		return $this->getFullURL($this->return_received_url.$this->getReturnCompletedCode());
	}

	public function getRefundURL(): string  {
		return $this->getFullURL($this->refund_url.$this->getReturnConfirmCode());
	}

	public function getReplaceURL(): string  {
		return $this->getFullURL($this->replace_url.$this->getReturnConfirmCode());
	}


	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getCreatedAt(): string {
		return $this->date_order_created;
	}

	/**
	 * @return int
	 */
	public function getOrderId(): int {
		return $this->order_id;
	}

	/**
	 * @return bool|WC_Order
	 */
	public function getOrder(): WC_Order {
		return wc_get_order($this->order_id);
	}

	/**
	 * @return string
	 */
	public function getOrderEmail(): string {
		return $this->order_email;
	}

	/**
	 * @return string
	 */
	public function getOrderStatus(): string {
		return $this->order_status;
	}

	/**
	 * @return float
	 */
	public function getTotalPrice(): float {
		return $this->total_price;
	}

	/**
	 * @return string
	 */
	public function getProcessingCode(): string {
		return $this->processing_code;
	}

	/**
	 * @return bool
	 */
	public function isProcessingActivated(): bool {
		return $this->processing_activated;
	}

	/**
	 * @return string
	 */
	public function getSentCode(): string {
		return $this->sent_code;
	}

	/**
	 * @return bool
	 */
	public function isSentActivated(): bool {
		return $this->sent_activated;
	}

	/**
	 * @return string
	 */
	public function getDateDelivered(): string {
		return $this->date_delivered;
	}

	/**
	 * @return string
	 */
	public function getReturnConfirmCode(): string {
		return $this->return_code;
	}

	/**
	 * @return bool
	 */
	public function isReturnActivated(): bool {
		return $this->return_activated;
	}

	/**
	 * @return array
	 */
	public function getReturnProducts(): array {

		$product_ids = $this->getReturnProductsIds();
		return array_map('wc_get_product', $product_ids); //wc_get_products(['include' => $product_ids]);
	}

	public function getReturnProductsIds(): array {
		// return products is a string in the format: 123,45,23
		$exploded = preg_split('@,@', trim($this->return_products), NULL, PREG_SPLIT_NO_EMPTY);
		return array_map('intval', $exploded);
	}

	/**
	 * @return bool
	 */
	public function isReturnControlStarted(): bool {
		return $this->return_control_started;
	}

	/**
	 * @return string
	 */
	public function getReturnCompletedCode(): string {
		return $this->return_received_code;
	}

	/**
	 * @return bool
	 */
	public function isReturnReceivedActivated(): bool {
		return $this->return_received_activated;
	}

	/**
	 * @return string
	 */
	public function getReturnOrderReceivedCode(): string {
		return $this->return_received_admin_code;
	}

	/**
	 * @return bool
	 */
	public function isReturnReceivedAdminActivated(): bool {
		return $this->return_received_admin_activated;
	}

	/**
	 * @return bool
	 */
	public function isRefunded(): bool {
		return $this->refunded;
	}

	/**
	 * @return bool
	 */
	public function isReplaceActivated(): bool {
		return $this->replace_activated;
	}

	/**
	 * @return int
	 */
	public function getReplacementOrderId(): int {
		return $this->replacement_order;
	}

	public function getReplacementOrder(): WC_Order {
		return wc_get_order($this->replacement_order);
	}

	/**
	 * @return bool
	 */
	public function isReplacementOrder(): bool {
		return $this->is_replacement_order;
	}

	/**
	 * @return int
	 */
	public function getReplacedOrderId(): int {
		return $this->is_replacement_order_of;
	}

	/**
	 * @return float
	 */
	public function getCostOfSending(): float {
		return $this->cost_of_sending;
	}

	/**
	 * @return bool
	 */
	public function isNotOk(): bool {
		return $this->not_ok;
	}

	/**
	 * @return string
	 */
	public function getReturnReason(): string {
		return $this->return_reason;
	}

	public function generateLogisticStartQRCode():string {
		return $this->qr_code_generator->generate(
			$this->getLogisticStartURL(),
			$this->getOrderId()."-order-start.png"
		);
	}

	public function generateLogisticSentQRCode():string {
		return $this->qr_code_generator->generate(
			$this->getLogisticSentURL(),
			$this->getOrderId()."-order-sent.png"
		);
	}

	public function generateReturnOrderReceivedQRCode():string {
		return $this->qr_code_generator->generate(
			$this->getReturnOrderReceivedURL(),
			$this->getOrderId()."-return-order-received.png"
		);
	}

	public function generateReturnCompletedQRCode():string {
		return $this->qr_code_generator->generate(
			$this->getReturnCompletedURL(),
			$this->getOrderId()."-return-completed.png"
		);
	}

	public function generateReturnConfirmQRCode():string {
		return $this->qr_code_generator->generate(
			$this->getReturnConfirmURL(),
			$this->getOrderId()."-return-confirm.png"
		);
	}


	private function getFullURL($url): string {
		return $this->home_url.$url;
	}



	private function getProcessStatus(): string {

		// TODO: go backwards through boolean flags to determine in which state the order is

	}



	public function getReturnStatus(): ?string {
		if($this->isReturnActivated()) {
			//
			if($this->isReturnReceivedAdminActivated()) {
				return 'logistics';
			} else {
				return 'activated';
			}
		} else {
			return null;
		}
	}

	public function isWithinReturnDeadline(): bool {
		$deadline = date('Y-m-d', strtotime($this->getDateDelivered() . " + " . $this->getReturnDaysLimit() . " days"));
		$now = date("Y-m-d", time());
		return $deadline >= $now;
	}

	/**
	 * @return false|int|mixed|void
	 */
	public function getReturnDaysLimit() {
		return $this->return_days_limit;
	}

	public function setTotalPrice( float $total_price ) {
		$this->total_price = $total_price;
	}

	public function setReturnProducts( string $return_products ) {
		$this->return_products = $return_products;
	}


	/**
	 * @param bool $return_control_started
	 */
	public function setReturnControlStarted( bool $return_control_started ): void {
		$this->return_control_started = $return_control_started;
	}

	/**
	 * @param bool $return_activated
	 */
	public function setReturnActivated( bool $return_activated ): void {
		$this->return_activated = $return_activated;
	}

	/**
	 * @param bool $return_received_activated
	 */
	public function setReturnReceivedActivated( bool $return_received_activated): void {
		$this->return_received_activated = $return_received_activated;
	}

	/**
	 * @param bool  $return_received_admin_activated
	 */
	public function setReturnReceivedAdminActivated( bool $return_received_admin_activated): void {
		$this->return_received_admin_activated = $return_received_admin_activated;
	}

	/**
	 * @param string $return_reason
	 */
	public function setReturnReason( string $return_reason ): void {
		$this->return_reason = $return_reason;
	}

	public function restock_order() {
		$return_products = $this->getReturnProducts();
		foreach($return_products as $product) {
			$stock_old = $product->get_stock_quantity();
			$stock_new = (int)$stock_old+1;
			$product->set_stock_quantity($stock_new);
			$product->save();
		}
	}

	/**
	 * @param float $cost_of_sending
	 */
	public function setCostOfSending( float $cost_of_sending ): void {
		$this->cost_of_sending = $cost_of_sending;
	}

	/**
	 * @param bool $processing_activated
	 */
	public function setProcessingActivated( bool $processing_activated ): void {
		$this->processing_activated = $processing_activated;
	}

	/**
	 * @param bool $sent_activated
	 */
	public function setSentActivated( bool $sent_activated ): void {
		$this->sent_activated = $sent_activated;
	}

	/**
	 * @param string $date_delivered
	 */
	public function setDateDelivered( string $date_delivered ): void {
		$this->date_delivered = $date_delivered;
	}

	/**
	 * @param bool $not_ok
	 */
	public function setNotOk( bool $not_ok ): void {
		$this->not_ok = $not_ok;
	}

	/**
	 * @param bool $refunded
	 */
	public function setRefunded( bool $refunded ): void {
		$this->refunded = $refunded;
	}

    public function setIsReplacementOrder(bool $is_replacement_order): void {
        $this->is_replacement_order = $is_replacement_order;
    }

    /**
     * Set the id of the order that is replaced by this order
     * @param int $replaced_order_id
     * @return void
     */
    public function setReplacedOrderId(int $replaced_order_id): void {
        $this->is_replacement_order_of = $replaced_order_id;
    }

    public function setReplaceActivated(bool $replace_activated): void {
        $this->replace_activated = $replace_activated;
    }

    public function setReplacementOrderId(int $replacement_order_id): void {
        $this->replacement_order = $replacement_order_id;
    }

}