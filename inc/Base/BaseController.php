<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Base;

class BaseController
{
	public $plugin_path;

	public $plugin_url;

	public $plugin;

	public $home_url;

	public static string $shipping_label_folder = 'shipping-labels';
	public static string $return_label_folder = 'return-labels';
	public static string $logistic_information_folder = 'logistic-information';


	public function __construct() {
		$this->plugin_path = plugin_dir_path( dirname( __FILE__, 2 ) );
		$this->plugin_url = plugin_dir_url( dirname( __FILE__, 2 ) );
		$this->plugin = plugin_basename( dirname( __FILE__, 3 ) ) . '/babytuch-plugin.php';
		$this->home_url = get_home_url();
	}

	public static function getShippingLabelPath():string {
		return self::getUploadPath(self::$shipping_label_folder);
	}

	public static function getShippingLabelURL():string {
		return self::getUploadURL(self::$shipping_label_folder);
	}

	public static function getReturnLabelPath():string {
		return self::getUploadPath(self::$return_label_folder);
	}

	public static function getReturnLabelURL():string {
		return self::getUploadURL(self::$return_label_folder);
	}

	public static function getLogisticInformationPath():string {
		return self::getUploadPath(self::$logistic_information_folder);
	}

	public static function getLogisticInformationURL():string {
		return self::getUploadURL(self::$logistic_information_folder);
	}

	private static function getUploadPath($subfolder):string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir']."/".$subfolder;
	}

	private static function getUploadURL($subfolder):string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl']."/".$subfolder;
	}

}