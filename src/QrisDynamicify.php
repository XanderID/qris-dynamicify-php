<?php

declare(strict_types=1);

namespace XanderID\QrisDynamicify;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use XanderID\QrisDynamicify\metadata\QrisMetadata;
use XanderID\QrisDynamicify\parser\QrImageParser;
use XanderID\QrisDynamicify\utils\DynamicUtils;
use function file_put_contents;
use function in_array;
use function is_numeric;
use function pathinfo;
use function round;
use function str_ends_with;
use function strtolower;
use const PATHINFO_EXTENSION;

class QrisDynamicify {
	/** The current QRIS string. */
	private string $qris;

	/** Transaction price. */
	private float $price = 0.0;

	/** Tax amount (either nominal or calculated from percentage). */
	private float $taxAmount = 0.0;

	/**
	 * QrisDynamicify constructor.
	 *
	 * @param string $initialQris The initial static QRIS string
	 *
	 * @throws QrisException If $initialQris is empty
	 */
	public function __construct(string $initialQris) {
		if (empty($initialQris)) {
			throw new QrisException('Initial QRIS string cannot be empty.');
		}

		$this->qris = $initialQris;
	}

	/**
	 * Sets the transaction price.
	 *
	 * @param int $price The price amount (must be >= 0)
	 *
	 * @return self Returns the current instance for method chaining
	 *
	 * @throws QrisException If $price is negative
	 */
	public function setPrice(int $price) : self {
		if ($price < 0) {
			throw new QrisException('Price must be greater than or equal to 0.');
		}

		$this->price = $price;
		$this->qris = DynamicUtils::setPrice($this->qris, $price);

		return $this;
	}

	/**
	 * Sets the tax for the transaction.
	 *
	 * Can accept either a nominal amount (int) or a percentage string (e.g., "10%").
	 *
	 * @param int|string $tax Nominal tax amount or percentage string
	 *
	 * @return self Returns the current instance for method chaining
	 *
	 * @throws QrisException If the tax is negative or the format is invalid
	 */
	public function setTax(int|string $tax) : self {
		if (is_numeric($tax)) {
			if ($tax < 0) {
				throw new QrisException('Tax must be greater than or equal to 0.');
			}

			$this->taxAmount = (float) $tax;
		} elseif (str_ends_with($tax, '%')) {
			$percent = (float) $tax;
			if ($percent < 0) {
				throw new QrisException('Tax percentage must be greater than or equal to 0.');
			}

			$this->taxAmount = round(($percent / 100) * $this->price, 2);
		} else {
			throw new QrisException('Invalid tax format. Must be numeric or percentage string.');
		}

		$this->qris = DynamicUtils::setTax($this->qris, $tax);

		return $this;
	}

	/**
	 * Retrieves metadata from the current QRIS string.
	 *
	 * Parses the QRIS payload and returns a `QrisMetadata` instance containing:
	 * - Merchant name
	 * - Company or organization name
	 * - Region/province
	 * - Country code (ISO)
	 * - Postal/ZIP code
	 * - Merchant PAN (Primary Account Number)
	 * - Transaction price (if available)
	 * - Tax amount (if available)
	 *
	 * @return QrisMetadata An object representing the parsed QRIS metadata
	 *
	 * @throws QrisException If QRIS parsing fails
	 */
	public function getMetadata() : QrisMetadata {
		return QrisMetadata::extractMetadata($this->qris);
	}

	/**
	 * Returns the default QR code generation options.
	 *
	 * The output type is automatically set based on the provided file extension:
	 * - 'png'  → QROutputInterface::GDIMAGE_PNG
	 * - 'jpg' or 'jpeg' → QROutputInterface::GDIMAGE_JPG
	 *
	 * Other default settings:
	 * - version: 5
	 * - error correction level: ECC_L
	 * - scale: 5
	 * - imageBase64: false
	 * - addQuietzone: true
	 *
	 * @param string $ext Optional file extension to determine output type (default: 'png')
	 *
	 * @return QROptions Default QR code generation settings
	 */
	protected function getDefaultQROptions(string $ext = 'png') : QROptions {
		$qrOptions = new QROptions();

		$qrOptions->outputType = match (strtolower($ext)) {
			'jpg', 'jpeg' => QROutputInterface::GDIMAGE_JPG,
			'png' => QROutputInterface::GDIMAGE_PNG,
			default => QROutputInterface::GDIMAGE_PNG,
		};

		return $qrOptions;
	}

	/**
	 * Writes the current QRIS string to a file.
	 *
	 * - `.txt` saves as plain text
	 * - `.png`, `.jpg`, `.jpeg` saves as QR code image
	 *
	 * @param string         $filePath  Full path of the file to save
	 * @param QROptions|null $qrOptions Optional QR code generation options (e.g., size, error correction)
	 *
	 * @return string Returns the saved file path
	 *
	 * @throws QrisException If file extension is unsupported
	 */
	public function writeToFile(string $filePath, ?QROptions $qrOptions = null) : string {
		$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

		if ($ext === 'txt') {
			file_put_contents($filePath, $this->qris);
		} elseif (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
			(new QRCode($qrOptions ?? $this->getDefaultQROptions($ext)))->render($this->qris, $filePath);
		} else {
			throw new QrisException("Unsupported file extension: {$ext}");
		}

		return $filePath;
	}

	/**
	 * Returns the current QRIS string.
	 *
	 * @return string Dynamic QRIS string
	 */
	public function __toString() : string {
		return $this->qris;
	}

	/**
	 * Creates a dynamic QRIS instance from a static QRIS string.
	 *
	 * @param string $staticQris Static QRIS string
	 *
	 * @return self A new QrisDynamicify instance
	 *
	 * @throws QrisException If $staticQris is empty
	 */
	public static function fromString(string $staticQris) : self {
		if (empty($staticQris)) {
			throw new QrisException('Static QRIS string cannot be empty.');
		}

		return new self($staticQris);
	}

	/**
	 * Creates a dynamic QRIS instance from a file containing a static QRIS.
	 *
	 * @param string $filePath Path to the file containing the static QRIS
	 *
	 * @return self A new QrisDynamicify instance
	 *
	 * @throws QrisException If the file is invalid or cannot be read
	 */
	public static function fromFile(string $filePath) : self {
		$staticQris = QrImageParser::readFileAsQris($filePath);
		return self::fromString($staticQris);
	}
}
