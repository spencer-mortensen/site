<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Theme;

use Exception;

class Page
{
	private static $elements = [
		'css' => '<link href="{$url}" rel="stylesheet" type="text/css">',
		'js' => '<script src="{$url}" defer></script>'
	];

	private $siteKey;
	private $siteUrl;
	private $values;
	private $dependencies;

	public function __construct (string $siteKey, string $siteUrl, Values $values)
	{
		$this->siteKey = $siteKey;
		$this->siteUrl = $siteUrl;
		$this->values = $values;
		$this->dependencies = [];
	}

	public function add (string $packageKey): void
	{
		$childNames = Directory::list("{$this->siteKey}{$packageKey}");

		foreach ($childNames as $childName) {
			$childKey = "{$packageKey}{$childName}";
			$childPath = "{$this->siteKey}{$childKey}";

			if (is_dir($childPath)) {
				if (($childName ===  'css') || ($childName === 'js')) {
					$this->addDependencies($childName, "{$childKey}/");
				}
			} else {
				if ($childName === '.include') {
					$this->include($childPath);
				} elseif (Text::startsWith($childName, '.')) {
					$this->addData(substr($childName, 1), $childPath);
				} elseif (Text::endsWith($childName, '.css')) {
					$this->addDependency('css', $childKey);
				} elseif (Text::endsWith($childName, '.js')) {
					$this->addDependency('js', $childKey);
				}
			}
		}
	}

	private function addDependencies (string $type, string $directoryKey): void
	{
		$childNames = Directory::list("{$this->siteKey}{$directoryKey}");

		foreach ($childNames as $childName) {
			$childKey = "{$directoryKey}{$childName}";

			if (is_dir("{$this->siteKey}/{$childKey}")) {
				$this->addDependencies($type, "{$childKey}/");
			} elseif (Text::endsWith($childName, ".{$type}")) {
				$this->addDependency($type, $childKey);
			}
		}
	}

	private function addDependency (string $type, string $key): void
	{
		$this->dependencies[$type][$key] = $key;
	}

	private function addData (string $name, string $path): void
	{
		$value = file_get_contents($path);

		$this->values->add($name, $value);
	}

	private function include (string $path): void
	{
		$contents = file_get_contents($path);
		$includeKeys = explode("\n", trim($contents));

		foreach ($includeKeys as $includeKey) {
			$this->add($includeKey);
		}
	}

	public function send (string $status): void
	{
		$content = $this->getContent();
		$length = strlen($content);

		header("HTTP/1.1 {$status}");
		header("Content-Length: {$length}");

		echo $content;
	}

	private function getContent (): string
	{
		foreach ($this->dependencies as $type => $keys) {
			$sectionHtml = $this->getSectionHtml($type, $keys);
			$this->values->add($type, $sectionHtml);
		}

		return $this->values->get('html');
	}

	private function getSectionHtml (string $type, array $keys): string
	{
		if (count($keys) === 0) {
			return '';
		}

		$elementHtmls = [];
		$patternHtml = self::$elements[$type];

		foreach ($keys as $key) {
			$elementHtmls[] = $this->getElementHtml($key, $patternHtml);
		}

		return "\n" . implode("\n", $elementHtmls);
	}

	private function getElementHtml (string $key, string $patternHtml): string
	{
		$urlHtml = self::HtmlEncode("{$this->siteUrl}{$key}");

		return str_replace('{$url}', $urlHtml, $patternHtml);
	}

	private static function HtmlEncode (string $text): string
	{
		return htmlspecialchars($text, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
	}
}
