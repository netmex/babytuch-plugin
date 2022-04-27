<div class="wrap">
<h1>Babytuch Plugin</h1>
<?php settings_errors();?>
<form method="post" action="">Plugin ist aktiv.
<br><br>
<h2>Offene Zahlungen</h2>
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
    $result = get_data();
    ?>
    <br><br>
    <input type="submit" value="Überprüfen" name="check_all"> Überprüft die Zahlungen, welche markiert sind.
</form>
</div>

<?php
if(isset($_POST['check_all'])){
    check_payment($result);
}

function get_data(){
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
