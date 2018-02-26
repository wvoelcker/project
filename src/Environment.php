<?php
namespace WillV\Project;

abstract class Environment {
	use Trait_AbstractTemplate;
	protected $data, $isActiveClosure, $requiredFields = array();

	protected function postSetUp() {

		// Unpack arguments
		$args = func_get_args();
		$numArgs = count($args);
		switch ($numArgs) {
			case 2:
				list($data, $isActiveClosure) = $args;
				break;
			case 3:
				list($data, $dataFiles, $isActiveClosure) = $args;
				break;
			default:
				throw new \Exception("Unexpected number of arguments");
		}

		// Extract extra data from data-files if any were specified
		if (!empty($dataFiles)) {
			foreach ($dataFiles as $dataFile) {
				if (!file_exists($dataFile)) {
					throw new \Exception("Data file not found: ".$dataFile);
				}

				$data += json_decode(file_get_contents($dataFile), true);
			}
		}

		if (!is_array($this->requiredFields)) {
			throw new \Exception("requiredFields is not an array");
		}

		// Make sure all required data was provided
		$notSupplied = array_diff($this->requiredFields, array_keys($data));
		if (!empty($notSupplied)) {
			throw new \Exception("Missing fields: {".join(", ", $notSupplied)."}");
		}

		// Populate class properties
		$this->data = $data;
		$this->isActiveClosure = $isActiveClosure;
	}

	public function isActive() {
		$closure = $this->isActiveClosure;
		return $closure();
	}

	public function get($key) {
		if (!isset($this->data[$key])) {
			throw new \Exception("Data key '".$key."' not set");
		}
		return $this->data[$key];
	}

}