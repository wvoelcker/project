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
				continue;
			}

			if (!empty($fielddetails["validateDateMySQL"])) {
				$result = preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $data[$fieldname], $m);

				if ($result == true and checkdate($m[2], $m[3], $m[1])) {
					$errmsg = null;
				} elseif ($result == false) {
					$errmsg = "Not a date in the format yyyy-mm-dd";
				} else {
					$errmsg = "Not a valid date";
				}

				if (!empty($errmsg)) {
					$foundErrors[$fieldname] = $errmsg;
					continue;
				}
			}

			if (!empty($fielddetails["validateDateISO8601"])) {
				$result = preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(([+\-])([0-9]{2}):([0-9]{2}))?$/", $data[$fieldname], $m);

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

					if (strtotime($data[$fieldname]) != gmmktime($m[4], $m[5] - $minutesOffset, $m[6], $m[2], $m[3], $m[1])) {
						$errmsg = "Not a valid date";
					}
				}

				if (!empty($errmsg)) {
					$foundErrors[$fieldname] = $errmsg;
					continue;
				}
			}

			if (!empty($fielddetails["validateEmailAddress"])) {
				$result = filter_var($data[$fieldname], FILTER_VALIDATE_EMAIL);

				if (!$result) {
					$foundErrors[$fieldname] = "Not a valid email address";
					continue;
				}
			}

			if (!empty($fielddetails["customValidation"])) {
				$result = call_user_func_array($fielddetails["customValidation"], array($data[$fieldname]));

				if ($result === true) {
					$errmsg = null;
				} elseif (is_string($result)) {
					$errmsg = $result;
				} else {
					$errmsg = "This field is invalid";
				}

				if (!empty($errmsg)) {
					$foundErrors[$fieldname] = $errmsg;
					continue;
				}
			}
		}

		// Merge any validation errors found into the 'errors' array
		$errors = $foundErrors + $errors;

		// Return a boolean flag to show the result of the validation
		return empty($foundErrors);
	}

}