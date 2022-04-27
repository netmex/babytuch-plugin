<?php


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

    <h3>Lagerhaltung / Supply-Chain-Management</h3>
    <p>Die Lagerhaltung verwaltet die Bestände der Tücher und des Verpackungsmaterials. Bei jedem Aufruf dieser Seite wird automatisch geprüft, ob ein Check oder eine Nachbestellung fällig ist und führt diese durch.
</p>

    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=babytuch_supply_chain" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Babytücher</a>
            <a href="?page=babytuch_supply_chain&tab=product_amounts" class="nav-tab <?php if($tab==='product_amounts'):?>nav-tab-active<?php endif; ?>">Bestand Babytücher</a>
        <a href="?page=babytuch_supply_chain&tab=supplements" class="nav-tab <?php if($tab==='supplements'):?>nav-tab-active<?php endif; ?>">Verpackungsmaterial</a>
        <a href="?page=babytuch_supply_chain&tab=supplements_settings" class="nav-tab <?php if($tab==='supplements_settings'):?>nav-tab-active<?php endif; ?>">Einstellungen Verpackungsmaterial</a>
        <a href="?page=babytuch_supply_chain&tab=check_settings" class="nav-tab <?php if($tab==='check_settings'):?>nav-tab-active<?php endif; ?>">Einstellungen Checks</a>
        <a href="?page=babytuch_supply_chain&tab=manual_reorder" style="color:lightcoral;" class="nav-tab <?php if($tab==='manual_reorder'):?>nav-tab-active<?php endif; ?>">Manuelle Nachbestellung/Korrekturen</a>
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
    case 'supplements':
        global $wpdb;
        $res = $wpdb->get_results("
                SELECT * FROM babytuch_inventory 
                WHERE item_name = 'packagings' OR item_name = 'labels' 
                OR item_name = 'supplements' OR item_name = 'packagings_large'
                OR item_name = 'referral_labels'"
            );
        $res_json = json_decode(json_encode($res), true);
      ?>
    <form method="post" action="">
    <h3>Verpackungsmaterial Lagermanagement</h3>
    <p>Im Tab Verpackungsmaterial befinden sich die aktuellen Bestände, den Bedarf, die Limiten und die Nachbestellmenge der Verpackungsmaterialien. Die Farben sowie die Tabellen entsprechen dem gleichen Schema wie bei den Babytüchern.
</p>
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
                        if($name=='packagings'){
                            $name='Verpackungen';
                            $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
                        }elseif($name=='labels'){
                            $name='Klebeetiketten-Blätter';
                            $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/label.PNG';
                        }elseif($name=='supplements'){
                            $name='Beilagen';
                            $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/suppl.PNG';
                        }elseif($name=='packagings_large'){
                            $name='Verpackungen (Gross)';
                            $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
                        }elseif($name=='referral_labels'){
                            $name='Vermittlungsbögen';
                            $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/referral_label.PNG';
                        }
                       
                        if($img_url){
                            echo " <th style='width:100px;'>
                            <img src=$img_url width='80px'/>$name</th>";
                        }else{
                            echo " <th style='width:100px;'>$name</th>";
                        }
                    }
                    echo"</tr>";
                    
                    echo"<tr>";
                    echo"<th style='height:50px;'></th>";
                    foreach($res_json as $supplement){
                        $new_order_sending = $supplement["new_order_sending"];
                        $stock = $supplement["amount"];
                        $color = check_status_supplements($supplement);
                        if($new_order_sending==1){
                            echo " <td style='text-align: center; background-color:$color;'>$stock <b>(B)</b></td>";
                        }else{
                            echo " <td style='text-align: center; background-color:$color;'>$stock</td>";
                        }
                    }
                    echo"</tr>";
                    
                    
                ?>
        </table>
        <br><br>
        <table style="border: 1px solid black;">
            <tr>
                <th style="width:100px;">Bedarf bis zur nächsten Nachbestellung am <?php echo get_option('products_next_reorder');?></th>
                <th colspan="12">Beilagen</th>
            </tr>
            <tr>
                <th>&nbsp; <!-- EMPTY --></th>
                <?php
                    foreach($res_json as $supplement){
                        $name = $supplement["item_name"];
                        if($name=='packagings'){
                            $name='Verpackungen';
                        }elseif($name=='labels'){
                            $name='Klebeetiketten-Blätter';
                        }elseif($name=='supplements'){
                            $name='Beilagen';
                        }elseif($name=='packagings_large'){
                            $name='Verpackungen (Gross)';
                        }elseif($name=='referral_labels'){
                            $name='Vermittlungsbögen';
                        }
                        echo " <th style='width:100px;'>$name</th>";
                    }
                    echo"</tr>";
                    
                    echo"<tr>";
                    echo"<th style='height:50px;'></th>";
                    foreach($res_json as $supplement){
                        $fall_rate_sup = get_average_decrease_supplement($supplement);
                        echo " <td style='text-align:center;'>$fall_rate_sup</td>";
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
                        if($name=='packagings'){
                            $name='Verpackungen';
                        }elseif($name=='labels'){
                            $name='Klebeetiketten-Blätter';
                        }elseif($name=='supplements'){
                            $name='Beilagen';
                        }elseif($name=='packagings_large'){
                            $name='Verpackungen (Gross)';
                        }elseif($name=='referral_labels'){
                            $name='Vermittlungsbögen';
                        }
                        echo " <th style='width:100px;'>$name</th>";
                    }
                    echo"</tr>";
                    
                    echo"<tr>";
                    echo"<th style='height:50px;'></th>";
                    foreach($res_json as $supplement){
                        $stock = $supplement["new_order_limit"];
                        $name = $supplement["item_name"];
                        echo ' <td style="text-align: center;">
                        <input type="text" style="width: 40px; text-align: right;" class="regular-text"
                        name="'.$name.'_new_order_limit2"
                        value="'.$stock.'" placeholder="'.$stock.'">
                        </td>';
                    }
                    echo"</tr>";
                    
                    
                ?>
        </table>
        <br>
        <input type="submit" value="Reserven speichern" name="save_limits_supplements"> Speichert alle Felder der Reserven/Limiten.
        <br><br>
        <br>
    </form>
    <?php
    break;
    case 'manual_reorder':
        ?>
      <form method="post" action="">
        <h3>Manuelle Nachbestellung/Korrekturen</h3>
        <p>Im Tab Manuelle Nachbestellung/Korrekturen kann man notfallmässig oder wenn man selbst eine Bestellung machen möchte beliebige Mengen aller Produkte und Verpackungsmaterialien machen. => Nur verwenden bei Fehlern im normalen Nachbestellprozess oder bei Wünschen. Der Knopf "Nachbestellung generieren" aktualisiert alle Nachbestellmengen und es wird ein neues Dokument für die Eingangskontrolle generiert. Bitte dieses dann der Logistik weiterleiten.
