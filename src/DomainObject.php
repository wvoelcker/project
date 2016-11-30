<?php
namespace WillV\Project;

class DomainObject {
	use Trait_AbstractTemplate;
	protected $data = array(), $fields = array();

	protected function postSetUp() {
		$data = func_get_arg(0);

		// Make sure the 'id' field is present, because the data-mapper class expects it
		if (!isset($this->fields["id"])) {
			$this->fields["id"] = array("publicVersion" => true);
		}

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

	public function get($key) {
		$this->confirmValidField($key);
		if (!isset($this->data[$key])) {
			return null;
		}
		return $this->data[$key];
	}

	public function getForPublic() {
		$output = array();
		foreach ($this->fields as $fieldName => $fieldDetails) {
			if (empty($fieldDetails["publicVersion"])) {
				continue;
			}
			$value = (isset($this->data[$fieldName])?$this->data[$fieldName]:null);
			if (!empty($value) and !empty($fieldDetails["publicVersion"]["useBase64"])) {
				$value = base64_encode($value);
			}
			$output[$fieldName] = $value;
		}
		return (object)$output;
	}

	private function confirmValidField($fieldName) {
		if (!$this->isValidField($fieldName)) {
			throw new \Exception("Invalid field name");
		}
	}

	public function isValidField($fieldName) {
		return isset($this->fields[$fieldName]);
	}

	public function set($key, $value) {
		$this->confirmValidField($key);
		$this->data[$key] = $value;

		return $this;
	}

}