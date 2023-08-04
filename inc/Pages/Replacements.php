<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;

use Exception;
use Inc\Controllers\LogisticsController;
use Inc\Models\BT_OrderProcess;


class Replacements {
	public function register() {
        add_shortcode( 'replacements', array($this, 'replacements_form'));
    }

    function replacements_form( $attributes ) {

        // user filled out replacement form
        if(isset($_POST['replace'])){

            $return_code = $_GET['code'];
            $return_reason = $_POST["reason"];
            $replaced_product_ids = $_POST["products_to_send_back"] ?: array();
            $replacement_ids = $_POST["replacement_ids"];
            $replacement_sizes = $_POST["replacement_sizes"];

            try {
                $controller = LogisticsController::create_from_return_code($return_code);
                $controller->start_replacement($return_reason, $replaced_product_ids, $replacement_ids, $replacement_sizes);
                ?>
                <h3>Deine Bestellung wurde erfolgreich zum Umtausch aktiviert.</h3>
                <h4>Sobald wir deine Rücksendung erhalten haben werden wir deine ausgewählten Ersatzprodukte versenden.</h4>
                <?php
                return;

            } catch (Exception $e) {
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
                if($order_process->isReturnActivated()) {
                    // TODO: maybe start return control similar to returns?
                    ?>
                    <h3>Deine Bestellung wurde erfolgreich zum Umtausch aktiviert.</h3>
                    <h4>Sobald wir deine Rücksendung erhalten haben werden wir deine ausgewählten Ersatzprodukte versenden.</h4>
                    <?php
                } else {

                    if($order_process->isReplacementOrder()) {
                        throw new Exception("Eine Ersatzbestellung kann nicht noch einmal umgetauscht werden.");
                    }
                    if($order_process->isReplaceActivated()) {
                        throw new Exception("Diese Bestellung wurde bereits zum Umtausch markiert.");
                    }

                    // customer wants replacement
                    $this->render_replacement_form($order_process);
                }
            } catch ( \Exception $e ) {
                ?>
                <h3>Es gab ein Problem</h3>
                <h4><?php echo $e->getMessage(); ?></h4>
                <?php
            }

        } /* else {
            echo ''?>

            <h3>Bitte füllen Sie das Formular aus, um ihre Rücksendung zu aktivieren.</h3>

            <form method="post" action="">
            <label for="fname">E-Mail: </label><br>
            <input type="text" id="fname" name="email" value=""><br>
            <label for="lname">Bestell-Nummer: </label><br>
            <input type="text" id="lname" name="order_id" value=""><br><br>
            <input type="submit" value="Senden" name="submit">
            </form> 

            <?php
        }
       
        if(isset($_POST['submit'])){
            global $wpdb;
            $email = $_POST['email'];
            $order_id = $_POST['order_id'];

            $order_data = $wpdb->get_results( 
                $wpdb->prepare( "
                    SELECT * FROM babytuch_order_process 
                    WHERE order_email = %s", 
                    $email 
                ) 
            );
            $order_data_json = json_decode(json_encode($order_data), true);

            if($order_data_json == null){
                echo "Ihre E-Mail Adresse (" . $email . ") ist nicht gültig.";
                die();
            }
            if($this->order_valid($order_data_json, $order_id)){
                $return_code = $this->get_return_code($order_id);
                echo ''?>
                <html>
                <body>
                <h4 style="color:green">Vielen Dank für Ihre Angaben. 
                    Ihr Rücksende-Code lautet: <?php echo $return_code ?> </h4>
                </body>
                </html>
                <?php
                //.....email & make return_activated true in DB
                die();
            }
            echo "Ihre Bestell-Nummer (" . $order_id . ") ist nicht gültig.";
            die();
        }*/
    }

