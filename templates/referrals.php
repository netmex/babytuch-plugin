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
    <h3>Vermittlungsprogramm</h3>
    <p>Vermittlungen verwaltet alle Vermittlungen. Bei jedem Aufruf der Seite wird überprüft, ob eine Abrechnung der aktuellen Vermittlungen fällig ist und würde in diesem Fall die Abrechnung generieren, welche an den Admin geht.
    </p>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=babytuch_referrals" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Aktuelle Vermittlungen</a>
      <a href="?page=babytuch_referrals&tab=archive" class="nav-tab <?php if($tab==='archive'):?>nav-tab-active<?php endif; ?>">Abgeschlossene Vermittlungen</a>
      <a href="?page=babytuch_referrals&tab=settings" class="nav-tab <?php if($tab==='settings'):?>nav-tab-active<?php endif; ?>">Einstellungen</a>
        <a href="?page=babytuch_referrals&tab=top" class="nav-tab <?php if($tab==='top'):?>nav-tab-active<?php endif; ?>">Top10</a>
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'archive':
          ?>
          <form method="post" action="">
            <h2>Übersicht abgeschlossene Vermittlungen</h2>
            <p>Im Tab Abgeschlossene Vermittlungen erscheinen alle finalisierten d.h. ausbezahlten Vermittlungen.
</p>
            <br>
            <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>IBAN</th>
                            <th>Vermittlungen</th>
                            <th>Anzahl Tücher</th>
                            <th>Belohnung</th>
                        </tr>
                    </thead>
                    <tbody>
            <?php
            $users = get_users();
            foreach($users as $user){
                $user_id = $user->ID;
                global $wpdb;
                $res = $wpdb->get_results( 
                    $wpdb->prepare("
                        SELECT * FROM wp_usermeta
                        WHERE user_id = %s AND meta_key = 'iban_num'", 
                        $user_id
                    ));
                $res_json = json_decode(json_encode($res), true);
                if($res_json){
                    $iban = $res_json[0]["meta_value"];
                }

                $user_email = $user->user_email;
                $args = array(
                    'posts_per_page'   => -1,
                    'post_type'        => 'shop_coupon',
                    'post_status'      => 'publish',
                    'meta_query' => array (
                        array (
                        'key' => 'customer_email',
                        'value' => $user_email,
                        'compare' => 'LIKE'
                        )
                    ),
                );
                    
                $coupons = get_posts( $args );
            
                $count=0;
                $total_amount=0;
                $total_refund= 0;
                foreach ( $coupons as $coupon ) {
                    if(substr( $coupon->post_title, 0, 3 ) != "RAF") {
                        continue;
                    }
                    $discount = get_post_meta($coupon->ID, "coupon_amount" ,true);
                    $order_amount = get_post_meta($coupon->ID, "order_amount" ,true);
                    $transaction_complete = get_post_meta($coupon->ID, "transaction_complete" ,true);
                    $customer_email = get_post_meta($coupon->ID,"customer_email",true);
                    $reffered_who = get_post_meta($coupon->ID, "reffered_who" ,true);
                    $reffered_who_fname = get_post_meta($coupon->ID, "reffered_who_fname" ,true);
                    $reffered_who_lname = get_post_meta($coupon->ID, "reffered_who_lname" ,true);
                    
                    if($transaction_complete){
                        $count++;
                        $total_amount = $total_amount + (int)$order_amount;
                        $total_refund = $total_refund + ($discount*(int)$order_amount);
                        ?>
                        <tr>
                        <td style="text-align: center;"></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: center;"><?php echo $reffered_who; ?></td>
                        <td style="text-align: center;"><?php echo $reffered_who_fname.' '.$reffered_who_lname; ?></td>
                        <td style="text-align: center;"></td>
                        </tr><?php
                    }
                   
                    
                }
                if($count != 0) {
                    $user_fn = $user->first_name;
                    if($user_fn==''){
                        $user_fn = $user->billing_first_name;
                    }
                    $user_ln = $user->last_name;
                    if($user_ln==''){
                        $user_ln = $user->billing_last_name;
                    }
                    ?>
                        <tr style="border-bottom: 1px solid black;">
                            <td style="text-align: center;"><?php echo $user_fn.' '.$user_ln; ?></td>
                            <td style="text-align: center;"><?php echo $customer_email; ?></td>
                            <td style="text-align: center;"><?php echo $iban; ?></td>
                            <td style="text-align: center;"><?php echo $count; ?></td>
                            <td style="text-align: center;"><?php echo $total_amount; ?></td>
                            <td style="text-align: center;"><strike><?php echo $total_refund; ?> Fr.</strike></td>
                        </tr><?php
                } 
            }
            ?>
            </tbody>
            </table>
          </form>
      <?php
        break;
      case 'settings':
        ?>
        <form method="post" action="">
        <h2>Allgemeine Einstellungen</h2>
        <p>Im Tab Einstellungen kann man die Daten der Abrechnung einstellen (gleiches Schema wie Quartalsabrechnung), sowie die Anzahl Franken, welche im Vermittlungsprogramm ausbezahlt werden.
