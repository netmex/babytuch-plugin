<?php
// check user capabilities

use WPO\WC\PDF_Invoices\Compatibility\Product;

if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  //Get the active tab from the $_GET param
  $default_tab = null;
  $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

  ?>
  <!-- Our admin page content should all be inside .wrap -->
  <div class="wrap">
  <h1>Babytuch Plugin</h1>
    <?php settings_errors();

    ?>
    <h3>Quartalsabrechnung</h3>
    <p>Die Abrechnung erstellt die Quartalsabrechnungen. Bei jedem Aufruf dieser Seite wird überprüft, ob eine neue Abrechnung fällig ist und generiert dann eine solche und sendet sie an Admin und Österreich.
</p>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=babytuch_billing" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Statistiken</a>
      <a href="?page=babytuch_billing&tab=settings" class="nav-tab <?php if($tab==='settings'):?>nav-tab-active<?php endif; ?>">Einstellungen</a>
        <a href="?page=babytuch_billing&tab=archive_data" class="nav-tab <?php if($tab==='archive_data'):?>nav-tab-active<?php endif; ?>">Daten</a>
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'settings':
        ?>
        <form method="post" action="">
            <h3>Allgemeine Einstellungen</h3>
            <p style="color:grey;">Im Tab Einstellungen kann man die Check Einstellungen anpassen:</p><br>
            <?php
            $billing_last_generated = get_option('billing_last_generated');
            echo "<p><input type='date' style='width: 150px' class='regular-text'
            name='billing_last_generated' value=$billing_last_generated> Letzte Abrechnung</p>";
            ?><p style="color:grey;">Datum der letzten Abrechnung (beeinflusst nichts bei Änderung)</p><?php
            echo"<br>";
            $billing_next_generated = get_option('billing_next_generated');
            echo "<p><input type='date' style='width: 150px' class='regular-text'
            name='billing_next_generated' value=$billing_next_generated> Nächste Abrechnung</p>";
            ?><p style="color:grey;"> Wenn dieses Datum erreicht wurde, wird eine Quartalsabrechnung generiert
             und versendet (an Admin und Österreich). 
            Neues Datum wird danach automatisch berechnet aufgrund dem eingestellten Interval.</p><?php
            echo"<br>";
            $billing_interval = get_option('billing_interval');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_interval' value=$billing_interval> Interval (in Monate)</p>";
            ?><p style="color:grey;">Monate bis zur nächsten Abrechnung.</p><?php
            echo"<br>";
            $billing_quarter = get_option('billing_quarter');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_quarter' value='$billing_quarter'> Quartal</p>";
            ?><p style="color:grey;">Nummer des Quartals. Diese wird nach jeder Abrechnung automatisch erhöht bzw. 
            bei 4 wieder auf 1 gestellt.</p><?php
            echo"<br><br>";
            echo"<h4>Daten Babytuch.com</h4>";
            ?><p style="color:grey;">Daten von Babytuch in Österreich, welche 
            im Dokument erscheinen. 
            Die E-Mail ist zudem diejenige an die das Dokument auch versandt wird.</p><?php
           
            $billing_second_address = get_option('billing_second_address');
            echo "<p><input type='text' style='width: 150px' class='regular-text'
            name='billing_second_address' value=$billing_second_address> E-Mail Adresse (babytuch.com) </p>";

            $initials_aut = get_option('initials_aut');
            echo "<p><input type='text' style='width: 150px'
            name='initials_aut' value='$initials_aut'> Initialen </p>";

            $lname_aut = get_option('lname_aut');
            echo "<p><input type='text' style='width: 150px' class='regular-text'
            name='lname_aut' value='$lname_aut'> Nachname </p>";

            $fname_aut = get_option('fname_aut');
            echo "<p><input type='text' style='width: 150px' class='regular-text'
            name='fname_aut' value='$fname_aut'> Vorname </p>";

            $street_aut = get_option('street_aut');
            echo "<p><input type='text' style='width: 150px' class='regular-text'
            name='street_aut' value='$street_aut'> Adresse </p>";

            $zip_aut = get_option('zip_aut');
            echo "<p><input type='text' style='width: 150px' class='regular-text'
            name='zip_aut' value=$zip_aut> PLZ </p>";

            $city_aut = get_option('city_aut');
            echo "<p><input type='text' style='width: 150px' class='regular-text'
            name='city_aut' value='$city_aut'> Ort </p>";
            echo"<br><br>";
            echo"<h4>Lizenzgebühr</h4>";
            ?><p style="color:grey;">Lizenzgebühr Berechnungsparameter,
             welche für die Berechnungen der Lizenzgebühren im Dokument verantwortlich sind.</p><?php
            $billing_license_lower_limit = get_option('billing_license_lower_limit');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_license_lower_limit' value='$billing_license_lower_limit'> Unterer Grenzwert (Anzahl Tücher)</p>";

            $billing_license_upper_limit = get_option('billing_license_upper_limit');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_license_upper_limit' value='$billing_license_upper_limit'> Oberer Grenzwert </p>";
            echo"<br>";
            $billing_license_factor_1 = get_option('billing_license_factor_1');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_license_factor_1' value='$billing_license_factor_1'> Unterer Faktor </p>";

            $billing_license_factor_2 = get_option('billing_license_factor_2');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_license_factor_2' value='$billing_license_factor_2'> Mittlerer Faktor </p>";

            $billing_license_factor_3 = get_option('billing_license_factor_3');
            echo "<p><input type='text' style='width: 50px' class='regular-text'
            name='billing_license_factor_3' value='$billing_license_factor_3'> Oberer Faktor </p>";
            echo"<br>";

            ?>
            <input type="submit" value="Speichern" name="submit_common_settings"> Speichert alle Felder.
        </form>
    <?php
        break;
        case 'archive_data':
            $billing_quarter = get_option('billing_quarter');
            if((int)$billing_quarter==1){
                $billing_quarter = 4;
            }else{
                $billing_quarter = (int)$billing_quarter - 1;
            }
            ?>
            <form method="post" action="">
                <h3>Daten (Archiv)</h3> 
                <p style="color:grey;">Im Tab Daten befinden sich alle “Archiv”-Daten der letzten (“echten”) Abrechnung. Diese sollte man nur in Problemfällen oder für Testzwecke modifizieren. Dazu befinden sich noch die aktuell berechneten Werte vom jetzigen Quartal (die würden also im Sofort Export Bericht gleich sein).
