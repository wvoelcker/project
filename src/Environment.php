<?php
namespace WillV\Project;

abstract class Environment {
	protected $data, $isActiveClosure, $requiredFields;

	static public function create($data, \Closure $isActiveClosure) {	
		$className = get_called_class();
		$environment = new $className($data, $isActiveClosure);
		return $environment;
	}

	private function __construct($data, \Closure $isActiveClosure) {
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