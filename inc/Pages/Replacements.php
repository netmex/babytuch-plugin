<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;

use Inc\Api\Helpers;
use Inc\Models\BT_OrderProcess;
use WC_Order;
use WC_Product;

class Replacements
{
	public function register() {
        add_shortcode( 'replacements', array($this, 'replacements_form'));
    }

    
    function replacements_form( $attributes ) {
    
         if (isset($_GET['code'])) {
            $return_code = $_GET['code'];
            //echo $return_code;
            $order_id = $this->get_order_id($return_code);
            if($order_id!='no_order_error'){
                if($order_id == 'activated'){
                    echo ''?>
                    <html>
                    <body>
                    <h3>Deine Bestellung wurde erfolgreich zum Umtausch aktiviert.</h3>
                    <h4>Sobald wir deine Rücksendung erhalten haben werden wir deine ausgewählten Ersatzprodukte versenden.</h4>
                    </body>
                    </html>
                    <?php
                }elseif($order_id == 'deadline_expired'){
                    echo ''?>
                    <html>
                    <body>
                    <h3>Frist abgelaufen</h3>
                    <h4>Diese Bestellung kann nicht mehr umgetauscht werden.</h4>
                    </body>
                    </html>
                    <?php
                }else{
                    $order = wc_get_order($order_id);
                    $first_name = $order->get_billing_first_name();
                    $last_name = $order->get_billing_last_name();
                    global $wpdb;
                    $order_details = $wpdb->get_results( 
                        $wpdb->prepare( "
                            SELECT * FROM babytuch_order_process 
                            WHERE order_id  = %s", 
                            $order_id 
                        ) 
                    );
                    $order_details_json = json_decode(json_encode($order_details), true);
                    if($order_details_json[0]["is_replacement_order"]==0 and 
                        $order_details_json[0]["replace_activated"]!='1'){
                            echo ''?>
                            <html>
                            <body>
                            <form method="post" action="">
                            <h3>Hallo <?php echo "$first_name" ?></h3>
                            <h4>Du kannst deine Bestellung umtauschen. 
                                Bitte wähle die Produkte welche du zurücksenden und umtauschen möchtest.</h4>
                            
                            <br>
                            
                            <p>Nenne uns bitte den Grund für deinen Umtausch</p>
                            <select style="width:250px;" name="reason">
                            <?php
                            $returns_reasons = get_option('returns_reasons');
                            foreach($returns_reasons as $reason_pair){
                                $reason = $reason_pair["reason"];
                                echo"
                                <option value='$reason'>$reason</option>";
                            }
                            ?>
                            </select><br><br>
                            
                            <?php
                            /*global $wpdb;
                            $table_name = $wpdb->prefix . 'wc_order_product_lookup';
                            $user_details = $wpdb->get_results( 
                                $wpdb->prepare( "
                                    SELECT customer_id FROM $table_name
                                    WHERE order_id  = %s", 
                                    $order_id 
                                ) 
                            );
                            $user_details_json = json_decode(json_encode($user_details), true);
                            $customer_id = $user_details_json[0]["customer_id"];
                            $table_name2 = $wpdb->prefix . 'wc_customer_lookup';
                            $user_details2 = $wpdb->get_results( 
                                $wpdb->prepare( "
                                    SELECT user_id FROM $table_name2
                                    WHERE customer_id  = %s", 
                                    $customer_id 
                                ) 
                            );
                            $user_details2_json = json_decode(json_encode($user_details2), true);
                            $client_id = $user_details2_json[0]["user_id"];
                            if(empty($client_id)){
                                $order = wc_get_order($order_id);
                                $email = $order->get_billing_email();
                                $user_details2 = $wpdb->get_results( 
                                $wpdb->prepare( "
                                    SELECT user_id FROM $table_name2
                                    WHERE email  = %s", 
                                    $email 
                                ) 
                                );
                                $user_details2_json = json_decode(json_encode($user_details2), true);
                                $client_id = $user_details2_json[0]["user_id"];
                                if(empty($client_id) and count($user_details2_json)>1){
                                    $client_id = $user_details2_json[1]["user_id"];
                                }
                            }
                            
                            $current_iban = get_user_meta($client_id, 'iban_num');
                            if(empty($current_iban)){
                                echo"
                                <label>IBAN-Nr.:</label><br>
                                <input style='width:250px;' type='text' name='iban' value=''><br><br>";
                            }else{
                              $temp = $current_iban[0];
                              echo"
                              <label>IBAN-Nr.:</label><br>
                              <input style='width:250px;' type='text' name='iban' value=$temp><br><br>";
                            }*/
                            
                            
                            $num_items = $order->get_item_count();
                            $all_items = $order->get_items();
                            echo "<p>Welches der Babytücher möchtest du umtauschen?</p>";
                            $j=0;
                            foreach( $all_items as $product ) {
                                $amount = $product->get_quantity();
                                for($i=0;$i<$amount;$i++){
                                    $product_id = $product['product_id']; 
                                    $product_obj = wc_get_product($product_id);
                                    $product_id = $product_obj->get_id();

                                    $data = $product->get_data();
                                    $variation_id = $data["variation_id"];
                                    $variation_obj = wc_get_product($variation_id);
                                    $size = $variation_obj->get_attributes();
                                    
                                    $attachment_ids = $product_obj->get_gallery_image_ids();
                                    if($attachment_ids){
                                       $img_url = wp_get_attachment_url($attachment_ids[0]);
                                    }
                                    //$size = $product_obj->get_default_attributes();
                                    ?>
                                    <img style="width: 150px; height:90px;" src="<?php if($attachment_ids){echo $img_url;}else{echo wp_get_attachment_url( $product_obj->get_image_id() );} ?>"/>
                                    <p><b>Grösse: <?php echo $size["groesse"]?></b>
                                    <?php echo"
                                    <input type='checkbox' name='products_to_send_back[]' value=$variation_id.$j >
                                    <label for='products'>Ersatzprodukt wählen: </label>
                                    <select style='width:250px;' name='products_$j' id='products'>";
                                    $ps = wc_get_products( array(
                                            'numberposts' => -1, // all products
                                            'status' => 'publish' // only published products
                                    ));
                                    foreach($ps as $pr){
                                        if($pr->is_type( 'variable' )){
                                            $product_single = wc_get_product($pr);
                                            $name = $product_single->get_name();
                                            $id = $product_single->get_id();
                                            echo "<option value=$id>$name</option>";
                                        }
                                    }
                                    echo '</select>';
                                    
                                    //SIZE
                                    echo" Grösse:
                                    <select style='width:65px;' name='products2_$j'>";
                                    $ps = wc_get_products( array('numberposts' => -1) );
                                    $pr = $ps[3];
                                        $product_single = wc_get_product($pr);
                                        $name = $product_single->get_name();
                                        $children   = $product_single->get_children();
                                        foreach($children as $child){
                                            $child_product = wc_get_product($child);
                                            $child_attr = $child_product->get_attributes();
                                            $child_id = $child_product->get_id();
                                            $out_of_stock = $child_product->get_stock_quantity();
                                            $child_size = $child_attr["groesse"];
                                            echo "<option style='font-size:25px;' value=$child_size>$child_size</option>";
                                            
                                        }
                                    echo '</select><br>';
                                    ?>
                                    </p>
                                    <br>
                                    <?php
                                    $j++;
                                }
                            }  
                            ?>
                            
                            <input type="submit" value="Umtauschen" name="replace"><br><br><br>
                            </form>
                            </body>
                            </html>
                            <?php                      
                        
                    }else{
                      echo '<p style="color:red;">Diese Bestellung wurde bereits umgetauscht.</p>';
                    }
                    
                    if(isset($_POST['replace'])){
                        //$num = count($_POST["products_to_send_back"]);
                        if(empty($_POST["products_to_send_back"])){
                            echo'<h2>Bitte wählen Sie mindestens 1 Produkt zum Umtauschen.</h2>';
                        }/*elseif(empty($_POST["iban"])){
                            echo'<h2>Bitte geben Sie Ihre IBAN-Nr. ein.</h2>';
                        }*/else{
                            $send_back = $_POST["products_to_send_back"];
                            $stock_test_res = false;
                            $out_of_stock_check = array();
                            $j=0;
                            $arr2=array();

                                for($i=0; $i<$num_items;$i++){
                                    $product_id = $_POST["products_$i"];
                                    $product_size = $_POST["products2_$i"];
                                    $t = $_POST["products_to_send_back"];
                                    foreach($t as $tt){         
                                        if($i==(int)substr($tt, -1)){
                                            $product = wc_get_product( $product_id ); 
                                            $childs = $product->get_children();
                                        
                                            foreach($childs as $chld){
                                                $chld_product = wc_get_product($chld);
                                                $chld_attr = $chld_product->get_attributes();
                                                $chld_size = $chld_attr["groesse"];

                                                if($chld_size==$product_size){
                                                    $prod_id = $chld;

                                                }
                                            }
                                            array_push($arr2,$prod_id);
                                        }
                                    }
                                }
                            

                            foreach($arr2 as $ar){
                                $product_obj = wc_get_product($ar);
                                $stock_id = $product_obj->get_id();
                                $out_of_stock = $product_obj->get_stock_quantity();
                            
                                $subarray = array(
                                    'stock_id' => $stock_id,
                                    'stock_amount' => $out_of_stock-1
                                );
                                foreach($out_of_stock_check as $check_pair){
                                if(in_array($stock_id, $check_pair)){
                                    $temp = (int)$check_pair["stock_amount"]-1;
                                    $check_pair["stock_amount"] = $temp;
                                    array_push($out_of_stock_check, $check_pair);
                                    
                                }
                                }
                                array_push($out_of_stock_check, $subarray);  
                                $j++;        
                                
                            }
                            //var_dump($out_of_stock_check);
                            
                                                    
                            foreach($out_of_stock_check as $check_pair){
                                if((int)$check_pair["stock_amount"] < 0){
                                $stock_test_res = true;
                                }
                            }
                            if($stock_test_res){
                                echo'<h2>Einige Ihrer Ersatzprodukte sind nicht mehr an Lager. Bitte wählen Sie eine andere Kombination.</h2>';
                            }
                            else{
                        
                               $cost=0;
                                $return_products = '';
                                $return_products_arr = array();
                                foreach( $send_back as $product) {
                                    $product_obj = wc_get_product( substr($product, 0, -2));
                                    $product_id = $product_obj->get_id();
                                    $price = (int)$product_obj->get_price();
                                    $return_products .= $product_id.',';
                                    array_push($return_products_arr, $product_id);
                                    global $wpdb;
                                    $table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
                                    $res = $wpdb->get_results( 
                                    "SELECT * FROM $table_name2
                                        WHERE meta_key  = 'rate_percent'"
                                        
                                    );
                                    $res_json = json_decode(json_encode($res), true);
                                    $tax = (float)end($res_json)["meta_value"];
                            
                                    $cost = $cost + ($price + round(($price*(0.01*$tax)),2));
                                }


                                $order_process = BT_OrderProcess::load_by_order_id($order_id);
                                $order_process->setTotalPrice($cost);
                                $order_process->setReturnProducts($return_products);
                                $order_process->save();

                                do_action('babytuch_return_start', $order_id, $return_products_arr);

                                $returns_reasons = get_option('returns_reasons');
                                $new_returns_array = array();
                                $option = $_POST["reason"];
                                foreach($returns_reasons as $reason_pair){
                                    $amount = (int)$reason_pair["amount"];
                                    $reason = $reason_pair["reason"];
                                    if($reason==$option){
                                        $arr = array(
                                        'reason'  => $reason,
                                        'amount' => $amount+1,
                                        );
                                        array_push($new_returns_array, $arr);
                                    }else{
                                        $arr = array(
                                        'reason'  => $reason,
                                        'amount' => $amount,
                                    );
                                    array_push($new_returns_array, $arr);
                                    }
                                }
                                update_option('returns_reasons', $new_returns_array);

                                /*$iban = $_POST["iban"];
                                update_user_meta($client_id, 'iban_num', $iban);*/


                               //echo'<h2>Ihr Umtausch wurde erfolgreich aktiviert.</h2>';

                                //add_action( 'woocommerce_email', 'unhook_those_pesky_emails' );

                                global $woocommerce;

                                $address = array(
                                    'first_name' => $order->get_billing_first_name(),
                                    'last_name'  => $order->get_billing_last_name(),
                                    'company'    => $order->get_billing_company(),
                                    'email'      => $order->get_billing_email(),
                                    'phone'      => $order->get_billing_phone(),
                                    'address_1'  => $order->get_billing_address_1(),
                                    'address_2'  => $order->get_billing_address_2(),
                                    'city'       => $order->get_billing_city(),
                                    'state'      => $order->get_billing_state(),
                                    'postcode'   => $order->get_billing_postcode(),
                                    'country'    => $order->get_billing_country()
                                );

                                // Now we create the order
                                $new_order = wc_create_order();

                                /*for($i=0; $i<$num_items;$i++){
                                    $product_id = $_POST["products_$i"];
                                    $t = $_POST["products_to_send_back"];
                                    foreach($t as $tt){
                                        if($i==(int)substr($tt, -1)){
                                            $product = wc_get_product( $product_id ); 
                                            // The add_product() function below is located in /plugins/woocommerce/includes/abstracts/abstract_wc_order.php
                                            $new_order->add_product( $product, 1);
                                        }
                                    }
                                }*/
                                foreach($arr2 as $product_id){
                                    $p = wc_get_product($product_id);
                                    $new_order->add_product( $p, 1);
                                }
                                
                                $new_order->set_address( $address, 'billing' );
                                //
                                $new_order->calculate_totals();
                                $new_order->set_total(0);
                                $new_order->update_status("on-hold", 'Umtausch Bestellung', TRUE);  

                                $id = $new_order->get_id();
                                $order_data = $new_order->get_data();
                                $order_status = $order_data["status"];
                                $total_price = (float)($order_data["total"]-$order_data["shipping_total"]);
                                $shipment_cost = (float)$order->get_shipping_total();
                                $cost_of_sending_tax = 3*($shipment_cost + round(($shipment_cost*(0.01*$tax)),2));
                                global $wpdb;
                                $wpdb->insert( 
                                    'babytuch_order_process', 
                                    array( 
                                        'date_order_created' => current_time( 'mysql' ), 
                                        'order_id' => $id, 
                                        'order_email' => $order->get_billing_email(),
                                        'order_status' => $order_status,
                                        'total_price' => $total_price, 
                                        'processing_code' => Helpers::generateUniqueCode($id),
                                        'processing_activated' => false,
                                        'sent_code' => Helpers::generateUniqueCode($id),
                                        'sent_activated' => false,
                                        'return_code' => Helpers::generateUniqueCode($id),
                                        'return_activated' => false,
                                        'return_received_code' => Helpers::generateUniqueCode($id),
                                        'return_received_activated' => false,
                                        'return_received_admin_code' => Helpers::generateUniqueCode($id),
                                        'return_received_admin_activated' => false,
                                        'refunded' => false,
                                        'is_replacement_order' => true,
                                        'is_replacement_order_of' => $order_id,
                                        'cost_of_sending' => $cost_of_sending_tax,
                                    )
                                );

                                $value = $wpdb->query( 
                                    $wpdb->prepare( "
                                        UPDATE babytuch_order_process SET replace_activated = true,
                                        replacement_order = %s
                                        WHERE order_id = %s", 
                                        $id, $order_id
                                    ) 
                                );

                                
                                /*$total_returns = get_option('total_returns');
                                if($total_returns){
                                    update_option('total_returns', (int)get_option('total_returns')+1);
                                }else{
                                    update_option('total_returns', 1);
                                }*/
                                
                                $value = $wpdb->query( 
                                    $wpdb->prepare( "
                                        UPDATE babytuch_order_process SET return_activated = true
                                        WHERE order_id = %s", 
                                        $order_id
                                    ) 
                                );
                                $value = $wpdb->query( 
                                    $wpdb->prepare( "
                                        UPDATE babytuch_order_process SET return_reason = %s
                                        WHERE order_id = %s", 
                                        $option, $order_id
                                    ) 
                                );
                                $order->update_status('returning');
                                
                                /*if($value){
                                $subject = "Umtausch erfolgreich";
                                $message = "Guten Tag ". $first_name ." ". $last_name .", 
                                    Sie haben Ihre Bestellung ". $order_id ." erfolgreich umgetauscht. 
                                    Die neue Bestellnummer lautet: ". $id .".";
                                wp_mail( 'davebasler@hotmail.com', $subject, $message );
                                }*/
                                echo'<h2>Ihre Bestellung wurde erfolgreich zum Umtausch aktiviert.</h2>';
                                header("Refresh:0");
                            }
                        }
                        
                    }
                }
            }else{
                echo ''?>
                <html>
                <body>
                <h3>Es gab ein Problem</h3>
                <h4>Ihre Bestellung konnte nicht gefunden werden.
                    Bitte prüfen Sie, ob Ihre Bestellung umtauschbar ist und füllen Sie sonst das
                    Formular aus.</h4>
                </body>
                </html>
                <?php
            }
        } else {
            echo ''?>
            <html>
            <body>

            <h3>Bitte füllen Sie das Formular aus, um ihre Rücksendung zu aktivieren.</h3>

            <form method="post" action="">
            <label for="fname">E-Mail: </label><br>
            <input type="text" id="fname" name="email" value=""><br>
            <label for="lname">Bestell-Nummer: </label><br>
            <input type="text" id="lname" name="order_id" value=""><br><br>
            <input type="submit" value="Senden" name="submit">
            </form> 
            </body>
            </html>
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
        }
    }