</p>
        <p style="color:lightcoral;">Nur verwenden bei Fehlern im normalen Nachbestellprozess oder bei Wünschen. Der Knopf "Nachbestellung generieren" aktualisiert alle Nachbestellmengen und es wird ein neues Dokument für die Eingangskontrolle generiert. Bitte dieses dann der Logistik weiterleiten.</p>
        <p style="color:darkred;">ACHTUNG!: Überschreibt alle pendenten Nachbestellungen. Bitte alle Mengen berücksichtigen, die zu bestellen sind.</p>
        <?php
        global $wpdb;
        $res = $wpdb->get_results("
                SELECT * FROM babytuch_inventory 
                WHERE item_name = 'packagings' OR item_name = 'labels' 
                OR item_name = 'supplements' OR item_name = 'packagings_large'
                OR item_name = 'referral_labels'"
            );
        $res_json = json_decode(json_encode($res), true);
        ?>
        <br>
        <table style="border: 1px solid black;">
            <tr>
                <th style="width:100px;">Nachbestellmengen</th>
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
                            $product_single = wc_get_product($product);
                            $children   = $product_single->get_children();
                            if($product_single->is_type( 'variable' )){
                                $child = $children[$i];
                                $child_product = wc_get_product($child);
                                $child_name = $child_product->get_slug();
                                echo ' <td style="text-align: center;">
                                <input type="text" style="width: 40px; text-align: right;" class="regular-text"
                                name="'.$child_name.'_new_reorder_amount"
                                value="0" placeholder="0">
                                </td>';
                            }
                        }
                        echo"</tr>";
                    }
                    
                ?>
        </table>
        <br>
        <table style="border: 1px solid black;">
            <tr>
                <th style="width:100px;">Nachbestellmengen (Anzahl Verpackungen)</th>
                <th colspan="12">Beilagen</th>
            </tr>
            <tr>
                <th>&nbsp; <!-- EMPTY --></th>
                <?php
                    foreach($res_json as $supplement){
                        $name = $supplement["item_name"];
                        if($name=='packagings'){
                            $name='Verpackungen';
                        }elseif($name=='labels'){
                            $name='Klebeetiketten-Blätter';
                        }elseif($name=='supplements'){
                            $name='Beilagen';
                        }elseif($name=='packagings_large'){
                            $name='Verpackungen (Gross)';
                        }elseif($name=='referral_labels'){
                            $name='Vermittlungsbögen';
                        }
                        echo " <th style='width:100px;'>$name</th>";
                    }
                    echo"</tr>";
                    
                    echo"<tr>";
                    echo"<th style='height:50px;'></th>";
                    foreach($res_json as $supplement){
                        $name = $supplement["item_name"];
                        echo ' <td style="text-align: center;">
                        <input type="text" style="width: 40px; text-align: right;" class="regular-text"
                        name="'.$name.'_new_reorder_amount"
                        value="0" placeholder="0">
                        </td>';
                    }
                    echo"</tr>";

                    echo"<tr>";
                    echo"<th style='height:50px;'></th>";
                    foreach($res_json as $supplement){
                        $vari = $supplement["reorder_multiple"];
                        echo "<td style='text-align: center;'>
                        (Verpackungen à <b>$vari</b> Stück)
                        </td>";
                    }
                    echo"</tr>";
                    
                    
                ?>
        </table>
        <br>
        <input type="submit" value="Nachbestellung generieren" name="submit_manual_reorder"> Speichert alle Felder und generiert neue Nachbestellung
      </form>
      <?php
      break;
    case 'supplements_settings':
        ?>
      <form method="post" action="">
        <h3>Aktuelle Mengen des Verpackungsmaterials</h3>
        <p>Im Tab Einstellungen Verpackungsmaterial kann man die aktuellen Bestände direkt des Verpackungsmaterials direkt einstellen. Ausserdem kann man für jedes Verpackungsmaterial die Menge an Einheiten pro Bestellung angeben, welches für die Berechnung der Nachbestellmenge benötigt wird (Auf bzw. Abrundung). 
