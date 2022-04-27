<?php 
/**
 * @package BabytuchPlugin
 */
namespace Inc\Api\Callbacks;

use Inc\Base\BaseController;

class AdminCallbacks extends BaseController{
    public function adminDashboard(){
        return require_once("$this->plugin_path/templates/admin.php");
    }

    public function babytuchOptionsGroup($input){
        return $input;
    }

    public function babytuchAdminSection(){
        echo 'Aktuelle Mengen des Verpackungsmaterials';
    }


    //FELDER VERPACKUNGEN(KLEIN)

    public function babytuchPackagingAmount(){
        $value = esc_attr( get_option('packaging_amount') );
        echo '<input type="text" class="regular-text" name="packaging_amount" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingLimit(){
        $value = esc_attr( get_option('packaging_limit') );
        echo '<input type="text" style="width: 50px" class="regular-text" name="packaging_limit" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingNewAmount(){
        $value = esc_attr( get_option('packaging_new_order_amount') );
        echo '<input type="text" style="width: 50px" class="regular-text" name="packaging_new_order_amount" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingNewOrderSent(){
        $value = esc_attr( get_option('packaging_new_order_sent') );
        echo '<input type="text" style="width: 50px" class="regular-text" name="packaging_new_order_sent" value="' .$value . '" placeholder="' .$value.'">';
    }
    
    public function babytuchPackagingLastOrderDate(){
        $value = esc_attr( get_option('packaging_last_order_date') );
        echo '<input type="date" style="width: 200px" class="regular-text" name="packaging_last_order_date" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingLastCheckDate(){
        $value = esc_attr( get_option('packaging_last_check_date') );
        echo '<input type="date" style="width: 200px" class="regular-text" name="packaging_last_check_date" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingInterval(){
        $value = esc_attr( get_option('packaging_interval') );
        echo '<input type="text" style="width: 50px" class="regular-text" name="packaging_interval" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingAmount1(){
        $value = esc_attr( get_option('packaging_amount_1') );
        echo '<input type="text" style="width: 50px" class="regular-text" name="packaging_amount_1" value="' .$value . '" placeholder="' .$value.'">';
    }

    public function babytuchPackagingAmount2(){
        $value = esc_attr( get_option('packaging_amount_2') );
        echo '<input type="text" style="width: 50px" class="regular-text" name="packaging_amount_2" value="' .$value . '" placeholder="' .$value.'">';
    }

    //FELDER VERPACKUNGEN(GROSS)

    public function babytuchPackagingBigAmount(){
        $value = esc_attr( get_option('packaging_big_amount') );
        echo '<input type="text" class="regular-text" name="packaging_big_amount" value="' .$value . '" placeholder="' .$value.'">';
    }


    //FELDER ETIKETTEN
    public function babytuchLabelAmount(){
        $value = esc_attr( get_option('label_amount') );
        echo '<input type="text" class="regular-text" name="label_amount" value="' .$value . '" placeholder="' .$value.'">';
    }


    //FELDER BEILAGEN
    public function babytuchSupplementAmount(){
        $value = esc_attr( get_option('supplement_amount') );
        echo '<input type="text" class="regular-text" name="supplement_amount" value="' .$value . '" placeholder="' .$value.'">';
    }





    //ALLE SUBPAGES
    /**
     * 
     * 
     * 
     * 
     * 
     */
    //VERPACKUNGSMATERIAL SUBPAGE
    public function supplementsDashboard(){
        return require_once("$this->plugin_path/templates/supplements.php");
    }
    //PRODUKTE SUBPAGE
    public function productsDashboard(){
        return require_once("$this->plugin_path/templates/products.php");
    }

    //BESTELLUNGEN SUBPAGE
    public function ordersDashboard(){
        return require_once("$this->plugin_path/templates/orders.php");
    }

    //BESTELLUNGEN SUBPAGE
    public function supply_chainDashboard(){
        return require_once("$this->plugin_path/templates/supply_chain.php");
    }

    //ZAHLUNGEN SUBPAGE
    public function paymentsDashboard(){
        return require_once("$this->plugin_path/templates/payments.php");
    }

    //LABELS SUBPAGE
    public function labelDashboard(){
        return require_once("$this->plugin_path/templates/labels.php");
    }

    //BILLING SUBPAGE
    public function billingDashboard(){
        return require_once("$this->plugin_path/templates/billing.php");
    }

    //REFERRALS SUBPAGE
    public function referralsDashboard(){
        return require_once("$this->plugin_path/templates/referrals.php");
    }
    

    public function babytuchLabelSection(){
        echo 'PDF Generator...';
    }

    public function babytuchLabelGenerator(){
        echo '<input type="text" class="regular-text" name="label_amount" value="Test2" placeholder="Test">';
    }

}