<?php declare(strict_types = 1);

namespace SpencerMortensen\Site\Errors;

use Exception;

class DataException extends Exception
{
	private $data;

	public function __construct (string $message = null, array $data = null, int $code = null)
	{
		parent::__construct($message, $code);

		$this->data = $data;
	}

	public function getData (): ?array
	{
		return $this->data;
	}
}