</p><br>
        <?php
        global $wpdb;
        $res = $wpdb->get_results("
                SELECT * FROM babytuch_inventory 
                WHERE item_name = 'packagings' OR item_name = 'labels' 
                OR item_name = 'supplements' OR item_name = 'packagings_large'
                OR item_name = 'referral_labels'"
            );
        $res_json = json_decode(json_encode($res), true);
    

        $small_package_limiter = get_option('small_package_limiter');
        $amount_normal = $res_json[0]["amount"];
        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
        echo "<img src=$img_url width='70px'/>";
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='amount_normal' value=$amount_normal> Bestand Normale Verpackungen (bis zu $small_package_limiter Tücher)</p>";      
        $reorder_multiple_normal = $res_json[0]["reorder_multiple"];
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='reorder_multiple_normal' value=$reorder_multiple_normal> Verpackungsmenge (für die Berechnung der Nachbestellmenge)</p>";
        echo"<br><br>";
        $amount_normal_large = $res_json[3]["amount"];
        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
        echo "<img src=$img_url width='100px'/>";
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='amount_normal_large' value=$amount_normal_large> Bestand Grosse Verpackungen (ab $small_package_limiter Tücher)</p>";
        $reorder_multiple_large = $res_json[3]["reorder_multiple"];
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='reorder_multiple_large' value=$reorder_multiple_large> Verpackungsmenge (für die Berechnung der Nachbestellmenge)</p>";
        echo"<br><br>";
        $amount_labels = $res_json[1]["amount"];
        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/label.PNG';
        echo "<img src=$img_url width='100px'/>";
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='amount_labels' value=$amount_labels> Bestand Klebeetiketten-Blätter</p>";
        $reorder_multiple_labels = $res_json[1]["reorder_multiple"];
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='reorder_multiple_labels' value=$reorder_multiple_labels> Verpackungsmenge (für die Berechnung der Nachbestellmenge)</p>";
        echo"<br><br>";
        $amount_referral_labels = $res_json[4]["amount"];
        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/referral_label.PNG';
        echo "<img src=$img_url width='100px'/>";
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='amount_referral_labels' value=$amount_referral_labels> Bestand Vermittlungsbögen</p>";
        $reorder_multiple_ref_labels = $res_json[4]["reorder_multiple"];
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='reorder_multiple_ref_labels' value=$reorder_multiple_ref_labels> Verpackungsmenge (für die Berechnung der Nachbestellmenge)</p>";
        echo"<br><br>";
        $amount_supplements = $res_json[2]["amount"];
        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/suppl.PNG';
        echo "<img src=$img_url width='100px'/>";
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='amount_supplements' value=$amount_supplements> Bestand Beilagen (1 Einheit = 1x Pflegehinweis)</p>";
        $reorder_multiple_supplements = $res_json[2]["reorder_multiple"];
        echo "<p><input type='text' style='width: 100px' class='regular-text'
        name='reorder_multiple_supplements' value=$reorder_multiple_supplements> Verpackungsmenge (für die Berechnung der Nachbestellmenge)</p>";
        echo"<br>";

        ?>
        <input type="submit" value="Speichern" name="submit_amounts_supplements"> Speichert alle Felder.
      </form>
      <?php
    break;
    case 'check_settings':
        ?>
      <form method="post" action="">
      <h3>Check Einstellungen</h3>
      <p>Im Tab Einstellungen Checks kann man die Daten der Checks einstellen.</p><p>
</p><br>
        <?php
        $products_last_check = get_option('products_last_check');
        echo "<p><input type='date' style='width: 150px' class='regular-text'
        name='products_last_check' value=$products_last_check> Letzter Check</p>";
        ?><p>Check prüft in regelmässigen Abständen den Bestand/Verbrauch und leitet 
        daraus die bei der nächsten Bestellung notwendige Anzahl Produkte her.</p><?php
        echo"<br>";
        $products_interval = get_option('products_interval');
        echo "<p><input type='text' style='width: 50px' class='regular-text'
        name='products_interval' value=$products_interval> Interval (in Tage) der Checks (aktualisiert die Bestandesverläufe und führt allenfalls eine Ausserordentliche Nachbestellung durch)</p>";
        ?><p>Tage bis zum nächsten Check (nicht Bestellung!). Standard: 7 Tage.</p><?php
        echo"<br>";
        $products_special_reorder_activated = get_option('products_special_reorder_activated');
        ?> <p><input type='checkbox' name='products_special_reorder_activated[]' 
        value='products_special_reorder_activated'
        <?php echo ((int)$products_special_reorder_activated==1 ? 'checked' : '');?>> 
        Ausserordentliche Nachbestellung aktiv<br>
        Aktiviert bzw. deaktiviert die Ausserordentliche Nachbestellung.</p>
        <?php
        echo"<br><br><br>";
        $products_last_reorder = get_option('products_last_reorder');
        echo "<p><input type='date' style='width: 150px' class='regular-text'
        name='products_last_reorder' value=$products_last_reorder> Letzte Bestellung</p>";
        ?><p>Datum der letzten Bestellung</p><?php
        echo"<br>";
        $products_next_reorder = get_option('products_next_reorder');
        echo "<p><input type='date' style='width: 150px' class='regular-text'
        name='products_next_reorder' value=$products_next_reorder> Nächste Bestellung</p>";
        ?><p>Wenn dieses Datum erreicht wurde, wird eine Nachbestellung ausgelöst mit 
            den berechneten Mengen aller Tücher und Verpackungsmaterial. Dies löst eine 
            Benachrichtigung aus mit einem PDF mit den Eingangskontrollen.</p><?php
        echo"<br>";
        $products_reorder_interval = get_option('products_reorder_interval');
        echo "<p><input type='text' style='width: 50px' class='regular-text'
        name='products_reorder_interval' value=$products_reorder_interval> Interval (in Monate) der Nachbestellungen </p>";
        ?><p>Monate zwischen Nachbestellungen.</p><?php
        echo"<br><br><br>";
        $products_reorder_multiple = get_option('products_reorder_multiple');
        echo "<p><input type='text' style='width: 50px' class='regular-text'
        name='products_reorder_multiple' value=$products_reorder_multiple> Menge pro Bestellung (auf das Vielfache dieses Wertes wird die Nachbestellung auf bzw. abgerundet)</p>";
        ?><p>Menge an Tüchern bestellbar (Standard: 100) 
            (noch nicht aktiv, zukünftige Weiterentwicklungsmöglichkeit)</p><?php
        echo"<br>";
        $products_round_limit = get_option('products_round_limit');
        echo "<p><input type='text' style='width: 50px' class='regular-text'
        name='products_round_limit' value=$products_round_limit> Aufrundungs-Limite für Babytücher (ab diesem Wert wird auf den nächsten $products_reorder_multiple-er aufgerundet)</p>";
        ?><p>ab diesem Wert wird auf den nächsten 100er gerundet. 
            Unter diesem Wert wird auf den unteren 100er gerundet.</p><?php
        echo"<br>";
        $products_test_mode = get_option('products_test_mode');
        $babytuch_admin_email = get_option('babytuch_admin_email');
        $mail_logistics_get = get_option('mail_logistics');
        ?> <p><input type='checkbox' name='products_test_mode[]' 
        value='products_test_mode'
        <?php echo ((int)$products_test_mode==1 ? 'checked' : '');?>> 
        Test-Modus<br> E-Mails werden nur dem Admin (<b><?php echo $babytuch_admin_email;?></b>) gesendet statt dem Admin und der Logistik (<b><?php echo $mail_logistics_get;?></b>)</p>
        <?php
        echo"<br>";
        ?>
        <input type="submit" value="Speichern" name="submit_common_settings"> Speichert alle Felder.
      </form>
    <?php
    break;
    case 'product_amounts':
        ?>
      <form method="post" action="">
      <h3>Bestand Babytücher</h3>
      <p>Im Tab Bestand Babytücher kann man die aktuellen Bestände der Babytücher einstellen.</p><p>