</p><br>
        <?php
        $referrals_last_generated = get_option('referrals_last_generated');
        echo "<p><input type='date' style='width: 150px' class='regular-text'
        name='referrals_last_generated' value=$referrals_last_generated> Letzte Abrechnung</p>";
        echo"<br>";
        $referrals_next_generated = get_option('referrals_next_generated');
        echo "<p><input type='date' style='width: 150px' class='regular-text'
        name='referrals_next_generated' value=$referrals_next_generated> Nächste Abrechnung</p>";
        echo"<br>";
        $referrals_interval = get_option('referrals_interval');
        echo "<p><input type='text' style='width: 50px' class='regular-text'
        name='referrals_interval' value=$referrals_interval> Interval (in Monaten)</p>";
        echo"<br>";

        echo"<h4>Belohnung</h4>";
        $gens_raf_coupon_amount = get_option('gens_raf_coupon_amount');
        echo "<p><input type='text' style='width: 50px' class='regular-text'
        name='gens_raf_coupon_amount' value='$gens_raf_coupon_amount'> Belohnung pro vermittelter Babytuch (in Franken)</p>";
        echo"<br>";

        ?>
        <input type="submit" value="Speichern" name="submit_common_settings"> Speichert alle Felder.
        </form>
    <?php
    break;
    case 'top':
      ?>
        <br><h2>Top 10 Vermittler</h2>
        <p>Im Tab Top10 erscheinen 10 besten Vermittler basiert auf der Anzahl vermittelten Tücher.
