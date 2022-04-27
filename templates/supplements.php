<div class="wrap">
<h1>Babytuch Plugin</h1>
<p>Plugin ist aktiv.</p>
<?php settings_errors();
global $wpdb;
$res = $wpdb->get_results("
        SELECT * FROM babytuch_inventory 
        WHERE item_name = 'packagings' OR item_name = 'labels' 
        OR item_name = 'supplements' OR item_name = 'packagings_large'"
    );
$res_json = json_decode(json_encode($res), true);




?>

<form method="post" action="">
<h3>Verpackungsmaterial Lagermanagement</h3>
<br>
<table style="border: 1px solid black;">
    <tr>
        <th style="width:100px;">Bestand</th>
        <th colspan="12">Beilagen</th>
    </tr>
    <tr>
        <th>&nbsp; <!-- EMPTY --></th>
        <?php
            foreach($res_json as $supplement){
                $name = $supplement["item_name"];
                echo " <th style='width:100px;'>$name</th>";
            }
            echo"</tr>";
            
            echo"<tr>";
            echo"<th style='height:50px;'></th>";
            foreach($res_json as $supplement){
                $stock = $supplement["amount"];
                $color = check_status($supplement);
                echo " <td style='text-align: center; background-color:$color;'>$stock</td>";
                
            }
            echo"</tr>";
            
            
        ?>
</table>
<br><br>
<table style="border: 1px solid black;">
    <tr>
        <th style="width:100px;">Reserve</th>
        <th colspan="12">Beilagen</th>
    </tr>
    <tr>
        <th>&nbsp; <!-- EMPTY --></th>
        <?php
            foreach($res_json as $supplement){
                $name = $supplement["item_name"];
                echo " <th style='width:100px;'>$name</th>";
            }
            echo"</tr>";
            
            echo"<tr>";
            echo"<th style='height:50px;'></th>";
            foreach($res_json as $supplement){
                $stock = $supplement["new_order_limit"];
                $name = $supplement["item_name"];
                echo ' <td style="text-align: center;">
                <input type="text" style="width: 40px" class="regular-text"
                name="'.$name.'_new_order_limit2"
                value="'.$stock.'" placeholder="'.$stock.'">
                </td>';
            }
            echo"</tr>";
            
            
        ?>
</table>
<br>
<input type="submit" value="Reserven speichern" name="save_limits">
<br><br>
<h4>Check Einstellungen (gleiche wie Produkte)</h4>
<?php
$products_last_check = get_option('products_last_check');
echo "<p><input type='date' style='width: 150px' class='regular-text'
name='products_last_check' value=$products_last_check> Letzter Check</p>";
echo"<br>";
$products_last_reorder = get_option('products_last_reorder');
echo "<p><input type='date' style='width: 150px' class='regular-text'
name='products_last_reorder' value=$products_last_reorder> Letzte Bestellung</p>";
echo"<br>";
$products_next_reorder = get_option('products_next_reorder');
echo "<p><input type='date' style='width: 150px' class='regular-text'
name='products_next_reorder' value=$products_next_reorder> Nächste Bestellung</p>";
echo"<br>";
$products_interval = get_option('products_interval');
echo "<p><input type='text' style='width: 50px' class='regular-text'
name='products_interval' value=$products_interval> Interval (in Tage)</p>";
echo"<br>";
?>
 <input type="submit" value="Speichern" name="submit_common_settings"> Speichert alle Felder.

 </form>
 <br><br>
<form method="post" action="options.php">
<h4>Aktuelle Mengen des Verpackungsmaterials</h4>
<?php
    //settings_fields( 'babytuch_options_group' );
    //do_settings_sections( 'babytuch_plugin' );
    //submit_button( );

$amount_normal = $res_json[0]["amount"];
echo "<p><input type='text' style='width: 100px' class='regular-text'
name='amount_normal' value=$amount_normal> Verpackungen (Normal)</p>";
echo"<br>";
$amount_normal_large = $res_json[3]["amount"];
echo "<p><input type='text' style='width: 100px' class='regular-text'
name='amount_normal_large' value=$amount_normal_large> Verpackungen (Gross)</p>";
echo"<br>";
$amount_labels = $res_json[1]["amount"];
echo "<p><input type='text' style='width: 100px' class='regular-text'
name='amount_labels' value=$amount_labels> Etiketten</p>";
echo"<br>";
$amount_supplements = $res_json[2]["amount"];
echo "<p><input type='text' style='width: 100px' class='regular-text'
name='amount_supplements' value=$amount_supplements> Beilagen</p>";
echo"<br>";

?>
<input type="submit" value="Speichern" name="submit_amounts"> Speichert alle Felder.

</body>
</html>
</form>
</div>
<?php

