<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Errors\Handlers;

use SpencerMortensen\Site\Sender;
use SpencerMortensen\Site\Errors\Formatter;
use SpencerMortensen\Exceptions\Handler;
use Throwable;

class EmailHandler implements Handler
{
	private $formatter;
	private $settings;
	private $toEmail;

	public function __construct (Formatter $formatter, array $settings, string $toEmail)
	{
		$this->formatter = $formatter;
		$this->settings = $settings;
		$this->toEmail = $toEmail;
	}

	public function handle (Throwable $throwable)
	{
		$email = [
			'from' => $this->settings['username'],
			'to' => $this->toEmail,
			'subject' => $this->formatter->getEmailSubject($throwable),
			'text' => $this->formatter->getEmailBody($throwable),
		];

		$sender = new Sender($this->settings);
		$sender->send($email);
	}
}
