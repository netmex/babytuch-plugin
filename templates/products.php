<div class="wrap">
<h1>Babytuch Plugin</h1>
<?php settings_errors();
perform_regular_check();
?>
<form method="post" action="">Plugin ist aktiv.
<br><br>
<h3>Produkte Lagermanagement</h3>
<br>
<table style="border: 1px solid black;">
    <tr>
        <th style="width:100px;">Bestand</th>
        <th colspan="12">Tücher</th>
    </tr>
    <tr>
        <th>&nbsp; <!-- EMPTY --></th>
        <?php
            $products = wc_get_products( array('numberposts' => -1) );
            foreach($products as $product){
                $product_single = wc_get_product($product);
                $name = $product_single->get_name();
                $children   = $product_single->get_children();
                $num = count($children);
                echo " <th style='width:100px;'>$name</th>";
            }
            echo"</tr>";
            for($i=0; $i<$num;$i++){
                echo"<tr>";
                $j=$i+1;
                echo"<th style='height:50px;'>Grösse $j</th>";
                foreach($products as $product){
                    $product_single = wc_get_product($product);
                    $children   = $product_single->get_children();
                    if($product_single->is_type( 'variable' )){
                        $child = $children[$i];
                        $child_product = wc_get_product($child);
                        $stock = $child_product->get_stock_quantity();
                        $color=check_status($child_product);
                        echo " <td style='text-align: center; background-color:$color;'>$stock</td>";
                    }
                }
                echo"</tr>";
            }
            
        ?>
</table>
<br>
<input type="submit" value="<" name="back" disabled=true>
<input type="submit" value=">" name="forward"disabled=true>
<input type="submit" value="Exportieren" name="export"disabled=true>
<a href="" onClick="popitup('<?php echo get_home_url();?>/bestandesverlaeufe')">Bestandesverläufe anzeigen</a>
<script type="text/javascript">
function popitup(url) {
newwindow=window.open(url,'name','height=1000,width=1400');
if (window.focus) {newwindow.focus()}
return false;
}
</script>
<br><br>
<table style="border: 1px solid black;">
    <tr>
        <th style="width:100px;">Reserve</th>
        <th colspan="12">Tücher</th>
    </tr>
    <tr>
        <th>&nbsp; <!-- EMPTY --></th>
        <?php
            $products = wc_get_products( array('numberposts' => -1) );
            foreach($products as $product){
                $product_single = wc_get_product($product);
                $name = $product_single->get_name();
                $children   = $product_single->get_children();
                $num = count($children);
                echo " <th style='width:100px;'>$name</th>";
            }
            echo"</tr>";
            for($i=0; $i<$num;$i++){
                echo"<tr>";
                $j=$i+1;
                echo"<th style='height:50px;'>Grösse $j</th>";
                foreach($products as $product){
                    $product_single = wc_get_product($product);
                    $children   = $product_single->get_children();
                    if($product_single->is_type( 'variable' )){
                        $child = $children[$i];
                        $child_product = wc_get_product($child);
                        $child_name = $child_product->get_slug();
                        $child_limit = get_child_limit($child_name);
                        echo ' <td style="text-align: center;">
                        <input type="text" style="width: 40px" class="regular-text"
                        name="'.$child_name.'_new_order_limit2"
                        value="'.$child_limit.'" placeholder="'.$child_limit.'">
                        </td>';
                    }
                }
                echo"</tr>";
            }
            
        ?>
</table>
<br>
<input type="submit" value="Speichern" name="save_limits">
<br>
<h4>Allgemeine Einstellungen</h4>
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
$products_round_limit = get_option('products_round_limit');
echo "<p><input type='text' style='width: 50px' class='regular-text'
name='products_round_limit' value=$products_round_limit> Aufrundungs-Limite</p>";
echo"<br>";
?>
 <input type="submit" value="Speichern" name="submit_common_settings"> Speichert alle Felder.

    <?php
    /*$result = get_data();
    ?>
    <input type="submit" value="Speichern" name="submit"> Speichert alle Felder.
    */
    ?>
</form>
</div>