</p>
                <p>Daten des letzten Vertriebberichts (Quartal: <?php echo $billing_quarter?>)</p> 
                <?php
                 $total_sold = get_total_product_sales();
                 $best_sold_product = get_topseller();
                 $n1 = $best_sold_product["name"];
                    $a1 = $best_sold_product["amount"];
                 $total_returns = get_option('total_returns');
                if(!$total_returns){
                    $total_returns = 0;
                }
                 $most_used_reason = get_most_used_reason();
                 $n2 = $most_used_reason["reason"];
                 $a2 = $most_used_reason["amount"];

                 $google = 400;
                 $facebook = 560;
                 $flyer_qr = 75;
                 $clients_from_qr = 14;
                 $advice = 19;
             
                 $total_current_stock = get_total_current_stock();
                 $licence_fee = calculate_license_fee();

                 echo"<h4>Absatz</h4>";
                $billing_total_sold = get_option('billing_total_sold');
                echo "<p>Aktuell: $total_sold</p>";
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_total_sold' value=$billing_total_sold> Totale Verkäufe</p>";
                $billing_best_sold_product = get_option('billing_best_sold_product');
                $name = $billing_best_sold_product["name"];
                $amount = $billing_best_sold_product["amount"];
                echo "<p>Aktuell: $n1 ($a1)</p>";
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_best_sold_product' value=$amount> Bestseller <b>($name)</b></p>";
                echo "<p>Aktuell: $total_returns</p>";
                $billing_total_returns = get_option('billing_total_returns');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_total_returns' value=$billing_total_returns> Rücksendungen</p>";
                echo "<p>Aktuell: $n2 ($a2)</p>";
                $billing_total_most_used_reason = get_option('billing_total_most_used_reason');
                $reason = $billing_total_most_used_reason["reason"];
                $amount2 = $billing_total_most_used_reason["amount"];
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_total_most_used_reason' value='$amount2'> Meist genannter Grund für Rücksendung <b>($reason)</b></p>";
                echo"<br>";
                echo"<h4>Vertriebskanäle</h4>";
                $billing_google = get_option('billing_google');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_google' value=$billing_google disabled> Google (Klicks) </p>";
    
                $billing_facebook = get_option('billing_facebook');
                echo "<p><input type='text' style='width: 50px'
                name='billing_facebook' value='$billing_facebook' disabled> Facebook (Klicks) </p>";
    
                $billing_flyer_qr = get_option('billing_flyer_qr');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_flyer_qr' value='$billing_flyer_qr' disabled> Flyer (aktivierte QR-Codes) </p>";
    
                $billing_clients_from_qr = get_option('billing_clients_from_qr');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_clients_from_qr' value='$billing_clients_from_qr' disabled> Vermittlungsprogramm Kunden </p>";
    
                $billing_advice = get_option('billing_advice');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_advice' value='$billing_advice' disabled> Vermittlungsprogramm Trageberatung </p>";
                echo"<br>";
                echo"<h4>Logistik</h4>";
                echo "<p>Aktuell: $total_current_stock</p>";
                $billing_total_current_stock = get_option('billing_total_current_stock');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_total_current_stock' value=$billing_total_current_stock> Babytücher an Lager </p>";
                echo"<br>";
                echo"<h4>Finanzielles</h4>";
                echo "<p>Aktuell: $licence_fee</p>";
                $billing_licence_fee = get_option('billing_licence_fee');
                echo "<p><input type='text' style='width: 50px' class='regular-text'
                name='billing_licence_fee' value='$billing_licence_fee'> zu überweisende Lizenzgebühr (EUR) </p>";
                echo"<br>";
                ?>
                <input type="submit" value="Speichern" name="submit_archive_settings"> Speichert alle Felder.
            </form>
        <?php
        break;
      default:
      $total_product_sales = get_total_product_sales();
      $total_packaging = 15;
      $total_labels = 45;
      $total_supplements = 15;
      $total_returns = get_option('total_returns');
        if(!$total_returns){
            $total_returns = 0;
        }
      /*
      <p>Anzahl gebrauchte Verpackungen: <?php echo ($total_packaging)?></p>
      <p>Anzahl gebrauchte Etiketten: <?php echo ($total_labels)?></p>
      <p>Anzahl gebrauchte Verpackungen: <?php echo ($total_supplements)?></p>
      */
      generator();
    ?>
        <form method="post" action="">
        <p style="color:grey;">Im Tab Statistiken sind allgemeine Statistiken ersichtlich. Der Knopf “Sofort Exportieren” 
            generiert ein Dokument sofort mit den aktuellen Zahlen und sendet dieses dem Admin.
             Dies ändert allerdings nichts daran, wann oder wie das nächste Mal ein “echtes” Dokument 
             generiert wird. Die Statistiken sind immer auf dem Stand der letzten Überprüfung der 
             Bestände.
