<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;

use Inc\Controllers\LogisticsController;

/**
 * Class OrderSending
 * @package Inc\Pages
 *
 * Page that is used to mark the order as finished packing (and ready to be sent)
 */
class OrderSending
{
	public function register() {
        add_shortcode( 'order_sending', array($this, 'order_sending_form'));
    }

    function order_sending_form( $attributes ) {
        if (isset($_GET['code'])) {
            $sent_code = $_GET['code'];
            echo $sent_code;

	        try {
		        $controller = LogisticsController::create_from_sent_code( $sent_code );
		        $controller->finish_processing_order();
                $controller->renderLogisticsOrderProcess();
                echo "<h3>Der Verpackungsprozess für die Bestellung wurde erfolgreich abgeschlossen.</h3>";
		        echo "<h4>Die Bestellung kann nun verschickt werden.</h4>";
	        } catch ( \Exception $e ) {
		        ?>
                <?php $controller = LogisticsController::create_from_sent_code( $sent_code ); ?>
                <?php $controller->renderLogisticsOrderProcess(); ?>
                <h3>Es gab ein Problem</h3>
                <h4><?php echo $e->getMessage(); ?></h4>
		        <?php
	        }
        } else {
            ?>
            <h3>Bitte füllen Sie das Formular aus, um den Verpackungsprozess für die Bestellung abzuschliessen.</h3>
            <form method="get" action="">
                <label for="code">Code: </label><br>
                <input type="text" id="code" name="code" value=""><br><br>
                <input type="submit" value="Verpackungsprozess abschliessen" name="submit">
            </form>
            <?php
        }
    }
}
