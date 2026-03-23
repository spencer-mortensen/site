<?php declare(strict_types = 1);

namespace SpencerMortensen\Site;

use SpencerMortensen\Site\Errors\Handlers\MainHandler;
use SpencerMortensen\Exceptions\ErrorHandling;

class Site
{
	public function __construct (array $settings)
	{
		$errorHandler = new MainHandler($settings);
		new ErrorHandling($errorHandler, E_ALL);

		date_default_timezone_set($settings['timeZone']);
	}
}