</p><br>
     <br>
     <table style="width:100%">
             <thead>
                 <tr>
                     <th>Rang</th>
                     <th>Name</th>
                     <th>E-Mail</th>
                     <th>Anzahl Vermittlungen</th>
                     <th>Anzahl vermittelte Tücher</th>
                 </tr>
             </thead>
             <tbody>
     <?php
     $users = get_users();
     $top_list = array();

     foreach($users as $user){

         $user_email = $user->user_email;
         $args = array(
             'posts_per_page'   => -1,
             'post_type'        => 'shop_coupon',
             'post_status'      => 'publish',
             'meta_query' => array (
                 array (
                 'key' => 'customer_email',
                 'value' => $user_email,
                 'compare' => 'LIKE'
                 )
             ),
         );
             
         $coupons = get_posts( $args );
     
         $count=0;
         $total_amount=0;
         
         foreach ( $coupons as $coupon ) {
             if(substr( $coupon->post_title, 0, 3 ) != "RAF") {
                 continue;
             }
             $order_amount = get_post_meta($coupon->ID, "order_amount" ,true);
             $transaction_complete = get_post_meta($coupon->ID, "transaction_complete" ,true);
             $customer_email = get_post_meta($coupon->ID,"customer_email",true);
             $reffered_who = get_post_meta($coupon->ID, "reffered_who" ,true);
           
             
            $count++;
            $total_amount = $total_amount + (int)$order_amount;
         }
         $user_fn = $user->first_name;
         if($user_fn==''){
             $user_fn = $user->billing_first_name;
         }
         $user_ln = $user->last_name;
         if($user_ln==''){
             $user_ln = $user->billing_last_name;
         }
         $entry = array(
            'email' => $customer_email,
            'name' => $user_fn.' '.$user_ln,
            'count' => $count,
            'amount' => $total_amount
        );
        if($count!=0){
            if(empty($top_list)){
                array_push($top_list, $entry);
            }else{
                $this_amount = (int)$entry["amount"];
                $pos=0;
                foreach($top_list as $e){
                    $a = (int)$e["amount"];
                    if($this_amount<=$a){
                        if($e==end($top_list)){
                            array_push($top_list, $entry);
                        break;
                        }
                    }else{
                        array_splice($top_list, $pos, 0, array($entry));
                    break;
                    }
                    $pos++;
                }
            }
        }
        
     }
     //var_dump($top_list);
     
     for($i=0;$i<=10;$i++){
         if($i >= count($top_list)){
         break;
         }
         $email = $top_list[$i]["email"];
         $name = $top_list[$i]["name"];
         $count = $top_list[$i]["count"];
         $amount = $top_list[$i]["amount"];
         ?>
         <tr>
             <td style="text-align: center;"><?php echo ($i+1)."."; ?></td>
             <td style="text-align: center;"><?php echo $name; ?></td>
             <td style="text-align: center;"><?php echo $email; ?></td>
             <td style="text-align: center;"><?php echo $count; ?></td>
             <td style="text-align: center;"><?php echo $amount; ?></td>
         </tr>
         <tr><td><br><br></td></tr> <?php
     }
    
     ?>
     </tbody>
     </table>
    <?php
        break;
      default:
      generator();
    ?>
        <form method="post" action="">
         
       
        <h2>Übersicht offene Vermittlungen</h2>
        <p>Im Tab Aktuelle Vermittlungen hat man eine Übersicht aller offenen d.h. noch nicht ausbezahlten Vermittlungen. Pro Vermittler sieht man seine IBAN-Nr. Und seine Vermittlungen. Auch die einzelnen vermittelten Personen erscheinen in der Spalte “Vermittlungen” (falls vorhanden). Nach Erhalt der Abrechnung und nach dem Ausbezahlen bestätigt der Admin mit dem Knopf “Alles abschliessen” die Finalisierung. Dies markiert alle offenen Vermittlungen als finalisiert. Der Knopf “Sofort exportieren” erstellt eine aktuelle Abrechnung sofort und sendet diese dem Admin.
