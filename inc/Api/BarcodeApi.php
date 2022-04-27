<?php


namespace Inc\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class BarcodeApi
 * @package Inc\Api
 * Handles the communication with the Post API
 */
class BarcodeApi {

	private static string $barcodeApiBaseUrl = 'https://wedec.post.ch';

	private Client $client;
	private $authToken;
	private string $client_id;
	private string $client_secret;
	private string $franking_license;


	public function __construct(string $client_id, string $client_secret, string $franking_license) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->franking_license = $franking_license;
		$this->client = new Client(['base_uri' => self::$barcodeApiBaseUrl]);
	}

	public function auth() {
		$response = $this->client->post('/WEDECOAuth/token', ['form_params' => [
			'grant_type' => 'client_credentials',
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'scope' => 'WEDEC_BARCODE_READ'
		]]);

		if($response->getStatusCode() == 200) {
			$body = json_decode($response->getBody());
			$this->authToken = $body->access_token;
		}
	}


	/**
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function generateAddressLabel($orderId, Address $recipientAddress, Address $senderAddress, bool $return = false): array {

		$recipient = $this->getRecipientFromAddress($recipientAddress);
		$customer = $this->getCustomerFromAddress($senderAddress);
		$labelDefinition = $this->getLabelDefinition();

		$body = [
			'language' => 'DE', // de, fr, it, en
			'frankingLicense' => $this->franking_license, // Customer franking licence number or postcode
			'customer' => $customer, // Refers to the SENDER
			'labelDefinition' => $labelDefinition,
			'item' => [
				'itemID' => "$orderId", // ID assigned by customers at address label level will be returned unchanged in the response
				// 'itemNumber' => "", // Mailing number Optional, any value. If filled in, validation for uniqueness. If ItemNumber is empty, the item number is generated and the identcode is generated from this item number and the franking licence.
				'recipient' => $recipient,
				'attributes' => [
					'przl' => ["ECO"], // Service code (DLC): PostPac Economy
					'proClima' => false
				],
				// solve using trackingnumber and URL for now
				'notification' => [
					[
						"communication" => [
							"email" => $recipientAddress->getEmail()
						],
						"service" => 2, // delivery information
						"language" => "DE", // apparently there is a "DynPic" field that can be used for logo
						"type" => "EMAIL" // DispatchDate can be used to transmit the date, when the logistics provider dispatches the packet
					]
				]
			]
		];

		if($return) {
			// mark the label with "GeschäftsAntwortSendung" so customer doesn't have to pay for it when returing it
			$body['item']['attributes']['przl'][] = "GAS";
		}

		$headers = ['Authorization' => "Bearer $this->authToken"];


		//$json = json_encode($body);
		$response = $this->client->post( '/api/barcode/v1/generateAddressLabel', [ 'json' => $body, 'headers' => $headers ] );
		if($response->getStatusCode() == 200) {
			$body = json_decode($response->getBody());
			$item = $body->item;
			if(isset($item->errors)) {
				return [
					'errors' => $item->errors
				];
			} else {
				$label = $item->label[0];
				$identCode = $item->identCode;
				return [
					'identCode' => $identCode,
					'label' => $label
				];
			}
		} else {
			throw new Exception("There was an error while generating an address label: ".json_decode($response->getBody()));
		}
	}



	/**
	 * @return mixed
	 */
	public function getAuthToken() {
		return $this->authToken;
	}

	private function getRecipientFromAddress(Address $recipientAddress): array {
		$recipient = [
			'personallyAddressed' => true,
			'name1' => $recipientAddress->getFullName(), // max 35
			'street' => $recipientAddress->getStreet(), // max 35
			'zip' => $recipientAddress->getZip(),
			'city' => $recipientAddress->getCity(),
			'country' => $recipientAddress->getCountry()
		];
		if($recipientAddress->getName2()) {
			$recipient['name2'] = $recipientAddress->getName2();
		}
		if($recipientAddress->getEmail()) {
			$recipient['email'] = $recipientAddress->getEmail();
		}
		return $recipient;
	}

	private function getCustomerFromAddress(Address $senderAddress): array {
		$customer = [
			"name1" => $senderAddress->getFullName(), // max 25
			"street" => $senderAddress->getStreet(), // max 25
			"zip" => $senderAddress->getZip(), // max 6
			"city" => $senderAddress->getCity(), // max 25
			"country" => $senderAddress->getCountry() // max 2
		];
		if($senderAddress->getName2()) {
			$customer['name2'] = $senderAddress->getName2();
		}
		return $customer;
	}

	private function getLabelDefinition():array {
		return [
			'labelLayout' => "A6", // A5
			'printAddresses' => "RECIPIENT_AND_CUSTOMER", // Both the sender’s and the recipient’s addresses are printed) OnlyRecipient would only print recipients address
			'imageFileType' => "PDF", // using EPS is the fastest (~50ms figure generation + 500 - 1000 ms data transmission)
			'imageResolution' => 300,
			'printPreview' => false // PrintPreview enabled/disabled (SPECIMEN lettering from the label generated)
		];
	}


}

/*


$label = generateAddressLabel(...)
$code = $label['identCode'];
$label = $label['label'];

$img = base64_decode($label);

$wp_upload_dir = wp_upload_dir(null,false);
$wp_upload_base_dir = $wp_upload_dir['basedir'];
$wp_upload_base_url = $wp_upload_dir['baseurl'];

$uniq_part = (int) microtime(true); // prevents enumerating shipping labels solely by increasing the order-id

$filename = "$orderId-$uniq_part-shipping-label.eps";

$upload_full_path = "$wp_upload_base_dir/shipping-labels/$filename";

file_put_contents($upload_full_path, $img);


 */