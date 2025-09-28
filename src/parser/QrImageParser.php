<?php

declare(strict_types=1);

namespace XanderID\QrisDynamicify\parser;

use chillerlan\QRCode\QRCode;
use XanderID\QrisDynamicify\QrisException;
use function file_get_contents;
use function pathinfo;
use function strtolower;
use const PATHINFO_EXTENSION;

class QrImageParser {
	/**
	 * Reads a QRIS string from a file.
	 * If the file is a .txt, it reads the text directly.
	 * If the file is an image, it decodes the QR code from the image.
	 *
	 * @throws QrisException
	 */
	public static function readFileAsQris(string $filePath) : string {
		if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'txt') {
			$content = file_get_contents($filePath);
			if ($content === false) {
				throw new QrisException('Failed to read the file.');
			}

			return $content;
		}

		$qrcode = (new QRCode())->readFromFile($filePath);
		$text = $qrcode->data;

		if (!$text) {
			throw new QrisException('QR code not found in the image.');
		}

		return $text;
	}
}
