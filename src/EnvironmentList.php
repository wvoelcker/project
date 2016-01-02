<?php
/*
 * TODO:WV:20151218:Document how to use this and Environment.php
 */
namespace WillV\Project;

abstract class EnvironmentList {
	protected $environments = array();

	static public function create() {
		$className = get_called_class();
		$list = new $className;
		$list->addEnvironments();

		return $list;
	}

	public function addEnvironment($environmentName, Environment $environment) {
		if (isset($this->environments[$environmentName])) {
			throw new \Exception("Environment '".$environmentName."' already added");
		}
		$this->environments[$environmentName] = $environment;
	}

	abstract protected function addEnvironments();

	public function findActiveEnvironment() {
		foreach ($this->environments as $environment) {
			if ($environment->isActive()) {
				return $environment;
			}
		}
	}

	public function getEnvironment($environmentName) {
		if (!isset($this->environments[$environmentName])) {
			throw new Exception("Environment '".$environmentName."' not found");
		}
		return $this->environments[$environmentName];
	}

}