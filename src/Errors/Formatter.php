<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Errors;

use DateTimeImmutable;
use DateTimeZone;
use ErrorException;
use Throwable;

class Formatter
{
	private $name;
	private $codeKey;
	private $zone;

	public function __construct (string $name, string $codeKey, string $zone)
	{
		$this->name = $name;
		$this->codeKey = $codeKey;
		$this->zone = new DateTimeZone($zone);
	}

	public function getTerminalMessage (Throwable $throwable): string
	{
		$name = $this->getName($throwable);
		$this->getContext($throwable, $path, $line);
		$pathQuoted = var_export($path, true);
		$details = $this->getDetails($throwable);

		return "{$name} on line {$line} in {$pathQuoted}:\n * {$details}\n";
	}

	public function getLogLine (Throwable $throwable): string
	{
		$time = $this->getTime();
		$this->getContext($throwable, $path, $line);
		$pathQuoted = var_export($path, true);
		$name = $this->getName($throwable);
		$message = $this->getLogLineMessage($throwable);

		if ($throwable instanceof DataException) {
			$data = $throwable->getData();
			$message .= ' ' . json_encode($data);
		}

		return "{$time} {$pathQuoted}[{$line}]: {$name}: {$message}";
	}

	public function getEmailSubject (Throwable $throwable): string
	{
		$projectName = $this->name;
		$this->getContext($throwable, $path, $line);

		return "[{$projectName}] {$path}:{$line}";
	}

	public function getEmailBody (Throwable $throwable): string
	{
		return $this->getDetails($throwable);
	}

	public function getHtmlContext (Throwable $throwable): string
	{
		$this->getContext($throwable, $path, $line);

		return "{$path}[{$line}]";
	}

	public function getHtmlSummary (Throwable $throwable): string
	{
		$message = $throwable->getMessage();

		$summary = $message;

		if ($throwable instanceof DataException) {
			$data = $throwable->getData();
			$summary .= json_encode($data);
		}

		return $summary;
	}

	private function getDetails (Throwable $throwable): string
	{
		$message = $throwable->getMessage();

		$details = $message;

		if ($throwable instanceof DataException) {
			$data = $throwable->getData();

			// TODO: generate string representation of any data structure:
			$details .= "\n\n" . var_export($data, true);
		}

		return $details;
	}

	private function getTime (): string
	{
		$time = new DateTimeImmutable('now', $this->zone);

		return $time->format('m-d h:ia');
	}

	private function getContext (Throwable $throwable, &$path, &$line)
	{
		$path = $throwable->getFile();
		$line = (string)$throwable->getLine();

		if (preg_match("\x03^(?<path>.*?)\((?<line>[0-9]+)\) : eval\(\)'d code$\x03", $path, $matches) === 1) {
			$path = $matches['path'];
			$line = $matches['line'] . '(' . $line . ')';
		}

		$length = strlen($this->codeKey);

		if (strncmp($this->codeKey, $path, $length) === 0) {
			$path = substr($path, $length);
		}
	}

	public function getName (Throwable $throwable): string
	{
		if ($throwable instanceof ErrorException) {
			$severity = $throwable->getSeverity();
			return $this->getSeverityName($severity);
		} else {
			$class = get_class($throwable);
			$code = $throwable->getCode();

			$name = $class;

			if ($code !== 0) {
				$name .= " {$code}";
			}

			return $name;
		}
	}

	private function getSeverityName (int $code): string
	{
		switch ($code) {
			case E_ERROR: return 'E_ERROR';
			case E_WARNING: return 'E_WARNING';
			case E_PARSE: return 'E_PARSE';
			case E_NOTICE: return 'E_NOTICE';
			case E_CORE_ERROR: return 'E_CORE_ERROR';
			case E_CORE_WARNING: return 'E_CORE_WARNING';
			case E_COMPILE_ERROR: return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING: return 'E_COMPILE_WARNING';
			case E_USER_ERROR: return 'E_USER_ERROR';
			case E_USER_WARNING: return 'E_USER_WARNING';
			case E_USER_NOTICE: return 'E_USER_NOTICE';
			case E_STRICT: return 'E_STRICT';
			case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: return 'E_DEPRECATED';
			case E_USER_DEPRECATED: return 'E_USER_DEPRECATED';
			case E_ALL: return 'E_ALL';
		}

		return (string)$code;
	}

	private function getLogLineMessage (Throwable $throwable): string
	{
		$message = $throwable->getMessage();
		$message = preg_replace("\x03\\s+\x03", ' ', $message);

		$maxLength = 216;

		if ($maxLength <= strlen($message)) {
			$message = substr($message, 0, $maxLength - 1) . '…';
		}

		return $message;
	}
}