</p><br>
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
                                    $child_name = $child_product->get_slug();
                                    global $wpdb;
                                    $res = $wpdb->get_results( 
                                        $wpdb->prepare("
                                            SELECT * FROM babytuch_inventory
                                            WHERE item_name = %s", 
                                            $child_name
                                        ));
                                    $res_json = json_decode(json_encode($res), true);
                                    $stock = $child_product->get_stock_quantity();  
                                    echo ' <td style="text-align: center;">
                                    <input type="text" style="width: 40px; text-align: right;" 
                                    class="regular-text"
                                    name="'.$child_name.'_current_amount"
                                    value="'.$stock.'" placeholder="'.$stock.'">
                                    </td>';
                            
                                }
                            }
                        }
                        echo"</tr>";
                    }
                    
                ?>
        </table>
        <br>
        <input type="submit" value="Speichern" name="save_product_amounts"> Speichert alle Bestände.
        <br><br>
      </form>
    <?php
    break;
    default:
    perform_regular_check();
    ?>
    <form method="post" action="">
    <h3>Produkte Lagermanagement</h3>
    <p>Im Tab Babytücher wird eine Grafik generiert mit den aktuellen Beständen aller Tücher. Die Farbe bedeuten folgendes:
</p><p>
Grün: Bestand reicht aus bis zur nächsten Nachbestellung</p><p>
Orange: Limite wird unterschritten mit der aktuellen Abnahmerate innerhalb der nächsten Woche</p><p>
Rot: Limite ist unterschritten oder wird bis zur nächsten Nachbestellung unterschritten</p><p>
(B): Für dieses Produkt ist eine Nachbestellung pendent (es wurde bestellt, aber noch nicht erhalten)</p><p>

Unten kann man zudem auf die Bestandesverläufe klicken und die Limiten aller Produkte einstellen. In 2 weiteren Tabellen ist der Bedarf bis zur nächsten Nachbestellung ersichtlich und die aktuell berechnete Nachbestellmenge für 6 (bzw. kann eingestellt werden) weitere Monate.
</p>
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
                                    global $wpdb;
                                    $res = $wpdb->get_results( 
                                        $wpdb->prepare("
                                            SELECT * FROM babytuch_inventory
                                            WHERE item_name = %s", 
                                            $child_name
                                        ));
                                    $res_json = json_decode(json_encode($res), true);
                                    $stock = $child_product->get_stock_quantity();
                                    $color=check_status($child_product);
                                    $new_order_sending = $res_json[0]["new_order_sending"];
                                    if($new_order_sending==1){
                                        echo " <td style='text-align: center; background-color:$color;'>$stock <b>(B)</b></td>";
                                    }else{
                                        echo " <td style='text-align: center; background-color:$color;'>$stock</td>";
                                    }
                                }
                            }
                        }
                        echo"</tr>";
                    }
                    
                ?>
        </table>
        <br>
        
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
                <th style="width:100px;">Bedarf bis zur nächsten Nachbestellung am <?php echo get_option('products_next_reorder');?></th>
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
                        $fall_rate=10;
                        foreach($products as $product){
                            $product_single = wc_get_product($product);
                            $children   = $product_single->get_children();
                            if($product_single->is_type( 'variable' )){
                                $child = $children[$i];
                                $child_product = wc_get_product($child);
                                $child_name = $child_product->get_slug();
                                global $wpdb;
                                $res = $wpdb->get_results( 
                                    $wpdb->prepare("
                                        SELECT * FROM babytuch_inventory
                                        WHERE item_name = %s", 
                                        $child_name
                                    ));
                                $res_json = json_decode(json_encode($res), true);
                                $fall_rate = get_average_decrease($res_json);
                                echo ' <td style="text-align: center;">
                                '.$fall_rate.'
                                </td>';
                            }
                        }
                        echo"</tr>";
                    }
                    
                ?>
        </table>
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
                            $product_single = wc_get_product($product);
                            $children   = $product_single->get_children();
                            if($product_single->is_type( 'variable' )){
                                $child = $children[$i];
                                $child_product = wc_get_product($child);
                                $child_name = $child_product->get_slug();
                                $child_limit = get_child_limit($child_name);
                                echo ' <td style="text-align: center;">
                                <input type="text" style="width: 40px; text-align: right;"
                                class="regular-text"
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
        <input type="submit" value="Speichern" name="save_limits_products">Speichert alle Felder der Reserven/Limiten.
        <br><br>
    </form>
    <?php
    endswitch; ?>
    </div>
  </div>


<?php
if(isset($_POST['save_product_amounts'])){
    global $wpdb;
    $products = wc_get_products( array('numberposts' => -1) );
    foreach($products as $product){
        if($product->is_type( 'variable' )){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    $current_amount = $_POST[$child_name."_current_amount"];
                    $old_amount = $child_product->get_stock_quantity();
                    if((int)$old_amount!=(int)$current_amount){
                        $new_total = (float)$current_amount;
                        //var_dump($new_total);
                        //$child_product->set_stock_quantity($new_total);
                        $id = $child_product->get_id();
                        $value = $wpdb->query( 
                        $wpdb->prepare( "
                            UPDATE wp_postmeta SET meta_value = %s
                            WHERE post_id = %s and meta_key = '_stock' ", 
                            $new_total, $id
                        ) 
                        );
                        $value = $wpdb->query( 
                            $wpdb->prepare( "
                                UPDATE babytuch_inventory SET amount = %s
                                WHERE item_name = %s", 
                                $current_amount, $child_name
                            ) 
                        );
                    }
                    
                }
            }
        }
    }
    
    header("Refresh:0");
}

