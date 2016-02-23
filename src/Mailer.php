<?php
namespace WillV\Project;
require_once __DIR__."/Mailer/OmniTI_Mailer.php";

abstract class Mailer {
	private $mailer, $charset = "utf-8";

	public function create() {
		$className = get_called_class();
		$mailer = new $className;
		$mailer->mailer = new \OmniTI_Mail_Mailer;

		return $mailer;
	}

	public function createFromMessageDetails($toName, $toAddress, $subject, $bodyText = "", $bodyHTML = "") {
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
		$this->mailer->setFrom($address, $display, $this->charset);
		return $this;
	}

	public function addRecipient($address, $display) {
		$this->mailer->addRecipient($address, $display, "To", null, $this->charset);
		return $this;
	}

	public function setSubject($subject) {
		$this->mailer->setSubject($subject, $this->charset);
		return $this;
	}

	public function setBodyText($body) {
		$this->mailer->setBodyText($body, $this->charset);
		return $this;
	}

	public function setBodyHTML($body) {
		$this->mailer->setBodyHTML($body, $this->charset);
		return $this;
	}

	public function embedImage($filename, $mimetype, $data) {
		return $this->mailer->embedImage($filename, $mimetype, $data);
	}

	public function send() {
		return $this->mailer->send();
	}

}
