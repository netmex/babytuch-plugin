<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;



use Inc\Api\Helpers;
use Inc\Models\BT_OrderProcess;
use Inc\Controllers\LogisticsController;
use ReturnController;

/**
 * Class Returns
 * @package Inc\Pages
 *
 * Page where the user can mark am order for return -> refund
 */
class Returns
{
	public function register() {
        add_shortcode( 'returns', array($this, 'return_form'));
    }

    
    function return_form( $attributes ) {

        // user filled out refund form
	    if(isset($_POST['return'])){

		    if(empty($_POST["products_to_send_back"])){
			    echo'<h2>Bitte wählen Sie mindestens 1 Produkt zum Zurücksenden.</h2>';
                return;
		    }

            if(empty($_POST["iban"])) {
			    echo'<h2>Bitte geben Sie Ihre IBAN-Nr. ein.</h2>';
                return;
		    }

            $return_code = $_GET['code'];
            $return_reason = $_POST["reason"];
            $iban = $_POST['iban'];
            $return_product_ids = $_POST["products_to_send_back"];

            try {
                $controller = LogisticsController::create_from_return_code( $return_code );
                $controller->start_refund($return_reason, $iban, $return_product_ids);
                ?>
                <h3>Deine Rücksendung wurde erfolgreich aktiviert.</h3>
                <h4>Sobald wir deine Rücksendung erhalten und kontrolliert haben werden wir dir die Kosten zurückerstatten.</h4>
                <?php
                return;

            } catch ( \Exception $e ) {
                echo '<h2>'.$e->getMessage().'</h2>';
                return;
            }

	    }

        // code is provided via url
        if (isset($_GET['code'])) {
            $return_code = $_GET['code'];

             try {
                 $controller = LogisticsController::create_from_return_code( $return_code );
                 $order_process = $controller->getOrderProcess();

                 if($order_process->isReturnActivated() && $order_process->isReturnReceivedAdminActivated() && !$order_process->isReturnReceivedActivated()) {
                    // logistics confirms return
                     $controller->start_return_control();
                     ?>
                     <h3>Die Rücksendung wurde erfolgreich entgegengenommen!</h3>
                     <h4>Du kannst die Bestellung nun überprüfen.</h4>
                     <?php

                 } else {
                     // customer wants refund
                     $this->render_refund_form($order_process);
                 }

             } catch ( \Exception $e ) {
                 ?>
                 <h3>Es gab ein Problem</h3>
                 <h4><?php echo $e->getMessage(); ?></h4>
                 <?php
             }
        }
    }


    public function render_refund_form(BT_OrderProcess $order_process) {
        $order = $order_process->getOrder();
	    $first_name = $order->get_billing_first_name();
	    $current_iban = Helpers::getCustomerIbanFromOrder($order) ?: '';

	    ?>

        <form method="post" action="">
            <h3>Hallo <?php echo "$first_name" ?></h3>
            <h4>Du kannst deine Rücksendung hier aktivieren. Bitte fülle dazu das folgende Formular aus.</h4>
            <label for="reason">Nenne uns bitte den Grund dafür:</label><br>
            <select id="reason" style="width:250px;" name="reason">
			    <?php
			    $returns_reasons = get_option('returns_reasons');
			    foreach($returns_reasons as $reason_pair){
				    $reason = $reason_pair["reason"];
				    echo"<option value='$reason'>$reason</option>";
			    }
			    ?>
            </select><br><br>
            <label for="iban">Bankkonto (IBAN) für die Rückerstattung:</label><br>
            <input id="iban" style='width:250px;' type='text' name='iban' value="<?php echo $current_iban;?>"><br><br>
            <p>Welches der Babytücher möchtest du zurücksenden?</p>

		    <?php

		    $all_items = $order->get_items();
		    foreach( $all_items as $product ) {
			    $amount = $product->get_quantity();
			    for($i=0;$i<$amount;$i++){
				    $product_id = $product['product_id'];
				    $data = $product->get_data();
				    $variation_id = $data["variation_id"];
				    $variation_obj = wc_get_product($variation_id);
				    $size = $variation_obj->get_attributes();
				    $product_obj = wc_get_product($product_id);

				    $attachment_ids = $product_obj->get_gallery_image_ids();
				    if($attachment_ids){
					    $img_url = wp_get_attachment_url($attachment_ids[0]);
				    } else {
				        $img_url = wp_get_attachment_url( $product_obj->get_image_id());
				    }
				    ?>
                    <img style="width: 150px; height:90px;" src="<?php echo $img_url; ?>"/>
                    <label for="product-<?php echo $variation_id; ?>" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                        <input id="product-<?php echo $variation_id; ?>" type='checkbox' name='products_to_send_back[]' value="<?php echo $variation_id; ?>" >
                        <span>Grösse: <?php echo $size["groesse"]; ?></span>
                    </label>
                    <br>
				    <?php
			    }
		    }

		    ?>

            <input type="submit" value="Zurücksenden" name="return"><br><br><br>
		    <?php if($order_process->isReplacementOrder()){ ?>
                <br><p>Da es sich um eine Umtausch Bestellung handelt, haben Sie
                    die Versandkosten von CHF <?php echo $order_process->getCostOfSending(); ?> zu begleichen.
                    Diese Kosten werden von der Rückerstattung abgezogen.</p><br>
            <?php } ?>
        </form>

	    <?php
    }

}