<?php
if(isset($_POST['save_limits'])){
    global $wpdb;
    $products = wc_get_products( array('numberposts' => -1) );
    foreach($products as $product){
        $product_single = wc_get_product($product);
        $children   = $product_single->get_children();
        $num = count($children);
        for($i=0; $i<$num;$i++){
            if($product_single->is_type( 'variable' )){
                $child = $children[$i];
                $child_product = wc_get_product($child);
                $child_name = $child_product->get_slug();
                $new_order_amount = $_POST[$child_name."_new_order_limit2"];
                var_dump($new_order_amount);
                $value = $wpdb->query( 
                    $wpdb->prepare( "
                        UPDATE babytuch_inventory SET new_order_limit = %s
                        WHERE item_name = %s", 
                        $new_order_amount, $child_name
                    ) 
                );
            }
        }
    }
    
    header("Refresh:0");
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
    $products_round_limit = $_POST['products_round_limit'];
    update_option('products_round_limit', $products_round_limit);

    header("Refresh:0");
}
if(isset($_POST['submit'])){
    update_data($result);
}
if(isset($_POST['export'])){
    return 'export';
}


function perform_regular_check(){
    global $wpdb;
    //REGULAR CHECK
    $products_last_check = get_option('products_last_check');
    $interval = get_option('products_interval');
    $next_check = strtotime($products_last_check. " + $interval days");
    $products_next_check = date('Y-m-d',$next_check);
    if($products_next_check<date('Y-m-d',time())){
        $res = $wpdb->get_results("
			SELECT * FROM babytuch_inventory 
            WHERE item_name != 'packagings' AND item_name != 'labels' 
            AND item_name != 'supplements' AND item_name != 'packagings_large'"
        );
        $res_json = json_decode(json_encode($res), true);
        
        $products = wc_get_products( array('numberposts' => -1) );
        $all_current_slugs = array();
        foreach($products as $product){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    array_push($all_current_slugs, $child_name);

                }
            }
        }

        for($i=0; $i<count($res_json); $i++){
            $name = $res_json[$i]["item_name"];
            for($j=52; $j>1; $j--){
                $k=$j-1;
                $stock_new = $res_json[$i]["stock_$k"];
                $test = $wpdb->get_results( 
                    $wpdb->prepare("
                        UPDATE babytuch_inventory SET stock_$j = %s
                        WHERE item_name = %s", 
                        $stock_new, $name
                    ));
                if($j==2){
                    $current_stock = $res_json[$i]["amount"];
                    $test = $wpdb->get_results( 
                        $wpdb->prepare("
                            UPDATE babytuch_inventory SET stock_1 = %s
                            WHERE item_name = %s", 
                            $current_stock, $name
                        ));
                }
            }
        };
        $supplements = $wpdb->get_results("
                SELECT * FROM babytuch_inventory 
                WHERE item_name = 'packagings' OR item_name = 'labels' 
                OR item_name = 'supplements' OR item_name = 'packagings_large'"
            );
        $supplements_json = json_decode(json_encode($supplements), true);
        foreach($supplements_json as $supplement){
            $name = $supplement["item_name"];
            for($j=52; $j>1; $j--){
                $k=$j-1;
                $stock_new = $supplement["stock_$k"];
                $test = $wpdb->get_results( 
                    $wpdb->prepare("
                        UPDATE babytuch_inventory SET stock_$j = %s
                        WHERE item_name = %s", 
                        $stock_new, $name
                    ));
                if($j==2){
                    $current_stock = $supplement["amount"];
                    $test = $wpdb->get_results( 
                        $wpdb->prepare("
                            UPDATE babytuch_inventory SET stock_1 = %s
                            WHERE item_name = %s", 
                            $current_stock, $name
                        ));
                }
            }
        }


        //SPECIAL ORDERS
        $products_last_reorder = get_option('products_last_reorder');
        $products_next_reorder = get_option('products_next_reorder');
        $next_special_order_check = strtotime($products_last_reorder. " + 20 days");
        $next_special_order_check_date = date('Y-m-d',$next_special_order_check);
        if($next_special_order_check_date<date('Y-m-d',time()) and
        $products_next_check<date('Y-m-d',time()) and
        !($products_next_reorder<=date('Y-m-d',time()))){
            $products_to_reorder = array();
            $products = wc_get_products( array('numberposts' => -1) );
            $msg = "(AUSSERORDENTLICHE) Nachbestellung mit den folgenden Produkten: <br>";
            $at_least_one = false;
            foreach($products as $product){
                $product_single = wc_get_product($product);
                $children   = $product_single->get_children();
                $num = count($children);
                for($i=0; $i<$num;$i++){
                    if($product_single->is_type( 'variable' )){
                        $child = $children[$i];
                        $child_product = wc_get_product($child);
                        $attr = $product->get_attributes();
                        $size = $attr["groesse"];
                        $child_slug = $child_product->get_slug();
                        $reorder_amount = get_reorder_amount($child_product);
                        $color=check_status($child_product);
                       
                        $details = $wpdb->get_results( 
                            $wpdb->prepare("
                                SELECT * FROM babytuch_inventory
                                WHERE item_name = %s", 
                                $child_slug
                            ));
                        $details_json = json_decode(json_encode($details), true);

                        if(($color == 'LightCoral' or 'SandyBrown' == $color) and
                            $details_json["new_order_sending"]==0){
                            $at_least_one = true;
                            $msg .= "<br> Vom Produkt $child_slug: (Grösse: $size) <b>$reorder_amount</b> Stück. <br>";
                            $today = date('Y-m-d h:i:s',time());
                            $test = $wpdb->query( 
                                $wpdb->prepare("
                                    UPDATE babytuch_inventory SET last_special_order = %s,
                                    new_order_sending = true, new_order_received = false, 
                                    new_order_amount = %s
                                    WHERE item_name = %s", 
                                    $today, $reorder_amount, $child_slug
                                ));
                            array_push($products_to_reorder, $child_product);
                        }
                    }
                }
            }
            foreach($supplements_json as $supplement){
                $color=check_supplement_status($supplement);
                if($color == 'LightCoral' or 'SandyBrown' == $color){
                    $name = $supplement["item_name"];
                    $reorder_amount = get_supplement_reorder_amount($supplement);
                    $up = (int)ceil($reorder_amount/pow(10, 2)) * pow(10, 2);
                    $at_least_one = true;
                    $msg .= "<br><br> Vom Verpackungsmaterial $name: $up Stück.<br>";
                    $today = date('Y-m-d h:i:s',time());
                    $test = $wpdb->get_results( 
                        $wpdb->prepare("
                            UPDATE babytuch_inventory SET last_special_order = %s,
                            new_order_sending = true, new_order_received = false, 
                            new_order_amount = %s
                            WHERE item_name = %s", 
                            $today,$reorder_amount, $name
                        ));
                    array_push($products_to_reorder, $child_product);
                }
            }
            if($at_least_one){

                generate_pdf($products_to_reorder);

                $num = get_option('reorder_document_num');
                $num = (int)$num - 1;
                $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/reorder_documents/reorder_document_$num.pdf");

                $header = array('Content-Type: text/html; charset=UTF-8');
                $babytuch_admin_email = get_option('babytuch_admin_email');
                wp_mail($babytuch_admin_email, '(AUSSERORDENTLICHE) Nachbestellung', $msg, $header, $attach);
            }
        }
        
        update_option('products_last_check', date('Y-m-d',time()));
    }

    //NEW ORDER
    $products_next_reorder = get_option('products_last_reorder'); //CHANGE TO _next_ !!!!
    if($products_next_reorder<=date('Y-m-d',time())){
        $products_round_limit = get_option('products_round_limit');
        $products = wc_get_products( array('numberposts' => -1) );
        $products_to_reorder = array();
        $total_reorder_amount = 0;
        $msg = "Nachbestellung mit den folgenden Produkten: <br>";
        foreach($products as $product){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                 
                    $reorder_amount = get_reorder_amount($child_product);
                    $total_reorder_amount = $total_reorder_amount + (int)$reorder_amount;

                    $child_slug = $child_product->get_slug();
                    $details = $wpdb->get_results( 
                        $wpdb->prepare("
                            SELECT * FROM babytuch_inventory
                            WHERE item_name = %s", 
                            $child_slug
                        ));
                    $details_json = json_decode(json_encode($details), true);

                    if($reorder_amount!=0 and $details_json[0]["new_order_sending"]==0){
                        array_push($products_to_reorder, $child_product);
                    }
                }
            }            
        }
        if(empty($products_to_reorder)){
            $msg .= 'Keine Nachbestellung von Produkten nötig.';
        }
        elseif(($total_reorder_amount%100 != 0 and $total_reorder_amount%100 >= (int)$products_round_limit )
            or ($total_reorder_amount%100 != 0 and $total_reorder_amount<=100)){
           //AUFRUNDEN
            $up = (int)ceil($total_reorder_amount/pow(10, 2)) * pow(10, 2);
            $percentage = $up/$total_reorder_amount;
            $new_total = 0;
            $count = count($products_to_reorder);
            $i=1;
            foreach($products_to_reorder as $product){
                $child_slug = $product->get_slug();
                $attr = $product->get_attributes();
                $size = $attr["groesse"];
                $reorder_amount = get_reorder_amount($product);
                $reorder_amount = round($reorder_amount*$percentage);
                $new_total = $new_total + (int)$reorder_amount;
                if($i == $count){
                    if($new_total%100 != 0 and $new_total%100>=10){
                        $diff = 100 - $new_total%100;
                        $reorder_amount = $reorder_amount + $diff;
                        $new_total = $new_total + $diff;
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }elseif($new_total%100 != 0 and $new_total%100<10){
                        $diff = $new_total%100;
                        $reorder_amount = $reorder_amount - $diff;
                        $new_total = $new_total - $diff;
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }else{
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }
                }else{
                    update_db($reorder_amount, $child_slug);
                    $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                }
                $i++;
            }
            $msg .= "<br><br>Bestellungen (Produkte) insgesamt: <b>$new_total</b> Stück. (wurde aufgerundet)<br>";
        }elseif($total_reorder_amount%100 != 0 and $total_reorder_amount%100 < (int)$products_round_limit ){
            //ABRUNDEN
            $down = floor($total_reorder_amount/pow(10, 2)) * pow(10, 2);
            $percentage =  $down/$total_reorder_amount;
            $new_total = 0;
            $count = count($products_to_reorder);
            $i=1;
            foreach($products_to_reorder as $product){
                $child_slug = $product->get_slug();
                $attr = $product->get_attributes();
                $size = $attr["groesse"];
                $reorder_amount = get_reorder_amount($product);
                $reorder_amount = round($reorder_amount*$percentage);
                $new_total = $new_total + (int)$reorder_amount;
                if($i == $count){
                    if($new_total%100 != 0 and $new_total%100>=10){
                        $diff = 100 - $new_total%100;
                        $reorder_amount = $reorder_amount + $diff;
                        $new_total = $new_total + $diff;
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }elseif($new_total%100 != 0 and $new_total%100<10){
                        $diff = $new_total%100;
                        $reorder_amount = $reorder_amount - $diff;
                        $new_total = $new_total - $diff;
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }else{
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }
                }else{
                    update_db($reorder_amount, $child_slug);
                    $msg .= "<br> Vom Produkt $child_slug (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                }
                $i++;
            }
            $msg .= "<br><br>Bestellungen (Produkte) insgesamt: <b>$new_total</b> Stück. (wurde abgerundet)<br>";
        }else{
            foreach($products_to_reorder as $product){
                $child_slug = $product->get_slug();
                $reorder_amount = get_reorder_amount($product);
                update_db($reorder_amount, $child_slug);
                $msg .= "<br> Vom Produkt $child_slug: <b>$reorder_amount</b> Stück. <br>";
            }
            $msg .= "<br>Bestellungen (Produkte) insgesamt: <b>$total_reorder_amount</b> Stück. <br>";
        }

        //VERPACKUNGSMATERIAL
        $supplements = $wpdb->get_results("
                SELECT * FROM babytuch_inventory 
                WHERE item_name = 'packagings' OR item_name = 'labels' 
                OR item_name = 'supplements' OR item_name = 'packagings_large'"
            );
        $supplements_json = json_decode(json_encode($supplements), true);
        $msg .= "<br><br>Verpackungsmaterial: <br><br>";
        foreach($supplements_json as $supplement){
            $reorder_amount = get_supplement_reorder_amount($supplement);
            if($reorder_amount != 0 and $supplement["new_order_sending"]==0){
                $up = (int)ceil($reorder_amount/pow(10, 2)) * pow(10, 2);
                $name = $supplement["item_name"];
                $msg .= "<br> Vom Verpackungsmaterial $name: $up Stück.<br>";
                update_db($up, $name);
                array_push($products_to_reorder, $supplement);
            }
        }
        if(!empty($products_to_reorder)){

        
            generate_pdf($products_to_reorder);

            $next_reorder = date('Y-m-d', strtotime($products_next_reorder . " + 6 months"));
            //update_option('products_last_reorder', date('Y-m-d',time()));
            //update_option('products_next_reorder', $next_reorder);
            $num = get_option('reorder_document_num');
            $num = (int)$num - 1;
            $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/reorder_documents/reorder_document_$num.pdf");
            $msg .= "<br> Nächste Nachbestellung: $next_reorder";
            $header = array('Content-Type: text/html; charset=UTF-8');
            $babytuch_admin_email = get_option('babytuch_admin_email');
            wp_mail($babytuch_admin_email, 'Halbjährige Nachbestellung', $msg, $header, $attach);
       }
    }
}

