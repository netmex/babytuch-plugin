<?php
// check user capabilities

use Inc\Api\BT_PDF;
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
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p>In den allgemeinen Einstellungen befinden sich die wichtigsten Einstellungen. </p>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=babytuch_plugin" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Allgemeines</a>
      <a href="?page=babytuch_plugin&tab=sending" class="nav-tab <?php if($tab==='sending'):?>nav-tab-active<?php endif; ?>">Versand</a>
      <a href="?page=babytuch_plugin&tab=logistics" class="nav-tab <?php if($tab==='logistics'):?>nav-tab-active<?php endif; ?>">Logistik</a>
      <a href="?page=babytuch_plugin&tab=returns" class="nav-tab <?php if($tab==='returns'):?>nav-tab-active<?php endif; ?>">Rücksendungen</a>
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'sending':
        $bpost = get_option('woocommerce_flat_rate_1_settings');
        $apost = get_option('woocommerce_flat_rate_3_settings');
        $apost_cost = $apost["cost"];
        $apost_name = $apost["title"];
        $bpost_cost = $bpost["cost"];
        $bpost_name = $bpost["title"];
        $small_package_limiter = get_option('small_package_limiter');

        $post_api_client_id = get_option('post_api_client_id');
        $post_api_client_secret = get_option('post_api_client_secret');
        $post_api_test_client_id = get_option('post_api_test_client_id');
        $post_api_test_client_secret = get_option('post_api_test_client_secret');
        $post_api_franking_license = get_option('post_api_franking_license');
        $post_api_testing = get_option('post_api_testing');

        ?>
        <form method="post" action="">
        <br>
        <h2>Versand</h2>
        <p style="color:grey;">Der Tab Versand verwaltet die beiden Versandmethoden und deren 
        Kosten sowie die Grenze zwischen normalen und grossen Verpackungen.</p>
            <p><b>1. Versandtyp Name</b></p>
            <input type="text" name="apost_name" value=<?php echo"$apost_name";?>>
            <br>
            <p>Kosten <?php echo"$apost_name";?></p>
            <input type="text" name="apost_cost" value=<?php echo"$apost_cost";?>>
            <br><br>
            <p><b>2. Versandtyp Name</b></p>
            <input type="text" name="bpost_name" value=<?php echo"$bpost_name";?>>
            <br>
            <p>Kosten <?php echo"$bpost_name";?></p>
            <input type="text" name="bpost_cost" value=<?php echo"$bpost_cost";?>>
            <br><br>
            <p><b>Grosse Verpackung ab (in Stk.)</b></p>
            <input type="text" name="small_package_limiter" value=<?php echo"$small_package_limiter";?>>
            <p style="color:grey;">Grenze zwischen normalen und grossen Verpackungen 
            (bsp. falls 3 eingestellt => normale Verpackung bis und mit 2 Tücher).</p>


            <h2>Post Etiketten API</h2>
            <p style="color:grey;">Einstellungen bezüglich der Interkation mit der Post API zum Erstellen der Versand- & Rückversandetiketten.</p>

            <p><b>Client ID</b></p>
            <input type="text" name="post_api_client_id" value=<?php echo"$post_api_client_id";?>>
            <p><b>Client Secret</b></p>
            <input type="text" name="post_api_client_secret" value=<?php echo"$post_api_client_secret";?>>
            <p><b>Frankierlizenz</b></p>
            <input type="text" name="post_api_franking_license" value=<?php echo"$post_api_franking_license";?>>
            <p><b>Test Client ID</b></p>
            <input type="text" name="post_api_test_client_id" value=<?php echo"$post_api_test_client_id";?>>
            <p><b>Test Client Secret</b></p>
            <input type="text" name="post_api_test_client_secret" value=<?php echo"$post_api_test_client_secret";?>>
            <p><b>Test Modus aktivieren</b></p>
            <input id="post_api_testing" type="checkbox" name="post_api_testing" value="1" <?php checked( '1', $post_api_testing ); ?>>
            <label for="post_api_testing">Test Modus aktiv</label>
            <br /><br />
            <input type="submit" value="Speichern" name="submit_sending"
            <?php echo isset($_POST["submit_sending"]) ? "disabled" : "";?>> Speichert alle Felder.
        </form>
    <?php
       break;
      case 'returns':
          $returns_reasons = get_option('returns_reasons');
          $return_days_limit = get_option('return_days_limit');
          ?>
          <form method="post" action="">
          <br>
          <h2>Rücksendungen</h2>
          <p style="color:grey;">Der Tab Rücksendungen verwaltet die Gründe für Rücksendungen bzw. Umtausche.</p>
              <p><b>Gründe für Rücksendungen</b></p>
              <?php
                $j=0;
                foreach($returns_reasons as $reason_pair){
                  $reason = $reason_pair["reason"];
                  ?>
                  <p>Grund</p>
                  <input style="width:250px" type="text" name="reason_<?php echo $j;?>" value='<?php echo"$reason";?>'>
                  <br>
                  <?php
                  $j++;
                }
              ?>
              <br><br>
              <p>Weiterer Grund hinzufügen</p>
              <input style="width:250px" type="text" name="new_reason">
              <p style="color:grey;"> Hier können beliebig viel neue Gründe hinzugefügt werden im Feld 
            “Weiterer Grund hinzufügen”. Diese Gründe werden dann den Kunden bei der 
            Rücksendung zur Auswahl gegeben.</p>
              <br><br>
              <p><b>Rücksendefrist (in Tagen)</b></p>
              <input style="width:50px" type="text" name="return_days_limit" value="<?php echo $return_days_limit; ?>">
              <p style="color:grey;">Die Rücksendefrist entspricht den Tagen in denen 
              eine Bestellung nach dem Versand noch zurückgesandt werden kann.</p>
              <br><br><br>
              <input type="submit" value="Speichern" name="submit_returns"
              <?php echo isset($_POST["submit_returns"]) ? "disabled" : "";?>> Speichert alle Felder.
          </form>
      <?php
        break;
      case 'logistics':
        $mail_logistics_get = get_option('mail_logistics');
        $name_logistics = get_option('name_logistics');
        $name2_logistics = get_option('name2_logistics');
        $adress_logistics = get_option('adress_logistics');
        $plz_logistics = get_option('plz_logistics');
        $city_logistics = get_option('city_logistics');
        $copy_to_admin = get_option('copy_to_admin');
        $babytuch_admin_email = get_option('babytuch_admin_email');
        ?>
        <form method="post" action="">
        <br>
        <h2>Logistik</h2>
        <p style="color:grey;">Der Tab Logistik verwaltet die Daten der Logistik. Die E-Mail Adresse erhält alle Benachrichtigungen, welche die Logistik betreffen (Versandauftrag, Rücksendeavisierung). Die restlichen Daten der Logistik werden für die Rücksendungen verwendet.</p>
            <br><p>E-Mail Adresse der Logistik</p>
            <input type="text" name="mail_logistics" value='<?php echo"$mail_logistics_get";?>'>
            <p style="color:grey;">Diese E-Mail erhält den
             Versandauftrag und die Nachbestellungs-Eingangskontrolle</p>
            <br>
            <p><input type='checkbox' name='copy_to_admin[]' 
            value='copy_to_admin'
            <?php echo ((int)$copy_to_admin==1 ? 'checked' : '');?>> 
            Kopie an Admin <br><div style="color:grey;">
             E-Mails an die Logistik erhält der Admin (<b><?php echo $babytuch_admin_email;?></b>)
             als Kopie.</div></p>
            <br>
            <p>Name der Logistik</p>
            <input type="text" name="name_logistics" value='<?php echo"$name_logistics";?>'>
            <br><br>
            <p>2. Name der Logistik</p>
            <input type="text" name="name2_logistics" value='<?php echo"$name2_logistics";?>'>
            <br><br>
            <p>Adresse der Logistik</p>
            <input type="text" name="adress_logistics" value='<?php echo"$adress_logistics";?>'>
            <br><br>
            <p>Postleitzahl der Logistik</p>
            <input type="text" name="plz_logistics" value='<?php echo"$plz_logistics";?>'>
            <br><br>
            <p>Ort der Logistik</p>
            <input type="text" name="city_logistics" value='<?php echo"$city_logistics";?>'>
            <br><br>
            <br><br>
            <input type="submit" value="Speichern" name="submit_mail"
            <?php echo isset($_POST["submit_mail"]) ? "disabled" : "";?>> Speichert alle Felder.
        </form>
    <?php
        break;
      default:
      $address = get_option('woocommerce_store_address');
      $plz = get_option('woocommerce_store_postcode');
      $city = get_option('woocommerce_store_city');
      $mwst = get_option('company_mwst_number');

      $account_details = get_option('woocommerce_bacs_accounts');
      $account_name = $account_details[0]["account_name"];
      $account_number = $account_details[0]["account_number"];
      $bank_name = $account_details[0]["bank_name"];
      $sort_code = $account_details[0]["sort_code"];
      $iban = $account_details[0]["iban"];
      $bic = $account_details[0]["bic"];

      $babytuch_admin_email = get_option('babytuch_admin_email');

      $bank_address = get_option('babytuch_bank_address');
      $bank_city = get_option('babytuch_bank_city')
      
    ?>
        <form method="post" action="">
        <br>
        <p style="color:grey;">Der erste Tab Allgemeines verwaltet die wichtigsten Geschäftsdaten, wie Adresse, E-Mail und Bankdaten. Diese werden in den entsprechenden Dokumenten angezeigt (Rechnung, Quartalsabrechnung).</p>
        <h2>Admin E-Mail</h2>
            <input type="text" name="babytuch_admin_email" value='<?php echo"$babytuch_admin_email";?>'>
            <p style="color:grey;">Die Admin E-Mail ist diejenige E-Mail welche alle Benachrichtigungen des Plugins erhält.</p>
            <br>
        <h2>Geschäftsdaten</h2>
        <p style="color:grey;">Diese Daten erscheinen im diversen Dokumenten</p>
            <p>Adresszeile</p>
            <input type="text" name="address" value='<?php echo"$address";?>'>
            <br><br>
            <p>PLZ</p>
            <input type="text" name="plz" value='<?php echo"$plz";?>'>
            <br><br>
            <p>Stadt</p>
            <input type="text" name="city" value='<?php echo"$city";?>'>
            <br><br>
            <p> MWST-Nummer</p>
            <input type="text" name="mwst" placeholder="CHE-000.000.000" value='<?php echo"$mwst";?>'>
            <br><br>
            <h2>Bankdaten</h2>
            <p style="color:grey;">Diese Daten erscheinen im Rechnungsdokument</p>
            <p>Inhaber</p>
            <input style="width:250px" type="text" name="account_name" value='<?php echo"$account_name";?>'>
            <br><br>
            <p>Bank</p>
            <input style="width:250px" type="text" name="bank_name" value='<?php echo"$bank_name";?>'>
            <br><br>
            <p>Bank Adresse</p>
            <input style="width:250px" type="text" name="bank_address" value='<?php echo"$bank_address";?>'>
            <br><br>
            <p>Bank PLZ Ort</p>
            <input style="width:250px" type="text" name="bank_city" value='<?php echo"$bank_city";?>'>
            <br><br>
            <p>BLZ</p>
            <input style="width:250px" type="text" name="sort_code" value='<?php echo"$sort_code";?>'>
            <br><br>
            <p>Konto-Nr.</p>
            <input style="width:250px" type="text" name="account_number" value='<?php echo"$account_number";?>'>
            <br><br>
            <p>BIC</p>
            <input style="width:250px" type="text" name="bic" value='<?php echo"$bic";?>'>
            <br><br>
            <p>IBAN</p>
            <input style="width:250px" type="text" name="iban" placeholder="CH10 0000 0000 0000 000 1" value='<?php echo"$iban";?>'>
            <br><br>
            <br><br>
            <input type="submit" value="Speichern" name="submit_general"
            <?php echo isset($_POST["submit_general"]) ? "disabled" : "";?>> Speichert alle Felder.
        </form>
        <h2>Weitere Aktionen</h2>
        <form method="post" action="">
            <input type="submit" value="Schriftarten für PDFs generieren" name="install_fonts">
            <span class="description">Diese Aktion ist nur nötig, wenn neue Schriftarten hinzugefügt worden sind oder das Plugin geändert worden ist.</span>
        </form>
    <?php
    endswitch; ?>
    </div>
  </div>
  <?php
  if(isset($_POST['submit_general'])){
    $address = $_POST['address'];
    update_option('woocommerce_store_address', $address);
    $plz = $_POST['plz'];
    update_option('woocommerce_store_postcode', $plz);
    $city = $_POST['city'];
    update_option('woocommerce_store_city', $city);
    $mwst = $_POST['mwst'];
    update_option('company_mwst_number', $mwst);
    $babytuch_admin_email = $_POST['babytuch_admin_email'];
    update_option('babytuch_admin_email', $babytuch_admin_email);
    $account_name = $_POST['account_name'];
    $bank_name = $_POST['bank_name'];
    $sort_code = $_POST['sort_code'];
    $account_number = $_POST['account_number'];
    $bic = $_POST['bic'];
    $iban = $_POST['iban'];
    $bank_array = array(array(
      'account_name'  => $account_name,
      'account_number' => $account_number,
      'bank_name'  => $bank_name,
      'sort_code' => $sort_code,
      'iban' => $iban,
      'bic' => $bic,));
    update_option('woocommerce_bacs_accounts', $bank_array);

    $bank_address = $_POST['bank_address'];
    $bank_city = $_POST['bank_city'];

    update_option('babytuch_bank_address', $bank_address);
    update_option('babytuch_bank_city', $bank_city);

    header("Refresh:0");
  }

  if(isset($_POST['submit_mail'])){
    $mail_logistics = $_POST['mail_logistics'];
    update_option('mail_logistics', $mail_logistics);
    $name_logistics = $_POST['name_logistics'];
    update_option('name_logistics', $name_logistics);
    $name2_logistics = $_POST['name2_logistics'];
    update_option('name2_logistics', $name2_logistics);
    $adress_logistics = $_POST['adress_logistics'];
    update_option('adress_logistics', $adress_logistics);
    $plz_logistics = $_POST['plz_logistics'];
    update_option('plz_logistics', $plz_logistics);
    $city_logistics = $_POST['city_logistics'];
    update_option('city_logistics', $city_logistics);
    if(empty($_POST["copy_to_admin"])){
      update_option('copy_to_admin', 0);
    }else{
        update_option('copy_to_admin', 1);
    }

    header("Refresh:0");
  }

  if(isset($_POST['submit_sending'])){
    $apost_name = $_POST['apost_name'];
    $apost_cost = $_POST['apost_cost'];
    $apost_array = array(
      'title'  => $apost_name,
      'tax_status' => 'none',
      'cost' => $apost_cost,);
    update_option('woocommerce_flat_rate_3_settings', $apost_array);
    $bpost_name = $_POST['bpost_name'];
    $bpost_cost = $_POST['bpost_cost'];
    $bpost["title"] = $bpost_name;
    $bpost["cost"] = $bpost_cost;
    $bpost_array = array(
      'title'  => $bpost_name,
      'cost' => $bpost_cost,);
    update_option('woocommerce_flat_rate_1_settings', $bpost_array);
    $small_package_limiter = $_POST['small_package_limiter'];
    update_option('small_package_limiter', $small_package_limiter);


	  $post_api_client_id = $_POST['post_api_client_id'];
	  $post_api_client_secret = $_POST['post_api_client_secret'];
	  $post_api_test_client_id = $_POST['post_api_test_client_id'];
	  $post_api_test_client_secret = $_POST['post_api_test_client_secret'];
	  $post_api_franking_license = $_POST['post_api_franking_license'];
	  $post_api_testing = $_POST['post_api_testing'];

	  update_option('post_api_client_id', $post_api_client_id);
	  update_option('post_api_client_secret', $post_api_client_secret);
	  update_option('post_api_test_client_id', $post_api_test_client_id);
	  update_option('post_api_test_client_secret', $post_api_test_client_secret);
	  update_option('post_api_franking_license', $post_api_franking_license);
	  update_option('post_api_testing', $post_api_testing);

    header("Refresh:0");
  }

  if(isset($_POST['submit_returns'])){
    $returns_reasons = get_option('returns_reasons');
    $new_returns_array = array();
    $j=0;
    foreach($returns_reasons as $reason_pair){
      $amount = $reason_pair["amount"];
      $value = $_POST["reason_$j"];
      $arr = array(
        'reason'  => $value,
        'amount' => $amount,
      );
      array_push($new_returns_array, $arr);
      $j++;
    }
    $value = $_POST["new_reason"];
    if(!empty($value)){
      $arr = array(
        'reason'  => $value,
        'amount' => 0,
      );
      array_push($new_returns_array, $arr);
    }
    update_option('returns_reasons',$new_returns_array);

    $return_days_limit = $_POST["return_days_limit"];
    update_option('return_days_limit', $return_days_limit);
    header("Refresh:0");
  }

  if(isset($_POST['install_fonts'])) {
      try {
          $plugin_dir = plugin_dir_path(dirname(__FILE__, 1));
          BT_PDF::generateFontFiles($plugin_dir);
          echo "Schriftarten wurden erfolgreich installiert.";
      } catch (Exception $e) {
          echo "Es gab einen Fehler beim Installieren der Schriftarten: \n". $e->getMessage();
      }
  }


?>
<br><br>
<?php
