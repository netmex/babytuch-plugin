<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use \Inc\Api\Address;
use \Inc\Api\BarcodeApi;

final class BarcodeApiTest extends TestCase {

	protected static string $client_id = "078ca809b7ff5bf8fa3df6c1dbe37842";
	protected static string $client_secret = "72cc289202df3617a9a6c77a428b38a5";
	protected static string $franking_license = "60138277";

	protected static BarCodeApi $api;

	protected function setUp() {
		update_option('post_api_test_client_id', '078ca809b7ff5bf8fa3df6c1dbe37842');
		update_option('post_api_test_client_secret', '72cc289202df3617a9a6c77a428b38a5');
		update_option('post_api_franking_license', '60138277');
		self::$api = new BarcodeApi(self::$client_id,self::$client_secret, self::$franking_license);
		parent::setUp();
	}

	public function testAuthTokenCanBeFetched(): void {
		$api = self::$api;
		$api->auth();
		$this->assertNotEmpty($api->getAuthToken() );
	}

	public function testAddressLabelCanBeFetched(): void {
		$api = self::$api;
		$api->auth();
		$senderAddress = new Address(
			"",
			"nevaland gmbh",
			"",
			"Spycherweg 3",
			"Spreitenbach",
			"8957"
		);
		$recipientAddress = new Address(
			"Hans",
			"Muster",
			"",
			"Musterstrasse 123",
			"Zurich",
			"8000",
			"CH",
			"mighty.miczed@gmail.com"
		);
		$response = $api->generateAddressLabel(607, $recipientAddress, $senderAddress);
		$label = $response['label'];
		$identCode = $response['identCode'];
		$this->assertNotEmpty($label);
		$this->assertNotEmpty($identCode);
		// todo: check if image is valid maybe?
	}

	public function testReturnLabelsCanBeFetched(): void {
		$api = self::$api;
		$api->auth();
		$warehouseAddress = new Address(
			"",
			"babytuch.ch Logistik",
			"Noveos Pack+",
			"Turicaphonstrasse 29",
			"Riedikon",
			"8616",
			"CH",
			"mighty.miczed@gmail.com"
		);
		$recipientAddress = new Address(
			"Hans",
			"Muster",
			"",
			"Musterstrasse 123",
			"Zurich",
			"8000",
			"CH",
			"mighty.miczed@gmail.com"
		);
		$response = $api->generateAddressLabel(607, $warehouseAddress, $recipientAddress, true);
		$label = $response['label'];
		$identCode = $response['identCode'];
		$this->assertNotEmpty($label);
		$this->assertNotEmpty($identCode);
	}


}