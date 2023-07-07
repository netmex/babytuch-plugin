<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;

use Inc\Controllers\LogisticsController;

/**
 * Class OrderProcessing
 *
 * Page that is used by the logistics provider to start / acknowledge the processing of an order.
 *
 * @package Inc\Pages
 */
class OrderProcessing
{
	public function register() {
        add_shortcode( 'order_processing', array($this, 'order_processing_form'));
    }

    function order_processing_form( $attributes ) {
        if (isset($_GET['code'])) {
            $processing_code = $_GET['code'];
            echo $processing_code;

	        try {
		        $controller = LogisticsController::create_from_processing_code( $processing_code );
		        $controller->start_processing_order();
                $controller->renderLogisticsOrderProcess();
                echo "<h3>Der Verpackungsprozess für die Bestellung wurde erfolgreich gestartet.</h3>";
		        echo "<h4>Sie können die Bestellung nun verpacken.</h4>";
	        } catch ( \Exception $e ) {
		        ?>
                <?php $controller = LogisticsController::create_from_processing_code( $processing_code ); ?>
                <?php $controller->renderLogisticsOrderProcess(); ?>
                <h3>Es gab ein Problem</h3>
                <h4><?php echo $e->getMessage(); ?></h4>
		        <?php
	        }

        } else {
            ?>
            <h3>Bitte füllen Sie das Formular aus, um die Bearbeitung der Bestellung zu aktivieren.</h3>
            <form method="get" action="">
            <label for="code">Code: </label><br>
            <input type="text" id="code" name="code" value=""><br><br>
            <input type="submit" value="Bearbeitung aktivieren" name="submit">
            </form>
            <?php
        }
    }

}
