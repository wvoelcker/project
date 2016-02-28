<?php
namespace WillV\Project;

abstract class ContactList {
	use Trait_AbstractTemplate;
	protected $contacts = array();

	public function addContact($contactName, $contactDetails) {
		if (isset($this->contacts[$contactName])) {
			throw new \Exception("Contact '".$contactName."' already added");
		}
		$this->contacts[$contactName] = $contactDetails;
	}

	public function getContact($contactName) {
		if (!isset($this->contacts[$contactName])) {
			throw new Exception("Contact '".$contactName."' not found");
		}
		return $this->contacts[$contactName];
	}

}