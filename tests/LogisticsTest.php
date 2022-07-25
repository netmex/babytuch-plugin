<?php


use Inc\Base\Returns;
use Inc\Controllers\LogisticsController;
use Inc\Models\BT_OrderProcess;

final class LogisticsTest extends BT_TestCase {

    /**
     * @throws Exception
     */
    public function test_accept_shipping_order() {

        $order = self::initOrder();
        $order->update_status("processing"); // mark order as paid

        $this->start_processing_order($order);

        // reload to see changes in effect
        $order = new WC_Order($order->get_id());
        $order_process = BT_OrderProcess::load_by_order_id($order->get_id());

        // assertions
        $this->assertEquals( "packing", $order->get_status());
        $this->assertTrue($order_process->isProcessingActivated());

    }

    public function test_finish_processing_order() {
        $order = self::initOrder();
        $order->update_status("processing"); // mark order as paid

        $this->start_processing_order($order);
        $this->finish_processing_order($order);

        // reload to see changes in effect
        $order = new WC_Order($order->get_id());
        $order_process = BT_OrderProcess::load_by_order_id($order->get_id());

        // assertions
        $this->assertEquals( "completed", $order->get_status());
        $this->assertTrue($order_process->isSentActivated());
        $this->assertNotEmpty($order_process->getDateDelivered());

    }

    public function test_finish_processing_order_without_acknowledgement_fails() {
        $this->expectException(Exception::class);

        $order = self::initOrder();
        $order->update_status("processing"); // mark order as paid

        $this->finish_processing_order($order);

    }

    public function test_finish_processing_order_twice_fails() {
        $this->expectException(Exception::class);

        $order = self::initOrder();
        $order->update_status("processing"); // mark order as paid

        $this->start_processing_order($order);
        $this->finish_processing_order($order); // should be ok
        $this->finish_processing_order($order); // should not be ok

    }

    public function test_start_order_twice_fails() {
        $this->expectException(Exception::class);

        $order = self::initOrder();
        $order->update_status("processing"); // mark order as paid
        $this->start_processing_order($order); // should be ok
        $this->start_processing_order($order); // should not be ok
    }

    private function start_processing_order(WC_Order $order) {
        $order_process = BT_OrderProcess::load_by_order_id($order->get_id());
        $controller = LogisticsController::create_from_processing_code($order_process->getProcessingCode());
        $controller->start_processing_order();
    }

    private function finish_processing_order(WC_Order $order) {
        $order_process = BT_OrderProcess::load_by_order_id($order->get_id());
        $controller = LogisticsController::create_from_sent_code($order_process->getSentCode());
        $controller->finish_processing_order();
    }

}
