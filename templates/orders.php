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
    <h3>Bestellungen</h3>
    <p>Bestellungen verwaltet die Bestellungen in verschiedenen Phasen des Bestellvorgangs. 
</p>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=babytuch_orders" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Rücksendungen</a>
      <a href="?page=babytuch_orders&tab=replace" class="nav-tab <?php if($tab==='replace'):?>nav-tab-active<?php endif; ?>">Umtausche</a>
        <a href="?page=babytuch_orders&tab=problems" class="nav-tab <?php if($tab==='problems'):?>nav-tab-active<?php endif; ?>">Problemfälle</a>
        <a href="?page=babytuch_orders&tab=payments" class="nav-tab <?php if($tab==='payments'):?>nav-tab-active<?php endif; ?>">Offene Zahlungen</a>
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'archive':
          ?>
          <form method="post" action="">
            <h2>Übersicht abgeschlossene Vermittlungen</h2>
     
            
          </form>
      <?php
        break;
        case 'problems':
            ?>
            <form method="post" action="">
              <h2>Problemfälle (nicht in Ordnung)</h2>
              <p>Im Tab Problemfälle befinden sich alle Rücksendungen und Umtausche, welche als nicht in Ordnung markiert worden sind. Sobald der Admin ein Problem gelöst hat, markiert er das entsprechende Problem und drückt den Knopf um alle Markierten abzuschließen.</p>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>E-Mail</th>
                        <th>IBAN</th>
                        <th>Status</th>
                        <th>Zustellungsdatum</th>
                        <th>Kosten</th>
                        <th>Rückerstattet</th>
                        <th>Umtausch</th>
                        <th>Versandkosten (x3)</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            global $wpdb;
                    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE (order_status = 'returning' OR order_status = 'wc-returning') 
                    AND return_received_activated = 1 AND refunded = 0
                    AND not_ok = 1"
                    );
                    $res_json = json_decode(json_encode($res), true);
                    foreach ($res_json as $res_single) :
                        $order_id = $res_single["order_id"];
                        $order_email = $res_single["order_email"];
                        $order_status = $res_single["order_status"];
                        $date_delivered = $res_single["date_delivered"];
                        $cost_of_sending = $res_single["cost_of_sending"];
                        $return_activated = $res_single["return_activated"];
                        $cost = $res_single["total_price"];
                        $is_replacement_order = $res_single["is_replacement_order"];
                        $refunded = $res_single["refunded"];; 
                        
                        $order = wc_get_order($order_id);

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
                        
                         $table_name2 = $wpdb->prefix . 'usermeta';
                         $ref_id = $wpdb->get_results( 
                             $wpdb->prepare( "
                                 SELECT meta_value FROM $table_name2
                                 WHERE user_id = %s AND meta_key='iban_num'", 
                                 $client_id
                             ) 
                         );
                         $ref_id_json = json_decode(json_encode($ref_id), true);
                         if($ref_id_json){
                            $iban = $ref_id_json[0]["meta_value"]; 
                         } else{
                             $iban = 'n.a.';
                         }
                        
                        ?>
                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo $order_email; ?></td>
                        <td><?php echo $iban; ?></td>
                        <td><?php echo $order_status; ?></td>
                        <td><?php echo $date_delivered; ?></td>
                        <td><?php echo $cost.' Fr.'; ?></td>
                        <td><?php if($refunded){echo 'Ja';}else{echo 'Nein';}; ?></td>
                        <td><?php if($is_replacement_order){echo 'Ja';}else{echo 'Nein';}; ?></td>
                        <td><?php if($cost_of_sending){echo $cost_of_sending;}else{echo 'n/a';}; ?></td>
                        <th><input type='checkbox' name='solved_problems[]' value="<?php echo $order_id?>"></th>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table><br>
            <input type="submit" value="Alle gelöst" name="problems_solved"> Sobald ein Problem gelöst wurde, können Sie den betroffenen Fall oben ankreuzen. Wenn alle angekreuzt sind, können Sie diese mit diesem Knopf hiermit bestätigen und abschliessen.
            </form>
        <?php
          break;
      case 'replace':
        ?>
        <form method="post" action="">
        <h2>Umtausche</h2>
        <p>Im Tab Umtausche sieht man alle Umtausche inklusive Angabe der Bestellnummer der neuen “Ersatzbestellung”.</p>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Zustellungsdatum</th>
                        <th>Ersatzbestellung</th>
                        <th>Rücksendung erhalten</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            global $wpdb;
                    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE (order_status = 'returning' OR order_status = 'wc-returning') 
                    AND replace_activated = 1"
                    );
                    $res_json = json_decode(json_encode($res), true);
                    foreach ($res_json as $res_single) :
                        $order_id = $res_single["order_id"];
                        $order_email = $res_single["order_email"];
                        $order_status = $res_single["order_status"];
                        $date_delivered = $res_single["date_delivered"];
                        $return_code = $res_single["return_code"];
                        $return_activated = $res_single["return_activated"];
                        $replacement_order = $res_single["replacement_order"];
                        $return_received_activated = $res_single["return_received_activated"];
                        
                        $order = wc_get_order($order_id);
                        
                        ?>
                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo $order_email; ?></td>
                        <td><?php echo $order_status; ?></td>
                        <td><?php echo $date_delivered; ?></td>
                        <td><?php echo $replacement_order; ?></td>
                        <td><?php if($return_received_activated){echo 'Ja';}else{echo 'Nein';}; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php
    break;
    case 'payments':
      ?>
    <form method="post" action="">
        <h2>Offene Zahlungen</h2>
        <p>Im Tab Offene Zahlungen werden alle Bestellungen, die noch nicht bezahlt wurden angezeigt. Diese Seite hat nur einen informativen Charakter und könnte für zukünftige Entwicklungen benutzt werden (ebics etc.).</p>
        <table style="width:100%">
            <thead>
                <tr>
                    <th>Bestellnummer</th>
                    <th>E-Mail</th>
                    <th>Status</th>
                    <th>Bestelldatum</th>
                </tr>
            </thead>
            <tbody>
            <?php 
                global $wpdb;
                $res = $wpdb->get_results("
                        SELECT * FROM babytuch_order_process
                        WHERE order_status = 'on-hold' OR order_status = 'wc-on-hold'"
                );
                $res_json = json_decode(json_encode($res), true);
                foreach ($res_json as $res_single) :
                    $order_id = $res_single["order_id"];
                    $order_email = $res_single["order_email"];
                    $order_status = $res_single["order_status"];
                    $order_date = $res_single["date_order_created"];; ?>
                <tr>
                    <td><?php echo $order_id; ?></td>
                    <td><?php echo $order_email; ?></td>
                    <td><?php echo $order_status; ?></td>
                    <td><?php echo $order_date; ?></td>
                    <th><input type="checkbox" name="check_<?php echo $order_id?>"></th>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $result = get_data_payments();
        ?>
        <br><br>
        <input type="submit" value="Überprüfen" name="check_all" disabled> Überprüft die Zahlungen, welche markiert sind.
    </form>
    <?php
        break;
      default:
      
    ?>
        <form method="post" action="">
        <h2>Rücksendbare Bestellungen</h2>
        <p>Im Tab Rücksendungen sieht man 4 Tabellen:</p><p>
