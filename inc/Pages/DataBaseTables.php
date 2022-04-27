<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;

use Inc\Api\Helpers;
use Inc\Models\BT_OrderProcess;


class DataBaseTables{

    public static function install_inventory() {
        global $wpdb;
        $db_version_inventory = '8.0';
        $installed_ver = get_option("db_version_inventory");
        if($installed_ver != $db_version_inventory){
            
            //$wpdb->prefix;
            $table_name = 'babytuch_inventory';
                
            $charset_collate = $wpdb->get_charset_collate();

            //INVENTORY TABLE
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                item_name tinytext NOT NULL,
                amount int(10) NOT NULL,
                new_order_amount int(10) NOT NULL,
                new_order_limit int(10) NOT NULL,
                reorder_multiple int(10) DEFAULT 10 NOT NULL,
                new_order_sending boolean NOT NULL,
                new_order_sending_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                receiving_code tinytext NOT NULL,
                new_order_received boolean DEFAULT 0 NOT NULL,
                last_reorder_received datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                last_special_order datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                stock_1 int(10) DEFAULT 0 NOT NULL,
                stock_2 int(10) DEFAULT 0 NOT NULL,
                stock_3 int(10) DEFAULT 0 NOT NULL,
                stock_4 int(10) DEFAULT 0 NOT NULL,
                stock_5 int(10) DEFAULT 0 NOT NULL,
                stock_6 int(10) DEFAULT 0 NOT NULL,
                stock_7 int(10) DEFAULT 0 NOT NULL,
                stock_8 int(10) DEFAULT 0 NOT NULL,
                stock_9 int(10) DEFAULT 0 NOT NULL,
                stock_10 int(10) DEFAULT 0 NOT NULL,
                stock_11 int(10) DEFAULT 0 NOT NULL,
                stock_12 int(10) DEFAULT 0 NOT NULL,
                stock_13 int(10) DEFAULT 0 NOT NULL,
                stock_14 int(10) DEFAULT 0 NOT NULL,
                stock_15 int(10) DEFAULT 0 NOT NULL,
                stock_16 int(10) DEFAULT 0 NOT NULL,
                stock_17 int(10) DEFAULT 0 NOT NULL,
                stock_18 int(10) DEFAULT 0 NOT NULL,
                stock_19 int(10) DEFAULT 0 NOT NULL,
                stock_20 int(10) DEFAULT 0 NOT NULL,
                stock_21 int(10) DEFAULT 0 NOT NULL,
                stock_22 int(10) DEFAULT 0 NOT NULL,
                stock_23 int(10) DEFAULT 0 NOT NULL,
                stock_24 int(10) DEFAULT 0 NOT NULL,
                stock_25 int(10) DEFAULT 0 NOT NULL,
                stock_26 int(10) DEFAULT 0 NOT NULL,
                stock_27 int(10) DEFAULT 0 NOT NULL,
                stock_28 int(10) DEFAULT 0 NOT NULL,
                stock_29 int(10) DEFAULT 0 NOT NULL,
                stock_30 int(10) DEFAULT 0 NOT NULL,
                stock_31 int(10) DEFAULT 0 NOT NULL,
                stock_32 int(10) DEFAULT 0 NOT NULL,
                stock_33 int(10) DEFAULT 0 NOT NULL,
                stock_34 int(10) DEFAULT 0 NOT NULL,
                stock_35 int(10) DEFAULT 0 NOT NULL,
                stock_36 int(10) DEFAULT 0 NOT NULL,
                stock_37 int(10) DEFAULT 0 NOT NULL,
                stock_38 int(10) DEFAULT 0 NOT NULL,
                stock_39 int(10) DEFAULT 0 NOT NULL,
                stock_40 int(10) DEFAULT 0 NOT NULL,
                stock_41 int(10) DEFAULT 0 NOT NULL,
                stock_42 int(10) DEFAULT 0 NOT NULL,
                stock_43 int(10) DEFAULT 0 NOT NULL,
                stock_44 int(10) DEFAULT 0 NOT NULL,
                stock_45 int(10) DEFAULT 0 NOT NULL,
                stock_46 int(10) DEFAULT 0 NOT NULL,
                stock_47 int(10) DEFAULT 0 NOT NULL,
                stock_48 int(10) DEFAULT 0 NOT NULL,
                stock_49 int(10) DEFAULT 0 NOT NULL,
                stock_50 int(10) DEFAULT 0 NOT NULL,
                stock_51 int(10) DEFAULT 0 NOT NULL,
                stock_52 int(10) DEFAULT 0 NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            //INSTALL INITIAL DATA
            
            $name = 'packagings';
            $amount = 300;

            $name2 = 'labels';
            $amount2 = 300;

            $name3 = 'supplements';
            $amount3 = 300;

            $name4 = 'packagings_large';
            $amount4 = 300;

            $name5 = 'referral_labels';
            $amount5 = 300;
                
            $table_name = 'babytuch_inventory';
                
            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'item_name' => $name, 
                    'amount' => $amount, 
                    'new_order_amount' => 500,
                    'new_order_limit' => 50,
                    'new_order_sending' => false,
                    'receiving_code' => Helpers::generateUniqueCode(001),
                )
            );

            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'item_name' => $name2, 
                    'amount' => $amount2,
                    'new_order_amount' => 500,
                    'new_order_limit' => 50,
                    'new_order_sending' => false,
                    'receiving_code' => Helpers::generateUniqueCode(002),
                )
            );

            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'item_name' => $name3, 
                    'amount' => $amount3,
                    'new_order_amount' => 500,
                    'new_order_limit' => 50,
                    'new_order_sending' => false,
                    'receiving_code' => Helpers::generateUniqueCode(003),
                )
            );

            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'item_name' => $name4, 
                    'amount' => $amount4,
                    'new_order_amount' => 500,
                    'new_order_limit' => 50,
                    'new_order_sending' => false,
                    'receiving_code' => Helpers::generateUniqueCode(004),
                )
            );

            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'item_name' => $name5, 
                    'amount' => $amount5,
                    'new_order_amount' => 500,
                    'new_order_limit' => 50,
                    'new_order_sending' => false,
                    'receiving_code' => Helpers::generateUniqueCode(005),
                )
            );

            //INSERT EXISTING WC PRODUCTS
            $products = wc_get_products( array('numberposts' => -1) );
            foreach( $products as $product ){ 
                if($product->is_type( 'variable' )){
                    $children   = $product->get_children();
                    foreach($children as $child){
                        $child_product = wc_get_product($child);
                        $product_slug = $child_product->get_slug();
                        $product_id = $child_product->get_id();
                        $product_amount = $child_product->get_stock_quantity();
                        /*$attr = $child_product->get_attributes();
                        $size = $attr["groesse"];
                        $child_product->set_slug($product_slug.'-'.$size);
                        $product_slug = $child_product->get_slug();*/
                        $wpdb->insert( 
                            $table_name, 
                            array( 
                                'time' => current_time( 'mysql' ), 
                                'item_name' => $product_slug, 
                                'amount' => $product_amount,
                                'new_order_amount' => 500,
                                'new_order_limit' => 2,
                                'new_order_sending' => false,
                                'receiving_code' => Helpers::generateUniqueCode($product_id),
                            )
                        );
                    }
                }
                /*else{
                    $product_slug = $product->get_slug();
                    $product_id = $product->get_id();
                    $product_amount = $product->get_stock_quantity();
                    $wpdb->insert( 
                        $table_name, 
                        array( 
                            'time' => current_time( 'mysql' ), 
                            'item_name' => $product_slug, 
                            'amount' => $product_amount,
                            'new_order_amount' => 500,
                            'new_order_limit' => 0,
                            'new_order_sending' => false,
                            'receiving_code' => Helpers::generateUniqueCode($product_id),
                        )
                    );
                }*/
            }   

            update_option( 'db_version_inventory', $db_version_inventory );

            //Common Settings
            update_option( 'products_interval', 7 );
            //update_option( 'products_last_reorder', date('Y-m-d',time()) );
            //update_option( 'products_next_reorder', date('Y-m-d',time()) );
            //update_option( 'products_last_check', date('Y-m-d',time()) );
            //update_option('billing_last_generated', date('Y-m-d',time()));
            //update_option('billing_next_generated', date('Y-m-d',time()));
            update_option('billing_interval', 3);
            update_option('small_package_limiter', 3);
            update_option('products_round_limit', 40);
            update_option('reorder_document_num', 1);

            update_option('billing_total_sold', 0);
            update_option('billing_best_sold_product', array('name'=>'n/a','amount'=>0));
            update_option('billing_total_returns', 0);
            update_option('billing_total_most_used_reason', array('reason'=>'n/a','amount'=>0));
            update_option('billing_google', 0);
            update_option('billing_facebook', 0);
            update_option('billing_flyer_qr', 0);
            update_option('billing_clients_from_qr', 0);
            update_option('billing_advice', 0);
            update_option('billing_total_current_stock', 0);
            update_option('billing_licence_fee', 0);

            

        }
    }

    //ORDER PROCESS TABLE
    public static function install_order_process() {
        global $wpdb;
        $db_version_order_process = '9.0';
        $installed_ver = get_option("db_version_order_process");
        if($installed_ver != $db_version_order_process){   
            $charset_collate = $wpdb->get_charset_collate();
            $table_name = 'babytuch_order_process';

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                date_order_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                order_id int(10) NOT NULL,
                order_email tinytext NOT NULL,
                order_status tinytext NOT NULL,
                total_price float(10) NOT NULL,
                processing_code tinytext NOT NULL,
                processing_activated boolean NOT NULL,
                sent_code tinytext NOT NULL,
                sent_activated boolean NOT NULL,
                date_delivered datetime DEFAULT '0000-00-00 00:00:00',
                return_code tinytext NOT NULL,
                return_activated boolean NOT NULL,
                return_products text NOT NULL DEFAULT '',
                return_control_started boolean DEFAULT 0 NOT NULL,
                return_received_code tinytext NOT NULL,
                return_received_activated boolean NOT NULL,
                return_received_admin_code tinytext NOT NULL,
                return_received_admin_activated boolean NOT NULL,
                refunded boolean NOT NULL,
                replace_activated boolean DEFAULT 0 NOT NULL,
                replacement_order int(10) DEFAULT 0 NOT NULL,
                is_replacement_order boolean DEFAULT 0 NOT NULL,
                is_replacement_order_of int(10) DEFAULT 0 NOT NULL,
                cost_of_sending float(10) DEFAULT 0 NOT NULL,
                not_ok boolean DEFAULT 0 NOT NULL,
                return_reason text NOT NULL DEFAULT '',
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            //INSERT EXISTING WC ORDERS
            $orders = wc_get_orders( array('numberposts' => -1) );
            if(!empty($orders)){
                foreach( $orders as $order ){ 
                    $order_data = $order->get_data();
                    if($order_data["parent_id"]==0){
						$order_id = $order->get_id();
	                    $existing = BT_OrderProcess::load_by_order_id($order_id);
						if(!$existing) {
							BT_OrderProcess::create_from_order($order);
						}
                    }
                }
            }
            update_option( 'db_version_order_process', $db_version_order_process );
        }
    }

}