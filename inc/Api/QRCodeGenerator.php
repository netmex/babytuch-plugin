<?php


namespace Inc\Api;


use Inc\Base\BaseController;
use Inc\Models\BT_OrderProcess;
use PHPQRCode\QRcode;

class QRCodeGenerator extends BaseController {
	/**
	 * Generates a QR Code for a given url and saves it in the qr codes folder in WP
	 * @param $url
	 * @param $filename
	 * @return string
	 */
	public function generate($url , $filename): string {
		$qr_path = $this->plugin_path.'/qr_codes/'.$filename;
		$qr_url = $this->plugin_url.'/qr_codes/'.$filename;

		QRcode::png(trim($url), $qr_path);

		return $qr_path;
	}

}