“Rücksendbare Bestellungen”: Alle Bestellungen die bereits versandt wurden und noch im Zeitrahmen sind, sodass sie rücksendbar wären.</p><p>
“Aktivierte Rücksendungen”: Alle Rücksendungen, welche vom Kunden gemeldet worden sind, aber noch nicht in der Logistik angekommen wurden.</p><p>
“Erhaltene Rücksendungen”: Alle Rücksendungen, welche von der Logistik empfangen worden sind und nun bereit für die Rückerstattung sind. Falls es sich um eine “Umtausch Bestellung” handelt (d.h. der Kunde sendet seine umgetauschten Produkte zurück) ist in der Spalte “Umtausch” ein “Ja” vorhanden und die Kosten des 3-maligen Versands in der Spalte “Versandkosten” enthalten</p><p>
“Rückerstattete Bestellungen”: Alle Bestellungen, welche bereits rückerstattet wurden.
</p>
        <table style="width:100%">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Zustellungsdatum</th>
                        <th>Code</th>
                        <th>Kosten</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    global $wpdb;
                    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE order_status = 'completed' OR order_status = 'wc-completed'"
                    );
                    $res_json = json_decode(json_encode($res), true);
                    foreach ($res_json as $res_single) :
                        $order_id = $res_single["order_id"];
                        $order_email = $res_single["order_email"];
                        $order_status = $res_single["order_status"];
                        $date_delivered = $res_single["date_delivered"];
                        $return_code = $res_single["return_code"];
                        $return_activated = $res_single["return_activated"];
                        $cost = $res_single["total_price"];
                        $refunded = $res_single["refunded"];; 

                        $return_days_limit = get_option('return_days_limit');
                        if(!$return_days_limit){
                            $return_days_limit=35;
                        }
                        $due_date = date('Y-m-d', strtotime($date_delivered . " + $return_days_limit days"));
                        $today = date('Y-m-d', time());

                     if($today <= $due_date){   
                        ?>
                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo $order_email; ?></td>
                        <td><?php echo $order_status; ?></td>
                        <td><?php echo $date_delivered; ?></td>
                        <td><?php echo $return_code; ?></td>
                        <td><?php echo $cost.' Fr.'; ?></td>
                    </tr>
                    <?php } ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Aktivierte Rücksendungen</h2>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Zustellungsdatum</th>
                        <th>Code</th>
                        <th>Rückgesandt</th>
                        <th>Kosten</th>
                        <th>Rückerstattet</th>
                    </tr>
                </thead>
                <tbody>
            <?php
                    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE (order_status = 'returning' OR order_status = 'wc-returning')
                    AND return_received_activated = 0"
                    );
                    $res_json = json_decode(json_encode($res), true);
                    foreach ($res_json as $res_single) :
                        $order_id = $res_single["order_id"];
                        $order_email = $res_single["order_email"];
                        $order_status = $res_single["order_status"];
                        $date_delivered = $res_single["date_delivered"];
                        $return_code = $res_single["return_code"];
                        $return_activated = $res_single["return_activated"];
                        $cost = $res_single["total_price"];
                        $refunded = $res_single["refunded"];; ?>
                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo $order_email; ?></td>
                        <td><?php echo $order_status; ?></td>
                        <td><?php echo $date_delivered; ?></td>
                        <td><?php echo $return_code; ?></td>
                        <td><?php echo $return_activated; ?></td>
                        <td><?php echo $cost.' Fr.'; ?></td>
                        <td><?php echo $refunded; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Erhaltene Rücksendungen (bereit zur Rückerstattung)</h2>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>E-Mail</th>
                        <th>IBAN</th>
                        <th>Status</th>
                        <th>Zustellungsdatum</th>
                        <th>Kosten</th>
                        <th>Rückerstattet</th>
                        <th>Umtausch</th>
                        <th>Versandkosten (x3)</th>
                    </tr>
                </thead>
                <tbody>
            <?php
                    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE (order_status = 'returning' OR order_status = 'wc-returning') 
                    AND return_received_activated = 1 AND refunded = 0 AND replace_activated = 0
                    AND not_ok = 0"
                    );
                    $res_json = json_decode(json_encode($res), true);
                    foreach ($res_json as $res_single) :
                        $order_id = $res_single["order_id"];
                        $order_email = $res_single["order_email"];
                        $order_status = $res_single["order_status"];
                        $date_delivered = $res_single["date_delivered"];
                        $cost_of_sending = $res_single["cost_of_sending"];
                        $return_activated = $res_single["return_activated"];
                        $cost = $res_single["total_price"];
                        $is_replacement_order = $res_single["is_replacement_order"];
                        $refunded = $res_single["refunded"];; 
                        
                        $order = wc_get_order($order_id);

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

                        /*$customer_email = $order->get_billing_email('view');
                        $table_name = $wpdb->prefix . 'wc_customer_lookup';
                        $user_id = $wpdb->get_results( 
                          $wpdb->prepare( "
                            SELECT * FROM $table_name
                            WHERE email = %s", 
                            $customer_email
                          ) 
                        );
                      
                         $user_id_json = json_decode(json_encode($user_id), true);
                         $id=$user_id_json[0]["user_id"];
                      
                         if($id==NULL){
                             $id=$user_id_json[1]["user_id"];
                         }   */
                        
                         $table_name2 = $wpdb->prefix . 'usermeta';
                         $ref_id = $wpdb->get_results( 
                             $wpdb->prepare( "
                                 SELECT meta_value FROM $table_name2
                                 WHERE user_id = %s AND meta_key='iban_num'", 
                                 $client_id
                             ) 
                         );
                         $ref_id_json = json_decode(json_encode($ref_id), true);
                         if($ref_id_json){
                            $iban = $ref_id_json[0]["meta_value"]; 
                         } else{
                             $iban = 'n.a.';
                         }
                        
                        ?>
                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo $order_email; ?></td>
                        <td><?php echo $iban; ?></td>
                        <td><?php echo $order_status; ?></td>
                        <td><?php echo $date_delivered; ?></td>
                        <td><?php echo $cost.' Fr.'; ?></td>
                        <td><?php if($refunded){echo 'Ja';}else{echo 'Nein';}; ?></td>
                        <td><?php if($is_replacement_order){echo 'Ja';}else{echo 'Nein';}; ?></td>
                        <td><?php if($cost_of_sending){echo $cost_of_sending;}else{echo 'n/a';}; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br><br>
            <input type="submit" value="Rückerstattungen abschliessen" name="submit"> Markiert alle 
            erhaltene Rücksendungen (welche nicht Umtausche sind) als rückerstattet und sendet den betroffenen Kunden eine E-Mail.
            <br><br>
            <h2>Rückerstattete Bestellungen</h2>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Zustellungsdatum</th>
                        <th>Code</th>
                        <th>Rückgesandt</th>
                        <th>Kosten</th>
                        <th>Rückerstattet</th>
                    </tr>
                </thead>
                <tbody>
            <?php
                    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE (order_status = 'returning' OR order_status = 'wc-returning') 
                    AND refunded = 1"
                    );
                    $res_json = json_decode(json_encode($res), true);
                    foreach ($res_json as $res_single) :
                        $order_id = $res_single["order_id"];
                        $order_email = $res_single["order_email"];
                        $order_status = $res_single["order_status"];
                        $date_delivered = $res_single["date_delivered"];
                        $return_code = $res_single["return_code"];
                        $return_activated = $res_single["return_activated"];
                        $cost = $res_single["total_price"];
                        $refunded = $res_single["refunded"];; ?>
                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo $order_email; ?></td>
                        <td><?php echo $order_status; ?></td>
                        <td><?php echo $date_delivered; ?></td>
                        <td><?php echo $return_code; ?></td>
                        <td><?php echo $return_activated; ?></td>
                        <td><?php echo $cost.' Fr.'; ?></td>
                        <td><?php if($refunded){echo 'Ja';}else{echo 'Nein';}; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $result = get_data();
            ?>
            
        </form>
    <?php
    endswitch; ?>
    </div>
  </div>