if(isset($_POST['submit_amounts'])){
    global $wpdb;
   
    $amount_normal = $_POST["amount_normal"];
    $amount_normal_large = $_POST["amount_normal_large"];
    $amount_labels = $_POST["amount_labels"];
    $amount_supplements = $_POST["amount_supplements"];
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s
            WHERE item_name = 'packagings'", 
            $amount_normal
        ) 
    ); 
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s
            WHERE item_name = 'packagings_large'", 
            $amount_normal_large
        ) 
    );   
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s
            WHERE item_name = 'labels'", 
            $amount_labels
        ) 
    );   
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s
            WHERE item_name = 'supplements'", 
            $amount_supplements
        ) 
    );               
    header("Refresh:0");
}

if(isset($_POST['save_limits'])){
    global $wpdb;
    foreach($res_json as $supplement){
        $name = $supplement["item_name"];
        $new_order_limit = $_POST[$name."_new_order_limit2"];
        $value = $wpdb->query( 
            $wpdb->prepare( "
                UPDATE babytuch_inventory SET new_order_limit = %s
                WHERE item_name = %s", 
                $new_order_limit, $name
            ) 
        );             
    }
    header("Refresh:0");
}

function check_status($supplement){
    $decrease_rates = array();
    for($j=52; $j>1; $j--){
        $stock_old = $supplement["stock_$j"];
        $k=$j-1;
        $stock_new = $supplement["stock_$k"];
        if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
            $decrease_rate = (int)$stock_old - (int)$stock_new;  
            array_push($decrease_rates, $decrease_rate);
        }
        if($j==2){
            $stock_old = $supplement["stock_1"];
            $stock_new = $supplement["amount"];
            if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
                $decrease_rate = (int)$stock_old - (int)$stock_new;  
                array_push($decrease_rates, $decrease_rate);
            }
        }
    }
    if(count($decrease_rates)!=0){
        $average = array_sum($decrease_rates)/count($decrease_rates);
    }else{
        $average = 0;
    }
    var_dump($average);
    $limit = $supplement["new_order_limit"];
    $current_amount = $supplement["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', $last_check, $next_order, false);
    /**if($current_amount-($num_weeks*2*$average)>=0){
        return 'lightgreen';
    }**/
    if((int)$current_amount<(int)$limit or 
        (int)$current_amount-($num_weeks*$average)<(int)$limit){
        return 'LightCoral';
    }
    if((int)$current_amount-(int)round($average)<=(int)$limit){
        return 'SandyBrown';
    }
    return 'lightgreen';
}