if(isset($_POST['save_limits_products'])){
    global $wpdb;
    $products = wc_get_products( array('numberposts' => -1) );
    foreach($products as $product){
        if($product->is_type( 'variable' )){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    $new_order_amount = $_POST[$child_name."_new_order_limit2"];
                    //var_dump($new_order_amount);
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
    }
    
    header("Refresh:0");
}

if(isset($_POST['submit_manual_reorder'])){
    global $wpdb;
    $value = $wpdb->query( 
        "UPDATE babytuch_inventory SET new_order_sending = '0', new_order_received = '0'" 
    ); 
    $products_to_reorder = array();
    $res = $wpdb->get_results("
                SELECT * FROM babytuch_inventory 
                WHERE item_name = 'packagings' OR item_name = 'labels' 
                OR item_name = 'supplements' OR item_name = 'packagings_large'
                OR item_name = 'referral_labels'"
    );
    $res_json = json_decode(json_encode($res), true);
    $today = date('Y-m-d');
    $msg = 'Manuelle Nachbestellung: ';
    foreach($res_json as $item){
        $name = $item["item_name"];
        if($name=='packagings'){
            $name2='Verpackungen';
        }elseif($name=='labels'){
            $name2='Klebeetiketten-Blätter';
        }elseif($name=='supplements'){
            $name2='Beilagen';
        }elseif($name=='packagings_large'){
            $name2='Verpackungen (Gross)';
        }elseif($name=='referral_labels'){
            $name2='Vermittlungsbögen';
        }
        $new_order_amount = $_POST[$name."_new_reorder_amount"];
        if((int)$new_order_amount>0){
            $vari = $item["reorder_multiple"];
            $value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET new_order_amount = %s,
                    new_order_sending = 1, new_order_received = 0,
                    new_order_sending_date = %s
                    WHERE item_name = %s", 
                    $new_order_amount, $today, $name
                ) 
            );    
            array_push($products_to_reorder, $item);  
            $msg .= "<br> Vom Verpackungsmaterial: $name2: <b>$new_order_amount</b> Verpackungen (à $vari Stück). <br>";
        }else{
            $value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET new_order_sending = 0, new_order_received = 0,
                    WHERE item_name = %s", 
                    $name
                ) 
            );    
        }        
    }

    $products = wc_get_products( array('numberposts' => -1) ); 
    foreach($products as $product){
        if($product->is_type( 'variable' )){
            $product_single = wc_get_product($product);
            $children   = $product_single->get_children();
            $num = count($children);
            for($i=0; $i<$num;$i++){
                if($product_single->is_type( 'variable' )){
                    $child = $children[$i];
                    $child_product = wc_get_product($child);
                    $child_name = $child_product->get_slug();
                    $name = $child_product->get_name();
                    $new_order_amount = $_POST[$child_name."_new_reorder_amount"];
                    if((int)$new_order_amount>0){
                        $value = $wpdb->query( 
                            $wpdb->prepare( "
                                UPDATE babytuch_inventory SET new_order_amount = %s,
                                new_order_sending = 1, new_order_received = 0,
                                new_order_sending_date = %s
                                WHERE item_name = %s", 
                                $new_order_amount, $today, $child_name
                            ) 
                        );  
                        array_push($products_to_reorder, $child_product);  
                        $msg .= "<br> Vom Produkt $name: <b>$new_order_amount</b> Stück. <br>";
                    }else{
                        $value = $wpdb->query( 
                            $wpdb->prepare( "
                                UPDATE babytuch_inventory SET new_order_sending = 0, new_order_received = 0,
                                WHERE item_name = %s", 
                                $child_name
                            ) 
                        );  
                    }     
                }
            }
        }
    }
    if(!empty($products_to_reorder)){
        generate_pdf($products_to_reorder);

        $num = get_option('reorder_document_num');
        $num = (int)$num - 1;
        $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/reorder_documents/reorder_document_$num.pdf");
        $header[] = 'Content-Type: text/html; charset=UTF-8';
        $babytuch_admin_email = get_option('babytuch_admin_email');
        $products_test_mode = get_option('products_test_mode');
        $mail_logistics_get = get_option('mail_logistics');
        if($products_test_mode==1){
            wp_mail($babytuch_admin_email, 'Manuelle Nachbestellung', $msg, $header, $attach);
        }else{
            $copy_to_admin = get_option('copy_to_admin');
            if($copy_to_admin){
                if($copy_to_admin==1){
                    $header[] = "Cc: $babytuch_admin_email";
                }
            }
            //wp_mail($babytuch_admin_email, '(AUSSERORDENTLICHE) Nachbestellung', $msg, $header, $attach);
            wp_mail($mail_logistics_get, 'Manuelle Nachbestellung', $msg, $header, $attach);
        }
        header("Refresh:0");
    }else{
        echo 'Keine Produkte nachbestellt.';
    }
    
}

