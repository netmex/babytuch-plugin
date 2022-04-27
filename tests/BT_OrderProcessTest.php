<?php


use Inc\Api\Helpers;
use Inc\Models\BT_OrderProcess;
use Tests\BT_TestCase;

class BT_OrderProcessTest extends BT_TestCase {
    public function test_new_order_process_is_saved() {
	    $order = wc_create_order();
	    $order->add_product( self::$variation, 2);
	    $order->set_address( $this->customerAddress, 'billing' );
	    $order->calculate_totals();
	    $attributes = array(
		    'date_order_created' => current_time( 'mysql' ),
		    'order_id' => $order->get_id(),
		    'order_email' => $order->get_billing_email(),
		    'order_status' => $order->get_status(),
		    'total_price' => $order->get_total(),
		    'processing_code' => Helpers::generateUniqueCode($order->get_id()),
		    'processing_activated' => false,
		    'sent_code' => Helpers::generateUniqueCode($order->get_id()),
		    'sent_activated' => false,
		    'return_code' => Helpers::generateUniqueCode($order->get_id()),
		    'return_activated' => false,
		    'return_received_code' => Helpers::generateUniqueCode($order->get_id()),
		    'return_received_activated' => false,
		    'return_received_admin_code' => Helpers::generateUniqueCode($order->get_id()),
		    'return_received_admin_activated' => false,
		    'refunded' => false,
	    );

	    $order_process = new BT_OrderProcess((object) $attributes);

	    $order_process->save();

		$this->assertNotEmpty($order_process->getId());

		$persisted_order_process = BT_OrderProcess::load_by_id($order_process->getId());


		foreach($attributes as $attribute => $value) {
			$property = new ReflectionProperty(get_class($order_process), $attribute);
			$property->setAccessible(true);

			$this->assertEquals($property->getValue($order_process), $property->getValue($persisted_order_process));
		}

    }

    public function test_order_can_be_loaded_by_order_id() {
	    $order = wc_create_order();
	    $order_process = BT_OrderProcess::load_by_order_id($order->get_id());
	    $this->assertNotNull($order_process);
    }

    public function test_order_can_be_loaded_by_return_code() {
	    $order = wc_create_order();
	    $order_process = BT_OrderProcess::load_by_order_id($order->get_id());

	    $loaded_by_return_code = BT_OrderProcess::load_by_return_code($order_process->getReturnConfirmCode());
	    $this->assertNotNull($loaded_by_return_code);
    }


    public function test_order_process_update_is_persisted() {
	    $order = wc_create_order();
	    $order->add_product( self::$variation, 2);
	    $order->set_address( $this->customerAddress, 'billing' );
	    $order->calculate_totals();

	    $total_price = $order->get_total() + 20;
	    $return_products = 28;

	    $order_process = BT_OrderProcess::load_by_order_id($order->get_id());

	    $order_process->setTotalPrice($total_price);
	    $order_process->setReturnProducts($return_products);
	    $order_process->save();

	    $persisted_order_process = BT_OrderProcess::load_by_id($order_process->getId());

	    $this->assertEquals($order_process->getTotalPrice(), $persisted_order_process->getTotalPrice());
	    $this->assertEquals($order_process->getReturnProducts(), $persisted_order_process->getReturnProducts());

    }


}