<?php

declare(strict_types=1);

namespace XanderID\QrisDynamicify\parser;

use function strlen;
use function substr;

class EmvParser {
	/**
	 * Generic EMV TLV (Tag-Length-Value) parser
	 * Parses QRIS string into key-value pairs where key is the tag.
	 *
	 * @param string $qris The QRIS string
	 *
	 * @return array<string, string> Parsed TLV key-value pairs
	 */
	public static function parseEmv(string $qris) : array {
		$i = 0;
		$result = [];

		while ($i < strlen($qris)) {
			$tag = substr($qris, $i, 2);
			$len = (int) substr($qris, $i + 2, 2); // length as integer
			$value = substr($qris, $i + 4, $len);
			$result[$tag] = $value;
			$i += 4 + $len;
		}

		return $result;
	}
}
