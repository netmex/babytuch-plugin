<?php


namespace Inc\Api;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use WC_Order;

class Helpers {

	public static function getLogisticsAddress(): Address {
		return new Address(
			get_option('name_logistics'),
			"",
			get_option('name2_logistics'),
			get_option('adress_logistics'),
			get_option('city_logistics'),
			get_option('plz_logistics'),
			"CH",
			get_option('mail_logistics')
		);
	}

	/**
	 * @untested
	 * @return Address
	 */
	public static function getAdminAddress(): Address {
		$firstname = "";
		$lastname = WC()->countries->get_base_address();
		$street = WC()->countries->get_base_address_2();
		$city = WC()->countries->get_base_city();
		$zip = WC()->countries->get_base_postcode();
		$email = get_option('admin_email');
		$country = WC()->countries->get_base_country();
		return new Address($firstname, $lastname, "", $street, $city, $zip, $country, $email);
	}

	/**
	 * @param $orderId
	 * @param $recipientAddress
	 * @param $senderAddress
	 *
	 * @untested
	 * @return array
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function createAddressLabel($orderId, $recipientAddress, $senderAddress, bool $return = false): array {

		$post_api_testing = get_option('post_api_testing');
		if($post_api_testing) {
			$post_api_client_id = get_option('post_api_test_client_id');
			$post_api_client_secret = get_option('post_api_test_client_secret');
		} else {
			$post_api_client_id = get_option('post_api_client_id');
			$post_api_client_secret = get_option('post_api_client_secret');
		}

		$post_api_franking_license = get_option('post_api_franking_license');

		if(!$post_api_client_id) {
			throw new Exception("Please make sure to set Post API Client Id");
		}
		if(!$post_api_client_secret) {
			throw new Exception("Please make sure to set Post API Client Secret");
		}
		if(!$post_api_franking_license) {
			throw new Exception("Please make sure to set Post API Franking License");
		}

		$api = new BarcodeApi($post_api_client_id, $post_api_client_secret, $post_api_franking_license);
		$api->auth();
		$response = $api->generateAddressLabel($orderId, $recipientAddress, $senderAddress, $return);
		// TODO: handle errors
		$label = $response['label'];
		$identCode = $response['identCode'];
		$img = base64_decode($label);
		$filename = "$orderId-$identCode-shipping-label.pdf";
		if($return) {
			$filename = "$orderId-$identCode-return-label.pdf";
		}

		$filepath = self::saveAddressLabel($img, $filename, $return);
		return [
			'trackingNumber' => $identCode,
			'labelPath' => $filepath
		];
	}

	/**
	 * @param $img
	 * @param $filename
	 * @untested
	 * @return string
	 */
	public static function saveAddressLabel($img, $filename, bool $return = false): string {
		$wp_upload_dir = wp_upload_dir(null,false);
		$wp_upload_base_dir = $wp_upload_dir['basedir'];

		$upload_full_path = "$wp_upload_base_dir/shipping-labels/$filename";
		if($return) {
			$upload_full_path = "$wp_upload_base_dir/return-labels/$filename";
		}

		file_put_contents($upload_full_path, $img);
		return $upload_full_path;
	}

	/**
	 * @param $orderId
	 * @param $filepath
	 * @untested
	 * @return false|string
	 */
	public static function attachFileToOrder($orderId, $filepath):string {
		include_once(ABSPATH . 'wp-admin/includes/image.php');

		$filename = basename($filepath);

		$wp_filetype = wp_check_filetype($filename, null);

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => $filename,
			'post_content' => '',
			'post_status' => 'inherit'
		);
		// Insert the attachment and attach it to the order.
		$attach_id = wp_insert_attachment($attachment, $filepath, $orderId);

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
		wp_update_attachment_metadata($attach_id, $attach_data);

		return wp_get_attachment_url($attach_id);
	}

	/**
	 * @param WC_Order $order
	 * @untested
	 * @return Address
	 */
	public static function getShippingAddressFromOrder(WC_Order $order): Address {
		$firstname = $order->get_shipping_first_name('view') ?: $order->get_billing_first_name('view');
		$lastname = $order->get_shipping_last_name('view') ?: $order->get_billing_last_name('view');
		$street = $order->get_shipping_address_1('view') ?: $order->get_billing_address_1('view');
		$city = $order->get_shipping_city('view') ?: $order->get_billing_city('view');
		$zip = $order->get_shipping_postcode('view') ?: $order->get_billing_postcode('view');
		$email = $order->get_billing_email();
		$country = $order->get_shipping_country() ?: $order->get_billing_country() ?: "CH";
		return new Address($firstname, $lastname, "", $street, $city, $zip, $country, $email);
	}

	/**
	 * @param $fullStreet
	 * @untested
	 * @return array|null[]
	 */
	private static function splitStreet($fullStreet): array {
		$result = [];
		$houseNo = null;
		$street = null;
		if ( preg_match('/([^\d]+)\s?(.+)/i', $fullStreet, $result) ) {
			// $result[1] will have the street name
			$street = trim($result[1]);
			// and $result[2] is the number part.
			$houseNo = trim($result[2]);
		} else {
			// TODO: handle errors
		}
		return [
			'street' => $street,
			'houseNo' => $houseNo
		];
	}

	public static function generateUniqueCode($order_id): string {
		$chars = "ABCDEFGHIJKLMNOPQRSTVUVWXYZ0123456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;
		while ($i <= 5) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		$pass = $pass . $order_id;
		return $pass;
	}

	public static function getCustomerIbanFromOrder(WC_Order $order): ?string {
		$customer_id = $order->get_user_id();
		if(empty($customer_id)) {
			$email = $order->get_billing_email();
			$customer = get_user_by('email', $email);
		} else {
			$customer = get_user_by('id',$customer_id);
		}
		if($customer) {
			$iban = get_user_meta($customer->ID, 'iban_num', true);
			if($iban) {
				return $iban;
			}
		}
		return false;

	}

}