<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Errors\Handlers;

use SpencerMortensen\Site\Errors\Formatter;
use SpencerMortensen\Exceptions\Handler;
use Throwable;

class LogHandler implements Handler
{
	private $formatter;
	private $logPath;

	public function __construct (Formatter $formatter, string $logPath)
	{
		$this->formatter = $formatter;
		$this->logPath = $logPath;
	}

	public function handle (Throwable $throwable)
	{
		$line = $this->formatter->getLogLine($throwable);

		$this->write($line);
	}

	private function write (string $line)
	{
		file_put_contents($this->logPath, "{$line}\n", FILE_APPEND | LOCK_EX);
	}
}
