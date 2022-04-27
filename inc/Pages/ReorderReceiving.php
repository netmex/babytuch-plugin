<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;


class ReorderReceiving
{
	public function register() {
        add_shortcode( 'reorder_receiving', array($this, 'reorder_receiving_form'));
    }

    function reorder_receiving_form( $attributes ) {
        if (isset($_GET['code'])) {
            $receive_code = $_GET['code'];
            echo $receive_code;
            $check_value = $this->check_code($receive_code);
            $this->process_check_value($check_value);
        } else {
            echo ''?>
            <html>
            <body>

            <h3>Bitte füllen Sie das Formular aus, um den Erhalt des Artikels zu bestätigen.</h3>

            <form method="post" action="">
            <label for="fname">Code: </label><br>
            <input type="text" id="fname" name="code" value=""><br><br>
            <input type="submit" value="Bearbeitung aktivieren" name="submit">
            </form> 
            </body>
            </html>
            <?php
        }
       
        if(isset($_POST['submit'])){
            global $wpdb;
            $receive_code = $_POST['code'];
            echo $receive_code;

            $check_value = $this->check_code($receive_code);
            $this->process_check_value($check_value);
        }
    }

    public function check_code($receive_code){
        //.....check if return_activated == false
        global $wpdb;
        $details = $wpdb->get_results( 
            $wpdb->prepare( "
                SELECT * FROM babytuch_inventory
                WHERE receiving_code = %s", 
                $receive_code 
            ) 
        );
        $details_json = json_decode(json_encode($details), true);
        if(empty($details_json)){
            return 'no_reorder_error';
        }elseif($details_json[0]["new_order_received"]=='1'){
            return 'activated';
        }elseif($details_json[0]["new_order_sending"]=='0'){
            return 'no_reorder_error';
        }
        else{
            return $details_json;
        }
    }