<?php
if(isset($_POST['problems_solved'])){
    if(!empty($_POST["solved_problems"])){
        $solved_problems = $_POST["solved_problems"];
        foreach($solved_problems as $solved_problem){
            global $wpdb;
            $res = $wpdb->get_results( 
                $wpdb->prepare( "
                    SELECT * FROM babytuch_order_process 
                    WHERE order_id  = %s", 
                    $solved_problem 
                ) 
            );
            $res_json = json_decode(json_encode($res), true);
            
                   
                //LAGER AUFFÜLLEN
                $return_products = $res_json[0]["return_products"];
                $split_arr = explode(",", $return_products);
                foreach($split_arr as $id){
                    if(!empty($id)){
                        $product_to_refill = wc_get_product((int)$id);
                        $stock_old = $product_to_refill->get_stock_quantity();
                        $stock_new = (int)$stock_old+1;
                        $product_to_refill->set_stock_quantity($stock_new);
                        //$data = $product_to_refill->get_data();
                        $product_to_refill->save();
                    }
                }
        
                $order_id = $res_single["order_id"];
                $cost = $res_single["total_price"];
                $email = $res_single["order_email"];
                $order = wc_get_order($order_id);
                $fn = $order->get_billing_first_name();
                $ln = $order->get_billing_last_name();
                $full_name = $fn.' '.$ln;

                global $wpdb;
                $value = $wpdb->query( 
                            $wpdb->prepare( "
                                UPDATE babytuch_order_process SET refunded = true
                                WHERE order_id = %s", 
                                $order_id
                            ) 
                );
        }
        header("Refresh:0");
      
    }else{
        echo'<br>Kein Problemfall ausgewählt. Es wurde deshalb nichts geändert.';
    }
}
if(isset($_POST['submit'])){
    global $wpdb;
    $res = $wpdb->get_results("
                    SELECT * FROM babytuch_order_process
                    WHERE (order_status = 'returning' OR order_status = 'wc-returning') 
                    AND return_received_activated = 1 AND replace_activated = 0 AND refunded = 0
                    AND not_ok = 0"
                    );
    $res_json = json_decode(json_encode($res), true);
    foreach ($res_json as $res_single){
        $order_id = $res_single["order_id"];
        $cost = $res_single["total_price"];
        $email = $res_single["order_email"];
        $order = wc_get_order($order_id);
        $fn = $order->get_billing_first_name();
        $ln = $order->get_billing_last_name();
        $full_name = $fn.' '.$ln;
        global $wpdb;
        $value = $wpdb->query( 
                    $wpdb->prepare( "
                        UPDATE babytuch_order_process SET refunded = true
                        WHERE order_id = %s", 
                        $order_id
                    ) 
        );
        if($value){
            global $woocommerce, $wpdb;
            $mailer = $woocommerce->mailer();
          
            $user_fname = $order->get_billing_first_name('view');
          
          $order_date_form = date('d.m.Y', time());
     
          $items = $order->get_items();
          
          $shipping_method = $order->get_shipping_method();
            global $wpdb;
            $table_name2 = $wpdb->prefix . 'woocommerce_order_itemmeta';
            $res = $wpdb->get_results( 
            "SELECT * FROM $table_name2
                WHERE meta_key  = 'rate_percent'"
                
            );
            $res_json = json_decode(json_encode($res), true);
            $tax = (float)end($res_json)["meta_value"];
            $endcost = $order->get_total();
          
          
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
          $table_name2 = $wpdb->prefix . 'usermeta';
          $ref_id = $wpdb->get_results( 
             $wpdb->prepare( "
                 SELECT meta_value FROM $table_name2
                 WHERE user_id = %s AND meta_key='iban_num'", 
                 $client_id
             ) 
          );
          $ref_id_json = json_decode(json_encode($ref_id), true);
          if($ref_id_json){
            $iban = $ref_id_json[0]["meta_value"]; 
          } else{
             $iban = 'n.a.';
          }
          
          
            $user_message = "Hallo $user_fname, <br><br> Wir haben am $order_date_form deine Rücksendung mit diesen Produkten in Empfang
             genommen:
            <br><br>";
            $user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
            <thead>
                <tr>
                    <th>Produkte</th>
                    <th>Anzahl</th>
                    <th>Preis (CHF)</th>
                </tr>
            </thead>
            <tbody>";
            global $wpdb;
            $order_details = $wpdb->get_results( 
                $wpdb->prepare( "
                    SELECT * FROM babytuch_order_process
                    WHERE order_id = %s", 
                    $order_id 
                ) 
            );
            $order_details_json = json_decode(json_encode($order_details), true);
            
            $return_products = $order_details_json[0]["return_products"];
            $split_arr = explode(",", $return_products);
           $total=0;
            foreach($split_arr as $id){
                if(!empty($id)){
                    $product_to_refill = wc_get_product((int)$id);
                    $price = $product_to_refill->get_price();
                    $name = $product_to_refill->get_name();
                    $size = $product_to_refill->get_attributes();
                    $size2 = $size["groesse"];
                    $user_message .= "<tr>
                    <td>$name, Grösse $size2</td>
                    <td>1</td>
                    <td>$price</td>
                    </tr>";
                    $total = $total+(int)$price;
                }
            }
            $user_message .="</tbody>
          </table>
            <br><br>";
            $user_message .="Menge und Zustand waren in Ordnung.
            <br><br>";
            $user_message .="Wir haben dir deshalb heute den Warenwert inkl. MWST auf dein bei uns
             hinterlegtes Konto mit der IBAN $iban zurückerstattet.
            <br><br>";
            $user_message .="<table style='width:100%;border: 1px solid black;border-collapse: collapse;'>
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>";
            $mwst = get_option('company_mwst_number');
            $mwst_cost = round($total*(0.01*$tax),2);
            $end_refund = $mwst_cost+$total;
            $user_message .= "<tr>
                    <td>Warenwert</td>
                    <td>$total</td>
                    </tr>";
            $user_message .= "<tr>
                    <td>Abzüge</td>
                    <td>0.00</td>
                    </tr>";
            $user_message .= "<tr>
                    <td>Zwischensumme</td>
                    <td>$total</td>
                    </tr>";
            $user_message .= "<tr>
                    <td>CHF-$mwst MWST ($tax%)</td>
                    <td>$mwst_cost</td>
                    </tr>";
            $is_replacement_order = $order_details_json[0]["is_replacement_order"];
            if($is_replacement_order==1){
                $cost_of_sending = $order_details_json[0]["cost_of_sending"];
                $user_message .= "<tr>
                    <td>Versandkosten (inkl. MwST)</td>
                    <td>-$cost_of_sending</td>
                    </tr>";
                $end_refund = $end_refund-(float)$cost_of_sending;
            }
            $user_message .= "<tr>
                    <td><b>Unsere Rückerstattung an dich</b></td>
                    <td>$end_refund</td>
                    </tr>";
          
            $user_message .="</tbody>
          </table>
            <br><br>";
            $user_message .="Stimmt alles? Super.
            <br><br>";
            $faq_link = get_home_url().'/faq';
            $user_message .="Solltest du weitere Unterstützung benötigen,
             findest du die meisten Antworten hier $faq_link
           <br><br>";
            $user_message .="Liebe Grüsse <br>
            Neva von babytuch.ch
            <br>";
              $subject = "Deine Rücksendung ist angekommen. Die Gutschrift auf dein Konto ist erfolgt.";
          
              
            ob_start();
            wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Deine Rücksendung ist angekommen. Die Gutschrift auf dein Konto ist erfolgt.' ) );
            echo str_replace( '{{name}}', $user_fname, $user_message );
            wc_get_template( 'emails/email-footer.php' );
            $message = ob_get_clean();
            // Debug wp_die($user_email);
            $mailer->send( $email, $subject, $message);
        }
    }
}

