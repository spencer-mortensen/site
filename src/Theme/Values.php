<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Theme;

use Exception;

class Values
{
	private $values;
	private $seeds;

	public function __construct ()
	{
		$this->values = [];
		$this->seeds = [];
	}

	public function add (string $key, string $value): void
	{
		$variables = $this->parse($value);

		if (isset($this->seeds[$key])) {
			$this->merge($key, $value, $variables);
		} else {
			$this->seeds[$key] = [$value, $variables];
		}

		while (!$this->expandAll());
	}

	public function get (string $key): string
	{
		if (isset($this->values[$key])) {
			return $this->values[$key];
		} else {
			$undefinedKey = $this->getUndefinedKey($key);
			throw new Exception("Undefined key: {$undefinedKey}");
		}
	}

	public function has (string $key): bool
	{
		return isset($this->values[$key]) || isset($this->seeds[$key]);
	}

	private function getUndefinedKey (string $key): string
	{
		if (!isset($this->seeds[$key])) {
			return $key;
		}

		$variables = $this->seeds[$key][1];

		foreach ($variables as $i => $childKey) {
			return $this->getUndefinedKey($childKey);
		}
	}

	private function parse (string $haystack): array
	{
		$variables = [];
		$re = '~\\{\\$([^\\{\\$\\}\\s]+)\\}~DusX';
		$i = 0;

		while (preg_match($re, $haystack, $match, PREG_OFFSET_CAPTURE, $i) === 1) {
			$i = $match[0][1];
			$key = $match[1][0];
			$variables[$i] = $key;
			$i += 3 + strlen($key);
		}

		return $variables;
	}

	private function merge (string $key, string $value1, array $variables1): void
	{
		$newValues = [];
		$newVariables = [];
		$delta = 0;
		$i1 = 0;

		foreach ($variables1 as $j1 => $key1) {
			if ($key1 === $key) {
				$value0 = $this->seeds[$key][0];
				$variables0 = $this->seeds[$key][1];

				foreach ($variables0 as $i0 => $key0) {
					$newVariables[$j1 + $delta + $i0] = $key0;
				}

				$newValues[] = substr($value1, $i1, $j1 - $i1);
				$newValues[] = $value0;

				$keyLength = 3 + strlen($key1);
				$delta += strlen($value0) - $keyLength;
				$i1 = $j1 + $keyLength;
			} else {
				$newVariables[$j1 + $delta] = $key1;
			}
		}

		$newValues[] = substr($value1, $i1);

		$this->seeds[$key][0] = implode('', $newValues);
		$this->seeds[$key][1] = $newVariables;
	}

	private function expandAll (): bool
	{
		foreach ($this->seeds as $key => &$seed) {
			$value = &$seed[0];
			$variables = &$seed[1];

			$this->expand($value, $variables);

			if (count($variables) === 0) {
				unset($this->seeds[$key]);
				$this->values[$key] = $value;
				return false;
			}
		}

		return true;
	}

	private function expand (string &$value, array &$variables): void
	{
		$newValues = [];
		$newVariables = [];
		$delta = 0;
		$i = 0;

		foreach ($variables as $j => $key) {
			if (isset($this->values[$key])) {
				$newValues[] = substr($value, $i, $j - $i);
				$newValues[] = $this->values[$key];

				$keyLength = 3 + strlen($key);
				$delta += strlen($this->values[$key]) - $keyLength;
				$i = $j + $keyLength;
			} else {
				$newVariables[$j + $delta] = $key;
			}
		}

		$newValues[] = substr($value, $i);

		$value = implode('', $newValues);
		$variables = $newVariables;
	}
}