</p>
        <br>
        <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>IBAN</th>
                        <th>Vermittlungen</th>
                        <th>Anzahl Tücher</th>
                        <th>Belohnung</th>
                    </tr>
                </thead>
                <tbody>
        <?php
        $users = get_users();
        foreach($users as $user){
            $user_email = $user->user_email;
            $user_id = $user->ID;
            global $wpdb;
            $table_name = $wpdb->prefix . 'usermeta';
            $res = $wpdb->get_results( 
                $wpdb->prepare("
                    SELECT * FROM $table_name
                    WHERE user_id = %s AND meta_key = 'iban_num'", 
                    $user_id
                ));
            $res_json = json_decode(json_encode($res), true);
            if($res_json){
                $iban = $res_json[0]["meta_value"];
            }else{
                $iban = 'IBAN ausstehend (Kunde wurde informiert)';
            }
            


            $args = array(
                'posts_per_page'   => -1,
                'post_type'        => 'shop_coupon',
                'post_status'      => 'publish',
                'meta_query' => array (
                    array (
                    'key' => 'customer_email',
                    'value' => $user_email,
                    'compare' => 'LIKE'
                    )
                ),
            );
                
            $coupons = get_posts( $args );
        
            $count=0;
            $total_amount=0;
            $total_refund= 0;
            foreach ( $coupons as $coupon ) {
                if(substr( $coupon->post_title, 0, 3 ) != "RAF") {
                    continue;
                }
                $discount = get_post_meta($coupon->ID, "coupon_amount" ,true);
                $order_amount = get_post_meta($coupon->ID, "order_amount" ,true);
                $transaction_complete = get_post_meta($coupon->ID, "transaction_complete" ,true);
                $customer_email = get_post_meta($coupon->ID,"customer_email",true);
                $reffered_who = get_post_meta($coupon->ID, "reffered_who" ,true);
                $reffered_who_fname = get_post_meta($coupon->ID, "reffered_who_fname" ,true);
                $reffered_who_lname = get_post_meta($coupon->ID, "reffered_who_lname" ,true);
                $reffered_who_order_id = get_post_meta($coupon->ID, "reffered_who_order_id" ,true);
                $creation_date = get_post_meta($coupon->ID, "creation_date" ,true);
                $return_days_limit = get_option('return_days_limit');
                $fin_date = date('Y-m-d',strtotime($creation_date. " + $return_days_limit days"));
                $today = date('Y-m-d');
                //var_dump($fin_date>$today);
                if($reffered_who_order_id){
                    $order = wc_get_order((int)$reffered_who_order_id);
                    $order_status = $order->get_status();
                    if($order_status=='returning' or $fin_date>=$today){
                        $checks=false;
                    }else{
                        $checks=true;
                    }
                }else{
                    $checks=true;
                }
                
                if(!$transaction_complete and $checks){
                    $count++;
                    $total_amount = $total_amount + (int)$order_amount;
                    $total_refund = $total_refund + ($discount*(int)$order_amount);
                    ?>
                    <tr>
                    <td style="text-align: center;"></td>
                    <td style="text-align: center;"></td>
                    <td style="text-align: center;"><?php echo $reffered_who; ?></td>
                    <td style="text-align: center;"><?php echo $reffered_who_fname.' '.$reffered_who_lname; ?></td>
                    <td style="text-align: center;"></td>
                    </tr> <?php
                }
                
            }
            if($count != 0) {
                $user_fn = $user->first_name;
                if($user_fn==''){
                    $user_fn = $user->billing_first_name;
                }
                $user_ln = $user->last_name;
                if($user_ln==''){
                    $user_ln = $user->billing_last_name;
                }
                ?>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="text-align: center;"><?php echo $user_fn.' '.$user_ln; ?></td>
                        <td style="text-align: center;"><?php echo $customer_email; ?></td>
                        <td style="text-align: center;"><?php echo $iban; ?></td>
                        <td style="text-align: center;"><?php echo $count; ?></td>
                        <td style="text-align: center;"><?php echo $total_amount; ?></td>
                        <td style="text-align: center;"><?php echo $total_refund; ?> Fr.</td>
                    </tr>
                    <tr><td><br><br></td></tr> <?php
            } 
        }
        ?>
        </tbody>
        </table>
        <br><br>
        <input type="submit" value="Alle abschliessen" name="complete_all"> Alle offenen Vermittlungen (welche bereits eine IBAN-Nr. besitzen) werden als bezahlt durch den Admin markiert. 
        <br><br><br><br>
        <input type="submit" value="Sofort Exportieren" name="export"> Generiert die Abrechnung der Vermittlungen sofort.
        </form>
    <?php
    endswitch; ?>
    </div>
  </div>

<?php


if(isset($_POST['submit_common_settings'])){
    
    $referrals_last_generated = $_POST['referrals_last_generated'];
    update_option('referrals_last_generated', $referrals_last_generated);
    $referrals_next_generated = $_POST['referrals_next_generated'];
    update_option('referrals_next_generated', $referrals_next_generated);
    $referrals_interval = $_POST['referrals_interval'];
    update_option('referrals_interval', $referrals_interval);
   
    $gens_raf_coupon_amount = $_POST['gens_raf_coupon_amount'];
    update_option('gens_raf_coupon_amount', $gens_raf_coupon_amount);

    header("Refresh:0");
}

if(isset($_POST['complete_all'])){
    $users = get_users();
    $result = array();
    foreach($users as $user){
        $obj = array();
        $user_email = $user->user_email;
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'shop_coupon',
            'post_status'      => 'publish',
            'meta_query' => array (
                array (
                'key' => 'customer_email',
                'value' => $user_email,
                'compare' => 'LIKE'
                )
            ),
        );
            
        $coupons = get_posts( $args );

        foreach ( $coupons as $coupon ) {
            if(substr( $coupon->post_title, 0, 3 ) != "RAF") {
                continue;
            }
            $transaction_complete = get_post_meta($coupon->ID, "transaction_complete" ,true);
            
            if(!$transaction_complete){
                $user_id = $user->ID;
                global $wpdb;
                $table_name = $wpdb->prefix . 'usermeta';
                $res = $wpdb->get_results( 
                    $wpdb->prepare("
                        SELECT * FROM $table_name
                        WHERE user_id = %s AND meta_key = 'iban_num'", 
                        $user_id
                    ));
                $res_json = json_decode(json_encode($res), true);
                if($res_json){
                    update_post_meta( $coupon->ID, 'transaction_complete', 1 );
                }
            }
            
        }
    }
    header("Refresh:0");

}