</p>
            <br>
            <input type="submit" value="Sofort Exportieren" name="export">
            <p style="color:grey;">Generiert ein Dokument sofort mit den aktuellen Zahlen und 
            sendet dieses dem Admin.
             Dies ändert allerdings nichts daran, wann oder wie das nächste Mal ein “echtes” Dokument 
             generiert wird.</p>
            <h3>Allgemeine Statistiken</h3>
            <p>Anzahl verkaufter Tücher: <?php echo ($total_product_sales)?></p>
            <p>Anzahl Rücksendungen: <?php echo ($total_returns)?></p>
            <h4>Datum der letzten Bestellung</h4>
            <?php
                $orders = wc_get_orders( array('numberposts' => -1) );
                if(empty($orders)){
                    echo "<p>Noch keine Bestellungen.</p>";
                }else{
                    $order_single = wc_get_order($orders[0]);
                    $order_date = $order_single->get_date_created();
                    echo $order_date->date('Y-m-d');
                }
                
            ?>
            <h4>Aktueller Bestand</h4>
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
                            if($product->is_type( 'variable' )){
                                $product_single = wc_get_product($product);
                                $name = $product_single->get_name();
                                $children   = $product_single->get_children();
                                $num = count($children);
                                //$img_url = wp_get_attachment_url( $product_single->get_image_id() );
                                $attachment_ids = $product_single->get_gallery_image_ids();
                                if($attachment_ids){
                                $img_url = wp_get_attachment_url($attachment_ids[0]);
                                }
                                if($attachment_ids){
                                    echo " <th style='width:100px;'>
                                    <img src=$img_url width='80px'/></th>";
                                }else{
                                    echo " <th style='width:100px;'>$name</th>";
                                }
                            }
                        }
                        echo"</tr>";
                        for($i=0; $i<$num;$i++){
                            echo"<tr>";
                            $j=$i+1;
                            echo"<th style='height:50px;'>Grösse $j</th>";
                            foreach($products as $product){
                                if($product->is_type( 'variable' )){
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
                            }
                            echo"</tr>";
                        }
                        
                    ?>
            </table>
            <br>
            <h4>Verkäufe (im den letzten <?php echo get_option('billing_interval');?> Monaten)</h4>
            <table style="border: 1px solid black;">
                <tr>
                    <th style="width:100px;">Verkäufe</th>
                    <th colspan="12">Tücher</th>
                </tr>
                <tr>
                    <th>&nbsp; <!-- EMPTY --></th>
                    <?php
                        $products = wc_get_products( array('numberposts' => -1) );
                        foreach($products as $product){
                            if($product->is_type( 'variable' )){
                                $product_single = wc_get_product($product);
                                $name = $product_single->get_name();
                                $children   = $product_single->get_children();
                                $num = count($children);
                                //$img_url = wp_get_attachment_url( $product_single->get_image_id() );
                                $attachment_ids = $product_single->get_gallery_image_ids();
                                if($attachment_ids){
                                $img_url = wp_get_attachment_url($attachment_ids[0]);
                                }
                                if($attachment_ids){
                                    echo " <th style='width:100px;'>
                                    <img src=$img_url width='80px'/></th>";
                                }else{
                                    echo " <th style='width:100px;'>$name</th>";
                                }
                            }
                        }
                        echo"</tr>";
                        for($i=0; $i<$num;$i++){
                            echo"<tr>";
                            $j=$i+1;
                            echo"<th style='height:50px;'>Grösse $j</th>";
                            foreach($products as $product){
                                if($product->is_type( 'variable' )){
                                    $product_single = wc_get_product($product);
                                    $children   = $product_single->get_children();
                                    if($product_single->is_type( 'variable' )){
                                        $child = $children[$i];
                                        $child_product = wc_get_product($child);
                                        $child_name = $child_product->get_slug();
                                        $child_sales= get_child_sales($child_name);
                                        echo " <td style='text-align: center;'>
                                        $child_sales
                                        </td>";
                                    }
                                }
                            }
                            echo"</tr>";
                        }
                    ?>
            </table>
        </form>
    <?php
    endswitch; ?>
    </div>
  </div>

<?php
if(isset($_POST['submit_archive_settings'])){
  
    $billing_total_sold = $_POST['billing_total_sold'];
    update_option('billing_total_sold', $billing_total_sold);
    $billing_best_sold_product = $_POST['billing_best_sold_product'];
    $name = get_option('billing_best_sold_product');
    $name2 = $name["name"];
    $temp = array(
        'name' => $name2,
        'amount' => $billing_best_sold_product
    );
    update_option('billing_best_sold_product', $temp);
    $billing_total_returns = $_POST['billing_total_returns'];
    update_option('billing_total_returns', $billing_total_returns);
    $billing_total_most_used_reason = $_POST['billing_total_most_used_reason'];
    $name3 = get_option('billing_total_most_used_reason');
    $name4 = $name3["reason"];
    $temp2 = array(
        'reason' => $name4,
        'amount' => $billing_total_most_used_reason
    );
    update_option('billing_total_most_used_reason', $temp2);
    $billing_google = $_POST['billing_google'];
    update_option('billing_google', $billing_google);
    $billing_facebook = $_POST['billing_facebook'];
    update_option('billing_facebook', $billing_facebook);
    $billing_flyer_qr = $_POST['billing_flyer_qr'];
    update_option('billing_flyer_qr', $billing_flyer_qr);
    $billing_clients_from_qr = $_POST['billing_clients_from_qr'];
    update_option('billing_clients_from_qr', $billing_clients_from_qr);
    $billing_advice = $_POST['billing_advice'];
    update_option('billing_advice', $billing_advice);
    $billing_total_current_stock = $_POST['billing_total_current_stock'];
    update_option('billing_total_current_stock', $billing_total_current_stock);
    $billing_licence_fee = $_POST['billing_licence_fee'];
    update_option('billing_licence_fee', $billing_licence_fee);

    header("Refresh:0");
}

if(isset($_POST['submit_common_settings'])){
    
    $billing_last_generated = $_POST['billing_last_generated'];
    update_option('billing_last_generated', $billing_last_generated);
    $billing_next_generated = $_POST['billing_next_generated'];
    update_option('billing_next_generated', $billing_next_generated);
    $billing_interval = $_POST['billing_interval'];
    update_option('billing_interval', $billing_interval);
    $billing_second_address = $_POST['billing_second_address'];
    update_option('billing_second_address', $billing_second_address);
    $initials_aut = $_POST['initials_aut'];
    update_option('initials_aut', $initials_aut);
    $lname_aut = $_POST['lname_aut'];
    update_option('lname_aut', $lname_aut);
    $fname_aut = $_POST['fname_aut'];
    update_option('fname_aut', $fname_aut);
    $street_aut = $_POST['street_aut'];
    update_option('street_aut', $street_aut);
    $zip_aut = $_POST['zip_aut'];
    update_option('zip_aut', $zip_aut);
    $city_aut = $_POST['city_aut'];
    update_option('city_aut', $city_aut);
    $billing_quarter = $_POST['billing_quarter'];
    update_option('billing_quarter', $billing_quarter);
    $billing_license_lower_limit = $_POST['billing_license_lower_limit'];
    update_option('billing_license_lower_limit', $billing_license_lower_limit);
    $billing_license_upper_limit = $_POST['billing_license_upper_limit'];
    update_option('billing_license_upper_limit', $billing_license_upper_limit);
    $billing_license_factor_1 = $_POST['billing_license_factor_1'];
    update_option('billing_license_factor_1', $billing_license_factor_1);
    $billing_license_factor_2 = $_POST['billing_license_factor_2'];
    update_option('billing_license_factor_2', $billing_license_factor_2);
    $billing_license_factor_3 = $_POST['billing_license_factor_3'];
    update_option('billing_license_factor_3', $billing_license_factor_3);

    header("Refresh:0");
}


if(isset($_POST['export'])){
    //GET ALL THE DATA
    $total_sold = get_total_product_sales();
    $best_sold_product = get_topseller();
    $total_returns = get_option('total_returns');
    $most_used_reason = get_most_used_reason();

    $google = 400;
    $facebook = 560;
    $flyer_qr = 75;
    $clients_from_qr = 14;
    $advice = 19;

    $total_current_stock = get_total_current_stock();
    $licence_fee = calculate_license_fee();

    $billing_total_sold = get_option('billing_total_sold');
    $billing_best_sold_product = get_option('billing_best_sold_product');
    $billing_total_returns = get_option('billing_total_returns');
    $billing_total_most_used_reason = get_option('billing_total_most_used_reason');
    $billing_google = get_option('billing_google');
    $billing_facebook = get_option('billing_facebook');
    $billing_flyer_qr = get_option('billing_flyer_qr');
    $billing_clients_from_qr = get_option('billing_clients_from_qr');
    $billing_advice = get_option('billing_advice');
    $billing_total_current_stock = get_option('billing_total_current_stock');
    $billing_licence_fee = get_option('billing_licence_fee');

    create_billing_document($total_sold, $best_sold_product, $total_returns,$most_used_reason,
    $google, $facebook, $flyer_qr, $clients_from_qr,$advice,$total_current_stock, $licence_fee,
    $billing_total_sold, $billing_best_sold_product, $billing_total_returns,
    $billing_total_most_used_reason,$billing_google,$billing_facebook,$billing_flyer_qr,
    $billing_clients_from_qr,$billing_advice,$billing_total_current_stock,$billing_licence_fee);

    $num = get_option('billing_document_num');
    $num = (int)$num - 1;
    $subject = 'Vetriebsbericht (Sofort Export)';
    $message = "Vetriebsbericht befindet sich im Anhang";
    $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/billing_documents/billing_document_$num.pdf");
    $header = 'Babytuch.ch <myname@mydomain.com>' . "\r\n";
    $babytuch_admin_email = get_option('babytuch_admin_email');
    wp_mail( $babytuch_admin_email, $subject, $message, $header, $attach);
    header("Refresh:0");
}

function calculate_license_fee(){
    $total_sales = get_total_product_sales();
    $billing_license_lower_limit = (int)get_option('billing_license_lower_limit');
    $billing_license_upper_limit = (int)get_option('billing_license_upper_limit');
    $billing_license_factor_1 = (int)get_option('billing_license_factor_1');
    $billing_license_factor_2 = (int)get_option('billing_license_factor_2');
    $billing_license_factor_3 = (int)get_option('billing_license_factor_3');
    $license_fee = 0;

    if($total_sales <= $billing_license_lower_limit){
        $license_fee = $total_sales*$billing_license_factor_1;
    }elseif($total_sales > $billing_license_upper_limit){
        $license_fee = ($billing_license_lower_limit*$billing_license_factor_1)
        +(($billing_license_upper_limit-$billing_license_lower_limit)*$billing_license_factor_2)
        +(($total_sales-$billing_license_upper_limit)*$billing_license_factor_3);
    }else{
        $license_fee = ($billing_license_lower_limit*$billing_license_factor_1)
        +(($total_sales-$billing_license_lower_limit)*$billing_license_factor_2);
    }

    return $license_fee;
}

function get_total_product_sales(){
    $total = 0;
    $products = wc_get_products( array('numberposts' => -1) );
    foreach($products as $product){
        if($product->is_type( 'variable' )){
            $product_single = wc_get_product($product);
            $name = $product_single->get_slug();
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                $product_single = wc_get_product($product);
                $children   = $product_single->get_children();
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    $child_sales= get_child_sales($child_name);
                    $total = $total + $child_sales;
                }
            }
        }
    }
    return $total;
}

