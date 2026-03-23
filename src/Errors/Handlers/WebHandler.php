<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Errors\Handlers;

use SpencerMortensen\Site\Errors\Formatter;
use SpencerMortensen\Site\Theme\Page;
use SpencerMortensen\Site\Theme\Values;
use SpencerMortensen\Exceptions\Handler;
use Throwable;

class WebHandler implements Handler
{
	private $formatter;
	private $wwwKey;
	private $themeKey;
	private $siteUrl;

	public function __construct (Formatter $formatter, string $wwwKey, string $themeKey, string $siteUrl)
	{
		$this->formatter = $formatter;
		$this->wwwKey = $wwwKey;
		$this->themeKey = $themeKey;
		$this->siteUrl = $siteUrl;
	}

	public function handle (Throwable $throwable)
	{
		$values = new Values();
		$values->add('title', $this->formatter->getName($throwable));
		$values->add('context', $this->formatter->getHtmlContext($throwable));
		$values->add('summary', $this->formatter->getHtmlSummary($throwable));

		$page = new Page($this->wwwKey, $this->siteUrl, $values);

		$page->add($this->themeKey);
		$page->send('500 Internal Server Error');
	}
}