if(isset($_POST['submit_amounts_supplements'])){
    global $wpdb;
   
    $amount_normal = $_POST["amount_normal"];
    $amount_normal_large = $_POST["amount_normal_large"];
    $amount_labels = $_POST["amount_labels"];
    $amount_supplements = $_POST["amount_supplements"];
    $amount_referral_labels = $_POST["amount_referral_labels"];
    $reorder_multiple_normal = $_POST["reorder_multiple_normal"];
    $reorder_multiple_large = $_POST["reorder_multiple_large"];
    $reorder_multiple_labels = $_POST["reorder_multiple_labels"];
    $reorder_multiple_ref_labels = $_POST["reorder_multiple_ref_labels"];
    $reorder_multiple_supplements = $_POST["reorder_multiple_supplements"];
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s, reorder_multiple = %s
            WHERE item_name = 'packagings'", 
            $amount_normal, $reorder_multiple_normal
        ) 
    ); 
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s, reorder_multiple = %s
            WHERE item_name = 'packagings_large'", 
            $amount_normal_large, $reorder_multiple_large
        ) 
    );   
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s, reorder_multiple = %s
            WHERE item_name = 'labels'", 
            $amount_labels, $reorder_multiple_labels
        ) 
    );   
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s, reorder_multiple = %s
            WHERE item_name = 'supplements'", 
            $amount_supplements, $reorder_multiple_supplements
        ) 
    ); 
    $value = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE babytuch_inventory SET amount = %s, reorder_multiple = %s
            WHERE item_name = 'referral_labels'", 
            $amount_referral_labels, $reorder_multiple_ref_labels
        ) 
    );                 
    header("Refresh:0");
}

