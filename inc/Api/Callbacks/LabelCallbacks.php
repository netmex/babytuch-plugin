<?php 
/**
 * @package BabytuchPlugin
 */
namespace Inc\Api\LabelCallbacks;

use Inc\Base\BaseController;

class LabelCallbacks extends BaseController{
    public function adminDashboard(){
        return require_once("$this->plugin_path/templates/labels.php");
    }

    public function babytuchOptionsGroup($input){
        return $input;
    }

    public function babytuchAdminSection(){
        echo 'Etiketten';
    }

    public function babytuchPackagingAmount(){
        $current_amount = $this->get_package_amount();
        //$value = esc_attr( get_option('text_example') );
        echo '<input type="text" class="regular-text" name="packaging_amount" value="' .$current_amount . '" placeholder="' .$current_amount.'">';
    }

    public function babytuchLabelAmount(){
        $current_amount = $this->get_label_amount();
        //$value = esc_attr( get_option('text_example') );
        echo '<input type="text" class="regular-text" name="label_amount" value="' .$current_amount . '" placeholder="' .$current_amount.'">';
    }

    public function get_package_amount(){
        global $wpdb, $table_prefix;
        return $wpdb->get_var("SELECT option_value FROM wp_options WHERE option_id=3093");
    }

    public function get_label_amount(){
        global $wpdb, $table_prefix;
        return $wpdb->get_var("SELECT option_value FROM wp_options WHERE option_id=3097");
    }


}