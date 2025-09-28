<?php

declare(strict_types=1);

namespace XanderID\QrisDynamicify\metadata;

use XanderID\QrisDynamicify\parser\EmvParser;
use function strtoupper;

class QrisMetadata {
	/** Merchant name. */
	private string $merchant;

	/** Company or organization name. */
	private string $company;

	/** Region or province. */
	private string $region;

	/** Country code (ISO). */
	private string $country;

	/** Postal/ZIP code. */
	private string $postal_code;

	/** Merchant PAN (Primary Account Number). */
	private string $merchant_pan;

	/** Transaction price (if available). */
	private ?string $price;

	/** Tax amount (if available). */
	private ?string $tax;

	/**
	 * QrisMetadata constructor.
	 *
	 * @param string      $merchant     Merchant name
	 * @param string      $company      Company/organization name
	 * @param string      $region       Region or province
	 * @param string      $country      Country code (ISO)
	 * @param string      $postal_code  Postal/ZIP code
	 * @param string      $merchant_pan Merchant PAN
	 * @param string|null $price        Transaction price
	 * @param string|null $tax          Tax amount
	 */
	public function __construct(
		string $merchant,
		string $company,
		string $region,
		string $country,
		string $postal_code,
		string $merchant_pan,
		?string $price,
		?string $tax
	) {
		$this->merchant = $merchant;
		$this->company = $company;
		$this->region = $region;
		$this->country = $country;
		$this->postal_code = $postal_code;
		$this->merchant_pan = $merchant_pan;
		$this->price = $price;
		$this->tax = $tax;
	}

	/**
	 * Get merchant name.
	 */
	public function getMerchant() : string {
		return $this->merchant;
	}

	/**
	 * Get company name.
	 */
	public function getCompany() : string {
		return $this->company;
	}

	/**
	 * Get region/province.
	 */
	public function getRegion() : string {
		return $this->region;
	}

	/**
	 * Get country code (ISO).
	 */
	public function getCountry() : string {
		return $this->country;
	}

	/**
	 * Get postal/ZIP code.
	 */
	public function getPostalCode() : string {
		return $this->postal_code;
	}

	/**
	 * Get merchant PAN.
	 */
	public function getMerchantPan() : string {
		return $this->merchant_pan;
	}

	/**
	 * Get transaction price.
	 */
	public function getPrice() : ?string {
		return $this->price;
	}

	/**
	 * Get tax amount.
	 */
	public function getTax() : ?string {
		return $this->tax;
	}

	/**
	 * Return all metadata as an associative array.
	 *
	 * @return array<string, string|null>
	 */
	public function toArray() : array {
		return [
			'merchant' => $this->merchant,
			'company' => $this->company,
			'region' => $this->region,
			'country' => $this->country,
			'postal_code' => $this->postal_code,
			'merchant_pan' => $this->merchant_pan,
			'price' => $this->price,
			'tax' => $this->tax,
		];
	}

	/**
	 * Extracts merchant, company, and transaction metadata from a QRIS string.
	 *
	 * @param string $qris The QRIS string to parse
	 *
	 * @return QrisMetadata Returns an instance containing parsed metadata
	 */
	public static function extractMetadata(string $qris) : self {
		$emv = EmvParser::parseEmv($qris);

		$emv += [
			'26' => '',
			'27' => '',
			'51' => '',
			'54' => null,
			'55' => null,
			'58' => 'ID',
			'59' => '',
			'60' => '',
			'61' => '',
		];

		$merchant = $emv['59'];
		$region = $emv['60'];
		$country = $emv['58'];
		$postal_code = $emv['61'];

		$company = '';
		$merchant_pan = '';

		foreach (['26', '27', '51'] as $tag) {
			if ($emv[$tag] !== '') {
				$nested = EmvParser::parseEmv($emv[$tag]);
				$gui = $nested['00'] ?? '';
				$acc = $nested['01'] ?? '';

				if ($gui && strtoupper($gui) !== 'ID.CO.QRIS.WWW') {
					$company = $gui;
					if ($acc) {
						$merchant_pan = $acc;
					}

					break;
				}
			}
		}

		$price = $emv['54'];
		$tax = $emv['55'];

		return new self(
			$merchant,
			$company,
			$region,
			$country,
			$postal_code,
			$merchant_pan,
			$price,
			$tax
		);
	}
}
