<?php declare(strict_types = 1);

namespace SpencerMortensen\Site;

class Sender
{
	private $address;
	private $user;
	private $pass;
	private $connection;

	public function __construct (array $settings)
	{
		$this->address = $this->getAddress($settings['host'], $settings['port'], $settings['secure']);
		$this->user = $settings['username'];
		$this->pass = $settings['password'];
		$this->connection = null;
	}

	private function getAddress (string $host, int $port, string $secure): string
	{
		$address = "{$host}:{$port}";

		if ($secure === 'ssl') {
			$address = "ssl://{$address}";
		}

		return $address;
	}

	// TODO: do this asynchronously:
	public function send (array $message): void
	{
		$timeout = 15;
		$this->connection = stream_socket_client($this->address, $errorCode, $errorMessage, $timeout, STREAM_CLIENT_CONNECT);
		stream_set_timeout($this->connection, $timeout, 0);

		$this->sendMessage($message);

		fclose($this->connection);
		$this->connection = null;
	}

	private function sendMessage (array $message): bool
	{
		if (!$this->getReady()) {
			return false;
		}

		$this->write('EHLO localhost');

		while ($this->get('250-'));

		$credentials = base64_encode("{$this->user}\0{$this->user}\0{$this->pass}");
		$this->write("AUTH PLAIN {$credentials}");

		if (!$this->get('235 ')) {
			return false;
		}

		$from = self::getBasicEmail($message['from']);
		$this->write("MAIL FROM:<{$from}>");

		if (!$this->get('250 ')) {
			return false;
		}

		$to = self::getBasicEmail($message['to']);
		$this->write("RCPT TO:<{$to}>");

		if (!$this->get('250 ')) {
			return false;
		}

		$this->write('DATA');

		if (!$this->get('354 ')) {
			return false;
		}

		$data = $this->newData($message);

		$this->write($data);
		$this->write('.');

		if (!$this->get('250 ')) {
			return false;
		}

		$this->write('QUIT');

		if (!$this->get('221 ')) {
			return false;
		}

		return true;
	}

	private function getReady (): bool
	{
		return $this->read($response)
			&& (preg_match('~^220 [^ ]+ ESMTP~DusX', $response) === 1);
	}

	private function get (string $prefix): bool
	{
		return $this->read($response)
			&& (strncmp($response, $prefix, strlen($prefix)) === 0);
	}

	private function write (string $text): void
	{
		fwrite($this->connection, "{$text}\r\n");
	}

	private function read (&$line): bool
	{
		$response = fgets($this->connection);

		if ($response === false) {
			return false;
		}

		$line = substr($response, 0, -2);
		return true;
	}

	private function newData (array $message): string
	{
		$dateText = date('r');
		$messageId = $this->getMessageId($message);
		$content = $this->getContent($message);

		$from = self::getNamedEmail($message['from']);
		$to = self::getNamedEmail($message['to']);

		$text = <<<"EOS"
MIME-Version: 1.0
Date: {$dateText}
Subject: {$message['subject']}
From: {$from}
To: {$to}
Message-ID: <{$messageId}>
{$content}
EOS;

		return str_replace("\n", "\r\n", $text);
	}

	private function getContent (array $message): string
	{
		if (isset($message['text'], $message['html'])) {
			$versions = [
				$this->getTextContent($message['text']),
				$this->getHtmlContent($message['html'])
			];

			return $this->getAlternativeContent($versions);
		}

		if (isset($message['text'])) {
			return $this->getTextContent($message['text']);
		}

		if (isset($message['html'])) {
			return $this->getHtmlContent($message['html']);
		}
	}

	private function getAlternativeContent (array $versions): string
	{
		$boundary = 'JGzQohN2Cqs3';

		$contents = [];

		foreach ($versions as $version) {
			$contents[] = "--{$boundary}\n{$version}";
		}

		$contents[] = "--{$boundary}--";

		$content = implode("\n\n", $contents);

		return <<<"EOS"
Content-Type: multipart/alternative; boundary="{$boundary}"
{$content}
EOS;
	}

	private function getTextContent (string $content): string
	{
		return <<<"EOS"
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

{$content}
EOS;
	}

	private function getHtmlContent (string $content): string
	{
		return <<<"EOS"
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

{$content}
EOS;
	}

	private function getMessageId (array $message): string
	{
		$email = self::getBasicEmail($message['from']);

		$local = $this->getLocalId();
		$domain = $this->getDomain($email);

		return "{$local}@{$domain}";
	}

	private function getLocalId (): string
	{
		$time = time();
		$seed = hrtime(false)[1];

		return self::encode($time, 6) . self::encode($seed, 5);
	}

	private static function encode (int $seed, int $n): string
	{
		$numbers = [];

		for ($i = 0; $i < $n; ++$i) {
			$numbers[] = $seed & 0b111111;
			$seed = $seed >> 6;
		}

		$numbers = array_reverse($numbers);
		return Encoder::encode($numbers);
	}

	private function getDomain (string $email): string
	{
		$i = strrpos($email, '@');

		return substr($email, $i + 1);
	}

	private static function getBasicEmail ($contact): string
	{
		if (is_array($contact)) {
			return $contact['email'];
		}

		return $contact;
	}

	private static function getNamedEmail ($contact): string
	{
		if (is_array($contact)) {
			return "{$contact['name']} <{$contact['email']}>";
		}

		return $contact;
	}
}