    public function order_valid($order_data_json, $order_id){
        $is_valid = false;
        foreach($order_data_json as $order){
            if((int)$order["order_id"] == (int)$order_id){
                $is_valid = true;
                break;
            }
        }
        return $is_valid;
    }

    public function get_return_code($order_id){
        //.....check if return_activated == false
        global $wpdb;
        $code = $wpdb->get_results( 
            $wpdb->prepare( "
                SELECT return_received_admin_code FROM babytuch_order_process 
                WHERE order_id = %s", 
                $order_id 
            ) 
        );
        $code_json = json_decode(json_encode($code), true);
        return $code_json[0]["return_received_admin_code"];
    }

    public function get_order_id($return_code){
        //.....check if return_activated == false
        global $wpdb;
        $order_id = $wpdb->get_results( 
            $wpdb->prepare( "
                SELECT * FROM babytuch_order_process 
                WHERE return_code = %s", 
                $return_code 
            ) 
        );
        $order_id_json = json_decode(json_encode($order_id), true);
        $return_days_limit = get_option('return_days_limit');
        if(!$return_days_limit){
            $return_days_limit=35;
        }
        if(empty($order_id) or $order_id_json[0]["is_replacement_order"]==1){
            return 'no_order_error';
        }elseif($order_id_json[0]["return_activated"]=='1'){
            return 'activated';
        }
        elseif('completed'!=$order_id_json[0]["order_status"]){
            return 'no_order_error';
        }
        elseif(date('Y-m-d',strtotime($order_id_json[0]["date_delivered"] . " + $return_days_limit days"))<date('Y-m-d',time())){
            return 'deadline_expired';
        }
        else{
            return $order_id_json[0]["order_id"]; 
        }
    }

    public function unhook_those_pesky_emails($email_class){
        remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', 
            array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    }

}
