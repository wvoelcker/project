<?php
namespace WillV\Project;

abstract class Environment {
	use Trait_AbstractTemplate;
	protected $data, $isActiveClosure, $requiredFields;

	protected function postSetUp() {
		list($data, $isActiveClosure) = func_get_args();
		$notSupplied = array_diff($this->requiredFields, array_keys($data));
		if (!empty($notSupplied)) {
			throw new \Exception("Missing fields: {".join(", ", $notSupplied)."}");
		}
		$this->data = $data;
		$this->isActiveClosure = $isActiveClosure;
	}

	public function isActive() {
		$closure = $this->isActiveClosure;
		return $closure();
	}

	public function get($key) {
		if (!isset($this->data[$key])) {
			throw new Exception("Data key '".$key."' not set");
		}
		return $this->data[$key];
	}

}