function get_total_current_stock(){
    $total = 0;
    $products = wc_get_products( array('numberposts' => -1) );
    foreach($products as $product){
        $product_single = wc_get_product($product);
        if($product_single->is_type( 'variable' )){
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                $child = $children[$i];
                $child_product = wc_get_product($child);
                $total_sales = $child_product->get_stock_quantity();
                $total = $total + $total_sales;
            }
        }
    }
    return $total;
}

function get_topseller(){
    $top = array(
        'name' => '',
        'amount' => 0
    );
    $products = wc_get_products( array('numberposts' => -1) );
    foreach($products as $product){
        $product_single = wc_get_product($product);
        if($product_single->is_type( 'variable' )){
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                $child = $children[$i];
                $child_product = wc_get_product($child);
                $child_slug = $child_product->get_slug();
                $child_sales= get_child_sales($child_slug);
                $child_name = $child_product->get_name();
                if($child_sales>=$top["amount"]){
                    $top["amount"] = $child_sales;
                    $top["name"] = $child_name;
                }
            }
        }
    }
    return $top;
}

function get_most_used_reason(){
    $returns_reasons = get_option('returns_reasons');
    $most_used_reason = array();
    $max = 0;
    foreach($returns_reasons as $reason_pair){
      $amount = (int)$reason_pair["amount"];
      $reason = $reason_pair["reason"];
      if($amount >= $max){
          $most_used_reason = array(
            'reason' => $reason,
            'amount' => $amount
          );
          $max = $amount;
      }
    }
    return $most_used_reason;
}

