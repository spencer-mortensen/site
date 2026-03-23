<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Errors\Handlers;

use SpencerMortensen\Site\Errors\Formatter;
use SpencerMortensen\Exceptions\Handler;
use Throwable;

class MainHandler implements Handler
{
	private $settings;

	public function __construct (array $settings)
	{
		$this->settings = $settings;
	}

	public function handle (Throwable $throwable)
	{
		$formatter = new Formatter(
			$this->settings['project'],
			$this->settings['keys']['code'],
			$this->settings['timeZone']
		);

		$logPath = $this->settings['errors']['log'] ?? null;

		if ($logPath !== null) {
			$handler = new LogHandler($formatter, $logPath);
			$handler->handle($throwable);
		}

		$email = $this->settings['errors']['email'] ?? null;

		if ($email !== null) {
			$handler = new EmailHandler($formatter, $this->settings['mail'], $email);
			$handler->handle($throwable);
		}

		$handler = new WebHandler($formatter, $this->settings['keys']['www'], $this->settings['errors']['key'], $this->settings['url']['site']);
		$handler->handle($throwable);
	}
}