if(isset($_POST['save_limits_supplements'])){
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

function get_average_decrease($res_json){
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
    //$products_reorder_interval = (int)get_option('products_reorder_interval');
    //$num_weeks = (int)round($products_reorder_interval*4.345);
    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
    if(count($decrease_rates)!=0){
        $average = array_sum($decrease_rates)/count($decrease_rates);
    }else{
        $average = 0;
    }
    return round($average*$num_weeks);
}

function get_average_decrease_supplement($supplement){
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

    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
    /**if($current_amount-($num_weeks*2*$average)>=0){
        return 'lightgreen';
    }**/
    if(count($decrease_rates)!=0){
        $average = array_sum($decrease_rates)/count($decrease_rates);
    }else{
        $average = 0;
    }
    return round($average*$num_weeks);
}

function check_status_supplements($supplement){
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
    //var_dump($average);
    $limit = $supplement["new_order_limit"];
    $current_amount = $supplement["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
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
    $products_reorder_interval = $_POST['products_reorder_interval'];
    update_option('products_reorder_interval', $products_reorder_interval);
    $products_round_limit = $_POST['products_round_limit'];
    update_option('products_round_limit', $products_round_limit);
    $products_reorder_multiple = $_POST['products_reorder_multiple'];
    update_option('products_reorder_multiple', $products_reorder_multiple);
    if(empty($_POST["products_special_reorder_activated"])){
        update_option('products_special_reorder_activated', 0);
    }else{
        update_option('products_special_reorder_activated', 1);
    }
    if(empty($_POST["products_test_mode"])){
        update_option('products_test_mode', 0);
    }else{
        update_option('products_test_mode', 1);
    }

    header("Refresh:0");
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
            AND item_name != 'supplements' AND item_name != 'packagings_large'
            AND item_name != 'referral_labels'"
        );
        $res_json = json_decode(json_encode($res), true);
        
        $products = wc_get_products( array('numberposts' => -1) );
        $all_current_slugs = array();
        foreach($products as $product){
            if($product->is_type( 'variable' )){
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
                OR item_name = 'supplements' OR item_name = 'packagings_large'
                OR item_name = 'referral_labels'"
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
        $products_special_reorder_activated = get_option('products_special_reorder_activated');
        $products_last_reorder = get_option('products_last_reorder');
        $products_next_reorder = get_option('products_next_reorder');
        $next_special_order_check = strtotime($products_last_reorder. " + 20 days");
        $next_special_order_check_date = date('Y-m-d',$next_special_order_check);
        if($next_special_order_check_date<date('Y-m-d',time()) and
        $products_next_check<date('Y-m-d',time()) and
        !($products_next_reorder<=date('Y-m-d',time())) and
        (int)$products_special_reorder_activated==1){
            $products_to_reorder = array();
            $products = wc_get_products( array('numberposts' => -1) );
            $msg = "(AUSSERORDENTLICHE) Nachbestellung mit den folgenden Produkten: <br>";
            $at_least_one = false;
            foreach($products as $product){
                if($product->is_type( 'variable' )){
                    $product_single = wc_get_product($product);
                    $children   = $product_single->get_children();
                    $num = count($children);
                    for($i=0; $i<$num;$i++){
                        if($product_single->is_type( 'variable' )){
                            $child = $children[$i];
                            $child_product = wc_get_product($child);
                            $attr = $child_product->get_attributes();
                            $size = $attr["groesse"];
                            $child_slug = $child_product->get_slug();
                            $name = $child_product->get_name();
                            $reorder_amount = get_reorder_amount($child_product);
                            $color=check_status($child_product);
                            global $wpdb;
                            $details = $wpdb->get_results( 
                                $wpdb->prepare("
                                    SELECT * FROM babytuch_inventory
                                    WHERE item_name = %s", 
                                    $child_slug
                                ));
                            $details_json = json_decode(json_encode($details), true);

                            if(($color == 'LightCoral' or 'SandyBrown' == $color) and
                                $details_json[0]["new_order_sending"]==0 and
                                $reorder_amount>0){
                                $at_least_one = true;
                                $msg .= "<br> Vom Produkt $name: (Grösse: $size) <b>$reorder_amount</b> Stück. <br>";
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
            }
            foreach($supplements_json as $supplement){
                $color=check_supplement_status($supplement);
                if($color == 'LightCoral' or 'SandyBrown' == $color){
                    $name = $supplement["item_name"];
                    if($name=='packagings'){
                        $name2='Verpackungen';
                    }elseif($name=='labels'){
                        $name2='Klebeetiketten-Blätter';
                    }elseif($name=='supplements'){
                        $name2='Beilagen';
                    }elseif($name=='packagings_large'){
                        $name2='Verpackungen (Gross)';
                    }elseif($name=='referral_labels'){
                        $name2='Vermittlungsbögen';
                    }
                    $reorder_amount = get_supplement_reorder_amount($supplement);
                    $vari = $supplement["reorder_multiple"];
                    $up = (float)$reorder_amount + (float)($vari/2);
                    $up -= fmod($up,$vari);
                    $at_least_one = true;
                    $up = $up/(int)$vari;
                    $msg .= "<br><br> Vom Verpackungsmaterial $name2: $up Verpackungen (à $vari Stück).<br>";
                    $today = date('Y-m-d h:i:s',time());
                    $test = $wpdb->get_results( 
                        $wpdb->prepare("
                            UPDATE babytuch_inventory SET last_special_order = %s,
                            new_order_sending = true, new_order_received = false, 
                            new_order_amount = %s
                            WHERE item_name = %s", 
                            $today,$up, $name
                        ));
                    array_push($products_to_reorder, $supplement);
                }
            }
            if($at_least_one){

                generate_pdf($products_to_reorder);

                $num = get_option('reorder_document_num');
                $num = (int)$num - 1;
                $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/reorder_documents/reorder_document_$num.pdf");

                $header[] = 'Content-Type: text/html; charset=UTF-8';
                $babytuch_admin_email = get_option('babytuch_admin_email');
                $mail_logistics_get = get_option('mail_logistics');
                $products_test_mode = get_option('products_test_mode');
                if($products_test_mode==1){
                    wp_mail($babytuch_admin_email, '(AUSSERORDENTLICHE) Nachbestellung', $msg, $header, $attach);
                }else{
                    $copy_to_admin = get_option('copy_to_admin');
                    if($copy_to_admin){
                        if($copy_to_admin==1){
                            $header[] = "Cc: $babytuch_admin_email";
                        }
                    }
                    //wp_mail($babytuch_admin_email, '(AUSSERORDENTLICHE) Nachbestellung', $msg, $header, $attach);
                    wp_mail($mail_logistics_get, '(AUSSERORDENTLICHE) Nachbestellung', $msg, $header, $attach);
                }
            }
        }
        
        update_option('products_last_check', date('Y-m-d',time()));
        header("Refresh:0");
    }

    //NEW ORDER
    $products_next_reorder = get_option('products_next_reorder'); //CHANGE TO _next_ !!!!
    if($products_next_reorder<=date('Y-m-d',time())){
        $products_round_limit = get_option('products_round_limit');
        $products = wc_get_products( array('numberposts' => -1) );
        $products_to_reorder = array();
        $total_reorder_amount = 0;
        $msg = "Nachbestellung mit den folgenden Produkten: <br>";
        foreach($products as $product){
            if($product->is_type( 'variable' )){
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
                $name = $product->get_name();
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
                        $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }elseif($new_total%100 != 0 and $new_total%100<10){
                        $diff = $new_total%100;
                        $reorder_amount = $reorder_amount - $diff;
                        $new_total = $new_total - $diff;
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }else{
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }
                }else{
                    update_db($reorder_amount, $child_slug);
                    $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
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
                $name = $product->get_name();
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
                        $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }elseif($new_total%100 != 0 and $new_total%100<10){
                        $diff = $new_total%100;
                        $reorder_amount = $reorder_amount - $diff;
                        $new_total = $new_total - $diff;
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }else{
                        update_db($reorder_amount, $child_slug);
                        $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
                    }
                }else{
                    update_db($reorder_amount, $child_slug);
                    $msg .= "<br> Vom Produkt $name (Grösse: $size): <b>$reorder_amount</b> Stück. <br>";
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
                OR item_name = 'supplements' OR item_name = 'packagings_large'
                OR item_name = 'referral_labels'"
            );
        $supplements_json = json_decode(json_encode($supplements), true);
        $msg .= "<br><br>Verpackungsmaterial: <br><br>";
        foreach($supplements_json as $supplement){
            $reorder_amount = get_supplement_reorder_amount($supplement);
            $vari = $supplement["reorder_multiple"];
            if($reorder_amount != 0 and $supplement["new_order_sending"]==0){
                $up = (float)$reorder_amount + (float)($vari/2);
                $up -= fmod($up,$vari);
                $up = $up/(int)$vari;
                $name = $supplement["item_name"];
                if($name=='packagings'){
                    $name2='Verpackungen';
                }elseif($name=='labels'){
                    $name2='Klebeetiketten-Blätter';
                }elseif($name=='supplements'){
                    $name2='Beilagen';
                }elseif($name=='packagings_large'){
                    $name2='Verpackungen (Gross)';
                }elseif($name=='referral_labels'){
                    $name2='Vermittlungsbögen';
                }
                $msg .= "<br> Vom Verpackungsmaterial $name2: $up Verpackungen (à $vari Stück).<br>";
                update_db($up, $name);
                array_push($products_to_reorder, $supplement);
            }
        }
        if(!empty($products_to_reorder)){

        
            generate_pdf($products_to_reorder);

            $products_reorder_interval = (int)get_option('products_reorder_interval');
            $next_reorder = date('Y-m-d', strtotime($products_next_reorder . " + $products_reorder_interval months"));
            update_option('products_last_reorder', date('Y-m-d',time()));
            update_option('products_next_reorder', $next_reorder);
            $num = get_option('reorder_document_num');
            $num = (int)$num - 1;
            $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/reorder_documents/reorder_document_$num.pdf");
            $msg .= "<br> Nächste Nachbestellung: $next_reorder";
            $header[] = 'Content-Type: text/html; charset=UTF-8';
            $babytuch_admin_email = get_option('babytuch_admin_email');
            $mail_logistics_get = get_option('mail_logistics');
            $products_test_mode = get_option('products_test_mode');
            if($products_test_mode==1){
                wp_mail($babytuch_admin_email, 'Halbjährige Nachbestellung', $msg, $header, $attach);
            }else{
                $copy_to_admin = get_option('copy_to_admin');
                if($copy_to_admin){
                    if($copy_to_admin==1){
                        $header[] = "Cc: $babytuch_admin_email";
                    }
                }
                //wp_mail($babytuch_admin_email, 'Halbjährige Nachbestellung', $msg, $header, $attach);
                wp_mail($mail_logistics_get, 'Halbjährige Nachbestellung', $msg, $header, $attach);
            }
       }
       header("Refresh:0");
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

    //$home_path = get_home_path();
    require_once(ABSPATH.'/wp-content/plugins/babytuch-plugin/assets/TCPDF-master/tcpdf.php');

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
            $name2 = substr($name, -3,-2);
            $name3 = substr($name, -4,-3);
            if($name2=='-'){
                $name=substr($name, 0,-4);
            }elseif($name3=='-'){
              $name=substr($name, 0,-5);
            }
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
            $prodid = $product->get_parent_id();
            $prod = wc_get_product($prodid);
            $attachment_ids = $prod->get_gallery_image_ids();
            if($attachment_ids){
               $img_url = wp_get_attachment_url($attachment_ids[0]);
            }
            //$size = substr($name, -1);
        }else{
            $details_json = $product;
            $name = $details_json["item_name"];
            $details = $wpdb->get_results( 
                $wpdb->prepare("
                    SELECT * FROM babytuch_inventory
                    WHERE item_name = %s", 
                    $name
                ));
            $details_json = json_decode(json_encode($details), true);
            if($name=='packagings'){
                $name='Verpackungen';
                $img2_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
            }elseif($name=='labels'){
                $name='Klebeetiketten-Blätter';
                $img2_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/label.PNG';
            }elseif($name=='supplements'){
                $name='Beilagen';
                $img2_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/suppl.PNG';
            }elseif($name=='packagings_large'){
                $name='Verpackungen (Gross)';
                $img2_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
            }elseif($name=='referral_labels'){
                $name='Vermittlungsbögen';
                $img2_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/referral_label.PNG';
            }

            $date = date('d.m.Y',strtotime($details_json[0]["new_order_sending_date"]));
            $amount = $details_json[0]["new_order_amount"];
            $code = $details_json[0]["receiving_code"];
            $vari = $details_json[0]["reorder_multiple"];
            $img_url = 'no';
        }
       
        
        $pdf->AddPage();
        $pdf->SetXY(10, 10);
        $html = '<h1 style="font-size:32px;">Empfangskontrolle</h1><br><br>';
        $html .= '<h1>Babytuch</h1><br><br><br><br>';
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->SetXY(10, 40);
        $html = '<h3>Absender:</h3><br>';
        $html .= '<h1>Babytuch</h1><br>';
        $html .= '<h1>Hopfgartenstrasse 10</h1><br>';
        $html .= '<h1>A - 5302 Henndorf a.W.</h1><br>';
        $html .= '<h1>Oestereich</h1><br><br>';
       
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->SetXY(10, 70);
        $html = "<br><br><h1>Bestellung vom $date</h1><br><br><br>";
       
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->SetXY(10, 100);
        $html = "<h1>Muster:</h1><br>";
        $html .= '<br><h1 style="font-size:48px;">'.$name.'</h1><br>';
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->SetXY(10, 130);
        if($img_url!='no'){
         
            $html = '<div style=" float: left; width: 33.33%; padding: 5px;">';
            $html .= '<img src="'.$img_url.'" width="120px" heigth="95px"/>';
            $html .= '</div>';
            
        }else{
            $html = '<div style=" float: left; width: 33.33%; padding: 5px;">';
            $html .= '<img src="'.$img2_url.'" width="120px" heigth="95px"/>';
            $html .= '</div>';
        }
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->SetXY(10, 200);
        $html = "<br>";
        if($img_url!='no'){
            $html .= '<br><h1 style="font-size:48px;">Grösse: <b>'.$size.'</b></h1><br><br>';
            $html .= '<h1 style="font-size:48px;">Menge:  <b>'.$amount.'</b></h1><br><br>';
        }else{
            $html .= '<h1 style="font-size:48px;">Menge:  <b>'.$amount.'</b></h1><br><br>';
            $html .= '<h2>Verpackungen à <b>'.$vari.'</b> Stück</h2><br><br>';
        }


        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

        $pdf->Text(150, 50, 'Lieferung entgegengenommen');
        $pdf->SetXY(180, 13);
       $api_qr ='
       <div style="color:white">__________________________________________________________________
   
       <img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=http%3A%2F%2F'.$home_url.'/eingang-nachbestellungen/?code='.$code.'&choe=UTF-8" title="Link to Google.com"
        width="130" height="130"/>';
   
       
       $pdf->writeHTML($api_qr, true, 0, true, 0);
        
    }
    
    // ---------------------------------------------------------
    ob_end_clean();

    $reorder_document_num = get_option('reorder_document_num');
    update_option('reorder_document_num', (int)$reorder_document_num+1);

    //$home_path = get_home_path();
    $path = ABSPATH.'/wp-content/plugins/babytuch-plugin/reorder_documents/';
    $pdf->Output($path."reorder_document_$reorder_document_num.pdf", 'F');
}

function get_supplement_reorder_amount($supplement){
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
    $limit = $supplement["new_order_limit"];
    $current_amount = $supplement["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $products_reorder_interval = (int)get_option('products_reorder_interval');
    $num_weeks = (int)round($products_reorder_interval*4.345)+2;
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
    //var_dump($average);
    $limit = $supplement["new_order_limit"];
    $current_amount = $supplement["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
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
    
    $limit = $res_json[0]["new_order_limit"];
    $current_amount = $res_json[0]["amount"];
    $next_order = get_option('products_next_reorder');
    //$num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
    //$num_weeks = $num_weeks+2;
    $products_reorder_interval = (int)get_option('products_reorder_interval');
    $num_weeks = (int)round($products_reorder_interval*4.345)+2;
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
    //var_dump($average);
    $limit = $res_json[0]["new_order_limit"];
    $current_amount = $res_json[0]["amount"];
    $last_check = get_option('products_last_check');
    $next_order = get_option('products_next_reorder');
    $num_weeks = (int)datediff('ww', date('Y-m-d',time()), $next_order, false);
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