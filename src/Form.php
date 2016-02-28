<?php
namespace WillV\Project;

abstract class Form {
	use Trait_AbstractTemplate;
	protected $fields;

	public function getFields() {
		return $this->fields;
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

			// Check for empty-but-not-supposed to be (NB do this before the isset check, or "notempty" fields that are not set will pass validation)
			if (empty($data[$fieldname])) {
				if (!empty($fielddetails["notempty"])) {
					$foundErrors[$fieldname] = "This field should not be empty";
				}

				// NB no 'continue' here because 0 is an empty value, but can also be processed by the rest of this function
			}

			if (!isset($data[$fieldname])) {
				if (!empty($fielddetails["required"])) {
					$foundErrors[$fieldname] = "This field is required";
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