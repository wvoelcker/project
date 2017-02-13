<?php
namespace WillV\Project;

abstract class Dataset {
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
			if (!empty($fielddetails["notempty"]) and empty($data[$fieldname])) {
				$foundErrors[$fieldname] = "This field should not be empty";
				// NB no 'continue' here because 0 is an empty value, but can also be processed by the rest of this function
			}

			if (!empty($fielddetails["required"]) and !isset($data[$fieldname])) {
				$foundErrors[$fieldname] = "This field is required";
				continue;
			}

			// Stop validating here if not provided
			if (!isset($data[$fieldname])) {
				continue;
			}

			$validators = array();

			if (!empty($fielddetails["allowedValues"])) {
				$validators[] = function($value, $dataset) use ($fielddetails) {
					if (!in_array($value, $fielddetails["allowedValues"])) {
						return "This field should have one of the following values: {".join(", ", $fielddetails["allowedValues"])."}";
					}
				};
			}

			if (!empty($fielddetails["validate12HrTime"])) {
				$validators[] = array($this, "validate12HrTime");
			}

			if (!empty($fielddetails["validateDateUK"])) {
				$validators[] = array($this, "validateDateUK");
			}

			if (!empty($fielddetails["validateDateMySQL"])) {
				$validators[] = array($this, "validateDateMySQL");
			}

			if (!empty($fielddetails["validateDateISO8601"])) {
				$validators[] = array($this, "validateDateISO8601");
			}

			if (!empty($fielddetails["validateEmailAddress"])) {
				$validators[] = array($this, "validateEmailAddress");
			}

			if (!empty($fielddetails["customValidation"])) {
				$validators[] = $fielddetails["customValidation"];
			}

			foreach ($validators as $validator) {
				$result = $this->doValidationStep($validator, $data, $fieldname, $foundErrors);

				if ($result === false) {
					continue 2;
				}
			}
		}

		// Merge any validation errors found into the 'errors' array
		$errors = $foundErrors + $errors;

		// Return a boolean flag to show the result of the validation
		return empty($foundErrors);
	}

	protected function validate12HrTime($value) {
		if (!preg_match("/^([0-9]+):([0-9]+)([ap]m)$/", $time, $m)) {
			return "Expected a time in the format h:m(am/pm)";
		}
		if ($m[1] > 12) {
			return "The hour cannot be more than 12";
		}
		if ($m[2] > 59) {
			return "The number of minutes cannot be more than 59";
		}

		return true;
	}

	protected function validateDateUK($value) {
		$result = preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/", $value, $m);

		if ($result == true) {
			if (checkdate($m[2], $m[3], $m[1])) {
				return true;
			} else {
				return "Not a valid date";
			}
		}

		return "Not a date in the format dd-mm-yyyy";
	}

	protected function validateDateMySQL($value) {
		$result = preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $value, $m);

		if ($result == true) {
			if (checkdate($m[2], $m[3], $m[1])) {
				return true;
			} else {
				return "Not a valid date";
			}
		}

		return "Not a date in the format yyyy-mm-dd";
	}

	protected function validateDateISO8601($value) {
		$result = preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(([+\-])([0-9]{2}):([0-9]{2}))?$/", $value, $m);

		if ($result == false) {
			$errmsg = "Not a date in the format yyyy-mm-ddThh:mm:ss(+/-hh:mm)";
		} else {
			if (empty($m[8])) {
				$minutesOffset = 0;
				$offsetIsPositive = true;
			} else {
				$minutesOffset = (($m[9] * 60) + $m[10]);
				if ($m[8] == "-") {
					$minutesOffset *= -1;
				}
			}

			if (strtotime($value) != gmmktime($m[4], $m[5] - $minutesOffset, $m[6], $m[2], $m[3], $m[1])) {
				$errmsg = "Not a valid date";
			}
		}

		if (!empty($errmsg)) {
			return $errmsg;
		}

		return true;
	}

	private function doValidationStep($validationFunction, $data, $fieldname, &$foundErrors) {
		$validatorArguments = array(
			$data[$fieldname]
		);

		// Apart from in-built PHP functions, pass in the whole data-set as well
		// (don't do it for in-built functions, to avoid PHP 'too many arguments warning)
		if (!is_string($validationFunction)) {
			$validatorArguments[] = $data;
		}
		$result = call_user_func_array($validationFunction, $validatorArguments);

		if ($result === true) {
			return true;
		} elseif (is_string($result)) {
			$errmsg = $result;
		} else {
			$errmsg = "This field is invalid";
		}

		$foundErrors[$fieldname] = $errmsg;
		return false;
	}

	protected function validateEmailAddress($value) {
		$result = filter_var($data[$fieldname], FILTER_VALIDATE_EMAIL);

		if (!$result) {
			return "Not a valid email address";
		}

		return true;
	}

}