function get_data(){
    global $wpdb;
    $res = $wpdb->get_results("
			SELECT * FROM babytuch_order_process
			WHERE order_status = 'completed' OR order_status = 'wc-completed'"
    );
    $res_json = json_decode(json_encode($res), true);
    /**for($i=0; $i<count($res_json); $i++){
        $order_id = $res_json[$i]["order_id"];
        $order_email = $res_json[$i]["order_email"];
        $order_status = $res_json[$i]["order_status"];
        $date_delivered = $res_json[$i]["date_delivered"];
        $return_code = $res_json[$i]["return_code"];
        $return_activated = $res_json[$i]["return_activated"];
        $refunded = $res_json[$i]["refunded"];
        
        echo '<h3>'.$order_id.'</h3>
         <table style="width:100%">
            <tr>
                <th>Bestellnummer</th>
                <th>E-Mail</th>
                <th>Status</th>
                <th>Zustellungsdatum</th>
                <th>Code</th>
                <th>Rückgesandt</th>
                <th>Rückerstattet</th>
            </tr>
            <tr>
                <th>'.$order_id.'</th>
                <th>'.$order_email.'</th>
                <th>'.$order_status.'</th>
                <th>'.$date_delivered.'</th>
                <th>'.$return_code.'</th>
                <th>'.$return_activated.'</th>
                <th>'.$refunded.'</th>
            </tr>
        </table>
         ';
    };**/
    return $res_json;
}

