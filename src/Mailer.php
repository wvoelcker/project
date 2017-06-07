<?php
namespace WillV\Project;
require_once __DIR__."/OmniTI_Mailer.php";

abstract class Mailer {
	private $bodyText = null, $bodyHtml = null;
	private $fromAddress, $fromDisplayName, $recipients = array(), $subject;
	private $embeddedImages = array(), $attachedFiles = array();
	private $mailLibrary = "OmniTI", $smtpDetails = array();

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

	public function setSmtpDetails($smtpDetails) {
		$this->smtpDetails = $smtpDetails;
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
		$token = "wv-project-embedded-image-".count($this->embeddedImages);
		$this->embeddedImages[$token] = array("filename" => $filename, "mimetype" => $mimetype, "data" => $data);
		return $token;
	}

	public function attachFile($filename, $mimetype, $data) {
		$this->attachedFiles[] = array("filename" => $filename, "mimetype" => $mimetype, "data" => $data);
		return $this;
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
		$message = \Swift_Message::newInstance();

		$message->setFrom(array($this->fromAddress => $this->fromDisplayName));
		foreach ($this->recipients as $address => $display) {
			$message->addTo($address, $display);
		}

		if (!empty($this->attachedFiles)) {
			foreach ($this->attachedFiles as $attachedFile) {
				$message->embed(new \Swift_Attachment($attachedFile["data"], $attachedFile["filename"], $attachedFile["mimetype"]));
			}
		}

		$message->setSubject($this->subject);

		// Note that this part has to be added via addPart (not "setBody") or Swiftmail will not render the email correctly in the case that it includes embedded images
		$message->addPart($this->bodyText, "text/plain");


		// NB should do this before adding the HTML body to the message, as the latter needs to be changed here
		if (!empty($this->embeddedImages)) {
			foreach ($this->embeddedImages as $token => $embeddedImage) {
				$replacementToken = $message->embed(new \Swift_Image($embeddedImage["data"], $embeddedImage["filename"], $embeddedImage["mimetype"]));
				$this->swapImageSRC($token, $replacementToken);
			}
		}

		// NB HTML part must be added after embedding any images
		if (!empty($this->bodyHtml)) {
			$message->addPart($this->bodyHtml, "text/html");
		}

		// Find a suitable transport
		// More info: http://swiftmailer.org/docs/sending.html
		if (empty($this->smtpDetails)) {
			$transport = \Swift_MailTransport::newInstance();
		} else {

			if (empty($this->smtpDetails["server"])) {
				throw new \Exception("No server");
			}

			if (empty($this->smtpDetails["port"])) {
				throw new \Exception("No port");
			}

			if (empty($this->smtpDetails["security"]) or !in_array($this->smtpDetails["security"], array("ssl", "tls"))) {
				throw new \Exception("Security should be SSL or TLS");
			}

			$security = (empty($this->smtpDetails["security"])?null:$this->smtpDetails["security"]);
			$transport = \Swift_SmtpTransport::newInstance($this->smtpDetails["server"], $this->smtpDetails["port"], $security);

			if (!empty($this->smtpDetails["username"]) and !empty($this->smtpDetails["password"])) {
				$transport->setUsername($this->smtpDetails["username"]);
				$transport->setPassword($this->smtpDetails["password"]);
			}
		}

		$mailer = \Swift_Mailer::newInstance($transport);
		return $mailer->send($message);
	}

	public function sendWithOmniTI() {
		$message = new \OmniTI_Mail_Mailer;
		$charset = "utf-8";

		$message->setFrom($this->fromAddress, $this->fromDisplayName, $charset);

		foreach ($this->recipients as $address => $display) {
			$message->addRecipient($address, $display, "To", null, $charset);
		}
		$message->setSubject($this->subject, $charset);
		$message->setBodyText($this->bodyText, $charset);

		if (!empty($this->attachedFiles)) {
			throw new \Exception("Cannot yet attach files to OmniTI messages.  Please use SwiftMail, or an alternative if available.");
		}

		if (!empty($this->smtpDetails)) {
			throw new \Exception("Cannot yet send OmniTI messages via SMTP.  Please use SwiftMail, or an alternative if available.");
		}

		// NB should do this before adding the HTML body to the message, as the latter needs to be changed here
		if (!empty($this->embeddedImages)) {
			foreach ($this->embeddedImages as $token => $embeddedImage) {
				$cid = $message->embedImage($embeddedImage["filename"], $embeddedImage["mimetype"], $embeddedImage["data"]);
				$replacementToken = "cid:".$cid;
				$this->swapImageSRC($token, $replacementToken);
			}
		}

		// NB HTML part must be added after embedding any images
		if (!empty($this->bodyHtml)) {
			$message->setBodyHTML($this->bodyHtml, $charset);
		}

		$message->send();
	}

	private function swapImageSRC($old, $new) {
		if (!empty($this->bodyHtml)) {
			$this->bodyHtml = str_replace(
				array("src='".$old."'", "src=\"".$old."\""),
				array("src='".$new."'", "src=\"".$new."\""),
				$this->bodyHtml
			);
		}
	}
}