if(isset($_POST['export'])){
    //GET ALL THE DATA
    $data = get_all_data();
 

    create_billing_document($data);

    $num = get_option('referral_document_num');
    $num = (int)$num - 1;
    $subject = 'Abrechnung Vermittlungsprogramm (Sofort Export)';
    $message = "Abrechnung Vermittlungsprogramm (Sofort Export) befindet sich im Anhang.";
    $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/referral_documents/referral_document_$num.pdf");
    $header = 'Babytuch.ch <myname@mydomain.com>' . "\r\n";
    $babytuch_admin_email = get_option('babytuch_admin_email');
    wp_mail( $babytuch_admin_email, $subject, $message, $header, $attach);
    header("Refresh:0");
}


function generator(){
    global $wpdb;
    //GENERATES PDF TO MAIL
    $referrals_next_generated = get_option('referrals_next_generated');
    if($referrals_next_generated<date('Y-m-d',time())){  
        $referrals_interval = get_option('referrals_interval');
        $next_generated = date('Y-m-d', strtotime($referrals_next_generated . " + $referrals_interval months"));
    
        //GET ALL THE DATA
        $data = get_all_data();
        if(empty($data)){
            return 'Keine Vermittlungen.';
        }
        
        create_billing_document($data);

        $num = get_option('referral_document_num');
        $num = (int)$num - 1;
        $subject = 'Abrechnung Vermittlungsprogramm';
        $message = "Nächste Abrechnung am: $next_generated";
        $attach = array(WP_CONTENT_DIR . "/plugins/babytuch-plugin/referral_documents/referral_document_$num.pdf");
        $header = 'Babytuch.ch <myname@mydomain.com>' . "\r\n";
        $babytuch_admin_email = get_option('babytuch_admin_email');
        wp_mail( $babytuch_admin_email, $subject, $message, $header, $attach);


        update_option('referrals_last_generated', date('Y-m-d',time()));
        update_option('referrals_next_generated', $next_generated);
        header("Refresh:0");
    }
    
}

function get_all_data(){
    $users = get_users();
    $result = array();
    foreach($users as $user){
        $obj = array();
        $user_email = $user->user_email;
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'shop_coupon',
            'post_status'      => 'publish',
            'meta_query' => array (
                array (
                'key' => 'customer_email',
                'value' => $user_email,
                'compare' => 'LIKE'
                )
            ),
        );

        $user_id = $user->ID;
        global $wpdb;
        $table_name = $wpdb->prefix . 'usermeta';
        $res = $wpdb->get_results( 
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE user_id = %s AND meta_key = 'iban_num'", 
                $user_id
            ));
        $res_json = json_decode(json_encode($res), true);
        if($res_json){
        $iban = $res_json[0]["meta_value"];
        }else{
            $iban = 'no_iban';
        }
            
        $coupons = get_posts( $args );
    
        $count=0;
        $total_amount=0;
        $total_refund=0;
        $t = 0;
        foreach ( $coupons as $coupon ) {
            if(substr( $coupon->post_title, 0, 3 ) != "RAF") {
                continue;
            }
            $discount = get_post_meta($coupon->ID, "coupon_amount" ,true);
            $order_amount = get_post_meta($coupon->ID, "order_amount" ,true);
            $creation_date = get_post_meta($coupon->ID, "creation_date" ,true);
            $transaction_complete = get_post_meta($coupon->ID, "transaction_complete" ,true);
            $customer_email = get_post_meta($coupon->ID,"customer_email",true);
            $reffered_who_order_id = get_post_meta($coupon->ID, "reffered_who_order_id" ,true);
                $return_days_limit = get_option('return_days_limit');
                $fin_date = date('Y-m-d',strtotime($creation_date. " + $return_days_limit days"));
                $today = date('Y-m-d');
                //var_dump($fin_date>$today);
                if($reffered_who_order_id){
                    $order = wc_get_order((int)$reffered_who_order_id);
                    $order_status = $order->get_status();
                    if($order_status=='returning' or $fin_date>=$today){
                        $checks=false;
                    }else{
                        $checks=true;
                    }
                }else{
                    $checks=true;
                }
            
            if(!$transaction_complete and $checks){
                $count++;
                $total_amount = $total_amount + (int)$order_amount;
                $total_refund = $total_refund + ($discount*(int)$order_amount);
                if($t!=0){
                    $cd =  $creation_date;
                    $t++;
                }
                
            }
            
        }

        $user_fn = $user->first_name;
        if($user_fn==''){
            $user_fn = $user->billing_first_name;
        }
        $user_ln = $user->last_name;
        if($user_ln==''){
            $user_ln = $user->billing_last_name;
        }
        $name = $user_fn.' '.$user_ln;

    
        
        if($count != 0) {
            $obj = array(
                'name' => $name,
                'email' => $user_email,
                'creation_date' => $cd,
                'count' => $count,
                'total_amount' => $total_amount,
                'total_refund' => $total_refund,
                'iban' => $iban
             );
            array_push($result, $obj);
        } 
    }
    return $result;
}





