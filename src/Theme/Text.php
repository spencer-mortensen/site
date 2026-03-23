<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Theme;

class Text
{
	public static function startsWith (string $haystack, string $needle): bool
	{
		return strncmp($haystack, $needle, strlen($needle)) === 0;
	}

	public static function endsWith (string $haystack, string $needle): bool
	{
		$length = strlen($needle);

		return ($length < strlen($haystack))
			&& (substr_compare($haystack, $needle, -$length) === 0);
	}
}
