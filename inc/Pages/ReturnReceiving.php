<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;


use Inc\Controllers\LogisticsController;


/**
 * Class ReturnReceiving
 * @package Inc\Pages
 *
 * Page that is used to control the contents of the package.
 */
class ReturnReceiving
{
	public function register() {
        add_shortcode( 'return_receiving', array($this, 'return_receiving_form'));
    }

    function return_receiving_form( $attributes ) {

        if (isset($_GET['code'])) {
            $return_received_code = $_GET['code'];

	        try {
		        $controller = LogisticsController::create_from_return_received_code( $return_received_code );


		        if (isset($_POST['ok'])) {
		            $controller->return_control_ok();
			        echo'<h2>Vollzugsmeldung erfolgreich!</h2>';

                } elseif(isset($_POST['not_ok'])) {
		            $controller->return_control_not_ok();
			        echo '<h2>Der Admin wurde informiert.</h2>';
                } else {
			        $controller->finish_return_control();
			        $order_id = $controller->getOrderProcess()->getOrderId();
			        ?>

                    <h3>Vollzugsmeldung der Rücksendung (Bst.Nr:  <?php echo "$order_id" ?>)</h3>
                    <h4>Bitte bestätigen Sie den Zustand der Rücksendung:
                    </h4><br>
                    <form method="post" action="">
                        <input style="color:black; background-color:lightgreen; " type="submit" value="OK" name="ok">
                        <input type="submit" value="Nicht OK" name="not_ok">
                    </form>

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
            <h3>Bitte füllen Sie das Formular aus, um den Erhalt der Rücksendung zu komplettieren.</h3>
            <form method="get" action="">
            <label for="code">Code: </label><br>
            <input type="text" id="code" name="code" value=""><br><br>
            <input type="submit" value="Versand aktivieren" name="submit">
            </form>
            <?php
        }
    }
}
