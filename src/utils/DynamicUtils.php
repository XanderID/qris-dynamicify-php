<?php

declare(strict_types=1);

namespace XanderID\QrisDynamicify\utils;

use XanderID\QrisDynamicify\QrisException;
use function count;
use function dechex;
use function explode;
use function is_int;
use function ord;
use function preg_match;
use function rtrim;
use function str_ends_with;
use function str_pad;
use function str_replace;
use function strlen;
use function strtoupper;
use function substr;
use const STR_PAD_LEFT;

class DynamicUtils {
	/**
	 * Computes CRC16 (CCITT-FALSE) checksum for a QRIS payload.
	 *
	 * @param string $str QRIS payload string without CRC
	 *
	 * @return string Uppercase hexadecimal checksum (4 characters)
	 */
	public static function computeCRC16(string $str) : string {
		$crc = 0xFFFF;

		for ($c = 0; $c < strlen($str); ++$c) {
			$crc ^= ord($str[$c]) << 8;
			for ($i = 0; $i < 8; ++$i) {
				if ($crc & 0x8000) {
					$crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
				} else {
					$crc = ($crc << 1) & 0xFFFF;
				}
			}
		}

		$hex = strtoupper(dechex($crc));
		return str_pad($hex, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Inserts or updates the transaction amount (tag 54) in a QRIS payload.
	 *
	 * @param string $staticQris Original QRIS string (must be valid)
	 * @param int    $price      Transaction amount (>=0)
	 *
	 * @return string New QRIS string with price and valid CRC16
	 *
	 * @throws QrisException
	 */
	public static function setPrice(string $staticQris, int $price) : string {
		if ($price < 0) {
			throw new QrisException('Price must be >= 0');
		}

		// Remove old CRC (last 4 characters)
		$qris = substr($staticQris, 0, -4);

		// Convert to dynamic QR (replace tag 01 from "11" to "12")
		$qris = str_replace('010211', '010212', $qris);

		// Split before country code (tag 58)
		$parts = explode('5802ID', $qris);
		if (count($parts) !== 2) {
			throw new QrisException('Invalid QRIS format (missing 5802ID)');
		}

		// Build tag 54 with the given price
		$priceStr = (string) $price;
		$priceTag = '54' . str_pad((string) strlen($priceStr), 2, '0', STR_PAD_LEFT) . $priceStr;

		// Rebuild QRIS string
		$result = $parts[0] . $priceTag . '5802ID' . $parts[1];

		// Append updated CRC16
		return $result . self::computeCRC16($result);
	}

	/**
	 * Inserts or updates a service fee (tag 55) in a QRIS payload.
	 *
	 * @param string     $staticQris QRIS string (must already include tag 54)
	 * @param int|string $tax        Either number (nominal) or string percentage (e.g., "10%")
	 *
	 * @return string New QRIS string with service fee and valid CRC16
	 *
	 * @throws QrisException
	 */
	public static function setTax(string $staticQris, int|string $tax) : string {
		$tag = '';

		if (is_int($tax)) {
			// Nominal service fee
			$taxStr = (string) $tax;
			$tag = '55020256' . str_pad((string) strlen($taxStr), 2, '0', STR_PAD_LEFT) . $taxStr;
		} elseif (str_ends_with((string) $tax, '%')) {
			// Percentage service fee
			$percent = rtrim((string) $tax, '%');
			$tag = '55020357' . str_pad((string) strlen($percent), 2, '0', STR_PAD_LEFT) . $percent;
		} else {
			throw new QrisException("Invalid tax format, must be a number or a percentage string (e.g., '10%').");
		}

		// Remove old CRC (last 4 characters)
		$qris = substr($staticQris, 0, -4);
		$qris = str_replace('010211', '010212', $qris);

		// Split before country code (tag 58)
		$parts = explode('5802ID', $qris);
		if (count($parts) !== 2) {
			throw new QrisException('Invalid QRIS format (missing 5802ID tag).');
		}

		// Find transaction amount (tag 54)
		if (!preg_match('/54\d{2}\d+/', $parts[0], $matches)) {
			throw new QrisException('QRIS does not contain a transaction amount (tag 54). Please call setPrice() first.');
		}

		// Insert service fee right after the amount
		$withTax = str_replace($matches[0], $matches[0] . $tag, $parts[0]);

		$result = $withTax . '5802ID' . $parts[1];

		return $result . self::computeCRC16($result);
	}
}
