<?php


namespace Inc\Api;

/**
 * Class Address
 * Holds the addresses that are used within the babytuch plugin
 * @package Inc\Api
 */
final class Address {

	private ?string $email;
	private string $firstname;
	private string $name;
	private ?string $name2;
	private string $street;
	private string $city;
	private string $zip;
	private string $country;

	function __construct($firstname, $name, $name2, $street, $city, $zip, $country = "CH", $email=null) {
		$this->firstname = $firstname;
		$this->name = $name;
		$this->name2 = $name2;
		$this->street = $street;
		$this->city = $city;
		$this->zip = $zip;
		$this->country = $country;
		$this->email = $email;
	}

	/**
	 * @return mixed|string|null
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return string
	 */
	public function getFirstname(): string {
		return $this->firstname;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getName2(): ?string {
		return $this->name2;
	}

	/**
	 * @return string
	 */
	public function getFullName(): string {
		if($this->firstname == "") {
			return $this->name;
		} else {
			return "$this->firstname $this->name";
		}
	}

	/**
	 * @return string
	 */
	public function getStreet(): string {
		return $this->street;
	}

	/**
	 * @return string
	 */
	public function getCity(): string {
		return $this->city;
	}

	/**
	 * @return string
	 */
	public function getZip(): string {
		return $this->zip;
	}

	/**
	 * @return mixed|string
	 */
	public function getCountry() {
		return $this->country;
	}


	/**
	 * Returns the zip code and the city in one line
	 * (and the country if specified)
	 *
	 * @param false $includeCountry
	 *
	 * @return string
	 */
	public function getZipAndCity( bool $includeCountry = false): string {
		$line = $this->zip." ".$this->city;
		if($includeCountry) {
			$line = $line." ".$this->country;
		}
		return $line;
	}

}