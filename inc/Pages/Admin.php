<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;

use \Inc\Api\SettingsApi;
use \Inc\Base\BaseController;
use \Inc\Api\Callbacks\LabelCallbacks;
use \Inc\Api\Callbacks\AdminCallbacks;

/**
* 
*/
class Admin extends BaseController
{
	public $settings;

	public $callbacks;

	public $pages = array();

	public $subpages = array();

	public function register() 
	{
		$this->settings = new SettingsApi();
		$this->callbacks = new AdminCallbacks();
		$this->setPages();
		$this->setSubPages();

		$this->setSettings();
		$this->setSections();
		$this->setFields();
		
		$this->settings->addPages( $this->pages )->withSubPage('Allgemeine Einstellungen')->addSubPages($this->subpages)->register();
    
	}

	public function setPages(){
		$this->pages = array(
			array(
				'page_title' => 'Babytuch Plugin', 
				'menu_title' => 'Babytuch', 
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_plugin', 
				'callback' => array($this->callbacks, 'adminDashboard'), 
				'icon_url' => 'dashicons-store', 
				'position' => 110
			)
		);
	}

	public function setSubPages(){
		$this->subpages = array(
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Lagerhaltung',
				'menu_title' => 'Lagerhaltung',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_supply_chain', 
				'callback' => array($this->callbacks, 'supply_chainDashboard')
			)
			/*,
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Verpackungsmaterial',
				'menu_title' => 'Verpackungsmaterial',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_supplements', 
				'callback' => array($this->callbacks, 'supplementsDashboard')
			),
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Produkte',
				'menu_title' => 'Produkte',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_products', 
				'callback' => array($this->callbacks, 'productsDashboard')
			)
			*/,
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Abrechnung',
				'menu_title' => 'Abrechnung',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_billing', 
				'callback' => array($this->callbacks, 'billingDashboard')
			),
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Bestellungen',
				'menu_title' => 'Bestellungen',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_orders', 
				'callback' => array($this->callbacks, 'ordersDashboard')
			),
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Etiketten',
				'menu_title' => 'Etiketten',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_labels', 
				'callback' => array($this->callbacks, 'labelDashboard')
			)
			/*,
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Zahlungen',
				'menu_title' => 'Zahlungen',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_payments', 
				'callback' => array($this->callbacks, 'paymentsDashboard')
			),
			array(
				'parent_slug' => 'babytuch_plugin', 
				'page_title' => 'Lieferungen',
				'menu_title' => 'Lieferungen',
				'capability' => 'manage_options', 
				'menu_slug' => 'babytuch_shippings', 
				'callback' => function(){echo'<h2>Lieferungen</h2>';}
			)
			*/
		);
	}

	public function setSettings(){
		$args = array(
			array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_amount',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'label_amount',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			)/*,array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_limit',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_new_order_amount',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_new_order_sent',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_last_order_date',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_last_check_date',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_interval',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_amount_1',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_amount_2',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			)*/,array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'packaging_big_amount',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			),array(
				'option_group' => 'babytuch_options_group',
				'option_name' => 'supplement_amount',
				'callback' => array($this->callbacks, 'babytuchOptionsGroup')
			)
		);

		$this->settings->setSettings($args);
	}

	public function setSections(){
		$args = array(
			array(
				'id' => 'babytuch_admin_index',
				'title' => 'Allgemeine Einstellungen',
				'callback' => array($this->callbacks, 'babytuchAdminSection'),
				'page' => 'babytuch_plugin'
			), array(
				'id' => 'babytuch_labels_index',
				'title' => 'Etiketten',
				'callback' => array($this->callbacks, 'babytuchLabelSection'),
				'page' => 'babytuch_labels'
			)
		);

		$this->settings->setSections($args);
	}

	public function setFields(){
		$args = array(
			array(
				'id' => 'packaging_amount',
				'title' => '<h2>Verpackungen (Klein)</h2>',
				'callback' => array($this->callbacks, 'babytuchPackagingAmount'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_amount', 'class' => 'example-class')

			)/*, array(
				'id' => 'packaging_limit',
				'title' => '<p style="color:blue">Unterer Grenzwert für Nachbestellungen (Reserve)</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingLimit'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_limit', 'class' => 'example-class')

			), array(
				'id' => 'packaging_new_order_amount',
				'title' => '<p style="color:blue">Menge der Nachbestellungen</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingNewAmount'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_new_order_amount', 'class' => 'example-class')

			), array(
				'id' => 'packaging_new_order_sent',
				'title' => '<p style="color:blue">Nachbestellung gesendet (1/0) </p>',
				'callback' => array($this->callbacks, 'babytuchPackagingNewOrderSent'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_new_order_sent', 'class' => 'example-class')

			)*/, array(
				'id' => 'labels_pdf',
				'title' => 'Etiketten Generieren',
				'callback' => array($this->callbacks, 'babytuchLabelGenerator'),
				'page' => 'babytuch_labels',
				'section' => 'babytuch_labels_index',
				'args' => array('label_for' => 'label_amount', 'class' => 'example-class')

			)/*, array(
				'id' => 'packaging_last_order_date',
				'title' => '<p style="color:blue">Letzte Nachbestellung</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingLastOrderDate'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_last_order_date', 'class' => 'example-class')

			), array(
				'id' => 'packaging_last_check_date',
				'title' => '<p style="color:blue">Letzter Check</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingLastCheckDate'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_last_check_date', 'class' => 'example-class')

			), array(
				'id' => 'packaging_interval',
				'title' => '<p style="color:blue">Interval (in Tagen)</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingInterval'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_interval', 'class' => 'example-class')

			), array(
				'id' => 'packaging_amount_1',
				'title' => '<p style="color:blue">Alter Bestand 1</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingAmount1'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_amount_1', 'class' => 'example-class')

			), array(
				'id' => 'packaging_amount_2',
				'title' => '<p style="color:blue">Alter Bestand 2</p>',
				'callback' => array($this->callbacks, 'babytuchPackagingAmount2'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_amount_2', 'class' => 'example-class')

			)*/, array(
				'id' => 'packaging_big_amount',
				'title' => '<h2>Verpackungen (Gross)</h2>',
				'callback' => array($this->callbacks, 'babytuchPackagingBigAmount'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'packaging_big_amount', 'class' => 'example-class')

			), array(
				'id' => 'label_amount',
				'title' => '<h2>Etiketten</h2>',
				'callback' => array($this->callbacks, 'babytuchLabelAmount'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'label_amount', 'class' => 'example-class')

			), array(
				'id' => 'supplement_amount',
				'title' => '<h2>Beilagen</h2>',
				'callback' => array($this->callbacks, 'babytuchSupplementAmount'),
				'page' => 'babytuch_plugin',
				'section' => 'babytuch_admin_index',
				'args' => array('label_for' => 'supplement_amount', 'class' => 'example-class')

			)
		);

		$this->settings->setFields($args);
	}

}