function generator(){
    global $wpdb;
    //GENERATES PDF TO MAIL
    $billing_next_generated = get_option('billing_next_generated');
    if($billing_next_generated<date('Y-m-d',time())){  
        $billing_interval = get_option('billing_interval');
        $next_generated = date('Y-m-d', strtotime($billing_next_generated . " + $billing_interval months"));
    
        //GET ALL THE DATA
        $total_sold = get_total_product_sales();
        $best_sold_product = get_topseller();
        $total_returns = get_option('total_returns');
        if(!$total_returns){
            $total_returns = 0;
        }
        $most_used_reason = get_most_used_reason();

        $google = 400;
        $facebook = 560;
        $flyer_qr = 75;
        $clients_from_qr = get_option('clients_from_qr');
        if(!$clients_from_qr){
            $clients_from_qr = 0;
        }
        $advice = 19;

        $total_current_stock = get_total_current_stock();
        $licence_fee = calculate_license_fee();

        $billing_total_sold = get_option('billing_total_sold');
        $billing_best_sold_product = get_option('billing_best_sold_product');
        $billing_total_returns = get_option('billing_total_returns');
        $billing_total_most_used_reason = get_option('billing_total_most_used_reason');
        $billing_google = get_option('billing_google');
        $billing_facebook = get_option('billing_facebook');
        $billing_flyer_qr = get_option('billing_flyer_qr');
        $billing_clients_from_qr = get_option('billing_clients_from_qr');
        $billing_advice = get_option('billing_advice');
        $billing_total_current_stock = get_option('billing_total_current_stock');
        $billing_licence_fee = get_option('billing_licence_fee');
        
        create_billing_document($total_sold, $best_sold_product, $total_returns,$most_used_reason,
        $google, $facebook, $flyer_qr, $clients_from_qr,$advice,$total_current_stock, $licence_fee,
        $billing_total_sold, $billing_best_sold_product, $billing_total_returns,
        $billing_total_most_used_reason,$billing_google,$billing_facebook,$billing_flyer_qr,
        $billing_clients_from_qr,$billing_advice,$billing_total_current_stock,$billing_licence_fee);

        $num = get_option('billing_document_num');
        $num = (int)$num - 1;
        $subject = 'Vetriebsbericht';
        $message = "Nächste Abrechnung am: $next_generated";
        $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/billing_documents/billing_document_$num.pdf");
        $header = 'Babytuch.ch <myname@mydomain.com>' . "\r\n";
        $babytuch_admin_email = get_option('babytuch_admin_email');
        wp_mail( $babytuch_admin_email, $subject, $message, $header, $attach);

        //SECOND E-MAIL------>BABYTUCH.COM
        /*$billing_second_address = get_option('billing_second_address');
        if($billing_second_address){
           wp_mail( $billing_second_address, $subject, $message, $header, $attach); 
        }
        */
        $billing_quarter = get_option('billing_quarter');
        if((int)$billing_quarter==4){
            $billing_quarter = 0;
        }
        update_option('billing_quarter', (int)$billing_quarter + 1);

        update_option('billing_total_sold', $total_sold);
        update_option('billing_best_sold_product', $best_sold_product);
        update_option('billing_total_returns', $total_returns);
        update_option('total_returns', 0);
        update_option('billing_total_most_used_reason', $most_used_reason);
        update_option('billing_google', $google);
        update_option('billing_facebook',  $facebook);
        update_option('billing_flyer_qr', $flyer_qr);
        update_option('billing_clients_from_qr', $clients_from_qr);
        update_option('clients_from_qr', 0);
        update_option('billing_advice', $advice);
        update_option('billing_total_current_stock', $total_current_stock);
        update_option('billing_licence_fee', $licence_fee);

        update_option('billing_last_generated', date('Y-m-d',time()));
        update_option('billing_next_generated', $next_generated);
        header("Refresh:0");
    }
   
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
        if($stock_old != '0'){
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
    $num_weeks = (int)datediff('ww', $last_check, $next_order, false);
    if((int)$current_amount<(int)$limit or 
        (int)$current_amount-($num_weeks*$average)<(int)$limit){
        return 'LightCoral';
    }
    if((int)$current_amount-(int)round($average)<=(int)$limit){
        return 'SandyBrown';
    }
    return 'lightgreen';
}

function get_child_sales($child_name){
    global $wpdb;
    $res = $wpdb->get_results( 
        $wpdb->prepare( "
            SELECT * FROM babytuch_inventory
            WHERE item_name = %s", 
            $child_name
        ) 
    );
    $res_json = json_decode(json_encode($res), true);
    $total_sales=0;
    $billing_interval = (int)get_option('billing_interval');
    $billing_interval = round($billing_interval*4.34524);
    for($j=(int)$billing_interval; $j>1; $j--){
        $stock_old = $res_json[0]["stock_$j"];
        $k=$j-1;
        $stock_new = $res_json[0]["stock_$k"];
        if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
            $sales = (int)$stock_old - (int)$stock_new;  
            $total_sales = $total_sales + $sales;
        }
        if($j==2){
            $stock_old = $res_json[0]["stock_1"];
            $stock_new = $res_json[0]["amount"];
            if((int)$stock_new<=(int)$stock_old){
                $sales = (int)$stock_old - (int)$stock_new;  
                $total_sales = $total_sales + $sales;
            }        
        }
    }
    return $total_sales;
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

//Abrechnung erstellen
function create_billing_document($total_sold, $best_sold_product, $total_returns,$most_used_reason,
$google, $facebook, $flyer_qr, $clients_from_qr,$advice,$total_current_stock, $licence_fee,
$billing_total_sold, $billing_best_sold_product, $billing_total_returns,
$billing_total_most_used_reason,$billing_google,$billing_facebook,$billing_flyer_qr,
$billing_clients_from_qr,$billing_advice,$billing_total_current_stock,$billing_licence_fee){

    $home_url_full = get_home_url();
    $home_url = substr($home_url_full, 7);
	
    $home_path = get_home_path();
    require_once($home_path.'/wp-content/plugins/babytuch-plugin/assets/TCPDF-master/tcpdf.php');

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', 'A4', true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Babytuch Schweiz');
    $pdf->SetTitle('Vertriebsbericht');
    $pdf->SetSubject('Vertriebsbericht');
    $pdf->SetKeywords('vertriebsbericht, lieferung, lieferaddresse, produktinfo');

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
	
    $pdf->AddPage();

    $city = get_option('woocommerce_store_city');
    $zip = get_option('woocommerce_store_postcode');
    $street = get_option('woocommerce_store_address');
    $date = date("d. m. Y");
    $quarter = get_option('billing_quarter');
    $initials_aut = get_option('initials_aut');
    $lname_aut = get_option('lname_aut');
    $fname_aut = get_option('fname_aut');
    $street_aut = get_option('street_aut');
    $zip_aut = get_option('zip_aut');
    $city_aut = get_option('city_aut');
    $year = date("Y");
    $billing_second_address = get_option('billing_second_address');

    $html = '<div>Babytuch(Schweiz) GmbH</div>';
    $html .= "<div>$street</div>";
    $html .= "<div>$zip $city</div>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<div>per E-Mail ($billing_second_address)</div>";
    $html .= "<div>$initials_aut</div>";
    $html .= "<div>$fname_aut $lname_aut</div>";
    $html .= "<div>$street_aut</div>";
    $html .= "<div>$zip_aut $city_aut</div>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<div>$city, $date</div>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";

	$pdf->writeHTML($html, true, 0, true, 0);

	$html = "<h3>Vertriebsbericht $year Q$quarter</h3>";

	$pdf->writeHTML($html, true, 0, true, 0);


	$pdf->SetXY(8, 115);

    // Set some content to print
    $total_sold_percentage = calc_percentage($total_sold, $billing_total_sold);
    $best_sold_product_string = $best_sold_product["name"].' ('.$best_sold_product["amount"].')';
    $billing_best_sold_product_string = $billing_best_sold_product["name"].'('.$billing_best_sold_product["amount"].')';
    $total_returns_percentage = calc_percentage($total_returns, $billing_total_returns);
    $most_used_reason_string = $most_used_reason["reason"].' ('.$most_used_reason["amount"].')';
    $billing_total_most_used_reason = $billing_total_most_used_reason["reason"].' ('.$billing_total_most_used_reason["amount"].')';
    $google_perc = calc_percentage($google, $billing_google);
    $facebook_perc = calc_percentage($facebook, $billing_facebook);
    $flyer_qr_perc = calc_percentage($flyer_qr, $billing_flyer_qr);
    $clients_from_qr_perc = calc_percentage($clients_from_qr, $billing_clients_from_qr);
    $advice_perc = calc_percentage($advice, $billing_advice);
    $total_current_stock_perc = $total_current_stock-$billing_total_current_stock;
    $licence_fee_perc = calc_percentage($licence_fee, $billing_licence_fee);

    $html = "<div>Sehr geehrte $initials_aut $lname_aut</div><br>";
    $html .= "<div>Gerne stellen wir Ihnen den Vertriebsbericht zum vergangenen Quartal mit Vergleichszahlung zum
    Vorquartal <br> zu:</div><br><br>";

    $html .= "<div><b>Absatz:</b></div><br>";
    $html .= '<table>
            <thead>
                <tr>
                    <th colspan="4" style="height:0px;"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="height:25px;width:350px;">Anzahl verkaufter Babytücher</td>
                    <td style="width:120px;">'.$total_sold.'</td>
                    <td style="width:120px;">'.$billing_total_sold.'</td>
                    <td style="width:120px;">'.$total_sold_percentage.'</td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;">Meistverkauftes Modell</td>
                    <td>'.$best_sold_product_string.'</td>
                    <td>'.$billing_best_sold_product_string.'</td>
                    <td></td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;">Anzahl Rücksendungen</td>
                    <td style="width:120px;">'.$total_returns.'</td>
                    <td style="width:120px;">'.$billing_total_returns.'</td>
                    <td style="width:120px;">'.$total_returns_percentage.'</td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;">Meistgenannter Grund </td>
                    <td style="width:120px;">'.$most_used_reason_string.'</td>
                    <td style="width:120px;">'.$billing_total_most_used_reason.'</td>
                    <td style="width:120px;"></td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;"><b>Logistik: </b></td>
                    <td style="width:120px;"></td>
                    <td style="width:120px;"></td>
                    <td style="width:120px;"></td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;">Babytücher an Lager</td>
                    <td style="width:120px;">'.$total_current_stock.'</td>
                    <td style="width:120px;">'.$billing_total_current_stock.'</td>
                    <td style="width:120px;">'.$total_current_stock_perc.'</td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;"><b>Finanzielles:</b></td>
                    <td style="width:120px;"></td>
                    <td style="width:120px;"></td>
                    <td style="width:120px;"></td>
                </tr>
                <tr>
                    <td style="height:25px;width:350px;">zu überweisende Lizenzgebühr (EUR)</td>
                    <td style="width:120px;">'.$licence_fee.'</td>
                    <td style="width:120px;">'.$billing_licence_fee.'</td>
                    <td style="width:120px;">'.$licence_fee_perc.'</td>
                </tr>
            </tbody>
        </table><br><br><br><br>';


    $html .= "<div>Freundliche Grüsse</div>";
    $html .= "<div>Babytuch (Schweiz) GmbH</div>";
    $html .= "<div>Markus M. Müller, Geschäftsführer</div><br>";
    


    $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
    


    // ---------------------------------------------------------
    ob_end_clean();

    $billing_document_num = get_option('billing_document_num');
    if(empty($billing_document_num)){
        update_option('billing_document_num', 1);
        $billing_document_num = 1;
    }
    update_option('billing_document_num', (int)$billing_document_num+1);

    
    $home_path = get_home_path();
    $path = $home_path.'/wp-content/plugins/babytuch-plugin/billing_documents/';
    $pdf->Output($path."billing_document_$billing_document_num.pdf", 'F');
}
?>

