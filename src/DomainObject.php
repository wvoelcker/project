<?php
namespace WillV\Project;

abstract class DomainObject {
	use Trait_AbstractTemplate;
	protected $data = array(), $dataSetName;
	private $dataSet;

	protected function postSetUp() {
		$data = func_get_arg(0);

		// Validate supplied data
		$dataSetName = $this->dataSetName;
		$this->dataSet = $dataSetName::create();
		$this->fields = $this->dataSet->getFields();
		if (!isset($this->fields["id"])) {
			throw new \Exception("No ID field in supplied dataset");
		}
		if (!$this->dataSet->isValid($data, $validationErrors)) {
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
			if (empty($fieldDetails["visibility"]) or ($fieldDetails["visibility"] != "public")) {
				continue;
			}

			if (!isset($this->data[$fieldName])) {
				$value = null;
			} else {
				$value = $this->data[$fieldName];
				if (isset($fieldDetails["formatForPublic"])) {
					$value = call_user_func_array($fieldDetails["formatForPublic"], array($value));
				}
			}

			$output[$fieldName] = $value;
		}
		return (object)$output;
	}

	private function confirmValidField($fieldName) {
		if (!$this->isValidField($fieldName)) {
			throw new \Exception("Invalid field name '".$fieldName."'");
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

	public function setAnyIn($dataSet) {
		foreach ($dataSet as $key => $value) {
			if ($this->isValidField($key) and !empty($this->fields[$key]["allowDirectChange"])) {
				$this->set($key, $value);
			}
		}
		return $this;
	}

}