//Abrechnung erstellen
function create_billing_document($data){

    $home_url_full = get_home_url();
    $home_url = substr($home_url_full, 7);
	
    $home_path = get_home_path();
    require_once($home_path.'/wp-content/plugins/babytuch-plugin/assets/TCPDF-master/tcpdf.php');

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', 'A4', true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Babytuch Schweiz');
    $pdf->SetTitle('Vermittlungsprogramm');
    $pdf->SetSubject('Vermittlungsprogramm');
    $pdf->SetKeywords('vermittlungsprogramm, lieferung, lieferaddresse, produktinfo');

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
    $pdf->SetFont('helvetica', '', 9);
	$pdf->setCellHeightRatio(0.6);
	
    $pdf->AddPage();

    $city = get_option('woocommerce_store_city');
    $zip = get_option('woocommerce_store_postcode');
    $street = get_option('woocommerce_store_address');
    $date = date("d. m. Y");
    $year = date("Y");
    $month = date('m');

    $html = '<div>Babytuch(Schweiz) GmbH</div>';
    $html .= "<div>$street</div>";
    $html .= "<div>$zip $city</div>";
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

	$html = "<h3>Abrechnung Vermittlungsprogramm $year $month</h3>";

	$pdf->writeHTML($html, true, 0, true, 0);


	$pdf->SetXY(8, 65);
   

    $html = "<div>Offene Zahlungen für folgende Vermittlungen:</div><br>";

    $html .= "<div><b>Vermittlungen:</b></div><br>";
    $html .= '<table>
            <thead>
                <tr>
                    <th colspan="7" style="height:0px; width:100%;"></th>
                </tr>
            </thead>
            <tbody>';
    $total=0;
    foreach($data as $entry){
        $name = $entry["name"];
        $email = $entry["email"];
        $creation_date = $entry["creation_date"];
        $total_amount = $entry["total_amount"];
        $count = $entry["count"];
        $total_refund = $entry["total_refund"];
        $iban = $entry["iban"];
      
        if($iban!='no_iban'){
            $html .='<tr>
            <td style="height:25px;width:150px;">'.$name.'</td>
            <td style="width:180px;">'.$email.'</td>
            <td style="width:120px;">'.$iban.'</td>
            <td style="width:100px;">'.$creation_date.'</td>
            <td style="width:50px;">'.$count.'</td>
            <td style="width:50px;">'.$total_amount.'</td>
            <td style="width:60px;">'.$total_refund.' Fr.</td>
            </tr>';
            $total = $total + (int)$total_refund;
        }
    }
    $html .='<tr>
    <td style="height:25px;width:150px;"></td>
    <td style="width:180px;"></td>
    <td style="width:120px;"></td>
    <td style="width:100px;"></td>
    <td style="width:50px;"></td>
    <td style="width:50px;"></td>
    <td style="width:60px;"><b>'.$total.' Fr.</b></td>
    </tr>';
             
                
    $html .='</tbody>
        </table><br><br><br><br>';

    $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
    


    // ---------------------------------------------------------
    ob_end_clean();

    $referral_document_num = get_option('referral_document_num');
    if(empty($referral_document_num)){
        update_option('referral_document_num', 1);
        $referral_document_num = 1;
    }
    update_option('referral_document_num', (int)$referral_document_num+1);

    
    $home_path = get_home_path();
    $path = $home_path.'/wp-content/plugins/babytuch-plugin/referral_documents/';
    $pdf->Output($path."referral_document_$referral_document_num.pdf", 'F');
}
?>

<?php
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


