<?php
namespace WillV\Project;

abstract class ContactList {
	protected $contacts = array();

	static public function create() {
		$className = get_called_class();
		$list = new $className;
		$list->addContacts();

		return $list;
	}

	public function addContact($contactName, $contactDetails) {
		if (isset($this->contacts[$contactName])) {
			throw new \Exception("Contact '".$contactName."' already added");
		}
		$this->contacts[$contactName] = $contactDetails;
	}

	abstract protected function addContacts();

	public function getContact($contactName) {
		if (!isset($this->contacts[$contactName])) {
			throw new Exception("Contact '".$contactName."' not found");
		}
		return $this->contacts[$contactName];
	}

}