    public function process_check_value($check_value){
        if($check_value!='no_reorder_error'){
            if($check_value == 'activated'){
                echo ''?>
                <html>
                <body>
                <h3>Diese Nachbestellung wurde erfolgreich bestätigt.</h3>
                <h4>Der Empfang dieser Nachbestellung wurde von der Logistik bestätigt.</h4>
                </body>
                </html>
                <?php
            }else{
                $reorder_amount = $check_value[0]["new_order_amount"];
                $name = $check_value[0]["item_name"];
                $sending_date = $check_value[0]["new_order_sending_date"];
                
                if($name!='packagings' and $name!='labels'
                and $name!='supplements' and $name!='packagings_large' and
                $name!='referral_labels'){
                    global $wpdb;
                    $table_name2 = $wpdb->prefix . 'posts';
                    $res = $wpdb->get_results( 
                            $wpdb->prepare( "
                                SELECT * FROM $table_name2
                                WHERE post_name  = %s", 
                                $name 
                            ) 
                    );
                    $res_json = json_decode(json_encode($res), true);
                    //var_dump($res_json[0]);
                    if($res_json){
                        $name=$res_json[0]["post_title"];
                        $name2 = substr($name, -3,-2);
                        $name3 = substr($name, -4,-3);
                        if($name2=='-'){
                            $name2=substr($name, 0,-4);
                        }elseif($name3=='-'){
                            $name2=substr($name, 0,-5);
                        }else{
                            $name2=$name;
                        }
                    }
                    $name = $check_value[0]["item_name"];
                    $size = $res_json[0]["post_excerpt"];
                    $prod_id = $res_json[0]["ID"];
                    $prod = wc_get_product($prod_id);
                
                    $parent_id = $prod->get_parent_id();
                    $parent = wc_get_product($parent_id);
                
                    $img_url = wp_get_attachment_url( $parent->get_image_id() );
                    $attachment_ids = $parent->get_gallery_image_ids();
                    if($attachment_ids){
                    $img_url = wp_get_attachment_url($attachment_ids[0]);
                    }
                    $vari ="";
                }else{
                    $vari = $check_value[0]["reorder_multiple"];
                    $size='';
                    if($name=='packagings'){
                        $name2='Verpackungen';
                        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
                    }elseif($name=='labels'){
                        $name2='Klebeetiketten-Blätter';
                        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/label.PNG';
                    }elseif($name=='supplements'){
                        $name2='Beilagen';
                        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/suppl.PNG';
                    }elseif($name=='packagings_large'){
                        $name2='Verpackungen (Gross)';
                        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/package.PNG';
                    }elseif($name=='referral_labels'){
                        $name2='Vermittlungsbögen';
                        $img_url = get_home_url().'/wp-content/plugins/babytuch-plugin/templates/referral_label.PNG';
                    }
                }

                echo ''?>
                <html>
                <body>
                <h2>Nachbestellung von: <b><?php echo "$name2" ?></b></h2>
                <img src=<?php echo "$img_url" ?> width="200px" height="150px"/><br>
                <h2><b><?php if($size){echo "$size";}?></b></h2>
                <h1 style="font-size:48px;">Menge: <b><?php echo "$reorder_amount" ?></b></h1>
                <?php if($vari){echo "<h2>(Verpackungen à <b>$vari</b> Stück)</h2>";}?>
                <h3>Bitte bestätigen Sie den Empfang oder geben Sie die Anzahl tatsächlich eingetroffenen Stücke an (bei keinen 0 eingeben).</h3>
                <form method="post" action="">
                <input type="submit" style="font-weight:bold;font-size:20px;" value="OK" name="ok"><br><br><br>
                Gelieferte Menge: <input type="text" name="actual_amount" value=""><br><br>
                <input type="submit" value="Korrektur" name="not_ok">
                </form> 
                </body>
                </html>
                <?php
            }
            
        }else{
            echo ''?>
            <html>
            <body>
            <h3>Es gab ein Problem</h3>
            <h4>
            Bitte prüfen Sie die Nachbestellung und füllen Sie ansonsten das Formular aus.</h4>
            </body>
            </html>
            <?php
        }

        if(isset($_POST['ok'])){
            global $wpdb;
            $date = date('Y-m-d h:i:s',time());
            $value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE babytuch_inventory SET new_order_received = true,
                    new_order_sending = false, last_reorder_received = %s
                    WHERE item_name = %s", 
                    $date, $name
                ) 
            );
            if($name=='packagings' or $name=='labels' or $name=='supplements' 
                or $name=='packagings_large' or $name=='referral_labels' ){
                $res = $wpdb->get_results( 
                    $wpdb->prepare( "
                        SELECT * FROM babytuch_inventory
                        WHERE item_name = %s", 
                        $name
                    ) 
                );
                $res_json = json_decode(json_encode($res), true);
                $old_amount = $res_json[0]["amount"];
                $vari = $res_json[0]["reorder_multiple"];
                $new_total = (int)$old_amount + ((int)$reorder_amount*(int)$vari);
                /*if($name == 'packagings_large'){
                    update_option('packaging_big_amount', $new_total);
                }elseif($name == 'labels'){
                    update_option('label_amount', $new_total);
                }elseif($name == 'packagings'){
                    update_option('packaging_amount', $new_total);
                }elseif($name == 'supplements'){
                    update_option('supplement_amount', $new_total);
                }*/
                
                $value = $wpdb->query( 
                    $wpdb->prepare( "
                        UPDATE babytuch_inventory SET amount = %s
                        WHERE item_name = %s", 
                        $new_total, $name
                    ) 
                );
            }else{
                $products = wc_get_products( array('numberposts' => -1) );
                foreach($products as $product){
                    $product_single = wc_get_product($product);
                    $children   = $product_single->get_children();
                    $num = count($children);
                    for($i=0; $i<$num;$i++){
                        if($product_single->is_type( 'variable' )){
                            $child = $children[$i];
                            $child_product = wc_get_product($child);
                            $child_slug = $child_product->get_slug();
                            if($child_slug == $name){
                                $old_amount = $child_product->get_stock_quantity();
                                $new_total = $old_amount + (int)$reorder_amount;
                                $child_product->set_stock_quantity($new_total);
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
                                        WHERE item_name = %s ", 
                                        $new_total, $name
                                    ) 
                                    );
                            break;
                            }

                        }
                    }
                }
            }

            echo'<h2>Erhalt erfolgreich bestätigt.</h2>';
            header("Refresh:0");
            /*if($value){
                $subject = "Nachbestellung empfangen";
                $message = "Der Empfang der Nachbestellung wurde erfolgreich 
                    bestätigt.";
                wp_mail( 'davebasler@hotmail.com', $subject, $message );
            }*/
        }
        if(isset($_POST['not_ok'])){
            /*global $wpdb;
            $date = date('Y-m-d h:i:s',time());
            $value = $wpdb->query( 
                $wpdb->prepare( "
                    UPDATE wp_inventory SET new_order_received = true,
                    last_reorder_received = %s
                    WHERE item_name = %s", 
                    $date, $name
                ) 
            );
            */
            if(!empty($_POST['actual_amount'])){
                $actual_amount = $_POST['actual_amount'];
                $subject = "Nachbestellung nicht in Ordnung";
                $message = "Die Nachbestellung ist nicht komplett: $name ($reorder_amount Stück).
                Tatsächliche erhaltene Menge: $actual_amount";
                $babytuch_admin_email = get_option('babytuch_admin_email');
                wp_mail( $babytuch_admin_email, $subject, $message );
                echo'<h2 style="color:green;">Nachbestellung erfolgreich gemeldet!</h2>';
            }else{
               echo'<h2 style="color:red;">Bitte Menge angeben!</h2>';
            }
                
        }

    }

    
              
                  
}