function update_data($result){
    header("Refresh:0");
}

if(isset($_POST['check_all'])){
    check_payment($result);
}

function get_data_payments(){
    global $wpdb;
    $res = $wpdb->get_results("
			SELECT * FROM babytuch_order_process
			WHERE order_status = 'on-hold' OR order_status = 'wc-on-hold'"
    );
    $res_json = json_decode(json_encode($res), true);
    /**for($i=0; $i<count($res_json); $i++){
        $order_id = $res_json[$i]["order_id"];
        $order_email = $res_json[$i]["order_email"];
        $order_status = $res_json[$i]["order_status"];
        $order_date = $res_json[$i]["date_order_created"];

        echo '<h3>'.$order_id.'</h3>
         <table style="width:100%">
            <tr>
                <th>Bestellnummer</th>
                <th>E-Mail</th>
                <th>Status</th>
                <th>Bestelldatum</th>
            </tr>
            <tr>
                <th>'.$order_id.'</th>
                <th>'.$order_email.'</th>
                <th>'.$order_status.'</th>
                <th>'.$order_date.'</th>
                <th><input type="checkbox" name="check_'.$order_id.'"></th>
            </tr>
        </table>
         ';
    };**/
    return $res_json;
}

function check_payment($result){
    for($i=0; $i<count($result); $i++){
        $order_id = $result[$i]["order_id"];
        if (isset($_POST["check_".$order_id])) {
            var_dump($order_id);
        }
    }
  
    //header("Refresh:0");
}
?>
