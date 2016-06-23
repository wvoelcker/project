<?php
namespace WillV\Project;

abstract class Mailer {
	private $message, $haveFromAddress = false, $bodyText = null, $bodyHtml = null;

	static public function create() {
		$className = get_called_class();
		$mailer = new $className;
		$mailer->message = \Swift_Message::newInstance();

		return $mailer;
	}

	static public function createFromMessageDetails($toName, $toAddress, $subject, $bodyText = "", $bodyHTML = "") {
		$mailer = static::create();

		$mailer->addRecipient($toAddress, $toName);
		$mailer->setSubject($subject);

		if (!empty($bodyText)) {
			$mailer->setBodyText($bodyText);
		}

		if (!empty($bodyHTML)) {
			$mailer->setBodyHTML($bodyHTML);
		}

		return $mailer;
	}

	public function setFrom($address, $display) {
		$this->message->setFrom(array($address => $display));
		$this->haveFromAddress = true;
		return $this;
	}

	public function addRecipient($address, $display) {
		$this->message->addTo($address, $display);
		return $this;
	}

	public function setSubject($subject) {
		$this->message->setSubject($subject);
		return $this;
	}

	public function setBodyText($body) {
		$this->message->setBody($body);
		$this->bodyText = $body;
		return $this;
	}

	public function getBodyText() {
		return $this->bodyText;
	}

	public function setBodyHTML($body) {
		if (empty($this->bodyText)) {
			throw new \Exception("Please supply body text first");
		}
		$this->message->addPart($body, "text/html");
		$this->bodyHtml = $body;
		return $this;
	}

	public function getBodyHtml() {
		return $this->bodyHtml;
	}

	public function embedImage($filename, $mimetype, $data) {
		return $this->message->embed(new \Swift_Image($data, $filename, $mimetype));
	}

	public function attachFile($filename, $mimeType, $data) {
		return $this->message->attach(new \Swift_Attachment($data, $filename, $mimeType));
	}

	public function send() {
		if ($this->haveFromAddress == false) {
			throw new \Exception("Please supply a from address before sending the email");
		}

		// NB this uses the PHP mail() function - there are better transports available if required.
		// see http://swiftmailer.org/docs/sending.html
		$transport = \Swift_MailTransport::newInstance();

		$mailer = \Swift_Mailer::newInstance($transport);
		return $mailer->send($this->message);
	}

}
