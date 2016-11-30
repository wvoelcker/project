<?php
namespace WillV\Project;

class DomainObject {
	use Trait_AbstractTemplate;
	protected $data = array(), $fields = array();

	protected function postSetUp() {
		$data = func_get_arg(0);

		$validationErrors = array();
		foreach ($this->fields as $fieldname => $fieldDetails) {
			if (!empty($fieldDetails["required"]) and !isset($data[$fieldname])) {
				$validationErrors[$fieldname] = "Not supplied";
			} elseif (!empty($fieldDetails["notempty"]) and empty($data[$fieldname])) {
				$validationErrors[$fieldname] = "Cannot be empty";
			}
		}

		if (!empty($validationErrors)) {
			throw new \Exception("Problems with supplied data: ".json_encode($validationErrors));
		}

		$this->data = $data;
	}

}