if(isset($_POST['submit_common_settings'])){
    $products_last_check = $_POST['products_last_check'];
    update_option('products_last_check', $products_last_check);
    $products_last_reorder = $_POST['products_last_reorder'];
    update_option('products_last_reorder', $products_last_reorder);
    $products_next_reorder = $_POST['products_next_reorder'];
    update_option('products_next_reorder', $products_next_reorder);
    $products_interval = $_POST['products_interval'];
    update_option('products_interval', $products_interval);

    header("Refresh:0");
}
    

    
    //Test
    /*
    echo('<h1>TESTS</h1>');
    global $wpdb;
    echo("INVENTORY: ");
    var_dump($wpdb->get_var("SELECT amount FROM wp_inventory WHERE item_name='packagings'"));
    ?><br><?php
    echo("OPTIONS: ");
    var_dump($wpdb->get_var("SELECT option_value FROM wp_options WHERE option_name='packaging_amount'"));
    ?><br><?php
    var_dump($package_amount = $wpdb->get_var("SELECT option_value FROM wp_options WHERE option_id=3093"));
    var_dump($package_amount_int = (int)$package_amount);
    ?><br><?php
    $order_id = 73;
    var_dump(realpath($_SERVER["DOCUMENT_ROOT"]) . "/wp_test_ecom/wp-content/plugins/
    babytuch-plugin/shipping-labels/shiping_label_$order_id.pdf");
    ?><br><?php
    $email = 6;
    $test = $wpdb->get_results( 
        $wpdb->prepare( "
            SELECT * FROM wp_wc_order_product_lookup
            WHERE customer_id = %s", 
            $email
        ) );
    $array = json_decode(json_encode($test), true);
    foreach($array as $arr){
        var_dump((int)$arr["order_id"]);
    }
    var_dump($array[0]["order_id"]);
    ?><br><?php
    $res = $wpdb->get_results("
			SELECT * FROM wp_inventory 
			WHERE item_name != 'packagings' AND item_name != 'labels'"
            );
    $res_json = json_decode(json_encode($res), true);
    var_dump(count($res_json));
    ?><br><?php


    global $wpdb;
    $customer_ids = $wpdb->get_col("SELECT DISTINCT meta_value  FROM $wpdb->postmeta
        WHERE meta_key = '_customer_user' AND meta_value > 0");
    $currency = ' (' . get_option('woocommerce_currency') . ')';
    if (sizeof($customer_ids) > 0) : ?>
    <table style="width:100%">
        <thead>
            <tr>
                <th data-sort="string"><?php _e("Name", "woocommerce"); ?></th>
                <th data-sort="string"><?php _e("Email", "woocommerce"); ?></th>
                <th data-sort="float"><?php _e("Total spent", "woocommerce"); echo $currency; ?></th>
                <th data-sort="int"><?php _e("Orders placed", "woocommerce"); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($customer_ids as $customer_id) :
            $customer = new WP_User($customer_id); ?>
            <tr>
                <td><?php echo $customer->display_name; ?></td>
                <td><?php echo $customer->user_email; ?></td>
                <td><?php echo wc_get_customer_total_spent($customer_id); ?></td>
                <td><?php echo wc_get_customer_order_count($customer_id); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif;

    ?><br><?php
    $product_id = 46; // ID of parent product
    $product    = wc_get_product( $product_id );
    $children   = $product->get_children();
    var_dump($children);
    if($product->is_type( 'variable' )){
        foreach($children as $child){
            $child_product = wc_get_product($child);
            var_dump($child_product->get_stock_quantity());
        }
    }
    $orders = wc_get_products( array('numberposts' => -1) );
    var_dump($orders[0]->get_stock_quantity());
    ?><br>
    <img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=http%3A%2F%2Flocalhost/wp_test_ecom/ruecksenden/?code=YAJDLKT0104&choe=UTF-8" title="Link to Google.com" />
    <?php
    //echo esc_url( add_query_arg( 'c', '123456789ABC' ) );
    ?><br><?php
    $return_code='YAJDLKT0104';
    $first_part='https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=http%3A%2F%2Flocalhost/
    wp_test_ecom/ruecksenden/?code=';
    $end='&choe=UTF-8';
    $conc = $first_part.$return_code.$end;
    var_dump($conc);
    $api_qr = '
    <img src="'.$conc.'" 
    title="Link to Google.com"/>';
    var_dump($api_qr);
    ?><br><?php
    /**$packaging_last_order_date = get_option('packaging_last_check_date');
    $package_interval = get_option('packaging_interval');

    var_dump($packaging_last_order_date);
    var_dump($package_interval);

    $time = strtotime($packaging_last_order_date. " + $package_interval days");

    $newformat = date('Y-m-d',$time);

    var_dump($newformat);
    var_dump(date('Y-m-d',time()));
    var_dump($newformat<date('Y-m-d',time()));

        $package_amount = get_option('packaging_amount');
		$new_order_limit = get_option('packaging_limit');
		$new_order_amount = get_option('packaging_new_order_amount');
		$new_order_sent = get_option('packaging_new_order_sent');
		$last_order_date = get_option('packaging_last_order_date');
		$last_check_date = get_option('packaging_last_check_date');
		$interval = get_option('packaging_interval');
		$old_amount_1 = get_option('packaging_amount_1');
		$old_amount_2 = get_option('packaging_amount_2');
		$package_amount_int = (int)$package_amount;
		$new_order_limit_int = (int)$new_order_limit;
        $new_order_sent_int = (int)$new_order_sent;
        $old_amount_1_int = (int)$old_amount_1;
        $old_amount_2_int = (int)$old_amount_2;
        $new_check_date = strtotime($last_check_date. " + $interval days");
        $new_check_date_str = date('Y-m-d',$new_check_date);

        if($new_check_date_str <= date('Y-m-d',time())){
            if(($package_amount-$new_order_limit_int)-($old_amount_2_int-$package_amount)<=0){
                 $subject = 'Nachbestellung für Verpackungen.';
                $message = "Guten Tag, Hiermit bestellen wir ". $new_order_amount ." neue Verpackungen.";
                wp_mail( 'davebasler@hotmail.com', $subject, $message );

                $value = $wpdb->query("UPDATE wp_inventory SET new_order_sent = true WHERE id=1");
                update_option('packaging_new_order_sent', true);
                update_option('packaging_last_order_date', date('Y-m-d',time()));
            }else{
                update_option('packaging_amount_1', $old_amount_2);
                update_option('packaging_amount_2', $package_amount); 
            }
                //update_option('packaging_last_check_date', date('Y-m-d',time()));
        }**/

  /*
   $products_last_check = get_option('products_last_check');
    $products_interval = get_option('products_interval');

    var_dump($products_last_check);

    $newformat = date('Y-m-d',strtotime($products_last_check. " + $products_interval days"));

    var_dump($newformat);
    var_dump(date('Y-m-d',time()));
    var_dump($products_last_check<date('Y-m-d',time()));
    if($products_last_check<date('Y-m-d',time())){
        $res = $wpdb->get_results("
			SELECT * FROM wp_inventory 
			WHERE item_name != 'packagings' AND item_name != 'labels'"
        );
        $res_json = json_decode(json_encode($res), true);
        for($i=0; $i<count($res_json); $i++){
            $name = $res_json[$i]["item_name"];
            for($j=52; $j>1; $j--){
                $k=$j-1;
                $stock_new = $res_json[$i]["stock_$k"];
                $test = $wpdb->get_results( 
                    $wpdb->prepare("
                        UPDATE wp_inventory SET stock_$j = %s
                        WHERE item_name = %s", 
                        $stock_new, $name
                    ));
                if($j==2){
                    $current_stock = $res_json[$i]["amount"];
                    $test = $wpdb->get_results( 
                        $wpdb->prepare("
                            UPDATE wp_inventory SET stock_1 = %s
                            WHERE item_name = %s", 
                            $current_stock, $name
                        ));
                }
            }
        };
        

        update_option('products_last_check', date('Y-m-d',time()));
    }
    

        $package_amount = get_option('packaging_amount');
		$new_order_limit = get_option('packaging_limit');
		$new_order_amount = get_option('packaging_new_order_amount');
		$new_order_sent = get_option('packaging_new_order_sent');
		$last_order_date = get_option('packaging_last_order_date');
		$last_check_date = get_option('packaging_last_check_date');
		$interval = get_option('packaging_interval');
		$old_amount_1 = get_option('packaging_amount_1');
		$old_amount_2 = get_option('packaging_amount_2');
		$package_amount_int = (int)$package_amount;
		$new_order_limit_int = (int)$new_order_limit;
        $new_order_sent_int = (int)$new_order_sent;
        $old_amount_1_int = (int)$old_amount_1;
        $old_amount_2_int = (int)$old_amount_2;
        $new_check_date = strtotime($last_check_date. " + $interval days");
        $new_check_date_str = date('Y-m-d',$new_check_date);

        /**if($new_check_date_str <= date('Y-m-d',time())){
            if(($package_amount-$new_order_limit_int)-($old_amount_2_int-$package_amount)<=0){
                 $subject = 'Nachbestellung für Verpackungen.';
                $message = "Guten Tag, Hiermit bestellen wir ". $new_order_amount ." neue Verpackungen.";
                wp_mail( 'davebasler@hotmail.com', $subject, $message );

                $value = $wpdb->query("UPDATE wp_inventory SET new_order_sent = true WHERE id=1");
                update_option('packaging_new_order_sent', true);
                update_option('packaging_last_order_date', date('Y-m-d',time()));
            }else{
                update_option('packaging_amount_1', $old_amount_2);
                update_option('packaging_amount_2', $package_amount); 
            }
                //update_option('packaging_last_check_date', date('Y-m-d',time()));
        }**/


        function datediff($interval, $datefrom, $dateto, $using_timestamps = false)
{
    /*
    $interval can be:
    yyyy - Number of full years
    q    - Number of full quarters
    m    - Number of full months
    y    - Difference between day numbers
           (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
    d    - Number of full days
    w    - Number of full weekdays
    ww   - Number of full weeks
    h    - Number of full hours
    n    - Number of full minutes
    s    - Number of full seconds (default)
    */

    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto   = strtotime($dateto, 0);
    }

    $difference        = $dateto - $datefrom; // Difference in seconds
    $months_difference = 0;

    switch ($interval) {
        case 'yyyy': // Number of full years
            $years_difference = floor($difference / 31536000);
            if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                $years_difference--;
            }

            if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                $years_difference++;
            }

            $datediff = $years_difference;
        break;

        case "q": // Number of full quarters
            $quarters_difference = floor($difference / 8035200);

            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }

            $quarters_difference--;
            $datediff = $quarters_difference;
        break;

        case "m": // Number of full months
            $months_difference = floor($difference / 2678400);

            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }

            $months_difference--;

            $datediff = $months_difference;
        break;

        case 'y': // Difference between day numbers
            $datediff = date("z", $dateto) - date("z", $datefrom);
        break;

        case "d": // Number of full days
            $datediff = floor($difference / 86400);
        break;

        case "w": // Number of full weekdays
            $days_difference  = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day        = date("w", $datefrom);
            $days_remainder   = floor($days_difference % 7);
            $odd_days         = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?

            if ($odd_days > 7) { // Sunday
                $days_remainder--;
            }

            if ($odd_days > 6) { // Saturday
                $days_remainder--;
            }

            $datediff = ($weeks_difference * 5) + $days_remainder;
        break;

        case "ww": // Number of full weeks
            $datediff = floor($difference / 604800);
        break;

        case "h": // Number of full hours
            $datediff = floor($difference / 3600);
        break;

        case "n": // Number of full minutes
            $datediff = floor($difference / 60);
        break;

        default: // Number of full seconds (default)
            $datediff = $difference;
        break;
    }
    return $datediff;
}
?>