function update_db($amount, $name){
    global $wpdb;
    $test = $wpdb->get_results( 
        $wpdb->prepare("
            UPDATE babytuch_inventory SET new_order_amount = %s
            WHERE item_name = %s", 
            $amount, $name
        ));
    $date = date('Y-m-d h:i:s',time());
    $test = $wpdb->get_results( 
        $wpdb->prepare("
            UPDATE babytuch_inventory SET new_order_sending = true,
            new_order_sending_date = %s, new_order_received = false
            WHERE item_name = %s", 
            $date, $name
        ));   
}

//Nachbestellungs PDF erstellen
function generate_pdf($products_to_reorder){
    $home_url_full = get_home_url();
    $home_url = substr($home_url_full, 7);

    $home_path = get_home_path();
    require_once($home_path.'/wp-content/plugins/babytuch-plugin/assets/TCPDF-master/tcpdf.php');

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', 'A4', true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Babytuch Schweiz');
    $pdf->SetTitle('Nachbestellungskontrolle');
    $pdf->SetSubject('Nachbestellungskontrolle');
    $pdf->SetKeywords('nachbestellungskontrolle, produktinfo');

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
	$pdf->setTopMargin(13.0);
    $pdf->SetRightMargin(6.0);
    
    $pdf->setHeaderMargin(13);
    $pdf->SetFooterMargin(13.0); //13mm
	
	$pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // ---------------------------------------------------------

    // set default font subsetting mode
    $pdf->setFontSubsetting(true);

    // Set font
    $pdf->SetFont('helvetica', '', 11);
	$pdf->setCellHeightRatio(0.6);
	
    global $wpdb;
    foreach($products_to_reorder as $product){
        if($product instanceof WC_Product){
            $name = $product->get_name();
            $slug = $product->get_slug();
            $attr = $product->get_attributes();
            $size = $attr["groesse"];
            $details = $wpdb->get_results( 
                $wpdb->prepare("
                    SELECT * FROM babytuch_inventory
                    WHERE item_name = %s", 
                    $slug
                ));
            $details_json = json_decode(json_encode($details), true);
            //$name = $details_json[0]["item_name"];
            $date = $details_json[0]["new_order_sending_date"];
            $amount = $details_json[0]["new_order_amount"];
            $code = $details_json[0]["receiving_code"];
            $img_url = wp_get_attachment_url( $product->get_image_id() );
            //$size = substr($name, -1);
        }else{
            $details_json = $product;
            $name = $details_json["item_name"];
            $date = $details_json["new_order_sending_date"];
            $amount = $details_json["new_order_amount"];
            $code = $details_json["receiving_code"];
            $img_url = 'no';
        }
       
        
        $pdf->AddPage();
        $pdf->SetXY(10, 10);
        $html = '<h3>Nachbestellung</h3>';
        $html .= '<h4>Eingangskontrolle</h4><br><br><br><br>';
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

        $html = "<br><div>Name des Artikels: $name </div><br>";
        if($img_url!='no'){
            $html .= "<br><div>Grösse: $size </div><br>";
        }
        $html .= "<div>Datum der Nachbestellung: $date </div><br>";
        $html .= "<div>Anzahl bestellter Artikel: $amount</div><br><br>";
       

        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

        if($img_url!='no'){
            $pdf->SetXY(10, 50);
            $html = '<div style=" float: left; width: 33.33%; padding: 5px;">';
            $html .= '<img src="'.$img_url.'" width="120px"/>';
            $html .= '</div>';
            $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        }

        $pdf->SetXY(10, 75);
        $api_qr ='<p>Erhalt bestätigen:</p>
        <img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=http%3A%2F%2F'.$home_url.'/eingang-nachbestellungen/?code='.$code.'&choe=UTF-8" title="Link to Google.com"
        width="130" height="130"/>';
        
        $pdf->writeHTML($api_qr, true, 0, true, 0);
        
    }
    
    // ---------------------------------------------------------
    ob_end_clean();

    $reorder_document_num = get_option('reorder_document_num');
    update_option('reorder_document_num', (int)$reorder_document_num+1);

    $home_path = get_home_path();
    $path = $home_path.'/wp-content/plugins/babytuch-plugin/reorder_documents/';
    $pdf->Output($path."reorder_document_$reorder_document_num.pdf", 'F');
}

