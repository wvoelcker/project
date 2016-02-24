<?php
namespace WillV\Project;

class Validator {
	protected $fields;

	public function create($fields) {
		$validator = new Validator;
		$validator->fields = $fields;
		return $validator;
	}

	/**
	 * Private constructor, to enforce use of factory methods
	 **/
	private function __construct() {}

	public function setFields($fields) {
		$this->fields = $fields;
		return $this;
	}

	public function isValid($data, &$errors = null) {
		$foundErrors = array();

		if (empty($this->fields)) {
			throw new Exception("No valid definitions have been supplied");
		}

		// For some reason, defaulting to an array doesn't work as expected (instead, the variable defaults to null).
		// So default to null explicitly and set it to an array here.
		// TODO:WV:20160210:Get to the bottom of this.
		if (empty($errors)) {
			$errors = array();
		}

		foreach ($this->fields as $fieldname => $fielddetails) {

			if (!isset($data[$fieldname])) {
				if (!empty($fielddetails["required"])) {
					$foundErrors[$fieldname] = "This field is required";
				}
				continue;
			}

			if (empty($data[$fieldname])) {
				if (!empty($fielddetails["notempty"])) {
					$foundErrors[$fieldname] = "This field should not be empty";
				}
				continue;
			}

			if (!empty($fielddetails["allowedValues"]) and !in_array($data[$fieldname], $fielddetails["allowedValues"])) {
				$foundErrors[$fieldname] = "This field should have one of the following values: {".join(", ", $fielddetails["allowedValues"])."}";
			}

			if (!empty($fielddetails["customValidation"])) {
				$result = call_user_func_array($fielddetails["customValidation"], array($data[$fieldname]));

				if ($result === true) {
					continue;
				}

				if (is_string($result)) {
					$errmsg = $result;
				} else {
					$errmsg = "This field is invalid";
				}
				
				$foundErrors[$fieldname] = $errmsg;
			}
		}

		// Merge any validation errors found into the 'errors' array
		$errors = $foundErrors + $errors;

		// Return a boolean flag to show the result of the validation
		return empty($foundErrors);
	}

}