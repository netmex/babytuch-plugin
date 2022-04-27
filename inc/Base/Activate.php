<?php
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Base;

use Inc\Api\BT_PDF;
use Inc\Pages\DataBaseTables;

class Activate extends BaseController {
	public static function activate() {
		flush_rewrite_rules();
		self::create_return_labels_dir();
		self::create_shipping_labels_dir();
		self::create_logistic_information_dir();
		DataBaseTables::install_order_process();
		DataBaseTables::install_inventory();
		BT_PDF::generateFontFiles(plugin_dir_path( dirname( __FILE__, 2 ) ));
	}

	public static function create_shipping_labels_dir() {
		$dirname = self::getShippingLabelPath();
		if(!file_exists($dirname)) wp_mkdir_p($dirname);
	}

	public static function create_return_labels_dir() {
		$dirname = self::getReturnLabelPath();
		if(!file_exists($dirname)) wp_mkdir_p($dirname);
	}

	public static function create_logistic_information_dir() {
		$dirname = self::getLogisticInformationPath();
		if(!file_exists($dirname)) wp_mkdir_p($dirname);
	}
}