    public function render_replacement_form(BT_OrderProcess $order_process) {
        $order = $order_process->getOrder();
        $first_name = $order->get_billing_first_name();

        ?>
            <form method="post" action="">
                <h3>Hallo <?php echo "$first_name" ?></h3>
                <h4>Du kannst deine Bestellung umtauschen. Bitte wähle die Produkte, welche du zurücksenden und umtauschen möchtest.</h4>
                <label for="reason" class="mt4">Nenne uns bitte den Grund dafür:</label>
                <select style="width:250px;" name="reason" id="reason">
                    <?php
                    $returns_reasons = get_option('returns_reasons');
                    foreach($returns_reasons as $reason_pair){
                        $reason = $reason_pair["reason"];
                        echo"<option value='$reason'>$reason</option>";
                    }
                    ?>
                </select><br><br>

                <?php

                $all_items = $order->get_items();
                echo "<div class='f5 mb4 b'>Welches der Babytücher möchtest du umtauschen?</div>";
                echo "<hr class='o-50'/>";
                $j=0;
                foreach( $all_items as $product ) {
                    $amount = $product->get_quantity();
                    for($i=0;$i<$amount;$i++){
                        $product_id = $product['product_id'];
                        $product_obj = wc_get_product($product_id);
                        $data = $product->get_data();
                        $variation_id = $data["variation_id"];
                        $variation_obj = wc_get_product($variation_id);
                        $size = $variation_obj->get_attributes();


                        $attachment_ids = $product_obj->get_gallery_image_ids();
                        if($attachment_ids){
                            $img_url = wp_get_attachment_url($attachment_ids[0]);
                        } else {
                            $img_url = wp_get_attachment_url( $product_obj->get_image_id());
                        }
                        ?>
                            <div class="row mb3">
                                <div class="large-4 columns small-9">
                                    <label for="replaced_product_<?php echo $variation_id; ?>">
                                        <input type='checkbox' id="replaced_product_<?php echo $variation_id; ?>" name='products_to_send_back[]' value='<?php echo $variation_id; ?>'>
                                        <?php echo $product_obj->get_name(); ?> - Grösse: <?php echo $size["groesse"]?>
                                    </label>
                                </div>
                                <div class="large-2 columns small-3 ">
                                    <img class="w4 h3" src="<?php if($attachment_ids){echo $img_url;}else{echo wp_get_attachment_url( $product_obj->get_image_id() );} ?>"/>
                                </div>
                                <div class="large-3 columns small-9">

                                    <?php echo"
                                    
                                    <label for='replacement_ids'>Ersatzprodukt wählen:</label>
                                    <select style='width:250px;' name='replacement_ids[]' id='replacement_ids'>";
                                    $ps = wc_get_products( array(
                                        'numberposts' => -1, // all products
                                        'status' => 'publish' // only published products
                                    ));
                                    foreach($ps as $pr){
                                        if($pr->is_type( 'variable' )){
                                            $product_single = wc_get_product($pr);
                                            $name = $product_single->get_name();
                                            $id = $product_single->get_id();
                                            $selected = $product_single->get_id() === $product->get_product_id() ? "selected" : "";
                                            echo "<option value='$id' $selected>$name</option>";
                                        }
                                    }
                                    echo '</select>';
                                    ?>
                                </div>
                                <div class="large-3 columns small-3">
                                    <?php

                                    //SIZE
                                    echo"<label for='replacement_sizes'>Grösse:</label>
                                    <select style='width:65px;' name='replacement_sizes[]'>";
                                    $ps = wc_get_products( array('numberposts' => -1) );
                                    $pr = $ps[3];
                                    $product_single = wc_get_product($pr);
                                    $children   = $product_single->get_children();
                                    foreach($children as $child){
                                        $child_product = wc_get_product($child);
                                        $child_attr = $child_product->get_attributes();
                                        $child_size = $child_attr["groesse"];
                                        $selected = $size["groesse"] === $child_size ? "selected" : "";
                                        echo "<option style='font-size:25px;' value='$child_size' $selected>$child_size</option>";

                                    }
                                    echo '</select><br>';

                                    ?>
                                </div>
                            <hr class="pt4 o-50"/>
                            </div>

                        <?php
                        $j++;
                    }
                }
                ?>

                <input type="submit" value="Umtauschen" name="replace"><br><br><br>
            </form>
            <?php

    }
}