<?php

function calc_percentage($total_sold, $billing_total_sold){
    if((int)$billing_total_sold==0){
        return '+'.($total_sold*100).'%';
    }
    $ratio = $total_sold/$billing_total_sold;
    if($ratio>=1){
        $ratio = 100*(round($ratio - 1,2));
        $ratio = '+'.$ratio.'%';
    }else{
        $ratio = 100*(round(1-$ratio,2));
        $ratio = '-'.$ratio.'%';
    }
    return $ratio;
}

/*
 $html .= '<table style="border: 1px solid black;">
    <tr>
        <th style="width:200px; height:35px;">Bestand</th>
        <th colspan="12">Grössen</th>
    </tr>
    <tr>
        <th>&nbsp; <!-- EMPTY --></th>';
        $products = wc_get_products( array('numberposts' => -1) );
        $product = $products[0];
        $product_single = wc_get_product($product);
        $children   = $product_single->get_children();
        $num = count($children);
        for($i=0; $i<$num;$i++){
            $html .= '<th style="width:25px; height:35px;">'.$i.'</th>';
        }
        $html .="</tr>";

        foreach($products as $product){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $html .="<tr>";
            $name = $product_single->get_slug();
            $html .='<th style="height:35px;">'.$name.'</th>';
            for($i=0; $i<$num;$i++){
                
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $stock = $child_product->get_stock_quantity();
                    $color=check_status($child_product);
                    $html .= '<td style="text-align: center; background-color:'.$color.';">'.$stock.'</td>';
                }
            }
            $html .= "</tr>";
        }
        
    $html .= "</table><br>"; 
    */



    /**
     * VERTRIEBSKANÄLE (ZUKUNFT)
     * 
     * 
     * 
     */
   /* <tr>
    <td style="height:25px;width:350px;"><b>Vertriebskanäle: </b></td>
    <td style="width:120px;"></td>
    <td style="width:120px;"></td>
    <td style="width:120px;"></td>
</tr>
<tr>
    <td style="height:25px;width:350px;">Google (Klicks)</td>
    <td style="width:120px;">'.$google.'</td>
    <td style="width:120px;">'.$billing_google.'</td>
    <td style="width:120px;">'.$google_perc.'</td>
</tr>
<tr>
    <td style="height:25px;width:350px;">Facebook (Klicks)</td>
    <td style="width:120px;">'.$facebook.'</td>
    <td style="width:120px;">'.$billing_facebook.'</td>
    <td style="width:120px;">'.$facebook_perc.'</td>
</tr>
<tr>
    <td style="height:25px;width:350px;">Flyer (aktivierte QR-Codes)</td>
    <td style="width:120px;">'.$flyer_qr.'</td>
    <td style="width:120px;">'.$billing_flyer_qr.'</td>
    <td style="width:120px;">'.$flyer_qr_perc.'</td>
</tr>
<tr>
    <td style="height:25px;width:350px;">Vermittlungsprogramm Kunden</td>
    <td style="width:120px;">'.$clients_from_qr.'</td>
    <td style="width:120px;">'.$billing_clients_from_qr.'</td>
    <td style="width:120px;">'.$clients_from_qr_perc.'</td>
</tr>
<tr>
    <td style="height:25px;width:350px;">Vermittlungsprogramm Trageberatung</td>
    <td style="width:120px;">'.$advice.'</td>
    <td style="width:120px;">'.$billing_advice.'</td>
    <td style="width:120px;">'.$advice_perc.'</td>
</tr>*/