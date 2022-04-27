<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;


use Inc\Api\Helpers;
use Inc\Models\BT_OrderProcess;
use Inc\Controllers\LogisticsController;

/**
 * Class ReturnReceivingAdmin
 * @package Inc\Pages
 *
 * Page that is used by the logistics provider to start the processing of the return
 */
class ReturnReceivingAdmin
{
	public function register() {
        add_shortcode( 'return_receiving_admin', array($this, 'return_receiving_admin_form'));
    }

    function return_receiving_admin_form( $attributes ) {

	    // package had no return_label so we start the return_control step automatically.
	    if(isset($_POST["no_label"])){
		    $return_received_admin_code = $_GET['code'];
		    try {
			    $controller = LogisticsController::create_from_return_received_admin_code( $return_received_admin_code );
			    $controller->start_return_control();
			    ?>
                <h3>Die Rücksendung wurde erfolgreich entgegengenommen!</h3>
                <h4>Sie können die Bestellung nun überprüfen und danach die Vollzugsmeldung vornehmen.</h4>
			    <?php

		    } catch ( \Exception $e ) {
			    ?>

                <h3>Es gab ein Problem</h3>
                <h4><?php echo $e->getMessage(); ?></h4>

			    <?php
		    }
	    }


	    if (isset($_GET['code'])) {
            $return_received_admin_code = $_GET['code'];
            echo $return_received_admin_code;

	        try {
		        $controller = LogisticsController::create_from_return_received_admin_code( $return_received_admin_code );
		        $controller->receive_return_order();

		        $order = $controller->getOrderProcess()->getOrder();
		        $address = Helpers::getShippingAddressFromOrder($order);
		        ?>

                <h3>Die Rücksendung (Bst.Nr:  <?php echo $order->get_id(); ?>) wurde erfolgreich entgegengenommen.</h3>
                <h4>Bitte Scannen Sie den QR-Code auf der Rücksende-Etikette und kontrollieren anschliessend die Rücksendung.</h4>
                <br>
                <p>
                    <strong>Absender:</strong><br />
			        <?php echo $address->getFullName(); ?><br />
			        <?php echo $address->getStreet(); ?><br />
			        <?php echo $address->getZipAndCity(); ?><br />
			        <?php echo $address->getCountry(); ?><br />
                </p>
                <form method="post" action="">
                    <label for="fname">Keine Rücksende Etikette vorhanden? Dann bitte den Knopf drücken.</label><br>
                    <input type="submit" value="Keine Etikette" name="no_label">
                </form>
		        <?php

	        } catch ( \Exception $e ) {
		        ?>

                <h3>Es gab ein Problem</h3>
                <h4><?php echo $e->getMessage(); ?></h4>

		        <?php
	        }

        } else {
            ?>
            <h3>Bitte füllen Sie das Formular aus, um die Verarbeitung der Rücksendung zu beginnen</h3>
            <form method="get" action="">
            <label for="code">Code: </label><br>
            <input type="text" id="code" name="code" value=""><br><br>
            <input type="submit" value="Rücksendung beginnen" name="submit">
            </form>
            <?php
        }
    }

}
