<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Theme;

class Directory
{
	public static function list (string $path): array
	{
		$childNames = [];
		$directory = opendir($path);

		for ($childName = readdir($directory); $childName !== false; $childName = readdir($directory)) {
			if (($childName === '.') || ($childName === '..')) {
				continue;
			}

			$childNames[$childName] = $childName;
		}

		closedir($directory);
		return $childNames;
	}
}