function get_supplement_reorder_amount($supplement){
    $decrease_rates = array();
    for($j=26; $j>1; $j--){
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
            $decrease_rate = (int)$stock_old - (int)$stock_new;  
            array_push($decrease_rates, $decrease_rate);
        }
    }
    $average = array_sum($decrease_rates)/count($decrease_rates);
    $limit = $supplement["new_order_limit"];
    $current_amount = $supplement["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $num_weeks = 28;
    /**if($current_amount-($num_weeks*2*$average)>=0){
        return 'lightgreen';
    }**/
    if((int)$current_amount<(int)$limit){
        return round($num_weeks*$average)+((int)$limit-(int)$current_amount);
    } 
    elseif((int)$current_amount-($num_weeks*$average)<(int)$limit){
        return round($num_weeks*$average);
    }
    return 0;
}

function check_supplement_status($supplement){
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

function get_reorder_amount($child_product){
    $child_name = $child_product->get_slug();
    global $wpdb;
    $res = $wpdb->get_results( 
        $wpdb->prepare( "
            SELECT * FROM babytuch_inventory
            WHERE item_name = %s", 
            $child_name
        ) 
    );
    $res_json = json_decode(json_encode($res), true);
    $decrease_rates = array();
    for($j=26; $j>1; $j--){
        $stock_old = $res_json[0]["stock_$j"];
        $k=$j-1;
        $stock_new = $res_json[0]["stock_$k"];
        if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
            $decrease_rate = (int)$stock_old - (int)$stock_new;  
            array_push($decrease_rates, $decrease_rate);
        }
        if($j==2){
            $stock_old = $res_json[0]["stock_1"];
            $stock_new = $res_json[0]["amount"];
            $decrease_rate = (int)$stock_old - (int)$stock_new;  
            array_push($decrease_rates, $decrease_rate);
        }
    }
    $average = array_sum($decrease_rates)/count($decrease_rates);
    $limit = $res_json[0]["new_order_limit"];
    $current_amount = $res_json[0]["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
    $num_weeks = $num_weeks+2;
    /**if($current_amount-($num_weeks*2*$average)>=0){
        return 'lightgreen';
    }**/
    if((int)$current_amount<(int)$limit){
        return round($num_weeks*$average)+((int)$limit-(int)$current_amount);
    } 
    elseif((int)$current_amount-($num_weeks*$average)<(int)$limit){
        return round($num_weeks*$average);
    }
    return 0;
}

function check_status($child_product){
    $child_name = $child_product->get_slug();
    global $wpdb;
    $res = $wpdb->get_results( 
        $wpdb->prepare( "
            SELECT * FROM babytuch_inventory
            WHERE item_name = %s", 
            $child_name
        ) 
    );
    $res_json = json_decode(json_encode($res), true);
    $decrease_rates = array();
    for($j=52; $j>1; $j--){
        $stock_old = $res_json[0]["stock_$j"];
        $k=$j-1;
        $stock_new = $res_json[0]["stock_$k"];
        if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
            $decrease_rate = (int)$stock_old - (int)$stock_new;  
            array_push($decrease_rates, $decrease_rate);
        }
        if($j==2){
            $stock_old = $res_json[0]["stock_1"];
            $stock_new = $res_json[0]["amount"];
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
    $limit = $res_json[0]["new_order_limit"];
    $current_amount = $res_json[0]["amount"];
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

function get_child_limit($child_name){
    global $wpdb;
    $res = $wpdb->get_results( 
        $wpdb->prepare( "
            SELECT * FROM babytuch_inventory
            WHERE item_name = %s", 
            $child_name
        ) 
    );
    $res_json = json_decode(json_encode($res), true);
    return ($res_json[0]["new_order_limit"]);
}

function get_data(){
    global $wpdb;
    $res = $wpdb->get_results("
			SELECT * FROM babytuch_inventory 
            WHERE item_name != 'packagings' AND item_name != 'labels'
            AND item_name != 'supplements' AND item_name != 'packagings_large'"
    );
    $res_json = json_decode(json_encode($res), true);
    for($i=0; $i<count($res_json); $i++){
        $name = $res_json[$i]["item_name"];
        $new_order_amount = $res_json[$i]["new_order_amount"];
        $new_order_limit = $res_json[$i]["new_order_limit"];
        $new_order_sent = $res_json[$i]["new_order_sent"];
         echo '<h4>'.$name.'</h4><p>Unterer Grenzwert für Nachbestellungen</p>
            <input type="text" style="width: 50px" class="regular-text" name="'.$name.'_new_order_limit" 
            value="' .$new_order_limit . '" placeholder="' .$new_order_limit.'">
            <p>Menge der Nachbestellungen</p>
            <input type="text" style="width: 50px" class="regular-text" name="'.$name.'_new_order_amount" 
            value="' .$new_order_amount . '" placeholder="' .$new_order_amount.'">
            <p>Nachbestellung gesendet (1/0)</p>
            <input type="text" style="width: 50px" class="regular-text" name="'.$name.'_new_order_sent" 
            value="' .$new_order_sent . '" placeholder="' .$new_order_sent.'">
            <br><br>';
    };
    return $res_json;
}

function update_data($result){
    global $wpdb;
    for($i=0; $i<count($result); $i++){
        $name = $result[$i]["item_name"];
        $new_order_amount = $_POST[$name."_new_order_amount"];
        $value = $wpdb->query( 
            $wpdb->prepare( "
                UPDATE babytuch_inventory SET new_order_amount = %s
                WHERE item_name = %s", 
                $new_order_amount, $name
            ) 
        );
        $new_order_limit = $_POST[$name."_new_order_limit"];
        $value = $wpdb->query( 
            $wpdb->prepare( "
                UPDATE babytuch_inventory SET new_order_limit = %s
                WHERE item_name = %s", 
                $new_order_limit, $name
            ) 
        );
        $new_order_sent = $_POST[$name."_new_order_sent"];
        $value = $wpdb->query( 
            $wpdb->prepare( "
                UPDATE babytuch_inventory SET new_order_sent = %s
                WHERE item_name = %s", 
                $new_order_sent, $name
            ) 
        );
    }
    header("Refresh:0");
}




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
