<?php
namespace WillV\Project;
require_once __DIR__."/OmniTI_Mailer.php";

abstract class Mailer {
	private $bodyText = null, $bodyHtml = null;
	private $fromAddress, $fromDisplayName, $recipients = array(), $subject;
	private $attachedFiles = array();
	private $mailLibrary = "OmniTI";
	private $message;

	static public function create() {
		$className = get_called_class();
		$mailer = new $className;

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

	public function setMailLibrary($newLibrary) {
		$this->mailLibrary = $newLibrary;
	}

	public function setFrom($address, $display = null) {
		$this->fromAddress = $address;
		if (empty($display)) {
			$display = $address;
		}
		$this->fromDisplayName = $display;
		return $this;
	}

	public function addRecipient($address, $display) {
		if (empty($display)) {
			$display = $address;
		}
		$this->recipients[$address] = $display;
		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}

	public function setBodyText($body) {
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
		$this->bodyHtml = $body;
		return $this;
	}

	public function embedImage($filename, $mimetype, $data) {
		if (empty($this->message)) {
			switch ($this->mailLibrary) {
				case "swiftMail":
					$this->addSwiftMailMessage();
					break;
				case "OmniTI":
					$this->addOmniTIMessage();
					break;
				default:
					throw new \Exception("Unknown mail library: ".$this->mailLibrary);
			}
		}
		switch ($this->mailLibrary) {
			case "swiftMail":
				return $this->message->embed(new \Swift_Image($data, $filename, $mimetype));
			case "OmniTI":
				return "cid:".$this->message->embedImage($filename, $mimetype, $data);
			default:
				throw new \Exception("Unknown mail library: ".$this->mailLibrary);
		}
	}

	public function attachFile($filename, $mimeType, $data) {
		$this->attachedFiles[] = array("filename" => $filename, "mimetype" => $mimetype, "data" => $data);
		return $this;
	}

	private function addSwiftMailMessage() {
		$this->message = \Swift_Message::newInstance();
	}

	private function addOmniTIMessage() {
		$this->message = new \OmniTI_Mail_Mailer;
	}

	public function getBodyHtml() {
		return $this->bodyHtml;
	}

	public function send() {

		if (empty($this->fromAddress)) {
			throw new \Exception("Please supply a from address before sending the email");
		}

		if (empty($this->recipients)) {
			throw new \Exception("Please supply at least one recipient");
		}

		if (empty($this->subject)) {
			throw new \Exception("Please supply a subject");
		}

		if (empty($this->bodyText)) {
			throw new \Exception("Please supply at least a text version of the message body");
		}

		switch ($this->mailLibrary) {
			case "swiftMail":
				return $this->sendWithSwiftMail();
			case "OmniTI":
				return $this->sendWithOmniTI();
			default:
				throw new \Exception("Unknown mail library: ".$this->mailLibrary);
		}
	}

	public function sendWithSwiftMail() {
		if (empty($this->message)) {
			$this->addSwiftMailMessage();
		}

		$this->message->setFrom(array($this->fromAddress => $this->fromDisplayName));
		foreach ($this->recipients as $address => $display) {
			$this->message->addTo($address, $display);
		}

		$this->message->setSubject($this->subject);
		$this->message->setBody($this->bodyText);
		if (!empty($this->bodyHtml)) {
			$this->message->addPart($this->bodyHtml, "text/html");
		}

		if (!empty($this->embeddedImages)) {
			foreach ($this->embeddedImages as $embeddedImage) {
				$this->message->embed(new \Swift_Image($embeddedImage["data"], $embeddedImage["filename"], $embeddedImage["mimetype"]));
			}
		}

		if (!empty($this->attachedFiles)) {
			foreach ($this->attachedFiles as $attachedFile) {
				$this->message->embed(new \Swift_Attachment($attachedFile["data"], $attachedFile["filename"], $attachedFile["mimetype"]));
			}
		}

		// NB this uses the PHP mail() function - there are better transports available if required.
		// see http://swiftmailer.org/docs/sending.html
		$transport = \Swift_MailTransport::newInstance();

		$mailer = \Swift_Mailer::newInstance($transport);
		return $mailer->send($this->message);
	}

	public function sendWithOmniTI() {
		if (empty($this->message)) {
			$this->addOmniTIMessage();
		}
		$charset = "utf-8";

		$this->message->setFrom($this->fromAddress, $this->fromDisplayName, $charset);

		foreach ($this->recipients as $address => $display) {
			$this->message->addRecipient($address, $display, "To", null, $charset);
		}
		$this->message->setSubject($this->subject, $charset);
		$this->message->setBodyText($this->bodyText, $charset);

		if (!empty($this->bodyHtml)) {
			$this->message->setBodyHTML($this->bodyHtml, $charset);
		}

		if (!empty($this->embeddedImages)) {
			foreach ($this->embeddedImages as $embeddedImage) {
				$this->mailer->embedImage($embeddedImage["filename"], $embeddedImage["mimetype"], $embeddedImage["data"]);
			}
		}

		if (!empty($this->attachedFiles)) {
			throw new \Exception("Cannot yet attach files to OmniTI messages.  Please use SwiftMail, or an alternative if available.");
		}

		$this->message->send();
	}
}
