<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;


use Inc\Api\Helpers;
use Inc\Controllers\LogisticsController;
use Inc\Models\BT_OrderProcess;

/**
 * Class ReturnsAndReplacements
 * @package Inc\Pages
 *
 * Page where the user can mark the order for returning or replacement and the logistics can control the contents.
 */

class ReturnsAndReplacements
{
	public function register() {
        add_shortcode( 'returns_and_replacements_form', array($this, 'returns_and_replacements_form'));
    }

    
    function returns_and_replacements_form( $attributes ) {

	    // form submission with email and order id
	    if(isset($_POST['submit'])){
		    $email = $_POST['email'];
		    $order_id = $_POST['order_id'];

		    $order_process = BT_OrderProcess::load_by_email_and_order_id($email, $order_id);
		    if($order_process) {
			    $url = $order_process->getReturnConfirmURL();
			    header("Location: $url");
			    return;
		    } else {
		        ?>
                <h4>Zu dieser E-Mail Adresse und Bestellnummer wurde keine Bestellung gefunden!</h4>
                <?php
		    }
	    }

        // regular access with URL param
	    if (isset($_GET['code'])) {
           $return_code = $_GET['code'];

	        try {
		        $controller = LogisticsController::create_from_return_code( $return_code );
		        $order_process = $controller->getOrderProcess();

		        if($order_process->isReturnActivated()) {
		            $controller->start_return_control();
			        $order = $order_process->getOrder();
			        $address = Helpers::getShippingAddressFromOrder($order);
			        ?>


                    <h3>Die Rücksendung (Bst.Nr:  <?php echo $order->get_id(); ?>) wurde erfolgreich entgegengenommen.</h3>
                    <h4>Du kannst die Bestellung nun überprüfen.</h4>
                    <br>
                    <p>
                        <strong>Absender:</strong><br />
				        <?php echo $address->getFullName(); ?><br />
				        <?php echo $address->getStreet(); ?><br />
				        <?php echo $address->getZipAndCity(); ?><br />
				        <?php echo $address->getCountry(); ?><br />
                    </p>



			        <?php
                } else {
		            $controller->replace_or_refund();
		            $order = $order_process->getOrder();
		            $first_name = $order->get_billing_first_name();
		            $refund_url = $order_process->getRefundURL();
		            $replace_url = $order_process->getReplaceURL();

			        ?>

                    <h3>Hallo <?php echo "$first_name" ?></h3>
                    <h4>Möchtest du die Babytücher umtauschen oder eine Rückerstattung beantragen?</h4>
                    <br/>
                    <a href="<?php echo $refund_url; ?>" class="button">Rückerstattung</a>
                    <a href="<?php echo $replace_url; ?>" class="button">Umtauschen</a>

			        <?php
                }


	        } catch ( \Exception $e ) {
		        ?>
                <h3>Es gab ein Problem</h3>
                <h4><?php echo $e->getMessage(); ?></h4>
		        <?php
	        }
        } else {
	        ?>
            <h3>Bitte fülle das Formular aus, um Deine Rücksendung zu aktivieren.</h3>

            <form method="post" action="">
                <label for="email">E-Mail: </label><br>
                <input type="email" id="email" name="email" value=""><br>
                <label for="order_id">Bestell-Nummer: </label><br>
                <input type="text" id="order_id" name="order_id" value=""><br><br>
                <input type="submit" value="Senden" name="submit">
            </form>
	        <?php
        }
   }
}

